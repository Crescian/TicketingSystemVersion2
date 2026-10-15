<?php

namespace Tests\Feature\Api\Mobile;

use App\Models\BusinessUnits;
use App\Models\Companies;
use App\Models\Departments;
use App\Models\Role;
use App\Models\Tickets;
use App\Models\User;
use App\Support\TicketStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

// Shared setup for the employee mobile API tests. The project's migrations are
// Postgres-specific, so only the tables the mobile endpoints touch are built
// here (same approach as SupervisorReturnToHelpdeskTest).
abstract class MobileApiTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Postgres RIGHT() used by Tickets::generateTicketNumber().
        DB::connection()->getPdo()->sqliteCreateFunction('RIGHT', fn ($s, $n) => substr((string) $s, -$n), 2);

        Schema::create('roles', function ($table) {
            $table->uuid('id')->primary();
            $table->string('role_name');
            $table->string('description')->nullable();
            $table->unsignedTinyInteger('level')->nullable();
            $table->timestamps();
        });

        Schema::create('business_units', function ($table) {
            $table->uuid('id')->primary();
            $table->string('business_units_name');
        });

        Schema::create('companies', function ($table) {
            $table->uuid('id')->primary();
            $table->string('company_name');
            $table->uuid('business_units_id');
        });

        Schema::create('departments', function ($table) {
            $table->uuid('id')->primary();
            $table->uuid('companies_id');
            $table->string('department_name');
        });

        Schema::create('users', function ($table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('microsoft_id')->nullable();
            $table->uuid('role_id')->nullable();
            $table->uuid('department_id')->nullable();
            $table->string('position')->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('must_change_password')->default(false);
            $table->boolean('needs_account_setup')->default(false);
            $table->timestamp('org_info_updated_at')->nullable();
            $table->timestamp('onboarded_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('personal_access_tokens', function ($table) {
            $table->id();
            $table->uuidMorphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sla_categories', function ($table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('tickets', function ($table) {
            $table->uuid('id')->primary();
            $table->string('ticket_number');
            $table->uuid('users_id')->nullable();
            $table->uuid('assigned_to')->nullable();
            $table->uuid('resolved_by')->nullable();
            $table->string('ticket_type')->nullable();
            $table->string('request_category')->nullable();
            $table->string('subject')->nullable();
            $table->text('concern')->nullable();
            $table->text('request_details')->nullable();
            $table->string('asset')->nullable();
            $table->string('location')->nullable();
            $table->string('status');
            $table->string('pending_role')->nullable();
            $table->unsignedInteger('escalation_level')->default(0);
            $table->string('position')->nullable();
            $table->string('business_unit')->nullable();
            $table->string('company')->nullable();
            $table->string('department')->nullable();
            $table->string('date_received')->nullable();
            $table->string('time_received')->nullable();
            $table->string('method')->nullable();
            $table->date('date_acknowledged')->nullable();
            $table->time('time_acknowledged')->nullable();
            $table->uuid('sla_category_id')->nullable();
            $table->string('subcategory_name')->nullable();
            $table->timestamp('scheduled_start')->nullable();
            $table->text('hold_reason')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
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

        Schema::create('ticket_attachments', function ($table) {
            $table->uuid('id')->primary();
            $table->uuid('ticket_id');
            $table->uuid('ticket_message_id')->nullable();
            $table->uuid('uploaded_by')->nullable();
            $table->string('original_name');
            $table->string('stored_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();
        });

        Schema::create('ticket_messages', function ($table) {
            $table->uuid('id')->primary();
            $table->uuid('ticket_id');
            $table->uuid('sender_id');
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('ticket_feed_backs', function ($table) {
            $table->uuid('id')->primary();
            $table->uuid('ticket_id');
            $table->uuid('user_id');
            $table->unsignedTinyInteger('rating');
            $table->text('comments')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('user_devices', function ($table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('expo_push_token')->unique();
            $table->string('platform', 20)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Mail::fake();
        Storage::fake('local');
    }

    protected function userWithRole(string $roleName, array $attrs = []): User
    {
        $role = Role::firstOrCreate(['role_name' => $roleName], ['description' => $roleName]);

        return User::forceCreate($attrs + [
            'id' => (string) Str::uuid(),
            'name' => "{$roleName} User",
            'email' => Str::slug($roleName) . '-' . Str::random(4) . '@example.com',
            'password' => 'secret-password',
            'role_id' => $role->id,
            'active' => true,
        ]);
    }

    protected function employee(array $attrs = []): User
    {
        $bu = BusinessUnits::create(['business_units_name' => 'Leonio Land']);
        $company = Companies::create(['company_name' => 'LLRI', 'business_units_id' => $bu->id]);
        $department = Departments::create(['department_name' => 'Accounting', 'companies_id' => $company->id]);

        return $this->userWithRole('Employee', $attrs + [
            'name' => 'Juan Dela Cruz',
            'position' => 'Accountant',
            'department_id' => $department->id,
        ]);
    }

    // Bearer headers for a real login-issued token (the 'user' ability).
    protected function tokenFor(User $user): array
    {
        $token = $user->createToken('mobile', ['user'])->plainTextToken;

        return ['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'];
    }

    protected function ticketFor(User $user, array $attrs = []): Tickets
    {
        return Tickets::forceCreate($attrs + [
            'id' => (string) Str::uuid(),
            'ticket_number' => 'LGICT-26-' . random_int(1000, 9999),
            'users_id' => $user->id,
            'subject' => 'Printer not working',
            'concern' => 'Paper jam on 3F printer',
            'ticket_type' => 'Medium',
            'status' => TicketStatus::FOR_ACKNOWLEDGMENT,
            'pending_role' => TicketStatus::QUEUE_HELPDESK,
        ]);
    }
}
