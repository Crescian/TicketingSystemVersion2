@extends('layouts.executive')

@section('title', 'Executive Dashboard — LGICT')

@section('avatar-initials', 'CE')
@section('nav-username', 'C. Evangelista')

@section('styles')
    .rt-btn {
        background: rgba(200, 230, 60, .12);
        color: var(--ex-yg);
        border: 1px solid rgba(200, 230, 60, .25);
        border-radius: 20px;
        font-size: 11px;
        font-weight: 800;
        padding: 5px 14px;
        cursor: pointer;
        white-space: nowrap;
    }

    .rt-btn:hover {
        background: rgba(200, 230, 60, .22);
    }

    .esc-badge.success {
        background: rgba(63, 185, 80, .15);
        color: var(--ex-green);
        border: 1px solid rgba(63, 185, 80, .2);
    }

    .esc-badge.muted {
        background: rgba(125, 133, 144, .15);
        color: var(--ex-muted);
        border: 1px solid rgba(125, 133, 144, .2);
    }

    /* ── Dark-themed modal (Bootstrap defaults are light) ── */
    #timelineModal .modal-content {
        background: var(--ex-card);
        border: 1px solid var(--ex-bd);
        border-radius: 16px;
        color: var(--ex-txt);
    }

    #timelineModal .modal-header,
    #timelineModal .modal-footer {
        border-color: var(--ex-bd);
    }

    #timelineModal .btn-close {
        filter: invert(1) grayscale(100%) brightness(200%);
    }

    .tl-item {
        display: flex;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid rgba(48, 54, 61, .6);
    }

    .tl-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .tl-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: var(--ex-yg);
        flex-shrink: 0;
        margin-top: 5px;
    }

    .tl-time {
        font-size: 11px;
        color: var(--ex-muted);
        font-weight: 700;
    }

    .tl-status {
        font-size: 13px;
        font-weight: 700;
        color: var(--ex-txt);
        margin: 2px 0;
    }

    .tl-notes {
        font-size: 12px;
        color: var(--ex-muted);
    }

    /* ── Presence (online/offline) ── */
    .presence-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        flex-shrink: 0;
        display: inline-block;
    }

    .presence-dot.online {
        background: var(--ex-green);
        box-shadow: 0 0 0 0 rgba(63, 185, 80, .6);
        animation: presence-pulse 2s infinite;
    }

    .presence-dot.offline {
        background: var(--ex-muted);
    }

    @keyframes presence-pulse {
        0% { box-shadow: 0 0 0 0 rgba(63, 185, 80, .5); }
        70% { box-shadow: 0 0 0 5px rgba(63, 185, 80, 0); }
        100% { box-shadow: 0 0 0 0 rgba(63, 185, 80, 0); }
    }

    .presence-label {
        font-size: 12px;
        font-weight: 700;
    }

    .presence-label.online { color: var(--ex-green); }
    .presence-label.offline { color: var(--ex-muted); }

    /* ── Search + filter controls (All Active Tickets) ── */
    .ex-search-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
        background: var(--ex-card2);
        border: 1px solid var(--ex-bd);
        border-radius: 20px;
        padding: 7px 14px;
        flex: 1;
        min-width: 200px;
    }

    .ex-search-wrap input {
        background: transparent;
        border: none;
        outline: none;
        color: var(--ex-txt);
        font-size: 12px;
        width: 100%;
    }

    .ex-search-wrap input::placeholder { color: var(--ex-muted); }
    .ex-search-wrap i { color: var(--ex-muted); font-size: 12px; }

    .ex-filter-select {
        background: var(--ex-card2);
        border: 1px solid var(--ex-bd);
        border-radius: 20px;
        padding: 7px 14px;
        color: var(--ex-txt);
        font-size: 12px;
        font-weight: 700;
        outline: none;
    }

    .ex-pagination {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 12px;
        margin-top: 12px;
    }

    .ex-pg-btn {
        background: var(--ex-card2);
        color: var(--ex-txt);
        border: 1px solid var(--ex-bd);
        border-radius: 20px;
        font-size: 11px;
        font-weight: 800;
        padding: 5px 14px;
        cursor: pointer;
    }

    .ex-pg-btn:hover:not(:disabled) { border-color: var(--ex-yg); }
    .ex-pg-btn:disabled { opacity: .4; cursor: not-allowed; }

    .ex-pg-status {
        font-size: 11px;
        color: var(--ex-muted);
        font-weight: 700;
        white-space: nowrap;
    }
