<?php
// File: app/Http/Controllers/Admin/RoleController.php
// Purpose: CRUD management for system roles
// Author: System
// Date: 2026-06-30

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('users')->orderBy('id')->paginate(20);
        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        return view('admin.roles.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:50', 'unique:roles,name', 'regex:/^[a-z][a-z_]*$/'],
            'display_name' => ['required', 'string', 'max:100'],
            'description'  => ['nullable', 'string', 'max:500'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        Role::create($data);
        return redirect()->route('admin.roles.index')
            ->with('success', 'Role "' . $data['display_name'] . '" created successfully.');
    }

    public function edit(Role $role)
    {
        return view('admin.roles.edit', compact('role'));
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:50', 'unique:roles,name,' . $role->id, 'regex:/^[a-z][a-z_]*$/'],
            'display_name' => ['required', 'string', 'max:100'],
            'description'  => ['nullable', 'string', 'max:500'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $role->update($data);
        return redirect()->route('admin.roles.index')->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        $userCount = $role->users()->count();
        if ($userCount > 0) {
            return back()->with('error', "Cannot delete — role is assigned to {$userCount} user(s).");
        }
        $role->permissions()->detach();
        $role->delete();
        return back()->with('success', 'Role deleted.');
    }
}
