<?php

namespace Tests\Feature\Api\Mobile\Staff;

use App\Http\Controllers\Dashboard\SupervisorDashboardController;
use App\Models\ReclassificationRequest;
use App\Models\TicketAttachment;
use App\Support\Mobile\WebBridge;
use App\Support\TicketStatus;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\Feature\Staff\StaffTestCase;

// Staff ticket work over the mobile API. Each action runs the role's own web
// controller through WebBridge, so these tests check the bridging (queues match
// the web dashboards, flashes become JSON) rather than re-testing web rules.
// Helpdesk / Specialist / IT Admin dashboards use Postgres-only SQL for their
// stats, so their queues are checked live instead; their actions are tested here.
class StaffApiTest extends StaffTestCase
{
    public function test_support_supervisor_queue_matches_the_web_dashboard(): void
    {
        $supervisor = $this->staff('Supervisor - Support Specialist');
        $waiting = $this->staffTicket(['status' => TicketStatus::CLASSIFIED, 'pending_role' => TicketStatus::QUEUE_SUPPORT_SUPERVISOR]);
        $this->staffTicket(['status' => TicketStatus::CLASSIFIED, 'pending_role' => TicketStatus::QUEUE_ADMIN_SUPERVISOR]);

        $response = $this->getJson('/api/mobile/staff/queue', $this->tokenFor($supervisor))
            ->assertOk()
            ->assertJsonPath('tab', 'awaiting-classification')
            ->assertJsonPath('data.0.id', $waiting->id)
            ->assertJsonPath('meta.total', 1);

        $tabs = collect($response->json('tabs'))->keyBy('key');
        $this->assertSame('For Classification', $tabs['awaiting-classification']['label']);
        $this->assertSame(1, $tabs['awaiting-classification']['count']);

        // Same ids as the web dashboard itself (as a web-session user).
        Auth::shouldUse('web');
        Auth::login($supervisor);
        $web = app(SupervisorDashboardController::class)->supportIndex(Request::create('/', 'GET', ['status' => 'awaiting-classification']))->getData();
        $this->assertSame($web['tickets']->pluck('id')->all(), array_column($response->json('data'), 'id'));
    }

    public function test_queue_rejects_unknown_tab_and_non_staff(): void
    {
        $manager = $this->staff('Manager');
        $this->getJson('/api/mobile/staff/queue?status=bogus', $this->tokenFor($manager))->assertStatus(422);

        $this->app['auth']->forgetGuards();
        $this->getJson('/api/mobile/staff/queue', $this->tokenFor($this->employee()))->assertForbidden();
    }

    public function test_manager_acknowledges_from_the_phone(): void
    {
        $manager = $this->staff('Manager');
        $ticket = $this->staffTicket(['status' => TicketStatus::ESCALATED, 'pending_role' => TicketStatus::QUEUE_MANAGER, 'escalation_level' => 2]);
        $headers = $this->tokenFor($manager);

        $this->getJson("/api/mobile/staff/tickets/{$ticket->id}", $headers)
            ->assertOk()
            ->assertJsonPath('data.actions.0.key', 'acknowledge');

        $this->postJson("/api/mobile/staff/tickets/{$ticket->id}/actions/acknowledge", [], $headers)
            ->assertOk()
            ->assertJsonPath('message', "Ticket #{$ticket->ticket_number} acknowledged.")
            ->assertJsonPath('ticket.status', TicketStatus::IN_PROGRESS_SERVICE_REQUEST)
            ->assertJsonPath('ticket.assignee.id', $manager->id);

        $actions = array_column($this->getJson("/api/mobile/staff/tickets/{$ticket->id}", $headers)->json('data.actions'), 'key');
        $this->assertEqualsCanonicalizing(['start_report', 'pause'], $actions);
    }

