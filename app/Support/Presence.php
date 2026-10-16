<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// Who is "online right now": active on the web (session activity) or on the
// mobile app (API token used — Sanctum stamps last_used_at on every request)
// within the window. Used by every "Available IT" / team presence panel and by
// self-triage's Helpdesk coverage check, so mobile-only staff don't look away.
class Presence
{
    public static function onlineUserIds(int $minutes = 5): Collection
    {
        $web = DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', now()->subMinutes($minutes)->timestamp)
            ->distinct()
            ->pluck('user_id');

        $mobile = DB::table('personal_access_tokens')
            ->where('tokenable_type', \App\Models\User::class)
            ->where('last_used_at', '>=', now()->subMinutes($minutes))
            ->distinct()
            ->pluck('tokenable_id');

        return $web->merge($mobile)->map(fn ($id) => (string) $id)->unique()->values();
    }
}
