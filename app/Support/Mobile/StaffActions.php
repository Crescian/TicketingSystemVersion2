<?php

namespace App\Support\Mobile;

use App\Http\Controllers\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\Dashboard\SupervisorDashboardController as Supervisor;
use App\Http\Controllers\Helpdesk\TicketController as HelpdeskTicketController;
use App\Http\Controllers\Manager\TicketController as ManagerTicketController;
use App\Http\Controllers\Technician\TicketController as TechnicianTicketController;
use App\Models\Tickets;
use App\Models\User;
use App\Support\TicketStatus as S;

// Which ticket actions each staff role can take from the mobile app, mapped to
// the web controller method that performs it (run through WebBridge, so every
// rule, guard, mail and history row is exactly the web's). `when` mirrors the
// web dashboard's button conditions so the app only offers what the web would;
// the controller still enforces its own guards either way. `fields` must match
// the controller's validation (see Field).
//
// Deliberately absent, because the web UI never offers them: Helpdesk
// reassign/escalate, IT Admin takeover, IT Admin Supervisor takeover.
final class StaffActions
{
    public const ROLES = [
        'Helpdesk',
        'IT Support Specialist',
        'IT Admin',
        'Supervisor - Support Specialist',
        'Supervisor - IT Admin',
        'Manager',
    ];

    private const SERVICE_TYPES = ['Onsite', 'Remote', 'Preventive'];
    private const PRIORITIES = ['Critical', 'High', 'Medium', 'Low'];
    private const WORK_STATUSES = ['Actively working', 'Investigating', 'Waiting for parts or access'];
    private const DECLINE_REASONS = ['At full capacity', 'Outside my technical expertise', 'Requires hardware parts not available', 'Conflict of schedule', 'Other'];
    private const ESCALATION_REASONS = [
        'Issue requires admin-level system access',
        'Hardware fault beyond repair scope',
        'Requires vendor or manufacturer intervention',
        'Issue affects multiple users or systems',
        'Cannot diagnose root cause',
    ];
    private const ROOT_CAUSES = [
        'Hardware failure — replacement required',
        'Software / configuration error',
        'Network infrastructure issue',
        'User access / permissions',
        'Third-party vendor issue',
        'Human error',
        'Others',
    ];

    /** @return array<string, array> action key => definition */
    public static function forRole(?string $role): array
    {
        return match ($role) {
            'Helpdesk' => self::helpdesk(),
            'IT Support Specialist' => self::specialist(),
            'IT Admin' => self::itAdmin(),
            'Supervisor - Support Specialist' => self::supportSupervisor(),
            'Supervisor - IT Admin' => self::adminSupervisor(),
            'Manager' => self::manager(),
            default => [],
        };
    }

    public static function find(?string $role, string $action): ?array
    {
        return self::forRole($role)[$action] ?? null;
    }

    // The actions this user can take on this ticket right now, without the
    // internal controller/when keys — the shape the app renders.
    public static function availableFor(Tickets $ticket, User $user): array
    {
        $role = $user->role?->role_name;

        return collect(self::forRole($role))
            ->filter(fn ($def) => ($def['when'])($ticket, $user))
            ->map(fn ($def, $key) => [
                'key' => $key,
                'label' => $def['label'],
                'icon' => $def['icon'],
                'tone' => $def['tone'] ?? 'primary',
                'confirm' => $def['confirm'] ?? null,
                'fields' => $def['fields'] ?? [],
                'multipart' => collect($def['fields'] ?? [])->contains('type', 'files'),
            ])
            ->values()
            ->all();
    }

    // ── Shared building blocks ──────────────────────────────────────────────

    private static function action(string $controller, string $method, string $label, string $icon, callable $when, array $fields = [], array $extra = []): array
    {
        return ['controller' => $controller, 'method' => $method, 'label' => $label, 'icon' => $icon, 'when' => $when, 'fields' => $fields] + $extra;
    }

    private static function mine(Tickets $t, User $u): bool
    {
        return $t->assigned_to === $u->id;
    }

    private static function is(Tickets $t, string|array $statuses, ?string $queue = null): bool
    {
        return in_array($t->status, (array) $statuses, true) && ($queue === null || $t->pending_role === $queue);
    }

