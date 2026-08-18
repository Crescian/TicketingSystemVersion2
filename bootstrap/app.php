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
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        $withinNotificationWindow = fn () => BusinessClock::isWithinNotificationWindow(now());

        $schedule->command('sla:check')->everyMinute()->when($withinNotificationWindow);
        $schedule->command('tickets:remind-stale')->everyFifteenMinutes()->when($withinNotificationWindow);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
