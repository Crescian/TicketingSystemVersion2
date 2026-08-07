<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\Tickets;
use App\Models\User;
use App\Services\TicketScheduler;
use App\Support\BusinessClock;
use Carbon\Carbon;
use Illuminate\Http\Request;

class WorkHoursCalendarController extends Controller
{
    public function index(Request $request)
    {
        $view = in_array($request->get('view'), ['day', 'week', 'month'], true)
            ? $request->get('view')
            : 'day';

        $date = $request->filled('date')
            ? Carbon::parse($request->get('date'), 'Asia/Manila')->startOfDay()
            : now('Asia/Manila')->startOfDay();

        // A specialist viewing their own calendar can only ever see themselves —
        // ignore any technician_id tampering in the query string rather than letting
        // them browse a colleague's schedule.
        $isOwnCalendar = $request->user()->hasRole('IT Support Specialist');

        if ($isOwnCalendar) {
            $technicians = collect([$request->user()]);
            $allTechnicians = $technicians;
        } else {
            $technicianQuery = User::whereHas('role', fn ($q) => $q->where('role_name', 'IT Support Specialist'))
                ->orderBy('name');

            if ($request->filled('technician_id')) {
                $technicianQuery->where('id', $request->get('technician_id'));
            }

            $technicians = $technicianQuery->get();
            $allTechnicians = $request->filled('technician_id')
                ? User::whereHas('role', fn ($q) => $q->where('role_name', 'IT Support Specialist'))->orderBy('name')->get()
                : $technicians;
        }

        $data = $view === 'day'
            ? $this->buildDayView($technicians, $date)
            : $this->buildHeatmapView($technicians, $date, $view);

        $nav = $this->buildNav($view, $date);
        $routeName = $request->route()->getName();

        return view('dashboard.support.calendar', array_merge(
            compact('view', 'date', 'technicians', 'allTechnicians', 'nav', 'isOwnCalendar', 'routeName'),
            $data
        ));
    }

    private function buildDayView($technicians, Carbon $date): array
    {
        // Calendar-day boundaries for the ticket query (not BusinessClock's 8am/5pm
        // boundaries) — a stacked overtime ticket can itself start after 5pm, which
        // dayStart()/dayEnd() would wrongly exclude. See TicketScheduler::dailyLoadForRange().
        $calStart = $date->copy()->startOfDay();
        $calEnd = $date->copy()->endOfDay();
        $dayStart = BusinessClock::dayStart($date);
        $dayEnd = BusinessClock::dayEnd($date);
        $lunchStart = BusinessClock::afterLunch($date)->copy()->subHour();

        $rows = $technicians->map(function (User $tech) use ($date, $calStart, $calEnd, $dayStart, $dayEnd, $lunchStart) {
            $tickets = Tickets::where('assigned_to', $tech->id)
                ->whereNotNull('scheduled_start')
                ->whereBetween('scheduled_start', [$calStart, $calEnd])
                ->orderBy('scheduled_start')
                ->get();

            // Axis extends past 5pm to fit overtime, never shrinks below 8am-5pm.
            $latestEnd = $tickets->max('scheduled_end');
            $axisStart = $dayStart;
            $axisEnd = ($latestEnd && $latestEnd->gt($dayEnd)) ? $latestEnd->copy() : $dayEnd->copy();
            $axisMinutes = max(1, $axisStart->diffInMinutes($axisEnd));

            $blocks = $tickets->map(function (Tickets $t) use ($axisStart, $axisMinutes) {
                $effort = TicketScheduler::effortMinutes($t);
                $startOffset = max(0, $axisStart->diffInMinutes($t->scheduled_start));

                return [
                    'ticket' => $t,
                    'left_pct' => round(($startOffset / $axisMinutes) * 100, 2),
                    'width_pct' => max(0.5, round(($effort / $axisMinutes) * 100, 2)),
                    'is_overtime' => (bool) $t->is_overtime,
                ];
            });

            $onLeave = Leave::isOnLeave($tech, $date);
            $allocated = (int) $tickets->sum(fn (Tickets $t) => TicketScheduler::effortMinutes($t));
            $overtimeMinutes = (int) $tickets->where('is_overtime', true)
                ->sum(fn (Tickets $t) => TicketScheduler::effortMinutes($t));

            return [
                'technician' => $tech,
                'on_leave' => $onLeave,
                'axis_start' => $axisStart,
                'axis_end' => $axisEnd,
                'lunch_left_pct' => round($axisStart->diffInMinutes($lunchStart) / $axisMinutes * 100, 2),
                'lunch_width_pct' => round(60 / $axisMinutes * 100, 2),
                'blocks' => $blocks,
                'stats' => [
                    'working_minutes' => TicketScheduler::WORKING_MINUTES_PER_DAY,
                    'allocated_minutes' => $allocated,
                    // A capacity figure for the DISPLAYED date, not freeMinutesToday()
                    // — that method answers "how much is left from right now" and is
                    // only meaningful for today; reusing it for an arbitrary past/future
                    // date would incorrectly pull in initialCursor()'s live-anchor logic.
                    'remaining_minutes' => $onLeave ? 0 : max(0, TicketScheduler::WORKING_MINUTES_PER_DAY - $allocated),
                    'ticket_count' => $tickets->count(),
                    'overtime_minutes' => $overtimeMinutes,
                    'workload_pct' => round(($allocated / TicketScheduler::WORKING_MINUTES_PER_DAY) * 100, 1),
                ],
            ];
        });

        return ['rows' => $rows];
    }

