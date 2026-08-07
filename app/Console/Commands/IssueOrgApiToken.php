<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

// Issues a read-only 'org:read' Sanctum token for an in-house system to consume
// the /api/org/* directory endpoints. All tokens are owned by one shared,
// non-interactive service-account user (no password login, no web role) rather
// than a real employee account, so revoking access for one system never touches
// another and never affects a real user's session.
class IssueOrgApiToken extends Command
{
    protected $signature = 'api:issue-org-token {system : Short name identifying the consuming system, e.g. "finance-system"}';

    protected $description = 'Issue a read-only org/user-directory API token for an in-house system to consume';

    private const SERVICE_ACCOUNT_EMAIL = 'api-integration@internal.local';

    public function handle(): int
    {
        $system = $this->argument('system');

        $serviceAccount = User::firstOrCreate(
            ['email' => self::SERVICE_ACCOUNT_EMAIL],
            [
                'name' => 'API Integration Account',
                'password' => Str::random(40),
                'position' => 'System Integration',
                'active' => true,
            ]
        );

        $token = $serviceAccount->createToken($system, ['org:read'])->plainTextToken;

        $this->info("Read-only org/user-directory token for \"{$system}\":");
        $this->line($token);
        $this->warn('This is shown only once — store it securely now. Send it to that system as a Bearer token.');
        $this->line('Revoke it later from the DB (personal_access_tokens table, filter by name) if that system is decommissioned.');

        return self::SUCCESS;
    }
}
