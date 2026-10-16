<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\MobileNotification;
use Illuminate\Http\Request;

// Staff in-app notifications (the bell): everything StaffNotifier recorded for
// the signed-in user — the mobile copy of their "Action Needed"/SLA/FYI emails
// and new chat messages. Pass the previous server_time as ?since= to fetch
// only new ones.
class InboxController extends Controller
{
    private const LIMIT = 50;

    public function index(Request $request)
    {
        $request->validate(['since' => 'nullable|date']);
        $user = $request->user();

        $items = MobileNotification::where('user_id', $user->id)
            ->when($request->filled('since'), fn ($q) => $q->where('created_at', '>', $request->since))
            ->latest('created_at')
            ->limit(self::LIMIT)
            ->get();

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
            'server_time' => now()->toIso8601String(),
        ]);
    }

    // Mark the given notifications (or all, when no ids) as read.
    public function read(Request $request)
    {
        $request->validate(['ids' => 'nullable|array', 'ids.*' => 'uuid']);

        MobileNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->when($request->filled('ids'), fn ($q) => $q->whereIn('id', $request->ids))
            ->update(['read_at' => now()]);

        return response()->noContent();
    }
}
