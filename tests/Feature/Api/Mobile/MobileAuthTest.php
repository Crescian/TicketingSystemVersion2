<?php

namespace Tests\Feature\Api\Mobile;

use App\Models\UserDevice;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;

class MobileAuthTest extends MobileApiTestCase
{
    public function test_login_issues_a_scoped_token_not_a_wildcard(): void
    {
        $employee = $this->employee(['password' => Hash::make('secret-password')]);

        $response = $this->postJson('/api/login', [
            'email' => $employee->email,
            'password' => 'secret-password',
            'client' => 'mobile',
        ])->assertOk()
            ->assertJsonPath('user.role', 'Employee')
            ->assertJsonPath('user.needs_account_setup', false);

        $this->assertSame(['user'], $employee->tokens()->first()->abilities);

        // The integration-only endpoints must reject a user login token.
        $headers = ['Authorization' => 'Bearer ' . $response->json('access_token'), 'Accept' => 'application/json'];
        $this->getJson('/api/org/users', $headers)->assertForbidden();
        $this->getJson('/api/tickets', $headers)->assertForbidden();
        $this->getJson('/api/mobile/me', $headers)->assertOk()->assertJsonPath('data.department.name', 'Accounting');
    }

    public function test_login_still_works_for_staff_used_by_other_systems(): void
    {
        $admin = $this->userWithRole('IT Admin', ['password' => Hash::make('secret-password')]);

        $this->postJson('/api/login', ['email' => $admin->email, 'password' => 'secret-password'])
            ->assertOk()
            ->assertJsonPath('user.role', 'IT Admin');
    }

    public function test_mobile_routes_are_employee_only(): void
    {
        $admin = $this->userWithRole('IT Admin');

        $this->getJson('/api/mobile/tickets', $this->tokenFor($admin))->assertForbidden();
    }

    public function test_unauthenticated_gets_json_401_without_accept_header(): void
    {
        $this->get('/api/mobile/tickets')->assertUnauthorized()->assertJsonStructure(['message']);
    }

    public function test_integration_token_cannot_use_mobile_routes(): void
    {
        $employee = $this->employee();
        $token = $employee->createToken('finance-system', ['org:read'])->plainTextToken;

        $this->getJson('/api/mobile/tickets', ['Authorization' => "Bearer {$token}"])->assertForbidden();
    }

    public function test_account_setup_gate_blocks_until_org_info_and_password_are_done(): void
    {
        $employee = $this->employee([
            'password' => Hash::make('Temporary1'),
            'needs_account_setup' => true,
            'must_change_password' => true,
        ]);
        $headers = $this->tokenFor($employee);

        $this->getJson('/api/mobile/tickets', $headers)
            ->assertForbidden()
            ->assertJsonPath('code', 'account_setup_required');

        $this->getJson('/api/mobile/me', $headers)->assertOk()->assertJsonPath('data.needs_account_setup', true);
        $this->getJson('/api/mobile/org/hierarchy', $headers)->assertOk()->assertJsonPath('0.companies.0.departments.0.name', 'Accounting');

        $this->putJson('/api/mobile/profile/org-info', [
            'department_id' => $employee->department_id,
            'position' => 'Senior Accountant',
        ], $headers)->assertOk()->assertJsonPath('data.can_update_org_info', false);

        // One lifetime self-update only.
        $this->putJson('/api/mobile/profile/org-info', ['department_id' => $employee->department_id], $headers)
            ->assertStatus(422);

        $this->putJson('/api/mobile/profile/password', [
            'current_password' => 'wrong',
            'password' => 'NewPassword1',
            'password_confirmation' => 'NewPassword1',
        ], $headers)->assertStatus(422)->assertJsonValidationErrors('current_password');

        $this->putJson('/api/mobile/profile/password', [
            'current_password' => 'Temporary1',
            'password' => 'NewPassword1',
            'password_confirmation' => 'NewPassword1',
        ], $headers)->assertOk()->assertJsonPath('data.needs_account_setup', false);

        $this->getJson('/api/mobile/tickets', $headers)->assertOk();
    }

    public function test_microsoft_token_from_our_tenant_and_app_signs_in(): void
    {
        config(['services.azure.tenant' => 'our-tenant', 'services.azure.mobile_client_id' => 'mobile-app']);
        $employee = $this->employee(['email' => 'juan@leoniogroup.com']);
        $this->mockAzureUser('azure-oid-1', 'juan@leoniogroup.com');

        $this->postJson('/api/auth/microsoft', ['access_token' => $this->fakeJwt(['tid' => 'our-tenant', 'appid' => 'mobile-app'])])
            ->assertOk()
            ->assertJsonPath('user.id', $employee->id);

        $this->assertSame('azure-oid-1', $employee->fresh()->microsoft_id);
    }

    public function test_microsoft_token_from_another_tenant_is_rejected(): void
    {
        config(['services.azure.tenant' => 'our-tenant', 'services.azure.mobile_client_id' => 'mobile-app']);
        $this->employee(['email' => 'juan@leoniogroup.com']);
        $this->mockAzureUser('azure-oid-1', 'juan@leoniogroup.com');

        $this->postJson('/api/auth/microsoft', ['access_token' => $this->fakeJwt(['tid' => 'other-tenant', 'appid' => 'mobile-app'])])
            ->assertUnauthorized();

        $this->postJson('/api/auth/microsoft', ['access_token' => $this->fakeJwt(['tid' => 'our-tenant', 'appid' => 'someone-elses-app'])])
            ->assertUnauthorized();
    }

    public function test_logout_unregisters_the_device(): void
    {
        $employee = $this->employee();
        UserDevice::create(['user_id' => $employee->id, 'expo_push_token' => 'ExponentPushToken[abc]']);

        $this->postJson('/api/logout', ['expo_push_token' => 'ExponentPushToken[abc]'], $this->tokenFor($employee))
            ->assertOk();

        $this->assertDatabaseCount('user_devices', 0);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    private function mockAzureUser(string $id, string $email): void
    {
        $azureUser = (new SocialiteUser)->setRaw([])->map(['id' => $id, 'email' => $email, 'name' => 'Juan']);
        $driver = Mockery::mock();
        $driver->shouldReceive('userFromToken')->andReturn($azureUser);
        Socialite::shouldReceive('driver')->with('azure')->andReturn($driver);
    }

    private function fakeJwt(array $claims): string
    {
        $encode = fn ($data) => rtrim(strtr(base64_encode(json_encode($data)), '+/', '-_'), '=');

        return $encode(['alg' => 'RS256']) . '.' . $encode($claims) . '.signature';
    }
}
