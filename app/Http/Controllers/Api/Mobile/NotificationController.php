<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\TicketMessage;
use App\Models\TicketStatusHistories;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

// In-app activity feed for the employee's own tickets: status changes made by
// IT and messages IT sent, newest first. Push notifications cover "while the
// app is closed"; this is for the bell screen and for catching up after the
// app was offline. Pass the previous response's server_time as ?since= to
// fetch only what's new.
class NotificationController extends Controller
{
    private const DEFAULT_LOOKBACK_DAYS = 7;
    private const LIMIT = 50;

    public function index(Request $request)
    {
        $request->validate(['since' => 'nullable|date']);

        $user = $request->user();
        $since = $request->filled('since')
            ? Carbon::parse($request->since)
            : now()->subDays(self::DEFAULT_LOOKBACK_DAYS);

        $statusChanges = TicketStatusHistories::query()
            ->whereHas('ticket', fn ($q) => $q->where('users_id', $user->id))
            ->where(fn ($q) => $q->whereNull('changed_by')->orWhere('changed_by', '!=', $user->id))
            ->where('changed_at', '>', $since)
            ->with('ticket:id,ticket_number,subject')
            ->latest('changed_at')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn ($h) => [
                'type' => 'status_change',
                'ticket_id' => $h->ticket_id,
                'ticket_number' => $h->ticket?->ticket_number,
                'title' => $h->new_status,
                'body' => $h->notes ?: $h->ticket?->subject,
                'at' => Carbon::parse($h->changed_at)->toIso8601String(),
            ]);

        $messages = TicketMessage::query()
            ->whereHas('ticket', fn ($q) => $q->where('users_id', $user->id))
            ->where('sender_id', '!=', $user->id)
            ->where('created_at', '>', $since)
            ->with(['ticket:id,ticket_number', 'sender:id,name'])
            ->latest('created_at')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn ($m) => [
                'type' => 'new_message',
                'ticket_id' => $m->ticket_id,
                'ticket_number' => $m->ticket?->ticket_number,
                'title' => 'Message from ' . ($m->sender?->name ?? 'IT'),
                'body' => $m->message !== '' ? Str::limit($m->message, 120) : 'Sent an attachment',
                'is_read' => (bool) $m->is_read,
                'at' => $m->created_at->toIso8601String(),
            ]);

        return response()->json([
            'notifications' => $statusChanges->concat($messages)->sortByDesc('at')->take(self::LIMIT)->values(),
            'server_time' => now()->toIso8601String(),
        ]);
    }
}
