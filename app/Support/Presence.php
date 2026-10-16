<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// Who is "online right now": active on the web (session activity) or on the
// mobile app (its API token used — Sanctum stamps last_used_at on every request)
// within the window. Used by every "Available IT" / team presence panel and by
// self-triage's Helpdesk coverage check, so mobile-only staff don't look away.
class Presence
{
    // The token name the mobile app logs in with (`client` on /api/login and
    // /api/auth/microsoft — ticketing-mobile's API_CLIENT_NAME).
    public const MOBILE_TOKEN_NAME = 'mobile';

    public static function onlineUserIds(int $minutes = 5): Collection
    {
        $web = DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', now()->subMinutes($minutes)->timestamp)
            ->distinct()
            ->pluck('user_id');

        // Only the mobile app's own token — other in-house systems also log
        // users in through /api/login (their own token names), and server-side
        // calls from those must not make someone look online here.
        $mobile = DB::table('personal_access_tokens')
            ->where('tokenable_type', \App\Models\User::class)
            ->where('name', self::MOBILE_TOKEN_NAME)
            ->where('last_used_at', '>=', now()->subMinutes($minutes))
            ->distinct()
            ->pluck('tokenable_id');

        return $web->merge($mobile)->map(fn ($id) => (string) $id)->unique()->values();
    }
}
