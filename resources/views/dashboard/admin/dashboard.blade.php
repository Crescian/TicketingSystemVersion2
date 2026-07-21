@extends('layouts.app')

@section('title', 'Supervisor Dashboard — LGICT')

@section('nav-role-badge')
    <span class="role-badge"><i class="bi bi-headset me-1"></i>Supervisor</span>
    <a href="{{ route('portal.users.index') }}" style="text-decoration:none">
      <span class="role-badge-admin">
          <i class="bi bi-shield-fill me-1"></i>Settings
      </span>
    </a>
@endsection
@section('avatar-initials',
    strtoupper(substr(Auth::user()->name, 0, 1)) .
    strtoupper(substr(Auth::user()->name, strpos(Auth::user()->name, ' ') + 1, 1))
)
@section('nav-username', Auth::user()->name)

@section('hero-title')
    <h1>Supervisor <em>DASHBOARD</em></h1>
@endsection
@section('hero-subtitle', 'Acknowledge, assign, and track all incoming support tickets.')

@section('hero-stats')
    <div class="d-flex gap-2 flex-wrap">
        <div class="stat-pill danger">
            <span class="num">{{ $counts['awaiting_admin_supervisor'] }}</span>
            <span class="lbl">Awaiting Admin Supervisor</span>
        </div>
        <div class="stat-pill warn">
            <span class="num">{{ $counts['awaiting_admin_classification'] }}</span>
            <span class="lbl">Awaiting Classification</span>
        </div>
        <div class="stat-pill warn">
            <span class="num">{{ $counts['awaiting_administrator_ack'] }}</span>
            <span class="lbl">Awaiting Admin Ack.</span>
        </div>
        <div class="stat-pill">
            <span class="num">{{ $counts['awaiting_administrator_sla_start'] }}</span>
            <span class="lbl">Awaiting SLA Start</span>
        </div>
        <div class="stat-pill">
            <span class="num">{{ $counts['admin_in_progress'] }}</span>
            <span class="lbl">Admin In Progress</span>
        </div>
        <div class="stat-pill">
            <span class="num">{{ $counts['pending_admin_supervisor_approval'] }}</span>
            <span class="lbl">Pending Admin Supervisor Approval</span>
        </div>
        <div class="stat-pill">
            <span class="num">{{ $counts['closed'] }}</span>
            <span class="lbl">Closed</span>
        </div>
    </div>
@endsection

