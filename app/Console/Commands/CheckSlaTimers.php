<?php

namespace App\Console\Commands;

use App\Mail\SlaAlertMail;
use App\Models\Tickets;
use App\Models\User;
use App\Support\TicketStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class CheckSlaTimers extends Command
{
    protected $signature = 'sla:check';

    protected $description = 'Check in-progress tickets against their SLA deadline and email supervisors on at-risk/breach';

    // In Progress Service Report is shared across every resolver track (see
    // TicketReportProgress) — the resolution SLA clock is still running there (it
    // only stops once resolved_at is set, at the Done Service Report submit), so
    // it has to stay in the scan. Routing by the assignee's role (rather than a
    // fixed status → role map) is what lets one shared drafting status still reach
    // the right supervisor for whichever track it actually belongs to. Every
    // track's "actively fixing it" status collapses to one shared value now, so
    // this list is just the two live-work statuses.
    private const TRACKED_STATUSES = [
        TicketStatus::IN_PROGRESS_SERVICE_REQUEST,
        TicketStatus::IN_PROGRESS_SERVICE_REPORT,
    ];

    private const RESOLVER_ROLE_SUPERVISOR = [
        'IT Support Specialist' => 'Supervisor - Support Specialist',
        'Helpdesk' => 'Supervisor - Support Specialist',
        'Supervisor - Support Specialist' => 'Supervisor - Support Specialist',
        'IT Admin' => 'Supervisor - IT Admin',
    ];

    public function handle(): int
    {
        $tickets = Tickets::whereIn('status', self::TRACKED_STATUSES)
            ->whereNotNull('sla_due_at')
            ->with('assignedTo.role')
            ->get();

        $supervisorsByRole = collect(self::RESOLVER_ROLE_SUPERVISOR)
            ->unique()
            ->mapWithKeys(function ($roleName) {
                $supervisors = User::whereHas('role', fn($q) => $q->where('role_name', $roleName))
                    ->where('active', true)
                    ->get();

                if ($supervisors->isEmpty()) {
                    $this->warn("No active {$roleName} users found — skipping SLA emails for that queue.");
                }

                return [$roleName => $supervisors];
            });

        $sent = 0;

        foreach ($tickets as $ticket) {
            $resolverRole = $ticket->assignedTo?->role?->role_name;
            $supervisorRole = self::RESOLVER_ROLE_SUPERVISOR[$resolverRole] ?? null;
            $supervisors = $supervisorRole ? ($supervisorsByRole->get($supervisorRole) ?? collect()) : collect();

            if ($ticket->isSlaBreached() && !$ticket->sla_breached_notified_at) {
                foreach ($supervisors as $supervisor) {
                    Mail::to($supervisor->email)->send(new SlaAlertMail($ticket, 'breached'));
                }
                $ticket->update(['sla_breached_notified_at' => now()]);
                $sent++;
                continue;
            }

            if ($ticket->isSlaAtRisk() && !$ticket->sla_risk_notified_at) {
                foreach ($supervisors as $supervisor) {
                    Mail::to($supervisor->email)->send(new SlaAlertMail($ticket, 'at_risk'));
                }
                $ticket->update(['sla_risk_notified_at' => now()]);
                $sent++;
            }
        }

        $this->info("SLA check complete. {$sent} alert(s) sent for " . $tickets->count() . ' in-progress ticket(s) with an SLA deadline.');

        return self::SUCCESS;
    }
}
