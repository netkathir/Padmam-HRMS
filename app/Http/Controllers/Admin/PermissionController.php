<?php
// File: app/Http/Controllers/Admin/PermissionController.php
// Purpose: CRUD management for system permissions
// Author: System
// Date: 2026-06-30

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /** Valid access levels in the redesigned system */
    private const LEVELS = ['read', 'create', 'full', 'delete'];

    public function index(Request $request)
    {
        $query = Permission::query();
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }
        $permissions = $query->orderBy('module')->orderBy('access_level')->paginate(30)->withQueryString();
        $modules     = Permission::select('module')->distinct()->orderBy('module')->pluck('module');
        return view('admin.permissions.index', compact('permissions', 'modules'));
    }

    public function create()
    {
        $modules = Permission::select('module')->distinct()->orderBy('module')->pluck('module');
        $levels  = self::LEVELS;
        return view('admin.permissions.create', compact('modules', 'levels'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'module'       => ['required', 'string', 'max:50', 'regex:/^[a-z][a-z_]*$/'],
            'access_level' => ['required', 'string', 'in:read,create,full,delete'],
            'description'  => ['nullable', 'string', 'max:255'],
        ]);

        $data['name'] = $data['module'] . '.' . $data['access_level'];

        if (Permission::where('module', $data['module'])->where('access_level', $data['access_level'])->exists()) {
            return back()->withErrors(['module' => 'This module + access level already exists.'])->withInput();
        }

        Permission::create($data);
        return redirect()->route('admin.permissions.index')->with('success', 'Permission created successfully.');
    }

    public function edit(Permission $permission)
    {
        $modules = Permission::select('module')->distinct()->orderBy('module')->pluck('module');
        $levels  = self::LEVELS;
        return view('admin.permissions.edit', compact('permission', 'modules', 'levels'));
    }

    public function update(Request $request, Permission $permission)
    {
        $data = $request->validate([
            'module'       => ['required', 'string', 'max:50', 'regex:/^[a-z][a-z_]*$/'],
            'access_level' => ['required', 'string', 'in:read,create,full,delete'],
            'description'  => ['nullable', 'string', 'max:255'],
        ]);

        $data['name'] = $data['module'] . '.' . $data['access_level'];

        $duplicate = Permission::where('module', $data['module'])
            ->where('access_level', $data['access_level'])
            ->where('id', '!=', $permission->id)
            ->exists();

        if ($duplicate) {
            return back()->withErrors(['module' => 'This module + access level already exists.'])->withInput();
        }

        $permission->update($data);
        return redirect()->route('admin.permissions.index')->with('success', 'Permission updated successfully.');
    }

    public function destroy(Permission $permission)
    {
        $permission->roles()->detach();
        $permission->delete();
        return back()->with('success', 'Permission deleted.');
    }
}
