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
        $roles = Role::withCount('users')->with('createdBy')->orderBy('id')->paginate(20);
        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        return view('admin.roles.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateRole($request);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['created_by'] = auth()->id();
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
        $data = $this->validateRole($request, $role->id);
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

    private function validateRole(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name'                    => ['required', 'string', 'max:50', 'unique:roles,name' . ($ignoreId ? ",$ignoreId" : ''), 'regex:/^[a-z][a-z_]*$/'],
            'display_name'            => ['required', 'string', 'max:100', 'unique:roles,display_name' . ($ignoreId ? ",$ignoreId" : '')],
            'role_code'               => ['nullable', 'string', 'max:30', 'unique:roles,role_code' . ($ignoreId ? ",$ignoreId" : '')],
            'description'             => ['nullable', 'string', 'max:500'],
            'applicable_user_types'   => ['nullable', 'array'],
            'applicable_user_types.*' => ['in:branch_head,branch_user'],
        ]);
    }
}
