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
    private const LEVELS = Permission::ACCESS_LEVELS;

    /**
     * All known module keys — the sidebar-driven registry plus any custom
     * module a user has already created via this CRUD, so neither list
     * shadows the other.
     */
    private function allModules()
    {
        return collect(array_keys(config('menu_modules')))
            ->merge(Permission::select('module')->distinct()->pluck('module'))
            ->unique()
            ->sort()
            ->values();
    }

    public function index(Request $request)
    {
        // Self-heal: guarantee every registered sidebar module has permission
        // rows before listing, so it appears here without a manual reseed.
        Permission::syncModules();

        $modules = $this->allModules();

        // A module filter drops into the detailed, editable record list for
        // just that module. With no filter, show the module-grouped summary.
        if ($request->filled('module')) {
            $permissions = Permission::where('module', $request->module)
                ->orderBy('access_level')
                ->paginate(30)
                ->withQueryString();

            return view('admin.permissions.index', [
                'mode'        => 'detail',
                'permissions' => $permissions,
                'modules'     => $modules,
            ]);
        }

        $byModule = Permission::orderBy('access_level')->get()->groupBy('module');

        $summary = $modules->map(fn ($module) => [
            'module'   => $module,
            'label'    => config("menu_modules.$module.label", ucfirst($module)),
            'levelMap' => ($byModule->get($module) ?? collect())->keyBy('access_level'),
        ]);

        return view('admin.permissions.index', [
            'mode'    => 'summary',
            'summary' => $summary,
            'modules' => $modules,
        ]);
    }

    public function create()
    {
        $modules = $this->allModules();
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
