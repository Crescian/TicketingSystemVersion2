<?php

namespace Tests\Feature\Staff;

use App\Models\SlaCategory;
use App\Models\SlaRule;
use App\Models\Tickets;
use App\Models\User;
use App\Support\TicketStatus;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Feature\Api\Mobile\MobileApiTestCase;

// Shared setup for staff-role tests (web actions and the staff mobile API).
// Rebuilds `tickets` with every production column (all nullable) and adds the
// tables staff actions touch — escalations, reclassification requests, SLA
// rules, workload classes, holidays, leaves, sessions. Column lists mirror
// the live Postgres schema (information_schema) as of 2026-10-16.
abstract class StaffTestCase extends MobileApiTestCase
{
    private const TICKET_COLUMNS = [
        'uuid' => ['users_id', 'assigned_to', 'sla_category_id', 'workload_class_id', 'resolved_by'],
        'string' => [
            'ticket_number', 'ticket_type', 'request_category', 'asset', 'subject', 'status', 'location', 'position',
            'business_unit', 'company', 'department', 'level_request', 'employee_name', 'method', 'subcategory_name',
            'service_type', 'pending_role', 'date_received', 'time_received', 'date_acknowledged', 'time_acknowledged',
        ],
        'text' => [
            'request_details', 'concern', 'cannot_resolve_findings', 'cannot_resolve_recommendation', 'resolution_notes',
            'findings', 'recommendation', 'validation_notes', 'hold_reason', 'revision_notes',
        ],
        'integer' => ['escalation_level', 'response_time_minutes', 'resolution_time_minutes', 'total_hold_minutes'],
        'boolean' => ['cannot_resolve', 'is_overtime'],
        'timestamp' => [
            'started_at', 'resolved_at', 'sla_due_at', 'sla_risk_notified_at', 'sla_breached_notified_at', 'scheduled_start',
            'scheduled_end', 'queued_at', 'tech_acknowledged_at', 'validated_at', 'closed_at', 'report_started_at',
            'classification_reminded_at', 'report_review_reminded_at', 'requestor_confirmation_reminded_at',
            'helpdesk_ack_reminded_at', 'helpdesk_classification_reminded_at', 'tech_ack_reminded_at', 'paused_at',
            'revision_requested_at',
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Schema::drop('tickets');
        Schema::create('tickets', function ($table) {
            $table->uuid('id')->primary();
            foreach (self::TICKET_COLUMNS as $type => $columns) {
                foreach ($columns as $column) {
                    $table->{$type}($column)->nullable();
                }
            }
            $table->timestamps();
        });

        Schema::create('escalations', function ($table) {
            $table->uuid('id')->primary();
            $table->uuid('ticket_id');
            $table->integer('escalation_level')->nullable();
            $table->uuid('escalated_by')->nullable();
            $table->uuid('previous_tech_id')->nullable();
            $table->uuid('reassigned_to')->nullable();
            $table->string('reason')->nullable();
            $table->string('resolution_notes')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
        });

        Schema::create('reclassification_requests', function ($table) {
            $table->uuid('id')->primary();
            $table->uuid('ticket_id');
            foreach (['requested_by', 'reviewed_by', 'current_sla_category_id', 'proposed_sla_category_id'] as $c) {
                $table->uuid($c)->nullable();
            }
            foreach (['current_subcategory_name', 'current_priority', 'proposed_subcategory_name', 'proposed_priority', 'reason', 'status', 'review_notes'] as $c) {
                $table->string($c)->nullable();
            }
            $table->integer('proposed_response_time_minutes')->nullable();
            $table->integer('proposed_resolution_time_minutes')->nullable();
            foreach (['requested_at', 'reviewed_at', 'reminded_at'] as $c) {
                $table->timestamp($c)->nullable();
            }
        });

        Schema::create('sla_rules', function ($table) {
            $table->uuid('id')->primary();
            $table->uuid('sla_category_id')->nullable();
            $table->string('subcategory_name');
            $table->string('priority');
            $table->decimal('response_time_minutes')->nullable();
            $table->decimal('resolution_time_minutes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->boolean('helpdesk_resolvable')->default(false);
            $table->boolean('admin_only')->default(false);
            $table->uuid('workload_class_id')->nullable();
            $table->timestamps();
        });

        Schema::create('workload_classes', function ($table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('typical_application')->nullable();
            $table->integer('response_minutes')->nullable();
            $table->integer('resolution_minutes')->nullable();
            $table->string('response_label')->nullable();
            $table->string('resolution_label')->nullable();
            $table->boolean('requires_manual_resolution')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('holidays', function ($table) {
            $table->uuid('id')->primary();
            $table->date('date');
            $table->string('name')->nullable();
            $table->string('type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('leaves', function ($table) {
            $table->uuid('id')->primary();
            $table->uuid('technician_id');
            $table->date('date');
            $table->string('reason')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('sessions', function ($table) {
            $table->string('id')->primary();
            $table->uuid('user_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->text('payload')->nullable();
            $table->integer('last_activity');
        });
    }

    protected function staffTicket(array $attrs = []): Tickets
    {
        return Tickets::forceCreate($attrs + [
            'id' => (string) Str::uuid(),
            'ticket_number' => 'LGICT-26-' . random_int(1000, 9999),
            'users_id' => $this->userWithRole('Employee')->id,
            'subject' => 'Printer not working',
            'concern' => 'Paper jam on 3F printer',
            'ticket_type' => 'Medium',
            'status' => TicketStatus::FOR_ACKNOWLEDGMENT,
            'pending_role' => TicketStatus::QUEUE_HELPDESK,
            'escalation_level' => 0,
        ]);
    }

    protected function slaRule(array $attrs = []): SlaRule
    {
        $category = SlaCategory::forceCreate(['id' => (string) Str::uuid(), 'name' => 'Hardware', 'is_active' => true, 'sort_order' => 1]);

        return SlaRule::forceCreate($attrs + [
            'id' => (string) Str::uuid(),
            'sla_category_id' => $category->id,
            'subcategory_name' => 'Printer',
            'priority' => 'Medium',
            'response_time_minutes' => 30,
            'resolution_time_minutes' => 240,
            'is_active' => true,
        ]);
    }

    protected function staff(string $role): User
    {
        return $this->userWithRole($role, ['name' => "{$role} Tester"]);
    }
}
