<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (! $request->user()) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : redirect()->route('login');
        }

        if ($request->user()->status === 'inactive') {
            auth()->logout();
            return $request->expectsJson()
                ? response()->json(['message' => 'Your account is inactive.'], 403)
                : redirect()->route('login')->withErrors(['email' => 'Your account is inactive.']);
        }

        if (! in_array($request->user()->role, $roles)) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthorized.'], 403)
                : abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
