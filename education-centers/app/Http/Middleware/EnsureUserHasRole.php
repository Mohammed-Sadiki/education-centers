<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * Permet l'usage : middleware('role:super_admin')
     * ou middleware('role:super_admin,center_admin')
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if (empty($roles)) {
            return $next($request);
        }

        if (! in_array($user->role, $roles, true)) {
            abort(403, __('Accès non autorisé pour votre rôle.'));
        }

        return $next($request);
    }
}