    private static function hasPendingReclassification(Tickets $t): bool
    {
        return $t->reclassificationRequests()->where('status', 'pending')->exists();
    }

    private static function updateFields(): array
    {
        return [
            Field::select('work_status', 'Work status', self::WORK_STATUSES, true, 'Actively working'),
            Field::textarea('progress_notes', 'Progress notes', true),
            Field::files(),
        ];
    }

    private static function resolveFields(string $defaultServiceType = 'Onsite', bool $withRootCause = false, bool $withReportExtras = true): array
    {
        return array_values(array_filter([
            Field::radio('service_type', 'Service type', self::SERVICE_TYPES, true, $defaultServiceType),
            $withRootCause ? Field::select('root_cause', 'Root cause', self::ROOT_CAUSES, true) : null,
            Field::textarea('resolution_notes', 'Service details / action taken', true),
            $withReportExtras ? Field::textarea('findings', 'Findings & analysis') : null,
            $withReportExtras ? Field::textarea('recommendation', 'Other observation / recommendation') : null,
            $withReportExtras ? Field::files() : null,
        ]));
    }

    private static function reclassifyFields(): array
    {
        return [
            Field::lookup('sla_rule_id', 'Correct category', 'sla_rules', true),
            Field::textarea('reason', 'Why does it need reclassifying?', true),
            Field::select('priority', 'Priority (optional)', self::PRIORITIES),
            Field::number('response_time_minutes', 'Response time (minutes, optional)'),
            Field::number('resolution_time_minutes', 'Resolution time (minutes, optional)'),
        ];
    }

    private static function classifyAssignFields(string $assigneeLabel): array
    {
        return [
            Field::lookup('technician_id', $assigneeLabel, 'assignees', false, 'Leave empty if you take it over yourself.'),
            Field::boolean('take_over', 'Take it over myself'),
            Field::lookup('sla_rule_id', 'Category (change only if needed)', 'sla_rules'),
            Field::select('priority', 'Priority (optional)', self::PRIORITIES),
            Field::lookup('workload_class_id', 'Workload class (optional)', 'workload_classes'),
            Field::number('response_time_minutes', 'Response time (minutes, optional)'),
            Field::number('resolution_time_minutes', 'Resolution time (minutes, optional)'),
            Field::textarea('notes', 'Notes for the assignee', false, 500),
        ];
    }

    // ── Roles ───────────────────────────────────────────────────────────────

    private static function helpdesk(): array
    {
        $c = HelpdeskTicketController::class;

        return [
            'acknowledge' => self::action($c, 'acknowledge', 'Acknowledge', 'checkmark-circle-outline',
                fn ($t) => self::is($t, S::FOR_ACKNOWLEDGMENT, S::QUEUE_HELPDESK),
                [], ['confirm' => 'Acknowledge this ticket? The requester can no longer edit or cancel it.']),
            'classify' => self::action($c, 'classify', 'Classify', 'pricetags-outline',
                fn ($t) => self::is($t, S::FOR_CLASSIFICATION, S::QUEUE_HELPDESK) && $t->date_acknowledged,
                [
                    Field::lookup('sla_rule_id', 'Category', 'sla_rules', true),
                    Field::select('priority', 'Priority (optional)', self::PRIORITIES),
                    Field::lookup('workload_class_id', 'Workload class (optional)', 'workload_classes'),
                    Field::number('response_time_minutes', 'Response time (minutes, optional)'),
                    Field::number('resolution_time_minutes', 'Resolution time (minutes, optional)'),
                    Field::textarea('notes', 'Notes', false, 500),
                    Field::boolean('handle_myself', 'Handle it myself', 'Only for categories Helpdesk can resolve.'),
                ]),
            'cancel' => self::action($c, 'cancel', 'Cancel ticket', 'close-circle-outline',
                fn ($t) => self::is($t, [S::FOR_ACKNOWLEDGMENT, S::FOR_CLASSIFICATION], S::QUEUE_HELPDESK),
                [Field::textarea('reason', 'Reason', true)], ['tone' => 'danger']),
            'update' => self::action($c, 'update', 'Add update', 'create-outline',
                fn ($t, $u) => self::is($t, [S::IN_PROGRESS_SERVICE_REQUEST, S::IN_PROGRESS_SERVICE_REPORT]) && self::mine($t, $u),
                self::updateFields(), ['tone' => 'secondary']),
            'start_report' => self::action($c, 'startReport', 'Mark fixed', 'construct-outline',
                fn ($t, $u) => self::is($t, S::IN_PROGRESS_SERVICE_REQUEST) && self::mine($t, $u),
                [], ['confirm' => 'Mark the fix as done? Next you prepare the service report.']),
            'pause' => self::action($c, 'pause', 'Pause — need info', 'pause-circle-outline',
                fn ($t, $u) => self::is($t, S::IN_PROGRESS_SERVICE_REQUEST) && self::mine($t, $u),
                [Field::textarea('reason', 'What do you need from the requester?', true, 1000)], ['tone' => 'secondary']),
            'resume' => self::action($c, 'resume', 'Resume work', 'play-circle-outline',
                fn ($t, $u) => self::is($t, S::ON_HOLD) && self::mine($t, $u)),
            'resolve' => self::action($c, 'resolve', 'Submit service report', 'document-text-outline',
                fn ($t, $u) => self::is($t, S::IN_PROGRESS_SERVICE_REPORT) && self::mine($t, $u),
                self::resolveFields('Remote')),
        ];
    }

