<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use Closure;
use Illuminate\Http\Request;

/**
 * First-run setup gate: a Super Admin cannot use any other screen until at
 * least one Branch exists — mirrors ForcePasswordChange's unbypassable
 * redirect pattern. Only masters.branches.create/store and logout are exempt.
 * Non-Super-Admin users are left alone; only a Super Admin can create a
 * branch (BranchController::ensureSuperAdmin()).
 */
class RequireBranchExists
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->isSuperAdmin()
            && Branch::count() === 0
            && ! $request->routeIs('masters.branches.create', 'masters.branches.store', 'logout')) {
            return redirect()->route('masters.branches.create');
        }

        return $next($request);
    }
}
