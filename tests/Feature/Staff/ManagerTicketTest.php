<?php

namespace Tests\Feature\Staff;

use App\Http\Controllers\Manager\TicketController;
use App\Support\TicketStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Manager queue: tickets escalated to the Manager (status Escalated, pending
// role Manager — from escalateToAdmin L4 and escalateToManager) must show up
// in "Awaiting You" and be acknowledgeable, same as For Acknowledgment ones.
class ManagerTicketTest extends StaffTestCase
{
    private function managerQueue(string $status = 'awaiting-manager'): array
    {
        $view = app(TicketController::class)->index(Request::create('/executive/tickets', 'GET', ['status' => $status]));

        return $view->getData();
    }

    public function test_escalated_ticket_shows_in_awaiting_manager_and_counts(): void
    {
        $manager = $this->staff('Manager');
        $escalated = $this->staffTicket(['status' => TicketStatus::ESCALATED, 'pending_role' => TicketStatus::QUEUE_MANAGER, 'escalation_level' => 2]);
        $forAck = $this->staffTicket(['status' => TicketStatus::FOR_ACKNOWLEDGMENT, 'pending_role' => TicketStatus::QUEUE_MANAGER]);
        $this->staffTicket(['status' => TicketStatus::ESCALATED, 'pending_role' => TicketStatus::QUEUE_ADMIN_SUPERVISOR]);
        Auth::login($manager);

        $data = $this->managerQueue();

        $this->assertEqualsCanonicalizing([$escalated->id, $forAck->id], $data['tickets']->pluck('id')->all());
        $this->assertSame(2, $data['counts']['awaiting_manager']);
        $this->assertSame(2, $data['counts']['active']);
    }

    public function test_manager_can_acknowledge_an_escalated_ticket(): void
    {
        $manager = $this->staff('Manager');
        $ticket = $this->staffTicket(['status' => TicketStatus::ESCALATED, 'pending_role' => TicketStatus::QUEUE_MANAGER, 'escalation_level' => 2]);

        $this->actingAs($manager)
            ->post(route('executive.tickets.acknowledge', $ticket))
            ->assertSessionHas('success');

        $ticket->refresh();
        $this->assertSame(TicketStatus::IN_PROGRESS_SERVICE_REQUEST, $ticket->status);
        $this->assertSame($manager->id, $ticket->assigned_to);
        $this->assertNull($ticket->pending_role);
        $this->assertDatabaseHas('ticket_status_histories', ['ticket_id' => $ticket->id, 'old_status' => TicketStatus::ESCALATED, 'new_status' => TicketStatus::CLASSIFIED]);
    }

    public function test_manager_resolve_form_sends_service_type(): void
    {
        // The resolve modal must include the service type the server requires
        // (TicketResolutionRules::BASE) — without it every Manager resolve failed.
        $html = file_get_contents(resource_path('views/dashboard/manager/dashboard.blade.php'));
        $form = substr($html, strpos($html, 'id="resolveForm"'), 3000);

        $this->assertStringContainsString('name="service_type"', $form);
    }

    public function test_manager_resolves_with_service_type(): void
    {
        $manager = $this->staff('Manager');
        $ticket = $this->staffTicket(['status' => TicketStatus::IN_PROGRESS_SERVICE_REPORT, 'assigned_to' => $manager->id, 'pending_role' => null]);

        $this->actingAs($manager)
            ->post(route('executive.tickets.resolve', $ticket), ['resolution_notes' => 'Replaced the cable.', 'service_type' => 'Onsite'])
            ->assertSessionHas('success');

        $this->assertSame(TicketStatus::REQUESTOR_CONFIRMATION, $ticket->fresh()->status);
    }
}
