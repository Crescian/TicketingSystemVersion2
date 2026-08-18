<?php

namespace App\Services;

use App\Models\Leave;
use App\Models\Tickets;
use App\Models\User;
use App\Support\BusinessClock;
use App\Support\TicketStatus;
use Carbon\Carbon;

// Lays out ICT Support Specialist tickets into time slots against the 8:00 AM-5:00 PM
// business day (App\Support\BusinessClock), replacing the old "max 5 active tickets"
// workload gauge. A specialist's not-yet-started tickets form a priority queue
// (Critical > High > Medium > Low, then arrival order within a tier); inserting a
// higher-priority ticket can ripple already-queued lower-priority tickets later in the
// day (or into overtime / the next business day) without re-prompting a supervisor —
// the supervisor is only ever prompted about the ticket they are actively assigning.
class TicketScheduler
{
    private const NOT_STARTED_STATUSES = [
        TicketStatus::ASSIGNED,
    ];

    private const PRIORITY_RANK = [
        'Critical' => 1,
        'High' => 2,
        'Medium' => 3,
        'Low' => 4,
    ];

    private const DEFAULT_PRIORITY_RANK = 5;

    // Free-time cutoff for "available" vs "busy" status — a judgment-call default,
    // easy to tune without touching callers.
    public const AVAILABLE_THRESHOLD_MINUTES = 60;

    // Working minutes in a full business day: 9 wall-clock hours (8am-5pm) minus the
    // 1-hour lunch gap. Dashboards divide freeMinutesToday() by this to get a percentage.
    public const WORKING_MINUTES_PER_DAY = 480;

    // Effort minutes for an already-classified ticket (reassign/takeover path, where
    // response_time_minutes/resolution_time_minutes are already persisted).
    public static function effortMinutes(Tickets $ticket): int
    {
        return (int) ($ticket->effectiveResponseTimeMinutes() ?? 0)
            + (int) ($ticket->effectiveResolutionTimeMinutes() ?? 0);
    }

    // Classify & Assign: the ticket is being newly classified, so its effort/priority
    // aren't persisted yet — caller supplies them directly. The ticket is inserted into
    // the technician's not-started queue at its priority position, which may ripple
    // already-queued lower-priority tickets later (persisted here as a side effect).
    // Returns either ['needs_decision' => true, 'start', 'end', 'day_end'] (nothing
    // persisted) or ['needs_decision' => false, 'scheduled_start', 'scheduled_end',
    // 'is_overtime', 'queued_at'] for the caller to merge into its own ticket update.
    public static function commitAssignment(
        Tickets $ticket,
        User $technician,
        string $priority,
        int $effortMinutes,
        string $decision = 'auto',
        ?Carbon $now = null
    ): array {
        $now = ($now ?? now())->copy();
        $effortMinutes = max(1, $effortMinutes);

        $existing = self::notStartedQueueItems($technician, $ticket->id);
        $candidateKey = 'NEW';
        $candidate = [
            'key' => $candidateKey,
            'priorityRank' => self::PRIORITY_RANK[$priority] ?? self::DEFAULT_PRIORITY_RANK,
            'queuedAt' => $now,
            'effort' => $effortMinutes,
        ];

        $merged = self::insertSorted($existing, $candidate);
        $candidateIndex = array_search($candidateKey, array_column($merged, 'key'), true);
        $before = array_slice($merged, 0, $candidateIndex);

        $tentativeStart = self::cursorAfter($before, $technician, $now);

        $dayEnd = BusinessClock::dayEnd($tentativeStart);
        $tentativeEnd = BusinessClock::projectWithinDay($tentativeStart, $effortMinutes);
        $fits = $tentativeEnd->lte($dayEnd);

        if (!$fits && $decision === 'auto') {
            return [
                'needs_decision' => true,
                'start' => $tentativeStart,
                'end' => $tentativeEnd,
                'day_end' => $dayEnd,
            ];
        }

        // Only the candidate and whatever sorts after it can possibly move — items
        // before it are untouched by definition (nothing was inserted ahead of them),
        // so they're deliberately excluded here rather than recomputed. Recomputing
        // them would be actively wrong: it can retroactively strip an already-decided
        // same-day-overtime placement from an existing ticket the moment anything
        // gets appended after it, since it would no longer look positionally "last".
        $afterAndCandidate = array_slice($merged, $candidateIndex);
        $full = self::layOutQueue(
            $afterAndCandidate,
            $technician,
            $now,
            $fits ? null : $candidateKey,
            $fits ? null : $decision,
            false,
            $tentativeStart
        );

        foreach ($full as $key => $slot) {
            if ($key === $candidateKey) {
                continue;
            }

            Tickets::whereKey($key)->update([
                'scheduled_start' => $slot['start'],
                'scheduled_end' => $slot['end'],
                'is_overtime' => $slot['is_overtime'],
            ]);
        }

        $candidateSlot = $full[$candidateKey];

        return [
            'needs_decision' => false,
            'scheduled_start' => $candidateSlot['start'],
            'scheduled_end' => $candidateSlot['end'],
            'is_overtime' => $candidateSlot['is_overtime'],
            'queued_at' => $now,
        ];
    }

