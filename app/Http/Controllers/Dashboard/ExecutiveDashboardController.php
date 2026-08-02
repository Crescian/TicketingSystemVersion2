<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Tickets;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExecutiveDashboardController extends Controller
{
    // Roles the "IT Team Status" panel surfaces — the people actually working tickets.
    // Deliberately excludes Manager, since that's the viewer's own peer tier.
    private const IT_TEAM_ROLES = [
        'Helpdesk',
        'IT Support Specialist',
        'Supervisor - Support Specialist',
        'IT Admin',
        'Supervisor - IT Admin',
    ];

    private const ONLINE_WINDOW_MINUTES = 5;

    private const ACTIVE_TICKET_EXCLUDED_STATUSES = ['Closed', 'Cancelled'];

    public function index(Request $request)
    {
        $user = Auth::user()->load('role');
        $greeting = $this->getGreeting();
        $range = $request->get('range', '30D');

        // ── Get all data from shared method
        $data = $this->buildDashboardData($range);

        $itTeamStatus = $this->itTeamStatus();

        $activeTicketsPage = $this->activeTicketsData($request);

        return view('dashboard.executive', array_merge($data, compact(
            'user',
            'greeting',
            'range',
            'itTeamStatus',
        ), [
            'activeTickets' => $activeTicketsPage['tickets'],
            'activeTicketStatuses' => $activeTicketsPage['statuses'],
        ]));
    }

    // ── Date window for the selected range button — drives every date-bounded stat
    // on the dashboard (KPIs, charts, breakdowns, comparison strip).
    private function rangeWindows(string $range): array
    {
        $now = now();
        $end = $now->copy()->endOfDay();

        return match ($range) {
            '7D' => [
                'start' => $now->copy()->subDays(6)->startOfDay(),
                'end' => $end,
                'prevStart' => $now->copy()->subDays(13)->startOfDay(),
                'prevEnd' => $now->copy()->subDays(7)->endOfDay(),
                'bucketUnit' => 'day',
                'label' => 'the last 7 days',
            ],
            '90D' => [
                'start' => $now->copy()->subDays(89)->startOfDay(),
                'end' => $end,
                'prevStart' => $now->copy()->subDays(179)->startOfDay(),
                'prevEnd' => $now->copy()->subDays(90)->endOfDay(),
                'bucketUnit' => 'week',
                'label' => 'the last 90 days',
            ],
            'YTD' => [
                'start' => $now->copy()->startOfYear(),
                'end' => $end,
                'prevStart' => $now->copy()->subYear()->startOfYear(),
                'prevEnd' => $now->copy()->subYear()->endOfDay(),
                'bucketUnit' => 'month',
                'label' => 'year to date',
            ],
            default => [ // 30D
                'start' => $now->copy()->subDays(29)->startOfDay(),
                'end' => $end,
                'prevStart' => $now->copy()->subDays(59)->startOfDay(),
                'prevEnd' => $now->copy()->subDays(30)->endOfDay(),
                'bucketUnit' => 'day',
                'label' => 'the last 30 days',
            ],
        };
    }

    // ── Splits [$start, $end] into day/week/month buckets for chart trends, capped
    // at $cap points (most recent) so a YTD/90D range doesn't render a 365-point line.
    private function generateBuckets(\Illuminate\Support\Carbon $start, \Illuminate\Support\Carbon $end, string $unit, int $cap): array
    {
        $buckets = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $bucketStart = match ($unit) {
                'week' => $cursor->copy()->startOfWeek(),
                'month' => $cursor->copy()->startOfMonth(),
                default => $cursor->copy()->startOfDay(),
            };
            $bucketEnd = (match ($unit) {
                'week' => $cursor->copy()->endOfWeek(),
                'month' => $cursor->copy()->endOfMonth(),
                default => $cursor->copy()->endOfDay(),
            })->min($end);

            $buckets[] = [
                'start' => $bucketStart,
                'end' => $bucketEnd,
                'label' => $unit === 'month' ? $bucketStart->format('M') : $bucketStart->format('M d'),
            ];

            $cursor = match ($unit) {
                'week' => $bucketStart->copy()->addWeek(),
                'month' => $bucketStart->copy()->addMonthNoOverflow(),
                default => $bucketStart->copy()->addDay(),
            };
        }

        return count($buckets) > $cap ? array_slice($buckets, -$cap) : $buckets;
    }

    // ── Shared data logic extracted to avoid duplication
    private function buildDashboardData(string $range = '30D'): array
    {
        $window = $this->rangeWindows($range);
        $start = $window['start'];
        $end = $window['end'];
        $lastStart = $window['prevStart'];
        $lastEnd = $window['prevEnd'];
        $bucketUnit = $window['bucketUnit'];
        $rangeLabel = $window['label'];

        $curPeriodLabel = $start->format('M d') . '–' . $end->format('M d, Y');
        $prevPeriodLabel = $lastStart->format('M d') . '–' . $lastEnd->format('M d, Y');

        $totalTickets = DB::table('tickets')->whereBetween('created_at', [$start, $end])->count();
        $resolved = DB::table('tickets')->whereBetween('created_at', [$start, $end])->where('status', 'Closed')->count();
        $escalations = DB::table('tickets')->whereBetween('created_at', [$start, $end])->where('status', 'Escalated')->count();
        $avgResolutionTime = DB::table('tickets')
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'Closed')
            ->whereNotNull('resolved_at')->whereNotNull('started_at')
            ->selectRaw("ROUND(AVG(EXTRACT(EPOCH FROM (resolved_at - started_at)) / 3600)::numeric, 1) as avg_hours")
            ->value('avg_hours');

        $lastTotalTickets = DB::table('tickets')->whereBetween('created_at', [$lastStart, $lastEnd])->count();
        $lastResolved = DB::table('tickets')->whereBetween('created_at', [$lastStart, $lastEnd])->where('status', 'Closed')->count();
        $lastEscalations = DB::table('tickets')->whereBetween('created_at', [$lastStart, $lastEnd])->where('status', 'Escalated')->count();
        $lastAvgTime = DB::table('tickets')
            ->whereBetween('created_at', [$lastStart, $lastEnd])
            ->where('status', 'Closed')
            ->whereNotNull('resolved_at')->whereNotNull('started_at')
            ->selectRaw("ROUND(AVG(EXTRACT(EPOCH FROM (resolved_at - started_at)) / 3600)::numeric, 1) as avg_hours")
            ->value('avg_hours');

        $slaTotal = max(1, DB::table('tickets')->whereBetween('created_at', [$start, $end])->count());
        $slaBreach = DB::table('escalations')->whereBetween('escalated_at', [$start, $end])->count();
        $slaPercent = round((($slaTotal - $slaBreach) / $slaTotal) * 100);
        $avgRating = DB::table('ticket_feed_backs')->whereBetween('created_at', [$start, $end])->avg('rating') ?? 0;

        // Volume trend — bucketed by day/week/month depending on range so long ranges stay readable.
        // Day buckets are only used for 7D/30D (≤30 points), so they don't need the 13-point cap
        // that keeps the 90D/YTD week/month trends from getting cramped.
        $volumeCap = $bucketUnit === 'day' ? 31 : 13;
        $volumeBuckets = $this->generateBuckets($start, $end, $bucketUnit, $volumeCap);
        $volumeDays = $volumeOpened = $volumeResolved = [];
        foreach ($volumeBuckets as $b) {
            $volumeDays[] = $b['label'];
            $volumeOpened[] = DB::table('tickets')->whereBetween('created_at', [$b['start'], $b['end']])->count();
            $volumeResolved[] = DB::table('tickets')->whereBetween('resolved_at', [$b['start'], $b['end']])->where('status', 'Closed')->count();
        }

        // SLA by priority
        $slaByPriority = [];
        foreach (['Critical', 'High', 'Medium', 'Low'] as $priority) {
            $total = max(1, DB::table('tickets')->whereBetween('created_at', [$start, $end])->where('ticket_type', $priority)->count());
            $breached = DB::table('escalations')
                ->join('tickets', 'tickets.id', '=', 'escalations.ticket_id')
                ->whereBetween('escalations.escalated_at', [$start, $end])
                ->where('tickets.ticket_type', $priority)->count();
            $slaByPriority[$priority] = round((($total - $breached) / $total) * 100);
        }

        // By department
        $byDepartment = DB::table('tickets')
            ->join('users', 'users.id', '=', 'tickets.users_id')
            ->join('departments', 'departments.id', '=', 'users.department_id')
            ->whereBetween('tickets.created_at', [$start, $end])
            ->selectRaw('departments.department_name, COUNT(*) as total')
            ->groupBy('departments.department_name')
            ->orderByDesc('total')
            ->get();

        // By category
        $byCategory = DB::table('tickets')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('request_category, COUNT(*) as total')
            ->groupBy('request_category')->orderByDesc('total')->get();

        // Resolution time by category
        $resTimeByCategory = DB::table('tickets')
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'Closed')
            ->whereNotNull('resolved_at')->whereNotNull('started_at')
            ->selectRaw("request_category, ROUND(AVG(EXTRACT(EPOCH FROM (resolved_at - started_at)) / 3600)::numeric, 1) as avg_hours")
            ->groupBy('request_category')->get();

        // Weekly data — most recent weekly buckets within the selected range (capped at 13)
        $weekBuckets = $bucketUnit === 'week' ? $volumeBuckets : $this->generateBuckets($start, $end, 'week', 13);
        $weeklyData = [];
        foreach ($weekBuckets as $b) {
            $weeklyData[] = [
                'label' => $b['start']->format('M d') . '–' . $b['end']->format('d'),
                'resolved' => DB::table('tickets')->whereBetween('created_at', [$b['start'], $b['end']])->where('status', 'Closed')->count(),
                'inProgress' => DB::table('tickets')->whereBetween('created_at', [$b['start'], $b['end']])->where('status', 'In Progress')->count(),
                'escalated' => DB::table('tickets')->whereBetween('created_at', [$b['start'], $b['end']])->where('status', 'Escalated')->count(),
            ];
        }

        // Leaderboard
        $leaderboard = DB::table('tickets')
            ->join('users', 'users.id', '=', 'tickets.assigned_to')
            ->leftJoin('ticket_feed_backs', 'ticket_feed_backs.ticket_id', '=', 'tickets.id')
            ->whereBetween('tickets.created_at', [$start, $end])
            ->where('tickets.status', 'Closed')
            ->whereNotNull('tickets.assigned_to')
            ->selectRaw("
                users.id, users.name, users.position,
                COUNT(tickets.id) as resolved_count,
                ROUND(AVG(EXTRACT(EPOCH FROM (tickets.resolved_at - tickets.started_at)) / 3600)::numeric, 1) as avg_hours,
                ROUND(AVG(ticket_feed_backs.rating)::numeric, 1) as avg_rating
            ")
            ->groupBy('users.id', 'users.name', 'users.position')
            ->orderByDesc('resolved_count')
            ->limit(5)
            ->get();

        // Open escalations — always current, regardless of the selected date range
        $openEscalations = DB::table('escalations')
            ->join('tickets', 'tickets.id', '=', 'escalations.ticket_id')
            ->join('users as reporter', 'reporter.id', '=', 'tickets.users_id')
            ->join('departments', 'departments.id', '=', 'reporter.department_id')
            ->leftJoin('users as tech', 'tech.id', '=', 'escalations.previous_tech_id')
            ->leftJoin('users as admin', 'admin.id', '=', 'escalations.reassigned_to')
            ->whereNull('escalations.resolved_at')
            ->selectRaw("
                tickets.subject, tickets.concern, tickets.status,
                departments.department_name, escalations.reason,
                escalations.escalated_at,
                tech.name as prev_tech,
                admin.name as reassigned_to_name
            ")
            ->orderBy('escalations.escalated_at', 'asc')
            ->get();

        // CSAT
        $totalFeedback = DB::table('ticket_feed_backs')->whereBetween('created_at', [$start, $end])->count();
        $csatBreakdown = [];
        foreach ([5, 4, 3, 2, 1] as $star) {
            $count = DB::table('ticket_feed_backs')
                ->whereBetween('created_at', [$start, $end])
                ->where('rating', $star)->count();
            $csatBreakdown[$star] = [
                'count' => $count,
                'percent' => $totalFeedback > 0 ? round(($count / $totalFeedback) * 100) : 0,
            ];
        }
        // ── Calculate changes for KPI trends
        $ticketChange = $lastTotalTickets > 0
            ? round((($totalTickets - $lastTotalTickets) / $lastTotalTickets) * 100)
            : 0;

        $resolvedChange = $lastResolved > 0
            ? round((($resolved - $lastResolved) / $lastResolved) * 100)
            : 0;

        $avgTimeChange = $lastAvgTime > 0
            ? round((($avgResolutionTime - $lastAvgTime) / $lastAvgTime) * 100)
            : 0;

        $escChange = $escalations - $lastEscalations;

        $resolutionRate = $totalTickets > 0
            ? round(($resolved / $totalTickets) * 100, 1)
            : 0;
        return compact(
            'range',
            'totalTickets',
            'resolved',
            'escalations',
            'avgResolutionTime',
            'lastTotalTickets',
            'lastResolved',
            'lastEscalations',
            'lastAvgTime',
            'lastStart',
            'lastEnd',
            'slaPercent',
            'avgRating',
            'slaBreach',
            'volumeDays',
            'volumeOpened',
            'volumeResolved',
            'slaByPriority',
            'byDepartment',
            'byCategory',
            'resTimeByCategory',
            'leaderboard',
            'openEscalations',
            'weeklyData',
            'totalFeedback',
            'csatBreakdown',
            'ticketChange',
            'resolvedChange',
            'avgTimeChange',
            'escChange',
            'resolutionRate',
            'curPeriodLabel',
            'prevPeriodLabel',
            'rangeLabel'
        );
    }

    // ── JSON endpoint for real-time updates (30s poll + range-button switch)
    public function data(Request $request)
    {
        $range = $request->get('range', '30D');
        $data = $this->buildDashboardData($range);
        $data['itTeamStatus'] = $this->itTeamStatus();

        return response()->json($data);
    }

    // ── Users online right now (session activity within the last 5 minutes) —
    // same mechanism as Admin > User Management's presence panel.
    private function onlineUserIds(): \Illuminate\Support\Collection
    {
        return DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', now()->subMinutes(self::ONLINE_WINDOW_MINUTES)->timestamp)
            ->distinct()
            ->pluck('user_id');
    }

    // ── "IT Team Status" panel: every active support-tier member with presence,
    // current workload, and a free/busy/full availability read — mirrors the
    // technician availability logic in SupervisorDashboardController::supportIndex().
    private function itTeamStatus(): \Illuminate\Support\Collection
    {
        $onlineIds = $this->onlineUserIds();

        return User::with('role')
            ->whereHas('role', fn($q) => $q->whereIn('role_name', self::IT_TEAM_ROLES))
            ->where('active', true)
            ->withCount(['assignedTickets as active_tickets' => fn($q) =>
                $q->whereNotIn('status', self::ACTIVE_TICKET_EXCLUDED_STATUSES)
            ])
            ->orderBy('name')
            ->get()
            ->map(function ($member) use ($onlineIds) {
                $member->online = $onlineIds->contains($member->id);
                $member->availability = match (true) {
                    $member->active_tickets === 0 => 'free',
                    $member->active_tickets <= 2 => 'busy',
                    default => 'full',
                };
                return $member;
            });
    }

    // ── Shared query for the "All Active Tickets" panel — org-wide, excludes
    // Closed/Cancelled, filterable by free-text search (ticket #, subject, requester
    // name) plus exact status/priority. Used by both the initial SSR page load and
    // the AJAX endpoint below so the two never drift.
    private function activeTicketsData(Request $request): array
    {
        // Strip a leading "#" — ticket numbers are always displayed with one (e.g. "#LGICT-26-0014")
        // but the stored ticket_number column never includes it, so searching the displayed value
        // verbatim would otherwise match nothing.
        $search = ltrim(trim((string) $request->get('search', '')), '#');
        $status = $request->get('status', '');
        $priority = $request->get('priority', '');

        $query = Tickets::with('user')
            ->whereNotIn('status', self::ACTIVE_TICKET_EXCLUDED_STATUSES);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'ilike', "%{$search}%")
                    ->orWhere('subject', 'ilike', "%{$search}%")
                    ->orWhereHas('user', fn($u) => $u->where('name', 'ilike', "%{$search}%"));
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($priority !== '') {
            $query->where('ticket_type', $priority);
        }

        $tickets = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        $statuses = Tickets::whereNotIn('status', self::ACTIVE_TICKET_EXCLUDED_STATUSES)
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        return ['tickets' => $tickets, 'statuses' => $statuses];
    }

    // ── AJAX endpoint backing the "All Active Tickets" search/filter table
    public function activeTickets(Request $request)
    {
        $data = $this->activeTicketsData($request);
        $tickets = $data['tickets'];

        return response()->json([
            'tickets' => $tickets->getCollection()->map(fn($t) => [
                'id' => $t->id,
                'ticket_number' => $t->ticket_number,
                'subject' => $t->subject,
                'requester_name' => $t->user->name ?? 'Unknown',
                'status' => $t->status,
                'priority' => $t->ticket_type,
                'created_at' => optional($t->created_at)->toISOString(),
            ])->values(),
            'pagination' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'total' => $tickets->total(),
            ],
            'statuses' => $data['statuses'],
        ]);
    }

    private function getGreeting(): string
    {
        $hour = now()->hour;
        return match (true) {
            $hour >= 5 && $hour < 12 => 'Good Morning',
            $hour >= 12 && $hour < 18 => 'Good Afternoon',
            $hour >= 18 && $hour < 22 => 'Good Evening',
            default => 'Good Night',
        };
    }
}
