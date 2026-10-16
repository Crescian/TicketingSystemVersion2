<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Mobile\DeviceController as MobileDeviceController;
use App\Http\Controllers\Api\Mobile\MessageController as MobileMessageController;
use App\Http\Controllers\Api\Mobile\NotificationController as MobileNotificationController;
use App\Http\Controllers\Api\Mobile\ProfileController as MobileProfileController;
use App\Http\Controllers\Api\Mobile\Staff\StaffTicketController;
use App\Http\Controllers\Api\Mobile\TicketController as MobileTicketController;
use App\Support\Mobile\StaffActions;
use App\Http\Controllers\Api\OrgController;
use App\Http\Controllers\Api\TicketsController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/auth/microsoft', [AuthController::class, 'microsoft'])->middleware('throttle:login');

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/logout-all', [AuthController::class, 'logoutAll']);
});

// Read-only org/user directory for other in-house systems — separate 'org:read'
// token ability (issued via `php artisan api:issue-org-token`), not tied to the
// session-based web roles used elsewhere in this app.
Route::middleware(['auth:sanctum', 'abilities:org:read'])->prefix('org')->group(function () {
    Route::get('/hierarchy', [OrgController::class, 'hierarchy']);
    Route::get('/business-units',[OrgController::class, 'businessUnits']);
    Route::get('/companies', [OrgController::class, 'companies']);
    Route::get('/departments', [OrgController::class, 'departments']);
    Route::get('/users', [OrgController::class, 'users']);
});

// Read-only ticket export for SLA/workload/resolution-time analysis — separate
// 'tickets:read' token ability (issued via `php artisan api:issue-tickets-token`),
// scoped independently from 'org:read' so access can be granted per-resource.
Route::middleware(['auth:sanctum', 'abilities:tickets:read'])->group(function () {
    Route::get('/tickets', [TicketsController::class, 'index']);
});
// Mobile app (React Native / Expo), for user login tokens ('user' ability).
// This group is every role's own side — profile, push devices, chat, and
// "My Requests" (staff file their own tickets too, like the web's
// my-requests.* pages). Same rules as the web via RequestorTicketService /
// TicketChatService. Staff ticket work is the `staff` group below.
// Reference: docs/api/employee-mobile-api.md.
Route::middleware([
    'auth:sanctum',
    'abilities:' . AuthController::USER_ABILITY,
    \App\Http\Middleware\RequireMobileAccountSetup::class,
    'throttle:ticket-actions',
])
    ->prefix('mobile')
    ->name('mobile.')
    ->group(function () {
        // Profile & account setup
        Route::get('/me', [MobileProfileController::class, 'me'])->name('me');
        Route::put('/profile/password', [MobileProfileController::class, 'updatePassword'])->name('profile.password');
        Route::put('/profile/org-info', [MobileProfileController::class, 'updateOrgInfo'])->name('profile.org-info');
        Route::get('/org/hierarchy', [MobileProfileController::class, 'hierarchy'])->name('org.hierarchy');
        Route::post('/onboarding/complete', [MobileProfileController::class, 'completeOnboarding'])->name('onboarding.complete');
        Route::get('/it-team', [MobileProfileController::class, 'itTeam'])->name('it-team');

        // Tickets
        Route::get('/tickets', [MobileTicketController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/counts', [MobileTicketController::class, 'counts'])->name('tickets.counts');
        Route::post('/tickets', [MobileTicketController::class, 'store'])->name('tickets.store')->middleware(['throttle:ticket-submit', 'idempotent:10']);
        Route::get('/tickets/{ticket}', [MobileTicketController::class, 'show'])->name('tickets.show');
        // POST (not PATCH) so the app can send multipart attachments.
        Route::post('/tickets/{ticket}', [MobileTicketController::class, 'update'])->name('tickets.update')->middleware('idempotent:8');
        Route::patch('/tickets/{ticket}/cancel', [MobileTicketController::class, 'cancel'])->name('tickets.cancel')->middleware('idempotent:8');
        Route::patch('/tickets/{ticket}/confirm', [MobileTicketController::class, 'confirm'])->name('tickets.confirm')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/feedback', [MobileTicketController::class, 'feedback'])->name('tickets.feedback')->middleware('idempotent:8');
        Route::get('/tickets/{ticket}/service-report', [MobileTicketController::class, 'serviceReport'])->name('tickets.service-report');
        Route::get('/attachments/{attachment}', [MobileTicketController::class, 'attachment'])->name('attachments.show');

        // Chat
        Route::get('/tickets/{ticket}/messages', [MobileMessageController::class, 'index'])->name('messages.index');
        Route::post('/tickets/{ticket}/messages', [MobileMessageController::class, 'store'])->name('messages.store');
        Route::get('/messages/unread', [MobileMessageController::class, 'unread'])->name('messages.unread');

        // Notifications
        Route::get('/notifications', [MobileNotificationController::class, 'index'])->name('notifications.index');
        Route::post('/devices', [MobileDeviceController::class, 'store'])->name('devices.store');
        Route::delete('/devices', [MobileDeviceController::class, 'destroy'])->name('devices.destroy');
    });

// Staff ticket work on the phone — each ticket role's queue, ticket detail and
// actions, run through the role's own web controllers (App\Support\Mobile\
// WebBridge + StaffActions), so the rules are exactly the web's.
Route::middleware([
    'auth:sanctum',
    'abilities:' . AuthController::USER_ABILITY,
    'role:' . implode(',', StaffActions::ROLES),
    'throttle:ticket-actions',
])
    ->prefix('mobile/staff')
    ->name('mobile.staff.')
    ->group(function () {
        Route::get('/queue', [StaffTicketController::class, 'queue'])->name('queue');
        Route::get('/lookups', [StaffTicketController::class, 'lookups'])->name('lookups');
        Route::get('/tickets/{ticket}', [StaffTicketController::class, 'show'])->name('tickets.show');
        // POST for every action (multipart where the form has files).
        Route::post('/tickets/{ticket}/actions/{action}', [StaffTicketController::class, 'act'])
            ->where('action', '[a-z_]+')
            ->name('tickets.act')
            ->middleware('idempotent:8');
    });