    // Reassign/takeover: the ticket goes straight into an active state, so it can never
    // be reprioritized against other queued tickets — it simply appends after everything
    // already committed for that technician today. Never displaces/ripples anyone else.
    public static function commitDirectSlot(
        Tickets $ticket,
        User $technician,
        int $effortMinutes,
        string $decision = 'auto',
        ?Carbon $now = null
    ): array {
        $now = ($now ?? now())->copy();
        $effortMinutes = max(1, $effortMinutes);

        $existing = self::notStartedQueueItems($technician, $ticket->id);
        $cursor = self::cursorAfter($existing, $technician, $now);

        $dayEnd = BusinessClock::dayEnd($cursor);
        $start = $cursor;
        $end = BusinessClock::projectWithinDay($start, $effortMinutes);
        $fits = $end->lte($dayEnd);

        if (!$fits && $decision === 'auto') {
            return [
                'needs_decision' => true,
                'start' => $start,
                'end' => $end,
                'day_end' => $dayEnd,
            ];
        }

        $isOvertime = false;

        if (!$fits && $decision === 'next_day') {
            $start = self::startOfNextAvailableDay($technician, $cursor);
            $dayEnd = BusinessClock::dayEnd($start);
            $end = BusinessClock::projectWithinDay($start, $effortMinutes);
            $isOvertime = $end->gt($dayEnd);
        } elseif (!$fits && $decision === 'overtime') {
            $isOvertime = true;
        }

        return [
            'needs_decision' => false,
            'scheduled_start' => $start,
            'scheduled_end' => $end,
            'is_overtime' => $isOvertime,
            'queued_at' => $now,
        ];
    }

    // Rebuilds a technician's not-started queue from scratch — call after a ticket
    // leaves that queue early (declined, or promoted to In Progress) so the remaining
    // tickets' slots compact forward instead of leaving stale gaps.
    public static function resequence(User $technician, ?Carbon $now = null): void
    {
        $now = ($now ?? now())->copy();
        $items = self::notStartedQueueItems($technician);

        if (empty($items)) {
            return;
        }

        $layout = self::layOutQueue($items, $technician, $now);

        foreach ($layout as $key => $slot) {
            Tickets::whereKey($key)->update([
                'scheduled_start' => $slot['start'],
                'scheduled_end' => $slot['end'],
                'is_overtime' => $slot['is_overtime'],
            ]);
        }
    }

    public static function statusFor(User $technician, ?Carbon $now = null): string
    {
        $now = ($now ?? now())->copy();

        if (Leave::isOnLeave($technician, $now)) {
            return 'on_leave';
        }

        if (!self::isWithinBusinessHours($now)) {
            return 'available';
        }

        $dayEnd = BusinessClock::dayEnd($now);
        $lastEnd = self::latestScheduledEndToday($technician, $now);

        if ($lastEnd && $lastEnd->gt($dayEnd)) {
            return 'overtime';
        }

        return self::freeMinutesToday($technician, $now) >= self::AVAILABLE_THRESHOLD_MINUTES
            ? 'available'
            : 'busy';
    }

    public static function freeMinutesToday(User $technician, ?Carbon $now = null): int
    {
        $window = self::freeWindowToday($technician, $now);

        return $window ? BusinessClock::businessMinutesBetween($window['start'], $window['end']) : 0;
    }

