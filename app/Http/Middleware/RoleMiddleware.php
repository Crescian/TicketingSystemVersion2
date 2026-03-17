<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        $userRole = Auth::user()->role?->role_name;

        if (!in_array($userRole, $roles)) {
            abort(403, 'Unauthorized. You do not have access to this page.');
        }

        return $next($request);
    }
}