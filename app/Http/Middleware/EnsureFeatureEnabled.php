<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks direct route access to a module that's been temporarily hidden from
 * the UI/navigation via a config/features.php flag, without touching its
 * controller, model, or database schema.
 *
 * Usage in routes: ->middleware('feature:employee_types_enabled')
 */
class EnsureFeatureEnabled
{
    public function handle(Request $request, Closure $next, string $flag): Response
    {
        if (! config("features.$flag", true)) {
            abort(404);
        }

        return $next($request);
    }
}