    // The actual open clock range left today — e.g. 2:15 PM-5:00 PM — for anywhere
    // that needs to show the available hours rather than just a duration (the
    // Classify & Assign / Reassign picker's availability bar). Null when there's no
    // open window at all (outside business hours, or genuinely booked solid).
    public static function freeWindowToday(User $technician, ?Carbon $now = null): ?array
    {
        $now = ($now ?? now())->copy();
        $dayEnd = BusinessClock::dayEnd($now);

        if (!self::isTechnicianAvailable($technician, $now) || $now->gte($dayEnd)) {
            return null;
        }

        $lastEnd = self::latestScheduledEndToday($technician, $now);
        $cursor = ($lastEnd && $lastEnd->gt($now)) ? $lastEnd : $now->copy();

        if ($cursor->gte($dayEnd)) {
            return null;
        }

        return ['start' => $cursor, 'end' => $dayEnd];
    }

    // True only when "now" falls on a business day, before 5:00 PM — i.e. a 0-minute
    // freeMinutesToday() result is meaningful (the specialist could actually be
    // booked solid), as opposed to the day simply not being open right now.
    public static function isWithinBusinessHours(?Carbon $now = null): bool
    {
        $now = ($now ?? now())->copy();

        return BusinessClock::isBusinessDay($now) && $now->lt(BusinessClock::dayEnd($now));
    }

    // A 0-minute freeMinutesToday() result is ambiguous — it means either "actually
    // fully booked" or just "outside business hours right now" (weekend, holiday, or
    // after 5 PM), which is a completely different situation and shouldn't read as
    // the specialist being overloaded. This is the single place that distinction gets
    // resolved into copy, so every dashboard shows the same, correct label.
    public static function freeTimeLabel(User $technician, ?Carbon $now = null): string
    {
        if (Leave::isOnLeave($technician, $now ?? now())) {
            return 'On approved leave';
        }

        if (!self::isWithinBusinessHours($now)) {
            return 'Outside business hours';
        }

        $minutes = self::freeMinutesToday($technician, $now);

        return $minutes > 0
            ? floor($minutes / 60) . 'h ' . ($minutes % 60) . 'm free today'
            : 'Fully booked today';
    }

    // Groups $technician's tickets by scheduled_start's LOCAL calendar date within
    // [$rangeStart, $rangeEnd] inclusive — the Work Hours Calendar's Week/Month
    // heatmap uses this (one call per technician, not per cell). Any ticket with a
    // scheduled_start is included regardless of status: this is a retrospective/
    // prospective workload picture, not a live not-started queue view. A ticket's
    // scheduled_start/scheduled_end are always same-day by construction, so grouping
    // by scheduled_start's date alone is safe — no cross-midnight handling needed.
    //
    // Deliberately uses full calendar-day boundaries (midnight-midnight), NOT
    // BusinessClock::dayStart()/dayEnd() (8am/5pm) — those would wrongly exclude a
    // second overtime ticket stacked after a first one that already ran past 5pm
    // (its own scheduled_start would itself be after 5pm).
    public static function dailyLoadForRange(User $technician, Carbon $rangeStart, Carbon $rangeEnd): array
    {
        $tickets = Tickets::where('assigned_to', $technician->id)
            ->whereNotNull('scheduled_start')
            ->whereBetween('scheduled_start', [
                $rangeStart->copy()->timezone('Asia/Manila')->startOfDay(),
                $rangeEnd->copy()->timezone('Asia/Manila')->endOfDay(),
            ])
            ->get();

        $byDate = [];

        foreach ($tickets as $t) {
            $key = $t->scheduled_start->copy()->timezone('Asia/Manila')->format('Y-m-d');

            $byDate[$key] ??= ['allocated_minutes' => 0, 'ticket_count' => 0, 'has_overtime' => false];
            $byDate[$key]['allocated_minutes'] += self::effortMinutes($t);
            $byDate[$key]['ticket_count']++;
            $byDate[$key]['has_overtime'] = $byDate[$key]['has_overtime'] || (bool) $t->is_overtime;
        }

        foreach ($byDate as &$day) {
            $day['workload_pct'] = round(($day['allocated_minutes'] / self::WORKING_MINUTES_PER_DAY) * 100, 1);
        }
        unset($day);

        return $byDate;
    }

