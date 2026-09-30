<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Restrict a route to the given roles.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(403, 'Akses ditolak.');
        }

        $allowed = array_map(fn (string $role) => Role::from($role), $roles);

        if (! in_array($user->role, $allowed, true)) {
            abort(403, 'Akses ditolak.');
        }

        return $next($request);
    }
}
