<?php

namespace Tests\Feature\Api\Mobile\Staff;

use App\Mail\DeveloperConcernAcknowledgedMail;
use App\Mail\DeveloperConcernSubmittedMail;
use App\Models\DeveloperConcern;
use App\Support\TicketStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Staff\StaffTestCase;

// Calendar and Report-to-Developer over the mobile API (both via WebBridge).
class StaffExtrasTest extends StaffTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('developer_concerns', function ($table) {
            $table->uuid('id')->primary();
            $table->string('reference')->unique();
            $table->uuid('submitted_by');
            $table->string('subject');
            $table->text('details');
            $table->string('category');
            $table->string('urgency');
            $table->string('status');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('closing_note')->nullable();
            $table->timestamps();
        });
    }

    public function test_developer_concern_round_trip(): void
    {
        $developer = $this->staff('Developer');
        $helpdesk = $this->staff('Helpdesk');

        $this->getJson('/api/mobile/staff/developer-concerns', $this->tokenFor($helpdesk))
            ->assertOk()
            ->assertJsonPath('categories.0.group', 'System Issues')
            ->assertJsonPath('urgencies.2', 'High');

        $this->postJson('/api/mobile/staff/developer-concerns', [
            'subject' => 'Report page slow',
            'details' => 'Executive dashboard takes 20s',
            'category' => 'Page Not Loading / Slow',
            'urgency' => 'High',
        ], $this->tokenFor($helpdesk))
            ->assertCreated()
            ->assertJsonPath('concern.status', DeveloperConcern::OPEN);
        Mail::assertQueued(DeveloperConcernSubmittedMail::class);

        $this->postJson('/api/mobile/staff/developer-concerns', ['subject' => 'x', 'details' => 'y', 'category' => 'Nope', 'urgency' => 'High'], $this->tokenFor($helpdesk))
            ->assertStatus(422)->assertJsonValidationErrors('category');

        $concern = DeveloperConcern::first();
        $this->app['auth']->forgetGuards();
        $headers = $this->tokenFor($developer);

        $this->getJson('/api/mobile/developer/concerns', $headers)->assertOk()->assertJsonPath('counts.open', 1)->assertJsonPath('data.0.id', $concern->id);
        $this->patchJson("/api/mobile/developer/concerns/{$concern->id}/acknowledge", [], $headers)->assertOk()->assertJsonPath('concern.status', DeveloperConcern::ACKNOWLEDGED);
        // An instant repeat is a double tap (409); a later one hits the web's own guard.
        $this->patchJson("/api/mobile/developer/concerns/{$concern->id}/acknowledge", [], $headers)->assertStatus(409);
        Cache::flush();
        $this->patchJson("/api/mobile/developer/concerns/{$concern->id}/acknowledge", [], $headers)->assertStatus(422)->assertJsonPath('message', 'This concern is not open.');
        $this->patchJson("/api/mobile/developer/concerns/{$concern->id}/close", [], $headers)->assertStatus(422)->assertJsonValidationErrors('closing_note');
        $this->patchJson("/api/mobile/developer/concerns/{$concern->id}/close", ['closing_note' => 'Added an index.'], $headers)->assertOk()->assertJsonPath('concern.status', DeveloperConcern::CLOSED);
        Mail::assertQueued(DeveloperConcernAcknowledgedMail::class);
    }

    public function test_calendar_day_and_week(): void
    {
        $tech = $this->staff('IT Support Specialist');
        $start = now('Asia/Manila')->startOfDay()->setTime(9, 0);
        $this->staffTicket(['status' => TicketStatus::ASSIGNED, 'pending_role' => null, 'assigned_to' => $tech->id, 'scheduled_start' => $start, 'scheduled_end' => $start->copy()->addHours(2), 'resolution_time_minutes' => 120]);
        $headers = $this->tokenFor($tech);

        $this->getJson('/api/mobile/staff/calendar?view=day&date=' . $start->format('Y-m-d'), $headers)
            ->assertOk()
            ->assertJsonPath('is_own', true)
            ->assertJsonPath('rows.0.technician.id', $tech->id)
            ->assertJsonPath('rows.0.blocks.0.ticket.status', TicketStatus::ASSIGNED);

        $this->getJson('/api/mobile/staff/calendar?view=week', $headers)
            ->assertOk()
            ->assertJsonCount(5, 'columns')
            ->assertJsonPath('rows.0.technician.id', $tech->id);

        $this->app['auth']->forgetGuards();
        $this->getJson('/api/mobile/staff/calendar', $this->tokenFor($this->staff('Helpdesk')))->assertForbidden();
    }
}
