@extends('layouts.app')

@section('title', 'Supervisor Dashboard — LGICT')

@section('nav-role-badge')
    <span class="role-badge"><i class="bi bi-person-check-fill me-1"></i>Supervisor</span>
@endsection
@section('avatar-initials',
    strtoupper(substr(Auth::user()->name, 0, 1)) .
    strtoupper(substr(Auth::user()->name, strpos(Auth::user()->name, ' ') + 1, 1))
)
@section('nav-username', Auth::user()->name)

@section('hero-title')
    <h1>Supervisor <em>DASHBOARD</em></h1>
@endsection
@section('hero-subtitle', 'Acknowledge, classify, and assign incoming support tickets to your specialists.')

@section('hero-stats')
    <div class="d-flex gap-2 flex-wrap">
        <div class="stat-pill warn">
            <span class="num">{{ $counts['awaiting_classification'] }}</span>
            <span class="lbl">Awaiting Classification</span>
        </div>
        <div class="stat-pill warn">
            <span class="num">{{ $counts['in_progress'] }}</span>
            <span class="lbl">In Progress</span>
        </div>
        <div class="stat-pill">
            <span class="num">{{ $counts['escalated'] }}</span>
            <span class="lbl">Escalated</span>
        </div>
        <div class="stat-pill">
            <span class="num">{{ $counts['pending_supervisor_approval'] }}</span>
            <span class="lbl">Pending Supervisor Approval</span>
        </div>
    </div>
@endsection