    private static function specialist(): array
    {
        $c = TechnicianTicketController::class;

        return [
            'acknowledge' => self::action($c, 'acknowledge', 'Acknowledge', 'checkmark-circle-outline',
                fn ($t, $u) => self::is($t, S::ASSIGNED) && self::mine($t, $u) && !$t->tech_acknowledged_at,
                [Field::textarea('notes', 'Notes (optional)', false, 500)]),
            'start' => self::action($c, 'start', 'Start work', 'play-outline',
                fn ($t, $u) => self::is($t, S::ASSIGNED) && self::mine($t, $u) && $t->tech_acknowledged_at,
                [Field::textarea('notes', 'Notes (optional)', false, 500)]),
            'decline' => self::action($c, 'decline', 'Decline', 'return-up-back-outline',
                fn ($t, $u) => self::is($t, S::ASSIGNED) && self::mine($t, $u),
                [Field::select('reason', 'Reason', self::DECLINE_REASONS, true)], ['tone' => 'danger']),
            'update' => self::action($c, 'update', 'Add update', 'create-outline',
                fn ($t, $u) => self::is($t, [S::IN_PROGRESS_SERVICE_REQUEST, S::IN_PROGRESS_SERVICE_REPORT]) && self::mine($t, $u),
                self::updateFields(), ['tone' => 'secondary']),
            'start_report' => self::action($c, 'startReport', 'Mark fixed', 'construct-outline',
                fn ($t, $u) => self::is($t, S::IN_PROGRESS_SERVICE_REQUEST) && self::mine($t, $u),
                [], ['confirm' => 'Mark the fix as done? Next you prepare the service report.']),
            'resolve' => self::action($c, 'resolve', 'Submit service report', 'document-text-outline',
                fn ($t, $u) => self::is($t, S::IN_PROGRESS_SERVICE_REPORT) && self::mine($t, $u),
                self::resolveFields('Onsite')),
            'pause' => self::action($c, 'pause', 'Pause — need info', 'pause-circle-outline',
                fn ($t, $u) => self::is($t, S::IN_PROGRESS_SERVICE_REQUEST) && self::mine($t, $u),
                [Field::textarea('reason', 'What do you need from the requester?', true, 1000)], ['tone' => 'secondary']),
            'resume' => self::action($c, 'resume', 'Resume work', 'play-circle-outline',
                fn ($t, $u) => self::is($t, S::ON_HOLD) && self::mine($t, $u)),
            'escalate' => self::action($c, 'escalate', 'Escalate', 'arrow-up-circle-outline',
                fn ($t, $u) => self::is($t, [S::IN_PROGRESS_SERVICE_REQUEST, S::IN_PROGRESS_SERVICE_REPORT]) && self::mine($t, $u),
                [
                    Field::select('reason', 'Reason', self::ESCALATION_REASONS, true),
                    Field::textarea('already_tried', 'What have you already tried?', true),
                ], ['tone' => 'danger']),
            'request_reclassification' => self::action($c, 'requestReclassification', 'Request reclassification', 'swap-horizontal-outline',
                fn ($t, $u) => self::is($t, [S::IN_PROGRESS_SERVICE_REQUEST, S::IN_PROGRESS_SERVICE_REPORT]) && self::mine($t, $u),
                self::reclassifyFields(), ['tone' => 'secondary']),
            'self_triage' => self::action($c, 'selfTriage', 'Take it (Helpdesk away)', 'hand-left-outline',
                fn ($t) => self::is($t, S::FOR_ACKNOWLEDGMENT, S::QUEUE_HELPDESK) && !$t->date_acknowledged,
                [Field::lookup('sla_rule_id', 'Category', 'self_triage_rules', true)]),
        ];
    }

