<?php

namespace App\Console\Commands;

use App\Mail\TicketAssignedMail;
use App\Mail\TicketAwaitingConfirmationMail;
use App\Models\ReclassificationRequest;
use App\Models\Tickets;
use App\Models\User;
use App\Support\TicketReportProgress;
use App\Support\TicketStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class RemindStaleTickets extends Command
{
    protected $signature = 'tickets:remind-stale';

    protected $description = 'Re-notify Helpdesk/supervisors/assignees when a request has sat too long in For Acknowledgment, For Classification, For Reclassification, or Report For Review — and re-notify the employee when their request is still Awaiting Your Confirmation.';

    // How long a request can sit untouched before its first reminder, and the
    // repeat gap after that — a ticket still stuck at the next check gets
    // nudged again rather than only ever reminding once.
    private const THRESHOLD_HOURS = 1;

    public function handle(): int
    {
        $sent = 0;

        $sent += $this->remindClassification(
            statuses: [TicketStatus::CLASSIFIED],
            queue: TicketStatus::QUEUE_SUPPORT_SUPERVISOR,
            notifyRole: 'Supervisor - Support Specialist',
            dashboardRoute: 'supervisor.support.dashboard',
        );

        $sent += $this->remindClassification(
            statuses: [TicketStatus::FOR_ACKNOWLEDGMENT, TicketStatus::CLASSIFIED],
            queue: TicketStatus::QUEUE_ADMIN_SUPERVISOR,
            notifyRole: 'Supervisor - IT Admin',
            dashboardRoute: 'supervisor.dashboard',
        );

        // Helpdesk's own queue — a fresh ticket sits "For Acknowledgment" until
        // Helpdesk acknowledges it, then "For Classification" until they classify
        // it and route it onward. Both are Helpdesk's own action items, before
        // anything reaches a supervisor queue.
        $sent += $this->remindClassification(
            statuses: [TicketStatus::FOR_ACKNOWLEDGMENT],
            queue: TicketStatus::QUEUE_HELPDESK,
            notifyRole: 'Helpdesk',
            dashboardRoute: 'helpdesk.dashboard',
            remindedAtColumn: 'helpdesk_ack_reminded_at',
            queueLabel: 'acknowledgment',
        );

        $sent += $this->remindClassification(
            statuses: [TicketStatus::FOR_CLASSIFICATION],
            queue: TicketStatus::QUEUE_HELPDESK,
            notifyRole: 'Helpdesk',
            dashboardRoute: 'helpdesk.dashboard',
            remindedAtColumn: 'helpdesk_classification_reminded_at',
            queueLabel: 'classification',
            requireUnassigned: false,
        );

        $sent += $this->remindReclassification(
            resolverRoles: ['IT Support Specialist'],
            supervisorRole: 'Supervisor - Support Specialist',
            dashboardRoute: 'supervisor.support.dashboard',
        );

        $sent += $this->remindReclassification(
            resolverRoles: ['IT Admin'],
            supervisorRole: 'Supervisor - IT Admin',
            dashboardRoute: 'supervisor.dashboard',
        );

        $sent += $this->remindReportReview(
            resolverRoles: ['IT Support Specialist', 'Helpdesk'],
            supervisorRole: 'Supervisor - Support Specialist',
            dashboardRoute: 'supervisor.support.dashboard',
        );

        $sent += $this->remindReportReview(
            resolverRoles: ['IT Admin'],
            supervisorRole: 'Supervisor - IT Admin',
            dashboardRoute: 'supervisor.dashboard',
        );

        // Individually-assigned queues — the ticket already belongs to one
        // specific specialist/admin (assigned_to), so the reminder goes to
        // them directly rather than to a role-wide supervisor broadcast.
        $sent += $this->remindAcknowledgment(
            resolverRoles: ['IT Support Specialist'],
            dashboardRoute: 'technician.dashboard',
        );

        $sent += $this->remindAcknowledgment(
            resolverRoles: ['IT Admin'],
            dashboardRoute: 'admin.dashboard',
        );

        $sent += $this->remindRequestorConfirmation();

        $this->info("Stale-request reminder check complete. {$sent} reminder(s) sent.");

        return self::SUCCESS;
    }

    private function remindClassification(
        array $statuses,
        string $queue,
        string $notifyRole,
        string $dashboardRoute,
        string $remindedAtColumn = 'classification_reminded_at',
        string $queueLabel = 'classification',
        bool $requireUnassigned = true,
    ): int {
        $query = Tickets::whereIn('status', $statuses)->where('pending_role', $queue);

        if ($requireUnassigned) {
            $query->whereNull('assigned_to');
        }

        $tickets = $query->get();

        $sent = 0;

        foreach ($tickets as $ticket) {
            $checkpoint = $ticket->{$remindedAtColumn}
                ?? $this->statusEnteredAt($ticket, $ticket->status);

            if (!$this->dueForReminder($checkpoint)) {
                continue;
            }

            $this->notifySupervisors($notifyRole, $ticket, $dashboardRoute, $queueLabel);
            $ticket->update([$remindedAtColumn => now()]);
            $sent++;
        }

        return $sent;
    }

    private function remindReportReview(array $resolverRoles, string $supervisorRole, string $dashboardRoute): int
    {
        $tickets = TicketReportProgress::forRoles(Tickets::query(), TicketStatus::REPORT_FOR_REVIEW, $resolverRoles)->get();

        $sent = 0;

        foreach ($tickets as $ticket) {
            $checkpoint = $ticket->report_review_reminded_at
                ?? $this->statusEnteredAt($ticket, TicketStatus::REPORT_FOR_REVIEW);

            if (!$this->dueForReminder($checkpoint)) {
                continue;
            }

            $this->notifySupervisors($supervisorRole, $ticket, $dashboardRoute, 'report review');
            $ticket->update(['report_review_reminded_at' => now()]);
            $sent++;
        }

        return $sent;
    }

    private function remindReclassification(array $resolverRoles, string $supervisorRole, string $dashboardRoute): int
    {
        $requests = ReclassificationRequest::where('status', 'pending')
            ->whereHas('ticket.assignedTo.role', fn ($q) => $q->whereIn('role_name', $resolverRoles))
            ->with('ticket')
            ->get();

        $sent = 0;

        foreach ($requests as $request) {
            if (!$request->ticket) {
                continue;
            }

            $checkpoint = $request->reminded_at ?? $request->requested_at;

            if (!$this->dueForReminder($checkpoint)) {
                continue;
            }

            $this->notifySupervisors($supervisorRole, $request->ticket, $dashboardRoute, 'reclassification');
            $request->update(['reminded_at' => now()]);
            $sent++;
        }

        return $sent;
    }

    private function remindAcknowledgment(array $resolverRoles, string $dashboardRoute): int
    {
        $tickets = Tickets::where('status', TicketStatus::ASSIGNED)
            ->whereNull('tech_acknowledged_at')
            ->whereHas('assignedTo.role', fn ($q) => $q->whereIn('role_name', $resolverRoles))
            ->with('assignedTo')
            ->get();

        $sent = 0;

        foreach ($tickets as $ticket) {
            if (!$ticket->assignedTo || !$ticket->assignedTo->email) {
                continue;
            }

            $checkpoint = $ticket->tech_ack_reminded_at
                ?? $this->statusEnteredAt($ticket, TicketStatus::ASSIGNED);

            if (!$this->dueForReminder($checkpoint)) {
                continue;
            }

            Mail::to($ticket->assignedTo->email)->send(
                new TicketAssignedMail(
                    $ticket,
                    'Reminder: this ticket has been assigned to you and still needs your acknowledgement.',
                    $dashboardRoute,
                )
            );
            $ticket->update(['tech_ack_reminded_at' => now()]);
            $sent++;
        }

        return $sent;
    }

    private function remindRequestorConfirmation(): int
    {
        $tickets = Tickets::where('status', TicketStatus::REQUESTOR_CONFIRMATION)->get();

        $sent = 0;

        foreach ($tickets as $ticket) {
            if (!$ticket->user || !$ticket->user->email) {
                continue;
            }

            $checkpoint = $ticket->requestor_confirmation_reminded_at
                ?? $this->statusEnteredAt($ticket, TicketStatus::REQUESTOR_CONFIRMATION);

            if (!$this->dueForReminder($checkpoint)) {
                continue;
            }

            Mail::to($ticket->user->email)->send(new TicketAwaitingConfirmationMail($ticket, isReminder: true));
            $ticket->update(['requestor_confirmation_reminded_at' => now()]);
            $sent++;
        }

        return $sent;
    }

    private function dueForReminder(?Carbon $checkpoint): bool
    {
        return $checkpoint !== null && $checkpoint->diffInHours(now()) >= self::THRESHOLD_HOURS;
    }

    private function statusEnteredAt(Tickets $ticket, string $status): Carbon
    {
        $history = $ticket->statusHistories()
            ->where('new_status', $status)
            ->orderByDesc('changed_at')
            ->first();

        return $history->changed_at ?? $ticket->updated_at;
    }

    private function notifySupervisors(string $supervisorRole, Tickets $ticket, string $dashboardRoute, string $queueLabel): void
    {
        $supervisors = User::withActiveRole($supervisorRole)->get();

        foreach ($supervisors as $supervisor) {
            Mail::to($supervisor->email)->send(
                new TicketAssignedMail(
                    $ticket,
                    "Reminder: this request has been sitting in {$queueLabel} for over " . self::THRESHOLD_HOURS . " hour(s) and still needs your action.",
                    $dashboardRoute,
                )
            );
        }
    }
}