    private function buildHeatmapView($technicians, Carbon $date, string $view): array
    {
        if ($view === 'week') {
            $rangeStart = $date->copy()->startOfWeek(Carbon::MONDAY);
            $rangeEnd = $rangeStart->copy()->addDays(4); // Friday — not endOfWeek(Carbon::FRIDAY)
        } else {
            $rangeStart = $date->copy()->startOfMonth();
            $rangeEnd = $date->copy()->endOfMonth();
        }

        $columns = collect();
        for ($d = $rangeStart->copy(); $d->lte($rangeEnd); $d->addDay()) {
            if (BusinessClock::isBusinessDay($d)) {
                $columns->push($d->copy());
            }
        }

        $rows = $technicians->map(function (User $tech) use ($rangeStart, $rangeEnd, $columns) {
            $byDate = TicketScheduler::dailyLoadForRange($tech, $rangeStart, $rangeEnd);

            $cells = $columns->map(function (Carbon $day) use ($byDate, $tech) {
                $key = $day->format('Y-m-d');
                $onLeave = Leave::isOnLeave($tech, $day);
                $cell = $byDate[$key] ?? [
                    'allocated_minutes' => 0,
                    'ticket_count' => 0,
                    'has_overtime' => false,
                    'workload_pct' => 0.0,
                ];

                $cell['date'] = $day;
                $cell['on_leave'] = $onLeave;
                $cell['level'] = $onLeave
                    ? 'leave'
                    : match (true) {
                        $cell['has_overtime'] || $cell['workload_pct'] >= 100 => 'red',
                        $cell['workload_pct'] >= 60 => 'yellow',
                        default => 'green',
                    };

                return $cell;
            });

            return ['technician' => $tech, 'cells' => $cells];
        });

        return ['columns' => $columns, 'rows' => $rows];
    }

    private function buildNav(string $view, Carbon $date): array
    {
        $prev = match ($view) {
            'day' => $date->copy()->subDay(),
            'week' => $date->copy()->subWeek(),
            default => $date->copy()->subMonthNoOverflow(),
        };

        $next = match ($view) {
            'day' => $date->copy()->addDay(),
            'week' => $date->copy()->addWeek(),
            default => $date->copy()->addMonthNoOverflow(),
        };

        return [
            'prev' => $prev->format('Y-m-d'),
            'next' => $next->format('Y-m-d'),
            'today' => now('Asia/Manila')->format('Y-m-d'),
        ];
    }
}
