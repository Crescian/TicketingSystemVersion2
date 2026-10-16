<?php

namespace App\Listeners;

use App\Mail\DeveloperConcernAcknowledgedMail;
use App\Mail\DeveloperConcernClosedMail;
use App\Mail\DeveloperConcernSubmittedMail;
use App\Mail\SlaAlertMail;
use App\Mail\TicketAssignedMail;
use App\Mail\TicketClosedByRequestorMail;
use App\Mail\TicketReadyForRequestorMail;
use App\Models\User;
use App\Support\StaffNotifier;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Every staff-facing email (assignment, escalation, reclassification, report
// review, SLA alert, FYI, developer concern…) is also a mobile notification
// for the same recipients — one hook instead of ~25 call sites. Listens to
// MessageSent (after the mail service accepted it), so a failed send that the
// queue retries doesn't notify once per attempt. Everything runs inside
// rescue(): a notification problem must never fail or retry the email.
class PushStaffMailNotification
{
    private const STAFF_MAILABLES = [
        TicketAssignedMail::class,
        SlaAlertMail::class,
        TicketReadyForRequestorMail::class,
        TicketClosedByRequestorMail::class,
        DeveloperConcernSubmittedMail::class,
        DeveloperConcernAcknowledgedMail::class,
        DeveloperConcernClosedMail::class,
    ];

    public function handle(MessageSent $event): void
    {
        rescue(function () use ($event) {
            $mailable = $event->data['__laravel_mailable'] ?? null;
            if (!in_array($mailable, self::STAFF_MAILABLES, true)) {
                return;
            }

            $emails = collect($event->message->getTo())->map(fn ($address) => strtolower($address->getAddress()));
            // Case-insensitive: stored emails can have capitals, mail addresses vary.
            $recipients = User::whereIn(DB::raw('lower(email)'), $emails->all())->get();
            if ($recipients->isEmpty()) {
                return;
            }

            [$type, $title, $body] = $this->describe($mailable, $event->data);
            $ticket = $event->data['ticket'] ?? null;
            $concern = $event->data['concern'] ?? null;

            StaffNotifier::notify($recipients, $type, $title, $body, $ticket?->id, $concern?->id);
        }, report: true);
    }

    // TicketAssignedMail also carries a couple of plain updates ("your
    // reclassification request was declined", "the employee updated the
    // request") — those aren't titled "Action needed".
    private const INFO_ONLY_PHRASES = ['declined', 'updated this request'];

    private function describe(string $mailable, array $data): array
    {
        $ticket = $data['ticket'] ?? null;
        $concern = $data['concern'] ?? null;
        $number = $ticket ? "#{$ticket->ticket_number}" : null;

        return match ($mailable) {
            TicketAssignedMail::class => [
                Str::contains($data['action'] ?? '', self::INFO_ONLY_PHRASES, true) ? 'update' : 'action_needed',
                (Str::contains($data['action'] ?? '', self::INFO_ONLY_PHRASES, true) ? 'Update' : 'Action needed') . " · {$number}",
                Str::limit(trim(($data['action'] ?? '') . ' ' . ($ticket?->subject ? "— {$ticket->subject}" : '')), 180),
            ],
            SlaAlertMail::class => [
                'sla_alert',
                ($data['level'] ?? '') === 'breached' ? "SLA breached · {$number}" : "SLA at risk · {$number}",
                $ticket?->subject,
            ],
            TicketReadyForRequestorMail::class => ['resolved_fyi', "Resolved · {$number}", Str::limit($data['note'] ?? $ticket?->subject ?? '', 180)],
            TicketClosedByRequestorMail::class => ['closed_by_requestor', "Closed by requester · {$number}", $ticket?->subject],
            DeveloperConcernSubmittedMail::class => ['concern_new', "New concern {$concern?->reference}", $concern?->subject],
            DeveloperConcernAcknowledgedMail::class => ['concern_acknowledged', "Concern {$concern?->reference} acknowledged", $concern?->subject],
            DeveloperConcernClosedMail::class => ['concern_closed', "Concern {$concern?->reference} closed", $concern?->closing_note ?? $concern?->subject],
        };
    }
}
