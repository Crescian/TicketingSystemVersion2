<?php

namespace App\Jobs;

use App\Mail\TicketChatDigestMail;
use App\Models\TicketMessage;
use App\Models\Tickets;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

// Debounced chat-digest email: fired whenever staff sends a message on a
// ticket, but delayed — if the conversation is still active when it wakes up
// it reschedules itself instead of sending, so a fast back-and-forth chat
// collapses into one email instead of one per message. Bounded to 30 minutes
// max wait so a long-running live chat still eventually notifies.
class SendTicketChatDigest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const QUIET_PERIOD_MINUTES = 2;
    private const MAX_WAIT_MINUTES = 30;

    public function __construct(public Tickets $ticket)
    {
        //
    }

    public function handle(): void
    {
        $ticket = $this->ticket->fresh();

        if (!$ticket || !$ticket->user || !$ticket->user->email) {
            Cache::forget($this->lockKey());
            return;
        }

        $pending = TicketMessage::where('ticket_id', $ticket->id)
            ->where('sender_id', '!=', $ticket->users_id)
            ->whereNull('notified_at')
            ->orderBy('created_at')
            ->with('sender')
            ->get();

        if ($pending->isEmpty()) {
            Cache::forget($this->lockKey());
            return;
        }

        $stillActive = $pending->last()->created_at->gt(now()->subMinutes(self::QUIET_PERIOD_MINUTES));
        $withinWaitCeiling = $pending->first()->created_at->gt(now()->subMinutes(self::MAX_WAIT_MINUTES));

        if ($stillActive && $withinWaitCeiling) {
            self::dispatch($ticket)->delay(now()->addMinutes(self::QUIET_PERIOD_MINUTES));
            return;
        }

        // Atomically claim these exact rows before sending. Whatever caused the
        // scheduling lock to let a duplicate job through (observed in testing —
        // two SendTicketChatDigest instances reaching this point for the same
        // ticket within seconds of each other), this UPDATE is the real
        // safety net: only the instance that actually flips notified_at from
        // NULL sends the email. A second instance racing here claims 0 rows
        // and walks away instead of sending a duplicate digest.
        $claimed = TicketMessage::whereIn('id', $pending->pluck('id'))
            ->whereNull('notified_at')
            ->update(['notified_at' => now()]);

        Cache::forget($this->lockKey());

        if ($claimed === 0) {
            return;
        }

        Mail::to($ticket->user->email)->send(new TicketChatDigestMail($ticket, $pending));
    }

    public static function scheduleIfNeeded(Tickets $ticket): void
    {
        $lockKey = self::buildLockKey($ticket->id);

        if (Cache::add($lockKey, true, now()->addMinutes(self::MAX_WAIT_MINUTES + 5))) {
            self::dispatch($ticket)->delay(now()->addMinutes(self::QUIET_PERIOD_MINUTES));
        }
    }

    private function lockKey(): string
    {
        return self::buildLockKey($this->ticket->id);
    }

    private static function buildLockKey(string $ticketId): string
    {
        return "chat-digest-pending:{$ticketId}";
    }
}
