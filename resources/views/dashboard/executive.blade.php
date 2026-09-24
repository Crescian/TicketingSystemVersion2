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

    /* ── Aging report table: category rows bold/tinted, status rows indented under them ── */
    .aging-cat-row td {
        font-size: 13px;
        font-weight: 800;
        color: var(--ex-txt);
        background: rgba(200, 230, 60, .03);
    }

    .aging-status-row td {
        font-size: 12px;
    }

    .aging-status-label {
        padding-left: 28px !important;
        color: var(--ex-muted);
    }

    .aging-status-label::before {
        content: '↳';
        margin-right: 6px;
        color: var(--ex-bd);
    }

    .aging-total-row td {
        font-weight: 900;
        font-size: 13px;
        color: var(--ex-yg);
        border-top: 2px solid var(--ex-bd);
        background: rgba(200, 230, 60, .05);
    }

    /* ── Per-category collapse toggle in the Category & Status aging table ── */
    .aging-toggle-btn {
        background: none;
        border: none;
        padding: 0;
        margin: 0 4px 0 0;
        cursor: pointer;
        color: var(--ex-muted);
        line-height: 1;
        vertical-align: -1px;
    }

    .aging-toggle-btn:hover { color: var(--ex-yg); }

    .aging-toggle-btn i { transition: transform .15s ease; }

    .aging-cat-row.aging-collapsed .aging-toggle-btn i { transform: rotate(-90deg); }

    /* ── Clickable aging counts — drill into the matching support request list ── */
    .aging-cell-btn {
        background: none;
        border: none;
        padding: 0;
        margin: 0;
        font: inherit;
        font-weight: 800;
        cursor: pointer;
        color: inherit;
    }

    .aging-cell-btn:hover {
        text-decoration: underline;
        opacity: .85;
    }

    .aging-total-badge {
        cursor: pointer;
        font: inherit;
    }

    .aging-total-badge:hover {
        opacity: .85;
    }

    /* ── Dark-themed modal (Bootstrap defaults are light) ── */
    #timelineModal .modal-content,
    #agingListModal .modal-content,
    #resTopModal .modal-content {
        background: var(--ex-card);
        border: 1px solid var(--ex-bd);
        border-radius: 16px;
        color: var(--ex-txt);
    }

    #timelineModal .modal-header,
    #timelineModal .modal-footer,
    #agingListModal .modal-header,
    #agingListModal .modal-footer,
    #resTopModal .modal-header,
    #resTopModal .modal-footer {
        border-color: var(--ex-bd);
    }

    #timelineModal .btn-close,
    #agingListModal .btn-close,
    #resTopModal .btn-close {
        filter: invert(1) grayscale(100%) brightness(200%);
    }

    /* ── Aging list modal: header row + zebra-free table match the rest of the dark UI ── */
    #agingListModal .modal-header {
        align-items: flex-start;
    }

    #agingListModal .lb-table thead th,
    #resTopModal .lb-table thead th {
        background: var(--ex-card2);
    }

    #agingListModal .lb-table tbody tr:hover,
    #resTopModal .lb-table tbody tr:hover {
        background: rgba(200, 230, 60, .04);
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

    .esc-badges {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 4px;
        flex-shrink: 0;
    }

    .esc-meta strong { color: var(--ex-txt); font-weight: 700; }

    .itteam-aging-select {
        background: var(--ex-card2);
        color: var(--ex-txt);
        border: 1px solid var(--ex-bd);
        border-radius: 8px;
        padding: 4px 8px;
        font-size: 12px;
        font-weight: 700;
    }

    .itteam-aging-select:focus { outline: none; border-color: var(--ex-yg); }

    /* ── Floating IT Team Status ── */
    .itteam-fab {
        position: fixed;
        right: 24px;
        top: 50%;
        transform: translateY(-50%);
        z-index: 1040;
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        border: 1px solid var(--ex-bd);
        border-radius: 50%;
        background: var(--ex-card2);
        color: var(--ex-yg);
        font-size: 22px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, .45);
        cursor: pointer;
        transition: border-color .15s, box-shadow .15s;
    }

    .itteam-fab:hover,
    .itteam-fab[aria-expanded="true"] {
        border-color: var(--ex-yg);
        box-shadow: 0 8px 24px rgba(0, 0, 0, .45), 0 0 0 3px rgba(200, 230, 60, .15);
    }

    .itteam-fab-count {
        position: absolute;
        top: -4px;
        right: -4px;
        min-width: 20px;
        height: 20px;
        padding: 0 5px;
        border: 2px solid var(--ex-bg);
        border-radius: 999px;
        background: var(--ex-green);
        color: var(--ex-bg);
        font-size: 10px;
        font-weight: 800;
        line-height: 16px;
        text-align: center;
    }

    .itteam-float {
        position: fixed;
        right: 92px;
        top: 50%;
        transform: translateY(-50%);
        z-index: 1040;
        width: min(760px, calc(100vw - 124px));
        max-height: calc(100vh - 48px);
        overflow-y: auto;
        border-radius: 12px;
        box-shadow: 0 16px 48px rgba(0, 0, 0, .55);
    }

    .itteam-float .chart-card { margin: 0; }

    @media (max-width: 640px) {
        .itteam-fab { right: 16px; }
        .itteam-float { right: 16px; left: 16px; width: auto; top: 24px; transform: none; max-height: calc(100vh - 48px); }
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
                @if($user->role->role_name === 'Manager')
                    <a href="{{ route('executive.tickets.index') }}"
                       style="display:inline-flex;align-items:center;gap:6px;margin-top:14px;background:var(--ex-yg);color:#161611;font-family:'Nunito',sans-serif;font-weight:800;font-size:13px;padding:9px 20px;border-radius:50px;text-decoration:none">
                        <i class="bi bi-inbox-fill"></i> View Support Request Queue
                    </a>
                @else
                    <span style="display:inline-flex;align-items:center;gap:6px;margin-top:14px;background:rgba(255,255,255,.08);color:rgba(255,255,255,.6);font-family:'Nunito',sans-serif;font-weight:800;font-size:13px;padding:9px 20px;border-radius:50px">
                        <i class="bi bi-eye"></i> Read-Only Executive View
                    </span>
                @endif
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
            <div class="kpi-label">Closed</div>
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

    {{-- ── Tactical Dashboard ── --}}
    {{-- Age-bucket colors shared by every aging table below (JS mirror: AGING_COLORS). --}}
    @php
        $agingColors = [
            '0-7' => '#3fb950',
            '8-14' => '#c8e63c',
            '15-30' => '#d29922',
            '31-60' => '#f0883e',
            '61-90' => '#f85149',
            '90+' => '#ff2d2d',
        ];
    @endphp
    <div class="section-label">Tactical Dashboard</div>

    {{-- ── Row 3.6: Status Aging ── --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="chart-card">
                <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-2">
                    <div>
                        <div class="chart-title">Status Aging</div>
                        <div class="chart-sub">Every support request, grouped by status, bucketed by days since
                            creation — always current</div>
                    </div>
                    <button type="button" id="statusAgingTotalBadge" class="aging-total-badge"
                        onclick="openAgingList('', '', '', true)"
                        style="background:rgba(200,230,60,.12);color:var(--ex-yg);font-size:11px;font-weight:800;padding:4px 10px;border-radius:20px;border:1px solid rgba(200,230,60,.25)">
                        {{ $statusAging['grandTotal'] }} total
                    </button>
                </div>
                <div class="chart-sub mb-1">Click any count to see the matching support requests and who's assigned or pending.</div>
                <div class="table-responsive">
                    <table class="lb-table aging-table mt-2">
                        <thead>
                            <tr>
                                <th>Status</th>
                                @foreach($statusAging['buckets'] as $bucket)
                                    <th style="text-align:center">{{ $bucket }}d</th>
                                @endforeach
                                <th style="text-align:right">Total</th>
                            </tr>
                        </thead>
                        <tbody id="statusAgingBody">
                            @forelse($statusAging['statuses'] as $st)
                                <tr class="aging-cat-row">
                                    <td>{{ $st['status'] }}</td>
                                    @foreach($statusAging['buckets'] as $bucket)
                                        @php $count = $st['buckets'][$bucket]; @endphp
                                        <td style="text-align:center">
                                            @if($count > 0)
                                                <button type="button" class="aging-cell-btn" style="color:{{ $agingColors[$bucket] }}"
                                                    data-category="" data-status="{{ $st['status'] }}" data-bucket="{{ $bucket }}">{{ $count }}</button>
                                            @else
                                                <span style="color:var(--ex-muted)">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td style="text-align:right">
                                        <button type="button" class="aging-cell-btn"
                                            data-category="" data-status="{{ $st['status'] }}" data-bucket="">{{ $st['total'] }}</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($statusAging['buckets']) + 2 }}"
                                        style="text-align:center;color:var(--ex-muted);padding:20px">
                                        No support requests found.
                                    </td>
                                </tr>
                            @endforelse
                            @if(count($statusAging['statuses']))
                                <tr class="aging-total-row">
                                    <td>All Statuses</td>
                                    @foreach($statusAging['buckets'] as $bucket)
                                        @php $count = $statusAging['grandTotals'][$bucket]; @endphp
                                        <td style="text-align:center">
                                            @if($count > 0)
                                                <button type="button" class="aging-cell-btn"
                                                    data-category="" data-status="" data-bucket="{{ $bucket }}" data-scope="all">{{ $count }}</button>
                                            @else
                                                <span>—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td style="text-align:right">
                                        <button type="button" class="aging-cell-btn"
                                            data-category="" data-status="" data-bucket="" data-scope="all">{{ $statusAging['grandTotal'] }}</button>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ── IT Team Aging (rows rendered by renderItTeamAging() — filtered client-side by status) ── --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="chart-card">
                <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-2">
                    <div>
                        <div class="chart-title">IT Team Aging</div>
                        <div class="chart-sub">Support requests waiting on each IT team member — assigned to them, not
                            pending on a role queue, supervisor or requester — bucketed by days since creation. Always current.</div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <label for="itTeamAgingStatus" class="chart-sub mb-0">Status</label>
                        <select id="itTeamAgingStatus" class="itteam-aging-select">
                            <option value="">All statuses</option>
                            @foreach($itTeamAging['statuses'] as $st)
                                <option value="{{ $st }}">{{ $st }}</option>
                            @endforeach
                        </select>
                        <span id="itTeamAgingTotalBadge"
                            style="background:rgba(200,230,60,.12);color:var(--ex-yg);font-size:11px;font-weight:800;padding:4px 10px;border-radius:20px;border:1px solid rgba(200,230,60,.25);white-space:nowrap"></span>
                    </div>
                </div>
                <div class="chart-sub mb-1">Click any count to see the matching support requests.</div>
                <div class="table-responsive">
                    <table class="lb-table aging-table mt-2">
                        <thead>
                            <tr>
                                <th>IT Team Member</th>
                                @foreach($itTeamAging['buckets'] as $bucket)
                                    <th style="text-align:center">{{ $bucket }}d</th>
                                @endforeach
                                <th style="text-align:right">Total</th>
                            </tr>
                        </thead>
                        <tbody id="itTeamAgingBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Row 3.5: Support Request Aging by Category & Status ── --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="chart-card">
                <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-2">
                    <div>
                        <div class="chart-title">Support Request Aging by Category & Status</div>
                        <div class="chart-sub">Open support requests (excludes Closed and Cancelled), grouped by category and status, bucketed by
                            days since creation — always current</div>
                    </div>
                    <button type="button" id="agingTotalBadge" class="aging-total-badge"
                        onclick="openAgingList('', '', '')"
                        style="background:rgba(200,230,60,.12);color:var(--ex-yg);font-size:11px;font-weight:800;padding:4px 10px;border-radius:20px;border:1px solid rgba(200,230,60,.25)">
                        {{ $aging['grandTotal'] }} total
                    </button>
                </div>
                <div class="chart-sub mb-1">Click any count to see the matching support requests and who's assigned or pending.</div>
                <div class="table-responsive">
                    <table class="lb-table aging-table mt-2">
                        <thead>
                            <tr>
                                <th>Category / Status</th>
                                @foreach($aging['buckets'] as $bucket)
                                    <th style="text-align:center">{{ $bucket }}d</th>
                                @endforeach
                                <th style="text-align:right">Total</th>
                            </tr>
                        </thead>
                        <tbody id="agingBody">
                            @forelse($aging['categories'] as $cat)
                                {{-- Collapsed by default on first load — see collapsedAgingCategories/applyAgingCollapse() in scripts, which seed themselves from these classes. --}}
                                <tr class="aging-cat-row aging-collapsed" data-cat="{{ $cat['category'] }}">
                                    <td>
                                        <button type="button" class="aging-toggle-btn" aria-label="Toggle category">
                                            <i class="bi bi-chevron-down"></i>
                                        </button>
                                        {{ $cat['category'] }}
                                    </td>
                                    @foreach($aging['buckets'] as $bucket)
                                        @php $count = $cat['buckets'][$bucket]; @endphp
                                        <td style="text-align:center">
                                            @if($count > 0)
                                                <button type="button" class="aging-cell-btn" style="color:{{ $agingColors[$bucket] }}"
                                                    data-category="{{ $cat['category'] }}" data-status="" data-bucket="{{ $bucket }}">{{ $count }}</button>
                                            @else
                                                <span style="color:var(--ex-muted)">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td style="text-align:right">
                                        <button type="button" class="aging-cell-btn"
                                            data-category="{{ $cat['category'] }}" data-status="" data-bucket="">{{ $cat['total'] }}</button>
                                    </td>
                                </tr>
                                @foreach($cat['statuses'] as $st)
                                    <tr class="aging-status-row" data-cat="{{ $cat['category'] }}" style="display:none">
                                        <td class="aging-status-label">{{ $st['status'] }}</td>
                                        @foreach($aging['buckets'] as $bucket)
                                            @php $count = $st['buckets'][$bucket]; @endphp
                                            <td style="text-align:center">
                                                @if($count > 0)
                                                    <button type="button" class="aging-cell-btn" style="color:{{ $agingColors[$bucket] }}"
                                                        data-category="{{ $cat['category'] }}" data-status="{{ $st['status'] }}" data-bucket="{{ $bucket }}">{{ $count }}</button>
                                                @else
                                                    <span style="color:var(--ex-muted)">—</span>
                                                @endif
                                            </td>
                                        @endforeach
                                        <td style="text-align:right">
                                            <button type="button" class="aging-cell-btn" style="color:var(--ex-muted)"
                                                data-category="{{ $cat['category'] }}" data-status="{{ $st['status'] }}" data-bucket="">{{ $st['total'] }}</button>
                                        </td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="{{ count($aging['buckets']) + 2 }}"
                                        style="text-align:center;color:var(--ex-muted);padding:20px">
                                        No open support requests right now.
                                    </td>
                                </tr>
                            @endforelse
                            @if(count($aging['categories']))
                                <tr class="aging-total-row">
                                    <td>All Categories</td>
                                    @foreach($aging['buckets'] as $bucket)
                                        @php $count = $aging['grandTotals'][$bucket]; @endphp
                                        <td style="text-align:center">
                                            @if($count > 0)
                                                <button type="button" class="aging-cell-btn"
                                                    data-category="" data-status="" data-bucket="{{ $bucket }}">{{ $count }}</button>
                                            @else
                                                <span>—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td style="text-align:right">
                                        <button type="button" class="aging-cell-btn"
                                            data-category="" data-status="" data-bucket="">{{ $aging['grandTotal'] }}</button>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
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

    {{-- ── Row 3: Category donut + IT Team performance ── --}}
    <div class="row g-3 mb-4">

        <div class="col-lg-5">
            <div class="chart-card h-100">
                <div class="chart-title">Support Requests by Category</div>
                <div class="chart-sub">Distribution across all request types</div>
                {{-- Row 1: donut chart on its own row, full card width so it isn't squeezed
                     beside the list and hover targets are big enough to actually use. --}}
                <div class="chart-wrap d-flex justify-content-center mt-2">
                    <canvas id="categoryChart" width="220" height="220"></canvas>
                </div>

                {{-- Row 2: full category list below the chart, not squeezed into a narrow
                     flex column beside it. Every category present in the range, not a fixed
                     top-N — kept in sync with the donut (same colors, same source) by
                     updateKPIs() on range switch/poll. --}}
                <div class="d-flex flex-column gap-2 mt-3" id="categoryLegend">
                    @php $catColors = ['#f5c842', '#58a6ff', '#c8e63c', '#3fb950', '#f85149', '#d29922', '#a371f7', '#39c5cf', '#e3b341', '#7ee787', '#ff9bce', '#79c0ff']; @endphp
                    @forelse($byCategory as $i => $cat)
                        @php $catPct = $totalTickets > 0 ? round(($cat->total / $totalTickets) * 100) : 0; @endphp
                        <div class="d-flex justify-content-between align-items-center">
                            <span style="font-size:12px;font-weight:700;color:var(--ex-txt)"><span
                                    style="display:inline-block;width:8px;height:8px;border-radius:50%;background:{{ $catColors[$i % count($catColors)] }};margin-right:6px"></span>{{ $cat->request_category }}</span>
                            <span style="font-family:'Nunito',sans-serif;font-weight:800;font-size:13px">{{ $cat->total }} <span
                                    style="color:var(--ex-muted);font-size:11px">{{ $catPct }}%</span></span>
                        </div>
                    @empty
                        <div style="font-size:12px;color:var(--ex-muted)">No support requests in this range.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="chart-card h-100">
                <div class="chart-title">IT Team Performance</div>
                <div class="chart-sub" id="leaderboardSub">Ranked by support requests closed — {{ $rangeLabel }}</div>
                <table class="lb-table mt-3">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>IT Team Member</th>
                            <th>Closed</th>
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

    </div>

    {{-- ── Row 3.1: Department volume + Resolution time ── --}}
    <div class="row g-3 mb-4">

        <div class="col-lg-6">
            <div class="chart-card h-100">
                <div class="chart-title">Support Requests by Department</div>
                <div class="chart-sub" id="deptChartSub">Volume — {{ $rangeLabel }} — number of requests</div>
                <div class="chart-wrap mt-2">
                    <div id="deptChartBox" style="position:relative"><canvas id="deptChart"></canvas></div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="chart-card h-100">
                <div class="chart-title">Avg Resolution Time</div>
                <div class="chart-sub">By category — hours to resolve. Click a category to see its 5 slowest support requests.</div>
                <div class="chart-wrap mt-2"><canvas id="resTimeChart" height="180"></canvas></div>
            </div>
        </div>

    </div>

    {{-- ── Row 4: Period compare + Escalations ── --}}
    <div class="row g-3 mb-4">

        <div class="col-12">
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
                </div>

                {{-- Escalations requiring attention --}}
                    <div class="col-12">
                        <div class="chart-card" id="escalationsSection">
                            @php $criticalCount = $openEscalations->where('needs_action', true)->count(); @endphp
                            <div class="d-flex justify-content-between align-items-start mb-1 gap-2">
                                <div>
                                    <div class="chart-title">Escalations Requiring Attention</div>
                                    <div class="chart-sub">Escalated support requests that are not yet closed — oldest first. Always current; not affected by the date range.</div>
                                </div>
                                <div class="d-flex gap-2 flex-shrink-0">
                                    <span id="escOpenBadge" class="esc-badge admin" style="font-size:11px;padding:4px 10px">
                                        {{ $openEscalations->count() }} open
                                    </span>
                                    <span id="criticalBadge" class="esc-badge breach" style="font-size:11px;padding:4px 10px"
                                        title="Escalated and not yet reassigned to anyone">
                                        {{ $criticalCount }} need reassignment
                                    </span>
                                </div>
                            </div>
                            <div class="mt-2" id="escalationsList">
                                @forelse($openEscalations as $esc)
                                    @php
                                        $urgent = $esc->needs_action || $esc->sla_breached;
                                        $hoursAgo = \Carbon\Carbon::parse($esc->escalated_at)->diffForHumans();
                                    @endphp
                                    <div class="esc-item">
                                        <div class="esc-icon {{ $urgent ? 'crit' : 'info' }}">
                                            <i class="bi bi-{{ $urgent ? 'exclamation-triangle-fill' : 'hourglass-split' }}"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="esc-title">
                                                <span style="color:var(--ex-yg)">#{{ $esc->ticket_number }}</span> · {{ $esc->subject }}
                                            </div>
                                            <div class="esc-meta">
                                                {{ $esc->ticket_type }} priority · Escalation level {{ $esc->escalation_level }} · {{ $esc->department_name }} dept
                                            </div>
                                            <div class="esc-meta"><strong>Reason:</strong> {{ $esc->reason }}</div>
                                            <div class="esc-meta">
                                                Escalated {{ $hoursAgo }}@if($esc->escalated_by_name) by {{ $esc->escalated_by_name }}@endif
                                                · <strong>Technician:</strong> {{ $esc->prev_tech ?? '—' }}
                                                @if($esc->reassigned_to_name)
                                                    <strong>→ Reassigned to:</strong> {{ $esc->reassigned_to_name }}
                                                @elseif($esc->needs_action)
                                                    <strong>→ Reassigned to:</strong> <span style="color:var(--ex-red)">nobody yet</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="esc-badges">
                                            <span class="esc-badge {{ $esc->status_class }}">{{ $esc->status_label }}</span>
                                            @if($esc->sla_breached)
                                                <span class="esc-badge breach">SLA breached</span>
                                            @endif
                                        </div>
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

        {{-- ── Floating IT Team Status ── --}}
        <button type="button" class="itteam-fab" id="itTeamFab" title="IT Team Status" aria-label="IT Team Status" aria-controls="itTeamPanel" aria-expanded="false">
            <i class="bi bi-people-fill"></i>
            <span class="itteam-fab-count" title="Online" id="itTeamFabCount">{{ $itTeamStatus->where('online', true)->count() }}</span>
        </button>
        <div class="itteam-float" id="itTeamPanel" hidden>
            <div>
                <div class="chart-card">
                    <div class="d-flex justify-content-between align-items-center mb-1 gap-2">
                        <div>
                            <div class="chart-title">IT Team Status</div>
                            <div class="chart-sub">Presence and workload for every active support-tier member</div>
                        </div>
                        <button type="button" class="btn-close btn-close-white ms-auto order-last" id="itTeamClose" aria-label="Close"></button>
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

    <div class="modal fade" id="agingListModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="mb-0">Support Requests — <em id="agingListTitle" style="color:var(--ex-yg)"></em></h5>
                        <div id="agingListCount" style="font-size:12px;color:var(--ex-muted);margin-top:2px"></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-3" style="max-height:65vh;overflow-y:auto">
                    <div class="table-responsive">
                        <table class="lb-table">
                            <thead>
                                <tr>
                                    <th>Support Request</th>
                                    <th>Requester</th>
                                    <th>Status</th>
                                    <th>Assigned / Pending</th>
                                    <th>Age</th>
                                    <th style="text-align:right">Details</th>
                                </tr>
                            </thead>
                            <tbody id="agingListBody"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="rt-btn" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="resTopModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="mb-0">Top 5 Longest Resolution Time — <em id="resTopTitle" style="color:var(--ex-yg)"></em></h5>
                        <div id="resTopSub" style="font-size:12px;color:var(--ex-muted);margin-top:2px"></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-3" style="max-height:65vh;overflow-y:auto">
                    <div class="table-responsive">
                        <table class="lb-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Support Request</th>
                                    <th>Requester</th>
                                    <th>Assigned To</th>
                                    <th>Priority</th>
                                    <th>Started → Resolved</th>
                                    <th style="text-align:right">Resolution Time</th>
                                    <th style="text-align:right">Details</th>
                                </tr>
                            </thead>
                            <tbody id="resTopBody"></tbody>
                        </table>
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
                let deptChartInstance = null;
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

                    /* ── Category donut (+ legend, same source/colors — see #categoryLegend) ── */
                    if (categoryChartInstance) categoryChartInstance.destroy();
                    const catColors = ['#f5c842', '#58a6ff', '#c8e63c', '#3fb950', '#f85149', '#d29922', '#a371f7', '#39c5cf', '#e3b341', '#7ee787', '#ff9bce', '#79c0ff'];
                    categoryChartInstance = new Chart(document.getElementById('categoryChart'), {
                        type: 'doughnut',
                        data: {
                            labels: data.byCategory.map(c => c.request_category),
                            datasets: [{ data: data.byCategory.map(c => c.total), backgroundColor: data.byCategory.map((c, i) => catColors[i % catColors.length]), borderWidth: 0, borderRadius: 4, spacing: 2 }]
                        },
                        options: { cutout: '65%', responsive: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => ' ' + c.label + ': ' + c.raw + ' support requests' } } } }
                    });

                    const catLegendEl = document.getElementById('categoryLegend');
                    if (catLegendEl) {
                        catLegendEl.innerHTML = data.byCategory.length ? data.byCategory.map((c, i) => {
                            const pct = data.totalTickets > 0 ? Math.round((c.total / data.totalTickets) * 100) : 0;
                            return `
                    <div class="d-flex justify-content-between align-items-center">
                        <span style="font-size:12px;font-weight:700;color:var(--ex-txt)"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:${catColors[i % catColors.length]};margin-right:6px"></span>${escHtml(c.request_category)}</span>
                        <span style="font-family:'Nunito',sans-serif;font-weight:800;font-size:13px">${c.total} <span style="color:var(--ex-muted);font-size:11px">${pct}%</span></span>
                    </div>`;
                        }).join('') : '<div style="font-size:12px;color:var(--ex-muted)">No support requests in this range.</div>';
                    }

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
                            // Click a bar — or its category label — to list that category's 5 slowest requests.
                            onClick: (evt, els, chart) => {
                                let idx = els.length ? els[0].index : null;
                                if (idx === null && evt.x < chart.chartArea.left) {
                                    idx = Math.round(chart.scales.y.getValueForPixel(evt.y));
                                }
                                const label = chart.data.labels[idx];
                                if (label !== undefined) openResTop(label);
                            },
                            onHover: (evt, els, chart) => {
                                const onLabel = evt.x < chart.chartArea.left && evt.y >= chart.chartArea.top && evt.y <= chart.chartArea.bottom;
                                chart.canvas.style.cursor = (els.length || onLabel) ? 'pointer' : 'default';
                            },
                            plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => ` ${c.raw}h avg`, footer: () => 'Click to see the 5 slowest' } } },
                            scales: {
                                x: { beginAtZero: true, grid: { color: 'rgba(48,54,61,.6)' }, ticks: { callback: v => v + 'h' } },
                                y: { grid: { display: false } }
                            }
                        }
                    });

                    /* ── Support requests by department bar ── */
                    if (data.byDepartment) {
                        if (deptChartInstance) deptChartInstance.destroy();
                        const deptColor = t => t >= 35 ? '#f85149' : (t >= 20 ? '#d29922' : '#3fb950');
                        // Compact two-line labels (department / company), ~22px per bar, no scrolling;
                        // long names are shortened here and shown in full in the tooltip.
                        const shorten = (t, n) => t && t.length > n ? t.slice(0, n - 1) + '…' : t;
                        document.getElementById('deptChartBox').style.height = Math.max(140, data.byDepartment.length * 22 + 30) + 'px';
                        deptChartInstance = new Chart(document.getElementById('deptChart'), {
                            type: 'bar',
                            data: {
                                labels: data.byDepartment.map(d => [shorten(d.department_name, 26), shorten(d.company_name, 28)]),
                                datasets: [{ data: data.byDepartment.map(d => d.total), backgroundColor: data.byDepartment.map(d => deptColor(d.total)), borderRadius: 3, borderSkipped: false, barPercentage: .85, categoryPercentage: .9 }]
                            },
                            options: {
                                indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                                layout: { padding: { right: 28 } },
                                plugins: { legend: { display: false }, tooltip: { callbacks: {
                                    title: items => { const d = data.byDepartment[items[0].dataIndex]; return `${d.department_name} — ${d.company_name}`; },
                                    label: c => ` ${c.raw} support request${c.raw == 1 ? '' : 's'}` } } },
                                scales: {
                                    x: { beginAtZero: true, grid: { color: 'rgba(48,54,61,.6)' }, ticks: { precision: 0, font: { size: 10 } } },
                                    y: { grid: { display: false }, ticks: { autoSkip: false, font: { size: 9, lineHeight: 1.05 } } }
                                }
                            },
                            plugins: [{
                                id: 'deptCountLabels',
                                afterDatasetsDraw(chart) {
                                    const { ctx } = chart;
                                    ctx.save();
                                    ctx.font = '600 10px sans-serif';
                                    ctx.fillStyle = Chart.defaults.color;
                                    ctx.textBaseline = 'middle';
                                    chart.getDatasetMeta(0).data.forEach((bar, i) => {
                                        ctx.fillText(chart.data.datasets[0].data[i], bar.x + 6, bar.y);
                                    });
                                    ctx.restore();
                                }
                            }]
                        });
                    }

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

                    // ── Escalations list (labels/flags computed server-side — see controller)
                    const escList = document.getElementById('escalationsList');
                    const critBadge = document.getElementById('criticalBadge');
                    const openBadge = document.getElementById('escOpenBadge');
                    if (escList && data.openEscalations) {
                        const openCount = data.openEscalations.length;
                        const critCount = data.openEscalations.filter(e => e.needs_action).length;
                        if (openBadge) openBadge.textContent = `${openCount} open`;
                        if (critBadge) critBadge.textContent = `${critCount} need reassignment`;

                        if (!openCount) {
                            escList.innerHTML = `
                    <div style="text-align:center;color:var(--ex-muted);padding:20px">
                        ✅ No open escalations right now.
                    </div>`;
                        } else {
                            escList.innerHTML = data.openEscalations.map(esc => {
                                const urgent = esc.needs_action || esc.sla_breached;
                                return `
                        <div class="esc-item">
                            <div class="esc-icon ${urgent ? 'crit' : 'info'}">
                                <i class="bi bi-${urgent ? 'exclamation-triangle-fill' : 'hourglass-split'}"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="esc-title">
                                    <span style="color:var(--ex-yg)">#${escHtml(esc.ticket_number)}</span> · ${escHtml(esc.subject)}
                                </div>
                                <div class="esc-meta">
                                    ${escHtml(esc.ticket_type)} priority · Escalation level ${escHtml(esc.escalation_level)} · ${escHtml(esc.department_name)} dept
                                </div>
                                <div class="esc-meta"><strong>Reason:</strong> ${escHtml(esc.reason)}</div>
                                <div class="esc-meta">
                                    Escalated ${timeAgo(esc.escalated_at)}${esc.escalated_by_name ? ` by ${escHtml(esc.escalated_by_name)}` : ''}
                                    · <strong>Technician:</strong> ${escHtml(esc.prev_tech || '—')}
                                    ${esc.reassigned_to_name ? `<strong>→ Reassigned to:</strong> ${escHtml(esc.reassigned_to_name)}`
                                        : (esc.needs_action ? '<strong>→ Reassigned to:</strong> <span style="color:var(--ex-red)">nobody yet</span>' : '')}
                                </div>
                            </div>
                            <div class="esc-badges">
                                <span class="esc-badge ${escHtml(esc.status_class)}">${escHtml(esc.status_label)}</span>
                                ${esc.sla_breached ? '<span class="esc-badge breach">SLA breached</span>' : ''}
                            </div>
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
                            deptChartSub: `Volume — ${data.rangeLabel} — number of requests`,
                            leaderboardSub: `Ranked by support requests closed — ${data.rangeLabel}`,
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

                    // ── Support Request Aging by Category & Status
                    renderAging(data);

                    // ── Status Aging
                    renderStatusAging(data);

                    // ── IT Team Aging
                    renderItTeamAging(data);

                    // ── IT Team Status
                    const itTeamBody = document.getElementById('itTeamBody');
                    if (itTeamBody && data.itTeamStatus) {
                        const onlineBadge = document.getElementById('itTeamOnlineBadge');
                        const onlineCount = data.itTeamStatus.filter(m => m.online).length;
                        if (onlineBadge) onlineBadge.textContent = `${onlineCount} online`;
                        const fabCount = document.getElementById('itTeamFabCount');
                        if (fabCount) fabCount.textContent = onlineCount;

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

                // ── Support Request Aging by Category & Status — rebuilds the aging table
                // (category rows + indented status sub-rows + grand-total row) from JSON.
                const AGING_COLORS = { '0-7': '#3fb950', '8-14': '#c8e63c', '15-30': '#d29922', '31-60': '#f0883e', '61-90': '#f85149', '90+': '#ff2d2d' };

                function renderAging(data) {
                    const body = document.getElementById('agingBody');
                    const badge = document.getElementById('agingTotalBadge');
                    if (!body || !data.aging) return;

                    const buckets = data.aging.buckets;

                    if (badge) badge.textContent = `${data.aging.grandTotal} total`;

                    if (!data.aging.categories.length) {
                        body.innerHTML = `<tr><td colspan="${buckets.length + 2}" style="text-align:center;color:var(--ex-muted);padding:20px">No open support requests right now.</td></tr>`;
                        return;
                    }

                    const cell = (count, bucket, category, status) => {
                        if (count > 0) {
                            return `<td style="text-align:center"><button type="button" class="aging-cell-btn" style="color:${AGING_COLORS[bucket]}" data-category="${escHtml(category)}" data-status="${escHtml(status)}" data-bucket="${bucket}">${count}</button></td>`;
                        }
                        return `<td style="text-align:center"><span style="color:var(--ex-muted)">—</span></td>`;
                    };
                    const totalBtn = (total, category, status) =>
                        `<button type="button" class="aging-cell-btn" data-category="${escHtml(category)}" data-status="${escHtml(status)}" data-bucket="">${total}</button>`;

                    const rows = data.aging.categories.map(cat => {
                        const catCells = buckets.map(b => cell(cat.buckets[b], b, cat.category, '')).join('');
                        const statusRows = cat.statuses.map(st => {
                            const stCells = buckets.map(b => cell(st.buckets[b], b, cat.category, st.status)).join('');
                            return `<tr class="aging-status-row" data-cat="${escHtml(cat.category)}"><td class="aging-status-label">${escHtml(st.status)}</td>${stCells}<td style="text-align:right;color:var(--ex-muted)">${totalBtn(st.total, cat.category, st.status)}</td></tr>`;
                        }).join('');
                        const toggle = `<button type="button" class="aging-toggle-btn" aria-label="Toggle category"><i class="bi bi-chevron-down"></i></button>`;
                        return `<tr class="aging-cat-row" data-cat="${escHtml(cat.category)}"><td>${toggle}${escHtml(cat.category)}</td>${catCells}<td style="text-align:right">${totalBtn(cat.total, cat.category, '')}</td></tr>${statusRows}`;
                    }).join('');

                    const totalCells = buckets.map(b => cell(data.aging.grandTotals[b], b, '', '')).join('');
                    const totalRow = `<tr class="aging-total-row"><td>All Categories</td>${totalCells}<td style="text-align:right">${totalBtn(data.aging.grandTotal, '', '')}</td></tr>`;

                    body.innerHTML = rows + totalRow;
                    applyAgingCollapse();
                }

                // ── Status Aging — rebuilds the flat status table (no category grouping,
                // includes every status) from JSON. Mirrors renderAging() above.
                function renderStatusAging(data) {
                    const body = document.getElementById('statusAgingBody');
                    const badge = document.getElementById('statusAgingTotalBadge');
                    if (!body || !data.statusAging) return;

                    const buckets = data.statusAging.buckets;

                    if (badge) badge.textContent = `${data.statusAging.grandTotal} total`;

                    if (!data.statusAging.statuses.length) {
                        body.innerHTML = `<tr><td colspan="${buckets.length + 2}" style="text-align:center;color:var(--ex-muted);padding:20px">No support requests found.</td></tr>`;
                        return;
                    }

                    const cell = (count, bucket, status) => {
                        if (count > 0) {
                            return `<td style="text-align:center"><button type="button" class="aging-cell-btn" style="color:${AGING_COLORS[bucket]}" data-category="" data-status="${escHtml(status)}" data-bucket="${bucket}">${count}</button></td>`;
                        }
                        return `<td style="text-align:center"><span style="color:var(--ex-muted)">—</span></td>`;
                    };
                    const totalBtn = (total, status, scopeAll) =>
                        `<button type="button" class="aging-cell-btn" data-category="" data-status="${escHtml(status)}" data-bucket=""${scopeAll ? ' data-scope="all"' : ''}>${total}</button>`;

                    const rows = data.statusAging.statuses.map(st => {
                        const stCells = buckets.map(b => cell(st.buckets[b], b, st.status)).join('');
                        return `<tr class="aging-cat-row"><td>${escHtml(st.status)}</td>${stCells}<td style="text-align:right">${totalBtn(st.total, st.status, false)}</td></tr>`;
                    }).join('');

                    const totalCells = buckets.map(b => `<td style="text-align:center">${data.statusAging.grandTotals[b] > 0 ? `<button type="button" class="aging-cell-btn" data-category="" data-status="" data-bucket="${b}" data-scope="all">${data.statusAging.grandTotals[b]}</button>` : '<span>—</span>'}</td>`).join('');
                    const totalRow = `<tr class="aging-total-row"><td>All Statuses</td>${totalCells}<td style="text-align:right">${totalBtn(data.statusAging.grandTotal, '', true)}</td></tr>`;

                    body.innerHTML = rows + totalRow;
                }

                // ── IT Team Aging — per-member rows for the status picked in #itTeamAgingStatus
                // ('' = all statuses, summed). Re-run on every refresh and on filter change.
                let lastItTeamAging = null;

                function renderItTeamAging(data) {
                    if (data && data.itTeamAging) lastItTeamAging = data.itTeamAging;
                    const agingData = lastItTeamAging;
                    const body = document.getElementById('itTeamAgingBody');
                    const select = document.getElementById('itTeamAgingStatus');
                    if (!body || !agingData) return;

                    const buckets = agingData.buckets;
                    const status = select ? select.value : '';
                    const statusesOf = m => status ? [status] : Object.keys(m.counts || {});
                    const countFor = (m, st, b) => ((m.counts || {})[st] || {})[b] || 0;

                    // Option labels carry each status's team-wide count so empty ones are obvious.
                    if (select) {
                        const totalFor = st => agingData.members.reduce((sum, m) => sum + buckets.reduce((t, b) => t + countFor(m, st, b), 0), 0);
                        let allTotal = 0;
                        Array.from(select.options).forEach(opt => {
                            if (!opt.value) return;
                            const n = totalFor(opt.value);
                            allTotal += n;
                            opt.textContent = `${opt.value} (${n})`;
                        });
                        select.options[0].textContent = `All statuses (${allTotal})`;
                    }

                    const rows = agingData.members.map(m => {
                        const perBucket = buckets.map(b => statusesOf(m).reduce((sum, st) => sum + countFor(m, st, b), 0));
                        return { m, perBucket, total: perBucket.reduce((a, c) => a + c, 0) };
                    }).sort((a, b) => b.total - a.total || a.m.name.localeCompare(b.m.name));

                    const grand = buckets.map((_, i) => rows.reduce((sum, r) => sum + r.perBucket[i], 0));
                    const grandTotal = grand.reduce((a, c) => a + c, 0);

                    const badge = document.getElementById('itTeamAgingTotalBadge');
                    if (badge) badge.textContent = `${grandTotal} total`;

                    const btn = (count, bucket, member, color) => count > 0
                        ? `<button type="button" class="aging-cell-btn"${color ? ` style="color:${color}"` : ''} data-category="" data-status="${escHtml(status)}" data-bucket="${bucket}" data-scope="all" data-assignee="${escHtml(member ? member.id : '__team__')}" data-assignee-name="${escHtml(member ? member.name : 'All IT Team')}">${count}</button>`
                        : '<span style="color:var(--ex-muted)">—</span>';

                    body.innerHTML = rows.map(({ m, perBucket, total }) => `
                        <tr class="aging-cat-row">
                            <td>
                                <div class="lb-name">${escHtml(m.name)}</div>
                                <div class="lb-role">${escHtml(m.role || '')}</div>
                            </td>
                            ${perBucket.map((c, i) => `<td style="text-align:center">${btn(c, buckets[i], m, AGING_COLORS[buckets[i]])}</td>`).join('')}
                            <td style="text-align:right">${btn(total, '', m)}</td>
                        </tr>`).join('') + `
                        <tr class="aging-total-row">
                            <td>All IT Team</td>
                            ${grand.map((c, i) => `<td style="text-align:center">${btn(c, buckets[i], null)}</td>`).join('')}
                            <td style="text-align:right">${btn(grandTotal, '', null)}</td>
                        </tr>`;
                }

                $('#itTeamAgingStatus').on('change', () => renderItTeamAging());

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

                // Status badge coloring for support-request rows — still used by the
                // aging drill-down list (renderAgingList) after the old "All Active
                // Support Requests" table (which introduced this helper) was removed.
                function activeTicketsBadgeClass(status) {
                    const s = (status || '').toLowerCase();
                    if (s.includes('escalated')) return 'breach';
                    if (s.includes('awaiting') || s.includes('pending')) return 'open';
                    if (s.includes('progress')) return 'admin';
                    return 'muted';
                }

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
                        })
                        .catch(() => { }); // Silent fail
                }

                // ── Date-range buttons (7D/30D/90D/YTD) — event fired by layouts/executive.blade.php
                $(document).on('rangeChange', function (e, range) {
                    currentRange = range;
                    fetchExecutiveData();
                });

                /* ══ Floating IT Team Status panel ══ */
                function toggleItTeam(open) {
                    const panel = document.getElementById('itTeamPanel');
                    panel.hidden = !open;
                    document.getElementById('itTeamFab').setAttribute('aria-expanded', open);
                }
                $('#itTeamFab').on('click', () => toggleItTeam(document.getElementById('itTeamPanel').hidden));
                $('#itTeamClose').on('click', () => toggleItTeam(false));
                $(document).on('keydown', e => { if (e.key === 'Escape') toggleItTeam(false); });

                /* ══ INIT ON PAGE LOAD with existing PHP data ══ */
                initCharts({
                    volumeDays: {!! json_encode($volumeDays) !!},
                    volumeOpened: {!! json_encode($volumeOpened) !!},
                    volumeResolved: {!! json_encode($volumeResolved) !!},
                    slaByPriority: {!! json_encode($slaByPriority ?? []) !!},
                    byCategory: {!! json_encode($byCategory) !!},
                    resTimeByCategory: {!! json_encode($resTimeByCategory) !!},
                    byDepartment: {!! json_encode($byDepartment) !!},
                    weeklyData: {!! json_encode($weeklyData) !!},
                });

                renderItTeamAging({ itTeamAging: {!! json_encode($itTeamAging) !!} });

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

                /* ══ Avg Resolution Time drill-down: top 5 slowest closed requests in a category ══ */
                const fmtHours = h => {
                    const n = Number(h);
                    if (n >= 24) return `${n}h <span style="color:var(--ex-muted);font-weight:600">(${(n / 24).toFixed(1)}d)</span>`;
                    return `${n}h`;
                };
                const fmtDate = d => d ? new Date(d.replace(' ', 'T')).toLocaleString([], { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }) : '—';

                window.openResTop = function (category) {
                    $('#resTopTitle').text(category);
                    $('#resTopSub').text('');
                    $('#resTopBody').html(`
                        <tr><td colspan="8" style="text-align:center;color:var(--ex-muted);padding:20px">
                            <div class="spinner-border spinner-border-sm me-2"></div>Loading…
                        </td></tr>`);
                    bootstrap.Modal.getOrCreateInstance('#resTopModal').show();

                    const params = new URLSearchParams({ category, range: currentRange });
                    fetch('{{ route('executive.dashboard.resolution-top') }}?' + params.toString(), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                    })
                        .then(r => r.json())
                        .then(data => {
                            $('#resTopSub').text(`Closed support requests, ${data.rangeLabel} — longest time from start to resolution`);
                            if (!data.tickets.length) {
                                $('#resTopBody').html('<tr><td colspan="8" style="text-align:center;color:var(--ex-muted);padding:20px">No closed support requests in this category.</td></tr>');
                                return;
                            }
                            $('#resTopBody').html(data.tickets.map((t, i) => `
                                <tr>
                                    <td class="lb-rank ${['gold', 'silver', 'bronze'][i] || ''}">${i + 1}</td>
                                    <td>
                                        <div style="font-weight:800;font-size:13px;color:var(--ex-txt)">#${escHtml(t.ticket_number)}</div>
                                        <div style="font-size:12px;color:var(--ex-muted)">${escHtml((t.subject || '').slice(0, 50))}</div>
                                    </td>
                                    <td style="font-size:12px;color:var(--ex-muted)">${escHtml(t.requester_name)}</td>
                                    <td style="font-size:12px">${escHtml(t.assigned_to_name || '—')}</td>
                                    <td style="font-size:12px">${escHtml(t.priority || '—')}</td>
                                    <td style="font-size:11px;color:var(--ex-muted);white-space:nowrap">${fmtDate(t.started_at)} → ${fmtDate(t.resolved_at)}</td>
                                    <td style="text-align:right;font-family:'Nunito',sans-serif;font-weight:800;font-size:14px;color:var(--ex-red);white-space:nowrap">${fmtHours(t.hours)}</td>
                                    <td style="text-align:right">
                                        <button type="button" class="rt-btn" onclick="openDetailsFromResTop('${t.id}', '${escHtml(t.ticket_number)}')">
                                            <i class="bi bi-clock-history me-1"></i>Details
                                        </button>
                                    </td>
                                </tr>`).join(''));
                        })
                        .catch(() => {
                            $('#resTopBody').html('<tr><td colspan="8" style="text-align:center;color:var(--ex-red);padding:20px">Failed to load support requests.</td></tr>');
                        });
                };

                // Same modal swap as openDetailsFromAgingList() — Bootstrap doesn't stack modals.
                window.openDetailsFromResTop = function (ticketId, ticketNumber) {
                    bootstrap.Modal.getInstance(document.getElementById('resTopModal'))?.hide();
                    openTimelineModal(ticketId, ticketNumber);
                };

                /* ══ Aging drill-down: click any count in the aging table to list its tickets ══ */

                // Delegated so it keeps working after renderAging() rebuilds #agingBody on refresh.
                $(document).on('click', '.aging-cell-btn', function () {
                    const el = $(this);
                    openAgingList(el.data('category') ?? '', el.data('status') ?? '', el.data('bucket') ?? '', el.data('scope') === 'all',
                        el.attr('data-assignee') || '', el.attr('data-assignee-name') || '');
                });

                /* ══ Per-category collapse in "Support Request Aging by Category & Status" ══ */
                // Category names collapsed by the user, in-memory only (resets on page reload).
                // Re-applied after every renderAging() rebuild (30s refresh) so state survives it.
                // Seeded from the server-rendered rows (all marked .aging-collapsed — see the
                // Blade template) so every category starts collapsed on first load.
                const collapsedAgingCategories = new Set(
                    $('#agingBody .aging-cat-row').map(function () { return $(this).data('cat'); }).get()
                );

                function applyAgingCollapse() {
                    $('#agingBody .aging-cat-row').each(function () {
                        const $row = $(this);
                        $row.toggleClass('aging-collapsed', collapsedAgingCategories.has($row.data('cat')));
                    });
                    $('#agingBody .aging-status-row').each(function () {
                        const $row = $(this);
                        $row.toggle(!collapsedAgingCategories.has($row.data('cat')));
                    });
                }

                // Delegated so it keeps working after renderAging() rebuilds #agingBody on refresh.
                $(document).on('click', '.aging-toggle-btn', function (e) {
                    e.stopPropagation();
                    const cat = $(this).closest('.aging-cat-row').data('cat');
                    if (collapsedAgingCategories.has(cat)) {
                        collapsedAgingCategories.delete(cat);
                    } else {
                        collapsedAgingCategories.add(cat);
                    }
                    applyAgingCollapse();
                });

                window.openAgingList = function (category, status, bucket, includeAll, assignee, assigneeName) {
                    const titleParts = [];
                    if (assigneeName) titleParts.push(assigneeName);
                    if (category) titleParts.push(category);
                    else if (!status) titleParts.push(includeAll ? 'All Statuses' : 'All Categories');
                    if (status) titleParts.push(status);
                    titleParts.push(bucket ? `${bucket} days` : 'all ages');
                    $('#agingListTitle').text(titleParts.join(' · '));
                    $('#agingListCount').text('');
                    $('#agingListBody').html(`
                        <tr><td colspan="6" style="text-align:center;color:var(--ex-muted);padding:20px">
                            <div class="spinner-border spinner-border-sm me-2"></div>Loading…
                        </td></tr>
                    `);
                    new bootstrap.Modal('#agingListModal').show();

                    const params = new URLSearchParams({ category: category || '', status: status || '', bucket: bucket || '', all: includeAll ? '1' : '', assignee: assignee || '' });
                    fetch('{{ route('executive.dashboard.aging-tickets') }}?' + params.toString(), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                    })
                        .then(r => r.json())
                        .then(renderAgingList)
                        .catch(() => {
                            $('#agingListBody').html('<tr><td colspan="6" style="text-align:center;color:var(--ex-red);padding:20px">Failed to load support requests.</td></tr>');
                        });
                };

                function renderAgingList(data) {
                    const body = document.getElementById('agingListBody');
                    if (!body) return;

                    const countEl = document.getElementById('agingListCount');
                    if (countEl) countEl.textContent = `${data.total} support request${data.total === 1 ? '' : 's'}`;

                    if (!data.tickets.length) {
                        body.innerHTML = `<tr><td colspan="6" style="text-align:center;color:var(--ex-muted);padding:20px">No support requests match this slice.</td></tr>`;
                        return;
                    }

                    body.innerHTML = data.tickets.map(t => {
                        const who = t.assigned_to_name
                            ? escHtml(t.assigned_to_name)
                            : `<span style="color:var(--ex-amber)">Pending: ${escHtml(t.pending_role || 'Unassigned')}</span>`;
                        return `
                            <tr>
                                <td>
                                    <div style="font-weight:800;font-size:13px;color:var(--ex-txt)">#${escHtml(t.ticket_number)}</div>
                                    <div style="font-size:12px;color:var(--ex-muted)">${escHtml((t.subject || '').slice(0, 40))}</div>
                                </td>
                                <td style="font-size:12px;color:var(--ex-muted)">${escHtml(t.requester_name)}</td>
                                <td><span class="esc-badge ${activeTicketsBadgeClass(t.status)}">${escHtml(t.status)}</span></td>
                                <td style="font-size:12px">${who}</td>
                                <td style="font-size:12px;color:var(--ex-muted)">${t.age_days}d</td>
                                <td style="text-align:right">
                                    <button type="button" class="rt-btn" onclick="openDetailsFromAgingList('${t.id}', '${escHtml(t.ticket_number)}')">
                                        <i class="bi bi-clock-history me-1"></i>Details
                                    </button>
                                </td>
                            </tr>`;
                    }).join('');
                }

                // Swaps the aging list modal for the existing ticket-timeline modal — Bootstrap
                // doesn't stack modals cleanly, so hide this one before opening that one.
                window.openDetailsFromAgingList = function (ticketId, ticketNumber) {
                    const modalEl = document.getElementById('agingListModal');
                    bootstrap.Modal.getInstance(modalEl)?.hide();
                    openTimelineModal(ticketId, ticketNumber);
                };

            });
        </script>
    @endsection