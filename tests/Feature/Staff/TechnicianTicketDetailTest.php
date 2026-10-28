<?php

namespace Tests\Feature\Staff;

use App\Support\TicketStatus;

// The IT Support Specialist's "View Support Request Details" opens the full
// details page (same layout as Helpdesk's), not an inline modal.
class TechnicianTicketDetailTest extends StaffTestCase
{
    public function test_assigned_technician_sees_the_details_page(): void
    {
        $tech = $this->staff('IT Support Specialist');
        $ticket = $this->staffTicket([
            'status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST,
            'pending_role' => null,
            'assigned_to' => $tech->id,
            'started_at' => now(),
        ]);

        $this->actingAs($tech)
            ->get(route('technician.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('SUPPORT REQUEST', false)
            ->assertSee($ticket->ticket_number)
            ->assertSee('Back to My Work Queue');
    }

    public function test_other_technician_is_forbidden(): void
    {
        $ticket = $this->staffTicket([
            'status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST,
            'pending_role' => null,
            'assigned_to' => $this->staff('IT Support Specialist')->id,
        ]);
        $other = $this->userWithRole('IT Support Specialist', ['name' => 'Other Tech']);

        $this->actingAs($other)
            ->get(route('technician.tickets.show', $ticket))
            ->assertForbidden();
    }
}
