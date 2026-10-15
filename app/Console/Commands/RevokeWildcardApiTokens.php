<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Sanctum\PersonalAccessToken;

// One-off cleanup for user login tokens issued before /api/login switched to the
// scoped 'user' ability. Those older tokens carry '*', which also passes the
// 'org:read' / 'tickets:read' checks meant only for integration tokens.
// Revoking them signs those users out of whichever system holds the token
// (the token name is the system); they just log in again and get a scoped one.
class RevokeWildcardApiTokens extends Command
{
    protected $signature = 'api:revoke-wildcard-tokens {--dry-run : List the tokens without revoking them}';

    protected $description = "Revoke API tokens that carry the '*' ability (pre-scoping user login tokens)";

    public function handle(): int
    {
        $tokens = PersonalAccessToken::with('tokenable')
            ->get()
            ->filter(fn (PersonalAccessToken $t) => in_array('*', $t->abilities ?? [], true));

        if ($tokens->isEmpty()) {
            $this->info('No wildcard tokens found.');

            return self::SUCCESS;
        }

        $this->table(
            ['Token name (system)', 'User', 'Created', 'Last used'],
            $tokens->map(fn ($t) => [$t->name, $t->tokenable?->email ?? '(deleted user)', $t->created_at, $t->last_used_at ?? 'never'])
        );

        if ($this->option('dry-run')) {
            $this->comment("Dry run — {$tokens->count()} token(s) would be revoked.");

            return self::SUCCESS;
        }

        if (!$this->confirm("Revoke these {$tokens->count()} token(s)? Those users will need to log in again in those systems.")) {
            return self::FAILURE;
        }

        PersonalAccessToken::whereIn('id', $tokens->pluck('id'))->delete();
        $this->info("Revoked {$tokens->count()} token(s).");

        return self::SUCCESS;
    }
}
