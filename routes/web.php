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
use App\Http\Controllers\Admin\WorkloadClassController;
use App\Http\Controllers\Admin\HolidayController;
use App\Http\Controllers\Admin\LeaveController;
use App\Http\Controllers\Dashboard\WorkHoursCalendarController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\WebAuthController;
use App\Http\Controllers\Auth\MicrosoftController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SSOController;
use App\Http\Controllers\Dashboard\SupervisorDashboardController as SupportSupervisorController;
;

// ── Root: send authenticated users to their dashboard, everyone else to login
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route(auth()->user()->dashboardRoute())
        : redirect('/login');
});

// ── Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [WebAuthController::class, 'login'])->middleware('throttle:login');

    Route::get('/auth/microsoft/redirect', [MicrosoftController::class, 'redirect'])->name('auth.microsoft.redirect');
    Route::get('/auth/microsoft/callback', [MicrosoftController::class, 'callback'])->name('auth.microsoft.callback');
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
        Route::post('/onboarding/complete', [EmployeeTicketsController::class, 'completeOnboarding'])->name('onboarding.complete');
    });

// ── "My Requests" — lets ICT staff roles file and track their own support
// requests the same way an Employee does (e.g. a Technician's own laptop
// issue), instead of only ever being on the fulfilling side of a ticket.
// Reuses EmployeeTicketsController's self-file branch (already keyed off
// users_id === Auth::id(), not role) and the same dashboard.employee /
// employee.ticket-detail views — TicketsController computes $routePrefix
// so those views resolve to these route names instead of employee.*.
Route::middleware(['auth', 'role:Helpdesk,IT Support Specialist,Supervisor - Support Specialist,IT Admin,Supervisor - IT Admin,Manager', 'throttle:ticket-actions'])
    ->prefix('my-requests')
    ->name('my-requests.')
    ->group(function () {
        Route::get('/dashboard', [EmployeeTicketsController::class, 'index'])->name('tickets.index');
        Route::post('/tickets', [EmployeeTicketsController::class, 'store'])->name('tickets.store')->middleware(['throttle:ticket-submit', 'idempotent:10']);
        Route::get('/tickets/{ticket}', [EmployeeTicketsController::class, 'show'])->name('tickets.show');
        Route::patch('/tickets/{ticket}/cancel', [EmployeeTicketsController::class, 'cancel'])->name('tickets.cancel')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/feedback', [EmployeeTicketsController::class, 'storeFeedback'])->name('tickets.feedback')->middleware('idempotent:8');
        Route::patch('/tickets/{ticket}/acknowledge', [EmployeeTicketsController::class, 'acknowledge'])->name('tickets.acknowledge')->middleware('idempotent:8');
    });

Route::middleware(['auth', 'role:Helpdesk,IT Admin,Supervisor - IT Admin,Supervisor - Support Specialist'])
    ->prefix('portal')
    ->name('portal.')
    ->group(function () {
        // User management
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::get('/users/presence', [UserManagementController::class, 'presence'])->name('users.presence');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::get('/users/{user}', [UserManagementController::class, 'show'])->name('users.show');
        Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
        Route::patch('/users/{user}/deactivate', [UserManagementController::class, 'deactivate'])->name('users.deactivate');
        Route::patch('/users/{user}/reactivate', [UserManagementController::class, 'reactivate'])->name('users.reactivate');
        Route::patch('/users/{user}/reset-password', [UserManagementController::class, 'resetPassword'])->name('users.reset-password');


        // Audit log  ← now correctly INSIDE the admin group
        Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log');
        Route::get('/audit-log/export', [AuditLogController::class, 'export'])->name('audit-log.export');

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
    });

