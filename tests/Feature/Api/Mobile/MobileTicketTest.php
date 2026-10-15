<?php

namespace Tests\Feature\Api\Mobile;

use App\Mail\TicketAssignedMail;
use App\Mail\TicketClosedByRequestorMail;
use App\Mail\TicketSubmittedMail;
use App\Models\TicketAttachment;
use App\Models\Tickets;
use App\Support\TicketStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class MobileTicketTest extends MobileApiTestCase
{
    public function test_employee_submits_a_ticket_with_attachment(): void
    {
        $helpdesk = $this->userWithRole('Helpdesk');
        $employee = $this->employee();

        $response = $this->post('/api/mobile/tickets', [
            'subject' => 'Cannot print',
            'concern' => 'Printer shows offline',
            'location' => '3F Accounting',
            'attachments' => [UploadedFile::fake()->image('printer.jpg')],
        ], $this->tokenFor($employee))
            ->assertCreated()
            ->assertJsonPath('data.status', TicketStatus::FOR_ACKNOWLEDGMENT)
            ->assertJsonPath('data.step', 1)
            ->assertJsonPath('data.step_label', 'Submitted')
            ->assertJsonPath('data.can_cancel', true)
            ->assertJsonPath('data.attachments.0.name', 'printer.jpg');

        $ticket = Tickets::find($response->json('data.id'));
        $this->assertSame('Accounting', $ticket->department);
        $this->assertSame('LLRI', $ticket->company);
        $this->assertSame('Medium', $ticket->ticket_type);
        $this->assertSame('System', $ticket->method);
        $this->assertMatchesRegularExpression('/^LGICT-\d{2}-\d{4}$/', $ticket->ticket_number);
        $this->assertDatabaseHas('ticket_status_histories', ['ticket_id' => $ticket->id, 'notes' => 'Ticket submitted by employee.']);
        Storage::disk('local')->assertExists(TicketAttachment::first()->stored_path);

        Mail::assertQueued(TicketSubmittedMail::class, fn ($m) => $m->hasTo($employee->email));
        Mail::assertQueued(TicketAssignedMail::class, fn ($m) => $m->hasTo($helpdesk->email));
    }

    public function test_submit_validates_input(): void
    {
        $this->postJson('/api/mobile/tickets', ['concern' => 'x'], $this->tokenFor($this->employee()))
            ->assertStatus(422)
            ->assertJsonValidationErrors('subject');
    }

    public function test_list_shows_only_own_tickets_with_phase_filter_and_counts(): void
    {
        $employee = $this->employee();
        $other = $this->userWithRole('Employee');
        $this->ticketFor($employee);
        $this->ticketFor($employee, ['status' => TicketStatus::CLOSED]);
        $this->ticketFor($other);
        $headers = $this->tokenFor($employee);

        $this->getJson('/api/mobile/tickets', $headers)->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('meta.total', 2);
        $this->getJson('/api/mobile/tickets?status=closed', $headers)->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.step_label', 'Closed')
            ->assertJsonPath('data.0.can_rate', true);
        $this->getJson('/api/mobile/tickets?status=bogus', $headers)->assertStatus(422);

        $this->getJson('/api/mobile/tickets/counts', $headers)->assertOk()
            ->assertJson(['all' => 2, 'submitted' => 1, 'closed' => 1, 'in_progress' => 0]);
    }

    public function test_cannot_see_or_act_on_someone_elses_ticket(): void
    {
        $employee = $this->employee();
        $ticket = $this->ticketFor($this->userWithRole('Employee'));
        $headers = $this->tokenFor($employee);

        $this->getJson("/api/mobile/tickets/{$ticket->id}", $headers)->assertForbidden();
        $this->patchJson("/api/mobile/tickets/{$ticket->id}/cancel", [], $headers)->assertForbidden();
        $this->getJson("/api/mobile/tickets/{$ticket->id}/messages", $headers)->assertForbidden();
        $this->assertSame(TicketStatus::FOR_ACKNOWLEDGMENT, $ticket->fresh()->status);
    }

    public function test_edit_and_cancel_only_before_helpdesk_acknowledges(): void
    {
        $employee = $this->employee();
        $headers = $this->tokenFor($employee);
        $open = $this->ticketFor($employee);
        $acknowledged = $this->ticketFor($employee, ['date_acknowledged' => '2026-10-15', 'status' => TicketStatus::FOR_CLASSIFICATION]);

        $this->post("/api/mobile/tickets/{$open->id}", ['subject' => 'Updated', 'concern' => 'More detail'], $headers)
            ->assertOk()->assertJsonPath('data.subject', 'Updated');

        $this->post("/api/mobile/tickets/{$acknowledged->id}", ['subject' => 'Updated', 'concern' => 'x'], $headers)
            ->assertStatus(422)
            ->assertJsonPath('message', 'This support request can no longer be edited — it has already been acknowledged by Helpdesk.');

        $this->patchJson("/api/mobile/tickets/{$acknowledged->id}/cancel", [], $headers)->assertStatus(422);

        $this->patchJson("/api/mobile/tickets/{$open->id}/cancel", [], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', TicketStatus::CANCELLED)
            ->assertJsonPath('data.step', null);
    }

    public function test_confirm_resolution_then_rate_once(): void
    {
        $helpdesk = $this->userWithRole('Helpdesk');
        $employee = $this->employee();
        $headers = $this->tokenFor($employee);
        $waiting = $this->ticketFor($employee, ['status' => TicketStatus::REQUESTOR_CONFIRMATION]);
        $inProgress = $this->ticketFor($employee, ['status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST]);

        $this->patchJson("/api/mobile/tickets/{$inProgress->id}/confirm", [], $headers)->assertStatus(422);
        $this->postJson("/api/mobile/tickets/{$waiting->id}/feedback", ['rating' => 5], $headers)
            ->assertStatus(422)->assertJsonPath('message', 'You can only rate closed tickets.');

        $this->patchJson("/api/mobile/tickets/{$waiting->id}/confirm", [], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', TicketStatus::CLOSED)
            ->assertJsonPath('data.can_rate', true);
        Mail::assertQueued(TicketClosedByRequestorMail::class, fn ($m) => $m->hasTo($helpdesk->email));

        $this->postJson("/api/mobile/tickets/{$waiting->id}/feedback", ['rating' => 6], $headers)->assertStatus(422);
        $this->postJson("/api/mobile/tickets/{$waiting->id}/feedback", ['rating' => 5, 'comments' => 'Fast!'], $headers)
            ->assertOk()
            ->assertJsonPath('data.feedback.rating', 5)
            ->assertJsonPath('data.can_rate', false);
        $this->postJson("/api/mobile/tickets/{$waiting->id}/feedback", ['rating' => 4], $headers)
            ->assertStatus(422)->assertJsonPath('message', 'You have already submitted feedback for this ticket.');
    }

    public function test_attachment_download_is_owner_only(): void
    {
        $employee = $this->employee();
        $ticket = $this->ticketFor($employee);
        Storage::disk('local')->put('ticket-attachments/x/file.pdf', 'pdf-bytes');
        $attachment = TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'original_name' => 'file.pdf',
            'stored_path' => 'ticket-attachments/x/file.pdf',
            'mime_type' => 'application/pdf',
            'size' => 9,
        ]);

        $this->get("/api/mobile/attachments/{$attachment->id}", $this->tokenFor($employee))->assertOk();

        $this->app['auth']->forgetGuards();
        $this->get("/api/mobile/attachments/{$attachment->id}", $this->tokenFor($this->userWithRole('Employee')))->assertForbidden();
    }

    public function test_service_report_hidden_until_awaiting_confirmation(): void
    {
        $employee = $this->employee();
        $ticket = $this->ticketFor($employee, ['status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST]);

        $this->get("/api/mobile/tickets/{$ticket->id}/service-report", $this->tokenFor($employee))->assertNotFound();
        $this->getJson("/api/mobile/tickets/{$ticket->id}", $this->tokenFor($employee))
            ->assertJsonPath('data.service_report_url', null)
            ->assertJsonPath('data.step_label', 'In Progress');
    }
}
