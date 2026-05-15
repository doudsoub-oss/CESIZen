<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use ValueError;

class EnsureUserHasRole
{
    /**
     * Pass when the authenticated user's role is at least one of the given roles.
     * Usage: `->middleware('role:admin')` or `->middleware('role:admin,super_admin')`.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        foreach ($roles as $roleValue) {
            try {
                $required = Role::from($roleValue);
            } catch (ValueError) {
                abort(500, "Unknown role: {$roleValue}");
            }

            if ($user->role?->isAtLeast($required)) {
                return $next($request);
            }
        }

        abort(403);
    }
}
