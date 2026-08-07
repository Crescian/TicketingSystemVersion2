<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

// Issues a read-only 'tickets:read' Sanctum token for an in-house system to
// consume the /api/tickets analysis endpoint. Reuses the same shared
// service-account user as IssueOrgApiToken (no password login, no web role),
// but the ability is scoped separately from 'org:read' so a system can be
// granted ticket-data access without also getting user-directory access (or
// vice versa). Token name is suffixed ":tickets" so it can be revoked
// independently of an org:read token issued to the same system name.
class IssueTicketsApiToken extends Command
{
    protected $signature = 'api:issue-tickets-token {system : Short name identifying the consuming system, e.g. "analytics-dashboard"}';

    protected $description = 'Issue a read-only ticket-analysis API token for an in-house system to consume';

    private const SERVICE_ACCOUNT_EMAIL = 'api-integration@internal.local';

    public function handle(): int
    {
        $system = $this->argument('system');
        $tokenName = "{$system}:tickets";

        $serviceAccount = User::firstOrCreate(
            ['email' => self::SERVICE_ACCOUNT_EMAIL],
            [
                'name' => 'API Integration Account',
                'password' => Str::random(40),
                'position' => 'System Integration',
                'active' => true,
            ]
        );

        $token = $serviceAccount->createToken($tokenName, ['tickets:read'])->plainTextToken;

        $this->info("Read-only ticket-analysis token for \"{$system}\":");
        $this->line($token);
        $this->warn('This is shown only once — store it securely now. Send it to that system as a Bearer token.');
        $this->line("Revoke it later from the DB (personal_access_tokens table, filter by name = '{$tokenName}') if that system is decommissioned.");

        return self::SUCCESS;
    }
}
