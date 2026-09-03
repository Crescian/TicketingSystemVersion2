<?php

use App\Support\BusinessClock;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php', // ← add this line
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        // Register role middleware alias
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'idempotent' => \App\Http\Middleware\PreventDuplicateSubmission::class,
            'abilities' => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
            'ability' => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
        ]);

        // Runs after session/auth so Auth::user() is available; redirects
        // Employees who still need account setup (org info and/or password) to
        // the account-setup tracker on every request except that tracker, the
        // profile/password/logout routes, and a few polling endpoints.
        $middleware->web(append: [
            \App\Http\Middleware\RequirePasswordChange::class,
        ]);
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        $withinNotificationWindow = fn () => BusinessClock::isWithinNotificationWindow(now());

        $schedule->command('sla:check')->everyMinute()->when($withinNotificationWindow);
        $schedule->command('tickets:remind-stale')->everyFifteenMinutes()->when($withinNotificationWindow);

        // Deliberately NOT gated by isWithinNotificationWindow — this is a real
        // 24-hour wall-clock SLA on the requestor, not a "don't email off-hours"
        // courtesy, so it keeps ticking through nights/weekends.
        $schedule->command('tickets:auto-close-confirmations')->everyFifteenMinutes();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
