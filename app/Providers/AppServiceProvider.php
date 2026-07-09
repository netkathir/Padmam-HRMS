<?php
// File: app/Providers/AppServiceProvider.php
// Purpose: Bootstrap application services — pagination, permission gates
// Author: System
// Date: 2024-01-01 | Modified: 2026-06-30

namespace App\Providers;

use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Use Bootstrap 5 pagination HTML instead of the default Tailwind output
        Paginator::useBootstrapFive();

        // Permission gate — runs before any @can / ->can() / ->authorize() check.
        //
        // Access-level hierarchy (highest to lowest):
        //   full(3) > create(2) > read(1)
        //
        // Having a higher level automatically grants all lower-level checks
        // within the same module. E.g. a role with 'attendance.full' passes
        // checks for 'attendance.read' and 'attendance.create' as well.
        Gate::before(function (User $user, string $ability) {
            if ($user->isSuperAdmin()) {
                return true;
            }

            $userPerms = $user->role?->permissions->pluck('name') ?? collect();

            // Fast path: exact match
            if ($userPerms->contains($ability)) {
                return true;
            }

            // Parse module and level from ability string (e.g. 'attendance.read')
            $parts  = explode('.', $ability, 2);
            $module = $parts[0] ?? null;
            $level  = $parts[1] ?? null;

            if (!$module || !$level) {
                return null;
            }

            // Hierarchy: full grants read+create, create grants read
            $hierarchy = ['read' => 1, 'create' => 2, 'full' => 3];
            $required  = $hierarchy[$level] ?? 0;

            foreach ($hierarchy as $permLevel => $permValue) {
                if ($permValue >= $required && $userPerms->contains("$module.$permLevel")) {
                    return true;
                }
            }

            return null;
        });
    }
}
