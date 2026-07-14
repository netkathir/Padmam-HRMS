<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * FSD 15.1 — "Force Password Change: Checkbox, optional, configurable." The
 * checkbox/column already existed but was never enforced anywhere (a dead
 * flag). This closes that gap: any authenticated request is redirected to
 * the change-password screen while the flag is set, so it can't be bypassed
 * by navigating directly to another URL — only the change-password screen
 * itself and logout are exempt.
 */
class ForcePasswordChange
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->force_password_change
            && ! $request->routeIs('password.force-change', 'password.force-change.update', 'logout')) {
            return redirect()->route('password.force-change');
        }

        return $next($request);
    }
}
