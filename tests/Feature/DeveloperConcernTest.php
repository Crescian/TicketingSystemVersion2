<?php

namespace Tests\Feature;

use App\Mail\DeveloperConcernAcknowledgedMail;
use App\Mail\DeveloperConcernClosedMail;
use App\Mail\DeveloperConcernSubmittedMail;
use App\Models\DeveloperConcern;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

// "Report to Developer": ICT staff send concerns, only the Developer acknowledges
// and closes them. The project's migrations are Postgres-specific, so only the
// tables these routes touch are built here (same approach as TicketHoldSlaTest).
class DeveloperConcernTest extends TestCase
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

        Mail::fake();
    }

    private function userWithRole(string $roleName, string $email = null): User
    {
        $role = Role::firstOrCreate(['role_name' => $roleName], ['description' => $roleName]);

        return User::forceCreate([
            'id' => (string) Str::uuid(),
            'name' => "{$roleName} User",
            'email' => $email ?? Str::slug($roleName) . '-' . Str::random(4) . '@example.com',
            'password' => 'x',
            'role_id' => $role->id,
            'active' => true,
        ]);
    }

    private function concernFrom(User $sender, array $attrs = []): DeveloperConcern
    {
        return DeveloperConcern::create($attrs + [
            'submitted_by' => $sender->id,
            'subject' => 'Export button broken',
            'details' => 'Clicking export does nothing.',
            'category' => 'System Error / Bug',
            'urgency' => 'High',
        ]);
    }

    public function test_ict_staff_can_send_a_concern_and_developer_is_emailed(): void
    {
        $developer = $this->userWithRole('Developer', 'cmlanoy@leoniogroup.com');
        $sender = $this->userWithRole('Helpdesk');

        $this->actingAs($sender)
            ->post(route('developer-concerns.store'), [
                'subject' => 'Export button broken',
                'details' => 'Clicking export does nothing.',
                'category' => 'System Error / Bug',
                'urgency' => 'High',
            ])
            ->assertRedirect(route('developer-concerns.index'));

        $concern = DeveloperConcern::sole();
        $this->assertSame(DeveloperConcern::OPEN, $concern->status);
        $this->assertSame($sender->id, $concern->submitted_by);
        $this->assertMatchesRegularExpression('/^DEV-\d{2}-0001$/', $concern->reference);

        Mail::assertQueued(DeveloperConcernSubmittedMail::class, fn ($m) => $m->hasTo($developer->email));
    }

    public function test_network_category_is_accepted(): void
    {
        $this->userWithRole('Developer');

        $this->actingAs($this->userWithRole('IT Support Specialist'))
            ->post(route('developer-concerns.store'), [
                'subject' => 'Cannot reach internal sites',
                'details' => 'PC-0412 at 3F: no LGPN, internet works.',
                'category' => 'No Connection to LGPN (outside network OK)',
                'urgency' => 'High',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('No Connection to LGPN (outside network OK)', DeveloperConcern::sole()->category);
    }

    public function test_unknown_category_is_rejected(): void
    {
        $this->actingAs($this->userWithRole('Helpdesk'))
            ->post(route('developer-concerns.store'), [
                'subject' => 'x', 'details' => 'y', 'category' => 'Made Up', 'urgency' => 'Low',
            ])
            ->assertSessionHasErrors('category');

        $this->assertSame(0, DeveloperConcern::count());
    }

    public function test_employee_cannot_send_a_concern(): void
    {
        $this->actingAs($this->userWithRole('Employee'))
            ->post(route('developer-concerns.store'), [
                'subject' => 'x', 'details' => 'y', 'category' => 'System Error / Bug', 'urgency' => 'Low',
            ])
            ->assertForbidden();

        $this->assertSame(0, DeveloperConcern::count());
    }

    public function test_only_the_developer_can_acknowledge_and_close(): void
    {
        $concern = $this->concernFrom($this->userWithRole('IT Admin'));
        $helpdesk = $this->userWithRole('Helpdesk');

        $this->actingAs($helpdesk)
            ->patch(route('developer.concerns.acknowledge', $concern))
            ->assertForbidden();
        $this->actingAs($helpdesk)
            ->patch(route('developer.concerns.close', $concern), ['closing_note' => 'done'])
            ->assertForbidden();

        $this->assertSame(DeveloperConcern::OPEN, $concern->fresh()->status);
    }

    public function test_developer_acknowledges_then_closes_and_sender_is_emailed(): void
    {
        $developer = $this->userWithRole('Developer');
        $sender = $this->userWithRole('IT Support Specialist');
        $concern = $this->concernFrom($sender);

        $this->actingAs($developer)->patch(route('developer.concerns.acknowledge', $concern));
        $this->assertSame(DeveloperConcern::ACKNOWLEDGED, $concern->fresh()->status);
        $this->assertNotNull($concern->fresh()->acknowledged_at);
        Mail::assertQueued(DeveloperConcernAcknowledgedMail::class, fn ($m) => $m->hasTo($sender->email));

        $this->actingAs($developer)
            ->patch(route('developer.concerns.close', $concern), ['closing_note' => 'Fixed in v2.1.']);

        $concern->refresh();
        $this->assertSame(DeveloperConcern::CLOSED, $concern->status);
        $this->assertSame('Fixed in v2.1.', $concern->closing_note);
        $this->assertNotNull($concern->closed_at);
        Mail::assertQueued(DeveloperConcernClosedMail::class, fn ($m) => $m->hasTo($sender->email));
    }

    public function test_close_requires_a_note_and_closing_open_concern_acknowledges_it(): void
    {
        $developer = $this->userWithRole('Developer');
        $concern = $this->concernFrom($this->userWithRole('Manager'));

        $this->actingAs($developer)
            ->patch(route('developer.concerns.close', $concern), ['closing_note' => ''])
            ->assertSessionHasErrors('closing_note');
        $this->assertSame(DeveloperConcern::OPEN, $concern->fresh()->status);

        $this->actingAs($developer)
            ->patch(route('developer.concerns.close', $concern), ['closing_note' => 'Not a bug.']);

        $concern->refresh();
        $this->assertSame(DeveloperConcern::CLOSED, $concern->status);
        $this->assertNotNull($concern->acknowledged_at);
    }

    public function test_acknowledging_a_closed_concern_is_rejected(): void
    {
        $developer = $this->userWithRole('Developer');
        $concern = $this->concernFrom($this->userWithRole('Helpdesk'), [
            'status' => DeveloperConcern::CLOSED,
            'closing_note' => 'done',
        ]);

        $this->actingAs($developer)
            ->patch(route('developer.concerns.acknowledge', $concern))
            ->assertSessionHas('error');

        Mail::assertNothingQueued();
    }

    public function test_developer_lands_on_the_developer_dashboard(): void
    {
        $developer = $this->userWithRole('Developer');

        $this->assertSame('developer.dashboard', $developer->load('role')->dashboardRoute());
    }
}