// ── SLA Rules — also open to Supervisor - Support Specialist, since they classify
// tickets against these rules and can already override them per-ticket at assignment.
Route::middleware(['auth', 'role:Helpdesk,IT Admin,Supervisor - IT Admin,Supervisor - Support Specialist'])
    ->prefix('portal')
    ->name('portal.')
    ->group(function () {
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

        // ── Workload classes (Quick Fix / Standard / Complex / Major / Project / Vendor)
        Route::post('/sla-rules/workload-classes', [WorkloadClassController::class, 'store'])->name('sla-rules.workload-class.store');
        Route::put('/sla-rules/workload-classes/{workloadClass}', [WorkloadClassController::class, 'update'])->name('sla-rules.workload-class.update');
        Route::delete('/sla-rules/workload-classes/{workloadClass}', [WorkloadClassController::class, 'destroy'])->name('sla-rules.workload-class.destroy');
        Route::patch('/sla-rules/workload-classes/{workloadClass}/toggle', [WorkloadClassController::class, 'toggle'])->name('sla-rules.workload-class.toggle');

        // ── Holidays — org-wide calendar consulted by App\Support\BusinessClock so
        // "next working day" scheduling skips them the same way it skips weekends.
        Route::get('/holidays', [HolidayController::class, 'index'])->name('holidays.index');
        Route::post('/holidays', [HolidayController::class, 'store'])->name('holidays.store');
        Route::put('/holidays/{holiday}', [HolidayController::class, 'update'])->name('holidays.update');
        Route::delete('/holidays/{holiday}', [HolidayController::class, 'destroy'])->name('holidays.destroy');
        Route::patch('/holidays/{holiday}/toggle', [HolidayController::class, 'toggle'])->name('holidays.toggle');

        // ── Leave — per-technician approved-leave calendar consulted by
        // App\Services\TicketScheduler alongside holidays/weekends, so "next working
        // day" scheduling and freeMinutesToday()/statusFor() skip a technician's own
        // leave days too.
        Route::get('/leaves', [LeaveController::class, 'index'])->name('leaves.index');
        Route::post('/leaves', [LeaveController::class, 'store'])->name('leaves.store');
        Route::put('/leaves/{leave}', [LeaveController::class, 'update'])->name('leaves.update');
        Route::delete('/leaves/{leave}', [LeaveController::class, 'destroy'])->name('leaves.destroy');
        Route::patch('/leaves/{leave}/toggle', [LeaveController::class, 'toggle'])->name('leaves.toggle');
    });
// ── Helpdesk routes
Route::middleware(['auth', 'role:Helpdesk', 'throttle:ticket-actions'])
    ->prefix('helpdesk')
    ->name('helpdesk.')
    ->group(function () {
        Route::get('/dashboard', [HelpdeskTicketController::class, 'index'])->name('dashboard');
        Route::post('/tickets/{ticket}/acknowledge', [HelpdeskTicketController::class, 'acknowledge'])->name('tickets.acknowledge')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/classify', [HelpdeskTicketController::class, 'classify'])->name('tickets.classify')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/cancel', [HelpdeskTicketController::class, 'cancel'])->name('tickets.cancel')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/assign', [HelpdeskTicketController::class, 'assign'])->name('tickets.assign')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/reassign', [HelpdeskTicketController::class, 'reassign'])->name('tickets.reassign')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/escalate', [HelpdeskTicketController::class, 'escalate'])->name('tickets.escalate')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/update', [HelpdeskTicketController::class, 'update'])->name('tickets.update')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/start-report', [HelpdeskTicketController::class, 'startReport'])->name('tickets.start-report')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/resolve', [HelpdeskTicketController::class, 'resolve'])->name('tickets.resolve')->middleware('idempotent:8');
        Route::post('/tickets', [EmployeeTicketsController::class, 'store'])->name('tickets.store')->middleware(['throttle:ticket-submit', 'idempotent:10']); // ← reuse employee store

        Route::get('/tickets/{ticket}', [HelpdeskTicketController::class, 'show'])->name('tickets.show');
    });

// ── IT Support Specialist routes
Route::middleware(['auth', 'role:IT Support Specialist', 'throttle:ticket-actions'])
    ->prefix('technician')
    ->name('technician.')
    ->group(function () {
        Route::get('/dashboard', [TechnicianTicketController::class, 'index'])->name('dashboard');
        Route::get('/calendar', [WorkHoursCalendarController::class, 'index'])->name('calendar');
        Route::post('/tickets/{ticket}/acknowledge', [TechnicianTicketController::class, 'acknowledge'])->name('tickets.acknowledge')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/start', [TechnicianTicketController::class, 'start'])->name('tickets.start')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/decline', [TechnicianTicketController::class, 'decline'])->name('tickets.decline')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/update', [TechnicianTicketController::class, 'update'])->name('tickets.update')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/start-report', [TechnicianTicketController::class, 'startReport'])->name('tickets.start-report')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/resolve', [TechnicianTicketController::class, 'resolve'])->name('tickets.resolve')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/escalate', [TechnicianTicketController::class, 'escalate'])->name('tickets.escalate')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/request-reclassification', [TechnicianTicketController::class, 'requestReclassification'])->name('tickets.request-reclassification')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/self-triage', [TechnicianTicketController::class, 'selfTriage'])->name('tickets.self-triage')->middleware('idempotent:8');
    });

