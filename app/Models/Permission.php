<?php
// File: app/Models/Permission.php
// Purpose: Permission model — maps to permissions table (module+action definitions)
// Author: System
// Date: 2024-01-01 | Modified: 2026-06-30

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    public $timestamps = false;

    protected $fillable = ['module', 'access_level', 'name', 'description'];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }
}
