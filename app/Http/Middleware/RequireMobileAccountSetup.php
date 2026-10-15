<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

// Mobile API counterpart of RequirePasswordChange: an Employee flagged
// needs_account_setup can only reach the setup endpoints (profile, org info,
// password, department picker, push registration) until both steps are done.
// The app reacts to code "account_setup_required" by opening its setup screens.
class RequireMobileAccountSetup
{
    private const ALLOWED_ROUTES = [
        'mobile.me',
        'mobile.profile.password',
        'mobile.profile.org-info',
        'mobile.org.hierarchy',
        'mobile.devices.store',
        'mobile.devices.destroy',
    ];

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user?->needs_account_setup
            && !in_array($request->route()?->getName(), self::ALLOWED_ROUTES, true)) {
            return response()->json([
                'message' => 'Please finish setting up your account first.',
                'code' => 'account_setup_required',
            ], 403);
        }

        return $next($request);
    }
}