    public function test_web_guard_error_becomes_422_with_the_web_message(): void
    {
        $tech = $this->staff('IT Support Specialist');
        $busy = $this->staffTicket(['status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST, 'pending_role' => null, 'assigned_to' => $tech->id]);
        $next = $this->staffTicket(['status' => TicketStatus::ASSIGNED, 'pending_role' => null, 'assigned_to' => $tech->id, 'tech_acknowledged_at' => now()]);

        $this->postJson("/api/mobile/staff/tickets/{$next->id}/actions/start", [], $this->tokenFor($tech))
            ->assertStatus(422)
            ->assertJsonPath('message', "Resolve or escalate ticket #{$busy->ticket_number} before starting another ticket.");

        $this->assertSame(TicketStatus::ASSIGNED, $next->fresh()->status);
    }

    public function test_validation_error_and_unavailable_action(): void
    {
        $tech = $this->staff('IT Support Specialist');
        $ticket = $this->staffTicket(['status' => TicketStatus::ASSIGNED, 'pending_role' => null, 'assigned_to' => $tech->id]);
        $headers = $this->tokenFor($tech);

        $this->postJson("/api/mobile/staff/tickets/{$ticket->id}/actions/decline", [], $headers)
            ->assertStatus(422)
            ->assertJsonValidationErrors('reason');

        $this->postJson("/api/mobile/staff/tickets/{$ticket->id}/actions/resolve", [], $headers)
            ->assertStatus(422)
            ->assertJsonPath('message', "This action isn't available for this ticket right now. Pull to refresh.");

        $this->postJson("/api/mobile/staff/tickets/{$ticket->id}/actions/validate", [], $headers)->assertNotFound();
    }

    public function test_specialist_only_sees_own_tickets(): void
    {
        $tech = $this->staff('IT Support Specialist');
        $other = $this->staffTicket(['status' => TicketStatus::ASSIGNED, 'pending_role' => null, 'assigned_to' => $this->staff('IT Support Specialist')->id]);
        $mine = $this->staffTicket(['status' => TicketStatus::ASSIGNED, 'pending_role' => null, 'assigned_to' => $tech->id]);
        $headers = $this->tokenFor($tech);

        $this->getJson("/api/mobile/staff/tickets/{$other->id}", $headers)->assertForbidden();
        $this->getJson("/api/mobile/staff/tickets/{$mine->id}", $headers)
            ->assertOk()
            ->assertJsonPath('data.actions.0.key', 'acknowledge');
    }

    public function test_specialist_submits_report_with_photo(): void
    {
        $tech = $this->staff('IT Support Specialist');
        $ticket = $this->staffTicket(['status' => TicketStatus::IN_PROGRESS_SERVICE_REPORT, 'pending_role' => null, 'assigned_to' => $tech->id, 'started_at' => now()->subHour()]);

        $this->post("/api/mobile/staff/tickets/{$ticket->id}/actions/resolve", [
            'service_type' => 'Onsite',
            'resolution_notes' => 'Cleared the paper jam and replaced the roller.',
            'findings' => 'Worn pickup roller.',
            'attachments' => [UploadedFile::fake()->image('after.jpg')],
        ], $this->tokenFor($tech))
            ->assertOk()
            ->assertJsonPath('ticket.status', TicketStatus::REPORT_FOR_REVIEW)
            ->assertJsonPath('ticket.report.findings', 'Worn pickup roller.');

        $this->assertSame(1, TicketAttachment::where('ticket_id', $ticket->id)->count());
    }

    public function test_supervisor_validates_and_sees_pending_reclassification(): void
    {
        $supervisor = $this->staff('Supervisor - Support Specialist');
        $tech = $this->staff('IT Support Specialist');
        $review = $this->staffTicket(['status' => TicketStatus::REPORT_FOR_REVIEW, 'pending_role' => null, 'assigned_to' => $tech->id, 'resolution_notes' => 'Fixed']);
        $reclass = $this->staffTicket(['status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST, 'pending_role' => null, 'assigned_to' => $tech->id]);
        ReclassificationRequest::forceCreate(['id' => (string) Str::uuid(), 'ticket_id' => $reclass->id, 'requested_by' => $tech->id, 'status' => 'pending', 'reason' => 'It is a network issue', 'proposed_subcategory_name' => 'LAN', 'proposed_priority' => 'High', 'requested_at' => now()]);
        $headers = $this->tokenFor($supervisor);

        $this->getJson("/api/mobile/staff/tickets/{$reclass->id}", $headers)
            ->assertOk()
            ->assertJsonPath('data.pending_reclassification.reason', 'It is a network issue')
            ->assertJsonPath('data.pending_reclassification.requested_by', $tech->name);

        $this->postJson("/api/mobile/staff/tickets/{$review->id}/actions/validate", ['validation_notes' => 'Checked with requester.'], $headers)
            ->assertOk()
            ->assertJsonPath('ticket.status', TicketStatus::REQUESTOR_CONFIRMATION);
    }

    public function test_lookups_give_assignees_and_sla_rules(): void
    {
        $supervisor = $this->staff('Supervisor - Support Specialist');
        $tech = $this->staff('IT Support Specialist');
        $rule = $this->slaRule();

        $this->getJson('/api/mobile/staff/lookups', $this->tokenFor($supervisor))
            ->assertOk()
            ->assertJsonPath('sla_rules.0.rules.0.id', $rule->id)
            ->assertJsonPath('sla_rules.0.rules.0.name', 'Printer')
            ->assertJsonPath('assignees.0.id', $tech->id);
    }

    public function test_schedule_conflict_flash_becomes_a_decision_request(): void
    {
        Route::post('/__bridge-test', fn () => back()->withInput()->with('scheduleConflict', [
            'ticket_number' => 'LGICT-26-0001',
            'technician_name' => 'Juan',
            'proposed_end' => '6:30 PM, Oct 16',
            'day_end' => '5:00 PM',
            'overtime_minutes' => 90,
        ]));
        $request = Request::create('/__bridge-test', 'POST');

        $response = WebBridge::action($request, BridgeTestController::class, 'conflict', []);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('schedule_decision_required', $response->getData(true)['code']);
        $this->assertSame(90, $response->getData(true)['conflict']['overtime_minutes']);
    }
}

class BridgeTestController
{
    public function conflict()
    {
        return back()->with('scheduleConflict', [
            'ticket_number' => 'LGICT-26-0001',
            'technician_name' => 'Juan',
            'proposed_end' => '6:30 PM, Oct 16',
            'day_end' => '5:00 PM',
            'overtime_minutes' => 90,
        ]);
    }
}
