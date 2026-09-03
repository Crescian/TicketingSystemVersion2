<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

// Hard-blocks every page except the account-setup tracker, the profile page
// (and logout) for Employees flagged needs_account_setup (set by
// UserManagementController::store()/resetPassword() alongside
// must_change_password for new/reset accounts; cleared by
// ProfileController::maybeCompleteAccountSetup() once BOTH the org-info form
// and the password have been completed). Scoped to the Employee role only —
// internal/staff roles are still mid-testing and shouldn't be forced through
// this while that's ongoing. The change-password modal component covers the
// visual/UX side; this is the actual enforcement, since a modal alone can be
// bypassed client-side.
class RequirePasswordChange
{
    // Keeps the tracker page and the profile page (org-info + password forms
    // live there) fully usable, and lets background polling JS in the shared
    // layout (notification bell, unread chat counts) keep returning JSON
    // instead of a redirect while the user is locked out.
    private const ALLOWED_ROUTES = [
        'account.setup', 'profile', 'profile.password', 'profile.org-info', 'logout',
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

        if ($user->needs_account_setup) {
            return redirect()->route('account.setup');
        }

        return $next($request);
    }
}
