<?php
// File: app/Models/Permission.php
// Purpose: Permission model — maps to permissions table (module+action definitions)
// Author: System
// Date: 2024-01-01 | Modified: 2026-07-08

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Permission extends Model
{
    public $timestamps = false;

    protected $fillable = ['module', 'access_level', 'name', 'description'];

    /** Access-level hierarchy, lowest to highest. Kept here as the single source of truth. */
    public const ACCESS_LEVELS = ['read', 'create', 'full', 'delete'];

    public const LEVEL_DESCRIPTIONS = [
        'read' => 'View and list records',
        'create' => 'Add new records (implies read)',
        'full' => 'Full access incl. edit & approve (implies create)',
        'delete' => 'Delete records (implies full access)',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }

    /**
     * Ensure every module declared in config('menu_modules') has a permission
     * row for each access level. Idempotent and safe to call on every request —
     * self-heals the permissions table when a new sidebar module is registered
     * without requiring a manual reseed.
     */
    public static function syncModules(): void
    {
        $modules = array_keys(config('menu_modules', []));

        $expected = count($modules) * count(self::ACCESS_LEVELS);
        if ($expected === 0 || self::whereIn('module', $modules)->count() >= $expected) {
            return;
        }

        $rows = [];
        foreach ($modules as $module) {
            $label = config("menu_modules.$module.label", ucfirst($module));
            foreach (self::ACCESS_LEVELS as $level) {
                $rows[] = [
                    'module' => $module,
                    'access_level' => $level,
                    'name' => "$module.$level",
                    'description' => "$label — " . self::LEVEL_DESCRIPTIONS[$level],
                ];
            }
        }

        DB::table('permissions')->insertOrIgnore($rows);
    }
}