// ── IT Admin routes
Route::middleware(['auth', 'role:IT Admin', 'throttle:ticket-actions'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // Escalation dashboard
        Route::get('/dashboard', [AdminTicketController::class, 'index'])->name('dashboard');
        Route::get('/calendar', [WorkHoursCalendarController::class, 'index'])->name('calendar');
        Route::post('/tickets/{ticket}/acknowledge', [AdminTicketController::class, 'acknowledge'])->name('tickets.acknowledge')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/start', [AdminTicketController::class, 'start'])->name('tickets.start')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/decline', [AdminTicketController::class, 'decline'])->name('tickets.decline')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/reassign', [AdminTicketController::class, 'reassign'])->name('tickets.reassign')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/takeover', [AdminTicketController::class, 'takeover'])->name('tickets.takeover')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/start-report', [AdminTicketController::class, 'startReport'])->name('tickets.start-report')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/resolve', [AdminTicketController::class, 'resolve'])->name('tickets.resolve')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/escalate', [AdminTicketController::class, 'escalate'])->name('tickets.escalate')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/request-reclassification', [AdminTicketController::class, 'requestReclassification'])->name('tickets.request-reclassification')->middleware('idempotent:8');
        Route::get('/tickets/{ticket}/history', [AdminTicketController::class, 'history'])->name('tickets.history');
        Route::get('/tickets/{ticket}', [AdminTicketController::class, 'show'])->name('tickets.show');
    });

Route::middleware(['auth', 'role:Supervisor - IT Admin', 'throttle:ticket-actions'])
    ->prefix('supervisor')
    ->name('supervisor.')
    ->group(function () {
        Route::get('/dashboard', [SupportSupervisorController::class, 'index'])
            ->name('dashboard');

        Route::get('/calendar', [WorkHoursCalendarController::class, 'index'])
            ->name('calendar');

        Route::post('/tickets/{ticket}/acknowledge', [SupportSupervisorController::class, 'adminAcknowledge'])
            ->name('tickets.acknowledge')->middleware('idempotent:8');

        Route::post('/tickets/{ticket}/start', [SupportSupervisorController::class, 'adminStartSla'])
            ->name('tickets.start')->middleware('idempotent:8');

        Route::post('/tickets/{ticket}/admin-classify-assign', [SupportSupervisorController::class, 'adminClassifyAndAssign'])
            ->name('tickets.admin-classify-assign')->middleware('idempotent:8');

        Route::post('/tickets/{ticket}/reassign', [SupportSupervisorController::class, 'adminReassign'])
            ->name('tickets.reassign')->middleware('idempotent:8');

        Route::post('/tickets/{ticket}/takeover', [SupportSupervisorController::class, 'adminTakeover'])
            ->name('tickets.takeover')->middleware('idempotent:8');

        Route::post('/tickets/{ticket}/validate-resolution', [SupportSupervisorController::class, 'adminValidateResolution'])
            ->name('tickets.validate-resolution')->middleware('idempotent:8');

        Route::post('/tickets/{ticket}/request-revision', [SupportSupervisorController::class, 'adminRequestRevision'])
            ->name('tickets.request-revision')->middleware('idempotent:8');

        Route::post('/tickets/{ticket}/escalate-manager', [SupportSupervisorController::class, 'escalateToManager'])
            ->name('tickets.escalate-manager')->middleware('idempotent:8');

        Route::post('/tickets/{ticket}/approve-reclassification', [SupportSupervisorController::class, 'adminApproveReclassification'])
            ->name('tickets.approve-reclassification')->middleware('idempotent:8');

        Route::post('/tickets/{ticket}/reject-reclassification', [SupportSupervisorController::class, 'adminRejectReclassification'])
            ->name('tickets.reject-reclassification')->middleware('idempotent:8');
    });

