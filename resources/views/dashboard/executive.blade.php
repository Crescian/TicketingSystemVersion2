@extends('layouts.executive')

@section('title', 'Executive Dashboard — LGICT')

@section('avatar-initials', 'CE')
@section('nav-username', 'C. Evangelista')

{{-- ══ MAIN CONTENT ══ --}}
@section('content')

    {{-- ── Greeting strip ── --}}
    <div class="greeting-strip mb-4">
        <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
            <div>
                <div class="live-pill mb-2">
                    <div class="live-dot"></div>Live Data
                </div>
                <div class="greeting-title">
                    {{ strtoupper($greeting) }}, <em>{{ strtoupper(explode(' ', Auth::user()->name)[0]) }}.</em>
                </div>
                <div class="greeting-sub">
                    Here's your IT Support overview for the last 30 days — {{ now()->format('F Y') }}.
                </div>
            </div>
            <div class="d-flex gap-3 flex-wrap align-items-center">
                <div style="text-align:center">
                    <div
                        style="font-family:'Nunito',sans-serif;font-weight:900;font-size:32px;color:var(--ex-yg);line-height:1">
                        {{ $slaPercent }}%
                    </div>
                    <div
                        style="font-size:11px;color:rgba(255,255,255,.45);text-transform:uppercase;letter-spacing:.4px;margin-top:2px">
                        Overall SLA
                    </div>
                </div>
                <div style="width:1px;height:40px;background:rgba(255,255,255,.1)"></div>
                <div style="text-align:center">
                    <div
                        style="font-family:'Nunito',sans-serif;font-weight:900;font-size:32px;color:var(--ex-green);line-height:1">
                        {{ number_format($avgRating, 1) }}
                    </div>
                    <div
                        style="font-size:11px;color:rgba(255,255,255,.45);text-transform:uppercase;letter-spacing:.4px;margin-top:2px">
                        Avg Rating ⭐
                    </div>
                </div>
                <div style="width:1px;height:40px;background:rgba(255,255,255,.1)"></div>
                <div style="text-align:center">
                    <div style="font-family:'Nunito',sans-serif;font-weight:900;font-size:32px;color:#ff8888;line-height:1">
                        {{ $slaBreach }}
                    </div>
                    <div
                        style="font-size:11px;color:rgba(255,255,255,.45);text-transform:uppercase;letter-spacing:.4px;margin-top:2px">
                        SLA Breaches
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── KPI Row ── --}}
    {{-- ── KPI Row ── --}}
    <div class="section-label">Key Performance Indicators</div>
    <div class="kpi-grid mb-4">

        @php
            $ticketChange = $lastTotalTickets > 0 ? round((($totalTickets - $lastTotalTickets) / $lastTotalTickets) * 100) : 0;
            $resolvedChange = $lastResolved > 0 ? round((($resolved - $lastResolved) / $lastResolved) * 100) : 0;
            $avgTimeChange = $lastAvgTime > 0 ? round((($avgResolutionTime - $lastAvgTime) / $lastAvgTime) * 100) : 0;
            $escChange = $lastEscalations > 0 ? $escalations - $lastEscalations : 0;
            $resolutionRate = $totalTickets > 0 ? round(($resolved / $totalTickets) * 100, 1) : 0;
        @endphp

        <div class="kpi-card yg">
            <div class="kpi-icon yg"><i class="bi bi-ticket-perforated"></i></div>
            <div class="kpi-value">{{ $totalTickets }}</div>
            <div class="kpi-label">Total Tickets</div>
            <span class="kpi-trend {{ $ticketChange >= 0 ? 'up' : 'down' }}">
                <i class="bi bi-arrow-{{ $ticketChange >= 0 ? 'up' : 'down' }}-short"></i>
                {{ $ticketChange >= 0 ? '+' : '' }}{{ $ticketChange }}%
            </span>
            <div class="kpi-compare">vs. {{ $lastTotalTickets }} last month</div>
        </div>

        <div class="kpi-card green">
            <div class="kpi-icon green"><i class="bi bi-check-circle"></i></div>
            <div class="kpi-value">{{ $resolved }}</div>
            <div class="kpi-label">Resolved</div>
            <span class="kpi-trend {{ $resolvedChange >= 0 ? 'up' : 'down' }}">
                <i class="bi bi-arrow-{{ $resolvedChange >= 0 ? 'up' : 'down' }}-short"></i>
                {{ $resolvedChange >= 0 ? '+' : '' }}{{ $resolvedChange }}%
            </span>
            <div class="kpi-compare">{{ $resolutionRate }}% resolution rate</div>
        </div>

        <div class="kpi-card amber">
            <div class="kpi-icon amber"><i class="bi bi-clock-history"></i></div>
            <div class="kpi-value">{{ $avgResolutionTime ? number_format($avgResolutionTime, 1) . 'h' : 'N/A' }}</div>
            <div class="kpi-label">Avg Resolution Time</div>
            <span class="kpi-trend {{ $avgTimeChange <= 0 ? 'up' : 'down' }}">
                <i class="bi bi-arrow-{{ $avgTimeChange <= 0 ? 'down' : 'up' }}-short"></i>
                {{ $avgTimeChange > 0 ? '+' : '' }}{{ $avgTimeChange }}%
            </span>
            <div class="kpi-compare">vs. {{ $lastAvgTime ? number_format($lastAvgTime, 1) . 'h' : 'N/A' }} last month</div>
        </div>

        <div class="kpi-card red">
            <div class="kpi-icon red"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="kpi-value">{{ $escalations }}</div>
            <div class="kpi-label">Escalations</div>
            <span class="kpi-trend {{ $escChange <= 0 ? 'up' : 'down' }}">
                <i class="bi bi-arrow-{{ $escChange > 0 ? 'up' : 'down' }}-short"></i>
                {{ $escChange > 0 ? '+' : '' }}{{ $escChange }}
            </span>
            <div class="kpi-compare">
                {{ $totalTickets > 0 ? number_format(($escalations / $totalTickets) * 100, 1) : 0 }}% escalation rate
            </div>
        </div>

    </div>

    {{-- ── Row 2: Volume chart + SLA gauges ── --}}
    <div class="row g-3 mb-4">

        <div class="col-lg-8">
            <div class="chart-card h-100">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <div>
                        <div class="chart-title">Ticket Volume Trend</div>
                        <div class="chart-sub">Daily tickets opened vs. resolved — last 30 days</div>
                    </div>
                    <div class="d-flex gap-3" style="font-size:12px;font-weight:700;color:var(--ex-muted)">
                        <span><span
                                style="display:inline-block;width:10px;height:10px;border-radius:50%;background:var(--ex-yg);margin-right:4px"></span>Opened</span>
                        <span><span
                                style="display:inline-block;width:10px;height:10px;border-radius:50%;background:var(--ex-green);margin-right:4px"></span>Resolved</span>
                    </div>
                </div>
                <div class="chart-wrap"><canvas id="volumeChart" height="110"></canvas></div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="chart-card h-100">
                <div class="chart-title">SLA Compliance</div>
                <div class="chart-sub">By priority level — this month</div>
                <div class="sla-gauge-wrap mt-2">
                    <div class="gauge-item">
                        <div class="gauge-ring">
                            <canvas id="gHigh"></canvas>
                            <div class="gauge-label-center">
                                <div class="gauge-pct" style="color:var(--ex-red)">88%</div>
                                <div class="gauge-sub">met</div>
                            </div>
                        </div>
                        <div class="gauge-name" style="color:var(--ex-red)">High</div>
                    </div>
                    <div class="gauge-item">
                        <div class="gauge-ring">
                            <canvas id="gMed"></canvas>
                            <div class="gauge-label-center">
                                <div class="gauge-pct" style="color:var(--ex-amber)">95%</div>
                                <div class="gauge-sub">met</div>
                            </div>
                        </div>
                        <div class="gauge-name" style="color:var(--ex-amber)">Med</div>
                    </div>
                    <div class="gauge-item">
                        <div class="gauge-ring">
                            <canvas id="gLow"></canvas>
                            <div class="gauge-label-center">
                                <div class="gauge-pct" style="color:var(--ex-green)">99%</div>
                                <div class="gauge-sub">met</div>
                            </div>
                        </div>
                        <div class="gauge-name" style="color:var(--ex-green)">Low</div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ── Row 3: Category donut + Department heatmap + Resolution time ── --}}
    <div class="row g-3 mb-4">

        <div class="col-lg-4">
            <div class="chart-card h-100">
                <div class="chart-title">Tickets by Category</div>
                <div class="chart-sub">Distribution across all request types</div>
                <div class="chart-wrap d-flex align-items-center gap-4 mt-2">
                    <canvas id="categoryChart" width="160" height="160" style="flex-shrink:0"></canvas>
                    <div class="d-flex flex-column gap-2" style="flex:1">
                        <div class="d-flex justify-content-between align-items-center">
                            <span style="font-size:12px;font-weight:700;color:var(--ex-txt)"><span
                                    style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#f5c842;margin-right:6px"></span>Hardware</span>
                            <span style="font-family:'Nunito',sans-serif;font-weight:800;font-size:13px">87 <span
                                    style="color:var(--ex-muted);font-size:11px">35%</span></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span style="font-size:12px;font-weight:700;color:var(--ex-txt)"><span
                                    style="display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--ex-blue);margin-right:6px"></span>Software</span>
                            <span style="font-family:'Nunito',sans-serif;font-weight:800;font-size:13px">74 <span
                                    style="color:var(--ex-muted);font-size:11px">30%</span></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span style="font-size:12px;font-weight:700;color:var(--ex-txt)"><span
                                    style="display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--ex-yg);margin-right:6px"></span>Network</span>
                            <span style="font-family:'Nunito',sans-serif;font-weight:800;font-size:13px">49 <span
                                    style="color:var(--ex-muted);font-size:11px">20%</span></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span style="font-size:12px;font-weight:700;color:var(--ex-txt)"><span
                                    style="display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--ex-green);margin-right:6px"></span>Account</span>
                            <span style="font-family:'Nunito',sans-serif;font-weight:800;font-size:13px">37 <span
                                    style="color:var(--ex-muted);font-size:11px">15%</span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="chart-card h-100">
                <div class="chart-title">Tickets by Department</div>
                <div class="chart-sub">Volume this month — color = severity</div>
                {{-- ── Tickets by Department ── --}}
                <div class="dept-grid mt-3">
                    @foreach($byDepartment as $dept)
                        @php
                            $heat = $dept->total >= 35 ? 'hot' : ($dept->total >= 20 ? 'warm' : 'cool');
                        @endphp
                        <div class="dept-cell {{ $heat }}">
                            <div class="dept-name">{{ $dept->department_name }}</div>
                            <div class="dept-count {{ $heat }}">{{ $dept->total }}</div>
                            <div class="dept-label">tickets</div>
                        </div>
                    @endforeach
                </div>
                <div class="d-flex gap-3 mt-3" style="font-size:11px;color:var(--ex-muted);font-weight:600">
                    <span><span
                            style="display:inline-block;width:8px;height:8px;border-radius:2px;background:var(--ex-red);margin-right:4px;opacity:.6"></span>High
                        (>35)</span>
                    <span><span
                            style="display:inline-block;width:8px;height:8px;border-radius:2px;background:var(--ex-amber);margin-right:4px;opacity:.6"></span>Med
                        (20–35)</span>
                    <span><span
                            style="display:inline-block;width:8px;height:8px;border-radius:2px;background:var(--ex-green);margin-right:4px;opacity:.6"></span>Low
                        (<20)< /span>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="chart-card h-100">
                <div class="chart-title">Avg Resolution Time</div>
                <div class="chart-sub">By category — hours to resolve</div>
                <div class="chart-wrap mt-2"><canvas id="resTimeChart" height="180"></canvas></div>
            </div>
        </div>

    </div>

    {{-- ── Row 4: Leaderboard + Escalations + Period compare ── --}}
    <div class="row g-3 mb-4">

        <div class="col-lg-5">
            <div class="chart-card h-100">
                <div class="chart-title">Technician Performance</div>
                <div class="chart-sub">Ranked by tickets resolved — this month</div>
                <table class="lb-table mt-3">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Technician</th>
                            <th>Resolved</th>
                            <th>Avg Time</th>
                            <th style="text-align:right">Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($leaderboard as $index => $tech)
                            @php
                                $rank = $index + 1;
                                $rankClass = match ($rank) { 1 => 'gold', 2 => 'silver', 3 => 'bronze', default => ''};
                                $initials = collect(explode(' ', $tech->name))->map(fn($w) => strtoupper($w[0]))->take(2)->join('');
                                $timeColor = $tech->avg_hours <= 2 ? 'var(--ex-green)' : ($tech->avg_hours <= 3 ? 'var(--ex-amber)' : 'var(--ex-red)');
                                $maxResolved = $leaderboard->first()->resolved_count;
                                $barWidth = $maxResolved > 0 ? round(($tech->resolved_count / $maxResolved) * 100) : 0;
                            @endphp
                            <tr>
                                <td class="lb-rank {{ $rankClass }}">{{ $rank }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="lb-av">{{ $initials }}</div>
                                        <div>
                                            <div class="lb-name">{{ $tech->name }}</div>
                                            <div class="lb-role">{{ $tech->position }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-family:'Nunito',sans-serif;font-weight:800;font-size:14px">
                                        {{ $tech->resolved_count }}
                                    </div>
                                    <div class="lb-bar-wrap">
                                        <div class="lb-bar" style="width:{{ $barWidth }}%"></div>
                                    </div>
                                </td>
                                <td style="font-size:13px;font-weight:700;color:{{ $timeColor }}">
                                    {{ $tech->avg_hours ?? 'N/A' }}h
                                </td>
                                <td class="lb-rating">
                                    <div class="lb-num">{{ $tech->avg_rating ?? 'N/A' }} ⭐</div>
                                </td>
                            </tr>
                        @endforeach

                        @if($leaderboard->isEmpty())
                            <tr>
                                <td colspan="5" style="text-align:center;color:var(--ex-muted);padding:20px">
                                    No resolved tickets this month yet.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="row g-3 h-100">

                {{-- Month-over-month comparison --}}
                <div class="col-12">
                    <div class="chart-card">
                        <div class="chart-title">Month-over-Month Comparison</div>
                        <div class="chart-sub">February vs. March 2026</div>
                        <div class="comparison-strip mt-3">
                            <div class="cmp-col">
                                <div class="cmp-period">{{ $lastStart->format('M Y') }}</div>
                                <div class="cmp-val">{{ $lastTotalTickets }}</div>
                                <div class="cmp-diff" style="color:var(--ex-muted)">Total tickets</div>
                            </div>
                            <div class="cmp-col">
                                <div class="cmp-period">{{ now()->format('M Y') }}</div>
                                <div class="cmp-val">{{ $totalTickets }}</div>
                                <div class="cmp-diff {{ $totalTickets > $lastTotalTickets ? 'worse' : 'better' }}">
                                    {{ $totalTickets > $lastTotalTickets ? '+' : '' }}{{ $totalTickets - $lastTotalTickets }}
                                    tickets
                                    {{ $totalTickets > $lastTotalTickets ? '↑' : '↓' }}
                                </div>
                            </div>
                            <div class="cmp-col">
                                <div class="cmp-period">Avg Resolve {{ $lastStart->format('M') }}</div>
                                <div class="cmp-val">{{ $lastAvgTime ? number_format($lastAvgTime, 1) . 'h' : 'N/A' }}</div>
                                <div class="cmp-diff" style="color:var(--ex-muted)">Resolution time</div>
                            </div>
                            <div class="cmp-col">
                                <div class="cmp-period">Avg Resolve {{ now()->format('M') }}</div>
                                <div class="cmp-val">
                                    {{ $avgResolutionTime ? number_format($avgResolutionTime, 1) . 'h' : 'N/A' }}
                                </div>
                                @if($avgResolutionTime && $lastAvgTime)
                                    <div class="cmp-diff {{ $avgResolutionTime < $lastAvgTime ? 'better' : 'worse' }}">
                                        {{ $avgResolutionTime < $lastAvgTime ? '−' : '+' }}{{ number_format(abs($avgResolutionTime - $lastAvgTime), 1) }}h
                                        {{ $avgResolutionTime < $lastAvgTime ? 'faster ↓' : 'slower ↑' }}
                                    </div>
                                @endif
                            </div>
                            <div class="cmp-col">
                                <div class="cmp-period">Escalations {{ $lastStart->format('M') }}</div>
                                <div class="cmp-val">{{ $lastEscalations }}</div>
                                <div class="cmp-diff" style="color:var(--ex-muted)">Escalations</div>
                            </div>
                            <div class="cmp-col">
                                <div class="cmp-period">Escalations {{ now()->format('M') }}</div>
                                <div class="cmp-val">{{ $escalations }}</div>
                                <div class="cmp-diff {{ $escalations > $lastEscalations ? 'worse' : 'better' }}">
                                    {{ $escalations > $lastEscalations ? '+' : '' }}{{ $escalations - $lastEscalations }}
                                    {{ $escalations > $lastEscalations ? '↑' : '↓' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Escalations requiring attention --}}
                <div class="col-12">
                    <div class="chart-card">
                        @php $criticalCount = $openEscalations->where('status', 'Escalated')->count(); @endphp
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div>
                                <div class="chart-title">Escalations Requiring Attention</div>
                                <div class="chart-sub">Critical or SLA-breached tickets this month</div>
                            </div>
                            <span
                                style="background:rgba(248,81,73,.12);color:var(--ex-red);font-size:11px;font-weight:800;padding:4px 10px;border-radius:20px;border:1px solid rgba(248,81,73,.2)">
                                {{ $criticalCount }} critical
                            </span>
                        </div>
                        <div class="mt-2">
                            @forelse($openEscalations as $esc)
                                @php
                                    $isBreach = str_contains(strtolower($esc->reason ?? ''), 'sla') || str_contains(strtolower($esc->status ?? ''), 'breach');
                                    $hoursAgo = \Carbon\Carbon::parse($esc->escalated_at)->diffForHumans();
                                @endphp
                                <div class="esc-item">
                                    <div class="esc-icon {{ $isBreach ? 'crit' : 'warn' }}">
                                        <i
                                            class="bi bi-{{ $isBreach ? 'exclamation-triangle-fill' : 'exclamation-circle' }}"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="esc-title">{{ $esc->subject }}</div>
                                        <div class="esc-meta">
                                            {{ $esc->department_name }} dept ·
                                            Escalated {{ $hoursAgo }}
                                            @if($esc->prev_tech) · {{ $esc->prev_tech }} →
                                            {{ $esc->reassigned_to_name ?? 'Unassigned' }} @endif
                                        </div>
                                    </div>
                                    <span class="esc-badge {{ $isBreach ? 'breach' : 'open' }}">
                                        {{ $isBreach ? 'SLA Breach' : 'Open' }}
                                    </span>
                                </div>
                            @empty
                                <div style="text-align:center;color:var(--ex-muted);padding:20px">
                                    ✅ No open escalations right now.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

    {{-- ── Row 5: Weekly bar + CSAT ── --}}
    <div class="row g-3 mb-2">

        <div class="col-lg-8">
            <div class="chart-card">
                <div class="chart-title">Weekly Ticket Breakdown</div>
                <div class="chart-sub">Tickets by status per week — last 4 weeks</div>
                <div class="chart-wrap mt-2"><canvas id="weeklyChart" height="100"></canvas></div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="chart-card h-100">
                <div class="chart-title">Customer Satisfaction</div>
                <div class="chart-sub">Rating breakdown this month</div>
                <div class="text-center my-3">
                    <div
                        style="font-family:'Nunito',sans-serif;font-weight:900;font-size:56px;line-height:1;color:var(--ex-yg)">
                        {{ number_format($avgRating, 1) }}
                    </div>
                    <div style="font-size:22px;margin:4px 0">⭐⭐⭐⭐⭐</div>
                    <div style="font-size:12px;color:var(--ex-muted)">Based on {{ $totalFeedback }} feedback responses</div>
                </div>
                <div class="d-flex flex-column gap-2 mt-2">
                    @foreach($csatBreakdown as $star => $data)
                        @php
                            $colors = [5 => 'var(--ex-green)', 4 => 'var(--ex-yg)', 3 => 'var(--ex-amber)', 2 => 'var(--ex-red)', 1 => '#5a2a2a'];
                        @endphp
                        <div class="d-flex align-items-center gap-2">
                            <span style="font-size:12px;color:var(--ex-muted);width:18px;text-align:right">{{ $star }}★</span>
                            <div style="flex:1;height:8px;background:var(--ex-card2);border-radius:4px;overflow:hidden">
                                <div
                                    style="height:8px;background:{{ $colors[$star] }};border-radius:4px;width:{{ $data['percent'] }}%">
                                </div>
                            </div>
                            <span style="font-size:12px;font-weight:700;width:28px">{{ $data['percent'] }}%</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>

@endsection

{{-- ══ CHART SCRIPTS ══ --}}
@section('scripts')
    <script>
        $(function () {

            /* ── Volume trend ── */
            const volumeChart = new Chart(document.getElementById('volumeChart'), {
                type: 'line',
                data: {
                    labels: {!! json_encode($volumeDays) !!},
                    datasets: [
                        { label: 'Opened', data: {!! json_encode($volumeOpened) !!}, borderColor: '#c8e63c', backgroundColor: 'rgba(200,230,60,.08)', borderWidth: 2, pointRadius: 0, tension: .4, fill: true },
                        { label: 'Resolved', data: {!! json_encode($volumeResolved) !!}, borderColor: '#3fb950', backgroundColor: 'rgba(63,185,80,.06)', borderWidth: 2, pointRadius: 0, tension: .4, fill: true }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false } },
                        y: { beginAtZero: true, grid: { color: 'rgba(48,54,61,.6)' }, ticks: { stepSize: 4 } }
                    }
                }
            });

            /* ── SLA Gauges ── */
            function makeGauge(id, pct, color) {
                new Chart(document.getElementById(id), {
                    type: 'doughnut',
                    data: { datasets: [{ data: [pct, 100 - pct], backgroundColor: [color, 'rgba(48,54,61,.8)'], borderWidth: 0, borderRadius: 4, spacing: 2 }] },
                    options: { cutout: '72%', responsive: false, plugins: { legend: { display: false }, tooltip: { enabled: false } } }
                });
            }
            makeGauge('gHigh', {{ $slaByPriority['High'] ?? 100 }}, '#f85149');
            makeGauge('gMed',  {{ $slaByPriority['Medium'] ?? 100 }}, '#d29922');
            makeGauge('gLow',  {{ $slaByPriority['Low'] ?? 100 }}, '#3fb950');

            /* ── Update gauge center labels ── */
            document.querySelector('#gHigh').closest('.gauge-ring').querySelector('.gauge-pct').textContent = '{{ $slaByPriority["High"] ?? 100 }}%';
            document.querySelector('#gMed').closest('.gauge-ring').querySelector('.gauge-pct').textContent = '{{ $slaByPriority["Medium"] ?? 100 }}%';
            document.querySelector('#gLow').closest('.gauge-ring').querySelector('.gauge-pct').textContent = '{{ $slaByPriority["Low"] ?? 100 }}%';

            /* ── Category donut ── */
            const catLabels = {!! json_encode($byCategory->pluck('request_category')) !!};
            const catData = {!! json_encode($byCategory->pluck('total')) !!};
            const catColors = ['#f5c842', '#58a6ff', '#c8e63c', '#3fb950', '#f85149', '#d29922'];
            new Chart(document.getElementById('categoryChart'), {
                type: 'doughnut',
                data: {
                    labels: catLabels,
                    datasets: [{ data: catData, backgroundColor: catColors.slice(0, catLabels.length), borderWidth: 0, borderRadius: 4, spacing: 2 }]
                },
                options: { cutout: '65%', responsive: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => ' ' + c.label + ': ' + c.raw + ' tickets' } } } }
            });

            /* ── Resolution time bar ── */
            new Chart(document.getElementById('resTimeChart'), {
                type: 'bar',
                data: {
                    labels: {!! json_encode($resTimeByCategory->pluck('request_category')) !!},
                    datasets: [{ data: {!! json_encode($resTimeByCategory->pluck('avg_hours')) !!}, backgroundColor: ['#f85149', '#58a6ff', '#d29922', '#3fb950', '#c8e63c'], borderRadius: 6, borderSkipped: false }]
                },
                options: {
                    indexAxis: 'y', responsive: true,
                    plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => ` ${c.raw}h avg` } } },
                    scales: {
                        x: { beginAtZero: true, grid: { color: 'rgba(48,54,61,.6)' }, ticks: { callback: v => v + 'h' } },
                        y: { grid: { display: false } }
                    }
                }
            });

            /* ── Weekly stacked bar ── */
            new Chart(document.getElementById('weeklyChart'), {
                type: 'bar',
                data: {
                    labels: {!! json_encode(collect($weeklyData)->pluck('label')) !!},
                    datasets: [
                        { label: 'Resolved', data: {!! json_encode(collect($weeklyData)->pluck('resolved')) !!}, backgroundColor: '#3fb950', borderRadius: 4, stack: 's' },
                        { label: 'In Progress', data: {!! json_encode(collect($weeklyData)->pluck('inProgress')) !!}, backgroundColor: '#d29922', borderRadius: 0, stack: 's' },
                        { label: 'Escalated', data: {!! json_encode(collect($weeklyData)->pluck('escalated')) !!}, backgroundColor: '#f85149', borderRadius: 0, stack: 's' },
                    ]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, padding: 16, color: '#7d8590' } } },
                    scales: {
                        x: { grid: { display: false }, stacked: true },
                        y: { beginAtZero: true, grid: { color: 'rgba(48,54,61,.6)' }, stacked: true }
                    }
                }
            });

        });
    </script>
@endsection