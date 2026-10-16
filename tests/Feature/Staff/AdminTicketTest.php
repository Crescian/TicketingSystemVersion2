<?php

namespace Tests\Feature\Staff;

use App\Models\Tickets;
use App\Support\TicketStatus;

class AdminTicketTest extends StaffTestCase
{
    // The IT Admin's own "Escalated" tab lists tickets with an escalations row
    // they created — escalate() has to write one, like the other tracks do.
    public function test_escalated_ticket_appears_in_the_admins_escalated_tab(): void
    {
        $admin = $this->staff('IT Admin');
        $ticket = $this->staffTicket(['status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST, 'pending_role' => null, 'assigned_to' => $admin->id]);

        $this->actingAs($admin)
            ->post(route('admin.tickets.escalate', $ticket), ['reason' => 'Needs vendor access'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('escalations', ['ticket_id' => $ticket->id, 'escalated_by' => $admin->id, 'reason' => 'Needs vendor access', 'escalation_level' => 1]);

        // The tab's own query (Admin\TicketController::index, status=escalated).
        // index() itself isn't run here: its stats use Postgres-only SQL.
        $this->assertSame([$ticket->id], Tickets::whereHas('escalations', fn ($q) => $q->where('escalated_by', $admin->id))->pluck('id')->all());
    }

    // Resolving clears a previous "request revision" note, as on the
    // Specialist and Helpdesk tracks — otherwise it shows on the next review.
    public function test_resolve_clears_revision_fields(): void
    {
        $admin = $this->staff('IT Admin');
        $ticket = $this->staffTicket([
            'status' => TicketStatus::IN_PROGRESS_SERVICE_REPORT,
            'pending_role' => null,
            'assigned_to' => $admin->id,
            'revision_requested_at' => now()->subHour(),
            'revision_notes' => 'Add the serial number',
        ]);

        $this->actingAs($admin)->post(route('admin.tickets.resolve', $ticket), [
            'resolution_notes' => 'Reconfigured the switch port.',
            'service_type' => 'Onsite',
            'root_cause' => 'Network infrastructure issue',
        ])->assertSessionHas('success');

        $ticket->refresh();
        $this->assertSame(TicketStatus::REPORT_FOR_REVIEW, $ticket->status);
        $this->assertNull($ticket->revision_requested_at);
        $this->assertNull($ticket->revision_notes);
    }
}
