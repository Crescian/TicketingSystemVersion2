<?php

namespace Tests\Feature\Staff;

use App\Mail\TicketAutoClosedMail;
use App\Mail\TicketAwaitingConfirmationMail;
use App\Mail\TicketClosedByRequestorMail;
use App\Models\TicketStatusHistories;
use App\Models\Tickets;
use App\Support\TicketStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

// Awaiting Your Confirmation: the requestor gets one reminder per business day,
// and the ticket auto-closes 5 calendar days after entering the status, with
// the service report attached to the auto-close email.
class RequestorConfirmationReminderTest extends StaffTestCase
{
    private function manila(string $time): Carbon
    {
        return Carbon::parse($time, 'Asia/Manila');
    }

    // Monday 2026-11-02, 10:00 AM Manila.
    private function awaitingTicket(): Tickets
    {
        $this->travelTo($this->manila('2026-11-02 10:00'));

        $ticket = $this->staffTicket([
            'status' => TicketStatus::REQUESTOR_CONFIRMATION,
            'pending_role' => null,
            'resolution_notes' => 'Cleared the jam.',
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => TicketStatus::APPROVED_SERVICE_REPORT,
            'new_status' => TicketStatus::REQUESTOR_CONFIRMATION,
            'changed_at' => now(),
        ]);

        return $ticket;
    }

    private function remindersAt(string $time): int
    {
        Mail::fake();
        $this->travelTo($this->manila($time));
        $this->artisan('tickets:remind-stale')->assertSuccessful();

        return Mail::queued(TicketAwaitingConfirmationMail::class)->count();
    }

    public function test_requestor_is_reminded_once_per_day(): void
    {
        $this->awaitingTicket();

        $this->assertSame(0, $this->remindersAt('2026-11-02 15:00'), 'not on the day it entered confirmation');
        $this->assertSame(1, $this->remindersAt('2026-11-03 08:00'));
        $this->assertSame(0, $this->remindersAt('2026-11-03 09:00'), 'only once that day');
        $this->assertSame(0, $this->remindersAt('2026-11-03 16:00'));
        $this->assertSame(1, $this->remindersAt('2026-11-04 08:00'));
    }

    public function test_reminder_shows_the_auto_close_date(): void
    {
        $ticket = $this->awaitingTicket();

        $html = (new TicketAwaitingConfirmationMail($ticket->fresh(), isReminder: true))->render();

        $this->assertStringContainsString('Nov 7, 2026 10:00 AM', $html);
    }

    public function test_auto_closes_after_five_days_with_service_report_attached(): void
    {
        $ticket = $this->awaitingTicket();
        Mail::fake();

        $this->travelTo($this->manila('2026-11-07 09:59'));
        $this->artisan('tickets:auto-close-confirmations')->assertSuccessful();
        $this->assertSame(TicketStatus::REQUESTOR_CONFIRMATION, $ticket->fresh()->status);
        Mail::assertNothingQueued();

        $this->travelTo($this->manila('2026-11-07 10:00'));
        $this->artisan('tickets:auto-close-confirmations')->assertSuccessful();
        $this->assertSame(TicketStatus::CLOSED, $ticket->fresh()->status);

        $mail = Mail::queued(TicketAutoClosedMail::class)->sole();
        $attachments = $mail->attachments();

        $this->assertCount(1, $attachments);
        $this->assertSame("Service-Report-{$ticket->ticket_number}.pdf", $attachments[0]->as);

        $bytes = $attachments[0]->attachWith(fn () => null, fn ($data) => $data());
        $this->assertStringStartsWith('%PDF', $bytes);

        $this->assertStringContainsString('within 5 days', $mail->render());
    }

    public function test_requestor_confirmed_close_email_attaches_the_service_report(): void
    {
        $ticket = $this->awaitingTicket();

        $attachments = (new TicketClosedByRequestorMail($ticket->fresh()))->attachments();

        $this->assertCount(1, $attachments);
        $this->assertSame("Service-Report-{$ticket->ticket_number}.pdf", $attachments[0]->as);

        $bytes = $attachments[0]->attachWith(fn () => null, fn ($data) => $data());
        $this->assertStringStartsWith('%PDF', $bytes);
    }
}