Route::middleware(['auth', 'role:Supervisor - Support Specialist', 'throttle:ticket-actions'])
    ->prefix('supervisor/support')
    ->name('supervisor.support.')
    ->group(function () {
        Route::get('/dashboard', [SupportSupervisorController::class, 'supportIndex'])
            ->name('dashboard');

        Route::get('/calendar', [WorkHoursCalendarController::class, 'index'])
            ->name('calendar');

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

        Route::post('/tickets/{ticket}/start-report', [SupportSupervisorController::class, 'supportStartReport'])
            ->name('tickets.start-report')->middleware('idempotent:8');

        Route::post('/tickets/{ticket}/resolve', [SupportSupervisorController::class, 'supportResolve'])
            ->name('tickets.resolve')->middleware('idempotent:8');

        Route::post('/tickets/{ticket}/validate-resolution', [SupportSupervisorController::class, 'validateResolution'])
            ->name('tickets.validate-resolution')->middleware('idempotent:8');

        Route::post('/tickets/{ticket}/request-revision', [SupportSupervisorController::class, 'requestRevision'])
            ->name('tickets.request-revision')->middleware('idempotent:8');

        Route::post('/tickets/{ticket}/escalate-admin', [SupportSupervisorController::class, 'escalateToAdmin'])
            ->name('tickets.escalate-admin')->middleware('idempotent:8');

        Route::post('/tickets/{ticket}/cannot-resolve', [SupportSupervisorController::class, 'cannotResolve'])
            ->name('tickets.cannot-resolve')->middleware('idempotent:8');

        Route::post('/tickets/{ticket}/approve-reclassification', [SupportSupervisorController::class, 'approveReclassification'])
            ->name('tickets.approve-reclassification')->middleware('idempotent:8');

        Route::post('/tickets/{ticket}/reject-reclassification', [SupportSupervisorController::class, 'rejectReclassification'])
            ->name('tickets.reject-reclassification')->middleware('idempotent:8');

        Route::get('/tickets/{ticket}', [SupportSupervisorController::class, 'show'])
            ->name('tickets.show');
    });

// ── Executive routes
Route::middleware(['auth', 'role:Manager', 'throttle:ticket-actions'])
    ->prefix('executive')
    ->name('executive.')
    ->group(function () {
        Route::get('/dashboard', [ExecutiveDashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/data', [ExecutiveDashboardController::class, 'data'])->name('dashboard.data'); // ← add this
        Route::get('/dashboard/active-tickets', [ExecutiveDashboardController::class, 'activeTickets'])->name('dashboard.active-tickets');

        Route::get('/tickets', [ManagerTicketController::class, 'index'])->name('tickets.index');
        Route::post('/tickets/{ticket}/acknowledge', [ManagerTicketController::class, 'acknowledge'])->name('tickets.acknowledge')->middleware('idempotent:8');
        Route::post('/tickets/{ticket}/start-report', [ManagerTicketController::class, 'startReport'])->name('tickets.start-report')->middleware('idempotent:8');
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
    Route::put('/profile/org-info', [ProfileController::class, 'updateOrgInfo'])->name('profile.org-info');
});

// ── Ticket attachment downloads/inline view (owner or staff — checked in the controller)
Route::middleware('auth')
    ->get('/attachments/{attachment}/download', [EmployeeTicketsController::class, 'downloadAttachment'])
    ->name('attachments.download');
Route::middleware('auth')
    ->get('/attachments/{attachment}/view', [EmployeeTicketsController::class, 'viewAttachment'])
    ->name('attachments.view');

// ── Ticket Service Report PDF (owner or staff — checked in the controller; ticket
// must be Closed)
Route::middleware('auth')
    ->get('/tickets/{ticket}/service-report', [\App\Http\Controllers\TicketServiceReportController::class, 'download'])
    ->name('tickets.service-report');
Route::middleware('auth')
    ->get('/tickets/{ticket}/service-report/preview', [\App\Http\Controllers\TicketServiceReportController::class, 'preview'])
    ->name('tickets.service-report.preview');