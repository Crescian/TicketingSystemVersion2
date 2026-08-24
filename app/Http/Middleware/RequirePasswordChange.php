<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

// Hard-blocks every page except the profile (and logout) for Employees still on
// the shared default password ("password" — set by UserManagementController::
// store()/resetPassword() for new/reset accounts). Scoped to the Employee role
// only — internal/staff roles are still mid-testing and shouldn't be forced
// through this while that's ongoing. The change-password modal component
// covers the visual/UX side; this is the actual enforcement, since a modal
// alone can be bypassed client-side.
class RequirePasswordChange
{
    // Keeps the profile page itself fully usable (org-info form lives on the same
    // page) and lets background polling JS in the shared layout (notification
    // bell, unread chat counts) keep returning JSON instead of a redirect while
    // the user is locked to this page.
    private const ALLOWED_ROUTES = [
        'profile', 'profile.password', 'profile.org-info', 'logout',
        'notifications.poll', 'messages.unread', 'messages.total-unread',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user
            || $user->role?->role_name !== 'Employee'
            || in_array($request->route()?->getName(), self::ALLOWED_ROUTES, true)) {
            return $next($request);
        }

        if (Hash::check('password', $user->password)) {
            return redirect()->route('profile')
                ->with('error', 'Please change your default password before continuing.');
        }

        return $next($request);
    }
}
