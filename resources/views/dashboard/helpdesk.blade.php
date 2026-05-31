@extends('layouts.app')

@section('title', 'Helpdesk Dashboard — LGICT')

@section('nav-role-badge')
    <span class="role-badge"><i class="bi bi-headset me-1"></i>Helpdesk</span>
@endsection
@section('avatar-initials',
    strtoupper(substr(Auth::user()->name, 0, 1)) .
    strtoupper(substr(Auth::user()->name, strpos(Auth::user()->name, ' ') + 1, 1))
)
@section('nav-username', Auth::user()->name)

@section('hero-title')
    <h1>HELPDESK <em>DASHBOARD</em></h1>
@endsection
@section('hero-subtitle', 'Acknowledge, assign, and track all incoming support tickets.')

@section('hero-stats')
    <div class="d-flex gap-2 flex-wrap">
        <div class="stat-pill danger">
            <span class="num">{{ $counts['unassigned'] }}</span>
            <span class="lbl">Unassigned</span>
        </div>
        <div class="stat-pill warn">
            <span class="num">{{ $counts['in_progress'] }}</span>
            <span class="lbl">In Progress</span>
        </div>
        <div class="stat-pill warn">
            <span class="num">{{ $counts['escalated'] }}</span>
            <span class="lbl">Escalated</span>
        </div>
        <div class="stat-pill">
            <span class="num">{{ $counts['resolved'] }}</span>
            <span class="lbl">Resolved</span>
        </div>
    </div>
@endsection

@section('hero-cta')
    <button class="btn-new" data-bs-toggle="modal" data-bs-target="#ticketModal">
        <i class="bi bi-plus-lg me-1"></i> New Ticket
    </button>
@endsection

