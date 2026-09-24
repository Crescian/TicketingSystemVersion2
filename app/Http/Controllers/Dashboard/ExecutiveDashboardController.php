<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Tickets;
use App\Models\User;
use App\Support\TicketStatus;
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

    private const ACTIVE_TICKET_EXCLUDED_STATUSES = [TicketStatus::CLOSED, TicketStatus::CANCELLED];

    // ── Lifecycle order for the "Status Aging" panel — TicketStatus::PIPELINE plus
    // the statuses it deliberately leaves out (FOR_CLASSIFICATION, a Helpdesk-only
    // sub-step of FOR_ACKNOWLEDGMENT; ON_HOLD and CANCELLED, both off-pipeline
    // detours — see TicketStatus's docblock) placed where they actually occur/end
    // up in a ticket's life, so the table reads start-to-end instead of by count.
    private const STATUS_ORDER = [
        TicketStatus::FOR_ACKNOWLEDGMENT,
        TicketStatus::FOR_CLASSIFICATION,
        TicketStatus::CLASSIFIED,
        TicketStatus::ASSIGNED,
        TicketStatus::IN_PROGRESS_SERVICE_REQUEST,
        TicketStatus::CLOSED_SERVICE_REQUEST,
        TicketStatus::IN_PROGRESS_SERVICE_REPORT,
        TicketStatus::DONE_SERVICE_REPORT,
        TicketStatus::REPORT_FOR_REVIEW,
        TicketStatus::APPROVED_SERVICE_REPORT,
        TicketStatus::ESCALATED,
        TicketStatus::REQUESTOR_CONFIRMATION,
        TicketStatus::CLOSED,
        TicketStatus::ON_HOLD,
        TicketStatus::CANCELLED,
    ];

    // ── Statuses where the next action is the assigned IT member's own — the only ones
    // "IT Team Aging" counts against a member. Everything else is waiting on someone else:
    // a role queue (pending_role set), the supervisor (Report For Review / Approved), the
    // requester (Requestor Confirmation / On Hold), or nobody (Closed / Cancelled).
    private const MEMBER_ACTION_STATUSES = [
        TicketStatus::ASSIGNED,
        TicketStatus::IN_PROGRESS_SERVICE_REQUEST,
        TicketStatus::CLOSED_SERVICE_REQUEST,
        TicketStatus::IN_PROGRESS_SERVICE_REPORT,
        TicketStatus::DONE_SERVICE_REPORT,
    ];

    // ── Aging report buckets, in days since tickets.created_at. Upper bound is inclusive;
    // the last bucket (90+) has no upper bound.
    private const AGING_BUCKETS = [
        '0-7' => [0, 7],
        '8-14' => [8, 14],
        '15-30' => [15, 30],
        '31-60' => [31, 60],
        '61-90' => [61, 90],
        '90+' => [91, PHP_INT_MAX],
    ];

    public function index(Request $request)
    {
        $user = Auth::user()->load('role');
        $greeting = $this->getGreeting();
        $range = $request->get('range', 'YTD');

        // ── Get all data from shared method
        $data = $this->buildDashboardData($range);

        $itTeamStatus = $this->itTeamStatus();

        return view('dashboard.executive', array_merge($data, compact(
            'user',
            'greeting',
            'range',
            'itTeamStatus',
        )));
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
    private function buildDashboardData(string $range = 'YTD'): array
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
        // Escalation events, not current ticket status: a ticket escalated during the window and
        // since resolved no longer has status 'Escalated', so counting live status undercounted this
        // KPI against the "SLA Breaches" figure above (and slaByPriority below), which both read the
        // escalations log instead. Match that so the two numbers on the page can't disagree.
        $escalations = DB::table('escalations')->whereBetween('escalated_at', [$start, $end])->count();
        $avgResolutionTime = DB::table('tickets')
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'Closed')
            ->whereNotNull('resolved_at')->whereNotNull('started_at')
            ->selectRaw("ROUND(AVG(EXTRACT(EPOCH FROM (resolved_at - started_at)) / 3600)::numeric, 1) as avg_hours")
            ->value('avg_hours');

        $lastTotalTickets = DB::table('tickets')->whereBetween('created_at', [$lastStart, $lastEnd])->count();
        $lastResolved = DB::table('tickets')->whereBetween('created_at', [$lastStart, $lastEnd])->where('status', 'Closed')->count();
        $lastEscalations = DB::table('escalations')->whereBetween('escalated_at', [$lastStart, $lastEnd])->count();
        $lastAvgTime = DB::table('tickets')
            ->whereBetween('created_at', [$lastStart, $lastEnd])
            ->where('status', 'Closed')
            ->whereNotNull('resolved_at')->whereNotNull('started_at')
            ->selectRaw("ROUND(AVG(EXTRACT(EPOCH FROM (resolved_at - started_at)) / 3600)::numeric, 1) as avg_hours")
            ->value('avg_hours');

        $slaTotal = max(1, DB::table('tickets')->whereBetween('created_at', [$start, $end])->count());
        $slaBreach = $escalations; // same underlying event log/window — kept as one source of truth
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

        // By department — grouped per department row (not by name) and labelled with its
        // company, since the same department name exists under several companies. Left joins
        // so requesters with no department still count (as "No department") and the bars add
        // up to Total Support Requests.
        $byDepartment = DB::table('tickets')
            ->join('users', 'users.id', '=', 'tickets.users_id')
            ->leftJoin('departments', 'departments.id', '=', 'users.department_id')
            ->leftJoin('companies', 'companies.id', '=', 'departments.companies_id')
            ->whereBetween('tickets.created_at', [$start, $end])
            ->selectRaw("COALESCE(departments.department_name, 'No department') as department_name,
                COALESCE(companies.company_name, '—') as company_name, COUNT(*) as total")
            ->groupBy('departments.id', 'departments.department_name', 'companies.company_name')
            ->orderByDesc('total')
            ->get();

        // By category — the free-text tickets.request_category column is legacy and left blank
        // on virtually every ticket now that classification runs through sla_categories (see
        // Tickets::slaCategory()), so grouping by it collapsed everything into 1-2 buckets and
        // the "by category" total silently stopped matching Total Support Requests. Group by the
        // real category instead; aliased back to request_category so the view/JS need no changes.
        $byCategory = DB::table('tickets')
            ->leftJoin('sla_categories', 'sla_categories.id', '=', 'tickets.sla_category_id')
            ->whereBetween('tickets.created_at', [$start, $end])
            ->selectRaw("COALESCE(sla_categories.name, 'Uncategorized') as request_category, COUNT(*) as total")
            ->groupBy('sla_categories.name')->orderByDesc('total')->get();

        // Resolution time by category — same real-category source as above.
        $resTimeByCategory = DB::table('tickets')
            ->leftJoin('sla_categories', 'sla_categories.id', '=', 'tickets.sla_category_id')
            ->whereBetween('tickets.created_at', [$start, $end])
            ->where('tickets.status', 'Closed')
            ->whereNotNull('tickets.resolved_at')->whereNotNull('tickets.started_at')
            ->selectRaw("COALESCE(sla_categories.name, 'Uncategorized') as request_category, ROUND(AVG(EXTRACT(EPOCH FROM (tickets.resolved_at - tickets.started_at)) / 3600)::numeric, 1) as avg_hours")
            ->groupBy('sla_categories.name')->get();

        // Weekly data — most recent weekly buckets within the selected range (capped at 13)
        $weekBuckets = $bucketUnit === 'week' ? $volumeBuckets : $this->generateBuckets($start, $end, 'week', 13);
        $weeklyData = [];
        foreach ($weekBuckets as $b) {
            $weeklyData[] = [
                'label' => $b['start']->format('M d') . '–' . $b['end']->format('d'),
                'resolved' => DB::table('tickets')->whereBetween('created_at', [$b['start'], $b['end']])->where('status', 'Closed')->count(),
                'inProgress' => DB::table('tickets')->whereBetween('created_at', [$b['start'], $b['end']])->where('status', TicketStatus::IN_PROGRESS_SERVICE_REQUEST)->count(),
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
            // Capped at 5 before, which silently cut every Supervisor - IT Admin out of the
            // leaderboard (only 1 person in that role, always outranked by volume) even though
            // the query itself never filtered by role. Raised to comfortably fit the full
            // resolver roster (currently 8 active across Helpdesk/Specialist/Supervisor/Admin/
            // Supervisor Admin) so every role stays visible, not just the highest-volume ones.
            ->limit(10)
            ->get();

        // Aging report — always current (backlog health), regardless of the selected date range
        $aging = $this->agingReport();

        // Status aging — every ticket regardless of status, bucketed by age. Always
        // current, same as $aging above.
        $statusAging = $this->statusAging();

        // IT team aging — per assignee, per status, bucketed by age. Always current.
        $itTeamAging = $this->itTeamAging();

        // Open escalations — always current, regardless of the selected date range.
        // escalations.resolved_at is rarely stamped when the ticket is later closed, so filter
        // on the ticket's own status too — otherwise long-closed tickets keep showing up here.
        $openEscalations = DB::table('escalations')
            ->join('tickets', 'tickets.id', '=', 'escalations.ticket_id')
            ->join('users as reporter', 'reporter.id', '=', 'tickets.users_id')
            ->join('departments', 'departments.id', '=', 'reporter.department_id')
            ->leftJoin('users as tech', 'tech.id', '=', 'escalations.previous_tech_id')
            ->leftJoin('users as admin', 'admin.id', '=', 'escalations.reassigned_to')
            ->leftJoin('users as escalator', 'escalator.id', '=', 'escalations.escalated_by')
            ->whereNull('escalations.resolved_at')
            ->whereNotIn('tickets.status', ['Closed', 'Cancelled'])
            ->selectRaw("
                tickets.ticket_number, tickets.subject, tickets.concern, tickets.status,
                tickets.ticket_type, tickets.sla_due_at,
                departments.department_name, escalations.reason,
                escalations.escalation_level, escalations.escalated_at,
                escalator.name as escalated_by_name,
                tech.name as prev_tech,
                admin.name as reassigned_to_name
            ")
            ->orderBy('escalations.escalated_at', 'asc')
            ->get()
            ->map(function ($esc) {
                // Plain-language "where is it stuck" label, computed once here so the Blade
                // first render and the JS poll render can't drift apart.
                [$esc->status_label, $esc->status_class] = match ($esc->status) {
                    'Escalated'              => ['Waiting for reassignment', 'breach'],
                    'Requestor Confirmation' => ['Waiting for requester to confirm fix', 'admin'],
                    'On Hold'                => ['On hold', 'open'],
                    'Assigned'               => ['Reassigned — being worked on', 'admin'],
                    default                  => [$esc->status, 'open'],
                };
                $esc->needs_action = $esc->status === 'Escalated';
                $esc->sla_breached = $esc->sla_due_at && now()->greaterThan($esc->sla_due_at);
                return $esc;
            });

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
            'aging',
            'statusAging',
            'itTeamAging',
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
        $range = $request->get('range', 'YTD');
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

    // tickets.created_at is a plain `timestamp` column with no zone attached, and
    // Eloquent (and now()) write/read it as config('app.timezone') = Asia/Manila
    // wall-clock digits. Postgres's NOW() is UTC — so a raw "NOW() - created_at" in
    // SQL implicitly compares UTC-now against Manila-labelled digits and is off by
    // the zone offset (verified: it under-counts age by ~8h, enough to misbucket
    // tickets near a day boundary). PHP-side Carbon::parse(...)/now() both resolve
    // through the same app timezone, so diffing there gives the real elapsed time.
    // Every age_days computation in this controller must go through this helper —
    // never "NOW() - created_at" in raw SQL — so the aging report and its
    // agingTickets() drill-down can't disagree.
    private function parseCreatedAt(string $createdAt): \Illuminate\Support\Carbon
    {
        return \Illuminate\Support\Carbon::parse($createdAt, config('app.timezone'));
    }

    // Carbon 3 changed diffInDays() to return a signed value by default (negative
    // when $createdAt is in the past relative to now), so an un-abs'd diff silently
    // floors to 0 for every ticket — always landing in the "0-7" bucket. Force the
    // absolute (unsigned) diff here.
    private function ticketAgeDays(\Illuminate\Support\Carbon $createdAt): int
    {
        return (int) now()->diffInDays($createdAt, true);
    }

    private function agingBucketFor(int $days): string
    {
        foreach (self::AGING_BUCKETS as $label => [$min, $max]) {
            if ($days >= $min && $days <= $max) {
                return $label;
            }
        }
        return '90+';
    }

    // ── "Support Request Aging" panel: every open ticket (excludes Closed/Cancelled,
    // same set as the "All Active Tickets" panel), grouped by category, then by status
    // within that category, then bucketed by age (days since created_at) — surfaces
    // backlog that's quietly getting old inside a category/status combo a plain
    // "by category" total would hide. Always current, not date-range scoped, since it
    // describes the state of the backlog right now rather than activity in a window.
    private function agingReport(): array
    {
        $bucketLabels = array_keys(self::AGING_BUCKETS);

        $rows = DB::table('tickets')
            ->leftJoin('sla_categories', 'sla_categories.id', '=', 'tickets.sla_category_id')
            ->whereNotIn('tickets.status', self::ACTIVE_TICKET_EXCLUDED_STATUSES)
            ->selectRaw("
                COALESCE(sla_categories.name, 'Uncategorized') as category,
                tickets.status as status,
                tickets.created_at
            ")
            ->get();

        $categories = [];
        foreach ($rows as $row) {
            $category = $row->category;
            $status = $row->status ?? 'Unknown';
            // Bucketed with the same PHP-side helper agingTickets() uses (rather than
            // computing age_days in SQL with NOW()) so the report and its drill-down
            // list can never disagree — see ticketAgeDays()'s docblock for why a raw
            // SQL "NOW() - created_at" is wrong on this schema.
            $bucket = $this->agingBucketFor($this->ticketAgeDays($this->parseCreatedAt($row->created_at)));

            if (!isset($categories[$category])) {
                $categories[$category] = [
                    'category' => $category,
                    'buckets' => array_fill_keys($bucketLabels, 0),
                    'total' => 0,
                    'statuses' => [],
                ];
            }
            if (!isset($categories[$category]['statuses'][$status])) {
                $categories[$category]['statuses'][$status] = [
                    'status' => $status,
                    'buckets' => array_fill_keys($bucketLabels, 0),
                    'total' => 0,
                ];
            }

            $categories[$category]['buckets'][$bucket]++;
            $categories[$category]['total']++;
            $categories[$category]['statuses'][$status]['buckets'][$bucket]++;
            $categories[$category]['statuses'][$status]['total']++;
        }

        $categoryRows = collect($categories)
            ->sortByDesc('total')
            ->map(function ($cat) {
                $cat['statuses'] = collect($cat['statuses'])->sortByDesc('total')->values()->all();
                return $cat;
            })
            ->values();

        $grandTotals = array_fill_keys($bucketLabels, 0);
        foreach ($categoryRows as $cat) {
            foreach ($cat['buckets'] as $bucket => $count) {
                $grandTotals[$bucket] += $count;
            }
        }

        return [
            'buckets' => $bucketLabels,
            'categories' => $categoryRows->all(),
            'grandTotals' => $grandTotals,
            'grandTotal' => array_sum($grandTotals),
        ];
    }

    // ── "Status Aging" panel: every ticket regardless of status (unlike agingReport()
    // above, which excludes Closed/Cancelled since it's a backlog-health view) —
    // grouped by status only, bucketed by age since created_at. Surfaces how old
    // tickets sat in each status, including terminal ones. Always current, not
    // date-range scoped, same reasoning as agingReport().
    private function statusAging(): array
    {
        $bucketLabels = array_keys(self::AGING_BUCKETS);

        $rows = DB::table('tickets')->selectRaw('status, created_at')->get();

        // Seed every lifecycle status up front (zero-filled) so one with no tickets
        // right now still gets a row instead of silently dropping off the table.
        $statuses = [];
        foreach (self::STATUS_ORDER as $status) {
            $statuses[$status] = [
                'status' => $status,
                'buckets' => array_fill_keys($bucketLabels, 0),
                'total' => 0,
            ];
        }

        foreach ($rows as $row) {
            $status = $row->status ?? 'Unknown';
            $bucket = $this->agingBucketFor($this->ticketAgeDays($this->parseCreatedAt($row->created_at)));

            if (!isset($statuses[$status])) {
                $statuses[$status] = [
                    'status' => $status,
                    'buckets' => array_fill_keys($bucketLabels, 0),
                    'total' => 0,
                ];
            }

            $statuses[$status]['buckets'][$bucket]++;
            $statuses[$status]['total']++;
        }

        // Sequential (start-to-end lifecycle) order, not by count — see STATUS_ORDER.
        // Any status not in that list (shouldn't happen, but data can drift) sorts last.
        $order = array_flip(self::STATUS_ORDER);
        $statusRows = collect($statuses)
            ->sortBy(fn ($st) => $order[$st['status']] ?? PHP_INT_MAX)
            ->values();

        $grandTotals = array_fill_keys($bucketLabels, 0);
        foreach ($statusRows as $st) {
            foreach ($st['buckets'] as $bucket => $count) {
                $grandTotals[$bucket] += $count;
            }
        }

        return [
            'buckets' => $bucketLabels,
            'statuses' => $statusRows->all(),
            'grandTotals' => $grandTotals,
            'grandTotal' => array_sum($grandTotals),
        ];
    }

    // ── "IT Team Aging" panel: tickets currently waiting on an active IT-team member —
    // assigned to them, no role queue pending (pending_role null), and in a status where
    // the next action is theirs (MEMBER_ACTION_STATUSES). Bucketed by age since created_at,
    // per member per status; the status filter (All + each status) is applied client-side,
    // so every status's counts ship in one payload: counts[status][bucket]. Always current.
    private function itTeamAging(): array
    {
        $bucketLabels = array_keys(self::AGING_BUCKETS);

        $members = User::with('role')
            ->whereHas('role', fn($q) => $q->whereIn('role_name', self::IT_TEAM_ROLES))
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'role_id'])
            ->keyBy('id');

        $rows = DB::table('tickets')
            ->whereIn('assigned_to', $members->keys())
            ->whereNull('pending_role')
            ->whereIn('status', self::MEMBER_ACTION_STATUSES)
            ->selectRaw('assigned_to, status, created_at')
            ->get();

        $counts = [];
        foreach ($rows as $row) {
            $status = $row->status ?? 'Unknown';
            $bucket = $this->agingBucketFor($this->ticketAgeDays($this->parseCreatedAt($row->created_at)));
            $counts[$row->assigned_to][$status] ??= array_fill_keys($bucketLabels, 0);
            $counts[$row->assigned_to][$status][$bucket]++;
        }

        // The filter offers exactly the statuses this panel can count, in lifecycle order.
        $statuses = self::MEMBER_ACTION_STATUSES;

        return [
            'buckets' => $bucketLabels,
            'statuses' => $statuses,
            'members' => $members->values()->map(fn($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'role' => $m->role?->role_name,
                'counts' => (object) ($counts[$m->id] ?? []),
            ])->all(),
        ];
    }

    // ── AJAX endpoint behind the "Avg Resolution Time" chart — clicking a category lists
    // the 5 closed tickets in it that took longest to resolve. Same filters, date window and
    // hours formula (resolved_at - started_at) as $resTimeByCategory, so the list explains
    // the bar it was clicked from.
    public function resolutionTimeTop(Request $request)
    {
        $category = trim((string) $request->get('category', ''));
        $window = $this->rangeWindows((string) $request->get('range', 'YTD'));

        $tickets = DB::table('tickets')
            ->leftJoin('sla_categories', 'sla_categories.id', '=', 'tickets.sla_category_id')
            ->join('users as requester', 'requester.id', '=', 'tickets.users_id')
            ->leftJoin('users as assignee', 'assignee.id', '=', 'tickets.assigned_to')
            ->whereBetween('tickets.created_at', [$window['start'], $window['end']])
            ->where('tickets.status', 'Closed')
            ->whereNotNull('tickets.resolved_at')->whereNotNull('tickets.started_at')
            ->when(
                $category === 'Uncategorized',
                fn($q) => $q->whereNull('sla_categories.name'),
                fn($q) => $q->where('sla_categories.name', $category)
            )
            ->selectRaw("
                tickets.id, tickets.ticket_number, tickets.subject, tickets.ticket_type as priority,
                tickets.started_at, tickets.resolved_at,
                requester.name as requester_name, assignee.name as assigned_to_name,
                ROUND((EXTRACT(EPOCH FROM (tickets.resolved_at - tickets.started_at)) / 3600)::numeric, 1) as hours
            ")
            ->orderByDesc('hours')
            ->limit(5)
            ->get();

        return response()->json([
            'category' => $category,
            'rangeLabel' => $window['label'],
            'tickets' => $tickets,
        ]);
    }

    // ── AJAX endpoint backing the aging table's clickable cells — lists the open
    // tickets behind one category/status/age-bucket slice. Any of the three filters
    // may be blank to widen the slice (e.g. the "All Categories" total row omits
    // category; a category's own Total cell omits status and bucket). Reuses
    // agingBucketFor() so a ticket's bucket here always matches the count it was
    // clicked from.
    public function agingTickets(Request $request)
    {
        $category = trim((string) $request->get('category', ''));
        $status = trim((string) $request->get('status', ''));
        $bucket = trim((string) $request->get('bucket', ''));
        // Set by the Status Aging panel's own "All Statuses" total (which, unlike the
        // category panel's grand total, includes Closed/Cancelled) so that cell's count
        // and its drill-down list agree.
        $includeAll = $request->boolean('all');
        // Set by the IT Team Aging panel — narrows the slice to one assignee.
        $assignee = trim((string) $request->get('assignee', ''));

        $query = Tickets::with(['user', 'assignedTo', 'slaCategory']);

        // '__team__' = the panel's "All IT Team" row: anyone in the IT team, same member
        // set itTeamAging() counts, so that row's totals and its list agree.
        if ($assignee === '__team__') {
            $query->whereHas('assignedTo', fn($q) => $q->where('active', true)
                ->whereHas('role', fn($r) => $r->whereIn('role_name', self::IT_TEAM_ROLES)));
        } elseif ($assignee !== '') {
            $query->where('assigned_to', $assignee);
        }
        // Same "waiting on the member" rule itTeamAging() counts with.
        if ($assignee !== '') {
            $query->whereNull('pending_role')->whereIn('status', self::MEMBER_ACTION_STATUSES);
        }

        // A specific status already disambiguates the slice — including Closed/
        // Cancelled ones, which the Status Aging panel (unlike the category one)
        // deliberately shows. Only apply the open-backlog exclusion when no status
        // was given and the caller didn't opt into the full set via $includeAll.
        if ($status !== '') {
            $query->where('status', $status);
        } elseif (!$includeAll) {
            $query->whereNotIn('status', self::ACTIVE_TICKET_EXCLUDED_STATUSES);
        }

        $tickets = $query->orderByDesc('created_at')->get()
            ->filter(function ($t) use ($category, $bucket) {
                if ($category !== '' && ($t->slaCategory->name ?? 'Uncategorized') !== $category) {
                    return false;
                }
                if ($bucket !== '' && $this->agingBucketFor($this->ticketAgeDays($t->created_at)) !== $bucket) {
                    return false;
                }
                return true;
            })
            ->values();

        return response()->json([
            'tickets' => $tickets->take(200)->map(fn ($t) => [
                'id' => $t->id,
                'ticket_number' => $t->ticket_number,
                'subject' => $t->subject,
                'requester_name' => $t->user->name ?? 'Unknown',
                'status' => $t->status,
                'pending_role' => $t->pending_role,
                'assigned_to_name' => $t->assignedTo->name ?? null,
                'priority' => $t->ticket_type,
                'category' => $t->slaCategory->name ?? 'Uncategorized',
                'created_at' => optional($t->created_at)->toISOString(),
                'age_days' => $this->ticketAgeDays($t->created_at),
            ])->values(),
            'total' => $tickets->count(),
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
