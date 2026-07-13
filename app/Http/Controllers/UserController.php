<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\BranchHeadAssignment;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Support\BranchAdminPermissions;
use App\Support\BranchScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['role', 'branch', 'employee'])->orderBy('name');

        // Branch Administration — strict branch-wise filtering. A
        // branch-scoped actor (branch_head/branch_user) only ever sees their
        // own branch's users; a Super Admin always sees only the currently
        // selected branch's users (switchable via the Branch Switcher —
        // there is no "All Branches" view). Only accounts that predate this
        // module (unscoped, currentBranchId() null) keep today's unscoped
        // behavior with an optional ad-hoc branch filter.
        $query = BranchScope::scopeQuery($query);

        if (BranchScope::currentBranchId() === null && $request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(fn($q) => $q->where('name', 'like', $s)->orWhere('email', 'like', $s));
        }
        if ($request->filled('role_id')) {
            $query->where('role_id', $request->role_id);
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active === '1');
        }

        $users = $query->paginate(20)->withQueryString();
        $roles = Role::orderBy('name')->get();
        $branches = BranchScope::currentBranchId() === null ? Branch::active()->orderBy('name')->get() : collect();

        return view('users.index', compact('users', 'roles', 'branches'));
    }

    public function create()
    {
        return view('users.create', $this->formData(auth()->user()));
    }

    public function store(Request $request)
    {
        $actor = auth()->user();
        $this->ensureManageUsersPermissionIfBranchScoped($actor);

        $data = $this->validateUser($request, $actor);
        BranchScope::assertBranchIsActive($data['branch_id'] ?? null);
        $data['password'] = Hash::make($data['password']);
        unset($data['password_confirmation']);
        $data['created_by'] = $actor->id;

        $user = User::create($data);

        // Creating a user with User Type = Branch Head is a real Branch Head
        // Assignment (same single-active-head-per-branch guarantee, same
        // Assigned By/Date trail) — not a separate, disconnected code path.
        if ($user->user_type === 'branch_head') {
            $assignment = BranchHeadAssignment::assign([
                'branch_id' => $user->branch_id,
                'user_id' => $user->id,
                'effective_from' => now()->toDateString(),
                'remarks' => $data['remarks'] ?? null,
            ], $actor->id);
            AuditLog::write($actor->id, 'assign', 'branch_head_assignments', $assignment->id, null, ['user_id' => $user->id, 'branch_id' => $user->branch_id], $user->branch_id);
        }

        AuditLog::write(
            $actor->id, 'create', 'users', $user->id, null,
            $user->only(['name', 'role_id', 'user_type', 'branch_id', 'is_active', 'remarks']),
            $user->branch_id
        );

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $this->assertSameBranchIfScoped(auth()->user(), $user);

        return view('users.edit', array_merge(['user' => $user], $this->formData(auth()->user())));
    }

    public function update(Request $request, User $user)
    {
        $actor = auth()->user();
        $this->ensureManageUsersPermissionIfBranchScoped($actor);
        $this->assertSameBranchIfScoped($actor, $user);

        $data = $this->validateUser($request, $actor, $user->id);

        if ($request->filled('password')) {
            $request->validate(['password' => ['confirmed', Password::min(8)]]);
            $data['password'] = Hash::make($request->password);
        } else {
            unset($data['password']);
        }
        unset($data['password_confirmation']);
        $data['updated_by'] = $actor->id;

        $oldValues = $user->only(['name', 'role_id', 'user_type', 'branch_id', 'is_active', 'is_locked', 'remarks']);
        $roleChanged = (int) $user->role_id !== (int) ($data['role_id'] ?? $user->role_id);
        $wasBranchHead = $user->user_type === 'branch_head';
        $oldBranchId = $user->branch_id;

        $user->update($data);

        // Same Branch Head Assignment sync as store() — becoming, changing
        // branch as, or ceasing to be a Branch Head all route through the
        // one shared assign()/release() mechanism, whichever screen triggered it.
        $isBranchHeadNow = $user->user_type === 'branch_head';
        if ($isBranchHeadNow && (! $wasBranchHead || $oldBranchId !== $user->branch_id)) {
            $assignment = BranchHeadAssignment::assign([
                'branch_id' => $user->branch_id,
                'user_id' => $user->id,
                'effective_from' => now()->toDateString(),
                'remarks' => $data['remarks'] ?? null,
            ], $actor->id);
            AuditLog::write($actor->id, 'assign', 'branch_head_assignments', $assignment->id, ['branch_id' => $oldBranchId], ['branch_id' => $user->branch_id], $user->branch_id);
        } elseif ($wasBranchHead && ! $isBranchHeadNow) {
            BranchHeadAssignment::release($user->id, $actor->id);
            AuditLog::write($actor->id, 'deactivate', 'branch_head_assignments', $user->id, ['branch_id' => $oldBranchId], null, $oldBranchId);
        }

        AuditLog::write(
            $actor->id, 'update', 'users', $user->id, $oldValues,
            $user->only(['name', 'role_id', 'user_type', 'branch_id', 'is_active', 'is_locked', 'remarks']),
            $user->branch_id
        );

        if ($roleChanged) {
            AuditLog::write($actor->id, 'role_assignment', 'users', $user->id, ['role_id' => $oldValues['role_id']], ['role_id' => $user->role_id], $user->branch_id);
        }

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $actor = auth()->user();
        $this->ensureManageUsersPermissionIfBranchScoped($actor);
        $this->assertSameBranchIfScoped($actor, $user);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($user->user_type === 'branch_head') {
            BranchHeadAssignment::release($user->id, $actor->id);
        }

        $user->delete();

        AuditLog::write($actor->id, 'delete', 'users', $user->id, null, null, $user->branch_id);

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }

    public function activate(User $user)
    {
        $actor = auth()->user();
        $this->ensureManageUsersPermissionIfBranchScoped($actor);
        $this->assertSameBranchIfScoped($actor, $user);

        $user->update(['is_active' => true, 'is_locked' => false, 'updated_by' => $actor->id]);
        AuditLog::write($actor->id, 'activate', 'users', $user->id, null, null, $user->branch_id);

        return back()->with('success', 'User activated.');
    }

    public function deactivate(User $user)
    {
        $actor = auth()->user();
        $this->ensureManageUsersPermissionIfBranchScoped($actor);
        $this->assertSameBranchIfScoped($actor, $user);
        abort_if($user->id === $actor->id, 403, 'You cannot deactivate your own account.');

        $user->update(['is_active' => false, 'updated_by' => $actor->id]);
        AuditLog::write($actor->id, 'deactivate', 'users', $user->id, null, null, $user->branch_id);

        return back()->with('success', 'User deactivated.');
    }

    public function lock(User $user)
    {
        $actor = auth()->user();
        $this->ensureManageUsersPermissionIfBranchScoped($actor);
        $this->assertSameBranchIfScoped($actor, $user);
        abort_if($user->id === $actor->id, 403, 'You cannot lock your own account.');

        $user->update(['is_locked' => true, 'updated_by' => $actor->id]);
        AuditLog::write($actor->id, 'lock', 'users', $user->id, null, null, $user->branch_id);

        return back()->with('success', 'User locked.');
    }

    public function unlock(User $user)
    {
        $actor = auth()->user();
        $this->ensureManageUsersPermissionIfBranchScoped($actor);
        $this->assertSameBranchIfScoped($actor, $user);

        $user->update(['is_locked' => false, 'updated_by' => $actor->id]);
        AuditLog::write($actor->id, 'unlock', 'users', $user->id, null, null, $user->branch_id);

        return back()->with('success', 'User unlocked.');
    }

    public function permissions(User $user)
    {
        $user->load('role');
        return view('users.permissions', compact('user'));
    }

    public function updatePermissions(Request $request, User $user)
    {
        // Permissions managed via role-based access control
        return redirect()->route('users.index')->with('success', 'Permissions updated.');
    }

    /**
     * Branch Administration adds no new gate for Super Admin or for accounts
     * that predate this module (BranchScope::isBranchScopedUser() is false
     * for both) — only an actual branch_head/branch_user actor needs the
     * "Manage Users" action permission from their role's grant.
     */
    private function ensureManageUsersPermissionIfBranchScoped(User $actor): void
    {
        if (BranchScope::isBranchScopedUser()) {
            abort_unless(BranchAdminPermissions::canManageUsers($actor), 403,
                'You do not have the "Manage Users" permission for your role.');
        }
    }

    private function assertSameBranchIfScoped(User $actor, User $target): void
    {
        if (BranchScope::isBranchScopedUser()) {
            abort_unless($target->branch_id === $actor->branch_id, 403, 'You can only manage users in your own branch.');
        }
    }

    private function formData(User $actor): array
    {
        $isSuperAdmin = $actor->isSuperAdmin();
        $isBranchScoped = BranchScope::isBranchScopedUser();
        $currentBranchId = BranchScope::currentBranchId();

        // A Super Admin's Branch field is locked to the currently selected
        // branch too (switch branches via the Branch Switcher to manage
        // users in a different one) — never a free pick from every branch.
        $branches = $isBranchScoped
            ? Branch::where('id', $actor->branch_id)->get()
            : ($currentBranchId ? Branch::where('id', $currentBranchId)->get() : Branch::active()->orderBy('name')->get());

        $userTypeOptions = $isSuperAdmin
            ? ['' => '— None —', 'super_admin' => 'Super Admin', 'branch_head' => 'Branch Head', 'branch_user' => 'Branch User']
            : ($isBranchScoped ? ['branch_user' => 'Branch User'] : ['' => '— None —']);

        // A Branch Head may only assign roles explicitly tagged as applicable
        // to Branch User accounts — never super_admin, never any other role
        // beyond their permitted scope.
        $roles = Role::where('is_active', true)
            ->when(! $isSuperAdmin, fn($q) => $q->where('name', '!=', 'super_admin'))
            ->when($isBranchScoped, fn($q) => $q->whereJsonContains('applicable_user_types', 'branch_user'))
            ->orderBy('display_name')
            ->get();

        $employees = BranchScope::scopeQuery(Employee::orderBy('first_name'))->get(['id', 'employee_code', 'first_name', 'last_name', 'branch_id']);

        return [
            'isSuperAdmin' => $isSuperAdmin,
            'isBranchScoped' => $isBranchScoped,
            'branches' => $branches,
            'userTypeOptions' => $userTypeOptions,
            'roles' => $roles,
            'employees' => $employees,
            'lockedBranchId' => $isBranchScoped ? $actor->branch_id : ($isSuperAdmin ? $currentBranchId : null),
        ];
    }

    private function validateUser(Request $request, User $actor, ?int $ignoreId = null): array
    {
        $isSuperAdmin = $actor->isSuperAdmin();
        $isBranchScoped = BranchScope::isBranchScopedUser();

        $allowedUserTypes = match (true) {
            $isSuperAdmin => ['', 'super_admin', 'branch_head', 'branch_user'],
            $isBranchScoped => ['branch_user'],
            default => [''],
        };

        $rules = [
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'max:255', 'unique:users,email' . ($ignoreId ? ",$ignoreId" : '')],
            'username'     => ['required', 'string', 'max:50', 'unique:users,username' . ($ignoreId ? ",$ignoreId" : '')],
            'mobile'       => ['nullable', 'string', 'max:20'],
            'user_type'    => ['nullable', 'in:' . implode(',', $allowedUserTypes)],
            'branch_id'    => ['nullable', 'exists:branches,id'],
            'employee_id'  => ['nullable', 'exists:employees,id'],
            'role_id'      => ['required', 'exists:roles,id'],
            'force_password_change' => ['boolean'],
            'account_expiry_date'   => ['nullable', 'date'],
            'status'       => ['nullable', 'in:active,inactive,locked'],
            'remarks'      => ['nullable', 'string', 'max:1000'],
            'is_active'    => ['boolean'],
        ];

        $rules['password'] = $ignoreId
            ? ['nullable', 'confirmed', Password::min(8)]
            : ['required', 'confirmed', Password::min(8)];

        $data = $request->validate($rules);

        if ($isBranchScoped) {
            // A branch-scoped actor can only ever create/keep branch_user
            // accounts in their own branch — never assign to another branch.
            $data['branch_id'] = $actor->branch_id;
            $data['user_type'] = 'branch_user';
        } elseif ($isSuperAdmin) {
            if (in_array($data['user_type'] ?? null, ['branch_head', 'branch_user'], true)) {
                // Force branch_id to the currently selected branch (via the
                // Branch Switcher) — a Super Admin manages users in that
                // branch only, never a free pick from any branch.
                $currentBranchId = BranchScope::currentBranchId();
                if (! $currentBranchId) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'branch_id' => 'Select a branch (use the Branch Switcher) before creating a Branch Head or Branch User account.',
                    ]);
                }
                $data['branch_id'] = $currentBranchId;
            }
            if (($data['user_type'] ?? null) === 'super_admin' || empty($data['user_type'])) {
                $data['branch_id'] = null;
            }
            if (empty($data['user_type'])) {
                $data['user_type'] = null; // normalize '' to NULL, never store an empty string
            }
        } else {
            // Existing (pre-module) accounts managing users keep exactly
            // today's behavior — no branch/user_type concept applied.
            $data['user_type'] = null;
            $data['branch_id'] = null;
        }

        // User Type must always match the underlying Role — otherwise you get
        // exactly the bug this module was fixed for: a "Super Admin" whose
        // real role isn't super_admin (mislabeled, actually restricted), or
        // the inverse — a "Branch Head" secretly holding the super_admin role
        // (mislabeled, actually unrestricted and able to bypass branch scoping
        // entirely, breaking "a Branch Head cannot access another branch").
        $selectedRole = \App\Models\Role::find($data['role_id']);
        $roleIsSuperAdmin = $selectedRole?->name === 'super_admin';

        if (($data['user_type'] ?? null) === 'super_admin' && ! $roleIsSuperAdmin) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'role_id' => 'A Super Admin user type must be assigned the super_admin role.',
            ]);
        }
        if (in_array($data['user_type'] ?? null, ['branch_head', 'branch_user'], true) && $roleIsSuperAdmin) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'role_id' => 'The super_admin role cannot be assigned to a Branch Head or Branch User.',
            ]);
        }

        // Server-side enforcement of "a Branch Head cannot assign roles
        // beyond their permitted scope" — not just a filtered dropdown, in
        // case a role_id outside that list is submitted directly.
        if ($isBranchScoped) {
            $applicableTypes = $selectedRole?->applicable_user_types ?? [];
            if (! in_array('branch_user', $applicableTypes, true)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'role_id' => 'You may only assign roles applicable to Branch User accounts.',
                ]);
            }
        }

        if (! empty($data['employee_id']) && ! empty($data['branch_id'])) {
            $employee = Employee::find($data['employee_id']);
            if ($employee && $employee->branch_id !== (int) $data['branch_id']) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'employee_id' => 'The selected employee does not belong to the selected branch.',
                ]);
            }
        }

        $data['force_password_change'] = $request->boolean('force_password_change');

        if (! empty($data['status'])) {
            $data['is_active'] = $data['status'] !== 'inactive';
            $data['is_locked'] = $data['status'] === 'locked';
        } else {
            $data['is_active'] = $request->boolean('is_active', true);
        }
        unset($data['status']);

        return $data;
    }
}
