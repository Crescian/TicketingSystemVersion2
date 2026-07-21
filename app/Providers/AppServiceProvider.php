<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // New-ticket filing: generous enough for Helpdesk filing several tickets in a
        // row on behalf of employees, tight enough to stop a runaway client/bug.
        RateLimiter::for('ticket-submit', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        // Ticket lifecycle actions (acknowledge/assign/reassign/escalate/resolve/etc.) —
        // staff churn through many tickets, so this is looser than submission.
        RateLimiter::for('ticket-actions', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Login: keyed by email+IP so it can't be used to lock a coworker out, but still
        // stops credential brute-forcing.
        RateLimiter::for('login', function (Request $request) {
            $email = strtolower((string) $request->input('email'));

            return Limit::perMinute(5)->by($email . '|' . $request->ip());
        });
    }
}
