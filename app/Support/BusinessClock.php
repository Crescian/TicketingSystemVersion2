<?php

namespace App\Support;

use App\Models\Holiday;
use Carbon\Carbon;

// Converts an SLA target expressed in "business minutes" (minutes that only tick
// during working hours) into an actual deadline timestamp — so "1 Business Day"
// filed at 4:50pm Friday lands Monday afternoon instead of breaching over the
// weekend. Workload Class targets (App\Models\WorkloadClass) are pre-computed
// against these same hours, so seeding and deadline math always agree.
//
// Also backs App\Services\TicketScheduler's time-slot queue, which needs the
// business-day boundaries and holiday calendar to lay out scheduled_start/end.
class BusinessClock
{
    // Business hours are evaluated in the office's local time regardless of the
    // app's storage timezone (config('app.timezone') is UTC) — SLA targets are a
    // local wall-clock concept, not a server-clock one.
    private const TIMEZONE = 'Asia/Manila';

    private const START_HOUR = 8;  // 8:00 AM
    private const END_HOUR = 17;   // 5:00 PM  → 9-hour business day, minus lunch below

    // Lunch is a paused gap inside the business day, not working time — a task that
    // straddles it (e.g. starts 11:00 AM, needs 90 minutes) continues at 1:00 PM
    // instead of powering through noon-1pm.
    private const LUNCH_START_HOUR = 12; // 12:00 PM
    private const LUNCH_END_HOUR = 13;   // 1:00 PM

    // Carbon dayOfWeek: 0 = Sunday ... 6 = Saturday
    private const BUSINESS_DAYS = [1, 2, 3, 4, 5];

    // Reminder/alert triggers (sla:check, tickets:remind-stale, ...) get a 30-minute
    // grace period past END_HOUR before going quiet — staff are often still wrapping
    // up at 5:00 PM sharp, so cutting notifications off exactly on the hour would
    // silence things prematurely. This window is deliberately separate from
    // START_HOUR/END_HOUR, which stay the source of truth for SLA/scheduling math.
    private const NOTIFICATION_END_HOUR = 17;
    private const NOTIFICATION_END_MINUTE = 30;

    public static function addBusinessMinutes(Carbon $start, int $minutes): Carbon
    {
        $originalTz = $start->getTimezone();
        $cursor = self::rollForwardIntoBusinessWindow($start->copy()->setTimezone(self::TIMEZONE));

        $remaining = $minutes;

        while ($remaining > 0) {
            $segmentEnd = self::segmentEnd($cursor);
            $minutesLeftInSegment = (int) $cursor->diffInMinutes($segmentEnd);

            if ($remaining <= $minutesLeftInSegment) {
                $cursor = $cursor->addMinutes($remaining);
                $remaining = 0;
            } else {
                $remaining -= $minutesLeftInSegment;
                $cursor = self::rollForwardIntoBusinessWindow($segmentEnd);
            }
        }

        return $cursor->setTimezone($originalTz);
    }

    // Adds $minutes to $start skipping the lunch gap, like addBusinessMinutes, but
    // never rolls onto a different calendar day — it's allowed to run past END_HOUR
    // (into overtime) instead. Used by App\Services\TicketScheduler, which decides
    // for itself what happens when a projected end lands after 5:00 PM.
    public static function projectWithinDay(Carbon $start, int $minutes): Carbon
    {
        $originalTz = $start->getTimezone();
        $cursor = $start->copy()->setTimezone(self::TIMEZONE);

        if (self::isDuringLunchMoment($cursor)) {
            $cursor->setTime(self::LUNCH_END_HOUR, 0, 0);
        } elseif ($cursor->hour < self::LUNCH_START_HOUR) {
            $lunchStart = $cursor->copy()->setTime(self::LUNCH_START_HOUR, 0, 0);
            $minutesBeforeLunch = (int) $cursor->diffInMinutes($lunchStart);

            if ($minutes > $minutesBeforeLunch) {
                $minutes -= $minutesBeforeLunch;
                $cursor = $lunchStart->setTime(self::LUNCH_END_HOUR, 0, 0);
            }
        }

        return $cursor->addMinutes($minutes)->setTimezone($originalTz);
    }

    // Pushes a moment that falls outside business hours/days, or inside the lunch
    // gap, forward to the start of the next open window. A moment already inside
    // one is left untouched.
    private static function rollForwardIntoBusinessWindow(Carbon $moment): Carbon
    {
        $moment = $moment->copy();

        while (true) {
            if (!in_array($moment->dayOfWeek, self::BUSINESS_DAYS, true) || self::isHolidayDate($moment)) {
                $moment = $moment->addDay()->setTime(self::START_HOUR, 0, 0);
                continue;
            }
            if ($moment->hour < self::START_HOUR) {
                $moment = $moment->setTime(self::START_HOUR, 0, 0);
                continue;
            }
            if (self::isDuringLunchMoment($moment)) {
                $moment = $moment->setTime(self::LUNCH_END_HOUR, 0, 0);
                continue;
            }
            if ($moment->hour >= self::END_HOUR) {
                $moment = $moment->addDay()->setTime(self::START_HOUR, 0, 0);
                continue;
            }
            break;
        }

        return $moment;
    }

    // The end of the current open segment: noon if $moment is in the morning
    // segment, otherwise end-of-day. Assumes $moment has already been rolled into
    // a valid open window (never inside the lunch gap itself).
    private static function segmentEnd(Carbon $moment): Carbon
    {
        return $moment->hour < self::LUNCH_START_HOUR
            ? $moment->copy()->setTime(self::LUNCH_START_HOUR, 0, 0)
            : $moment->copy()->setTime(self::END_HOUR, 0, 0);
    }

