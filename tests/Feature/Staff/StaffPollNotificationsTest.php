<?php

namespace Tests\Feature\Staff;

use App\Models\ReclassificationRequest;
use App\Models\TicketMessage;
use App\Support\TicketStatus;
use Illuminate\Support\Str;

// Web bell (NotificationController::poll) for staff roles.
class StaffPollNotificationsTest extends StaffTestCase
{
    private function poll($user): array
    {
        return $this->actingAs($user)
            ->getJson(route('notifications.poll', ['since' => now()->subMinute()->toISOString()]))
            ->assertOk()
            ->json('notifications');
    }

    public function test_specialist_and_it_admin_get_new_assignment_alerts(): void
    {
        foreach (['IT Support Specialist', 'IT Admin'] as $role) {
            $tech = $this->staff($role);
            $ticket = $this->staffTicket(['status' => TicketStatus::ASSIGNED, 'pending_role' => null, 'assigned_to' => $tech->id]);

            $types = array_column($this->poll($tech), 'type');
            $this->assertContains('new_assignment', $types, "{$role} should be told about {$ticket->ticket_number}");
        }
    }

    public function test_specialist_gets_message_alerts_on_assigned_tickets(): void
    {
        $tech = $this->staff('IT Support Specialist');
        $ticket = $this->staffTicket(['status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST, 'pending_role' => null, 'assigned_to' => $tech->id]);
        TicketMessage::create(['ticket_id' => $ticket->id, 'sender_id' => $ticket->users_id, 'message' => 'Any update?', 'is_read' => false, 'created_at' => now()]);

        $this->assertContains('new_message', array_column($this->poll($tech), 'type'));
    }

    public function test_support_supervisor_only_sees_support_track_reclassifications(): void
    {
        $supervisor = $this->staff('Supervisor - Support Specialist');
        $specialistTicket = $this->staffTicket(['status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST, 'pending_role' => null, 'assigned_to' => $this->staff('IT Support Specialist')->id]);
        $adminTicket = $this->staffTicket(['status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST, 'pending_role' => null, 'assigned_to' => $this->staff('IT Admin')->id]);
        foreach ([$specialistTicket, $adminTicket] as $t) {
            ReclassificationRequest::forceCreate(['id' => (string) Str::uuid(), 'ticket_id' => $t->id, 'status' => 'pending', 'reason' => 'Wrong category', 'requested_at' => now()]);
        }

        $reclass = array_values(array_filter($this->poll($supervisor), fn ($n) => $n['type'] === 'pending_reclassification'));

        $this->assertCount(1, $reclass);
        $this->assertStringContainsString($specialistTicket->ticket_number, $reclass[0]['body']);
    }
}
