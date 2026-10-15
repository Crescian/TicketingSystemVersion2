<?php

namespace Tests\Feature;

use App\Mail\TicketAssignedMail;
use App\Models\Role;
use App\Models\SlaRule;
use App\Models\TicketStatusHistories;
use App\Models\Tickets;
use App\Models\User;
use App\Support\TicketStatus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

// "Return to Helpdesk": either Supervisor can hand an unassigned ticket in their
// own queue back to Helpdesk's For Classification list. The project's migrations
// are Postgres-specific, so only the tables these routes touch are built here
// (same approach as DeveloperConcernTest).
class SupervisorReturnToHelpdeskTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('roles', function ($table) {
            $table->uuid('id')->primary();
            $table->string('role_name');
            $table->string('description')->nullable();
            $table->unsignedTinyInteger('level')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function ($table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->uuid('role_id')->nullable();
            $table->boolean('active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('tickets', function ($table) {
            $table->uuid('id')->primary();
            $table->string('ticket_number');
            $table->uuid('users_id')->nullable();
            $table->uuid('assigned_to')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->string('ticket_type')->nullable();
            $table->string('subject')->nullable();
            $table->string('status');
            $table->string('pending_role')->nullable();
            $table->unsignedInteger('escalation_level')->default(0);
            $table->date('date_acknowledged')->nullable();
            $table->time('time_acknowledged')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->uuid('sla_category_id')->nullable();
            $table->string('subcategory_name')->nullable();
            $table->uuid('workload_class_id')->nullable();
            $table->float('response_time_minutes')->nullable();
            $table->float('resolution_time_minutes')->nullable();
            $table->timestamp('scheduled_start')->nullable();
            $table->timestamp('scheduled_end')->nullable();
            $table->boolean('is_overtime')->default(false);
            $table->timestamp('queued_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ticket_status_histories', function ($table) {
            $table->uuid('id')->primary();
            $table->uuid('ticket_id');
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->uuid('changed_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('changed_at')->nullable();
        });

        Schema::create('sla_rules', function ($table) {
            $table->uuid('id')->primary();
            $table->uuid('sla_category_id')->nullable();
            $table->string('subcategory_name');
            $table->string('priority');
            $table->uuid('workload_class_id')->nullable();
            $table->float('response_time_minutes');
            $table->float('resolution_time_minutes');
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->boolean('helpdesk_resolvable')->default(false);
            $table->boolean('admin_only')->default(false);
            $table->timestamps();
        });

        Mail::fake();
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(['role_name' => $roleName], ['description' => $roleName]);

        return User::forceCreate([
            'id' => (string) Str::uuid(),
            'name' => "{$roleName} User",
            'email' => Str::slug($roleName) . '-' . Str::random(4) . '@example.com',
            'password' => 'x',
            'role_id' => $role->id,
            'active' => true,
        ]);
    }

    private function ticketIn(string $queue, array $attrs = []): Tickets
    {
        return Tickets::forceCreate($attrs + [
            'id' => (string) Str::uuid(),
            'ticket_number' => 'TKT-' . Str::random(5),
            'subject' => 'Server room access',
            'status' => TicketStatus::CLASSIFIED,
            'pending_role' => $queue,
            'subcategory_name' => 'Server Access',
            'ticket_type' => 'High',
            'response_time_minutes' => 30,
            'resolution_time_minutes' => 240,
            'date_acknowledged' => '2026-10-15',
            'time_acknowledged' => '09:00:00',
        ]);
    }

    public function test_admin_supervisor_returns_classified_ticket_to_helpdesk(): void
    {
        $helpdesk = $this->userWithRole('Helpdesk');
        $supervisor = $this->userWithRole('Supervisor - IT Admin');
        $ticket = $this->ticketIn(TicketStatus::QUEUE_ADMIN_SUPERVISOR);

        $this->actingAs($supervisor)
            ->post(route('supervisor.tickets.return-to-helpdesk', $ticket), ['reason' => 'Not an L3 issue'])
            ->assertSessionHas('success');

        $ticket->refresh();
        $this->assertSame(TicketStatus::FOR_CLASSIFICATION, $ticket->status);
        $this->assertSame(TicketStatus::QUEUE_HELPDESK, $ticket->pending_role);

        $history = TicketStatusHistories::where('ticket_id', $ticket->id)->sole();
        $this->assertSame(TicketStatus::CLASSIFIED, $history->old_status);
        $this->assertStringContainsString('Not an L3 issue', $history->notes);

        Mail::assertQueued(TicketAssignedMail::class, fn ($mail) => $mail->hasTo($helpdesk->email));
    }

    public function test_support_supervisor_returns_classified_ticket_to_helpdesk(): void
    {
        $supervisor = $this->userWithRole('Supervisor - Support Specialist');
        $ticket = $this->ticketIn(TicketStatus::QUEUE_SUPPORT_SUPERVISOR);

        $this->actingAs($supervisor)
            ->post(route('supervisor.support.tickets.return-to-helpdesk', $ticket), ['reason' => 'Wrong subcategory'])
            ->assertSessionHas('success');

        $ticket->refresh();
        $this->assertSame(TicketStatus::FOR_CLASSIFICATION, $ticket->status);
        $this->assertSame(TicketStatus::QUEUE_HELPDESK, $ticket->pending_role);
    }

    public function test_unacknowledged_admin_queue_ticket_gets_acknowledgement_stamp(): void
    {
        $supervisor = $this->userWithRole('Supervisor - IT Admin');
        $ticket = $this->ticketIn(TicketStatus::QUEUE_ADMIN_SUPERVISOR, [
            'status' => TicketStatus::FOR_ACKNOWLEDGMENT,
            'date_acknowledged' => null,
            'time_acknowledged' => null,
        ]);

        $this->actingAs($supervisor)
            ->post(route('supervisor.tickets.return-to-helpdesk', $ticket), ['reason' => 'Belongs to Helpdesk'])
            ->assertSessionHas('success');

        $this->assertNotNull($ticket->refresh()->date_acknowledged);
    }

    public function test_assigned_ticket_cannot_be_returned(): void
    {
        $supervisor = $this->userWithRole('Supervisor - IT Admin');
        $admin = $this->userWithRole('IT Admin');
        $ticket = $this->ticketIn(TicketStatus::QUEUE_ADMIN_SUPERVISOR, ['assigned_to' => $admin->id]);

        $this->actingAs($supervisor)
            ->post(route('supervisor.tickets.return-to-helpdesk', $ticket), ['reason' => 'x'])
            ->assertSessionHas('error');

        $this->assertSame(TicketStatus::CLASSIFIED, $ticket->refresh()->status);
    }

    public function test_ticket_in_other_supervisors_queue_cannot_be_returned(): void
    {
        $supervisor = $this->userWithRole('Supervisor - IT Admin');
        $ticket = $this->ticketIn(TicketStatus::QUEUE_SUPPORT_SUPERVISOR);

        $this->actingAs($supervisor)
            ->post(route('supervisor.tickets.return-to-helpdesk', $ticket), ['reason' => 'x'])
            ->assertSessionHas('error');

        $this->assertSame(TicketStatus::QUEUE_SUPPORT_SUPERVISOR, $ticket->refresh()->pending_role);
    }

    public function test_reason_is_required(): void
    {
        $supervisor = $this->userWithRole('Supervisor - IT Admin');
        $ticket = $this->ticketIn(TicketStatus::QUEUE_ADMIN_SUPERVISOR);

        $this->actingAs($supervisor)
            ->post(route('supervisor.tickets.return-to-helpdesk', $ticket), [])
            ->assertSessionHasErrors('reason');

        $this->assertSame(TicketStatus::CLASSIFIED, $ticket->refresh()->status);
    }

    public function test_helpdesk_can_reclassify_a_returned_ticket(): void
    {
        $helpdesk = $this->userWithRole('Helpdesk');
        $this->userWithRole('Supervisor - Support Specialist');
        $supervisor = $this->userWithRole('Supervisor - IT Admin');
        $ticket = $this->ticketIn(TicketStatus::QUEUE_ADMIN_SUPERVISOR);

        $this->actingAs($supervisor)
            ->post(route('supervisor.tickets.return-to-helpdesk', $ticket), ['reason' => 'Not an L3 issue']);

        $rule = SlaRule::forceCreate([
            'id' => (string) Str::uuid(),
            'subcategory_name' => 'Printer Issue',
            'priority' => 'Medium',
            'response_time_minutes' => 30,
            'resolution_time_minutes' => 120,
            'admin_only' => false,
        ]);

        $this->actingAs($helpdesk)
            ->post(route('helpdesk.tickets.classify', $ticket), ['sla_rule_id' => $rule->id])
            ->assertSessionHas('success');

        $ticket->refresh();
        $this->assertSame(TicketStatus::CLASSIFIED, $ticket->status);
        $this->assertSame(TicketStatus::QUEUE_SUPPORT_SUPERVISOR, $ticket->pending_role);
        $this->assertSame('Printer Issue', $ticket->subcategory_name);
    }
}