    // ── Core layout algorithm — the single place all scheduling math lives, reused
    // by every commit path and by resequence(), so a preview can never drift from
    // what actually gets persisted.
    //
    // $hasMoreAfter: true when $items is a truncated prefix of a larger queue (used
    // to compute a tentative cursor position ahead of a not-yet-inserted candidate)
    // — it stops the last item in $items from being treated as the true last item of
    // the whole queue, so it correctly rolls to the next business day instead of
    // silently overflowing into same-day overtime when it doesn't fit.
    //
    // $startCursor: when set, $items is laid out starting exactly here instead of
    // initialCursor() — used to continue after a prefix of already-committed items
    // that must NOT be recomputed (see commitAssignment()).
    private static function layOutQueue(
        array $items,
        User $technician,
        Carbon $now,
        ?string $forcedKey = null,
        ?string $forcedDecision = null,
        bool $hasMoreAfter = false,
        ?Carbon $startCursor = null
    ): array {
        $cursor = $startCursor ?? self::initialCursor($technician, $now);
        $dayEnd = BusinessClock::dayEnd($cursor);
        $count = count($items);
        $results = [];

        foreach ($items as $i => $item) {
            $isLast = ($i === $count - 1) && !$hasMoreAfter;
            $isForced = $forcedKey !== null && $item['key'] === $forcedKey;

            if ($isForced && $forcedDecision === 'next_day') {
                $cursor = self::startOfNextAvailableDay($technician, $cursor);
                $dayEnd = BusinessClock::dayEnd($cursor);
                $start = $cursor;
                $end = BusinessClock::projectWithinDay($start, $item['effort']);
                $results[$item['key']] = ['start' => $start, 'end' => $end, 'is_overtime' => $end->gt($dayEnd)];
                $cursor = $end;
                continue;
            }

            if ($isForced && $forcedDecision === 'overtime') {
                $start = $cursor;
                $end = BusinessClock::projectWithinDay($start, $item['effort']);
                $results[$item['key']] = ['start' => $start, 'end' => $end, 'is_overtime' => true];
                $cursor = $end;
                continue;
            }

            $start = $cursor;
            $end = BusinessClock::projectWithinDay($start, $item['effort']);

            if ($end->lte($dayEnd)) {
                $results[$item['key']] = ['start' => $start, 'end' => $end, 'is_overtime' => false];
                $cursor = $end;
                continue;
            }

            if ($isLast) {
                $results[$item['key']] = ['start' => $start, 'end' => $end, 'is_overtime' => true];
                $cursor = $end;
                continue;
            }

            // Doesn't fit today and more items are queued behind it — the whole
            // remaining tail rolls to the next business day; the walk continues there.
            $cursor = self::startOfNextAvailableDay($technician, $cursor);
            $dayEnd = BusinessClock::dayEnd($cursor);
            $start = $cursor;
            $end = BusinessClock::projectWithinDay($start, $item['effort']);
            $results[$item['key']] = ['start' => $start, 'end' => $end, 'is_overtime' => $end->gt($dayEnd)];
            $cursor = $end;
        }

        return $results;
    }

    // Where a technician's queue is actually free to continue after $items — i.e. the
    // real, already-committed scheduled_end of the last item, not a recomputation of
    // it. Recomputing here would be wrong: layOutQueue's isLast handling depends on
    // whether an item is truly the last thing in the queue, and $items is often a
    // deliberately-truncated prefix (see commitAssignment's $before), so re-running
    // it would flip already-decided same-day-overtime placements into next-day
    // rolls purely as an artifact of the truncation — silently relocating a ticket
    // without ever asking the supervisor. Falls back to recomputing only for items
    // that somehow have no stored placement yet (pre-scheduling legacy data).
    private static function cursorAfter(array $items, User $technician, Carbon $now): Carbon
    {
        if (empty($items)) {
            return self::initialCursor($technician, $now);
        }

        $lastEnd = end($items)['currentEnd'] ?? null;

        if ($lastEnd) {
            return $lastEnd->copy();
        }

        $layout = self::layOutQueue($items, $technician, $now, null, null, true);

        return $layout[array_key_last($layout)]['end'];
    }

    // True when $technician can be scheduled on $date's calendar day at all: it's a
    // business day (weekday, not an org-wide holiday) AND the technician isn't on
    // approved leave that day.
    private static function isTechnicianAvailable(User $technician, Carbon $date): bool
    {
        return BusinessClock::isBusinessDay($date) && !Leave::isOnLeave($technician, $date);
    }

    // 8:00 AM on the next day $technician is actually available — loops
    // BusinessClock::startOfNextBusinessDay() (which already skips weekends/holidays)
    // until it also lands on a day the technician isn't on leave. Bounded as a safety
    // net against pathological leave data — should be unreachable with real data.
    private static function startOfNextAvailableDay(User $technician, Carbon $from): Carbon
    {
        $next = BusinessClock::startOfNextBusinessDay($from);
        $guard = 0;

        while (!self::isTechnicianAvailable($technician, $next)) {
            $next = BusinessClock::startOfNextBusinessDay($next);

            if (++$guard > 366) {
                break;
            }
        }

        return $next;
    }