@section('styles')

    /* ── Employee: New Ticket button ── */
    .btn-new {
        background: var(--yg); color: var(--gd);
        font-family: 'Nunito', sans-serif; font-weight: 900; font-size: 15px;
        padding: 13px 28px; border-radius: 50px; border: none;
        transition: background .2s, transform .15s; white-space: nowrap;
    }
    .btn-new:hover { background: var(--ygd); transform: translateY(-2px); }

    /* ── Modal: step wizard ── */
    .step-ind { display: flex; align-items: center; }
    .step-item { display: flex; align-items: center; gap: 8px; flex: 1; }
    .step-num { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-family: 'Nunito', sans-serif; font-weight: 900; font-size: 13px; background: var(--bd); color: var(--tm); flex-shrink: 0; transition: all .3s; }
    .step-item.active .step-num { background: var(--gd); color: var(--yg); }
    .step-item.done   .step-num { background: var(--yg); color: var(--gd); }
    .step-lbl { font-size: 12px; font-weight: 700; color: var(--tm); white-space: nowrap; }
    .step-item.active .step-lbl { color: var(--gd); }
    .step-line { flex: 1; height: 2px; background: var(--bd); margin: 0 8px; transition: background .3s; }
    .step-line.done { background: var(--yg); }
    
    /* Device grid */
    .device-opt { border: 1.5px solid var(--bd); border-radius: 12px; padding: 14px 8px; text-align: center; cursor: pointer; transition: all .2s; background: var(--cr); user-select: none; }
    .device-opt:hover { border-color: var(--gl); background: var(--ygl); }
    .device-opt.selected { border-color: var(--gd); background: var(--ygl); box-shadow: 0 0 0 2px var(--yg); }
    .device-opt .d-icon { font-size: 26px; display: block; margin-bottom: 6px; }
    .device-opt .d-lbl  { font-size: 12px; font-weight: 700; }
    

    /* Priority */
    .pri-opt { flex: 1; padding: 10px; border: 1.5px solid var(--bd); border-radius: 10px; text-align: center; cursor: pointer; transition: all .2s; background: var(--cr); }
    .pri-dot { width: 10px; height: 10px; border-radius: 50%; margin: 0 auto 6px; }
    .pri-lbl { font-size: 12px; font-weight: 700; color: var(--tm); }
    .pri-opt.low    .pri-dot { background: #4a7c4a; }
    .pri-opt.medium .pri-dot { background: #f5c842; }
    .pri-opt.high   .pri-dot { background: #e24b4a; }
    .pri-opt.selected { border-color: var(--gd); background: var(--ygl); }
    .pri-opt.selected .pri-lbl { color: var(--gd); }
    
    /* Review */
    .review-box    { background: var(--ygl); border-radius: 12px; }
    .review-detail { border: 1.5px solid var(--bd); border-radius: 12px; }
    .review-lbl    { font-size: 11px; font-weight: 700; color: var(--tm); text-transform: uppercase; letter-spacing: .4px; }


    .btn-back-modal  { background: none; border: 1.5px solid var(--bd); color: var(--tm); font-family: 'Nunito', sans-serif; font-weight: 800; font-size: 14px; padding: 10px 22px; border-radius: 50px; transition: all .2s; }
    .btn-back-modal:hover { border-color: var(--gl); color: var(--gd); }
    .btn-continue    { background: var(--gd); color: var(--yg); font-family: 'Nunito', sans-serif; font-weight: 900; font-size: 14px; padding: 11px 28px; border-radius: 50px; border: none; transition: all .2s; }
    .btn-continue:hover { background: var(--gm); transform: translateY(-1px); }
    .btn-submit-ticket { background: var(--yg); color: var(--gd); font-family: 'Nunito', sans-serif; font-weight: 900; font-size: 14px; padding: 11px 28px; border-radius: 50px; border: none; transition: all .2s; }
    .btn-submit-ticket:hover { background: var(--ygd); }

    
    /* ── Main category grid ── */
    .cat-main-opt {
        border: 1.5px solid var(--bd);
        border-radius: 14px;
        padding: 16px 12px;
        text-align: center;
        cursor: pointer;
        transition: all .2s;
        background: var(--cr);
        user-select: none;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
    }
    .cat-main-opt:hover {
        border-color: var(--gl);
        background: var(--ygl);
    }
    .cat-main-opt.selected {
        border-color: var(--gd);
        background: var(--ygl);
        box-shadow: 0 0 0 2px var(--yg);
    }
    .cat-icon { font-size: 28px; display: block; }
    .cat-lbl  { font-family: 'Nunito', sans-serif; font-weight: 800; font-size: 13px; color: var(--gd); }

    /* ── Sub category options ── */
    .cat-sub-opt {
        border: 1.5px solid var(--bd);
        border-radius: 10px;
        padding: 10px 14px;
        cursor: pointer;
        transition: all .2s;
        background: var(--cr);
        font-size: 13px;
        font-weight: 600;
        color: var(--gd);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .cat-sub-opt:hover {
        border-color: var(--gl);
        background: var(--ygl);
    }
    .cat-sub-opt.selected {
        border-color: var(--gd);
        background: var(--ygl);
        box-shadow: 0 0 0 2px var(--yg);
        font-weight: 700;
    }
    .cat-sub-opt .sub-check {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        border: 2px solid var(--bd);
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        transition: all .2s;
    }
    .cat-sub-opt.selected .sub-check {
        background: var(--gd);
        border-color: var(--gd);
        color: var(--yg);
    }

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
    .btn-escalate { background:#fde8e8; color:#8b1a1a; font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; padding:6px 14px; border-radius:20px; border:1.5px solid #f0c0c0; cursor:pointer; transition:all .2s; }
    .btn-escalate:hover { background:#f8c8c8; }
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
    .resolve-info { background:var(--ygl); border-radius:10px; font-size:13px; color:var(--gd); }
    .btn-chat { background:#e8eeff; color:#2a4ab0; font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; padding:6px 14px; border-radius:20px; border:1.5px solid #b8c8ff; cursor:pointer; transition:all .2s; position:relative; display:inline-flex; align-items:center; gap:5px; }
    .btn-chat:hover { background:#d0dcff; border-color:#8898dd; }
    .chat-count-badge { background:#e24b4a; color:#fff; font-size:10px; font-weight:900; border-radius:20px; padding:1px 6px; font-family:'Nunito',sans-serif; min-width:18px; text-align:center; }
    /* ── Silent refresh pulse ── */
    @keyframes badgePulse {
        0%   { transform: scale(1); }
        50%  { transform: scale(1.3); background: var(--yg); color: var(--gd); }
        100% { transform: scale(1); }
    }
    .badge-pulse {
        animation: badgePulse .6s ease;
    }
    /* ── Method selector ── */
    .method-opt {
        border: 1.5px solid var(--bd);
        border-radius: 12px;
        padding: 10px 16px;
        cursor: pointer;
        transition: all .2s;
        background: var(--cr);
        display: flex;
        align-items: center;
        gap: 8px;
        user-select: none;
        min-width: 90px;
    }
    .method-opt:hover {
        border-color: var(--gl);
        background: var(--ygl);
    }
    .method-opt.selected {
        border-color: var(--mc);
        background: var(--mb);
        box-shadow: 0 0 0 2px color-mix(in srgb, var(--mc) 30%, transparent);
    }
    .method-opt .method-icon {
        font-size: 15px;
        color: var(--mc);
        flex-shrink: 0;
    }
    .method-opt .method-lbl {
        font-family: 'Nunito', sans-serif;
        font-weight: 800;
        font-size: 13px;
        color: var(--gd);
    }
    .method-opt.selected .method-lbl {
        color: var(--mc);
    }
@endsection

{{-- ══ SIDEBAR ══ --}}
@section('sidebar')

    {{-- Queue nav --}}
    <div class="sidebar-card mb-3">
        <div class="sidebar-head">Queue</div>
        <ul class="list-group sidebar-menu rounded-0">
            <li class="list-group-item {{ $status === 'all' ? 'active' : '' }}">
                <a href="{{ route('helpdesk.dashboard', ['status' => 'all']) }}"
                   class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-grid me-2"></i>All tickets</span>
                    <span class="badge-count">{{ $counts['all'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'unassigned' ? 'active' : '' }}">
                <a href="{{ route('helpdesk.dashboard', ['status' => 'unassigned']) }}"
                   class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-inbox me-2"></i>Unassigned</span>
                    <span class="badge-count">{{ $counts['unassigned'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'in-progress' ? 'active' : '' }}">
                <a href="{{ route('helpdesk.dashboard', ['status' => 'in-progress']) }}"
                   class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-arrow-repeat me-2"></i>In Progress</span>
                    <span class="badge-count">{{ $counts['in_progress'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'escalated' ? 'active' : '' }}">
                <a href="{{ route('helpdesk.dashboard', ['status' => 'escalated']) }}"
                   class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-exclamation-triangle me-2"></i>Escalated</span>
                    <span class="badge-count">{{ $counts['escalated'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'resolved' ? 'active' : '' }}">
                <a href="{{ route('helpdesk.dashboard', ['status' => 'resolved']) }}"
                   class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-check-circle me-2"></i>Resolved</span>
                    <span class="badge-count">{{ $counts['resolved'] }}</span>
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
                    'all' => 'All Tickets', 'unassigned' => 'Unassigned',
                    'in-progress' => 'In Progress', 'escalated' => 'Escalated',
                    'resolved' => 'Resolved',
                ];
            @endphp
            {{ $labels[$status] ?? 'All Tickets' }}
        </span>
        <form method="GET" action="{{ route('helpdesk.dashboard') }}"
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
                'all'         => ['label' => 'All',         'count' => $counts['all']],
                'unassigned'  => ['label' => 'Unassigned',  'count' => $counts['unassigned']],
                'in-progress' => ['label' => 'In Progress', 'count' => $counts['in_progress']],
                'escalated'   => ['label' => 'Escalated',   'count' => $counts['escalated']],
                'resolved'    => ['label' => 'Resolved',    'count' => $counts['resolved']],
            ];
        @endphp
        @foreach($tabs as $key => $tab)
            <a href="{{ route('helpdesk.dashboard', array_merge(request()->except('status'), ['status' => $key])) }}"
               class="tab-pill {{ $status === $key ? 'active' : '' }}">
                {{ $tab['label'] }} ({{ $tab['count'] }})
            </a>
        @endforeach
    </div>

    {{-- Ticket list --}}
    <div class="d-flex flex-column gap-3" id="ticketList">

        @forelse($tickets as $ticket)
            @php
                $isUnassigned = $ticket->status === 'Open' && !$ticket->assigned_to;
                $cardClass = match(true) {
                    $isUnassigned               => 'unassigned',
                    $ticket->status === 'In Progress' => 'in-progress',
                    $ticket->status === 'Escalated'   => 'escalated',
                    $ticket->status === 'Resolved'    => 'resolved',
                    default                           => 'open'
                };
                $badgeClass = match(true) {
                    $isUnassigned               => 'badge-unassigned',
                    $ticket->status === 'In Progress' => 'badge-in-progress',
                    $ticket->status === 'Escalated'   => 'badge-escalated',
                    $ticket->status === 'Resolved'    => 'badge-resolved',
                    default                           => 'badge-open'
                };
                $badgeLabel = match(true) {
                    $isUnassigned               => '<i class="bi bi-inbox me-1"></i>Unassigned',
                    $ticket->status === 'In Progress' => '<i class="bi bi-arrow-repeat me-1"></i>In Progress',
                    $ticket->status === 'Escalated'   => '<i class="bi bi-exclamation-triangle me-1"></i>Escalated',
                    $ticket->status === 'Resolved'    => '<i class="bi bi-check-circle me-1"></i>Resolved',
                    default                           => '● Open'
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
                    {{--  --}}
                    {{-- ── SLA Status indicator (In Progress only) ── --}}
                    @if($ticket->status === 'In Progress')
                        @php
                            $slaRule = \App\Models\SlaRule::where('is_active', true)
                                ->whereHas('category', fn($q) =>
                                    $q->where('name', explode(' — ', $ticket->request_category)[0] ?? '')
                                )
                                ->where('subcategory_name', explode(' — ', $ticket->request_category)[1] ?? '')
                                ->where('priority', $ticket->ticket_type)
                                ->first();

                            $minutesOpen = $ticket->created_at->diffInMinutes(now());
                            $isBreached  = $slaRule && $minutesOpen >= $slaRule->resolution_time_minutes;
                            $isAtRisk    = $slaRule && !$isBreached && $minutesOpen >= ($slaRule->resolution_time_minutes * 0.75);
                            $timeLeft    = $slaRule ? max(0, $slaRule->resolution_time_minutes - $minutesOpen) : null;

                            // ── Format time left inline (no function definition)
                            $slaTimeLeft = '';
                            if ($timeLeft !== null) {
                                if ($timeLeft <= 0) {
                                    $slaTimeLeft = 'Overdue';
                                } elseif ($timeLeft < 60) {
                                    $slaTimeLeft = intval($timeLeft) . 'm left';
                                } else {
                                    $h   = floor($timeLeft / 60);
                                    $min = intval($timeLeft % 60);
                                    $slaTimeLeft = $h . 'h' . ($min > 0 ? ' ' . $min . 'm' : '') . ' left';
                                }
                            }

                            $slaColor = $isBreached ? '#e24b4a' : ($isAtRisk ? '#f5c842' : '#3fb950');
                            $slaBg    = $isBreached ? '#fde8e8' : ($isAtRisk ? '#fff4cc' : '#d4f0d4');
                            $slaIcon  = $isBreached ? 'bi-exclamation-triangle-fill' : ($isAtRisk ? 'bi-clock-history' : 'bi-check-circle');
                            $slaLabel = $isBreached ? 'SLA Breached' : ($isAtRisk ? 'SLA At Risk' : 'SLA OK');
                        @endphp
                        @if($slaRule)
                            <span style="background:{{ $slaBg }};color:{{ $slaColor }};font-size:11px;font-weight:800;border-radius:20px;padding:3px 10px;display:inline-flex;align-items:center;gap:5px;border:1px solid {{ $slaColor }}20">
                                <i class="bi {{ $slaIcon }}"></i>
                                {{ $slaLabel }}
                                @if($isBreached)
                                    · {{ intval($minutesOpen - $slaRule->resolution_time_minutes) }}m over
                                @else
                                    · {{ $slaTimeLeft }}
                                @endif
                            </span>
                        @endif
                    @endif
                    {{--  --}}
                    @if($ticket->assignedTo)
                        <span class="tech-chip ms-auto">
                            <span class="tc-av">{{ $techInitials }}</span>
                            {{ $ticket->assignedTo->name }}
                        </span>
                    @endif
                </div>

                {{-- Escalation banner --}}
                @if($ticket->status === 'Escalated')
                    <div class="esc-banner p-2 mb-3">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        Escalated to IT Admin — Level {{ $ticket->escalation_level }}.
                        Awaiting admin resolution or reassignment.
                    </div>
                @endif

                {{-- Action buttons --}}
                <div class="d-flex gap-2 flex-wrap">

                    {{-- Unassigned: Acknowledge + Assign --}}
                    @if($isUnassigned)
                        <form method="POST"
                              action="{{ route('helpdesk.tickets.acknowledge', $ticket) }}">
                            @csrf
                            <button type="submit" class="btn-acknowledge">
                                <i class="bi bi-eye me-1"></i>Acknowledge
                            </button>
                        </form>
                        <button class="btn-assign"
                                onclick="openAssignModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}', false)">
                            <i class="bi bi-person-plus me-1"></i>Assign Technician
                        </button>
                    @endif

                    {{-- In Progress: Reassign + Escalate + Resolve + Message --}}
                    @if($ticket->status === 'In Progress')
                        <button class="btn-reassign"
                                onclick="openAssignModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}', true)">
                            <i class="bi bi-arrow-left-right me-1"></i>Reassign
                        </button>
                        <button class="btn-escalate"
                                onclick="openEscalateModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-exclamation-triangle me-1"></i>Escalate
                        </button>
                        <button class="btn-resolve"
                                onclick="openResolveModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-check-circle me-1"></i>Mark Resolved
                        </button>
                        {{-- ── Chat button ── --}}
                        <button class="btn-chat"
                                onclick="openChatModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-chat-dots me-1"></i>Message
                            @php $unread = $ticket->unreadMessages()->count(); @endphp
                            @if($unread > 0)
                                <span class="chat-count-badge" id="badge-{{ $ticket->id }}">{{ $unread }}</span>
                            @endif
                        </button>
                    @endif

                    {{-- Escalated: Reassign + Resolve + Message --}}
                    @if($ticket->status === 'Escalated')
                        <button class="btn-reassign"
                                onclick="openAssignModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}', true)">
                            <i class="bi bi-person-plus me-1"></i>Reassign to New Tech
                        </button>
                        <button class="btn-resolve"
                                onclick="openResolveModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-check-circle me-1"></i>Mark Resolved
                        </button>
                        {{-- ── Chat button ── --}}
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
        <div class="mt-4">{{ $tickets->links() }}</div>
    @endif

@endsection

{{-- ══ MODALS ══ --}}
@section('modals')

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
                                <span class="step-lbl">Issue type</span>
                            </div>
                            <div class="step-line" id="sl1"></div>
                            <div class="step-item" id="si2">
                                <div class="step-num">2</div>
                                <span class="step-lbl">Details</span>
                            </div>
                            <div class="step-line" id="sl2"></div>
                            <div class="step-item" id="si3">
                                <div class="step-num">3</div>
                                <span class="step-lbl">Review</span>
                            </div>
                        </div>

                        {{-- Step 1: Issue type --}}
                        <div class="form-step" id="fs1">

                            {{-- Main Category Selection --}}
                            <div class="mb-4">
                                <label class="form-label">
                                    Select category <span class="text-danger">*</span>
                                </label>
                                <div class="row g-2" id="mainCategoryGrid">
                                    @forelse($slaCategories as $slaCat)
                                        <div class="{{ $slaCategories->count() <= 2 ? 'col-12' : 'col-6' }}">
                                            <div class="cat-main-opt"
                                                data-cat="{{ $slaCat->name }}"
                                                data-cat-id="{{ $slaCat->id }}">
                                                <span class="cat-icon">
                                                    <i class="bi {{ $slaCat->icon ?? 'bi-tag' }}"
                                                    style="font-size:28px;color:{{ $slaCat->color ?? 'var(--gd)' }}"></i>
                                                </span>
                                                <span class="cat-lbl">{{ $slaCat->name }}</span>
                                            </div>
                                        </div>
                                    @empty
                                        {{-- Fallback if no SLA categories defined yet --}}
                                        @foreach(['Hardware' => ['🖥️','bi-laptop'], 'Software' => ['💿','bi-code-square'], 'Network' => ['🌐','bi-wifi'], 'Access Request' => ['🔐','bi-shield-lock']] as $name => $icons)
                                            <div class="col-6">
                                                <div class="cat-main-opt" data-cat="{{ $name }}">
                                                    <span class="cat-icon">{{ $icons[0] }}</span>
                                                    <span class="cat-lbl">{{ $name }}</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endforelse
                                </div>
                            </div>

                            {{-- Sub Category --}}
                            <div class="mb-4 d-none" id="subCategoryWrap">
                                <label class="form-label">
                                    Specific issue <span class="text-danger">*</span>
                                </label>
                                <div id="subCategoryList" class="d-flex flex-column gap-2"></div>
                            </div>

                            {{-- Priority --}}
                            <div class="mb-1">
                                <label class="form-label">Priority</label>
                                <div class="d-flex gap-2">
                                    <div class="pri-opt low" data-pri="Low">
                                        <div class="pri-dot"></div>
                                        <div class="pri-lbl">Low</div>
                                    </div>
                                    <div class="pri-opt medium selected" data-pri="Medium">
                                        <div class="pri-dot"></div>
                                        <div class="pri-lbl">Medium</div>
                                    </div>
                                    <div class="pri-opt high" data-pri="High">
                                        <div class="pri-dot"></div>
                                        <div class="pri-lbl">High</div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        {{-- Step 2: Details --}}
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
                                <div class="col-6">
                                    <label class="form-label">Date Acknowledged</label>
                                    <input type="date" class="form-control" id="mDateAck" name="date_acknowledged">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Time Acknowledged</label>
                                    <input type="time" class="form-control" id="mTimeAck" name="time_acknowledged">
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
                                <div class="col-6">
                                    <label class="form-label">Asset tag / serial no.</label>
                                    <input type="text" class="form-control" id="mAsset"
                                        placeholder="e.g. LT-00432">
                                </div>
                                <div class="col-6">
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
                        </div>

                        {{-- Step 3: Review --}}
                        <div class="form-step d-none" id="fs3">
                            <div class="review-box p-3 mb-3">
                                <div class="font-brand fw-900 mb-3"
                                    style="font-size:14px;color:var(--gd);text-transform:uppercase;letter-spacing:.5px">
                                    Ticket Summary
                                </div>
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="review-lbl">Category</div>
                                        <div class="fw-700" id="rv-device">—</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="review-lbl">Specific Issue</div>
                                        <div class="fw-700" id="rv-cat">—</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="review-lbl">Priority</div>
                                        <div class="fw-700" id="rv-pri">⚡ Medium</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="review-lbl">Asset / Location</div>
                                        <div class="fw-700" id="rv-asset">—</div>
                                    </div>
                                </div>
                            </div>
                            <div class="review-detail p-3">
                                <div class="review-lbl mb-1">Subject</div>
                                <div class="font-brand fw-800 mb-3" style="font-size:15px" id="rv-subject">—</div>
                                <div class="review-lbl mb-1">Concern</div>
                                <div style="font-size:13px;color:var(--tm);margin-bottom:12px" id="rv-desc">—</div>
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
    {{-- Assign / Reassign modal --}}
    <div class="modal fade" id="assignModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header-gd d-flex align-items-center justify-content-between">
                    <h5 class="mb-0" id="assignModalTitle">Assign <em>Technician</em></h5>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <form method="POST" id="assignForm">
                    @csrf
                    <div class="modal-body px-4 py-4">
                        <div class="mb-3 p-2 px-3 rounded"
                             style="background:var(--ygl);font-size:13px;color:var(--gd)">
                            Ticket <strong id="assignTicketRef"></strong> —
                            Select an available technician below.
                        </div>
                        <label class="form-label mb-2">Choose technician</label>
                        <div class="d-flex flex-column gap-2" id="techList">
                            @foreach($technicians as $tech)
                                @php
                                    $initials = strtoupper(substr($tech->name, 0, 1)) .
                                                strtoupper(substr($tech->name, strpos($tech->name, ' ') + 1, 1));
                                    $isFull   = $tech->availability === 'full';
                                    $loadPct  = min(100, $tech->active_tickets * 25);
                                    $barClass = match($tech->availability) {
                                        'busy' => 'busy', 'full' => 'full', default => ''
                                    };
                                    $badge = match($tech->availability) {
                                        'free' => ['bg' => 'var(--ygl)', 'color' => 'var(--gm)', 'label' => 'Available'],
                                        'busy' => ['bg' => '#fff4cc',    'color' => '#7a5a00',   'label' => 'Busy'],
                                        'full' => ['bg' => '#fde8e8',    'color' => '#8b1a1a',   'label' => 'Full'],
                                    };
                                @endphp
                                <div class="tech-select-option {{ $isFull ? 'disabled' : '' }}"
                                     data-tech-id="{{ $tech->id }}"
                                     data-tech-name="{{ $tech->name }}">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <div class="tc-av">{{ $initials }}</div>
                                        <div>
                                            <div class="ts-name">{{ $tech->name }}</div>
                                            <div class="ts-load">
                                                {{ $tech->active_tickets }} active ticket{{ $tech->active_tickets !== 1 ? 's' : '' }}
                                            </div>
                                        </div>
                                        <span class="ms-auto"
                                              style="background:{{ $badge['bg'] }};color:{{ $badge['color'] }};font-size:11px;font-weight:800;border-radius:20px;padding:2px 8px">
                                            {{ $badge['label'] }}
                                        </span>
                                    </div>
                                    <div class="load-bar-wrap">
                                        <div class="load-bar {{ $barClass }}"
                                             style="width:{{ $loadPct }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <input type="hidden" name="technician_id" id="selectedTechId">
                        <div class="mt-3">
                            <label class="form-label">Note to technician (optional)</label>
                            <textarea class="form-control" name="notes" rows="2"
                                      placeholder="Add any context or instructions…"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
                        <button type="button" class="btn-cancel-modal"
                                data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-confirm" id="btnConfirmAssign">
                            <i class="bi bi-check-lg me-1"></i>Confirm Assignment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Escalate modal --}}
    <div class="modal fade" id="escalateModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header-gd d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Escalate to <em>IT Admin</em></h5>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <form method="POST" id="escalateForm">
                    @csrf
                    <div class="modal-body px-4 py-4">
                        <div class="esc-banner p-3 mb-3">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            Ticket <strong id="escalateRef"></strong> — This ticket will be
                            escalated to IT Admin.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                                Reason for escalation <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" name="reason" required>
                                <option value="">Select a reason…</option>
                                <option value="Technician unable to resolve — hardware issue beyond scope">Technician unable to resolve — hardware issue beyond scope</option>
                                <option value="Technician unable to resolve — requires admin access">Technician unable to resolve — requires admin access</option>
                                <option value="Issue affecting multiple users">Issue affecting multiple users</option>
                                <option value="Repeated failure after reassignment">Repeated failure after reassignment</option>
                                <option value="Customer requested escalation">Customer requested escalation</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Escalation notes</label>
                            <textarea class="form-control" name="notes" rows="3"
                                      placeholder="Describe what was already attempted…"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
                        <button type="button" class="btn-cancel-modal"
                                data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-confirm"
                                style="background:#8b1a1a">
                            <i class="bi bi-exclamation-triangle me-1"></i>Confirm Escalation
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
                            Ticket <strong id="resolveRef"></strong> —
                            Confirm resolution and notify the customer.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                                Resolution summary <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" name="resolution_notes"
                                      rows="3" required
                                      placeholder="Describe how the issue was resolved…"></textarea>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                                   id="notifyCustomer" checked>
                            <label class="form-check-label" for="notifyCustomer"
                                   style="font-size:13px;font-weight:600">
                                Notify customer by email that ticket is resolved
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
                        <button type="button" class="btn-cancel-modal"
                                data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-confirm">
                            <i class="bi bi-check-circle me-1"></i>Mark as Resolved
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

    /* ── Dynamic SLA categories ── */
    const slaCategories = @json($slaCategoriesJson);
    const subCategoryMap = {};
    slaCategories.forEach(cat => { subCategoryMap[cat.name] = cat.subs; });

    /* ── Main category selection ── */
    $(document).on('click', '.cat-main-opt', function () {
        $('.cat-main-opt').removeClass('selected');
        $(this).addClass('selected');

        const catName = $(this).data('cat');
        const subs    = subCategoryMap[catName] || [];
        const $list   = $('#subCategoryList').empty();

        if (subs.length === 0) {
            $list.append(`
                <div style="font-size:12px;color:var(--tm);font-weight:600;padding:8px 12px;background:var(--ygl);border-radius:8px">
                    <i class="bi bi-info-circle me-1"></i>
                    No subcategories defined yet for this category. Contact IT Admin.
                </div>`);
        } else {
            subs.forEach(sub => {
                const priColor = sub.priority === 'High' ? '#e24b4a' : (sub.priority === 'Medium' ? '#f5c842' : '#4a7c4a');
                const priBg    = sub.priority === 'High' ? '#fde8e8' : (sub.priority === 'Medium' ? '#fff4cc' : '#d4f0d4');
                $list.append(`
                    <div class="cat-sub-opt" data-sub="${sub.name}" data-priority="${sub.priority}">
                        <div class="sub-check"></div>
                        <span style="flex:1">${sub.name}</span>
                        <span style="font-size:10px;font-weight:800;background:${priBg};color:${priColor};border-radius:20px;padding:2px 8px;flex-shrink:0">${sub.priority}</span>
                    </div>`);
            });
        }
        $('#subCategoryWrap').removeClass('d-none');
        $('#hCategory').val('');
    });

    /* ── Sub category selection ── */
    $(document).on('click', '.cat-sub-opt', function () {
        $('.cat-sub-opt').removeClass('selected');
        $(this).addClass('selected');
        $(this).find('.sub-check').html('<i class="bi bi-check"></i>');
        $('.cat-sub-opt:not(.selected) .sub-check').html('');

        const mainCat  = $('.cat-main-opt.selected').data('cat') || '';
        const subCat   = $(this).data('sub')      || '';
        const priority = $(this).data('priority') || '';

        $('#hCategory').val(mainCat + ' — ' + subCat);

        if (priority) {
            $('.pri-opt').removeClass('selected');
            $(`.pri-opt[data-pri="${priority}"]`).addClass('selected');
            $('#hTicketType').val(priority);
        }
    });

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

    /* ── Tech selection in modal ── */
    $(document).on('click', '.tech-select-option:not(.disabled)', function () {
        $(this).siblings().removeClass('selected');
        $(this).addClass('selected');
        $('#selectedTechId').val($(this).data('tech-id'));
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
    const stepPanels = ['fs2', 'fs1', 'fs3', 'fsSuccess'];

    let step = 1;

    function showStep(n) {
        step = n;

        // Show only the matching panel
        stepPanels.forEach((id, i) => {
            $('#' + id).toggleClass('d-none', i !== n - 1);
        });

        // Update step indicator bubbles (indicators 1-3 map to wizard steps 1-3)
        for (let i = 1; i <= 3; i++) {
            $('#si' + i).toggleClass('active', i === n).toggleClass('done', i < n);
            if (i < 3) $('#sl' + i).toggleClass('done', i < n);
        }

        // Back button: hide on step 1 and success screen
        $('#btnBack').css('visibility', n > 1 && n < 4 ? 'visible' : 'hidden');

        if (n === 3) {
            // Populate review panel
            const mainCat = $('.cat-main-opt.selected').data('cat') || '—';
            const subCat  = $('.cat-sub-opt.selected').data('sub')  || '—';
            const pri     = $('.pri-opt.selected').data('pri')      || 'Medium';
            const asset   = $('#mAsset').val() || '—';
            const loc     = $('#mLocation').val() || '';

            $('#rv-device').text(mainCat);
            $('#rv-cat').text(subCat);
            $('#rv-pri').text('⚡ ' + pri);
            $('#rv-asset').text(asset + (loc ? ' · ' + loc : ''));
            $('#rv-subject').text($('#mSubject').val() || '—');
            $('#rv-desc').text($('#mDesc').val() || '—');

            // Sync hidden fields
            $('#hCategory').val(mainCat + ' — ' + subCat);
            $('#hTicketType').val(pri);

            $('#btnNext')
                .removeClass('btn-continue')
                .addClass('btn-submit-ticket')
                .text('Submit ticket');

        } else if (n === 4) {
            $('#mFooter').hide();
        } else {
            $('#btnNext')
                .removeClass('btn-submit-ticket')
                .addClass('btn-continue')
                .text('Continue →');
        }
    }

    /* ── Next / Submit ── */
    $('#btnNext').on('click', function () {

        if (step === 1) {
            // Validate Details panel (fs2)
            if (!$('#mSubject').val().trim()) { alert('Please enter a subject.'); return; }
            if (!$('#mDesc').val().trim())    { alert('Please describe the issue.'); return; }
            $('#hAsset').val($('#mAsset').val());
            $('#hLocation').val($('#mLocation').val());
            if (!$('#mDateReceived').val())  { alert('Please enter the date received.'); return; }
            if (!$('#mTimeReceived').val())  { alert('Please enter the time received.'); return; }
            if (!$('#mRequestor').val())     { alert('Please select a requestor.'); return; }
            if (!$('#hMethod').val())        { alert('Please select a contact method.'); return; }
            showStep(2);

        } else if (step === 2) {
            // Validate Issue type panel (fs1)
            if (!$('.cat-main-opt.selected').length) { alert('Please select a category.'); return; }
            if (!$('.cat-sub-opt.selected').length)  { alert('Please select a specific issue.'); return; }

            const mainCat = $('.cat-main-opt.selected').data('cat') || '';
            const subCat  = $('.cat-sub-opt.selected').data('sub')  || '';
            $('#hCategory').val(mainCat + ' — ' + subCat);
            $('#hTicketType').val($('.pri-opt.selected').data('pri') || 'Medium');
            showStep(3);

        } else if (step === 3) {
            // Final sync before submit
            const mainCat = $('.cat-main-opt.selected').data('cat') || '';
            const subCat  = $('.cat-sub-opt.selected').data('sub')  || '';
            const pri     = $('.pri-opt.selected').data('pri')      || 'Medium';

            $('#hCategory').val(mainCat + ' — ' + subCat);
            $('#hTicketType').val(pri);
            $('#hAsset').val($('#mAsset').val());
            $('#hLocation').val($('#mLocation').val());

            if (!$('#hCategory').val().trim()) {
                alert('Please go back and select a specific issue.');
                return;
            }

            $.ajax({
                url:  $('#ticketForm').attr('action'),
                type: 'POST',
                data: $('#ticketForm').serialize(),
                success: function (response) {
                    $('#newTicketRef').text(response.ticket_number);
                    showStep(4);
                    setTimeout(() => { window.location.href = '{{ route("helpdesk.dashboard") }}'; }, 3000);
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

</script>
@endsection