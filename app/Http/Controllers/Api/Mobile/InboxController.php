<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\MobileNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;

// Staff in-app notifications (the bell): everything StaffNotifier recorded for
// the signed-in user — the mobile copy of their "Action Needed"/SLA/FYI emails
// and new chat messages. Without ?since=: the latest 50. With ?since= (the
// previous server_time): what's new since then, oldest-first in pages of 50 —
// keep polling with the returned server_time; de-duplicate by id.
class InboxController extends Controller
{
    private const LIMIT = 50;

    public function index(Request $request)
    {
        $request->validate(['since' => 'nullable|date']);
        $user = $request->user();
        // Taken before the query, so anything created while it runs is picked
        // up by the next poll (>= since; the app de-duplicates by id).
        $cutoff = now();

        if ($request->filled('since')) {
            // Parsed and converted to the app timezone: created_at has no zone,
            // so a raw "+08:00" string would have its offset ignored.
            $since = Carbon::parse($request->since)->setTimezone(config('app.timezone'));

            // Oldest first after `since`, so a long absence pages forward
            // instead of losing everything beyond the limit.
            $items = MobileNotification::where('user_id', $user->id)
                ->where('created_at', '>=', $since)
                ->oldest('created_at')
                ->limit(self::LIMIT)
                ->get();
            $serverTime = $items->count() === self::LIMIT ? $items->last()->created_at : $cutoff;
            $items = $items->reverse()->values();
        } else {
            $items = MobileNotification::where('user_id', $user->id)
                ->latest('created_at')
                ->limit(self::LIMIT)
                ->get();
            $serverTime = $cutoff;
        }

        return response()->json([
            'notifications' => $items->map(fn (MobileNotification $n) => [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'body' => $n->body,
                'ticket_id' => $n->ticket_id,
                'concern_id' => $n->concern_id,
                'is_read' => $n->read_at !== null,
                'at' => $n->created_at?->toIso8601String(),
            ]),
            'unread' => MobileNotification::where('user_id', $user->id)->whereNull('read_at')->count(),
            'server_time' => $serverTime->toIso8601String(),
        ]);
    }

    // Mark the given notifications (or all, when no ids) as read.
    public function read(Request $request)
    {
        $request->validate(['ids' => 'nullable|array', 'ids.*' => 'uuid']);

        // `ids` present (even empty) = just those; absent = everything.
        MobileNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->when($request->has('ids'), fn ($q) => $q->whereIn('id', (array) $request->input('ids', [])))
            ->update(['read_at' => now()]);

        return response()->noContent();
    }
}