    private static function isDuringLunchMoment(Carbon $moment): bool
    {
        return $moment->hour >= self::LUNCH_START_HOUR && $moment->hour < self::LUNCH_END_HOUR;
    }

    // Public check for callers that need to know if a moment falls in the lunch
    // gap without pulling in the rest of the business-window logic.
    public static function isDuringLunch(Carbon $moment): bool
    {
        return self::isDuringLunchMoment($moment->copy()->setTimezone(self::TIMEZONE));
    }

    // 1:00 PM on $date's calendar day, returned in $date's original timezone —
    // mirrors dayStart()/dayEnd() for the lunch boundary.
    public static function afterLunch(Carbon $date): Carbon
    {
        $originalTz = $date->getTimezone();

        return $date->copy()->setTimezone(self::TIMEZONE)
            ->setTime(self::LUNCH_END_HOUR, 0, 0)
            ->setTimezone($originalTz);
    }

    // Working minutes between two same-day moments, excluding any overlap with the
    // lunch gap — a plain diffInMinutes() would count noon-1pm as available time it
    // isn't. Used wherever a raw duration (not a scheduled placement) needs to
    // reflect actual working time, e.g. "free minutes today" dashboard figures.
    public static function businessMinutesBetween(Carbon $start, Carbon $end): int
    {
        $start = $start->copy()->setTimezone(self::TIMEZONE);
        $end = $end->copy()->setTimezone(self::TIMEZONE);

        $lunchStart = $start->copy()->setTime(self::LUNCH_START_HOUR, 0, 0);
        $lunchEnd = $start->copy()->setTime(self::LUNCH_END_HOUR, 0, 0);

        $overlapStart = $start->gt($lunchStart) ? $start : $lunchStart;
        $overlapEnd = $end->lt($lunchEnd) ? $end : $lunchEnd;

        $lunchOverlap = $overlapEnd->gt($overlapStart) ? $overlapStart->diffInMinutes($overlapEnd) : 0;

        return max(0, (int) $start->diffInMinutes($end) - $lunchOverlap);
    }

    // Per-request memoized holiday lookup, keyed by local calendar date — avoids
    // re-querying for every day walked while laying out a specialist's queue.
    private static array $holidayCache = [];

    private static function isHolidayDate(Carbon $moment): bool
    {
        $key = $moment->copy()->setTimezone(self::TIMEZONE)->format('Y-m-d');

        return self::$holidayCache[$key] ??= Holiday::where('date', $key)
            ->where('is_active', true)
            ->exists();
    }

    // Public wrapper for isHolidayDate — for callers outside this class that need
    // a plain holiday check without the rest of the business-window logic.
    public static function isHoliday(Carbon $date): bool
    {
        return self::isHolidayDate($date);
    }

    // True if $date falls on a weekday that is not a holiday. Does not consider
    // time-of-day — a moment at 11:00 PM on a Tuesday is still a "business day".
    public static function isBusinessDay(Carbon $date): bool
    {
        $local = $date->copy()->setTimezone(self::TIMEZONE);

        return in_array($local->dayOfWeek, self::BUSINESS_DAYS, true) && !self::isHolidayDate($local);
    }

    // Gate for scheduled reminder/alert triggers: business day, 8:00 AM through
    // 5:30 PM (the 30-minute grace period past END_HOUR — see NOTIFICATION_END_HOUR).
    // Not used for SLA deadline math, only for deciding whether an automated
    // notification is allowed to fire right now.
    public static function isWithinNotificationWindow(Carbon $moment): bool
    {
        $local = $moment->copy()->setTimezone(self::TIMEZONE);

        if (!self::isBusinessDay($local)) {
            return false;
        }

        $windowStart = $local->copy()->setTime(self::START_HOUR, 0, 0);
        $windowEnd = $local->copy()->setTime(self::NOTIFICATION_END_HOUR, self::NOTIFICATION_END_MINUTE, 0);

        return $local->gte($windowStart) && $local->lte($windowEnd);
    }

    // 8:00 AM on $date's calendar day, returned in $date's original timezone.
    // Does not roll forward past weekends/holidays — callers that need a valid
    // business-day boundary should combine this with isBusinessDay() or use
    // startOfNextBusinessDay() instead.
    public static function dayStart(Carbon $date): Carbon
    {
        $originalTz = $date->getTimezone();

        return $date->copy()->setTimezone(self::TIMEZONE)
            ->setTime(self::START_HOUR, 0, 0)
            ->setTimezone($originalTz);
    }

    // 5:00 PM on $date's calendar day, returned in $date's original timezone.
    public static function dayEnd(Carbon $date): Carbon
    {
        $originalTz = $date->getTimezone();

        return $date->copy()->setTimezone(self::TIMEZONE)
            ->setTime(self::END_HOUR, 0, 0)
            ->setTimezone($originalTz);
    }

    // 8:00 AM on the next business day after $from's calendar day — skips
    // weekends and holidays. Used by TicketScheduler for the "Next Working Day"
    // deferral and for rolling a queue tail forward when a day fills up.
    public static function startOfNextBusinessDay(Carbon $from): Carbon
    {
        $originalTz = $from->getTimezone();
        $next = $from->copy()->setTimezone(self::TIMEZONE)->addDay()->setTime(self::START_HOUR, 0, 0);

        return self::rollForwardIntoBusinessWindow($next)->setTimezone($originalTz);
    }
}
