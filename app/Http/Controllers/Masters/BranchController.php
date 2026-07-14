<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\User;
use App\Support\SequentialCodeGenerator;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BranchController extends Controller
{
    /** Unit Type suggestions — a datalist, not a closed enum, so admins can type a custom value too (FSD: "... or configurable value"). */
    public const UNIT_TYPES = ['Branch', 'Factory', 'Office', 'Unit'];

    public const WEEKDAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    /**
     * Branch Management (spec) is Super-Admin-only. Defense in depth on top
     * of the masters_branches.read route gate, which Super Admin bypasses
     * unconditionally via Gate::before anyway.
     */
    private function ensureSuperAdmin(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403, 'Only the Super Admin can manage branches.');
    }

    public function index(Request $request)
    {
        $this->ensureSuperAdmin();

        $query = Branch::with('branchHead')->orderBy('name');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(fn($q) => $q->where('name', 'like', $s)
                ->orWhere('code', 'like', $s)
                ->orWhere('city', 'like', $s)
                ->orWhere('district', 'like', $s)
                ->orWhere('email', 'like', $s));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $branches = $query->paginate(20)->withQueryString();

        return view('masters.branches.index', compact('branches'));
    }

    public function create()
    {
        $this->ensureSuperAdmin();

        $states = config('states');
        $unitTypes = self::UNIT_TYPES;
        $weekdays = self::WEEKDAYS;
        $branchHeads = User::where('is_active', true)->orderBy('name')->get();

        return view('masters.branches.create', compact('states', 'unitTypes', 'weekdays', 'branchHeads'));
    }

    public function store(Request $request)
    {
        $this->ensureSuperAdmin();

        $data = $this->validateBranch($request);
        // Branch Code is auto-generated on create — hidden from the UI, and
        // any value a caller submits directly is ignored so it can never be
        // set to something other than the next generated code.
        unset($data['code']);
        $data['created_by'] = auth()->id();

        // Structured address entry (Create Branch only — `address` above
        // remains the single field the Edit screen and its validation use).
        $addressLines = $request->validate([
            'address_line1' => ['nullable', 'string', 'max:200'],
            'address_line2' => ['nullable', 'string', 'max:200'],
        ]);
        $data['address_line1'] = $addressLines['address_line1'] ?? null;
        $data['address_line2'] = $addressLines['address_line2'] ?? null;

        $branch = $this->createWithGeneratedCode($data);

        AuditLog::write(auth()->id(), 'create', 'branches', $branch->id, null, $data, $branch->id);

        return redirect()->route('masters.branches.index')
            ->with('success', 'Branch created successfully.');
    }

    /**
     * Generates the next Branch Code (one higher than the latest existing
     * code, preserving its prefix/padding) and creates the branch with it.
     * A row lock on the latest branch serializes concurrent creations, and
     * the retry loop is a defensive fallback against the rare duplicate-key
     * race the lock doesn't cover (e.g. two processes on different DB
     * connections outside the lock's visibility) — the unique index on
     * `code` is the actual guarantee against ever storing a collision.
     */
    private function createWithGeneratedCode(array $data): Branch
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            try {
                return DB::transaction(function () use ($data) {
                    $lastCode = Branch::orderByDesc('id')->lockForUpdate()->value('code');
                    $data['code'] = SequentialCodeGenerator::next($lastCode, 'BR0001');

                    return Branch::create($data);
                });
            } catch (QueryException $e) {
                $isDuplicate = (string) $e->getCode() === '23000';
                if (! $isDuplicate || $attempt === 5) {
                    throw $e;
                }
            }
        }

        throw new \RuntimeException('Unable to generate a unique Branch Code after several attempts.');
    }

    public function edit(Branch $branch)
    {
        $this->ensureSuperAdmin();

        $states = config('states');
        $unitTypes = self::UNIT_TYPES;
        $weekdays = self::WEEKDAYS;
        $branchHeads = User::where('is_active', true)->orderBy('name')->get();

        return view('masters.branches.edit', compact('branch', 'states', 'unitTypes', 'weekdays', 'branchHeads'));
    }

    public function update(Request $request, Branch $branch)
    {
        $this->ensureSuperAdmin();

        $data = $this->validateBranch($request, $branch->id);
        $data['updated_by'] = auth()->id();

        $oldValues = $branch->only(array_keys($data));
        $branch->update($data);

        AuditLog::write(auth()->id(), 'update', 'branches', $branch->id, $oldValues, $data, $branch->id);

        return redirect()->route('masters.branches.index')
            ->with('success', 'Branch updated successfully.');
    }

    public function destroy(Branch $branch)
    {
        $this->ensureSuperAdmin();

        if ($branch->departments()->exists() || $branch->employees()->exists() || $branch->users()->exists() || $branch->headAssignments()->exists()) {
            return back()->with('error', 'Cannot delete a branch with existing departments, employees, users, or Branch Head assignments.');
        }

        $branch->delete();

        AuditLog::write(auth()->id(), 'delete', 'branches', $branch->id, null, null, $branch->id);

        return redirect()->route('masters.branches.index')
            ->with('success', 'Branch deleted successfully.');
    }

    public function activate(Branch $branch)
    {
        $this->ensureSuperAdmin();

        $branch->update(['is_active' => true, 'updated_by' => auth()->id()]);
        AuditLog::write(auth()->id(), 'activate', 'branches', $branch->id, ['is_active' => false], ['is_active' => true], $branch->id);

        return back()->with('success', 'Branch activated.');
    }

    public function deactivate(Branch $branch)
    {
        $this->ensureSuperAdmin();

        $branch->update(['is_active' => false, 'updated_by' => auth()->id()]);
        AuditLog::write(auth()->id(), 'deactivate', 'branches', $branch->id, ['is_active' => true], ['is_active' => false], $branch->id);

        return back()->with('success', 'Branch deactivated. Historical data remains available; new transactions are blocked for this branch.');
    }

    private function validateBranch(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name'                     => ['required', 'string', 'max:100', 'unique:branches,name' . ($ignoreId ? ",$ignoreId" : '')],
            // Auto-generated on create (hidden from the UI — see
            // createWithGeneratedCode()); still required/unique/editable on
            // update, preserving today's Edit Branch behavior exactly.
            'code'                     => $ignoreId
                ? ['required', 'string', 'max:20', 'unique:branches,code,' . $ignoreId]
                : ['nullable', 'string', 'max:20'],
            'unit_type'                => ['nullable', 'string', 'max:50'],
            // Address is only mandatory when the admin opts to maintain it
            // (the "Address Available" toggle — `has_address`, not a stored
            // column, just the signal for these `required_if`s).
            'has_address'              => ['sometimes', 'boolean'],
            'address'                  => ['nullable', 'string', 'required_if:has_address,1'],
            'state'                    => ['nullable', 'string', 'max:100', 'in:' . implode(',', config('states')), 'required_if:has_address,1'],
            'district'                 => ['nullable', 'string', 'max:100', 'required_if:has_address,1'],
            'city'                     => ['nullable', 'string', 'max:100', 'required_if:has_address,1'],
            'pincode'                  => ['nullable', 'digits:6', 'required_if:has_address,1'],
            'contact_person'           => ['nullable', 'string', 'max:150'],
            'phone'                    => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'email'                    => ['nullable', 'email', 'max:150'],
            'branch_head_user_id'      => ['nullable', 'exists:users,id'],
            'start_date'               => ['nullable', 'date'],
            'closure_date'             => ['nullable', 'date', 'after_or_equal:start_date'],
            'pf_establishment_number'  => ['nullable', 'string', 'max:50'],
            'esi_employer_code'        => ['nullable', 'string', 'max:50'],
            'weekly_off_days'          => ['nullable', 'array'],
            'weekly_off_days.*'        => ['in:' . implode(',', self::WEEKDAYS)],
            'is_active'                => ['required', 'boolean'],
        ], [
            'closure_date.after_or_equal' => 'The closure date cannot be before the branch start date.',
        ]);

        $data['weekly_off_days'] = $data['weekly_off_days'] ?? null;
        unset($data['has_address']);

        return $data;
    }
}