    // Earliest moment a technician's not-started queue can begin: max(now, day start,
    // end of their latest In-Progress ticket today), rolled forward to the next
    // business day if that lands outside business hours entirely, or to 1:00 PM if
    // it lands inside the lunch gap.
    private static function initialCursor(User $technician, Carbon $now): Carbon
    {
        $cursor = $now->copy();
        $anchor = self::inProgressAnchorEnd($technician);

        if ($anchor && $anchor->gt($cursor)) {
            $cursor = $anchor->copy();
        }

        $todayStart = BusinessClock::dayStart($cursor);
        if ($cursor->lt($todayStart)) {
            $cursor = $todayStart;
        }

        if (!self::isTechnicianAvailable($technician, $cursor) || $cursor->gte(BusinessClock::dayEnd($cursor))) {
            $cursor = self::startOfNextAvailableDay($technician, $cursor);
        } elseif (BusinessClock::isDuringLunch($cursor)) {
            $cursor = BusinessClock::afterLunch($cursor);
        }

        return $cursor;
    }

    // In Progress tickets are immutable anchors — they occupy real time but can never
    // be reordered/preempted, so the not-started queue must start after them. Includes
    // the drafting status too — writing up the report still occupies the anchor slot,
    // it hasn't freed up just because the underlying fix is done.
    private static function inProgressAnchorEnd(User $technician): ?Carbon
    {
        $max = Tickets::where('assigned_to', $technician->id)
            ->whereIn('status', [TicketStatus::IN_PROGRESS_SERVICE_REQUEST, TicketStatus::IN_PROGRESS_SERVICE_REPORT])
            ->whereNotNull('scheduled_end')
            ->max('scheduled_end');

        return $max ? Carbon::parse($max) : null;
    }

    private static function latestScheduledEndToday(User $technician, Carbon $now): ?Carbon
    {
        $todayStart = BusinessClock::dayStart($now);
        $todayEnd = BusinessClock::dayEnd($now);

        $max = Tickets::where('assigned_to', $technician->id)
            ->whereIn('status', array_merge(self::NOT_STARTED_STATUSES, [TicketStatus::IN_PROGRESS_SERVICE_REQUEST, TicketStatus::IN_PROGRESS_SERVICE_REPORT]))
            ->whereNotNull('scheduled_end')
            ->whereBetween('scheduled_start', [$todayStart, $todayEnd])
            ->max('scheduled_end');

        return $max ? Carbon::parse($max) : null;
    }

    // A technician's not-yet-started tickets, ordered by priority tier then arrival
    // order within a tier — this ordering IS the priority queue.
    private static function notStartedQueueItems(User $technician, ?string $excludeTicketId = null): array
    {
        $query = Tickets::where('assigned_to', $technician->id)
            ->whereIn('status', self::NOT_STARTED_STATUSES);

        if ($excludeTicketId) {
            $query->where('id', '!=', $excludeTicketId);
        }

        $items = $query->get()->map(function (Tickets $t) {
            return [
                'key' => $t->id,
                'priorityRank' => self::PRIORITY_RANK[$t->ticket_type] ?? self::DEFAULT_PRIORITY_RANK,
                'queuedAt' => $t->queued_at ?? $t->created_at ?? now(),
                'effort' => max(1, self::effortMinutes($t)),
                // Already-committed placement, if it has one — see commitAssignment()/
                // commitDirectSlot() for why this is trusted over recomputing it.
                'currentEnd' => $t->scheduled_end,
            ];
        })->all();

        usort($items, fn (array $a, array $b) => self::comparePriorityOrder($a, $b));

        return $items;
    }

    private static function insertSorted(array $sortedExisting, array $candidate): array
    {
        $merged = $sortedExisting;
        $insertAt = count($merged);

        foreach ($merged as $i => $item) {
            if (self::comparePriorityOrder($candidate, $item) < 0) {
                $insertAt = $i;
                break;
            }
        }

        array_splice($merged, $insertAt, 0, [$candidate]);

        return $merged;
    }

    private static function comparePriorityOrder(array $a, array $b): int
    {
        return $a['priorityRank'] <=> $b['priorityRank']
            ?: $a['queuedAt']->timestamp <=> $b['queuedAt']->timestamp;
    }
}