@endsection

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
                <div class="greeting-sub" id="greetingSub">
                    Here's your IT Support overview for {{ $rangeLabel }}.
                </div>
                <a href="{{ route('executive.tickets.index') }}"
                   style="display:inline-flex;align-items:center;gap:6px;margin-top:14px;background:var(--ex-yg);color:#161611;font-family:'Nunito',sans-serif;font-weight:800;font-size:13px;padding:9px 20px;border-radius:50px;text-decoration:none">
                    <i class="bi bi-inbox-fill"></i> View Support Request Queue
                </a>
            </div>
            <div class="d-flex gap-3 flex-wrap align-items-center">
                <div style="text-align:center">
                    <div style="font-family:'Nunito',sans-serif;font-weight:900;font-size:32px;color:var(--ex-yg);line-height:1"
                        data-stat="sla">
                        {{ $slaPercent }}%
                    </div>
                    <div
                        style="font-size:11px;color:rgba(255,255,255,.45);text-transform:uppercase;letter-spacing:.4px;margin-top:2px">
                        Overall SLA
                    </div>
                </div>
                <div style="width:1px;height:40px;background:rgba(255,255,255,.1)"></div>
                <div style="text-align:center">
                    <div style="font-family:'Nunito',sans-serif;font-weight:900;font-size:32px;color:var(--ex-green);line-height:1"
                        data-stat="rating">
                        {{ number_format($avgRating, 1) }}
                    </div>
                    <div
                        style="font-size:11px;color:rgba(255,255,255,.45);text-transform:uppercase;letter-spacing:.4px;margin-top:2px">
                        Avg Rating ⭐
                    </div>
                </div>
                <div style="width:1px;height:40px;background:rgba(255,255,255,.1)"></div>
                <div style="text-align:center">
                    <div style="font-family:'Nunito',sans-serif;font-weight:900;font-size:32px;color:#ff8888;line-height:1"
                        data-stat="breaches">
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
    <div class="section-label">Key Performance Indicators</div>
    <div class="kpi-grid mb-4">
        <div class="kpi-card yg">
            <div class="kpi-icon yg"><i class="bi bi-ticket-perforated"></i></div>
            <div class="kpi-value" data-kpi="totalTickets">{{ $totalTickets }}</div>
            <div class="kpi-label">Total Support Requests</div>
            <span class="kpi-trend {{ $ticketChange >= 0 ? 'up' : 'down' }}" data-kpi="ticketTrend">
                <i class="bi bi-arrow-{{ $ticketChange >= 0 ? 'up' : 'down' }}-short"></i>
                {{ $ticketChange >= 0 ? '+' : '' }}{{ $ticketChange }}%
            </span>
            <div class="kpi-compare" data-kpi="ticketCompare">vs. {{ $lastTotalTickets }} last month</div>
        </div>

        <div class="kpi-card green">
            <div class="kpi-icon green"><i class="bi bi-check-circle"></i></div>
            <div class="kpi-value" data-kpi="resolved">{{ $resolved }}</div>
            <div class="kpi-label">Resolved</div>
            <span class="kpi-trend {{ $resolvedChange >= 0 ? 'up' : 'down' }}" data-kpi="resolvedTrend">
                <i class="bi bi-arrow-{{ $resolvedChange >= 0 ? 'up' : 'down' }}-short"></i>
                {{ $resolvedChange >= 0 ? '+' : '' }}{{ $resolvedChange }}%
            </span>
            <div class="kpi-compare" data-kpi="resolvedCompare">{{ $resolutionRate }}% resolution rate</div>
        </div>

        <div class="kpi-card amber">
            <div class="kpi-icon amber"><i class="bi bi-clock-history"></i></div>
            <div class="kpi-value" data-kpi="avgTime">
                {{ $avgResolutionTime ? number_format($avgResolutionTime, 1) . 'h' : 'N/A' }}
            </div>
            <div class="kpi-label">Avg Resolution Time</div>
            <span class="kpi-trend {{ $avgTimeChange <= 0 ? 'up' : 'down' }}" data-kpi="avgTimeTrend">
                <i class="bi bi-arrow-{{ $avgTimeChange <= 0 ? 'down' : 'up' }}-short"></i>
                {{ $avgTimeChange > 0 ? '+' : '' }}{{ $avgTimeChange }}%
            </span>
            <div class="kpi-compare" data-kpi="avgTimeCompare">
                vs. {{ $lastAvgTime ? number_format($lastAvgTime, 1) . 'h' : 'N/A' }} last month
            </div>
        </div>

        <div class="kpi-card red">
            <div class="kpi-icon red"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="kpi-value" data-kpi="escalations">{{ $escalations }}</div>
            <div class="kpi-label">Escalations</div>
            <span class="kpi-trend {{ $escChange <= 0 ? 'up' : 'down' }}" data-kpi="escTrend">
                <i class="bi bi-arrow-{{ $escChange > 0 ? 'up' : 'down' }}-short"></i>
                {{ $escChange > 0 ? '+' : '' }}{{ $escChange }}
            </span>
            <div class="kpi-compare" data-kpi="escCompare">
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
                        <div class="chart-title">Support Request Volume Trend</div>
                        <div class="chart-sub" id="volumeChartSub">Support requests opened vs. resolved — {{ $rangeLabel }}</div>
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
                <div class="chart-sub" id="slaChartSub">By priority level — {{ $rangeLabel }}</div>
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
                <div class="chart-title">Support Requests by Category</div>
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
                <div class="chart-title">Support Requests by Department</div>
                <div class="chart-sub" id="deptChartSub">Volume — {{ $rangeLabel }} — color = severity</div>
                {{-- ── Tickets by Department ── --}}
                <div class="dept-grid mt-3" id="deptGrid">
                    @foreach($byDepartment as $dept)
                        @php
                            $heat = $dept->total >= 35 ? 'hot' : ($dept->total >= 20 ? 'warm' : 'cool');
                        @endphp
                        <div class="dept-cell {{ $heat }}">
                            <div class="dept-name">{{ $dept->department_name }}</div>
                            <div class="dept-count {{ $heat }}">{{ $dept->total }}</div>
                            <div class="dept-label">support requests</div>
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
                <div class="chart-sub" id="leaderboardSub">Ranked by support requests resolved — {{ $rangeLabel }}</div>
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
                    <tbody id="leaderboardBody">
                        @foreach($leaderboard as $index => $tech)
                            @php
                                $rank = $index + 1;
                                $rankClass = match ($rank) { 1 => 'gold', 2 => 'silver', 3 => 'bronze', default => ''};
                                $initials = collect(explode(' ', $tech->name))
                                    ->map(fn($w) => strtoupper($w[0]))->take(2)->join('');
                                $timeColor = $tech->avg_hours <= 2
                                    ? 'var(--ex-green)'
                                    : ($tech->avg_hours <= 3 ? 'var(--ex-amber)' : 'var(--ex-red)');
                                $maxResolved = $leaderboard->first()->resolved_count;
                                $barWidth = $maxResolved > 0
                                    ? round(($tech->resolved_count / $maxResolved) * 100) : 0;
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
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="row g-3 h-100">

                {{-- Month-over-month comparison --}}
                <div class="col-12">
                    <div class="chart-card">
                        <div class="chart-title">Period Comparison</div>
                        <div class="chart-sub" id="periodCompareSub">{{ $prevPeriodLabel }} vs. {{ $curPeriodLabel }}</div>
                        <div class="comparison-strip mt-3" id="momStrip">
                            <div class="cmp-col">
                                <div class="cmp-period" data-mom="lastMonth">Previous Period</div>
                                <div class="cmp-val" data-mom="lastTotal">{{ $lastTotalTickets }}</div>
                                <div class="cmp-diff" style="color:var(--ex-muted)">Total support requests</div>
                            </div>
                            <div class="cmp-col">
                                <div class="cmp-period" data-mom="curMonth">Current Period</div>
                                <div class="cmp-val" data-mom="curTotal">{{ $totalTickets }}</div>
                                <div class="cmp-diff {{ $totalTickets > $lastTotalTickets ? 'worse' : 'better' }}"
                                    data-mom="totalDiff">
                                    {{ $totalTickets > $lastTotalTickets ? '+' : '' }}{{ $totalTickets - $lastTotalTickets }}
                                    support requests
                                    {{ $totalTickets > $lastTotalTickets ? '↑' : '↓' }}
                                </div>
                            </div>
                            <div class="cmp-col">
                                <div class="cmp-period" data-mom="lastResolvePeriod">Previous Period</div>
                                <div class="cmp-val" data-mom="lastAvgTime">
                                    {{ $lastAvgTime ? number_format($lastAvgTime, 1) . 'h' : 'N/A' }}
                                </div>
                                <div class="cmp-diff" style="color:var(--ex-muted)">Resolution time</div>
                            </div>
                            <div class="cmp-col">
                                <div class="cmp-period" data-mom="curResolvePeriod">Current Period</div>
                                <div class="cmp-val" data-mom="curAvgTime">
                                    {{ $avgResolutionTime ? number_format($avgResolutionTime, 1) . 'h' : 'N/A' }}
                                </div>
                                @if($avgResolutionTime && $lastAvgTime)
                                    <div class="cmp-diff {{ $avgResolutionTime < $lastAvgTime ? 'better' : 'worse' }}"
                                        data-mom="avgTimeDiff">
                                        {{ $avgResolutionTime < $lastAvgTime ? '−' : '+' }}
                                        {{ number_format(abs($avgResolutionTime - $lastAvgTime), 1) }}h
                                        {{ $avgResolutionTime < $lastAvgTime ? 'faster ↓' : 'slower ↑' }}
                                    </div>
                                @endif
                            </div>
                            <div class="cmp-col">
                                <div class="cmp-period" data-mom="lastEscPeriod">Previous Period</div>
                                <div class="cmp-val" data-mom="lastEsc">{{ $lastEscalations }}</div>
                                <div class="cmp-diff" style="color:var(--ex-muted)">Escalations</div>
                            </div>
                            <div class="cmp-col">
                                <div class="cmp-period" data-mom="curEscPeriod">Current Period</div>
                                <div class="cmp-val" data-mom="curEsc">{{ $escalations }}</div>
                                <div class="cmp-diff {{ $escalations > $lastEscalations ? 'worse' : 'better' }}"
                                    data-mom="escDiff">
                                    {{ $escalations > $lastEscalations ? '+' : '' }}{{ $escalations - $lastEscalations }}
                                    {{ $escalations > $lastEscalations ? '↑' : '↓' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Escalations requiring attention --}}
                    <div class="col-12">
                        <div class="chart-card" id="escalationsSection">
                            @php $criticalCount = $openEscalations->where('status', 'Escalated')->count(); @endphp
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div>
                                    <div class="chart-title">Escalations Requiring Attention</div>
                                    <div class="chart-sub">Critical or SLA-breached support requests this month</div>
                                </div>
                                <span id="criticalBadge"
                                    style="background:rgba(248,81,73,.12);color:var(--ex-red);font-size:11px;font-weight:800;padding:4px 10px;border-radius:20px;border:1px solid rgba(248,81,73,.2)">
                                    {{ $criticalCount }} critical
                                </span>
                            </div>
                            <div class="mt-2" id="escalationsList">
                                @forelse($openEscalations as $esc)
                                    @php
                                        $isBreach = str_contains(strtolower($esc->reason ?? ''), 'sla') ||
                                            str_contains(strtolower($esc->status ?? ''), 'breach');
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
                                                {{ $esc->department_name }} dept · Escalated {{ $hoursAgo }}
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
                    <div class="chart-title">Weekly Support Request Breakdown</div>
                    <div class="chart-sub" id="weeklyChartSub">Support requests by status per week — {{ $rangeLabel }}</div>
                    <div class="chart-wrap mt-2"><canvas id="weeklyChart" height="100"></canvas></div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="chart-card h-100">
                    <div class="chart-title">Customer Satisfaction</div>
                    <div class="chart-sub" id="csatChartSub">Rating breakdown — {{ $rangeLabel }}</div>
                    <div class="text-center my-3">
                        <div style="font-family:'Nunito',sans-serif;font-weight:900;font-size:56px;line-height:1;color:var(--ex-yg)"
                            data-csat="avgRating">
                            {{ number_format($avgRating, 1) }}
                        </div>
                        <div style="font-size:22px;margin:4px 0">⭐⭐⭐⭐⭐</div>
                        <div style="font-size:12px;color:var(--ex-muted)" data-csat="totalFeedback">
                            Based on {{ $totalFeedback }} feedback responses
                        </div>
                    </div>
                    <div class="d-flex flex-column gap-2 mt-2" id="csatBars">
                        @foreach($csatBreakdown as $star => $data)
                            @php
                                $colors = [5 => 'var(--ex-green)', 4 => 'var(--ex-yg)', 3 => 'var(--ex-amber)', 2 => 'var(--ex-red)', 1 => '#5a2a2a'];
                            @endphp
                            <div class="d-flex align-items-center gap-2" data-csat-star="{{ $star }}">
                                <span
                                    style="font-size:12px;color:var(--ex-muted);width:18px;text-align:right">{{ $star }}★</span>
                                <div style="flex:1;height:8px;background:var(--ex-card2);border-radius:4px;overflow:hidden">
                                    <div class="csat-bar-fill"
                                        style="height:8px;background:{{ $colors[$star] }};border-radius:4px;width:{{ $data['percent'] }}%;transition:width .6s ease">
                                    </div>
                                </div>
                                <span class="csat-bar-pct" style="font-size:12px;font-weight:700;width:28px">
                                    {{ $data['percent'] }}%
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>

        {{-- ── Row 6: IT Team Status ── --}}
        <div class="row g-3 mb-2">
            <div class="col-12">
                <div class="chart-card">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div>
                            <div class="chart-title">IT Team Status</div>
                            <div class="chart-sub">Presence and workload for every active support-tier member</div>
                        </div>
                        <span id="itTeamOnlineBadge"
                            style="background:rgba(63,185,80,.12);color:var(--ex-green);font-size:11px;font-weight:800;padding:4px 10px;border-radius:20px;border:1px solid rgba(63,185,80,.2)">
                            {{ $itTeamStatus->where('online', true)->count() }} online
                        </span>
                    </div>
                    <div class="table-responsive">
                        <table class="lb-table mt-3">
                            <thead>
                                <tr>
                                    <th>Member</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Active Support Requests</th>
                                    <th style="text-align:right">Availability</th>
                                </tr>
                            </thead>
                            <tbody id="itTeamBody">
                                @forelse($itTeamStatus as $member)
                                    @php
                                        $initials = collect(explode(' ', $member->name))
                                            ->map(fn($w) => strtoupper($w[0] ?? ''))->take(2)->join('');
                                        $availBadge = match ($member->availability) {
                                            'free' => 'success',
                                            'busy' => 'open',
                                            default => 'breach',
                                        };
                                        $availLabel = ucfirst($member->availability);
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="lb-av">{{ $initials }}</div>
                                                <div class="lb-name">{{ $member->name }}</div>
                                            </div>
                                        </td>
                                        <td class="lb-role">{{ $member->role?->role_name ?? 'N/A' }}</td>
                                        <td>
                                            <span class="d-inline-flex align-items-center gap-2">
                                                <span class="presence-dot {{ $member->online ? 'online' : 'offline' }}"></span>
                                                <span class="presence-label {{ $member->online ? 'online' : 'offline' }}">
                                                    {{ $member->online ? 'Online' : 'Offline' }}
                                                </span>
                                            </span>
                                        </td>
                                        <td style="font-size:13px;font-weight:700;color:var(--ex-txt)">
                                            {{ $member->active_tickets }}
                                        </td>
                                        <td style="text-align:right">
                                            <span class="esc-badge {{ $availBadge }}">{{ $availLabel }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" style="text-align:center;color:var(--ex-muted);padding:20px">
                                            No IT team members found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Row 7: All Active Tickets ── --}}
        <div class="row g-3 mb-2">
            <div class="col-12">
                <div class="chart-card">
                    <div class="d-flex justify-content-between align-items-start mb-1 flex-wrap gap-2">
                        <div>
                            <div class="chart-title">All Active Support Requests</div>
                            <div class="chart-sub">Every open support request across the organization — click Timeline for the full history</div>
                        </div>
                        <a href="{{ route('executive.tickets.index') }}"
                           style="font-size:12px;font-weight:800;color:var(--ex-yg);text-decoration:none">
                            View Support Request Queue <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-3 mb-2">
                        <div class="ex-search-wrap">
                            <i class="bi bi-search"></i>
                            <input type="text" id="activeTicketsSearch"
                                   placeholder="Search support request #, subject, or requester…" autocomplete="off">
                        </div>
                        <select class="ex-filter-select" id="activeTicketsStatus">
                            <option value="">All Statuses</option>
                            @foreach($activeTicketStatuses as $st)
                                <option value="{{ $st }}">{{ $st }}</option>
                            @endforeach
                        </select>
                        <select class="ex-filter-select" id="activeTicketsPriority">
                            <option value="">All Priorities</option>
                            <option value="Critical">Critical</option>
                            <option value="High">High</option>
                            <option value="Medium">Medium</option>
                            <option value="Low">Low</option>
                        </select>
                    </div>
                    <div class="table-responsive">
                        <table class="lb-table mt-1">
                            <thead>
                                <tr>
                                    <th>Support Request</th>
                                    <th>Requester</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Opened</th>
                                    <th style="text-align:right">Timeline</th>
                                </tr>
                            </thead>
                            <tbody id="activeTicketsBody">
                                @forelse($activeTickets as $ticket)
                                    @php
                                        $statusLower = strtolower($ticket->status ?? '');
                                        $badgeClass = match (true) {
                                            str_contains($statusLower, 'escalated') => 'breach',
                                            str_contains($statusLower, 'awaiting') || str_contains($statusLower, 'pending') => 'open',
                                            str_contains($statusLower, 'progress') => 'admin',
                                            default => 'muted',
                                        };
                                        $priorityColor = match ($ticket->ticket_type) {
                                            'Critical' => 'var(--ex-critical)',
                                            'High' => 'var(--ex-red)',
                                            'Medium' => 'var(--ex-amber)',
                                            'Low' => 'var(--ex-green)',
                                            default => 'var(--ex-muted)',
                                        };
                                    @endphp
                                    <tr>
                                        <td>
                                            <div style="font-weight:800;font-size:13px;color:var(--ex-txt)">
                                                #{{ $ticket->ticket_number }}
                                            </div>
                                            <div style="font-size:12px;color:var(--ex-muted)">
                                                {{ Str::limit($ticket->subject, 40) }}
                                            </div>
                                        </td>
                                        <td style="font-size:12px;color:var(--ex-muted)">
                                            {{ $ticket->user->name ?? 'Unknown' }}
                                        </td>
                                        <td>
                                            <span class="esc-badge {{ $badgeClass }}">{{ $ticket->status }}</span>
                                        </td>
                                        <td>
                                            <span style="font-size:12px;font-weight:700;color:{{ $priorityColor }}">
                                                {{ $ticket->ticket_type ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td style="font-size:12px;color:var(--ex-muted)">
                                            {{ $ticket->created_at?->diffForHumans() }}
                                        </td>
                                        <td style="text-align:right">
                                            <button type="button" class="rt-btn"
                                                    onclick="openTimelineModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                                                <i class="bi bi-clock-history me-1"></i>Timeline
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" style="text-align:center;color:var(--ex-muted);padding:20px">
                                            No active tickets.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="ex-pagination" id="activeTicketsPagination">
                        <span class="ex-pg-status" id="activeTicketsStatusText">
                            Page {{ $activeTickets->currentPage() }} of {{ $activeTickets->lastPage() }}
                            ({{ $activeTickets->total() }} total)
                        </span>
                        <button type="button" class="ex-pg-btn" id="activeTicketsPrev"
                                {{ $activeTickets->onFirstPage() ? 'disabled' : '' }}>
                            <i class="bi bi-chevron-left"></i> Prev
                        </button>
                        <button type="button" class="ex-pg-btn" id="activeTicketsNext"
                                {{ $activeTickets->hasMorePages() ? '' : 'disabled' }}>
                            Next <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

@endsection

{{-- ══ MODALS ══ --}}
@section('modals')
    <div class="modal fade" id="timelineModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="mb-0">Support Request Timeline — <em id="timelineRef" style="color:var(--ex-yg)"></em></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-3" style="max-height:60vh;overflow-y:auto">
                    <div id="timelineBody">
                        <div class="text-center py-4" style="color:var(--ex-muted)">
                            <div class="spinner-border spinner-border-sm me-2"></div>
                            Loading timeline…
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="rt-btn" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

    {{-- ══ CHART SCRIPTS ══ --}}
    @section('scripts')
        <script>
            $(function () {

                /* ══ CHART INSTANCES — stored so we can update them later ══ */
                let volumeChartInstance = null;
                let categoryChartInstance = null;
                let resTimeChartInstance = null;
                let weeklyChartInstance = null;
                let gaugeHighInstance = null;
                let gaugeMedInstance = null;
                let gaugeLowInstance = null;

                /* ══ INIT ALL CHARTS ══ */
                function initCharts(data) {

                    /* ── Volume trend ── */
                    if (volumeChartInstance) volumeChartInstance.destroy();
                    volumeChartInstance = new Chart(document.getElementById('volumeChart'), {
                        type: 'line',
                        data: {
                            labels: data.volumeDays,
                            datasets: [
                                { label: 'Opened', data: data.volumeOpened, borderColor: '#c8e63c', backgroundColor: 'rgba(200,230,60,.08)', borderWidth: 2, pointRadius: 0, tension: .4, fill: true },
                                { label: 'Resolved', data: data.volumeResolved, borderColor: '#3fb950', backgroundColor: 'rgba(63,185,80,.06)', borderWidth: 2, pointRadius: 0, tension: .4, fill: true }
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
                        const existing = Chart.getChart(id);
                        if (existing) existing.destroy();

                        return new Chart(document.getElementById(id), {
                            type: 'doughnut',
                            data: { datasets: [{ data: [pct, 100 - pct], backgroundColor: [color, 'rgba(48,54,61,.8)'], borderWidth: 0, borderRadius: 4, spacing: 2 }] },
                            options: { cutout: '72%', responsive: false, plugins: { legend: { display: false }, tooltip: { enabled: false } } }
                        });
                    }

                    gaugeHighInstance = makeGauge('gHigh', data.slaByPriority?.High ?? 100, '#f85149');
                    gaugeMedInstance = makeGauge('gMed', data.slaByPriority?.Medium ?? 100, '#d29922');
                    gaugeLowInstance = makeGauge('gLow', data.slaByPriority?.Low ?? 100, '#3fb950');

                    /* ── Update gauge center labels ── */
                    const gHighEl = document.querySelector('#gHigh');
                    const gMedEl = document.querySelector('#gMed');
                    const gLowEl = document.querySelector('#gLow');
                    if (gHighEl) gHighEl.closest('.gauge-ring').querySelector('.gauge-pct').textContent = (data.slaByPriority?.High ?? 100) + '%';
                    if (gMedEl) gMedEl.closest('.gauge-ring').querySelector('.gauge-pct').textContent = (data.slaByPriority?.Medium ?? 100) + '%';
                    if (gLowEl) gLowEl.closest('.gauge-ring').querySelector('.gauge-pct').textContent = (data.slaByPriority?.Low ?? 100) + '%';

                    /* ── Category donut ── */
                    if (categoryChartInstance) categoryChartInstance.destroy();
                    const catColors = ['#f5c842', '#58a6ff', '#c8e63c', '#3fb950', '#f85149', '#d29922'];
                    categoryChartInstance = new Chart(document.getElementById('categoryChart'), {
                        type: 'doughnut',
                        data: {
                            labels: data.byCategory.map(c => c.request_category),
                            datasets: [{ data: data.byCategory.map(c => c.total), backgroundColor: catColors.slice(0, data.byCategory.length), borderWidth: 0, borderRadius: 4, spacing: 2 }]
                        },
                        options: { cutout: '65%', responsive: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => ' ' + c.label + ': ' + c.raw + ' support requests' } } } }
                    });

                    /* ── Resolution time bar ── */
                    if (resTimeChartInstance) resTimeChartInstance.destroy();
                    resTimeChartInstance = new Chart(document.getElementById('resTimeChart'), {
                        type: 'bar',
                        data: {
                            labels: data.resTimeByCategory.map(r => r.request_category),
                            datasets: [{ data: data.resTimeByCategory.map(r => r.avg_hours), backgroundColor: ['#f85149', '#58a6ff', '#d29922', '#3fb950', '#c8e63c'], borderRadius: 6, borderSkipped: false }]
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
                    if (weeklyChartInstance) weeklyChartInstance.destroy();
                    weeklyChartInstance = new Chart(document.getElementById('weeklyChart'), {
                        type: 'bar',
                        data: {
                            labels: data.weeklyData.map(w => w.label),
                            datasets: [
                                { label: 'Resolved', data: data.weeklyData.map(w => w.resolved), backgroundColor: '#3fb950', borderRadius: 4, stack: 's' },
                                { label: 'In Progress', data: data.weeklyData.map(w => w.inProgress), backgroundColor: '#d29922', borderRadius: 0, stack: 's' },
                                { label: 'Escalated', data: data.weeklyData.map(w => w.escalated), backgroundColor: '#f85149', borderRadius: 0, stack: 's' },
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
                }

                function updateKPIs(data) {

                    function animateNum(el, newVal) {
                        if (!el || el.textContent.trim() === String(newVal)) return;
                        el.style.transition = 'opacity .3s';
                        el.style.opacity = '0';
                        setTimeout(() => {
                            el.textContent = newVal;
                            el.style.opacity = '1';
                        }, 300);
                    }

                    // ── Greeting strip
                    const slaEl = document.querySelector('[data-stat="sla"]');
                    const ratingEl = document.querySelector('[data-stat="rating"]');
                    const breachEl = document.querySelector('[data-stat="breaches"]');
                    animateNum(slaEl, (data.slaPercent ?? 100) + '%');
                    animateNum(ratingEl, data.avgRating ? parseFloat(data.avgRating).toFixed(1) : '0.0');
                    animateNum(breachEl, data.slaBreach ?? 0);

                    // ── KPI cards
                    animateNum(document.querySelector('[data-kpi="totalTickets"]'), data.totalTickets);
                    animateNum(document.querySelector('[data-kpi="resolved"]'), data.resolved);
                    animateNum(document.querySelector('[data-kpi="avgTime"]'),
                        data.avgResolutionTime ? data.avgResolutionTime + 'h' : 'N/A');
                    animateNum(document.querySelector('[data-kpi="escalations"]'), data.escalations);

                    // ── KPI compare texts
                    const tChange = data.lastTotalTickets > 0
                        ? Math.round(((data.totalTickets - data.lastTotalTickets) / data.lastTotalTickets) * 100) : 0;
                    const rChange = data.lastResolved > 0
                        ? Math.round(((data.resolved - data.lastResolved) / data.lastResolved) * 100) : 0;
                    const resRate = data.totalTickets > 0
                        ? (data.resolved / data.totalTickets * 100).toFixed(1) : 0;
                    const escChange = data.escalations - (data.lastEscalations ?? 0);

                    animateNum(document.querySelector('[data-kpi="ticketCompare"]'),
                        `vs. ${data.lastTotalTickets} last month`);
                    animateNum(document.querySelector('[data-kpi="resolvedCompare"]'),
                        `${resRate}% resolution rate`);
                    animateNum(document.querySelector('[data-kpi="avgTimeCompare"]'),
                        `vs. ${data.lastAvgTime ? data.lastAvgTime + 'h' : 'N/A'} last month`);
                    animateNum(document.querySelector('[data-kpi="escCompare"]'),
                        `${(data.totalTickets > 0 ? (data.escalations / data.totalTickets * 100).toFixed(1) : 0)}% escalation rate`);

                    // ── Month-over-Month
                    animateNum(document.querySelector('[data-mom="lastTotal"]'), data.lastTotalTickets);
                    animateNum(document.querySelector('[data-mom="curTotal"]'), data.totalTickets);
                    animateNum(document.querySelector('[data-mom="lastAvgTime"]'),
                        data.lastAvgTime ? data.lastAvgTime + 'h' : 'N/A');
                    animateNum(document.querySelector('[data-mom="curAvgTime"]'),
                        data.avgResolutionTime ? data.avgResolutionTime + 'h' : 'N/A');
                    animateNum(document.querySelector('[data-mom="lastEsc"]'), data.lastEscalations ?? 0);
                    animateNum(document.querySelector('[data-mom="curEsc"]'), data.escalations);

                    const totalDiffEl = document.querySelector('[data-mom="totalDiff"]');
                    if (totalDiffEl) {
                        const diff = data.totalTickets - (data.lastTotalTickets ?? 0);
                        totalDiffEl.textContent = `${diff > 0 ? '+' : ''}${diff} support requests ${diff > 0 ? '↑' : '↓'}`;
                        totalDiffEl.className = `cmp-diff ${diff > 0 ? 'worse' : 'better'}`;
                    }

                    const escDiffEl = document.querySelector('[data-mom="escDiff"]');
                    if (escDiffEl) {
                        const diff = data.escalations - (data.lastEscalations ?? 0);
                        escDiffEl.textContent = `${diff > 0 ? '+' : ''}${diff} ${diff > 0 ? '↑' : '↓'}`;
                        escDiffEl.className = `cmp-diff ${diff > 0 ? 'worse' : 'better'}`;
                    }

                    // ── Escalations list
                    const escList = document.getElementById('escalationsList');
                    const critBadge = document.getElementById('criticalBadge');
                    if (escList && data.openEscalations) {
                        const critCount = data.openEscalations.length;
                        if (critBadge) critBadge.textContent = `${critCount} critical`;

                        if (!critCount) {
                            escList.innerHTML = `
                    <div style="text-align:center;color:var(--ex-muted);padding:20px">
                        ✅ No open escalations right now.
                    </div>`;
                        } else {
                            escList.innerHTML = data.openEscalations.map(esc => {
                                const isBreach = (esc.reason || '').toLowerCase().includes('sla');
                                const hoursAgo = timeAgo(esc.escalated_at);
                                return `
                        <div class="esc-item">
                            <div class="esc-icon ${isBreach ? 'crit' : 'warn'}">
                                <i class="bi bi-${isBreach ? 'exclamation-triangle-fill' : 'exclamation-circle'}"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="esc-title">${escHtml(esc.subject)}</div>
                                <div class="esc-meta">
                                    ${escHtml(esc.department_name)} dept · Escalated ${hoursAgo}
                                    ${esc.prev_tech ? `· ${escHtml(esc.prev_tech)} → ${escHtml(esc.reassigned_to_name || 'Unassigned')}` : ''}
                                </div>
                            </div>
                            <span class="esc-badge ${isBreach ? 'breach' : 'open'}">
                                ${isBreach ? 'SLA Breach' : 'Open'}
                            </span>
                        </div>`;
                            }).join('');
                        }
                    }

                    // ── Leaderboard
                    const lbBody = document.getElementById('leaderboardBody');
                    if (lbBody && data.leaderboard && data.leaderboard.length) {
                        const maxResolved = data.leaderboard[0].resolved_count;
                        const rankClasses = ['gold', 'silver', 'bronze', '', ''];
                        lbBody.innerHTML = data.leaderboard.map((tech, i) => {
                            const initials = tech.name.split(' ').map(w => w[0].toUpperCase()).slice(0, 2).join('');
                            const barWidth = maxResolved > 0 ? Math.round((tech.resolved_count / maxResolved) * 100) : 0;
                            const timeColor = tech.avg_hours <= 2
                                ? 'var(--ex-green)' : (tech.avg_hours <= 3 ? 'var(--ex-amber)' : 'var(--ex-red)');
                            return `
                    <tr>
                        <td class="lb-rank ${rankClasses[i] || ''}">${i + 1}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="lb-av">${initials}</div>
                                <div>
                                    <div class="lb-name">${escHtml(tech.name)}</div>
                                    <div class="lb-role">${escHtml(tech.position)}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="font-family:'Nunito',sans-serif;font-weight:800;font-size:14px">
                                ${tech.resolved_count}
                            </div>
                            <div class="lb-bar-wrap">
                                <div class="lb-bar" style="width:${barWidth}%"></div>
                            </div>
                        </td>
                        <td style="font-size:13px;font-weight:700;color:${timeColor}">
                            ${tech.avg_hours ?? 'N/A'}h
                        </td>
                        <td class="lb-rating">
                            <div class="lb-num">${tech.avg_rating ?? 'N/A'} ⭐</div>
                        </td>
                    </tr>`;
                        }).join('');
                    }

                    // ── CSAT
                    if (data.csatBreakdown) {
                        animateNum(document.querySelector('[data-csat="avgRating"]'),
                            data.avgRating ? parseFloat(data.avgRating).toFixed(1) : '0.0');
                        animateNum(document.querySelector('[data-csat="totalFeedback"]'),
                            `Based on ${data.totalFeedback ?? 0} feedback responses`);

                        const colors = { 5: 'var(--ex-green)', 4: 'var(--ex-yg)', 3: 'var(--ex-amber)', 2: 'var(--ex-red)', 1: '#5a2a2a' };
                        [5, 4, 3, 2, 1].forEach(star => {
                            const row = document.querySelector(`[data-csat-star="${star}"]`);
                            if (!row || !data.csatBreakdown[star]) return;
                            const pct = data.csatBreakdown[star].percent;
                            const barEl = row.querySelector('.csat-bar-fill');
                            const pctEl = row.querySelector('.csat-bar-pct');
                            if (barEl) barEl.style.width = pct + '%';
                            if (pctEl) pctEl.textContent = pct + '%';
                        });
                    }

                    // ── Range-aware section labels
                    if (data.rangeLabel) {
                        const subs = {
                            greetingSub: `Here's your IT Support overview for ${data.rangeLabel}.`,
                            volumeChartSub: `Support requests opened vs. resolved — ${data.rangeLabel}`,
                            slaChartSub: `By priority level — ${data.rangeLabel}`,
                            deptChartSub: `Volume — ${data.rangeLabel} — color = severity`,
                            leaderboardSub: `Ranked by support requests resolved — ${data.rangeLabel}`,
                            weeklyChartSub: `Support requests by status per week — ${data.rangeLabel}`,
                            csatChartSub: `Rating breakdown — ${data.rangeLabel}`,
                        };
                        Object.entries(subs).forEach(([id, text]) => {
                            const el = document.getElementById(id);
                            if (el) el.textContent = text;
                        });
                    }
                    if (data.prevPeriodLabel && data.curPeriodLabel) {
                        const cmpSub = document.getElementById('periodCompareSub');
                        if (cmpSub) cmpSub.textContent = `${data.prevPeriodLabel} vs. ${data.curPeriodLabel}`;
                    }

                    // ── Tickets by Department heatmap
                    const deptGrid = document.getElementById('deptGrid');
                    if (deptGrid && data.byDepartment) {
                        deptGrid.innerHTML = data.byDepartment.map(dept => {
                            const heat = dept.total >= 35 ? 'hot' : (dept.total >= 20 ? 'warm' : 'cool');
                            return `
                                <div class="dept-cell ${heat}">
                                    <div class="dept-name">${escHtml(dept.department_name)}</div>
                                    <div class="dept-count ${heat}">${dept.total}</div>
                                    <div class="dept-label">support requests</div>
                                </div>`;
                        }).join('') || '<div style="color:var(--ex-muted);font-size:12px;grid-column:1/-1">No data for this period.</div>';
                    }

                    // ── IT Team Status
                    const itTeamBody = document.getElementById('itTeamBody');
                    if (itTeamBody && data.itTeamStatus) {
                        const onlineBadge = document.getElementById('itTeamOnlineBadge');
                        const onlineCount = data.itTeamStatus.filter(m => m.online).length;
                        if (onlineBadge) onlineBadge.textContent = `${onlineCount} online`;

                        if (!data.itTeamStatus.length) {
                            itTeamBody.innerHTML = `<tr><td colspan="5" style="text-align:center;color:var(--ex-muted);padding:20px">No IT team members found.</td></tr>`;
                        } else {
                            itTeamBody.innerHTML = data.itTeamStatus.map(member => {
                                const initials = (member.name || '').split(' ').filter(Boolean)
                                    .map(w => w[0].toUpperCase()).slice(0, 2).join('');
                                const availability = member.availability || 'full';
                                const availBadge = availability === 'free' ? 'success' : (availability === 'busy' ? 'open' : 'breach');
                                const availLabel = availability.charAt(0).toUpperCase() + availability.slice(1);
                                const presenceClass = member.online ? 'online' : 'offline';
                                return `
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="lb-av">${initials}</div>
                                                <div class="lb-name">${escHtml(member.name)}</div>
                                            </div>
                                        </td>
                                        <td class="lb-role">${escHtml(member.role?.role_name ?? 'N/A')}</td>
                                        <td>
                                            <span class="d-inline-flex align-items-center gap-2">
                                                <span class="presence-dot ${presenceClass}"></span>
                                                <span class="presence-label ${presenceClass}">${member.online ? 'Online' : 'Offline'}</span>
                                            </span>
                                        </td>
                                        <td style="font-size:13px;font-weight:700;color:var(--ex-txt)">${member.active_tickets ?? 0}</td>
                                        <td style="text-align:right"><span class="esc-badge ${availBadge}">${availLabel}</span></td>
                                    </tr>`;
                            }).join('');
                        }
                    }
                }

                // ── Helper: time ago
                function timeAgo(dateStr) {
                    const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
                    if (diff < 60) return `${diff} seconds ago`;
                    if (diff < 3600) return `${Math.floor(diff / 60)} minutes ago`;
                    if (diff < 86400) return `${Math.floor(diff / 3600)} hours ago`;
                    return `${Math.floor(diff / 86400)} days ago`;
                }

                // ── Helper: escape HTML
                function escHtml(str) {
                    return String(str || '')
                        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                }

                /* ══ ALL ACTIVE TICKETS — search/filter/paginate (AJAX, no page reload) ══ */
                let activeTicketsPage = 1;
                let activeTicketsTimer = null;

                function activeTicketsBadgeClass(status) {
                    const s = (status || '').toLowerCase();
                    if (s.includes('escalated')) return 'breach';
                    if (s.includes('awaiting') || s.includes('pending')) return 'open';
                    if (s.includes('progress')) return 'admin';
                    return 'muted';
                }

                function priorityColor(priority) {
                    return priority === 'Critical' ? 'var(--ex-critical)'
                        : priority === 'High' ? 'var(--ex-red)'
                            : priority === 'Medium' ? 'var(--ex-amber)'
                                : priority === 'Low' ? 'var(--ex-green)'
                                    : 'var(--ex-muted)';
                }

                function renderActiveTickets(data) {
                    const body = document.getElementById('activeTicketsBody');
                    if (!body) return;

                    if (!data.tickets.length) {
                        body.innerHTML = `<tr><td colspan="6" style="text-align:center;color:var(--ex-muted);padding:20px">No active support requests match your filters.</td></tr>`;
                    } else {
                        body.innerHTML = data.tickets.map(t => `
                            <tr>
                                <td>
                                    <div style="font-weight:800;font-size:13px;color:var(--ex-txt)">#${escHtml(t.ticket_number)}</div>
                                    <div style="font-size:12px;color:var(--ex-muted)">${escHtml((t.subject || '').slice(0, 40))}</div>
                                </td>
                                <td style="font-size:12px;color:var(--ex-muted)">${escHtml(t.requester_name)}</td>
                                <td><span class="esc-badge ${activeTicketsBadgeClass(t.status)}">${escHtml(t.status)}</span></td>
                                <td><span style="font-size:12px;font-weight:700;color:${priorityColor(t.priority)}">${escHtml(t.priority || 'N/A')}</span></td>
                                <td style="font-size:12px;color:var(--ex-muted)">${timeAgo(t.created_at)}</td>
                                <td style="text-align:right">
                                    <button type="button" class="rt-btn" onclick="openTimelineModal('${t.id}', '${escHtml(t.ticket_number)}')">
                                        <i class="bi bi-clock-history me-1"></i>Timeline
                                    </button>
                                </td>
                            </tr>`).join('');
                    }

                    const statusText = document.getElementById('activeTicketsStatusText');
                    if (statusText) {
                        statusText.textContent = `Page ${data.pagination.current_page} of ${data.pagination.last_page} (${data.pagination.total} total)`;
                    }
                    const prevBtn = document.getElementById('activeTicketsPrev');
                    const nextBtn = document.getElementById('activeTicketsNext');
                    if (prevBtn) prevBtn.disabled = data.pagination.current_page <= 1;
                    if (nextBtn) nextBtn.disabled = data.pagination.current_page >= data.pagination.last_page;

                    activeTicketsPage = data.pagination.current_page;
                }

                function fetchActiveTickets(page) {
                    const params = new URLSearchParams({
                        page: page || activeTicketsPage,
                        search: document.getElementById('activeTicketsSearch')?.value || '',
                        status: document.getElementById('activeTicketsStatus')?.value || '',
                        priority: document.getElementById('activeTicketsPriority')?.value || '',
                    });

                    fetch('{{ route('executive.dashboard.active-tickets') }}?' + params.toString(), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                    })
                        .then(r => r.json())
                        .then(renderActiveTickets)
                        .catch(() => { });
                }

                document.getElementById('activeTicketsSearch')?.addEventListener('input', function () {
                    clearTimeout(activeTicketsTimer);
                    activeTicketsTimer = setTimeout(() => fetchActiveTickets(1), 400);
                });
                document.getElementById('activeTicketsStatus')?.addEventListener('change', () => fetchActiveTickets(1));
                document.getElementById('activeTicketsPriority')?.addEventListener('change', () => fetchActiveTickets(1));
                document.getElementById('activeTicketsPrev')?.addEventListener('click', () => fetchActiveTickets(activeTicketsPage - 1));
                document.getElementById('activeTicketsNext')?.addEventListener('click', () => fetchActiveTickets(activeTicketsPage + 1));

                /* ══ FETCH FRESH DATA FROM API — driven by the selected date range ══ */
                let currentRange = '{{ $range }}';

                function fetchExecutiveData() {
                    if (document.hidden) return; // Don't fetch if tab is hidden

                    fetch('/executive/dashboard/data?range=' + encodeURIComponent(currentRange), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        }
                    })
                        .then(r => {
                            if (!r.ok) throw new Error('HTTP ' + r.status);
                            return r.json();
                        })
                        .then(data => {
                            updateKPIs(data);
                            initCharts(data);
                            fetchActiveTickets(activeTicketsPage);
                        })
                        .catch(() => { }); // Silent fail
                }

                // ── Date-range buttons (7D/30D/90D/YTD) — event fired by layouts/executive.blade.php
                $(document).on('rangeChange', function (e, range) {
                    currentRange = range;
                    fetchExecutiveData();
                });

                /* ══ INIT ON PAGE LOAD with existing PHP data ══ */
                initCharts({
                    volumeDays: {!! json_encode($volumeDays) !!},
                    volumeOpened: {!! json_encode($volumeOpened) !!},
                    volumeResolved: {!! json_encode($volumeResolved) !!},
                    slaByPriority: {!! json_encode($slaByPriority ?? []) !!},
                    byCategory: {!! json_encode($byCategory) !!},
                    resTimeByCategory: {!! json_encode($resTimeByCategory) !!},
                    weeklyData: {!! json_encode($weeklyData) !!},
                });

                /* ══ AUTO-REFRESH every 30 seconds ══ */
                let refreshTimer = setInterval(fetchExecutiveData, 30000);

                // ── Pause when tab hidden, resume + instant refresh when visible
                document.addEventListener('visibilitychange', function () {
                    if (document.hidden) {
                        clearInterval(refreshTimer);
                    } else {
                        fetchExecutiveData();
                        refreshTimer = setInterval(fetchExecutiveData, 30000);
                    }
                });

                /* ══ Ticket timeline modal ══ */
                window.openTimelineModal = function (ticketId, ticketNumber) {
                    $('#timelineRef').text('#' + ticketNumber);
                    $('#timelineBody').html(`
                        <div class="text-center py-4" style="color:var(--ex-muted)">
                            <div class="spinner-border spinner-border-sm me-2"></div>
                            Loading timeline…
                        </div>
                    `);
                    new bootstrap.Modal('#timelineModal').show();

                    fetch('{{ url('/executive/tickets') }}/' + ticketId + '/history')
                        .then(r => r.json())
                        .then(data => {
                            const histories = data.status_histories || [];
                            if (!histories.length) {
                                $('#timelineBody').html('<div style="color:var(--ex-muted);font-size:13px">No history available.</div>');
                                return;
                            }
                            let html = '';
                            histories
                                .slice()
                                .sort((a, b) => new Date(b.changed_at) - new Date(a.changed_at))
                                .forEach(h => {
                                    const date = new Date(h.changed_at).toLocaleString('en-PH', {
                                        month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit'
                                    });
                                    const by = h.changed_by?.name ?? 'System';
                                    html += `
                                        <div class="tl-item">
                                            <div class="tl-dot"></div>
                                            <div>
                                                <div class="tl-time">${date} · ${by}</div>
                                                <div class="tl-status">${h.old_status ?? '—'} → ${h.new_status}</div>
                                                <div class="tl-notes">${h.notes ?? ''}</div>
                                            </div>
                                        </div>
                                    `;
                                });
                            $('#timelineBody').html(html);
                        })
                        .catch(() => {
                            $('#timelineBody').html('<div style="color:var(--ex-red)">Failed to load timeline.</div>');
                        });
                };

            });
        </script>
    @endsection