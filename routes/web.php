<?php

use App\Http\Controllers\TicketsController as EmployeeTicketsController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Helpdesk\TicketController as HelpdeskTicketController;
use App\Http\Controllers\Technician\TicketController as TechnicianTicketController;
use App\Http\Controllers\Dashboard\ExecutiveDashboardController;
use App\Http\Controllers\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\Manager\TicketController as ManagerTicketController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\OrgSettingsController;
use App\Http\Controllers\Admin\SlaRuleController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\WebAuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SSOController;
use App\Http\Controllers\Dashboard\SupervisorDashboardController as SupportSupervisorController;
;

// ── Redirect root to login
Route::get('/', fn() => redirect('/login'));

// ── Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [WebAuthController::class, 'login'])->middleware('throttle:login');
});

Route::get('/sso-login', [SSOController::class, 'handleSSO'])
    ->name('sso.login');

// ── Logout
Route::middleware('auth')
    ->post('/logout', [WebAuthController::class, 'logout'])
    ->name('logout');

// ── Employee routes
Route::middleware(['auth', 'role:Employee', 'throttle:ticket-actions'])
    ->prefix('employee')
    ->name('employee.')
    ->group(function () {
        Route::get('/dashboard', [EmployeeTicketsController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/create', [EmployeeTicketsController::class, 'create'])->name('tickets.create');
        Route::post('/tickets', [EmployeeTicketsController::class, 'store'])->name('tickets.store')->middleware(['throttle:ticket-submit', 'idempotent:10']);
        Route::get('/tickets/{ticket}', [EmployeeTicketsController::class, 'show'])->name('tickets.show');
        Route::patch('/tickets/{ticket}/cancel', [EmployeeTicketsController::class, 'cancel'])->name('tickets.cancel')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/feedback', [EmployeeTicketsController::class, 'storeFeedback'])->name('tickets.feedback')->middleware('idempotent:8'); // ← add this
        Route::patch('/tickets/{ticket}/acknowledge', [EmployeeTicketsController::class, 'acknowledge'])->name('tickets.acknowledge')->middleware('idempotent:8'); // ← add this
    });

Route::middleware(['auth', 'role:Helpdesk,IT Admin,Supervisor - IT Admin'])
    ->prefix('portal')
    ->name('portal.')
    ->group(function () {
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

        // ── Organization Settings
        Route::get('/organization', [OrgSettingsController::class, 'index'])->name('settings');

        // Business Units
        Route::post('/settings/business-units', [OrgSettingsController::class, 'storeBU'])->name('settings.bu.store');
        Route::put('/settings/business-units/{businessUnit}', [OrgSettingsController::class, 'updateBU'])->name('settings.bu.update');
        Route::delete('/settings/business-units/{businessUnit}', [OrgSettingsController::class, 'destroyBU'])->name('settings.bu.destroy');

        // Companies
        Route::post('/settings/companies', [OrgSettingsController::class, 'storeCompany'])->name('settings.company.store');
        Route::put('/settings/companies/{company}', [OrgSettingsController::class, 'updateCompany'])->name('settings.company.update');
        Route::delete('/settings/companies/{company}', [OrgSettingsController::class, 'destroyCompany'])->name('settings.company.destroy');

        // Departments
        Route::post('/settings/departments', [OrgSettingsController::class, 'storeDept'])->name('settings.dept.store');
        Route::put('/settings/departments/{department}', [OrgSettingsController::class, 'updateDept'])->name('settings.dept.update');
        Route::delete('/settings/departments/{department}', [OrgSettingsController::class, 'destroyDept'])->name('settings.dept.destroy');

        // SLA
        Route::get('/sla-rules', [SlaRuleController::class, 'index'])->name('sla-rules.index');
        Route::post('/sla-rules', [SlaRuleController::class, 'store'])->name('sla-rules.store');
        Route::put('/sla-rules/{slaRule}', [SlaRuleController::class, 'update'])->name('sla-rules.update');
        Route::delete('/sla-rules/{slaRule}', [SlaRuleController::class, 'destroy'])->name('sla-rules.destroy');
        Route::patch('/sla-rules/{slaRule}/toggle', [SlaRuleController::class, 'toggle'])->name('sla-rules.toggle');

        // ── Categories
        Route::post('/sla-rules/categories', [SlaRuleController::class, 'storeCategory'])->name('sla-rules.category.store');
        Route::put('/sla-rules/categories/{slaCategory}', [SlaRuleController::class, 'updateCategory'])->name('sla-rules.category.update');
        Route::delete('/sla-rules/categories/{slaCategory}', [SlaRuleController::class, 'destroyCategory'])->name('sla-rules.category.destroy');

        // ── Subcategory SLA rules
        Route::post('/sla-rules/rules', [SlaRuleController::class, 'storeRule'])->name('sla-rules.rule.store');
        Route::get('/sla-rules/rules/{slaRule}', [SlaRuleController::class, 'showRule'])->name('sla-rules.rule.show');
        Route::put('/sla-rules/rules/{slaRule}', [SlaRuleController::class, 'updateRule'])->name('sla-rules.rule.update');
        Route::delete('/sla-rules/rules/{slaRule}', [SlaRuleController::class, 'destroyRule'])->name('sla-rules.rule.destroy');
        Route::patch('/sla-rules/rules/{slaRule}/toggle', [SlaRuleController::class, 'toggleRule'])->name('sla-rules.rule.toggle');

    });
// ── Helpdesk routes
Route::middleware(['auth', 'role:Helpdesk', 'throttle:ticket-actions'])
    ->prefix('helpdesk')
    ->name('helpdesk.')
    ->group(function () {
        Route::get('/dashboard', [HelpdeskTicketController::class, 'index'])->name('dashboard');
        Route::post('/tickets/{ticket}/acknowledge', [HelpdeskTicketController::class, 'acknowledge'])->name('tickets.acknowledge')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/closenotify', [HelpdeskTicketController::class, 'closenotify'])->name('tickets.closenotify')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/assign', [HelpdeskTicketController::class, 'assign'])->name('tickets.assign')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/reassign', [HelpdeskTicketController::class, 'reassign'])->name('tickets.reassign')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/escalate', [HelpdeskTicketController::class, 'escalate'])->name('tickets.escalate')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/resolve', [HelpdeskTicketController::class, 'resolve'])->name('tickets.resolve')->middleware('idempotent:8');
        Route::post('/tickets', [EmployeeTicketsController::class, 'store'])->name('tickets.store')->middleware(['throttle:ticket-submit', 'idempotent:10']); // ← reuse employee store

    });

