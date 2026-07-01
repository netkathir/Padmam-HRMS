<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('role')->orderBy('name');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(fn($q) => $q->where('name', 'like', $s)->orWhere('email', 'like', $s));
        }
        if ($request->filled('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        $users = $query->paginate(20)->withQueryString();
        $roles = Role::orderBy('name')->get();
        return view('users.index', compact('users', 'roles'));
    }

    public function create()
    {
        $roles     = Role::orderBy('name')->get();
        $employees = Employee::orderBy('employee_code')->get();
        return view('users.create', compact('roles', 'employees'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'role_id'  => ['required', 'exists:roles,id'],
            'is_active' => ['boolean'],
        ]);

        User::create($data);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $roles     = Role::orderBy('name')->get();
        $employees = Employee::orderBy('employee_code')->get();
        return view('users.edit', compact('user', 'roles', 'employees'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'username' => ['required', 'string', 'max:50', 'unique:users,username,' . $user->id],
            'role_id'  => ['required', 'exists:roles,id'],
            'is_active' => ['boolean'],
        ]);

        if ($request->filled('password')) {
            $request->validate(['password' => ['confirmed', Password::min(8)]]);
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }
        $user->delete();
        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
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
}