    private static function itAdmin(): array
    {
        $c = AdminTicketController::class;
        $specialist = self::specialist();

        return [
            'acknowledge' => ['controller' => $c] + $specialist['acknowledge'],
            'start' => ['controller' => $c] + $specialist['start'],
            'decline' => ['controller' => $c, 'fields' => [Field::textarea('reason', 'Reason', true)]] + $specialist['decline'],
            'start_report' => ['controller' => $c] + $specialist['start_report'],
            'resolve' => ['controller' => $c, 'fields' => self::resolveFields('Onsite', true)] + $specialist['resolve'],
            'pause' => ['controller' => $c] + $specialist['pause'],
            'resume' => ['controller' => $c] + $specialist['resume'],
            'escalate' => ['controller' => $c, 'fields' => [Field::textarea('reason', 'Reason', true)]] + $specialist['escalate'],
            'request_reclassification' => ['controller' => $c] + $specialist['request_reclassification'],
            'self_triage' => ['controller' => $c] + $specialist['self_triage'],
        ];
    }

    private static function supportSupervisor(): array
    {
        $c = Supervisor::class;
        $q = S::QUEUE_SUPPORT_SUPERVISOR;
        $classifiedUnassigned = fn ($t) => self::is($t, S::CLASSIFIED, $q) && !$t->assigned_to;
        $forReview = fn ($t) => self::is($t, S::REPORT_FOR_REVIEW)
            && in_array($t->assignedTo?->role?->role_name, ['IT Support Specialist', 'Helpdesk'], true);

        return [
            'acknowledge' => self::action($c, 'supportAcknowledge', 'Acknowledge', 'checkmark-circle-outline',
                fn ($t) => self::is($t, S::FOR_ACKNOWLEDGMENT, $q)),
            'classify_assign' => self::action($c, 'classifyAndAssign', 'Assign', 'person-add-outline',
                $classifiedUnassigned, self::classifyAssignFields('IT Support Specialist')),
            'return_to_helpdesk' => self::action($c, 'returnToHelpdesk', 'Return to Helpdesk', 'arrow-undo-outline',
                $classifiedUnassigned, [Field::textarea('reason', 'Reason', true, 1000)], ['tone' => 'secondary']),
            'escalate_admin' => self::action($c, 'escalateToAdmin', 'Escalate', 'arrow-up-circle-outline',
                fn ($t) => $classifiedUnassigned($t) || self::is($t, S::IN_PROGRESS_SERVICE_REQUEST) || self::is($t, S::ESCALATED, $q),
                [
                    Field::radio('level', 'Escalate to', [
                        ['value' => 'L3', 'label' => 'Supervisor - IT Admin (L3)'],
                        ['value' => 'L4', 'label' => 'Manager (L4)'],
                    ], true, 'L3'),
                    Field::textarea('reason', 'Reason', false, 1000, 'Required when escalating to the Manager.'),
                ], ['tone' => 'danger']),
            'approve_reclassification' => self::action($c, 'approveReclassification', 'Approve reclassification', 'checkmark-done-outline',
                fn ($t) => self::hasPendingReclassification($t), [Field::textarea('review_notes', 'Notes (optional)', false, 500)]),
            'reject_reclassification' => self::action($c, 'rejectReclassification', 'Reject reclassification', 'close-outline',
                fn ($t) => self::hasPendingReclassification($t), [Field::textarea('review_notes', 'Why?', true, 500)], ['tone' => 'danger']),
            'update' => self::action($c, 'supportUpdate', 'Add update', 'create-outline',
                fn ($t, $u) => self::is($t, [S::IN_PROGRESS_SERVICE_REQUEST, S::IN_PROGRESS_SERVICE_REPORT]) && self::mine($t, $u),
                array_slice(self::updateFields(), 0, 2), ['tone' => 'secondary']),
            'start_report' => self::action($c, 'supportStartReport', 'Mark fixed', 'construct-outline',
                fn ($t, $u) => self::is($t, S::IN_PROGRESS_SERVICE_REQUEST) && self::mine($t, $u),
                [], ['confirm' => 'Mark the fix as done? Next you prepare the service report.']),
            'pause' => self::action($c, 'supportPause', 'Pause — need info', 'pause-circle-outline',
                fn ($t, $u) => self::is($t, S::IN_PROGRESS_SERVICE_REQUEST) && self::mine($t, $u),
                [Field::textarea('reason', 'What do you need from the requester?', true, 1000)], ['tone' => 'secondary']),
            'resume' => self::action($c, 'supportResume', 'Resume work', 'play-circle-outline',
                fn ($t, $u) => self::is($t, S::ON_HOLD) && self::mine($t, $u)),
            'resolve' => self::action($c, 'supportResolve', 'Submit service report', 'document-text-outline',
                fn ($t, $u) => self::is($t, S::IN_PROGRESS_SERVICE_REPORT) && self::mine($t, $u),
                self::resolveFields('Onsite')),
            'reassign' => self::action($c, 'reassign', 'Reassign', 'people-outline',
                fn ($t) => self::is($t, S::ESCALATED, $q),
                [Field::lookup('technician_id', 'IT Support Specialist', 'assignees', true), Field::textarea('notes', 'Notes', false, 500)]),
            'takeover' => self::action($c, 'takeover', 'Take over', 'hand-right-outline',
                fn ($t) => self::is($t, S::ESCALATED, $q),
                [], ['confirm' => 'Take over this escalated ticket and start working on it?']),
            'cannot_resolve' => self::action($c, 'cannotResolve', 'Cannot resolve', 'ban-outline',
                fn ($t) => self::is($t, S::ESCALATED, $q),
                [Field::textarea('findings', 'Findings', true), Field::textarea('recommendation', 'Recommendation')], ['tone' => 'danger']),
            'validate' => self::action($c, 'validateResolution', 'Validate', 'shield-checkmark-outline',
                $forReview, [Field::textarea('validation_notes', 'Validation notes', true, 1000)]),
            'request_revision' => self::action($c, 'requestRevision', 'Request revision', 'refresh-outline',
                $forReview, [Field::textarea('revision_notes', 'What needs to change?', true, 1000)], ['tone' => 'secondary']),
        ];
    }