@section('hero-cta')
    <button class="btn-new" data-bs-toggle="modal" data-bs-target="#ticketModal">
        <i class="bi bi-plus-lg me-1"></i> New Ticket
    </button>
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
    .btn-resolve  { background:#e8f5ee; color:#1a5a3a; font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; padding:6px 14px; border-radius:20px; border:1.5px solid #a8ddc0; cursor:pointer; transition:all .2s; }
    .btn-resolve:hover  { background:#c8ead8; }

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
@endsection

{{-- ══ SIDEBAR ══ --}}
@section('sidebar')

    {{-- Queue nav --}}
    <div class="sidebar-card mb-3">
        <div class="sidebar-head">Queue</div>
        <ul class="list-group sidebar-menu rounded-0">
            <li class="list-group-item {{ $status === 'awaiting-admin-supervisor' ? 'active' : '' }}">
                <a href="{{ route('supervisor.dashboard', ['status' => 'awaiting-admin-supervisor']) }}"
                class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-inbox me-2"></i>Awaiting Admin Supervisor</span>
                    <span class="badge-count">{{ $counts['awaiting_admin_supervisor'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'awaiting-admin-classification' ? 'active' : '' }}">
                <a href="{{ route('supervisor.dashboard', ['status' => 'awaiting-admin-classification']) }}"
                class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-tags me-2"></i>Awaiting Classification</span>
                    <span class="badge-count">{{ $counts['awaiting_admin_classification'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'awaiting-administrator-ack' ? 'active' : '' }}">
                <a href="{{ route('supervisor.dashboard', ['status' => 'awaiting-administrator-ack']) }}"
                class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-person-check me-2"></i>Awaiting Administrator Ack.</span>
                    <span class="badge-count">{{ $counts['awaiting_administrator_ack'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'awaiting-administrator-sla-start' ? 'active' : '' }}">
                <a href="{{ route('supervisor.dashboard', ['status' => 'awaiting-administrator-sla-start']) }}"
                class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-stopwatch me-2"></i>Awaiting SLA Start</span>
                    <span class="badge-count">{{ $counts['awaiting_administrator_sla_start'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'admin-in-progress' ? 'active' : '' }}">
                <a href="{{ route('supervisor.dashboard', ['status' => 'admin-in-progress']) }}"
                class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-arrow-repeat me-2"></i>Admin In Progress</span>
                    <span class="badge-count">{{ $counts['admin_in_progress'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'pending-admin-supervisor-approval' ? 'active' : '' }}">
                <a href="{{ route('supervisor.dashboard', ['status' => 'pending-admin-supervisor-approval']) }}"
                class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-arrow-repeat me-2"></i>Pending Admin Supervisor Approval</span>
                    <span class="badge-count">{{ $counts['pending_admin_supervisor_approval'] }}</span>
                </a>
            </li>
            
            <li class="list-group-item {{ $status === 'closed' ? 'active' : '' }}">
                <a href="{{ route('supervisor.dashboard', ['status' => 'closed']) }}"
                class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-check-circle me-2"></i>Closed</span>
                    <span class="badge-count">{{ $counts['closed'] }}</span>
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
                        <div class="tech-load">{{ $tech->active_tickets }} active ticket{{ $tech->active_tickets !== 1 ? 's' : '' }}</div>
                    </div>
                    <div class="avail-dot {{ $tech->availability }}"
                         title="{{ ucfirst($tech->availability) }}"></div>
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
            <span><span class="avail-dot full d-inline-block me-1"></span>Full</span>
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

    {{-- Controls --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <span class="font-brand fw-900" style="font-size:22px">
            @php
                $labels = [
                    'awaiting-admin-supervisor'         => 'Awaiting Admin Supervisor',
                    'awaiting-admin-classification'     => 'Awaiting Admin Classification',
                    'awaiting-administrator-ack'        => 'Awaiting Administrator Acknowledgement',
                    'awaiting-administrator-sla-start'  => 'Awaiting Administrator SLA Start',
                    'admin-in-progress'                 => 'Admin In Progress',
                    'pending-admin-supervisor-approval' => 'Pending Admin Supervisor Approval',
                    'closed'                            => 'Closed',
                ];
            @endphp
            {{ $labels[$status] ?? 'All Tickets' }}
        </span>
        <form method="GET" action="{{ route('supervisor.dashboard') }}"
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
                'awaiting-admin-supervisor'        => ['label' => 'Awaiting Admin Supervisor',        'count' => $counts['awaiting_admin_supervisor']],
                'awaiting-admin-classification'    => ['label' => 'Awaiting Admin Classification',    'count' => $counts['awaiting_admin_classification']],
                'awaiting-administrator-ack'       => ['label' => 'Awaiting Administrator Ack.',      'count' => $counts['awaiting_administrator_ack']],
                'awaiting-administrator-sla-start' => ['label' => 'Awaiting SLA Start',               'count' => $counts['awaiting_administrator_sla_start']],
                'admin-in-progress'                => ['label' => 'Admin In Progress',                'count' => $counts['admin_in_progress']],
                'pending-admin-supervisor-approval'=> ['label' => 'Pending Admin Supervisor Approval','count' => $counts['pending_admin_supervisor_approval']],
                'closed'                           => ['label' => 'Closed',                           'count' => $counts['closed']],
            ];
        @endphp
        @foreach($tabs as $key => $tab)
            <a href="{{ route('supervisor.dashboard', array_merge(request()->except('status'), ['status' => $key])) }}"
               class="tab-pill {{ $status === $key ? 'active' : '' }}">
                {{ $tab['label'] }} ({{ $tab['count'] }})
            </a>
        @endforeach
    </div>

    {{-- Ticket list --}}
    <div class="d-flex flex-column gap-3" id="ticketList">
        @forelse($tickets as $ticket)
            @php
                $isUnassigned = $ticket->status === 'Awaiting Admin Supervisor' && !$ticket->assigned_to;
                $cardClass = match(true) {
                    $isUnassigned                                                   => 'unassigned',
                    $ticket->status === 'Awaiting Admin Supervisor'                 => 'awaiting-admin-supervisor',
                    $ticket->status === 'Awaiting Admin Classification'             => 'awaiting-admin-classification',
                    $ticket->status === 'Awaiting Administrator Acknowledgement'    => 'awaiting-administrator-ack',
                    $ticket->status === 'Awaiting Administrator SLA Start'          => 'awaiting-administrator-sla-start',
                    $ticket->status === 'Admin In Progress'                         => 'admin-in-progress',
                    $ticket->status === 'Pending Admin Supervisor Approval'         => 'pending-admin-supervisor-Approval',
                    $ticket->status === 'Closed'                                    => 'closed',
                    $ticket->status === 'Cancelled'                                 => 'cancelled',
                    default                                                         => 'awaiting-admin-supervisor'
                };
                $badgeClass = match(true) {
                    $isUnassigned                                                   => 'badge-unassigned',
                    $ticket->status === 'Awaiting Admin Supervisor'                 => 'badge-awaiting-admin-supervisor',
                    $ticket->status === 'Awaiting Admin Classification'             => 'badge-awaiting-admin-classification',
                    $ticket->status === 'Awaiting Administrator Acknowledgement'    => 'badge-awaiting-administrator-ack',
                    $ticket->status === 'Awaiting Administrator SLA Start'          => 'badge-awaiting-administrator-sla-start',
                    $ticket->status === 'Admin In Progress'                         => 'badge-admin-in-progress',
                    $ticket->status === 'Pending Admin Supervisor Approval'         => 'badge-admin-in-progress',
                    $ticket->status === 'Closed'                                    => 'badge-closed',
                    $ticket->status === 'Cancelled'                                 => 'badge-cancelled',
                    default                                                         => 'badge-awaiting-admin-supervisor'
                };
                $badgeLabel = match(true) {
                    $isUnassigned                                                   => '<i class="bi bi-inbox me-1"></i>Unassigned',
                    $ticket->status === 'Awaiting Admin Supervisor'                 => '<i class="bi bi-hourglass-split me-1"></i>Awaiting Admin Supervisor',
                    $ticket->status === 'Awaiting Admin Classification'             => '<i class="bi bi-tags me-1"></i>Awaiting Classification',
                    $ticket->status === 'Awaiting Administrator Acknowledgement'    => '<i class="bi bi-person-check me-1"></i>Awaiting Ack.',
                    $ticket->status === 'Awaiting Administrator SLA Start'          => '<i class="bi bi-stopwatch me-1"></i>Awaiting SLA Start',
                    $ticket->status === 'Admin In Progress'                        => '<i class="bi bi-gear-fill me-1"></i>Admin In Progress',
                    $ticket->status === 'Pending Admin Supervisor Approval'         => '<i class="bi bi-gear-fill me-1"></i>Admin In Progress',
                    $ticket->status === 'Closed'                                    => '<i class="bi bi-check-circle-fill me-1"></i>Closed',
                    $ticket->status === 'Cancelled'                                 => '<i class="bi bi-x-circle-fill me-1"></i>Cancelled',
                    default                                                         => '● Awaiting Admin Supervisor'
                };
                $priorityClass = match($ticket->ticket_type) {
                    'High'   => 'pri-high',
                    'Medium' => 'pri-medium',
                    'Low'    => 'pri-low',
                    default  => ''
                };
                $techInitials = $ticket->assignedTo
                    ? strtoupper(substr($ticket->assignedTo->name, 0, 1)) .
                    strtoupper(substr($ticket->assignedTo->name, strpos($ticket->assignedTo->name, ' ') + 1, 1))
                    : '—';
            @endphp

            <div class="ticket-card {{ $cardClass }} p-3"
                data-status="{{ $cardClass }}"
                data-priority="{{ strtolower($ticket->ticket_type) }}">

                {{-- Header --}}
                <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="ticket-id">#{{ $ticket->ticket_number }}</span>
                        <span class="badge-type">{{ $ticket->request_category }}</span>
                        <span class="meta-item">
                            <span class="priority-dot {{ $priorityClass }}"></span>
                            {{ $ticket->ticket_type }}
                        </span>
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
                    @if($ticket->asset)
                        <span class="meta-item">
                            <i class="bi bi-laptop"></i> {{ $ticket->asset }}
                        </span>
                    @endif
                    <span class="meta-item">
                        <i class="bi bi-calendar3"></i>
                        {{ $ticket->created_at->diffForHumans() }}
                    </span>

                    {{-- ── SLA Status indicator (Admin In Progress only) ── --}}
                    @if($ticket->status === 'Admin In Progress' && $ticket->sla_due_at)
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

                {{-- Action buttons --}}
                <div class="d-flex gap-2 flex-wrap">

                    {{-- Awaiting Admin Supervisor (unassigned): Acknowledge into the queue --}}
                    @if($isUnassigned)
                        <form method="POST" action="{{ route('supervisor.tickets.acknowledge', $ticket) }}">
                            @csrf
                            <button type="submit" class="btn-acknowledge">
                                <i class="bi bi-eye me-1"></i>Acknowledge
                            </button>
                        </form>
                    @endif

                    {{-- Awaiting Admin Classification: classify & assign to an IT Admin --}}
                    @if($ticket->status === 'Awaiting Admin Classification')
                        <button class="btn-classify"
                                onclick="openClassifyAssignModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-tags me-1"></i>Classify & Assign
                        </button>
                    @endif

                    {{-- Awaiting Administrator Acknowledgement: view-only, only the assigned admin can acknowledge --}}
                    @if($ticket->status === 'Awaiting Administrator Acknowledgement')
                        <span class="resolve-info px-3 py-2">
                            <i class="bi bi-hourglass-split me-1"></i>
                            Waiting for {{ $ticket->assignedTo->name ?? 'the assigned admin' }} to acknowledge
                        </span>
                    @endif

                    {{-- Awaiting Administrator SLA Start: begin work, starts the SLA clock --}}
                    @if($ticket->status === 'Awaiting Administrator SLA Start')
                        <form method="POST" action="/supervisor/tickets/{{ $ticket->id }}/start">
                            @csrf
                            <button type="submit" class="btn-resolve">
                                <i class="bi bi-play-circle me-1"></i>Start Work
                            </button>
                        </form>
                    @endif

                    {{-- Admin In Progress: Reassign + Resolve --}}
                    @if($ticket->status === 'Admin In Progress')
                        <button class="btn-reassign"
                                onclick="openReassignModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-arrow-left-right me-1"></i>Reassign
                        </button>
                        <button class="btn-resolve"
                                onclick="openValidateModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-check-circle-fill me-1"></i>Resolve & Close
                        </button>
                        <button class="btn-reassign"
                                onclick="openEscManagerModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-arrow-up-circle me-1"></i>Escalate to Manager
                        </button>
                    @endif
                {{-- Pending Supervisor Approval: Validate Resolution --}}
                    @if($ticket->status === 'Pending Admin Supervisor Approval')
                        @if($ticket->validated_at)
                            <span class="resolve-info px-3 py-2">
                                <i class="bi bi-check-circle-fill me-1"></i>Validated — awaiting Helpdesk closure
                            </span>
                        @else
                            <button class="btn-resolve"
                                    onclick="openValidateModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                                <i class="bi bi-check-circle me-1"></i>Validate Resolution
                            </button>
                        @endif
                    @endif

                    {{-- Chat — available on every non-terminal status --}}
                    @if(!in_array($ticket->status, ['Closed', 'Cancelled']))
                        <button class="btn-chat"
                                onclick="openChatModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-chat-dots me-1"></i>Message
                            @php $unread = $ticket->unreadMessages()->count(); @endphp
                            @if($unread > 0)
                                <span class="chat-count-badge" id="badge-{{ $ticket->id }}">{{ $unread }}</span>
                            @endif
                        </button>
                    @endif

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

                        <hr style="border-color:var(--bd)">

                        <label class="form-label mb-2">Assign Support Specialist</label>
                        <div class="d-flex flex-column gap-2" id="caTechList">
                            @foreach($technicians as $tech)
                                @php
                                    $initials = strtoupper(substr($tech->name, 0, 1)) . strtoupper(substr($tech->name, strpos($tech->name, ' ') + 1, 1));
                                    $isFull   = $tech->availability === 'full';
                                    $loadPct  = min(100, $tech->active_tickets * 17);
                                    $barClass = $tech->availability === 'busy' ? 'busy' : ($tech->availability === 'full' ? 'full' : '');
                                @endphp
                                <div class="tech-select-option {{ $isFull ? 'disabled' : '' }}"
                                     data-tech-id="{{ $tech->id }}">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="tech-av-lg">{{ $initials }}</div>
                                        <div>
                                            <div class="ts-name">{{ $tech->name }}</div>
                                            <div class="ts-load">{{ $tech->active_tickets }} active</div>
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
                                    $isFull   = $tech->availability === 'full';
                                    $loadPct  = min(100, $tech->active_tickets * 17);
                                    $barClass = $tech->availability === 'busy' ? 'busy' : ($tech->availability === 'full' ? 'full' : '');
                                @endphp
                                <div class="tech-select-option {{ $isFull ? 'disabled' : '' }}"
                                     data-tech-id="{{ $tech->id }}">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="tech-av-lg">{{ $initials }}</div>
                                        <div>
                                            <div class="ts-name">{{ $tech->name }}</div>
                                            <div class="ts-load">{{ $tech->active_tickets }} active</div>
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
    {{-- Escalate to Manager Modal --}}
    <div class="modal fade" id="escManagerModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header-gd d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Escalate to <em>Manager</em></h5>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <form method="POST" id="escManagerForm">
                    @csrf
                    <div class="modal-body px-4 py-4">
                        <div class="resolve-info p-3 mb-3">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            Ticket <strong id="escManagerRef"></strong> — escalating hands this to the
                            Manager and unassigns it from you.
                        </div>
                        <label class="form-label">Reason (optional)</label>
                        <textarea class="form-control" name="reason" rows="3"
                                  placeholder="Why does this need Manager-level attention?"></textarea>
                    </div>
                    <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
                        <button type="button" class="btn-cancel-modal" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-confirm">
                            <i class="bi bi-arrow-up-circle me-1"></i>Confirm Escalation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    {{-- Submit Ticket Modal --}}
    <div class="modal fade" id="ticketModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header-gd d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">New <em>Support</em> Ticket</h5>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <form method="POST" action="{{ route('helpdesk.tickets.store') }}" id="ticketForm">
                    @csrf
                    <input type="hidden" name="ticket_type"      id="hTicketType"   value="Medium">
                    <input type="hidden" name="request_category" id="hCategory"     value="">
                    <input type="hidden" name="asset"            id="hAsset"        value="">
                    <input type="hidden" name="location"         id="hLocation"     value="">

                    <div class="modal-body px-4 pt-4 pb-2">

                        {{-- Step indicator --}}
                        <div class="step-ind mb-4">
                            <div class="step-item active" id="si1">
                                <div class="step-num">1</div>
                                <span class="step-lbl">Issue Type</span>
                            </div>

                            <div class="step-line" id="sl1"></div>

                            <div class="step-item" id="si2">
                                <div class="step-num">2</div>
                                <span class="step-lbl">Review</span>
                            </div>
                        </div>

                        {{-- Step 1 (Details) — fs2 --}}
                        <div class="form-step" id="fs2">

                            {{-- Ticket Number (read-only, auto-generated) --}}
                            <div class="mb-3 p-3 rounded d-flex align-items-center gap-3"
                                style="background:var(--ygl);border:1.5px solid var(--bd)">
                                <div>
                                    <div style="font-size:10px;font-weight:800;color:var(--tm);text-transform:uppercase;letter-spacing:.4px;margin-bottom:2px">
                                        Ticket Number
                                    </div>
                                    <div class="font-brand fw-900" style="font-size:18px;color:var(--gd);letter-spacing:1px">
                                        Auto-generated on submit
                                    </div>
                                </div>
                                <i class="bi bi-ticket-perforated ms-auto" style="font-size:28px;opacity:.2;color:var(--gd)"></i>
                            </div>

                            {{-- Date & Time Received / Acknowledged --}}
                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label class="form-label">Date Received <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="mDateReceived" name="date_received">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Time Received <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="mTimeReceived" name="time_received">
                                </div>
                            </div>

                            {{-- Requestor --}}
                            <div class="mb-3">
                                <label class="form-label">Requestor <span class="text-danger">*</span></label>
                                <select class="form-select" id="mRequestor" name="users_id">
                                    <option value="">— Select employee —</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}"
                                                data-position="{{ $user->position ?? '' }}"
                                                data-bu="{{ $user->business_units_name ?? '' }}"
                                                data-company="{{ $user->company_name ?? '' }}"
                                                data-department="{{ $user->department_name ?? '' }}">
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Auto-filled fields --}}
                            <div class="row g-3 mb-3">
                                <div class="col-12">
                                    <label class="form-label">Position</label>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="text" class="form-control" id="mPosition" name="position"
                                            placeholder="Auto-filled from requestor" readonly
                                            style="background:var(--ygl);color:var(--gd);font-weight:700">
                                        <i class="bi bi-magic" style="color:var(--tm);opacity:.5;flex-shrink:0"></i>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Business Unit</label>
                                    <input type="text" class="form-control" id="mBU" name="business_unit"
                                        placeholder="Auto-filled from requestor" readonly
                                        style="background:var(--ygl);color:var(--gd);font-weight:700">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Company</label>
                                    <input type="text" class="form-control" id="mCompany" name="company"
                                        placeholder="Auto-filled" readonly
                                        style="background:var(--ygl);color:var(--gd);font-weight:700">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Department</label>
                                    <input type="text" class="form-control" id="mDepartment" name="department"
                                        placeholder="Auto-filled" readonly
                                        style="background:var(--ygl);color:var(--gd);font-weight:700">
                                </div>
                            </div>

                            {{-- Method --}}
                            <div class="mb-1">
                                <label class="form-label">Method <span class="text-danger">*</span></label>
                                <div class="d-flex gap-2 flex-wrap" id="methodOptions">
                                    @foreach([
                                        ['value' => 'Verbal', 'icon' => 'bi-person-fill',   'color' => '#4a7c4a', 'bg' => '#d4f0d4'],
                                        ['value' => 'Email',  'icon' => 'bi-envelope-fill', 'color' => '#2a4ab0', 'bg' => '#e8eeff'],
                                        ['value' => 'Text',   'icon' => 'bi-chat-fill',     'color' => '#7a5a00', 'bg' => '#fff4cc'],
                                        ['value' => 'Viber',  'icon' => 'bi-phone-fill',    'color' => '#5a1a7a', 'bg' => '#f0e8ff'],
                                    ] as $method)
                                        <div class="method-opt" data-method="{{ $method['value'] }}"
                                            style="--mc:{{ $method['color'] }};--mb:{{ $method['bg'] }}">
                                            <i class="bi {{ $method['icon'] }} method-icon"></i>
                                            <span class="method-lbl">{{ $method['value'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                                <input type="hidden" name="method" id="hMethod" value="">
                            </div>
                            {{-- Divider --}}
                            <hr style="border-color:var(--bd);margin:20px 0">

                            {{-- Subject & Description --}}
                            <div class="mb-3">
                                <label class="form-label">Subject <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="mSubject"
                                    name="subject"
                                    placeholder="Brief description of the issue…">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Describe the issue <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="mDesc"
                                        name="concern" rows="3"
                                        placeholder="What happened, when it started…"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Additional details <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="mDetails"
                                        name="request_details" rows="2"
                                        placeholder="Error messages, steps to reproduce…"></textarea>
                            </div>
                            <div class="row g-3 mb-1">
                                    <label class="form-label">Location</label>
                                    <select class="form-select" id="mLocation">
                                        <option value="">— Select location —</option>
                                        <optgroup label="HQ">
                                            <option value="3rd Floor - HQ">3rd Floor - HQ</option>
                                            <option value="5th Floor - HQ">5th Floor - HQ</option>
                                            <option value="6th Floor - HQ">6th Floor - HQ</option>
                                            <option value="7th Floor - HQ">7th Floor - HQ</option>
                                        </optgroup>
                                        <optgroup label="Sites">
                                            <option value="Zambales Site">Zambales Site</option>
                                            <option value="Porac Site">Porac Site</option>
                                            <option value="Bauan Site">Bauan Site</option>
                                        </optgroup>
                                        <optgroup label="Vessels">
                                            <option value="Petro Elise">Petro Elise</option>
                                            <option value="Petro Cara">Petro Cara</option>
                                        </optgroup>
                                    </select>
                            </div>
                        </div>

                        {{-- Step 3: Review --}}
                        <div class="form-step d-none" id="fs3">
                            <div class="review-box p-3 mb-3">

                                <div class="font-brand fw-900 mb-3"
                                    style="font-size:14px;color:var(--gd);text-transform:uppercase;letter-spacing:.5px">
                                    Ticket Summary
                                </div>

                                <div class="mb-2"><b>Subject:</b> <span id="rv-subject">—</span></div>
                                <div class="mb-2"><b>Describe Issue:</b> <span id="rv-desc">—</span></div>
                                <div class="mb-2"><b>Additional Details:</b> <span id="rv-details">—</span></div>

                                <hr>

                                <div class="mb-2"><b>Requestor:</b> <span id="rv-requestor">—</span></div>
                                <div class="mb-2"><b>Position:</b> <span id="rv-position">—</span></div>
                                <div class="mb-2"><b>Business Unit:</b> <span id="rv-bu">—</span></div>
                                <div class="mb-2"><b>Company:</b> <span id="rv-company">—</span></div>
                                <div class="mb-2"><b>Department:</b> <span id="rv-department">—</span></div>

                                <hr>

                                <div class="mb-2"><b>Date Received:</b> <span id="rv-date">—</span></div>
                                <div class="mb-2"><b>Time Received:</b> <span id="rv-time">—</span></div>

                                <div class="mb-2"><b>Date Acknowledged:</b> <span id="rv-ack-date">—</span></div>
                                <div class="mb-2"><b>Time Acknowledged:</b> <span id="rv-ack-time">—</span></div>

                                <hr>

                                <div class="mb-2"><b>Method:</b> <span id="rv-method">—</span></div>
                                <div class="mb-2"><b>Location:</b> <span id="rv-location">—</span></div>

                            </div>
                            <div class="review-detail p-3">
                                <div class="review-lbl mb-1">Subject</div>
                                <div class="font-brand fw-800 mb-3" style="font-size:15px" id="rv-subject-preview">—</div>
                                <div class="review-lbl mb-1">Concern</div>
                                <div style="font-size:13px;color:var(--tm);margin-bottom:12px" id="rv-desc-preview">—</div>
                                <div class="review-lbl mb-1">What happens next</div>
                                <div style="font-size:13px;color:var(--tm)">
                                    Your ticket will be assigned to an available IT Support Specialist.
                                    Average first response: <strong style="color:var(--gd)">under 2 hours</strong>.
                                </div>
                            </div>
                        </div>

                        {{-- Success screen --}}
                        <div class="form-step d-none text-center py-3" id="fsSuccess">
                            <div class="success-icon d-flex align-items-center justify-content-center mx-auto mb-3">
                                ✅
                            </div>
                            <h5 class="font-brand fw-900 mb-1" style="font-size:22px">
                                Ticket submitted!
                            </h5>
                            <p class="mb-2" style="color:var(--tm)">
                                Your request has been received.<br>
                                Helpdesk will assign a technician shortly.
                            </p>
                            <div class="ticket-ref my-3" id="newTicketRef">—</div>
                            <p style="color:var(--tm);font-size:13px">
                                Track progress from your dashboard.<br>
                                You'll be notified when the status changes.
                            </p>
                        </div>
                    </div>

                    <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between"
                         id="mFooter">
                        <button type="button" class="btn-back-modal" id="btnBack">← Back</button>
                        <button type="button" class="btn-continue" id="btnNext">Continue →</button>
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

                {{-- Messages area --}}
                <div id="modalChatMessages"
                    style="height:360px;overflow-y:auto;padding:16px;background:#f8f8f4;display:flex;flex-direction:column;gap:12px;scroll-behavior:smooth">
                    <div class="text-center py-4" style="color:var(--tm);font-size:13px">
                        <div class="spinner-border spinner-border-sm me-2"></div>
                        Loading messages…
                    </div>
                </div>

                {{-- Input --}}
                <div style="border-top:1.5px solid var(--bd);padding:12px 16px;background:#fff">
                    <div style="font-size:10px;font-weight:800;background:#d4f0d4;color:var(--gm);border-radius:4px;padding:2px 8px;display:inline-block;margin-bottom:8px;text-transform:uppercase;letter-spacing:.3px">
                        Helpdesk
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
@endsection

@section('scripts')
<script>
    /* ── Auto-fill requestor details ── */
    $(document).on('change', '#mRequestor', function () {
        const opt = $(this).find('option:selected');
        $('#mPosition').val(opt.data('position')   || '');
        $('#mBU').val(opt.data('bu')               || '');
        $('#mCompany').val(opt.data('company')     || '');
        $('#mDepartment').val(opt.data('department') || '');
    });

    /* ── Method selection ── */
    $(document).on('click', '.method-opt', function () {
        $('.method-opt').removeClass('selected');
        $(this).addClass('selected');
        $('#hMethod').val($(this).data('method'));
    });
    /* ══ GLOBAL FUNCTIONS — must be outside $(function(){}) ══ */

    /* ── Chat modal ── */
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
            const avColors     = { 'IT Admin':'#fde8e8','IT Support Specialist':'#fff4cc','Helpdesk':'#d4f0d4','Executive':'#e8e0ff' };
            const avTextColors = { 'IT Admin':'#8b1a1a','IT Support Specialist':'#7a5a00','Helpdesk':'#2d5a2d','Executive':'#4a1a8a' };
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

    /* ══ DOM-READY ══ */
    $(function () {
        $(document).on('click', '.method-opt', function () {
        $('.method-opt').removeClass('selected');
        $(this).addClass('selected');

        $('#hMethod').val($(this).data('method'));
    });
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
                const priColor = sub.priority === 'High' ? '#e24b4a' : (sub.priority === 'Medium' ? '#f5c842' : '#4a7c4a');
                $subList.append(`<div class="cat-sub-opt" data-rule-id="${sub.rule_id}">
                    <div class="sub-check"></div>
                    <span style="flex:1">${sub.name}</span>
                    <span style="font-size:10px;font-weight:800;color:${priColor}">${sub.priority} · ${sub.resolution}m SLA</span>
                </div>`);
            });
        }
        $('#caSubWrap').removeClass('d-none');
    });

    /* ── Dynamic SLA categories ── */
    const slaCategories = @json($slaCategoriesJson);
    const subCategoryMap = {};
    slaCategories.forEach(cat => { subCategoryMap[cat.name] = cat.subs; });


    /* ── Classify & Assign modal ── */
    window.openClassifyAssignModal = function (ticketId, ticketNumber) {
        $('#caTicketRef').text('#' + ticketNumber);
        $('#classifyAssignForm').attr('action', `{{ route('supervisor.tickets.admin-classify-assign', ['ticket' => '__ID__']) }}`.replace('__ID__', ticketId))
        $('#caSlaRuleId, #caTechId').val('');
        $('#caSubWrap').addClass('d-none');
        $('#caSubList').empty();
        $('#caCategoryList .cat-main-opt, #caTechList .tech-select-option').removeClass('selected');

        const $catList = $('#caCategoryList').empty();
        slaCategories.forEach(cat => {
            $catList.append(`<div class="cat-main-opt" data-cat-id="${cat.id}">${cat.name}</div>`);
        });

        new bootstrap.Modal('#classifyAssignModal').show();
    };
    $(document).on('click', '.cat-main-opt', function () {
        $('.cat-main-opt').removeClass('selected');
        $(this).addClass('selected');
    });
    /* ── Sub category selection ── */
    
    /* ── Priority selection ── */
    $(document).on('click', '.pri-opt', function () {
        $(this).siblings().removeClass('selected');
        $(this).addClass('selected');
        $('#hTicketType').val($(this).data('pri'));
    });

    /* ── Search debounce ── */
    let searchTimer;
    $('#searchInput').on('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => $('#searchForm').submit(), 500);
    });

    /* ── Assign / Reassign modal ── */
    window.openAssignModal = function (ticketId, ticketNumber, isReassign) {
        $('#assignTicketRef').text('#' + ticketNumber);
        $('#assignModalTitle').html(isReassign ? 'Reassign <em>Technician</em>' : 'Assign <em>Technician</em>');
        $('#assignForm').attr('action', isReassign
            ? '/helpdesk/tickets/' + ticketId + '/reassign'
            : '/helpdesk/tickets/' + ticketId + '/assign');
        $('#selectedTechId').val('');
        $('.tech-select-option').removeClass('selected');
        $('.tech-select-option:not(.disabled)').first().trigger('click');
        new bootstrap.Modal('#assignModal').show();
    };

    /* ── Escalate modal ── */
    window.openEscalateModal = function (ticketId, ticketNumber) {
        $('#escalateRef').text('#' + ticketNumber);
        $('#escalateForm').attr('action', '/helpdesk/tickets/' + ticketId + '/escalate');
        new bootstrap.Modal('#escalateModal').show();
    };

    /* ── Resolve modal ── */
    window.openResolveModal = function (ticketId, ticketNumber) {
        $('#resolveRef').text('#' + ticketNumber);
        $('#resolveForm').attr('action', '/helpdesk/tickets/' + ticketId + '/resolve');
        new bootstrap.Modal('#resolveModal').show();
    };

    /* ── Validate assign ── */
    $('#assignForm').on('submit', function (e) {
        if (!$('#selectedTechId').val()) {
            e.preventDefault();
            alert('Please select a technician.');
        }
    });

    /* ── Stop polling when chat modal closes ── */
    $('#chatModal').on('hidden.bs.modal', function () {
        clearInterval(chatPollInterval);
        currentChatTicketId = null;
    });

    /* ════════════════════════════════════════
       STEP WIZARD  —  Order: Details → Issue type → Review
       Step 1 = fs2 (Details)
       Step 2 = fs1 (Issue type / category)
       Step 3 = fs3 (Review)
       Step 4 = fsSuccess
    ════════════════════════════════════════ */

    // Map wizard step numbers to the actual panel IDs
    // Step 1 → Details (fs2), Step 2 → Issue type (fs1), Step 3 → Review (fs3)
   const stepPanels = ['fs2', 'fs3', 'fsSuccess']; // 1: Details, 2: Review, 3: Success

let step = 1;

function showStep(n) {
    step = n;

    stepPanels.forEach((id, i) => {
        $('#' + id).toggleClass('d-none', i !== n - 1);
    });

    for (let i = 1; i <= 2; i++) {
        $('#si' + i).toggleClass('active', i === n).toggleClass('done', i < n);
        if (i < 2) $('#sl' + i).toggleClass('done', i < n);
    }

    $('#btnBack').css('visibility', n === 1 ? 'hidden' : 'visible');

    if (n === 2) {
        $('#rv-subject').text($('#mSubject').val() || '—');
        $('#rv-desc').text($('#mDesc').val() || '—');
        $('#rv-details').text($('#mDetails').val() || '—');
        $('#rv-requestor').text($('#mRequestor option:selected').text() || '—');
        $('#rv-position').text($('#mPosition').val() || '—');
        $('#rv-bu').text($('#mBU').val() || '—');
        $('#rv-company').text($('#mCompany').val() || '—');
        $('#rv-department').text($('#mDepartment').val() || '—');
        $('#rv-date').text($('#mDateReceived').val() || '—');
        $('#rv-time').text($('#mTimeReceived').val() || '—');
        $('#rv-ack-date').text($('#mDateAck').val() || '—');
        $('#rv-ack-time').text($('#mTimeAck').val() || '—');
        $('#rv-method').text($('#hMethod').val() || '—');
        $('#rv-location').text($('#mLocation').val() || '—');
        $('#rv-subject-preview').text($('#mSubject').val() || '—');
        $('#rv-desc-preview').text($('#mDesc').val() || '—');

        $('#mFooter').show();
        $('#btnNext').removeClass('btn-continue').addClass('btn-submit-ticket').text('Submit ticket');
    } else if (n === 3) {
        $('#mFooter').hide();
    } else {
        $('#mFooter').show();
        $('#btnNext').removeClass('btn-submit-ticket').addClass('btn-continue').text('Continue →');
    }
}

    /* ── Next / Submit ── */
    $('#btnNext').on('click', function () {

        if (step === 1) {
            // Validate Details panel
            if (!$('#mSubject').val().trim()) { alert('Please enter a subject.'); return; }
            if (!$('#mDesc').val().trim())    { alert('Please describe the issue.'); return; }
            if (!$('#mDateReceived').val())   { alert('Please enter the date received.'); return; }
            if (!$('#mTimeReceived').val())   { alert('Please enter the time received.'); return; }
            if (!$('#mRequestor').val())      { alert('Please select a requestor.'); return; }
            if (!$('#hMethod').val())         { alert('Please select a contact method.'); return; }

            $('#hAsset').val($('#mAsset').val());
            $('#hLocation').val($('#mLocation').val());

            showStep(2); // now review

        } else if (step === 2) {

            const mainCat = $('.cat-main-opt.selected').data('cat') || '';
            const subCat  = $('.cat-sub-opt.selected').data('sub')  || '';
            const pri     = $('.pri-opt.selected').data('pri')      || 'Medium';

            $('#hCategory').val(mainCat + ' — ' + subCat);
            $('#hTicketType').val(pri);

            $.ajax({
                url: $('#ticketForm').attr('action'),
                type: 'POST',
                data: $('#ticketForm').serialize(),
                success: function (response) {
                    $('#newTicketRef').text(response.ticket_number);
                    showStep(3);

                    setTimeout(() => {
                        window.location.href = '{{ route("supervisor.dashboard") }}';
                    }, 3000);
                },
                error: function (xhr) {
                    const errors = xhr.responseJSON?.errors;
                    alert(errors ? Object.values(errors).flat().join('\n') : 'Something went wrong. Please try again.');
                }
            });
        }
    });

    /* ── Back ── */
    $('#btnBack').on('click', function () {
        if (step > 1 && step < 4) showStep(step - 1);
    });

    /* ── Reset modal on open ── */
    $('#ticketModal').on('show.bs.modal', function () {
        $('#mFooter').show();
        showStep(1);

        $('#hCategory').val('');
        $('#hTicketType').val('Medium');
        $('#hAsset').val('');
        $('#hLocation').val('');

        $('.cat-main-opt').removeClass('selected');
        $('.cat-sub-opt').removeClass('selected');
        $('#subCategoryWrap').addClass('d-none');
        $('#subCategoryList').empty();

        $('#mSubject, #mDesc, #mDetails').val('');
        $('#mAsset').val('');
        $('#mLocation').val(''); 

        $('.pri-opt').removeClass('selected').filter('.medium').addClass('selected');
        $('#hTicketType').val('Medium');
        // Reset details fields
        $('#mDateReceived').val(new Date().toISOString().split('T')[0]);
        $('#mTimeReceived').val(new Date().toTimeString().slice(0,5));
        $('#mDateAck, #mTimeAck').val('');
        $('#mRequestor').val('').trigger('change');
        $('#mPosition, #mBU, #mCompany, #mDepartment').val('');
        $('.method-opt').removeClass('selected');
        $('#hMethod').val('');
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


    /* ── Reassign modal ── */
    window.openReassignModal = function (ticketId, ticketNumber) {
        $('#reassignTicketRef').text('#' + ticketNumber);
        $('#reassignForm').attr('action', '/supervisor/support/tickets/' + ticketId + '/reassign');
        $('#reassignTechId').val('');
        $('#reassignTechList .tech-select-option').removeClass('selected');
        new bootstrap.Modal('#reassignModal').show();
    };


    window.openValidateModal = function (ticketId, ticketNumber) {
        $('#validateRef').text('#' + ticketNumber);
        $('#validateForm').attr('action', '/supervisor/tickets/' + ticketId + '/validate-resolution');
        new bootstrap.Modal('#validateModal').show();
    };

    window.openEscManagerModal = function (ticketId, ticketNumber) {
        $('#escManagerRef').text('#' + ticketNumber);
        $('#escManagerForm').attr('action', '/supervisor/tickets/' + ticketId + '/escalate-manager');
        new bootstrap.Modal('#escManagerModal').show();
    };





    $(document).on('click', '#caSubList .cat-sub-opt', function () {
        $('#caSubList .cat-sub-opt').removeClass('selected');
        $(this).addClass('selected');
        $('#caSlaRuleId').val($(this).data('rule-id'));
    });

    $(document).on('click', '#caTechList .tech-select-option:not(.disabled)', function () {
        $('#caTechList .tech-select-option').removeClass('selected');
        $(this).addClass('selected');
        $('#caTechId').val($(this).data('tech-id'));
    });

    $('#classifyAssignForm').on('submit', function (e) {
        if (!$('#caSlaRuleId').val()) { e.preventDefault(); alert('Please select a subcategory.'); }
        if (!$('#caTechId').val())    { e.preventDefault(); alert('Please assign a support specialist.'); }
    });



</script>
@endsection