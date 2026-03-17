<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExecutiveDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user()->load('role');
        $greeting = $this->getGreeting();
        $now = now();
        $start = $now->copy()->startOfMonth();
        $end = $now->copy()->endOfMonth();
        $lastStart = $now->copy()->subMonth()->startOfMonth();
        $lastEnd = $now->copy()->subMonth()->endOfMonth();

        // ── KPI Cards ──
        $totalTickets = DB::table('tickets')->whereBetween('created_at', [$start, $end])->count();
        $resolved = DB::table('tickets')->whereBetween('created_at', [$start, $end])->where('status', 'Resolved')->count();
        $escalations = DB::table('tickets')->whereBetween('created_at', [$start, $end])->where('status', 'Escalated')->count();
        $avgResolutionTime = DB::table('tickets')
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'Resolved')
            ->whereNotNull('resolved_at')
            ->whereNotNull('started_at')
            ->selectRaw("AVG(EXTRACT(EPOCH FROM (resolved_at - started_at)) / 3600) as avg_hours")
            ->value('avg_hours');

        // ── Last Month KPIs (for comparison) ──
        $lastTotalTickets = DB::table('tickets')->whereBetween('created_at', [$lastStart, $lastEnd])->count();
        $lastResolved = DB::table('tickets')->whereBetween('created_at', [$lastStart, $lastEnd])->where('status', 'Resolved')->count();
        $lastEscalations = DB::table('tickets')->whereBetween('created_at', [$lastStart, $lastEnd])->where('status', 'Escalated')->count();
        $lastAvgTime = DB::table('tickets')
            ->whereBetween('created_at', [$lastStart, $lastEnd])
            ->where('status', 'Resolved')
            ->whereNotNull('resolved_at')
            ->whereNotNull('started_at')
            ->selectRaw("AVG(EXTRACT(EPOCH FROM (resolved_at - started_at)) / 3600) as avg_hours")
            ->value('avg_hours');

        // ── Overall SLA ──
        $slaTotal = DB::table('tickets')->whereBetween('created_at', [$start, $end])->count();
        $slaBreach = DB::table('escalations')->whereBetween('escalated_at', [$start, $end])->count();
        $slaPercent = $slaTotal > 0 ? round((($slaTotal - $slaBreach) / $slaTotal) * 100) : 100;

        // ── Avg Rating ──
        $avgRating = DB::table('ticket_feed_backs')
            ->whereBetween('created_at', [$start, $end])
            ->avg('rating') ?? 0;

        // ── Ticket Volume Trend (last 30 days) ──
        $volumeDays = [];
        $volumeOpened = [];
        $volumeResolved = [];
        for ($i = 29; $i >= 0; $i--) {
            $day = $now->copy()->subDays($i)->format('Y-m-d');
            $volumeDays[] = $now->copy()->subDays($i)->format('d');
            $volumeOpened[] = DB::table('tickets')->whereDate('created_at', $day)->count();
            $volumeResolved[] = DB::table('tickets')->whereDate('resolved_at', $day)->where('status', 'Resolved')->count();
        }

        // ── SLA by Priority ──
        foreach (['High', 'Medium', 'Low'] as $priority) {
            $total = DB::table('tickets')->whereBetween('created_at', [$start, $end])->where('ticket_type', $priority)->count();
            $breached = DB::table('escalations')
                ->join('tickets', 'tickets.id', '=', 'escalations.ticket_id')
                ->whereBetween('escalations.escalated_at', [$start, $end])
                ->where('tickets.ticket_type', $priority)
                ->count();
            $slaByPriority[$priority] = $total > 0 ? round((($total - $breached) / $total) * 100) : 100;
        }

        // ── Tickets by Category ──
        $byCategory = DB::table('tickets')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('request_category, COUNT(*) as total')
            ->groupBy('request_category')
            ->orderByDesc('total')
            ->get();

        // ── Tickets by Department ──
        $byDepartment = DB::table('tickets')
            ->join('users', 'users.id', '=', 'tickets.users_id')
            ->join('departments', 'departments.id', '=', 'users.department_id')
            ->whereBetween('tickets.created_at', [$start, $end])
            ->selectRaw('departments.department_name, COUNT(*) as total')
            ->groupBy('departments.department_name')
            ->orderByDesc('total')
            ->get();

        // ── Avg Resolution Time by Category ──
        $resTimeByCategory = DB::table('tickets')
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'Resolved')
            ->whereNotNull('resolved_at')
            ->whereNotNull('started_at')
            ->selectRaw("request_category, ROUND(AVG(EXTRACT(EPOCH FROM (resolved_at - started_at)) / 3600)::numeric, 1) as avg_hours")
            ->groupBy('request_category')
            ->get();

        // ── Technician Leaderboard ──
        $leaderboard = DB::table('tickets')
            ->join('users', 'users.id', '=', 'tickets.assigned_to')
            ->leftJoin('ticket_feed_backs', 'ticket_feed_backs.ticket_id', '=', 'tickets.id')
            ->whereBetween('tickets.created_at', [$start, $end])
            ->where('tickets.status', 'Resolved')
            ->whereNotNull('tickets.assigned_to')
            ->selectRaw("
                users.id,
                users.name,
                users.position,
                COUNT(tickets.id) as resolved_count,
                ROUND(AVG(EXTRACT(EPOCH FROM (tickets.resolved_at - tickets.started_at)) / 3600)::numeric, 1) as avg_hours,
                ROUND(AVG(ticket_feed_backs.rating)::numeric, 1) as avg_rating
            ")
            ->groupBy('users.id', 'users.name', 'users.position')
            ->orderByDesc('resolved_count')
            ->limit(5)
            ->get();

        // ── Escalations Requiring Attention ──
        $openEscalations = DB::table('escalations')
            ->join('tickets', 'tickets.id', '=', 'escalations.ticket_id')
            ->join('users as reporter', 'reporter.id', '=', 'tickets.users_id')
            ->join('departments', 'departments.id', '=', 'reporter.department_id')
            ->leftJoin('users as tech', 'tech.id', '=', 'escalations.previous_tech_id')
            ->leftJoin('users as admin', 'admin.id', '=', 'escalations.reassigned_to')
            ->whereNull('escalations.resolved_at')
            ->selectRaw("
                tickets.subject,
                tickets.concern,
                tickets.status,
                departments.department_name,
                escalations.reason,
                escalations.escalated_at,
                tech.name as prev_tech,
                admin.name as reassigned_to_name
            ")
            ->orderBy('escalations.escalated_at', 'asc')
            ->get();

        // ── Weekly Breakdown (last 4 weeks) ──
        $weeklyData = [];
        for ($i = 3; $i >= 0; $i--) {
            $wStart = $now->copy()->subWeeks($i)->startOfWeek();
            $wEnd = $now->copy()->subWeeks($i)->endOfWeek();
            $weeklyData[] = [
                'label' => $wStart->format('M d') . '–' . $wEnd->format('d'),
                'resolved' => DB::table('tickets')->whereBetween('created_at', [$wStart, $wEnd])->where('status', 'Resolved')->count(),
                'inProgress' => DB::table('tickets')->whereBetween('created_at', [$wStart, $wEnd])->where('status', 'In Progress')->count(),
                'escalated' => DB::table('tickets')->whereBetween('created_at', [$wStart, $wEnd])->where('status', 'Escalated')->count(),
            ];
        }

        // ── CSAT ──
        $totalFeedback = DB::table('ticket_feed_backs')->whereBetween('created_at', [$start, $end])->count();
        $csatBreakdown = [];
        foreach ([5, 4, 3, 2, 1] as $star) {
            $count = DB::table('ticket_feed_backs')
                ->whereBetween('created_at', [$start, $end])
                ->where('rating', $star)
                ->count();
            $csatBreakdown[$star] = [
                'count' => $count,
                'percent' => $totalFeedback > 0 ? round(($count / $totalFeedback) * 100) : 0,
            ];
        }

        return view('dashboard.executive', compact(
            'user',
            'greeting',
            'totalTickets',
            'resolved',
            'escalations',
            'avgResolutionTime',
            'lastTotalTickets',
            'lastResolved',
            'lastEscalations',
            'lastAvgTime',
            'slaPercent',
            'avgRating',
            'slaBreach',
            'volumeDays',
            'volumeOpened',
            'volumeResolved',
            'slaByPriority',
            'byCategory',
            'byDepartment',
            'resTimeByCategory',
            'leaderboard',
            'openEscalations',
            'weeklyData',
            'totalFeedback',
            'csatBreakdown',
            'avgRating',
            'lastStart',
            'lastEnd',
            'now'
        ));
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