<?php

namespace App\Console\Commands;

use App\Mail\SlaAlertMail;
use App\Models\Tickets;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class CheckSlaTimers extends Command
{
    protected $signature = 'sla:check';

    protected $description = 'Check in-progress tickets against their SLA deadline and email supervisors on at-risk/breach';

    // Which supervisor role owns each SLA-tracked queue.
    private const QUEUE_SUPERVISOR_ROLES = [
        'In Progress' => 'Supervisor - Support Specialist',
        'Admin In Progress' => 'Supervisor - IT Admin',
    ];

    public function handle(): int
    {
        $tickets = Tickets::whereIn('status', array_keys(self::QUEUE_SUPERVISOR_ROLES))
            ->whereNotNull('sla_due_at')
            ->with('assignedTo')
            ->get();

        $supervisorsByRole = collect(self::QUEUE_SUPERVISOR_ROLES)
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
            $supervisors = $supervisorsByRole->get(self::QUEUE_SUPERVISOR_ROLES[$ticket->status]) ?? collect();

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