    private static function adminSupervisor(): array
    {
        $c = Supervisor::class;
        $q = S::QUEUE_ADMIN_SUPERVISOR;
        $mineIn = fn (string|array $status) => fn ($t, $u) => self::is($t, $status) && self::mine($t, $u);
        $forReview = fn ($t) => self::is($t, S::REPORT_FOR_REVIEW) && $t->assignedTo?->role?->role_name === 'IT Admin';

        return [
            'acknowledge' => self::action($c, 'adminAcknowledge', 'Acknowledge', 'checkmark-circle-outline',
                fn ($t) => self::is($t, [S::FOR_ACKNOWLEDGMENT, S::ESCALATED], $q) && !$t->assigned_to),
            'classify_assign' => self::action($c, 'adminClassifyAndAssign', 'Classify & assign', 'person-add-outline',
                fn ($t) => self::is($t, [S::FOR_ACKNOWLEDGMENT, S::CLASSIFIED], $q) && !$t->assigned_to,
                self::classifyAssignFields('IT Admin')),
            'return_to_helpdesk' => self::action($c, 'adminReturnToHelpdesk', 'Return to Helpdesk', 'arrow-undo-outline',
                fn ($t) => self::is($t, [S::FOR_ACKNOWLEDGMENT, S::CLASSIFIED, S::ESCALATED], $q) && !$t->assigned_to,
                [Field::textarea('reason', 'Reason', true, 1000)], ['tone' => 'secondary']),
            'approve_reclassification' => self::action($c, 'adminApproveReclassification', 'Approve reclassification', 'checkmark-done-outline',
                fn ($t) => self::hasPendingReclassification($t), [Field::textarea('review_notes', 'Notes (optional)', false, 500)]),
            'reject_reclassification' => self::action($c, 'adminRejectReclassification', 'Reject reclassification', 'close-outline',
                fn ($t) => self::hasPendingReclassification($t), [Field::textarea('review_notes', 'Why?', true, 500)], ['tone' => 'danger']),
            'start' => self::action($c, 'adminStartSla', 'Start work', 'play-outline', $mineIn(S::ASSIGNED)),
            'reassign' => self::action($c, 'adminReassign', 'Reassign', 'people-outline',
                fn ($t) => self::is($t, [S::IN_PROGRESS_SERVICE_REQUEST, S::IN_PROGRESS_SERVICE_REPORT]),
                [Field::lookup('technician_id', 'IT Admin', 'assignees', true), Field::textarea('notes', 'Notes', false, 500)], ['tone' => 'secondary']),
            'escalate_manager' => self::action($c, 'escalateToManager', 'Escalate to Manager', 'arrow-up-circle-outline',
                fn ($t) => self::is($t, [S::IN_PROGRESS_SERVICE_REQUEST, S::IN_PROGRESS_SERVICE_REPORT]),
                [Field::textarea('reason', 'Reason (optional)', false, 1000)], ['tone' => 'danger']),
            'start_report' => self::action($c, 'adminStartReport', 'Mark fixed', 'construct-outline', $mineIn(S::IN_PROGRESS_SERVICE_REQUEST),
                [], ['confirm' => 'Mark the fix as done? Next you prepare the service report.']),
            'pause' => self::action($c, 'adminPause', 'Pause — need info', 'pause-circle-outline', $mineIn(S::IN_PROGRESS_SERVICE_REQUEST),
                [Field::textarea('reason', 'What do you need from the requester?', true, 1000)], ['tone' => 'secondary']),
            'resume' => self::action($c, 'adminResume', 'Resume work', 'play-circle-outline', $mineIn(S::ON_HOLD)),
            'resolve' => self::action($c, 'adminResolve', 'Submit service report', 'document-text-outline', $mineIn(S::IN_PROGRESS_SERVICE_REPORT),
                self::resolveFields('Onsite')),
            'validate' => self::action($c, 'adminValidateResolution', 'Validate', 'shield-checkmark-outline',
                $forReview, [Field::textarea('validation_notes', 'Validation notes', true, 1000)]),
            'request_revision' => self::action($c, 'adminRequestRevision', 'Request revision', 'refresh-outline',
                $forReview, [Field::textarea('revision_notes', 'What needs to change?', true, 1000)], ['tone' => 'secondary']),
        ];
    }

