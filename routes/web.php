<?php

use App\Http\Controllers\Auth\WebAuthController;
use App\Http\Controllers\Helpdesk\TicketController as HelpdeskTicketController;
use App\Http\Controllers\Technician\TicketController as TechnicianTicketController;
use App\Http\Controllers\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\TicketsController as EmployeeTicketsController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\Dashboard\ExecutiveDashboardController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

// ── Redirect root to login
Route::get('/', fn() => redirect('/login'));

// ── Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [WebAuthController::class, 'login']);
});

// ── Logout
Route::middleware('auth')
    ->post('/logout', [WebAuthController::class, 'logout'])
    ->name('logout');

// ── Employee routes
Route::middleware(['auth', 'role:Employee'])
    ->prefix('employee')
    ->name('employee.')
    ->group(function () {
        Route::get('/dashboard', [EmployeeTicketsController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/create', [EmployeeTicketsController::class, 'create'])->name('tickets.create');
        Route::post('/tickets', [EmployeeTicketsController::class, 'store'])->name('tickets.store');
        Route::get('/tickets/{ticket}', [EmployeeTicketsController::class, 'show'])->name('tickets.show');
        Route::patch('/tickets/{ticket}/cancel', [EmployeeTicketsController::class, 'cancel'])->name('tickets.cancel');
        Route::post('/tickets/{ticket}/feedback', [EmployeeTicketsController::class, 'storeFeedback'])->name('tickets.feedback'); // ← add this
    });

// ── Helpdesk routes
Route::middleware(['auth', 'role:Helpdesk'])
    ->prefix('helpdesk')
    ->name('helpdesk.')
    ->group(function () {
        Route::get('/dashboard', [HelpdeskTicketController::class, 'index'])->name('dashboard');
        Route::post('/tickets/{ticket}/acknowledge', [HelpdeskTicketController::class, 'acknowledge'])->name('tickets.acknowledge');
        Route::post('/tickets/{ticket}/assign', [HelpdeskTicketController::class, 'assign'])->name('tickets.assign');
        Route::post('/tickets/{ticket}/reassign', [HelpdeskTicketController::class, 'reassign'])->name('tickets.reassign');
        Route::post('/tickets/{ticket}/escalate', [HelpdeskTicketController::class, 'escalate'])->name('tickets.escalate');
        Route::post('/tickets/{ticket}/resolve', [HelpdeskTicketController::class, 'resolve'])->name('tickets.resolve');
    });

// ── IT Technician routes
Route::middleware(['auth', 'role:IT Technician'])
    ->prefix('technician')
    ->name('technician.')
    ->group(function () {
        Route::get('/dashboard', [TechnicianTicketController::class, 'index'])->name('dashboard');
        Route::post('/tickets/{ticket}/accept', [TechnicianTicketController::class, 'accept'])->name('tickets.accept');
        Route::post('/tickets/{ticket}/decline', [TechnicianTicketController::class, 'decline'])->name('tickets.decline');
        Route::post('/tickets/{ticket}/update', [TechnicianTicketController::class, 'update'])->name('tickets.update');
        Route::post('/tickets/{ticket}/resolve', [TechnicianTicketController::class, 'resolve'])->name('tickets.resolve');
        Route::post('/tickets/{ticket}/escalate', [TechnicianTicketController::class, 'escalate'])->name('tickets.escalate');
    });

// ── IT Admin routes
Route::middleware(['auth', 'role:IT Admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // Escalation dashboard
        Route::get('/dashboard', [AdminTicketController::class, 'index'])->name('dashboard');
        Route::post('/tickets/{ticket}/reassign', [AdminTicketController::class, 'reassign'])->name('tickets.reassign');
        Route::post('/tickets/{ticket}/takeover', [AdminTicketController::class, 'takeover'])->name('tickets.takeover');
        Route::post('/tickets/{ticket}/resolve', [AdminTicketController::class, 'resolve'])->name('tickets.resolve');
        Route::get('/tickets/{ticket}/history', [AdminTicketController::class, 'history'])->name('tickets.history');

        // User management
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::get('/users/{user}', [UserManagementController::class, 'show'])->name('users.show');
        Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
        Route::patch('/users/{user}/deactivate', [UserManagementController::class, 'deactivate'])->name('users.deactivate');
        Route::patch('/users/{user}/reactivate', [UserManagementController::class, 'reactivate'])->name('users.reactivate');
        Route::patch('/users/{user}/reset-password', [UserManagementController::class, 'resetPassword'])->name('users.reset-password');

        // Audit log  ← now correctly INSIDE the admin group
        Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log');
    });

// ── Executive routes
Route::middleware(['auth', 'role:Manager'])
    ->prefix('executive')
    ->name('executive.')
    ->group(function () {
        Route::get('/dashboard', [ExecutiveDashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/data', [ExecutiveDashboardController::class, 'data'])->name('dashboard.data'); // ← add this
    });
// ── Notifications (all authenticated users)
Route::middleware('auth')
    ->get('/notifications/poll', [NotificationController::class, 'poll'])
    ->name('notifications.poll');
// ── Messaging routes (all authenticated users)
Route::middleware('auth')
    ->prefix('tickets')
    ->name('messages.')
    ->group(function () {
        Route::get('/{ticket}/messages', [MessageController::class, 'index'])->name('index');
        Route::post('/{ticket}/messages', [MessageController::class, 'store'])->name('store');
        Route::get('/{ticket}/messages/unread', [MessageController::class, 'unreadCount'])->name('unread');
        Route::get('/messages/total-unread', [MessageController::class, 'totalUnread'])->name('total-unread');
    });

Route::post('/tickets/{ticket}/feedback', [EmployeeTicketsController::class, 'storeFeedback'])->name('tickets.feedback');