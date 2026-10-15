<?php

namespace Tests\Feature;

use App\Jobs\SendTicketChatDigest;
use App\Mail\TicketAssignedMail;
use App\Mail\TicketSubmittedMail;
use App\Models\TicketFeedback;
use App\Models\Tickets;
use App\Support\TicketStatus;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Api\Mobile\MobileApiTestCase;

// The web employee pages (session-based, /employee/*) after their rules moved
// into RequestorTicketService / TicketChatService — same redirects, flash
// messages and side effects as before, so the shared service can't drift.
class EmployeeWebTicketTest extends MobileApiTestCase
{
    public function test_web_submit_auto_fills_requestor_context(): void
    {
        $helpdesk = $this->userWithRole('Helpdesk');
        $employee = $this->employee();

        $this->actingAs($employee)
            ->post(route('employee.tickets.store'), ['users_id' => $employee->id, 'subject' => 'No internet', 'concern' => 'LAN is down'])
            ->assertRedirect(route('employee.tickets.index'))
            ->assertSessionHas('new_ticket_number');

        $ticket = Tickets::first();
        $this->assertSame('Accounting', $ticket->department);
        $this->assertSame('Medium', $ticket->ticket_type);
        $this->assertSame(TicketStatus::QUEUE_HELPDESK, $ticket->pending_role);
        Mail::assertQueued(TicketSubmittedMail::class);
        Mail::assertQueued(TicketAssignedMail::class, fn ($m) => $m->hasTo($helpdesk->email));
    }

    public function test_helpdesk_filing_on_behalf_keeps_entered_values_and_skips_helpdesk_email(): void
    {
        $helpdesk = $this->userWithRole('Helpdesk');
        $employee = $this->employee();

        $this->actingAs($helpdesk)
            ->post(route('helpdesk.tickets.store'), [
                'users_id' => $employee->id,
                'ticket_type' => 'High',
                'request_category' => 'Hardware',
                'subject' => 'Laptop broken',
                'concern' => 'Screen cracked',
                'department' => 'Typed Dept',
                'method' => 'Phone',
            ])->assertRedirect();

        $ticket = Tickets::first();
        $this->assertSame($employee->id, $ticket->users_id);
        $this->assertSame('High', $ticket->ticket_type);
        $this->assertSame('Typed Dept', $ticket->department);
        $this->assertSame('Phone', $ticket->method);
        $this->assertDatabaseHas('ticket_status_histories', ['notes' => 'Ticket filed by Helpdesk on behalf of employee.']);
        Mail::assertNotQueued(TicketAssignedMail::class);
    }

    public function test_web_edit_cancel_confirm_and_feedback(): void
    {
        $employee = $this->employee();
        $open = $this->ticketFor($employee);
        $acknowledged = $this->ticketFor($employee, ['date_acknowledged' => '2026-10-15']);
        $waiting = $this->ticketFor($employee, ['status' => TicketStatus::REQUESTOR_CONFIRMATION]);

        $this->actingAs($employee)->patch(route('employee.tickets.update', $open), ['subject' => 'Edited', 'concern' => 'x'])
            ->assertSessionHas('success');
        $this->assertSame('Edited', $open->fresh()->subject);

        $this->patch(route('employee.tickets.update', $acknowledged), ['subject' => 'Edited', 'concern' => 'x'])
            ->assertSessionHas('error', 'This support request can no longer be edited — it has already been acknowledged by Helpdesk.');

        $this->patch(route('employee.tickets.cancel', $acknowledged))
            ->assertSessionHas('error', 'This ticket can no longer be cancelled — it has already been acknowledged by Helpdesk.');
        $this->patch(route('employee.tickets.cancel', $open))->assertSessionHas('success');
        $this->assertSame(TicketStatus::CANCELLED, $open->fresh()->status);

        $this->patch(route('employee.tickets.acknowledge', $waiting))->assertSessionHas('success');
        $this->assertSame(TicketStatus::CLOSED, $waiting->fresh()->status);

        $this->post(route('employee.tickets.feedback', $waiting), ['rating' => 4])->assertSessionHas('success');
        $this->assertSame(4, TicketFeedback::first()->rating);
        $this->post(route('employee.tickets.feedback', $waiting), ['rating' => 5])
            ->assertSessionHas('error', 'You have already submitted feedback for this ticket.');
    }

    public function test_web_owner_check_still_applies(): void
    {
        $ticket = $this->ticketFor($this->userWithRole('Employee'));

        $this->actingAs($this->employee())->patch(route('employee.tickets.cancel', $ticket))->assertForbidden();
    }

    public function test_web_chat_still_returns_same_shape(): void
    {
        Bus::fake([SendTicketChatDigest::class]);
        $employee = $this->employee();
        $helpdesk = $this->userWithRole('Helpdesk');
        $ticket = $this->ticketFor($employee);

        $this->actingAs($helpdesk)->postJson(route('messages.store', $ticket), ['message' => 'Hello'])
            ->assertOk()
            ->assertJsonStructure(['id', 'message', 'sender', 'role', 'initials', 'is_me', 'attachments', 'time', 'time_ago']);
        Bus::assertDispatched(SendTicketChatDigest::class);

        $this->actingAs($employee)->getJson(route('messages.total-unread'))->assertJson(['count' => 1]);
        $this->getJson(route('messages.index', $ticket))
            ->assertOk()
            ->assertJsonPath('messages.0.sender', 'Helpdesk User')
            ->assertJsonPath('messages.0.initials', 'HU');
        $this->getJson(route('messages.total-unread'))->assertJson(['count' => 0]);
    }
}