// ── IT Support Specialist routes
Route::middleware(['auth', 'role:IT Support Specialist', 'throttle:ticket-actions'])
    ->prefix('technician')
    ->name('technician.')
    ->group(function () {
        Route::get('/dashboard', [TechnicianTicketController::class, 'index'])->name('dashboard');
        Route::post('/tickets/{ticket}/acknowledge', [TechnicianTicketController::class, 'acknowledge'])->name('tickets.acknowledge')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/start', [TechnicianTicketController::class, 'start'])->name('tickets.start')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/decline', [TechnicianTicketController::class, 'decline'])->name('tickets.decline')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/update', [TechnicianTicketController::class, 'update'])->name('tickets.update')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/resolve', [TechnicianTicketController::class, 'resolve'])->name('tickets.resolve')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/escalate', [TechnicianTicketController::class, 'escalate'])->name('tickets.escalate')->middleware('idempotent:8');
    });

// ── IT Admin routes
Route::middleware(['auth', 'role:IT Admin', 'throttle:ticket-actions'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // Escalation dashboard
        Route::get('/dashboard', [AdminTicketController::class, 'index'])->name('dashboard');
        Route::post('/tickets/{ticket}/acknowledge', [AdminTicketController::class, 'acknowledge'])->name('tickets.acknowledge')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/start', [AdminTicketController::class, 'start'])->name('tickets.start')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/decline', [AdminTicketController::class, 'decline'])->name('tickets.decline')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/reassign', [AdminTicketController::class, 'reassign'])->name('tickets.reassign')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/takeover', [AdminTicketController::class, 'takeover'])->name('tickets.takeover')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/resolve', [AdminTicketController::class, 'resolve'])->name('tickets.resolve')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/escalate', [AdminTicketController::class, 'escalate'])->name('tickets.escalate')->middleware('idempotent:8');
        Route::get('/tickets/{ticket}/history', [AdminTicketController::class, 'history'])->name('tickets.history');
    });