    private static function manager(): array
    {
        $c = ManagerTicketController::class;
        $mineIn = fn (string $status) => fn ($t, $u) => self::is($t, $status) && self::mine($t, $u);

        return [
            'acknowledge' => self::action($c, 'acknowledge', 'Acknowledge & start', 'checkmark-circle-outline',
                fn ($t) => self::is($t, [S::FOR_ACKNOWLEDGMENT, S::ESCALATED], S::QUEUE_MANAGER),
                [], ['confirm' => 'Acknowledge this ticket? It is assigned to you and work starts now.']),
            'start_report' => self::action($c, 'startReport', 'Mark fixed', 'construct-outline', $mineIn(S::IN_PROGRESS_SERVICE_REQUEST),
                [], ['confirm' => 'Mark the fix as done? Next you prepare the service report.']),
            'pause' => self::action($c, 'pause', 'Pause — need info', 'pause-circle-outline', $mineIn(S::IN_PROGRESS_SERVICE_REQUEST),
                [Field::textarea('reason', 'What do you need from the requester?', true, 1000)], ['tone' => 'secondary']),
            'resume' => self::action($c, 'resume', 'Resume work', 'play-circle-outline', $mineIn(S::ON_HOLD)),
            'resolve' => self::action($c, 'resolve', 'Resolve', 'document-text-outline', $mineIn(S::IN_PROGRESS_SERVICE_REPORT),
                self::resolveFields('Onsite', false, false)),
        ];
    }
}