@section('styles')

    .tech-row { padding:10px 16px; border-bottom:1px solid var(--bd); display:flex; align-items:center; gap:10px; font-size:13px; }
    .tech-row:last-child { border-bottom:none; }
    .tech-av-lg { width:30px; height:30px; background:var(--gd); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:900; color:var(--yg); font-family:'Nunito',sans-serif; flex-shrink:0; }
    .tech-name { font-weight:700; font-size:13px; }
    .tech-load { font-size:11px; color:var(--tm); }
    .avail-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; margin-left:auto; }
    .avail-dot.free { background:#4a7c4a; }
    .avail-dot.busy { background:#f5c842; }
    .avail-dot.full { background:#e24b4a; }

    .btn-assign   { background:var(--gd); color:var(--yg); font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; padding:6px 14px; border-radius:20px; border:none; cursor:pointer; transition:background .2s; }
    .btn-assign:hover { background:var(--gm); }
    .btn-acknowledge { background:var(--ygl); color:var(--gd); font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; padding:6px 14px; border-radius:20px; border:1.5px solid var(--bd); cursor:pointer; transition:all .2s; }
    .btn-acknowledge:hover { border-color:var(--gl); }
    .btn-reassign { background:var(--ygl); color:var(--gd); font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; padding:6px 14px; border-radius:20px; border:1.5px solid var(--bd); cursor:pointer; transition:all .2s; }
    .btn-reassign:hover { border-color:var(--gl); background:#d8eda0; }
    .btn-takeover { background:#fde8e8; color:#8b1a1a; font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; padding:6px 14px; border-radius:20px; border:1.5px solid #f0c0c0; cursor:pointer; transition:all .2s; }
    .btn-takeover:hover { background:#f8c8c8; }
    .btn-cannot-resolve { background:#eee; color:#555; font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; padding:6px 14px; border-radius:20px; border:1.5px solid #ccc; cursor:pointer; transition:all .2s; }
    .btn-cannot-resolve:hover { background:#e0e0e0; border-color:#aaa; }
    .btn-resolve  { background:#e8f5ee; color:#1a5a3a; font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; padding:6px 14px; border-radius:20px; border:1.5px solid #a8ddc0; cursor:pointer; transition:all .2s; }
    .btn-resolve:hover  { background:#c8ead8; }
    .btn-service-report { background:#e8f5ee; color:#1a5a3a; font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; padding:6px 14px; border-radius:20px; border:1.5px solid #a8ddc0; cursor:pointer; transition:all .2s; }
    .btn-service-report:hover { background:#c8ead8; }

    .tech-select-option { border:1.5px solid var(--bd); border-radius:12px; padding:12px 14px; cursor:pointer; transition:all .2s; background:var(--cr); }
    .tech-select-option:hover { border-color:var(--gl); background:var(--ygl); }
    .tech-select-option.selected { border-color:var(--gd); background:var(--ygl); box-shadow:0 0 0 2px var(--yg); }
    .tech-select-option.disabled { opacity:.5; cursor:not-allowed; pointer-events:none; }
    .ts-name { font-family:'Nunito',sans-serif; font-weight:800; font-size:14px; }
    .ts-load { font-size:12px; color:var(--tm); }
    .load-bar-wrap { height:6px; background:var(--bd); border-radius:4px; margin-top:6px; }
    .load-bar { height:6px; border-radius:4px; background:var(--gl); }
    .load-bar.busy { background:#f5c842; }
    .load-bar.full { background:#e24b4a; }

    .cat-main-opt {
        border: 1.5px solid var(--bd); border-radius: 12px; padding: 10px 16px;
        cursor: pointer; transition: all .2s; background: var(--cr); user-select: none;
        font-family:'Nunito',sans-serif; font-weight:800; font-size:13px; color:var(--gd);
    }
    .cat-main-opt:hover { border-color: var(--gl); background: var(--ygl); }
    .cat-main-opt.selected { border-color: var(--gd); background: var(--ygl); box-shadow: 0 0 0 2px var(--yg); }

    .cat-sub-opt {
        border: 1.5px solid var(--bd); border-radius: 10px; padding: 10px 14px;
        cursor: pointer; transition: all .2s; background: var(--cr);
        font-size: 13px; font-weight: 600; color: var(--gd);
        display: flex; align-items: center; gap: 8px;
    }
    .cat-sub-opt:hover { border-color: var(--gl); background: var(--ygl); }
    .cat-sub-opt.selected { border-color: var(--gd); background: var(--ygl); box-shadow: 0 0 0 2px var(--yg); font-weight: 700; }
    .cat-sub-opt .sub-check {
        width: 18px; height: 18px; border-radius: 50%; border: 2px solid var(--bd);
        flex-shrink: 0; display: flex; align-items: center; justify-content: center;
        font-size: 10px; transition: all .2s;
    }
    .cat-sub-opt.selected .sub-check { background: var(--gd); border-color: var(--gd); color: var(--yg); }

    .btn-back-modal  { background: none; border: 1.5px solid var(--bd); color: var(--tm); font-family: 'Nunito', sans-serif; font-weight: 800; font-size: 14px; padding: 10px 22px; border-radius: 50px; transition: all .2s; }
    .btn-back-modal:hover { border-color: var(--gl); color: var(--gd); }
    .btn-confirm { background: var(--gd); color: var(--yg); font-family: 'Nunito', sans-serif; font-weight: 900; font-size: 14px; padding: 11px 28px; border-radius: 50px; border: none; transition: all .2s; }
    .btn-confirm:hover { background: var(--gm); transform: translateY(-1px); }
    .btn-cancel-modal { background: none; border: 1.5px solid var(--bd); color: var(--tm); font-family: 'Nunito', sans-serif; font-weight: 800; font-size: 14px; padding: 10px 22px; border-radius: 50px; transition: all .2s; }

    .esc-banner { background:#fde8e8; color:#8b1a1a; border-radius:10px; font-size:13px; font-weight:600; }
    .resolve-info { background:var(--ygl); border-radius:10px; font-size:13px; color:var(--gd); }

    .btn-chat { background:#e8eeff; color:#2a4ab0; font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; padding:6px 14px; border-radius:20px; border:1.5px solid #b8c8ff; cursor:pointer; transition:all .2s; position:relative; display:inline-flex; align-items:center; gap:5px; }
    .btn-chat:hover { background:#d0dcff; border-color:#8898dd; }
    .chat-count-badge { background:#e24b4a; color:#fff; font-size:10px; font-weight:900; border-radius:20px; padding:1px 6px; font-family:'Nunito',sans-serif; min-width:18px; text-align:center; }

    @keyframes badgePulse {
        0%   { transform: scale(1); }
        50%  { transform: scale(1.3); background: var(--yg); color: var(--gd); }
        100% { transform: scale(1); }
    }
    .badge-pulse { animation: badgePulse .6s ease; }

    .pagination { flex-wrap: wrap; justify-content: center; gap: 6px; }
    .pagination li { margin: 2px; }
    .pagination .page-link { border-radius: 8px !important; padding: 6px 12px; font-size: 13px; }
    @media (max-width: 768px) {
        .pagination { font-size: 12px; }
        .pagination .page-link { padding: 4px 8px; }
    }

    .btn-escalate-admin {
        background: #fde8e8; color: #8b1a1a; border: 1.5px solid #f0b8b8;
        font-family: 'Nunito', sans-serif; font-weight: 800; font-size: 12px;
        padding: 7px 16px; border-radius: 20px; cursor: pointer; transition: all .2s;
    }
    .btn-escalate-admin:hover { background: #f8d0d0; border-color: #e08888; }

    .btn-update    { background:var(--ygl); color:var(--gd); font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; padding:7px 16px; border-radius:20px; border:1.5px solid var(--bd); cursor:pointer; transition:all .2s; }
    .btn-update:hover { background:#d8eda0; border-color:var(--gl); }
    .btn-resolve-t { background:#e8f5ee; color:#1a5a3a; font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; padding:7px 16px; border-radius:20px; border:1.5px solid #a8ddc0; cursor:pointer; transition:all .2s; }
    .btn-resolve-t:hover { background:#c8ead8; }
    .btn-escalate-t { background:#fde8e8; color:#8b1a1a; font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; padding:7px 16px; border-radius:20px; border:1.5px solid #f0c0c0; cursor:pointer; transition:all .2s; }
    .btn-escalate-t:hover { background:#f8c8c8; }

    .status-opts { display:flex; flex-direction:column; gap:8px; }
    .status-opt { border:1.5px solid var(--bd); border-radius:12px; padding:12px 16px; cursor:pointer; transition:all .2s; background:var(--cr); display:flex; align-items:center; gap:12px; }
    .status-opt:hover { border-color:var(--gl); background:var(--ygl); }
    .status-opt.selected { border-color:var(--gd); background:var(--ygl); box-shadow:0 0 0 2px var(--yg); }
    .status-opt .so-dot   { width:12px; height:12px; border-radius:50%; flex-shrink:0; }
    .status-opt .so-label { font-weight:800; font-size:14px; font-family:'Nunito',sans-serif; }
    .status-opt .so-desc  { font-size:12px; color:var(--tm); }

    .esc-timeline { background:#fde8e8; border-radius:10px; padding:12px 14px; }
    .etl-item { display:flex; gap:10px; font-size:12px; padding-bottom:8px; }
    .etl-item:last-child { padding-bottom:0; }
    .etl-dot { width:8px; height:8px; border-radius:50%; background:#8b1a1a; flex-shrink:0; margin-top:4px; }
    .etl-time { color:#8b1a1a; font-weight:700; min-width:70px; }
    .etl-text { color:#5a1a1a; font-weight:600; }
@endsection

{{-- ══ SIDEBAR ══ --}}
@section('sidebar')

    {{-- Queue nav --}}
    <div class="sidebar-card mb-3">
        <div class="sidebar-head">Queue</div>
        <ul class="list-group sidebar-menu rounded-0">
            <li class="list-group-item {{ $status === 'active' ? 'active' : '' }}">
                <a href="{{ route('supervisor.support.dashboard', ['status' => 'active']) }}"
                   class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-grid me-2"></i>Active</span>
                    <span class="badge-count">{{ $counts['active'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'awaiting-classification' ? 'active' : '' }}">
                <a href="{{ route('supervisor.support.dashboard', ['status' => 'awaiting-classification']) }}"
                   class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-tags me-2"></i>Awaiting Classification</span>
                    <span class="badge-count">{{ $counts['awaiting_classification'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'awaiting-tech-ack' ? 'active' : '' }}">
                <a href="{{ route('supervisor.support.dashboard', ['status' => 'awaiting-tech-ack']) }}"
                   class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-person-check me-2"></i>Awaiting Tech Acknowledgment</span>
                    <span class="badge-count">{{ $counts['awaiting_tech_ack'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'in-progress' ? 'active' : '' }}">
                <a href="{{ route('supervisor.support.dashboard', ['status' => 'in-progress']) }}"
                   class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-arrow-repeat me-2"></i>In Progress</span>
                    <span class="badge-count">{{ $counts['in_progress'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'escalated' ? 'active' : '' }}">
                <a href="{{ route('supervisor.support.dashboard', ['status' => 'escalated']) }}"
                   class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-exclamation-triangle me-2"></i>Escalated</span>
                    <span class="badge-count">{{ $counts['escalated'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'pending-reclassification' ? 'active' : '' }}">
                <a href="{{ route('supervisor.support.dashboard', ['status' => 'pending-reclassification']) }}"
                   class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-arrow-repeat me-2"></i>Pending Reclassification</span>
                    <span class="badge-count">{{ $counts['pending_reclassification'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'pending-supervisor-approval' ? 'active' : '' }}">
                <a href="{{ route('supervisor.support.dashboard', ['status' => 'pending-supervisor-approval']) }}"
                   class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-clock-history me-2"></i>Pending Supervisor Approval</span>
                    <span class="badge-count">{{ $counts['pending_supervisor_approval'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'pending-closure' ? 'active' : '' }}">
                <a href="{{ route('supervisor.support.dashboard', ['status' => 'pending-closure']) }}"
                   class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-hourglass-split me-2"></i>Pending Closure</span>
                    <span class="badge-count">{{ $counts['pending_closure'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'awaiting-requestor' ? 'active' : '' }}">
                <a href="{{ route('supervisor.support.dashboard', ['status' => 'awaiting-requestor']) }}"
                   class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-person-check me-2"></i>Awaiting Requestor</span>
                    <span class="badge-count">{{ $counts['awaiting_requestor'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'closed' ? 'active' : '' }}">
                <a href="{{ route('supervisor.support.dashboard', ['status' => 'closed']) }}"
                   class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-check-circle me-2"></i>Closed</span>
                    <span class="badge-count">{{ $counts['closed'] }}</span>
                </a>
            </li>
        </ul>
    </div>

    {{-- Work Hours Calendar --}}
    <div class="sidebar-card mb-3">
        <ul class="list-group sidebar-menu rounded-0">
            <li class="list-group-item">
                <a href="{{ route('supervisor.support.calendar') }}" class="d-flex align-items-center gap-2 text-decoration-none w-100">
                    <i class="bi bi-calendar3-week me-1"></i>Work Hours Calendar
                </a>
            </li>
        </ul>
    </div>

    {{-- Settings --}}
    <div class="sidebar-card mb-3">
        <div class="sidebar-head"><i class="bi bi-gear me-1"></i>Settings</div>
        <ul class="list-group sidebar-menu rounded-0">
            <li class="list-group-item">
                <a href="{{ route('portal.sla-rules.index') }}" class="d-flex align-items-center gap-2 text-decoration-none w-100">
                    <i class="bi bi-stopwatch me-1"></i>SLA Rules
                </a>
            </li>
            <li class="list-group-item">
                <a href="{{ route('portal.holidays.index') }}" class="d-flex align-items-center gap-2 text-decoration-none w-100">
                    <i class="bi bi-calendar-x me-1"></i>Holidays
                </a>
            </li>
            <li class="list-group-item">
                <a href="{{ route('portal.leaves.index') }}" class="d-flex align-items-center gap-2 text-decoration-none w-100">
                    <i class="bi bi-calendar-minus me-1"></i>Leave
                </a>
            </li>
        </ul>
    </div>

    {{-- IT Support Specialists availability --}}
    <div class="sidebar-card">
        <div class="sidebar-head">IT Support Specialists</div>
        <div>
            @forelse($technicians as $tech)
                @php
                    $initials = strtoupper(substr($tech->name, 0, 1)) .
                                strtoupper(substr($tech->name, strpos($tech->name, ' ') + 1, 1));
                @endphp
                <div class="tech-row">
                    <div class="tech-av-lg">{{ $initials }}</div>
                    <div>
                        <div class="tech-name">{{ $tech->name }}</div>
                        <div class="tech-load">{{ $tech->free_time_label }}</div>
                    </div>
                    <div class="avail-dot {{ $tech->availability }}"
                         title="{{ match($tech->schedule_status) { 'overtime' => 'Overtime', 'on_leave' => 'On approved leave', default => ucfirst($tech->availability) } }}"></div>
                </div>
            @empty
                <div class="p-3" style="font-size:13px;color:var(--tm)">
                    No technicians found.
                </div>
            @endforelse
        </div>
        <div class="p-2 px-3" style="font-size:11px;color:var(--tm);border-top:1px solid var(--bd)">
            <span class="me-3"><span class="avail-dot free d-inline-block me-1"></span>Available</span>
            <span class="me-3"><span class="avail-dot busy d-inline-block me-1"></span>Busy</span>
            <span><span class="avail-dot full d-inline-block me-1"></span>Overtime</span>
        </div>
    </div>

@endsection

{{-- ══ MAIN CONTENT ══ --}}
@section('content')

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3">
            <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @include('partials.schedule-conflict-modal')

    {{-- Controls --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <span class="font-brand fw-900" style="font-size:22px">
            @php
                $labels = [
                    'active' => 'Active',
                    'awaiting-classification' => 'Awaiting Classification',
                    'awaiting-tech-ack' => 'Awaiting Tech Acknowledgment',
                    'in-progress' => 'In Progress', 'escalated' => 'Escalated',
                    'pending-reclassification' => 'Pending Reclassification',
                    'pending-supervisor-approval' => 'Pending Supervisor Approval', 'pending supervisor approval' => 'Pending Supervisor Approval',
                    'pending-closure' => 'Pending Closure',
                    'awaiting-requestor' => 'Awaiting Requestor',
                    'closed' => 'Closed',
                ];
            @endphp
            {{ $labels[$status] ?? 'All Tickets' }}
        </span>
        <form method="GET" action="{{ route('supervisor.support.dashboard') }}"
              class="d-flex gap-2 flex-wrap" id="searchForm">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="search-wrap">
                <i class="bi bi-search" style="color:var(--tm)"></i>
                <input type="text" name="search" id="searchInput"
                       placeholder="Search by name, ID, issue…"
                       value="{{ $search }}" autocomplete="off">
            </div>
            <select class="sort-select" name="sort" onchange="this.form.submit()">
                <option value="newest"   {{ $sort === 'newest'   ? 'selected' : '' }}>Newest first</option>
                <option value="oldest"   {{ $sort === 'oldest'   ? 'selected' : '' }}>Oldest first</option>
                <option value="priority" {{ $sort === 'priority' ? 'selected' : '' }}>Priority</option>
            </select>
        </form>
    </div>

    {{-- Tab pills --}}
    <div class="d-flex flex-wrap gap-2 mb-3">
        @php
            $tabs = [
                'active'                  => ['label' => 'Active',                  'count' => $counts['active']],
                'awaiting-classification' => ['label' => 'Awaiting Classification', 'count' => $counts['awaiting_classification']],
                'awaiting-tech-ack'       => ['label' => 'Awaiting Tech Ack.',       'count' => $counts['awaiting_tech_ack']],
                'in-progress'             => ['label' => 'In Progress',             'count' => $counts['in_progress']],
                'escalated'               => ['label' => 'Escalated',               'count' => $counts['escalated']],
                'pending-reclassification' => ['label' => 'Pending Reclassification', 'count' => $counts['pending_reclassification']],
                'pending-supervisor-approval'         => ['label' => 'Pending Supervisor Approval',         'count' => $counts['pending_supervisor_approval']],
                'pending-closure'         => ['label' => 'Pending Closure',         'count' => $counts['pending_closure']],
                'awaiting-requestor'      => ['label' => 'Awaiting Requestor',      'count' => $counts['awaiting_requestor']],
                'closed'                  => ['label' => 'Closed',                  'count' => $counts['closed']],
            ];
        @endphp
        @foreach($tabs as $key => $tab)
            <a href="{{ route('supervisor.support.dashboard', array_merge(request()->except('status'), ['status' => $key])) }}"
               class="tab-pill {{ $status === $key ? 'active' : '' }}">
                {{ $tab['label'] }} ({{ $tab['count'] }})
            </a>
        @endforeach
    </div>

    {{-- Ticket list --}}
    <div class="d-flex flex-column gap-3" id="ticketList">

        @forelse($tickets as $ticket)
            @php
                // Check if Supervisor already acknowledged
                $supervisorAcknowledged = $ticket->statusHistories
                    ->contains(function ($history) {
                        return str_contains(
                            $history->notes ?? '',
                            'Acknowledged by Supervisor'
                        );
                    });

                // Show Acknowledge first
                $needsAck =
                    $ticket->status === 'Awaiting Supervisor'
                    && !$supervisorAcknowledged;

                // Show Classify only after Supervisor acknowledged
                $needsClass =
                    $ticket->status === 'Awaiting Supervisor'
                    && $supervisorAcknowledged
                    && !$ticket->assigned_to;
                $isInProgress = $ticket->status === 'In Progress';

                $cardClass = match(true) {
                    $needsAck                          => 'unassigned',
                    $needsClass                        => 'awaiting-supervisor',
                    $ticket->status === 'In Progress'  => 'in-progress',
                    $ticket->status === 'Escalated'    => 'escalated',
                    $ticket->status === 'Pending Reclassification' => 'escalated',
                    $ticket->status === 'Pending Supervisor Approval' => 'pending-supervisor-approval',
                    $ticket->status === 'Pending Closure'  => 'pending-closure',
                    $ticket->status === 'Awaiting Requestor' => 'awaiting-requestor',
                    $ticket->status === 'Closed'       => 'closed',
                    default                            => 'awaiting-supervisor'
                };

                $badgeClass = match(true) {
                    $needsAck                          => 'badge-unassigned',
                    $needsClass                        => 'badge-awaiting-supervisor',
                    $ticket->status === 'Awaiting Support Specialist Acknowledgement' => 'badge-awaiting-requestor',
                    $ticket->status === 'In Progress'  => 'badge-in-progress',
                    $ticket->status === 'Escalated'    => 'badge-escalated',
                    $ticket->status === 'Pending Reclassification' => 'badge-escalated',
                    $ticket->status === 'Pending Supervisor Approval' => 'badge-pending-supervisor-approval',
                    $ticket->status === 'Pending Closure'  => 'badge-pending-closure',
                    $ticket->status === 'Awaiting Requestor' => 'badge-awaiting-requestor',
                    $ticket->status === 'Closed'       => 'badge-closed',
                    default                            => 'badge-awaiting-supervisor'
                };

                $badgeLabel = match(true) {
                    $needsAck                          => '<i class="bi bi-inbox me-1"></i>Needs Acknowledgment',
                    $needsClass                        => '<i class="bi bi-tags me-1"></i>Needs Classification',
                    $ticket->status === 'Awaiting Support Specialist Acknowledgement' => '<i class="bi bi-hourglass-split me-1"></i>Awaiting Tech Acknowledgment',
                    $ticket->status === 'In Progress'  => '<i class="bi bi-gear-fill me-1"></i>In Progress',
                    $ticket->status === 'Escalated'    => '<i class="bi bi-exclamation-triangle-fill me-1"></i>Escalated',
                    $ticket->status === 'Pending Reclassification' => '<i class="bi bi-arrow-repeat me-1"></i>Pending Reclassification',
                    $ticket->status === 'Pending Supervisor Approval' => '<i class="bi bi-clock-history me-1"></i>Pending Supervisor Approval',
                    $ticket->status === 'Pending Closure'  => '<i class="bi bi-hourglass-split me-1"></i>Pending Closure',
                    $ticket->status === 'Awaiting Requestor' => '<i class="bi bi-person-check me-1"></i>Awaiting Requestor',
                    $ticket->status === 'Closed'       => '<i class="bi bi-check-circle-fill me-1"></i>Closed',
                    default                            => '● Awaiting Supervisor'
                };

                $priorityClass = match($ticket->ticket_type) {
                    'Critical' => 'pri-critical',
                    'High'     => 'pri-high',
                    'Medium'   => 'pri-medium',
                    'Low'      => 'pri-low',
                    default    => ''
                };

                $techInitials = $ticket->assignedTo
                    ? strtoupper(substr($ticket->assignedTo->name, 0, 1)) .
                    strtoupper(substr($ticket->assignedTo->name, strpos($ticket->assignedTo->name, ' ') + 1, 1))
                    : '—';
            @endphp

            <div class="ticket-card {{ $cardClass }} p-3"
                 data-status="{{ $cardClass }}"
                 data-priority="{{ strtolower($ticket->ticket_type ?? '') }}">

                {{-- Header --}}
                <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="ticket-id">#{{ $ticket->ticket_number }}</span>
                        @if($ticket->request_category)
                            <span class="badge-type">{{ $ticket->request_category }}</span>
                        @endif
                        @if($ticket->ticket_type)
                            <span class="meta-item">
                                <span class="priority-dot {{ $priorityClass }}"></span>
                                {{ $ticket->ticket_type }}
                            </span>
                        @endif
                        @if($ticket->escalation_level > 0)
                            <span class="esc-level">
                                <i class="bi bi-arrow-up me-1"></i>
                                Level {{ $ticket->escalation_level }}
                            </span>
                        @endif
                    </div>
                    <span class="badge-status {{ $badgeClass }}">{!! $badgeLabel !!}</span>
                </div>

                {{-- Title & desc --}}
                <div class="ticket-title mb-1">{{ $ticket->subject }}</div>
                <div class="ticket-desc mb-2">{{ Str::limit($ticket->concern, 140) }}</div>

                {{-- Recent activity --}}
                @if($ticket->statusHistories->isNotEmpty())
                    <div class="esc-timeline mb-3">
                        <div class="fw-800 mb-2"
                             style="font-size:12px;color:var(--tm);text-transform:uppercase;letter-spacing:.4px">
                            <i class="bi bi-clock-history me-1"></i>Recent Activity
                        </div>
                        @foreach($ticket->statusHistories->sortByDesc('changed_at')->take(3) as $history)
                            <div class="etl-item">
                                <div class="etl-dot"></div>
                                <div>
                                    <span class="etl-time">
                                        {{ \Carbon\Carbon::parse($history->changed_at)->format('M d, g:i A') }}
                                    </span>
                                    <span class="etl-text ms-2">{{ $history->notes }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Meta --}}
                <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                    @if($ticket->user)
                        <span class="meta-item">
                            <i class="bi bi-person"></i> {{ $ticket->user->name }}
                        </span>
                    @endif
                    @if($ticket->user?->department)
                        <span class="meta-item">
                            <i class="bi bi-building"></i>
                            {{ $ticket->user->department->department_name }}
                        </span>
                    @endif
                    <span class="meta-item">
                        <i class="bi bi-calendar3"></i>
                        {{ $ticket->created_at->diffForHumans() }}
                    </span>

                    {{-- SLA Status indicator (In Progress only) --}}
                    @if($ticket->status === 'In Progress' && $ticket->sla_due_at)
                        @php
                            $slaSecondsLeft = $ticket->slaSecondsRemaining();
                            $isBreached = $slaSecondsLeft <= 0;
                            $isAtRisk   = !$isBreached && $ticket->isSlaAtRisk();

                            $abs = abs($slaSecondsLeft);
                            $h   = intdiv($abs, 3600);
                            $m   = intdiv($abs % 3600, 60);
                            $slaTimeLeft = ($h > 0 ? $h . 'h ' : '') . $m . 'm';

                            $slaColor = $isBreached ? '#e24b4a' : ($isAtRisk ? '#f5c842' : '#3fb950');
                            $slaBg    = $isBreached ? '#fde8e8' : ($isAtRisk ? '#fff4cc' : '#d4f0d4');
                            $slaIcon  = $isBreached ? 'bi-exclamation-triangle-fill' : ($isAtRisk ? 'bi-clock-history' : 'bi-check-circle');
                            $slaLabel = $isBreached ? 'SLA Breached' : ($isAtRisk ? 'SLA At Risk' : 'SLA OK');
                        @endphp
                        <span style="background:{{ $slaBg }};color:{{ $slaColor }};font-size:11px;font-weight:800;border-radius:20px;padding:3px 10px;display:inline-flex;align-items:center;gap:5px;border:1px solid {{ $slaColor }}20">
                            <i class="bi {{ $slaIcon }}"></i>
                            {{ $slaLabel }}
                            @if($isBreached)
                                · {{ $slaTimeLeft }} over
                            @else
                                · {{ $slaTimeLeft }} left
                            @endif
                        </span>
                    @endif

                    @if($ticket->assignedTo)
                        <span class="tech-chip ms-auto">
                            <span class="tc-av">{{ $techInitials }}</span>
                            {{ $ticket->assignedTo->name }}
                        </span>
                    @endif
                </div>

                {{-- Task effort/schedule + assigned specialist's current remaining
                     capacity — otherwise the supervisor has no visibility into either
                     once a ticket is assigned, only while actively picking a tech. --}}
                @if($ticket->assignedTo && $ticket->scheduled_start)
                    @php
                        $taskEffort = ($ticket->effectiveResponseTimeMinutes() ?? 0) + ($ticket->effectiveResolutionTimeMinutes() ?? 0);
                        $fmtTaskMin = fn($m) => $m ? (intdiv($m, 60) > 0 ? intdiv($m, 60) . 'h ' . ($m % 60) . 'm' : $m . 'm') : '—';
                        $techDotColor = match($ticket->assignedTo->availability ?? '') {
                            'free' => '#4a7c4a', 'busy' => '#f5c842', 'full' => '#e24b4a', default => 'var(--tm)'
                        };
                    @endphp
                    <div class="d-flex flex-wrap align-items-center gap-3 mb-3" style="font-size:12px">
                        <span class="meta-item">
                            <i class="bi bi-stopwatch"></i>
                            Task: {{ $fmtTaskMin($taskEffort) }} total
                        </span>
                        <span class="meta-item">
                            <i class="bi bi-calendar-range"></i>
                            Scheduled {{ $ticket->scheduled_start->timezone('Asia/Manila')->format('g:i A') }}–{{ $ticket->scheduled_end->timezone('Asia/Manila')->format('g:i A, M d') }}
                            @if($ticket->is_overtime)<span style="color:#e24b4a;font-weight:800"> (Overtime)</span>@endif
                        </span>
                        <span class="meta-item">
                            <i class="bi bi-circle-fill" style="font-size:8px;color:{{ $techDotColor }}"></i>
                            {{ $ticket->assignedTo->name }}: {{ $ticket->assignedTo->free_time_label }}
                        </span>
                    </div>
                @endif

                {{-- Escalation banner --}}
                @if($ticket->status === 'Escalated')
                    <div class="esc-banner p-2 mb-3">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        Escalated by {{ $ticket->assignedTo->name ?? 'specialist' }} — Level {{ $ticket->escalation_level }}.
                        Reassign to another specialist or take over directly.
                    </div>
                @endif

                {{-- Action buttons --}}
                <div class="d-flex gap-2 flex-wrap">

                    {{-- Step 3: Acknowledge --}}
                    @if($needsAck)
                        <form method="POST"
                              action="{{ route('supervisor.support.tickets.acknowledge', $ticket) }}">
                            @csrf
                            <button type="submit" class="btn-acknowledge">
                                <i class="bi bi-eye me-1"></i>Acknowledge
                            </button>
                        </form>
                    @endif

                    {{-- Steps 4 & 5: Classify + Assign (combined) --}}
                    @if($needsClass)
                        @if($ticket->subcategory_name)
                            <span class="d-block w-100" style="font-size:11px;color:var(--tm);margin-bottom:2px">
                                <i class="bi bi-tags"></i>
                                Suggested classification: <strong>{{ $ticket->subcategory_name }}</strong> ({{ $ticket->ticket_type }})
                            </span>
                        @endif
                        <button class="btn-assign"
                                onclick="openClassifyAssignModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}', {{ Js::from([
                                    'categoryId' => $ticket->sla_category_id,
                                    'subcategoryName' => $ticket->subcategory_name,
                                    'priority' => $ticket->ticket_type,
                                    'response' => $ticket->response_time_minutes,
                                    'resolution' => $ticket->resolution_time_minutes,
                                ]) }})">
                            <i class="bi bi-tags me-1"></i>{{ $ticket->subcategory_name ? 'Review & Assign' : 'Classify & Assign' }}
                        </button>
                    @endif

                    {{-- Pending Reclassification: Approve / Reject --}}
                    @if($ticket->status === 'Pending Reclassification')
                        @php
                            $pendingReclass = $ticket->reclassificationRequests
                                ->where('status', 'pending')
                                ->sortByDesc('requested_at')
                                ->first();
                        @endphp
                        @if($pendingReclass)
                            <div class="w-100 mb-2 p-2 px-3 rounded" style="background:var(--ygl);font-size:12px;color:var(--gd)">
                                <div class="mb-1">
                                    <i class="bi bi-person me-1"></i>
                                    Requested by <strong>{{ $pendingReclass->requestedBy?->name ?? 'Unknown' }}</strong>
                                </div>
                                <div class="mb-1">
                                    <strong>Current:</strong> {{ $pendingReclass->current_subcategory_name ?? 'Unclassified' }}
                                    ({{ $pendingReclass->current_priority ?? 'N/A' }})
                                    <i class="bi bi-arrow-right mx-1"></i>
                                    <strong>Proposed:</strong> {{ $pendingReclass->proposed_subcategory_name }}
                                    ({{ $pendingReclass->proposed_priority }})
                                </div>
                                <div><strong>Reason:</strong> {{ $pendingReclass->reason }}</div>
                            </div>
                        @endif
                        <button class="btn-assign"
                                onclick="openApproveReclassModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-check-lg me-1"></i>Approve
                        </button>
                        <button class="btn-escalate-t"
                                onclick="openRejectReclassModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-x-lg me-1"></i>Reject
                        </button>
                    @endif

                    {{-- In Progress: Update + Resolve + Escalate + Message --}}
                    @if($isInProgress)
                        <button class="btn-update"
                                onclick="openUpdateModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-pencil me-1"></i>Add Update
                        </button>
                        <button class="btn-resolve-t"
                                onclick="openResolveModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-check-circle me-1"></i>Mark Resolved
                        </button>
                        <button class="btn-escalate-t"
                                onclick="openEscModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-exclamation-triangle me-1"></i>Escalate
                        </button>
                    @endif
                    {{-- Escalated: Reassign + Take Over + Escalate to Admin --}}
                    @if($ticket->status === 'Escalated')
                        <button class="btn-reassign"
                                onclick="openReassignModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-arrow-left-right me-1"></i>Reassign
                        </button>
                        <form method="POST" action="{{ route('supervisor.support.tickets.takeover', $ticket) }}">
                            @csrf
                            <button type="submit" class="btn-takeover">
                                <i class="bi bi-person-check me-1"></i>Take Over
                            </button>
                        </form>
                        <form method="POST" action="{{ route('supervisor.support.tickets.escalate-admin', $ticket) }}"
                            onsubmit="return confirm('Escalate ticket #{{ $ticket->ticket_number }} to Supervisor - IT Admin?');">
                            @csrf
                            <button type="submit" class="btn-escalate-admin">
                                <i class="bi bi-shield-exclamation me-1"></i>Escalate to Admin
                            </button>
                        </form>
                        <button class="btn-cannot-resolve"
                                onclick="openCannotResolveModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-x-octagon me-1"></i>Cannot Resolve
                        </button>
                    @endif

                    {{-- Pending Supervisor Approval: Validate Resolution / Request Revision --}}
                    @if($ticket->status === 'Pending Supervisor Approval')
                        @if($ticket->validated_at)
                            <span class="resolve-info px-3 py-2">
                                <i class="bi bi-check-circle-fill me-1"></i>Validated — awaiting Helpdesk closure
                            </span>
                        @else
                            <button class="btn-resolve"
                                    onclick="openValidateModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                                <i class="bi bi-check-circle me-1"></i>Validate Resolution
                            </button>
                            <button class="btn-cannot-resolve"
                                    onclick="openRevisionModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                                <i class="bi bi-arrow-counterclockwise me-1"></i>Request Revision
                            </button>
                        @endif
                    @endif

                    {{-- Closed: printable service report --}}
                    @if($ticket->status === 'Closed')
                        <button type="button" class="btn-service-report"
                                onclick="openServiceReportPreview('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-file-earmark-pdf me-1"></i>Service Report
                        </button>
                    @endif

                    {{-- Chat — available on every status --}}
                    <button class="btn-chat"
                            onclick="openChatModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                        <i class="bi bi-chat-dots me-1"></i>Message
                        @php $unread = $ticket->unreadMessages()->count(); @endphp
                        @if($unread > 0)
                            <span class="chat-count-badge" id="badge-{{ $ticket->id }}">{{ $unread }}</span>
                        @endif
                    </button>
                </div>
            </div>
        @empty
            <div class="ticket-card p-5 text-center">
                <div style="font-size:48px;opacity:.3">🎫</div>
                <div class="mt-3 font-brand fw-900" style="font-size:18px;color:var(--tm)">
                    No tickets found.
                </div>
            </div>
        @endforelse

    </div>

    {{-- Pagination --}}
    @if($tickets->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $tickets->onEachSide(1)->links('pagination::bootstrap-5') }}
        </div>
    @endif

@endsection

{{-- ══ MODALS ══ --}}
@section('modals')

    {{-- Classify & Assign modal (combined steps 4 + 5) --}}
    <div class="modal fade" id="classifyAssignModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header-gd d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Classify & Assign — <em id="caTicketRef">#TKT-0000</em></h5>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <form method="POST" id="classifyAssignForm">
                    @csrf
                    <input type="hidden" name="sla_rule_id" id="caSlaRuleId">
                    <input type="hidden" name="technician_id" id="caTechId">

                    <div class="modal-body px-4 py-4">

                        <label class="form-label mb-2">Category</label>
                        <div class="d-flex flex-wrap gap-2 mb-3" id="caCategoryList"></div>

                        <div id="caSubWrap" class="d-none">
                            <label class="form-label mb-2">Subcategory & Priority</label>
                            <div class="d-flex flex-column gap-2 mb-3" id="caSubList"></div>
                        </div>

                        <div id="caOverrideWrap" class="d-none mb-3">
                            <label class="form-label mb-2">
                                SLA & Priority
                                <span style="font-weight:400;color:var(--tm)">(defaults from the rule above — adjust if this ticket needs different targets)</span>
                            </label>

                            <div class="mb-2">
                                <label class="form-label" style="font-size:11px">
                                    Workload Class
                                    <span style="font-weight:400;color:var(--tm)">(optional — overrides response/resolution time below)</span>
                                </label>
                                <select class="form-select form-select-sm" name="workload_class_id" id="caWorkloadClass">
                                    <option value="">— None —</option>
                                </select>
                                <div id="caWorkloadManualHint" class="d-none" style="font-size:11px;color:var(--tm);font-weight:600;margin-top:4px">
                                    <i class="bi bi-info-circle me-1"></i>This class has no fixed resolution target — enter the agreed resolution time below.
                                </div>
                            </div>

                            <div class="d-flex gap-2 flex-wrap">
                                <div style="flex:1;min-width:110px">
                                    <label class="form-label" style="font-size:11px">Priority</label>
                                    <select class="form-select form-select-sm" name="priority" id="caPriority">
                                        <option value="Critical">Critical</option>
                                        <option value="High">High</option>
                                        <option value="Medium">Medium</option>
                                        <option value="Low">Low</option>
                                    </select>
                                </div>
                                <div style="flex:1;min-width:130px">
                                    <label class="form-label" style="font-size:11px">Response Time (min)</label>
                                    <input type="number" class="form-control form-control-sm" name="response_time_minutes"
                                           id="caResponseTime" min="5" max="43200">
                                </div>
                                <div style="flex:1;min-width:130px">
                                    <label class="form-label" style="font-size:11px">Resolution Time (min)</label>
                                    <input type="number" class="form-control form-control-sm" name="resolution_time_minutes"
                                           id="caResolutionTime" min="5" max="43200">
                                </div>
                            </div>
                        </div>

                        <hr style="border-color:var(--bd)">

                        <label class="form-label mb-2">Assign Support Specialist</label>
                        <div class="d-flex flex-column gap-2" id="caTechList">
                            @foreach($technicians as $tech)
                                @php
                                    $initials = strtoupper(substr($tech->name, 0, 1)) . strtoupper(substr($tech->name, strpos($tech->name, ' ') + 1, 1));
                                    // Bar fills with how much of the 8am-5pm day is still OPEN, not how
                                    // loaded they are — a big bar reads as "lots of room left".
                                    $loadPct  = min(100, round($tech->free_minutes_today / \App\Services\TicketScheduler::WORKING_MINUTES_PER_DAY * 100));
                                    $barClass = $tech->availability === 'busy' ? 'busy' : ($tech->availability === 'full' ? 'full' : '');
                                    $window   = $tech->free_window_today;
                                    $availLabel = $window
                                        ? $window['start']->timezone('Asia/Manila')->format('g:i A') . ' - ' . $window['end']->timezone('Asia/Manila')->format('g:i A')
                                        : $tech->free_time_label;
                                @endphp
                                {{-- Overtime-status specialists remain selectable: assigning to them past
                                     capacity is exactly what triggers the overtime/next-day prompt. --}}
                                <div class="tech-select-option"
                                     data-tech-id="{{ $tech->id }}">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="tech-av-lg">{{ $initials }}</div>
                                        <div>
                                            <div class="ts-name">{{ $tech->name }}</div>
                                            <div class="ts-load">{{ $availLabel }}</div>
                                        </div>
                                        <span class="avail-dot {{ $tech->availability }} ms-auto"></span>
                                    </div>
                                    <div class="load-bar-wrap"><div class="load-bar {{ $barClass }}" style="width:{{ $loadPct }}%"></div></div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-3">
                            <label class="form-label">Notes (optional)</label>
                            <textarea class="form-control" name="notes" rows="2" placeholder="Context for the specialist…"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
                        <button type="button" class="btn-cancel-modal" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-confirm">Confirm Classification & Assignment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Reassign modal --}}
    <div class="modal fade" id="reassignModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header-gd d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Reassign <em>Specialist</em></h5>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <form method="POST" id="reassignForm">
                    @csrf
                    <div class="modal-body px-4 py-4">
                        <div class="mb-3 p-2 px-3 rounded"
                             style="background:var(--ygl);font-size:13px;color:var(--gd)">
                            Ticket <strong id="reassignTicketRef"></strong> —
                            Select a new specialist below.
                        </div>
                        <label class="form-label mb-2">Choose specialist</label>
                        <div class="d-flex flex-column gap-2" id="reassignTechList">
                            @foreach($technicians as $tech)
                                @php
                                    $initials = strtoupper(substr($tech->name, 0, 1)) . strtoupper(substr($tech->name, strpos($tech->name, ' ') + 1, 1));
                                    // Bar fills with how much of the 8am-5pm day is still OPEN, not how
                                    // loaded they are — a big bar reads as "lots of room left".
                                    $loadPct  = min(100, round($tech->free_minutes_today / \App\Services\TicketScheduler::WORKING_MINUTES_PER_DAY * 100));
                                    $barClass = $tech->availability === 'busy' ? 'busy' : ($tech->availability === 'full' ? 'full' : '');
                                    $window   = $tech->free_window_today;
                                    $availLabel = $window
                                        ? $window['start']->timezone('Asia/Manila')->format('g:i A') . ' - ' . $window['end']->timezone('Asia/Manila')->format('g:i A')
                                        : $tech->free_time_label;
                                @endphp
                                <div class="tech-select-option"
                                     data-tech-id="{{ $tech->id }}">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="tech-av-lg">{{ $initials }}</div>
                                        <div>
                                            <div class="ts-name">{{ $tech->name }}</div>
                                            <div class="ts-load">{{ $availLabel }}</div>
                                        </div>
                                        <span class="avail-dot {{ $tech->availability }} ms-auto"></span>
                                    </div>
                                    <div class="load-bar-wrap"><div class="load-bar {{ $barClass }}" style="width:{{ $loadPct }}%"></div></div>
                                </div>
                            @endforeach
                        </div>
                        <input type="hidden" name="technician_id" id="reassignTechId">
                        <div class="mt-3">
                            <label class="form-label">Note (optional)</label>
                            <textarea class="form-control" name="notes" rows="2" placeholder="Reason for reassignment…"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
                        <button type="button" class="btn-cancel-modal" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-confirm">Confirm Reassignment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Validate Resolution modal --}}
    <div class="modal fade" id="validateModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header-gd d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Validate <em>Resolution</em></h5>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <form method="POST" id="validateForm">
                    @csrf
                    <div class="modal-body px-4 py-4">
                        <div class="resolve-info p-3 mb-3">
                            <i class="bi bi-check-circle me-1"></i>
                            Ticket <strong id="validateRef"></strong> —
                            Confirm resolution quality and readiness for closure.
                        </div>
                        <div>
                            <label class="form-label">
                                Validation notes <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" name="validation_notes"
                                      rows="3" required
                                      placeholder="Confirm resolution quality and service completeness…"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
                        <button type="button" class="btn-cancel-modal" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-confirm">
                            <i class="bi bi-check-circle me-1"></i>Confirm Validation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Request Revision modal --}}
    <div class="modal fade" id="revisionModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header-gd d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Request <em>Revision</em></h5>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <form method="POST" id="revisionForm">
                    @csrf
                    <div class="modal-body px-4 py-4">
                        <div class="p-3 mb-3 rounded" style="background:#eee;color:#555;font-size:13px">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>
                            Ticket <strong id="revisionRef"></strong> — this sends the resolution back to the
                            technician instead of validating it. It reopens as In Progress with your reason attached.
                        </div>
                        <div>
                            <label class="form-label">
                                What needs to be revised? <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" name="revision_notes"
                                      rows="3" required
                                      placeholder="Be specific — this is what the technician will see…"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
                        <button type="button" class="btn-cancel-modal" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-confirm">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Send Back for Revision
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Cannot Resolve modal --}}
    <div class="modal fade" id="cannotResolveModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header-gd d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Mark <em>Cannot Resolve</em></h5>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <form method="POST" id="cannotResolveForm">
                    @csrf
                    <div class="modal-body px-4 py-4">
                        <div class="resolve-info p-3 mb-3">
                            <i class="bi bi-x-octagon me-1"></i>
                            Ticket <strong id="cannotResolveRef"></strong> —
                            this documents why the ticket cannot be technically resolved and
                            sends it straight to Helpdesk for closure & notification.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                                Findings &amp; Analysis <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" name="findings"
                                      rows="3" required
                                      placeholder="What was investigated and why it can't be resolved…"></textarea>
                        </div>
                        <div>
                            <label class="form-label">Other Observation / Recommendation</label>
                            <textarea class="form-control" name="recommendation"
                                      rows="2"
                                      placeholder="Optional — e.g. hardware replacement needed, refer to vendor, user training…"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
                        <button type="button" class="btn-cancel-modal" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-confirm">
                            <i class="bi bi-x-octagon me-1"></i>Confirm Cannot Resolve
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Add Update modal --}}
    <div class="modal fade" id="updateModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header-gd d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Add <em>Update</em></h5>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <form method="POST" id="updateForm">
                    @csrf
                    <div class="modal-body px-4 py-4">
                        <p style="font-size:13px;color:var(--tm)" class="mb-3">
                            Ticket <strong id="updateRef" style="color:var(--gd)"></strong> —
                            Log your progress below.
                        </p>
                        <div class="mb-3">
                            <label class="form-label">What have you done so far?</label>
                            <textarea class="form-control" name="progress_notes" rows="3"
                                      required
                                      placeholder="Describe the steps you've taken, findings, or current status…"></textarea>
                        </div>
                        <div class="mb-1">
                            <label class="form-label">Current work status</label>
                            <div class="status-opts">
                                <div class="status-opt" data-val="Investigating">
                                    <span class="so-dot" style="background:#f5c842"></span>
                                    <div>
                                        <div class="so-label">Investigating</div>
                                        <div class="so-desc">Still diagnosing the root cause</div>
                                    </div>
                                </div>
                                <div class="status-opt selected" data-val="Actively working">
                                    <span class="so-dot" style="background:var(--yg)"></span>
                                    <div>
                                        <div class="so-label">Actively working</div>
                                        <div class="so-desc">Fix is underway</div>
                                    </div>
                                </div>
                                <div class="status-opt" data-val="Waiting for parts or access">
                                    <span class="so-dot" style="background:#d85a30"></span>
                                    <div>
                                        <div class="so-label">Waiting for parts / access</div>
                                        <div class="so-desc">Blocked, pending external resource</div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="work_status" id="workStatusVal"
                                   value="Actively working">
                        </div>
                    </div>
                    <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
                        <button type="button" class="btn-cancel-modal"
                                data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-confirm">
                            <i class="bi bi-arrow-up-circle me-1"></i>Save Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Resolve modal --}}
    <div class="modal fade" id="resolveModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header-gd d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Mark as <em>Resolved</em></h5>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <form method="POST" id="resolveForm">
                    @csrf
                    <div class="modal-body px-4 py-4">
                        <div class="resolve-info p-3 mb-3">
                            <i class="bi bi-check-circle me-1"></i>
                            Resolving <strong id="resolveRef"></strong> —
                            this ends the SLA resolution timer and sends the ticket
                            for closure.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                                Resolution summary <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" name="resolution_notes"
                                      rows="3" required
                                      placeholder="Describe exactly what was done to resolve the issue…"></textarea>
                        </div>
                        <div>
                            <label class="form-label">Time spent</label>
                            <select class="form-select" name="time_spent">
                                <option value="Less than 30 min">Less than 30 min</option>
                                <option value="30 – 60 min">30 – 60 min</option>
                                <option value="1 – 2 hours" selected>1 – 2 hours</option>
                                <option value="2 – 4 hours">2 – 4 hours</option>
                                <option value="More than 4 hours">More than 4 hours</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
                        <button type="button" class="btn-cancel-modal"
                                data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-confirm">
                            <i class="bi bi-check-circle me-1"></i>Submit Resolution
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Escalate (from In Progress) modal --}}
    <div class="modal fade" id="escModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header-gd d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Escalate to <em>IT Admin</em></h5>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <form method="POST" id="escForm">
                    @csrf
                    <div class="modal-body px-4 py-4">
                        <div class="esc-banner p-3 mb-3">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            <strong id="escRef"></strong> — This sends the ticket to
                            Supervisor - IT Admin for further handling.
                        </div>
                        <div>
                            <label class="form-label">
                                Reason for escalation <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" name="reason"
                                      rows="3" required
                                      placeholder="Explain why this needs to go to IT Admin…"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
                        <button type="button" class="btn-cancel-modal"
                                data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-confirm" style="background:#8b1a1a">
                            <i class="bi bi-exclamation-triangle me-1"></i>Confirm Escalation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Chat Modal --}}
    <div class="modal fade" id="chatModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width:480px">
            <div class="modal-content" style="border-radius:20px;overflow:hidden;border:none">
                <div class="modal-header-gd d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="mb-0">
                            <i class="bi bi-chat-dots-fill me-2" style="color:var(--yg)"></i>
                            Messages — <em id="chatTicketRef">#TKT-0000</em>
                        </h5>
                    </div>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>

                <div id="modalChatMessages"
                    style="height:360px;overflow-y:auto;padding:16px;background:#f8f8f4;display:flex;flex-direction:column;gap:12px;scroll-behavior:smooth">
                    <div class="text-center py-4" style="color:var(--tm);font-size:13px">
                        <div class="spinner-border spinner-border-sm me-2"></div>
                        Loading messages…
                    </div>
                </div>

                <div style="border-top:1.5px solid var(--bd);padding:12px 16px;background:#fff">
                    <div style="font-size:10px;font-weight:800;background:#fde8e8;color:#8b1a1a;border-radius:4px;padding:2px 8px;display:inline-block;margin-bottom:8px;text-transform:uppercase;letter-spacing:.3px">
                        Supervisor
                    </div>
                    <div class="d-flex gap-2 align-items-end">
                        <textarea id="modalChatInput"
                                placeholder="Type a message… (Enter to send)"
                                rows="1"
                                style="flex:1;border:1.5px solid var(--bd);border-radius:20px;padding:9px 14px;font-size:13px;resize:none;outline:none;font-family:'Nunito Sans',sans-serif;max-height:80px;overflow-y:auto;color:var(--gd);background:var(--cr);transition:border-color .2s"
                                onkeydown="handleModalChatKey(event)"
                                onfocus="this.style.borderColor='var(--gl)';this.style.background='#fff'"
                                onblur="this.style.borderColor='var(--bd)';this.style.background='var(--cr)'"></textarea>
                        <button onclick="sendModalMessage()"
                                style="width:38px;height:38px;background:var(--gd);color:var(--yg);border:none;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:14px;flex-shrink:0;transition:background .2s">
                            <i class="bi bi-send-fill"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Approve Reclassification modal --}}
    <div class="modal fade" id="approveReclassModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header-gd d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Approve Re-classification — <em id="arTicketRef">#TKT-0000</em></h5>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <form method="POST" id="approveReclassForm">
                    @csrf
                    <div class="modal-body px-4 py-4">
                        <div class="mb-3 p-2 px-3 rounded" style="background:var(--ygl);font-size:13px;color:var(--gd)">
                            The ticket's classification will be updated to the technician's proposal and
                            sent back to the classify &amp; assign queue for reassignment.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes (optional)</label>
                            <textarea class="form-control" name="review_notes" rows="2"
                                      placeholder="Any context for the record…"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
                        <button type="button" class="btn-cancel-modal" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-confirm">
                            <i class="bi bi-check-lg me-1"></i>Confirm Approval
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Reject Reclassification modal --}}
    <div class="modal fade" id="rejectReclassModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header-gd d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Reject Re-classification — <em id="rrTicketRef">#TKT-0000</em></h5>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <form method="POST" id="rejectReclassForm">
                    @csrf
                    <div class="modal-body px-4 py-4">
                        <div class="info-box-red p-3 mb-3">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            The ticket will be returned to the requesting technician unchanged.
                        </div>
                        <div>
                            <label class="form-label">
                                Reason for rejection <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" name="review_notes" rows="3" required
                                      placeholder="Explain why this re-classification isn't correct…"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
                        <button type="button" class="btn-cancel-modal" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-confirm" style="background:#8b1a1a">
                            <i class="bi bi-x-lg me-1"></i>Confirm Rejection
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-service-report-modal />
@endsection

@section('scripts')
<script>
const slaCategories = @json($slaCategoriesJson);
const workloadClasses = @json($workloadClassesJson);

/* ── Chat modal (global) ── */
let currentChatTicketId = null;
let chatPollInterval    = null;

window.openChatModal = function (ticketId, ticketNumber) {
    currentChatTicketId = ticketId;
    $('#badge-' + ticketId).remove();
    $('#chatTicketRef').text('#' + ticketNumber);
    $('#modalChatMessages').html(`
        <div class="text-center py-4" style="color:var(--tm);font-size:13px">
            <div class="spinner-border spinner-border-sm me-2"></div>
            Loading messages…
        </div>
    `);
    new bootstrap.Modal('#chatModal').show();
    loadChatMessages();
    clearInterval(chatPollInterval);
    chatPollInterval = setInterval(loadChatMessages, 3000);
};

window.sendModalMessage = function () {
    const input = document.getElementById('modalChatInput');
    const msg   = input.value.trim();
    if (!msg || !currentChatTicketId) return;
    input.value = '';
    input.style.height = 'auto';
    fetch(`/tickets/${currentChatTicketId}/messages`, {
        method:  'POST',
        headers: {
            'Content-Type':     'application/json',
            'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ message: msg }),
    })
    .then(r => r.json())
    .then(() => loadChatMessages())
    .catch(err => console.error('Send error:', err));
};

window.handleModalChatKey = function (e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        window.sendModalMessage();
    }
    const ta = document.getElementById('modalChatInput');
    setTimeout(() => {
        ta.style.height = 'auto';
        ta.style.height = Math.min(ta.scrollHeight, 80) + 'px';
    }, 0);
};

function loadChatMessages() {
    if (!currentChatTicketId) return;
    fetch(`/tickets/${currentChatTicketId}/messages`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
    .then(data => {
        const msgs = data.messages;
        const $box = document.getElementById('modalChatMessages');
        if (!$box) return;
        const prevCount = $box.querySelectorAll('[data-msg-id]').length;
        if (!msgs || !msgs.length) {
            $box.innerHTML = `
                <div class="text-center py-4" style="color:var(--tm)">
                    <i class="bi bi-chat-dots" style="font-size:32px;opacity:.3;display:block;margin-bottom:8px"></i>
                    <p style="font-size:13px;font-weight:600;margin:0">No messages yet.<br>Start the conversation!</p>
                </div>`;
            return;
        }
        if (msgs.length === prevCount) return;
        const avColors     = { 'IT Admin':'#fde8e8','IT Support Specialist':'#fff4cc','Helpdesk':'#d4f0d4','Supervisor - Support Specialist':'#fde8e8','Executive':'#e8e0ff' };
        const avTextColors = { 'IT Admin':'#8b1a1a','IT Support Specialist':'#7a5a00','Helpdesk':'#2d5a2d','Supervisor - Support Specialist':'#8b1a1a','Executive':'#4a1a8a' };
        let html = '';
        msgs.forEach(msg => {
            const avBg   = avColors[msg.role]      || '#e8f5b0';
            const avText = avTextColors[msg.role]   || '#1a3c1a';
            const isMe   = msg.is_me;
            html += `
                <div data-msg-id="${msg.id}" style="display:flex;gap:8px;align-items:flex-end;${isMe ? 'flex-direction:row-reverse' : ''}">
                    <div style="width:28px;height:28px;border-radius:50%;background:${avBg};color:${avText};display:flex;align-items:center;justify-content:center;font-family:'Nunito',sans-serif;font-weight:900;font-size:10px;flex-shrink:0">${msg.initials}</div>
                    <div style="max-width:75%">
                        <div style="font-size:10px;font-weight:700;color:var(--tm);margin-bottom:3px;${isMe ? 'text-align:right' : ''}">
                            ${isMe ? 'You' : escapeHtmlChat(msg.sender)}
                            <span style="font-size:9px;background:${avBg};color:${avText};border-radius:4px;padding:1px 5px;margin-left:4px;text-transform:uppercase;letter-spacing:.3px;font-weight:800">${msg.role || 'User'}</span>
                        </div>
                        <div style="padding:9px 13px;border-radius:16px;font-size:13px;line-height:1.5;word-break:break-word;${isMe ? 'background:var(--gd);color:var(--yg);border-bottom-right-radius:4px' : 'background:#fff;color:var(--gd);border-bottom-left-radius:4px;border:1.5px solid var(--bd)'}">
                            ${escapeHtmlChat(msg.message)}
                        </div>
                        <div style="font-size:10px;color:var(--tm);margin-top:3px;font-weight:600;${isMe ? 'text-align:right' : ''}">${msg.time_ago}</div>
                    </div>
                </div>`;
        });
        $box.innerHTML = html;
        $box.scrollTop = $box.scrollHeight;
    })
    .catch(err => console.error('Chat load error:', err));
}

function escapeHtmlChat(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ── Classify & Assign modal ── */
window.openClassifyAssignModal = function (ticketId, ticketNumber, defaults) {
    $('#caTicketRef').text('#' + ticketNumber);
    $('#classifyAssignForm').attr('action', '/supervisor/support/tickets/' + ticketId + '/classify-assign');
    $('#caSlaRuleId, #caTechId').val('');
    $('#caSubWrap, #caOverrideWrap').addClass('d-none');
    $('#caSubList').empty();
    $('#caResponseTime, #caResolutionTime').val('');
    $('#caCategoryList .cat-main-opt, #caTechList .tech-select-option').removeClass('selected');

    const $catList = $('#caCategoryList').empty();
    slaCategories.forEach(cat => {
        $catList.append(`<div class="cat-main-opt" data-cat-id="${cat.id}">${cat.name}</div>`);
    });

    const $wcSelect = $('#caWorkloadClass').empty().append('<option value="">— None —</option>');
    workloadClasses.forEach(wc => {
        $wcSelect.append(
            `<option value="${wc.id}" data-response="${wc.response_minutes}"
                     data-resolution="${wc.resolution_minutes ?? ''}"
                     data-manual="${wc.requires_manual_resolution ? '1' : '0'}">${wc.name}</option>`
        );
    });
    $('#caWorkloadManualHint').addClass('d-none');

    // Pre-select Helpdesk's default classification, if this ticket already has one —
    // the Supervisor can still change any of it, this just saves starting from blank.
    if (defaults && defaults.categoryId) {
        $('#caCategoryList .cat-main-opt').filter(function () {
            return $(this).data('cat-id') === defaults.categoryId;
        }).trigger('click');

        $('#caSubList .cat-sub-opt').filter(function () {
            return $(this).data('name') === defaults.subcategoryName;
        }).trigger('click');

        // The pill click above prefills the override fields from the SLA rule's own
        // defaults — override with the ticket's actual stored values in case Helpdesk
        // customized them at acknowledge time.
        if (defaults.priority) $('#caPriority').val(defaults.priority);
        if (defaults.response) $('#caResponseTime').val(defaults.response);
        if (defaults.resolution) $('#caResolutionTime').val(defaults.resolution);
    }

    new bootstrap.Modal('#classifyAssignModal').show();
};

$(document).on('click', '#caCategoryList .cat-main-opt', function () {
    $('#caCategoryList .cat-main-opt').removeClass('selected');
    $(this).addClass('selected');

    const catId = $(this).data('cat-id');
    const cat   = slaCategories.find(c => c.id === catId);
    const $subList = $('#caSubList').empty();

    if (!cat || !cat.subs.length) {
        $subList.append(`<div style="font-size:12px;color:var(--tm)">No SLA rules defined for this category yet.</div>`);
    } else {
        cat.subs.forEach(sub => {
            const priColor = sub.priority === 'Critical' ? '#8b0000' : (sub.priority === 'High' ? '#e24b4a' : (sub.priority === 'Medium' ? '#f5c842' : '#4a7c4a'));
            $subList.append(`<div class="cat-sub-opt" data-rule-id="${sub.rule_id}" data-name="${sub.name}"
                 data-priority="${sub.priority}" data-response="${sub.response}" data-resolution="${sub.resolution}">
                <div class="sub-check"></div>
                <span style="flex:1">${sub.name}</span>
                <span style="font-size:10px;font-weight:800;color:${priColor}">${sub.priority} · ${sub.resolution}m SLA</span>
            </div>`);
        });
    }
    $('#caSubWrap').removeClass('d-none');
    $('#caOverrideWrap').addClass('d-none');
});

$(document).on('click', '#caSubList .cat-sub-opt', function () {
    $('#caSubList .cat-sub-opt').removeClass('selected');
    $(this).addClass('selected');
    $('#caSlaRuleId').val($(this).data('rule-id'));

    // Prefill the override fields with the rule's defaults — supervisor can still edit them.
    $('#caPriority').val($(this).data('priority'));
    $('#caResponseTime').val($(this).data('response'));
    $('#caResolutionTime').val($(this).data('resolution'));
    $('#caOverrideWrap').removeClass('d-none');
});

$(document).on('click', '#caTechList .tech-select-option:not(.disabled)', function () {
    $('#caTechList .tech-select-option').removeClass('selected');
    $(this).addClass('selected');
    $('#caTechId').val($(this).data('tech-id'));
});

/* ── Workload class — auto-fills response/resolution, requires manual entry
       for classes with no fixed resolution target (Project/Planned, Vendor). ── */
$(document).on('change', '#caWorkloadClass', function () {
    const $opt = $(this).find(':selected');
    const isManual = $opt.data('manual') === 1 || $opt.data('manual') === '1';

    if (!$(this).val()) {
        $('#caWorkloadManualHint').addClass('d-none');
        return;
    }

    $('#caResponseTime').val($opt.data('response'));

    if (isManual) {
        $('#caResolutionTime').val('').trigger('focus');
        $('#caWorkloadManualHint').removeClass('d-none');
    } else {
        $('#caResolutionTime').val($opt.data('resolution'));
        $('#caWorkloadManualHint').addClass('d-none');
    }
});

$('#classifyAssignForm').on('submit', function (e) {
    if (!$('#caSlaRuleId').val()) { e.preventDefault(); alert('Please select a subcategory.'); return; }
    if (!$('#caTechId').val())    { e.preventDefault(); alert('Please assign a support specialist.'); return; }

    const $wc = $('#caWorkloadClass').find(':selected');
    const wcManual = $wc.data('manual') === 1 || $wc.data('manual') === '1';
    if ($('#caWorkloadClass').val() && wcManual && !$('#caResolutionTime').val()) {
        e.preventDefault();
        alert(`The "${$wc.text().trim()}" workload class has no fixed resolution target — enter the agreed resolution time in minutes.`);
    }
});

/* ── Approve / Reject Reclassification modals ── */
window.openApproveReclassModal = function (ticketId, ticketNumber) {
    $('#arTicketRef').text('#' + ticketNumber);
    $('#approveReclassForm').attr('action', '/supervisor/support/tickets/' + ticketId + '/approve-reclassification');
    $('#approveReclassForm textarea[name="review_notes"]').val('');
    new bootstrap.Modal('#approveReclassModal').show();
};

window.openRejectReclassModal = function (ticketId, ticketNumber) {
    $('#rrTicketRef').text('#' + ticketNumber);
    $('#rejectReclassForm').attr('action', '/supervisor/support/tickets/' + ticketId + '/reject-reclassification');
    $('#rejectReclassForm textarea[name="review_notes"]').val('');
    new bootstrap.Modal('#rejectReclassModal').show();
};

/* ── Reassign modal ── */
window.openReassignModal = function (ticketId, ticketNumber) {
    $('#reassignTicketRef').text('#' + ticketNumber);
    $('#reassignForm').attr('action', '/supervisor/support/tickets/' + ticketId + '/reassign');
    $('#reassignTechId').val('');
    $('#reassignTechList .tech-select-option').removeClass('selected');
    new bootstrap.Modal('#reassignModal').show();
};

$(document).on('click', '#reassignTechList .tech-select-option:not(.disabled)', function () {
    $('#reassignTechList .tech-select-option').removeClass('selected');
    $(this).addClass('selected');
    $('#reassignTechId').val($(this).data('tech-id'));
});

$('#reassignForm').on('submit', function (e) {
    if (!$('#reassignTechId').val()) { e.preventDefault(); alert('Please select a specialist.'); }
});

/* ── Validate Resolution modal ── */
window.openValidateModal = function (ticketId, ticketNumber) {
    $('#validateRef').text('#' + ticketNumber);
    $('#validateForm').attr('action', '/supervisor/support/tickets/' + ticketId + '/validate-resolution');
    new bootstrap.Modal('#validateModal').show();
};

/* ── Request Revision modal ── */
window.openRevisionModal = function (ticketId, ticketNumber) {
    $('#revisionRef').text('#' + ticketNumber);
    $('#revisionForm').attr('action', '/supervisor/support/tickets/' + ticketId + '/request-revision');
    $('#revisionForm textarea[name="revision_notes"]').val('');
    new bootstrap.Modal('#revisionModal').show();
};

/* ── Cannot Resolve modal (Escalated) ── */
window.openCannotResolveModal = function (ticketId, ticketNumber) {
    $('#cannotResolveRef').text('#' + ticketNumber);
    $('#cannotResolveForm').attr('action', '/supervisor/support/tickets/' + ticketId + '/cannot-resolve');
    $('#cannotResolveForm')[0].reset();
    new bootstrap.Modal('#cannotResolveModal').show();
};

/* ── Add Update modal (In Progress) ── */
window.openUpdateModal = function (ticketId, ticketNumber) {
    $('#updateRef').text('#' + ticketNumber);
    $('#updateForm').attr('action', '/supervisor/support/tickets/' + ticketId + '/update');
    $('.status-opt').removeClass('selected');
    $('.status-opt[data-val="Actively working"]').addClass('selected');
    $('#workStatusVal').val('Actively working');
    new bootstrap.Modal('#updateModal').show();
};

/* ── Resolve modal (In Progress) ── */
window.openResolveModal = function (ticketId, ticketNumber) {
    $('#resolveRef').text('#' + ticketNumber);
    $('#resolveForm').attr('action', '/supervisor/support/tickets/' + ticketId + '/resolve');
    new bootstrap.Modal('#resolveModal').show();
};

/* ── Escalate modal (In Progress -> Awaiting Admin Supervisor) ── */
window.openEscModal = function (ticketId, ticketNumber) {
    $('#escRef').text('#' + ticketNumber);
    $('#escForm').attr('action', '/supervisor/support/tickets/' + ticketId + '/escalate-admin');
    new bootstrap.Modal('#escModal').show();
};

$(document).on('click', '.status-opt', function () {
    $(this).siblings().removeClass('selected');
    $(this).addClass('selected');
    $('#workStatusVal').val($(this).data('val'));
});

/* ── DOM-ready ── */
$(function () {
    /* Search debounce */
    let searchTimer;
    $('#searchInput').on('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => $('#searchForm').submit(), 500);
    });

    /* Stop polling when chat modal closes */
    $('#chatModal').on('hidden.bs.modal', function () {
        clearInterval(chatPollInterval);
        currentChatTicketId = null;
    });
});

/* ── Smart silent background refresh ── */
let silentRefreshTimer = null;
let isModalOpen        = false;

function silentRefresh() {
    if (isModalOpen || document.hidden) return;
    const active = document.activeElement;
    if (active && active.matches('input, textarea, select')) return;

    fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' } })
    .then(r => r.text())
    .then(html => {
        const parser = new DOMParser();
        const doc    = parser.parseFromString(html, 'text/html');

        const newList = doc.getElementById('ticketList');
        const curList = document.getElementById('ticketList');
        if (newList && curList) curList.innerHTML = newList.innerHTML;

        doc.querySelectorAll('.badge-count').forEach((newEl, i) => {
            const curEl = document.querySelectorAll('.badge-count')[i];
            if (curEl && curEl.textContent.trim() !== newEl.textContent.trim()) {
                curEl.textContent = newEl.textContent;
                curEl.classList.add('badge-pulse');
                setTimeout(() => curEl.classList.remove('badge-pulse'), 600);
            }
        });

        doc.querySelectorAll('.stat-pill .num').forEach((newEl, i) => {
            const curEl = document.querySelectorAll('.stat-pill .num')[i];
            if (curEl && curEl.textContent.trim() !== newEl.textContent.trim()) curEl.textContent = newEl.textContent;
        });

        doc.querySelectorAll('.tab-pill').forEach((newEl, i) => {
            const curEl = document.querySelectorAll('.tab-pill')[i];
            if (curEl && curEl.textContent.trim() !== newEl.textContent.trim()) curEl.textContent = newEl.textContent;
        });
    })
    .catch(() => {});
}

silentRefreshTimer = setInterval(silentRefresh, 30000);
document.addEventListener('show.bs.modal',   () => { isModalOpen = true; });
document.addEventListener('hidden.bs.modal', () => { isModalOpen = false; });
document.addEventListener('visibilitychange', function () {
    if (document.hidden) {
        clearInterval(silentRefreshTimer);
    } else {
        silentRefresh();
        silentRefreshTimer = setInterval(silentRefresh, 30000);
    }
});
</script>
@endsection