Route::middleware(['auth', 'role:Supervisor - IT Admin', 'throttle:ticket-actions'])
    ->prefix('supervisor')
    ->name('supervisor.')
    ->group(function () {
        Route::get('/dashboard', [SupportSupervisorController::class, 'index'])
            ->name('dashboard');

        Route::post('/tickets/{ticket}/acknowledge', [SupportSupervisorController::class, 'adminAcknowledge'])
            ->name('tickets.acknowledge')->middleware('idempotent:8');

        Route::post('/tickets/{ticket}/start', [SupportSupervisorController::class, 'adminStartSla'])
            ->name('tickets.start')->middleware('idempotent:8');

        Route::post('/tickets/{ticket}/admin-classify-assign', [SupportSupervisorController::class, 'adminClassifyAndAssign'])
            ->name('tickets.admin-classify-assign')->middleware('idempotent:8');

        Route::post('/tickets/{ticket}/reassign', [SupportSupervisorController::class, 'adminReassign'])
            ->name('tickets.reassign')->middleware('idempotent:8');

        Route::post('/tickets/{ticket}/validate-resolution', [SupportSupervisorController::class, 'adminValidateResolution'])
            ->name('tickets.validate-resolution')->middleware('idempotent:8');

        Route::post('/tickets/{ticket}/escalate-manager', [SupportSupervisorController::class, 'escalateToManager'])
            ->name('tickets.escalate-manager')->middleware('idempotent:8');
    });

Route::middleware(['auth', 'role:Supervisor - Support Specialist', 'throttle:ticket-actions'])
    ->prefix('supervisor/support')
    ->name('supervisor.support.')
    ->group(function () {
        Route::get('/dashboard', [SupportSupervisorController::class, 'supportIndex'])
            ->name('dashboard');

        Route::post('/tickets/{ticket}/acknowledge', [SupportSupervisorController::class, 'supportAcknowledge'])
            ->name('tickets.acknowledge')->middleware('idempotent:8');

        Route::post('/tickets/{ticket}/classify-assign', [SupportSupervisorController::class, 'classifyAndAssign'])
            ->name('tickets.classify-assign')->middleware('idempotent:8');

        Route::post('/tickets/{ticket}/reassign', [SupportSupervisorController::class, 'reassign'])
            ->name('tickets.reassign')->middleware('idempotent:8');

        Route::post('/tickets/{ticket}/takeover', [SupportSupervisorController::class, 'takeover'])
            ->name('tickets.takeover')->middleware('idempotent:8');

        Route::post('/tickets/{ticket}/update', [SupportSupervisorController::class, 'supportUpdate'])
            ->name('tickets.update')->middleware('idempotent:8');

        Route::post('/tickets/{ticket}/resolve', [SupportSupervisorController::class, 'supportResolve'])
            ->name('tickets.resolve')->middleware('idempotent:8');

        Route::post('/tickets/{ticket}/validate-resolution', [SupportSupervisorController::class, 'validateResolution'])
            ->name('tickets.validate-resolution')->middleware('idempotent:8');

        Route::post('/tickets/{ticket}/escalate-admin', [SupportSupervisorController::class, 'escalateToAdmin'])
            ->name('tickets.escalate-admin')->middleware('idempotent:8');
    });

// ── Executive routes
Route::middleware(['auth', 'role:Manager', 'throttle:ticket-actions'])
    ->prefix('executive')
    ->name('executive.')
    ->group(function () {
        Route::get('/dashboard', [ExecutiveDashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/data', [ExecutiveDashboardController::class, 'data'])->name('dashboard.data'); // ← add this

        Route::get('/tickets', [ManagerTicketController::class, 'index'])->name('tickets.index');
        Route::post('/tickets/{ticket}/acknowledge', [ManagerTicketController::class, 'acknowledge'])->name('tickets.acknowledge')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/resolve', [ManagerTicketController::class, 'resolve'])->name('tickets.resolve')->middleware('idempotent:8');
        Route::get('/tickets/{ticket}/history', [ManagerTicketController::class, 'history'])->name('tickets.history');
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

// ── Profile (all roles)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});