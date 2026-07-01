<?php
// File: app/Http/Middleware/CheckPermission.php
// Purpose: Route-level permission enforcement via Laravel Gate
// Author: System
// Date: 2026-06-30

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Usage in routes:  ->middleware('permission:employees.view')
     * Multiple (OR):    ->middleware('permission:employees.view,employees.edit')
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return $next($request);
            }
        }

        abort(403, 'You do not have permission to access this resource.');
    }
}
