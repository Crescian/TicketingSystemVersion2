@extends('layouts.app')

@section('title', ($counts['awaiting_administrator_ack'] > 0 ? 'For IT Admin Acknowledgment (' . $counts['awaiting_administrator_ack'] . ') — ' : '') . 'Supervisor Dashboard — LGICT')

@section('nav-role-badge')
    <span class="role-badge"><i class="bi bi-headset me-1"></i>Supervisor</span>
    <a href="{{ route('portal.users.index') }}" style="text-decoration:none">
      <span class="role-badge">
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
        <div class="stat-pill warn">
            <span class="num">{{ $counts['awaiting_admin_classification'] }}</span>
            <span class="lbl">For Classification</span>
        </div>
        <div class="stat-pill warn">
            <span class="num">{{ $counts['awaiting_administrator_ack'] }}</span>
            <span class="lbl">For IT Admin Ack.</span>
        </div>
        <div class="stat-pill">
            <span class="num">{{ $counts['awaiting_administrator_sla_start'] }}</span>
            <span class="lbl">Start IT Admin Request</span>
        </div>
        <div class="stat-pill">
            <span class="num">{{ $counts['admin_in_progress'] }}</span>
            <span class="lbl">In Progress Service Request</span>
        </div>
        <div class="stat-pill info">
            <span class="num">{{ $counts['pending_admin_supervisor_approval'] }}</span>
            <span class="lbl">Report For Review</span>
        </div>
        <div class="stat-pill">
            <span class="num">{{ $counts['closed'] }}</span>
            <span class="lbl">Closed</span>
        </div>
    </div>
@endsection

@section('styles')

    /* ── Report-for-review attention banner ── */
    .awaiting-banner {
        display: flex; align-items: center; gap: 14px;
        background: linear-gradient(135deg, #ff9f43, #ff7a1a);
        border-radius: 14px; padding: 14px 20px; margin-bottom: 16px;
        box-shadow: 0 4px 14px rgba(255, 122, 26, .35);
        color: #fff; text-decoration: none;
        animation: awaitingPulse 2.2s ease-in-out infinite;
    }
    .awaiting-banner:hover { color: #fff; filter: brightness(1.05); }
    .awaiting-banner .aw-icon {
        width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0;
        background: rgba(255,255,255,.25);
        display: flex; align-items: center; justify-content: center; font-size: 20px;
    }
    .awaiting-banner .aw-title { font-family: 'Nunito', sans-serif; font-weight: 900; font-size: 15px; }
    .awaiting-banner .aw-sub { font-size: 12.5px; opacity: .9; font-weight: 600; }
    .awaiting-banner .aw-cta {
        margin-left: auto; background: #fff; color: #ff7a1a;
        font-family: 'Nunito', sans-serif; font-weight: 900; font-size: 13px;
        padding: 8px 18px; border-radius: 50px; white-space: nowrap;
    }
    @keyframes awaitingPulse {
        0%, 100% { box-shadow: 0 4px 14px rgba(255, 122, 26, .35); }
        50%      { box-shadow: 0 4px 22px rgba(255, 122, 26, .65); }
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
    .btn-takeover { background:#fde8e8; color:#8b1a1a; font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; padding:6px 14px; border-radius:20px; border:1.5px solid #f0c0c0; cursor:pointer; transition:all .2s; }
    .btn-takeover:hover { background:#f8c8c8; }
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
@endsection

{{-- ══ SIDEBAR ══ --}}
@section('sidebar')

    <x-recent-tickets-widget />

    {{-- Queue nav --}}
    <div class="sidebar-card mb-3">
        <div class="sidebar-head">Queue</div>
        <ul class="list-group sidebar-menu rounded-0">
            <li class="list-group-item {{ $status === 'active' ? 'active' : '' }}">
                <a href="{{ route('supervisor.dashboard', ['status' => 'active']) }}"
                class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-grid me-2"></i>Active</span>
                    <span class="badge-count">{{ $counts['active'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'awaiting-admin-supervisor' ? 'active' : '' }}">
                <a href="{{ route('supervisor.dashboard', ['status' => 'awaiting-admin-supervisor']) }}"
                class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-inbox me-2"></i>For Acknowledgment</span>
                    <span class="badge-count">{{ $counts['awaiting_admin_supervisor'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'awaiting-admin-classification' ? 'active' : '' }}">
                <a href="{{ route('supervisor.dashboard', ['status' => 'awaiting-admin-classification']) }}"
                class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-tags me-2"></i>For Classification</span>
                    <span class="badge-count">{{ $counts['awaiting_admin_classification'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'awaiting-administrator-ack' ? 'active' : '' }}">
                <a href="{{ route('supervisor.dashboard', ['status' => 'awaiting-administrator-ack']) }}"
                class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-person-check me-2"></i>For IT Admin Acknowledgment</span>
                    <span class="badge-count">{{ $counts['awaiting_administrator_ack'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'awaiting-administrator-sla-start' ? 'active' : '' }}">
                <a href="{{ route('supervisor.dashboard', ['status' => 'awaiting-administrator-sla-start']) }}"
                class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-stopwatch me-2"></i>Start IT Admin Request</span>
                    <span class="badge-count">{{ $counts['awaiting_administrator_sla_start'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'admin-in-progress' ? 'active' : '' }}">
                <a href="{{ route('supervisor.dashboard', ['status' => 'admin-in-progress']) }}"
                class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-arrow-repeat me-2"></i>In Progress Service Request</span>
                    <span class="badge-count">{{ $counts['admin_in_progress'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'admin-on-hold' ? 'active' : '' }}">
                <a href="{{ route('supervisor.dashboard', ['status' => 'admin-on-hold']) }}"
                class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-pause-circle me-2"></i>On Hold</span>
                    <span class="badge-count">{{ $counts['admin_on_hold'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'admin-in-progress-report' ? 'active' : '' }}">
                <a href="{{ route('supervisor.dashboard', ['status' => 'admin-in-progress-report']) }}"
                class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-file-earmark-text me-2"></i>In Progress Service Report</span>
                    <span class="badge-count">{{ $counts['admin_in_progress_report'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'pending-reclassification' ? 'active' : '' }}">
                <a href="{{ route('supervisor.dashboard', ['status' => 'pending-reclassification']) }}"
                class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-arrow-repeat me-2"></i>For Reclassification</span>
                    <span class="badge-count">{{ $counts['pending_admin_reclassification'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'pending-admin-supervisor-approval' ? 'active' : '' }}">
                <a href="{{ route('supervisor.dashboard', ['status' => 'pending-admin-supervisor-approval']) }}"
                class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-arrow-repeat me-2"></i>Report For Review</span>
                    <span class="badge-count">{{ $counts['pending_admin_supervisor_approval'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'awaiting-requestor' ? 'active' : '' }}">
                <a href="{{ route('supervisor.dashboard', ['status' => 'awaiting-requestor']) }}"
                class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-person-check me-2"></i>Requestor Confirmation</span>
                    <span class="badge-count">{{ $counts['awaiting_requestor'] }}</span>
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

    {{-- Work Hours Calendar --}}
    <div class="sidebar-card mb-3">
        <ul class="list-group sidebar-menu rounded-0">
            <li class="list-group-item">
                <a href="{{ route('supervisor.calendar') }}" class="d-flex align-items-center gap-2 text-decoration-none w-100">
                    <i class="bi bi-calendar3-week me-1"></i>Work Hours Calendar
                </a>
            </li>
        </ul>
    </div>

    {{-- IT team availability — everyone a ticket could pass through (Helpdesk,
         IT Support Specialist, IT Admin, and their Supervisors), not just IT
         Admin. Presence-based (online in the last 5 min) since Helpdesk/
         Technician don't share a ticket-count gauge with the Admin track. --}}
    <div class="sidebar-card">
        <div class="sidebar-head">IT Team Availability</div>
        <div>
            @forelse($itTeamAvailability as $member)
                @php
                    $initials = strtoupper(substr($member->name, 0, 1)) .
                                strtoupper(substr($member->name, strpos($member->name, ' ') + 1, 1));
                @endphp
                <div class="tech-row">
                    <div class="tech-av-lg">{{ $initials }}</div>
                    <div>
                        <div class="tech-name">{{ $member->name }}</div>
                        <div class="tech-load">{{ $member->role?->role_name }} — {{ $member->online ? 'Online' : 'Offline' }}</div>
                    </div>
                    <div class="avail-dot {{ $member->online ? 'free' : 'full' }}"
                         title="{{ $member->online ? 'Online' : 'Offline' }}"></div>
                </div>
            @empty
                <div class="p-3" style="font-size:13px;color:var(--tm)">
                    No IT staff on record.
                </div>
            @endforelse
        </div>
        <div class="p-2 px-3" style="font-size:11px;color:var(--tm);border-top:1px solid var(--bd)">
            <span class="me-3"><span class="avail-dot free d-inline-block me-1"></span>Online</span>
            <span><span class="avail-dot full d-inline-block me-1"></span>Offline</span>
        </div>
    </div>

@endsection

{{-- ══ MAIN CONTENT ══ --}}
@section('content')

    {{-- Attention banners — these queues need the supervisor's own action
         before a request can move forward, so each stays up (not dismissible)
         for as long as any ticket is sitting in that state. --}}
    @if($counts['awaiting_admin_classification'] > 0)
        <a href="{{ route('supervisor.dashboard', ['status' => 'awaiting-admin-classification']) }}"
           class="awaiting-banner">
            <span class="aw-icon"><i class="bi bi-tags"></i></span>
            <span>
                <div class="aw-title">
                    {{ $counts['awaiting_admin_classification'] }}
                    {{ Str::plural('support request', $counts['awaiting_admin_classification']) }} awaiting classification
                </div>
                <div class="aw-sub">Classify {{ $counts['awaiting_admin_classification'] === 1 ? 'it' : 'them' }} so it can be scheduled and assigned.</div>
            </span>
            <span class="aw-cta">Classify Now <i class="bi bi-arrow-right ms-1"></i></span>
        </a>
    @endif

    @if($counts['pending_admin_reclassification'] > 0)
        <a href="{{ route('supervisor.dashboard', ['status' => 'pending-reclassification']) }}"
           class="awaiting-banner">
            <span class="aw-icon"><i class="bi bi-arrow-repeat"></i></span>
            <span>
                <div class="aw-title">
                    {{ $counts['pending_admin_reclassification'] }}
                    {{ Str::plural('reclassification request', $counts['pending_admin_reclassification']) }} awaiting your decision
                </div>
                <div class="aw-sub">Review the admin's reclassification request so we can move {{ $counts['pending_admin_reclassification'] === 1 ? 'it' : 'them' }} forward.</div>
            </span>
            <span class="aw-cta">Review Now <i class="bi bi-arrow-right ms-1"></i></span>
        </a>
    @endif

    @if($counts['pending_admin_supervisor_approval'] > 0)
        <a href="{{ route('supervisor.dashboard', ['status' => 'pending-admin-supervisor-approval']) }}"
           class="awaiting-banner">
            <span class="aw-icon"><i class="bi bi-exclamation-lg"></i></span>
            <span>
                <div class="aw-title">
                    {{ $counts['pending_admin_supervisor_approval'] }}
                    {{ Str::plural('support request', $counts['pending_admin_supervisor_approval']) }} awaiting your review
                </div>
                <div class="aw-sub">Review the submitted report so we can close {{ $counts['pending_admin_supervisor_approval'] === 1 ? 'it' : 'them' }} out.</div>
            </span>
            <span class="aw-cta">Review Now <i class="bi bi-arrow-right ms-1"></i></span>
        </a>
    @endif

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
                    'active'                             => 'Active',
                    'awaiting-admin-supervisor'         => 'For Acknowledgment',
                    'awaiting-admin-classification'     => 'For Classification',
                    'awaiting-administrator-ack'        => 'Assigned',
                    'awaiting-administrator-sla-start'  => 'Assigned',
                    'admin-in-progress'                 => 'In Progress Service Request',
                    'admin-on-hold'                     => 'On Hold',
                    'admin-in-progress-report'          => 'In Progress Service Report',
                    'pending-reclassification'          => 'For Reclassification',
                    'pending-admin-supervisor-approval' => 'Report For Review',
                    'awaiting-requestor'                => 'Requestor Confirmation',
                    'closed'                            => 'Closed',
                ];
            @endphp
            {{ $labels[$status] ?? 'All Support Requests' }}
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
    <!-- <div class="d-flex flex-wrap gap-2 mb-3">
        @php
            $tabs = [
                'active'                            => ['label' => 'Active',                            'count' => $counts['active']],
                'awaiting-admin-supervisor'        => ['label' => 'For Acknowledgment',        'count' => $counts['awaiting_admin_supervisor']],
                'awaiting-admin-classification'    => ['label' => 'For Classification',    'count' => $counts['awaiting_admin_classification']],
                'awaiting-administrator-ack'       => ['label' => 'For IT Admin Acknowledgment',      'count' => $counts['awaiting_administrator_ack']],
                'awaiting-administrator-sla-start' => ['label' => 'Start IT Admin Request',               'count' => $counts['awaiting_administrator_sla_start']],
                'admin-in-progress'                => ['label' => 'In Progress Service Request',                'count' => $counts['admin_in_progress']],
                'admin-on-hold'                    => ['label' => 'On Hold',                    'count' => $counts['admin_on_hold']],
                'admin-in-progress-report'         => ['label' => 'In Progress Service Report',         'count' => $counts['admin_in_progress_report']],
                'pending-reclassification'         => ['label' => 'For Reclassification',         'count' => $counts['pending_admin_reclassification']],
                'pending-admin-supervisor-approval'=> ['label' => 'Report For Review','count' => $counts['pending_admin_supervisor_approval']],
                'awaiting-requestor'               => ['label' => 'Requestor Confirmation',               'count' => $counts['awaiting_requestor']],
                'closed'                           => ['label' => 'Closed',                           'count' => $counts['closed']],
            ];
        @endphp
        @foreach($tabs as $key => $tab)
            <a href="{{ route('supervisor.dashboard', array_merge(request()->except('status'), ['status' => $key])) }}"
               class="tab-pill {{ $status === $key ? 'active' : '' }}">
                {{ $tab['label'] }} ({{ $tab['count'] }})
            </a>
        @endforeach
    </div> -->

    {{-- Ticket list --}}
    <div class="d-flex flex-column gap-3" id="ticketList">
        @forelse($tickets as $ticket)
            @php
                // Mirrors SupervisorDashboardController@index's $counts query split:
                // 'awaiting_admin_supervisor' = escalated up to this queue, not yet
                // acknowledged (status Escalated, pending_role IT Admin, unassigned);
                // 'awaiting_admin_classification' = acknowledged (or routed straight in for
                // an Admin-only subcategory) and ready to classify & assign (status For
                // Acknowledgment, pending_role IT Admin). Both used to be distinct status
                // strings; now told apart by status + pending_role instead.
                $isUnassigned = $ticket->status === 'Escalated'
                    && $ticket->pending_role === 'Supervisor - IT Admin'
                    && is_null($ticket->assigned_to);
                // Covers both a fresh admin-only ticket routed straight in by
                // Helpdesk (already Classified — just needs assigning, though the
                // SLA rule can still be overridden) and an escalated ticket that's
                // been acknowledged and genuinely still needs a first
                // classification (For Acknowledgment).
                $isAwaitingClassification = in_array($ticket->status, ['For Acknowledgment', 'Classified'], true)
                    && $ticket->pending_role === 'Supervisor - IT Admin'
                    && is_null($ticket->assigned_to);
                // 'Awaiting Administrator Acknowledgement' and 'Awaiting Administrator SLA
                // Start' both collapsed into the single 'Assigned' status — told apart by
                // tech_acknowledged_at (same field the Admin's own dashboard uses).
                $isAwaitingAdminAck = $ticket->status === 'Assigned' && is_null($ticket->tech_acknowledged_at);
                $isReadyForSlaStart = $ticket->status === 'Assigned' && !is_null($ticket->tech_acknowledged_at);
                // Paused waiting on more information from the requestor (see TicketHold).
                $isOnHold = $ticket->status === 'On Hold';

                $cardClass = match(true) {
                    $isUnassigned                                      => 'unassigned',
                    $isAwaitingClassification                          => 'awaiting-admin-classification',
                    $isAwaitingAdminAck                                => 'awaiting-administrator-ack',
                    $isReadyForSlaStart                                => 'awaiting-administrator-sla-start',
                    $isOnHold                                          => 'unassigned',
                    $ticket->status === 'In Progress Service Request'  => 'admin-in-progress',
                    $ticket->status === 'In Progress Service Report'   => 'admin-in-progress-report',
                    $ticket->status === 'Report For Review'            => 'pending-admin-supervisor-Approval',
                    $ticket->status === 'Requestor Confirmation'       => 'awaiting-requestor',
                    $ticket->status === 'Closed'                       => 'closed',
                    $ticket->status === 'Cancelled'                    => 'cancelled',
                    default                                            => 'awaiting-admin-supervisor'
                };
                $badgeClass = match(true) {
                    $isUnassigned                                      => 'badge-unassigned',
                    $isAwaitingClassification                          => 'badge-awaiting-admin-classification',
                    $isAwaitingAdminAck                                => 'badge-awaiting-administrator-ack',
                    $isReadyForSlaStart                                => 'badge-awaiting-administrator-sla-start',
                    $isOnHold                                          => 'badge-unassigned',
                    $ticket->status === 'In Progress Service Request'  => 'badge-admin-in-progress',
                    $ticket->status === 'In Progress Service Report'   => 'badge-admin-in-progress-report',
                    $ticket->status === 'Report For Review'            => 'badge-admin-in-progress',
                    $ticket->status === 'Requestor Confirmation'       => 'badge-awaiting-requestor',
                    $ticket->status === 'Closed'                       => 'badge-closed',
                    $ticket->status === 'Cancelled'                    => 'badge-cancelled',
                    default                                            => 'badge-awaiting-admin-supervisor'
                };
                $badgeLabel = match(true) {
                    $isUnassigned                                      => '<i class="bi bi-inbox me-1"></i>For Acknowledgment',
                    $isAwaitingClassification                          => '<i class="bi bi-tags me-1"></i>For Classification',
                    $isAwaitingAdminAck                                => '<i class="bi bi-person-check me-1"></i>For IT Admin Ack.',
                    $isReadyForSlaStart                                => '<i class="bi bi-stopwatch me-1"></i>Start IT Admin Request',
                    $isOnHold                                          => '<i class="bi bi-pause-circle me-1"></i>On Hold',
                    $ticket->status === 'In Progress Service Request'  => '<i class="bi bi-gear-fill me-1"></i>In Progress Service Request',
                    $ticket->status === 'In Progress Service Report'   => '<i class="bi bi-file-earmark-text me-1"></i>Preparing Report',
                    $ticket->status === 'Report For Review'            => '<i class="bi bi-clock-history me-1"></i>Report For Review',
                    $ticket->status === 'Requestor Confirmation'       => '<i class="bi bi-person-check me-1"></i>Requestor Confirmation',
                    $ticket->status === 'Closed'                       => '<i class="bi bi-check-circle-fill me-1"></i>Closed',
                    $ticket->status === 'Cancelled'                    => '<i class="bi bi-x-circle-fill me-1"></i>Cancelled',
                    default                                            => '● ' . $ticket->status
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
                <div class="ticket-desc mb-2">{{ $ticket->concern }}</div>

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

                    {{-- ── SLA Status indicator — the resolution clock is still running
                         while drafting the report, it only stops at Report For Review. ── --}}
                    @if(in_array($ticket->status, ['In Progress Service Request', 'In Progress Service Report']) && $ticket->sla_due_at)
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

                {{-- Attachments (requestor's originals + resolution/update evidence) --}}
                @if($ticket->attachments->isNotEmpty())
                    <div class="mb-3">
                        <div style="font-size:12px;font-weight:800;color:var(--tm);margin-bottom:6px">
                            <i class="bi bi-paperclip me-1"></i>Attachments
                        </div>
                        <div class="d-flex flex-column gap-1">
                            @foreach($ticket->attachments as $attachment)
                                @php
                                    $viewableMimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'text/plain'];
                                    $isViewable = in_array($attachment->mime_type, $viewableMimes);
                                @endphp
                                <div class="d-flex align-items-center gap-2 p-2"
                                     style="background:var(--ygl);border-radius:8px;font-size:12px">
                                    <i class="bi bi-file-earmark-text" style="color:var(--tm)"></i>
                                    <span style="font-weight:600;color:var(--gd)">{{ $attachment->original_name }}</span>
                                    <span style="color:var(--tm)">({{ $attachment->humanSize() }})</span>
                                    <div class="ms-auto d-flex gap-2">
                                        @if($isViewable)
                                            <button type="button"
                                                    onclick="openAttachmentPreview('{{ $attachment->id }}', {{ Illuminate\Support\Js::from($attachment->original_name) }}, '{{ $attachment->mime_type }}')"
                                                    class="text-decoration-none border-0 bg-transparent p-0" style="color:var(--gd);font-weight:700">
                                                <i class="bi bi-eye me-1"></i>View
                                            </button>
                                        @else
                                            <a href="{{ route('attachments.view', $attachment) }}"
                                               target="_blank" rel="noopener"
                                               class="text-decoration-none" style="color:var(--gd);font-weight:700">
                                                <i class="bi bi-box-arrow-up-right me-1"></i>Open in New Tab
                                            </a>
                                        @endif
                                        <a href="{{ route('attachments.download', $attachment) }}"
                                           class="text-decoration-none" style="color:var(--tm);font-weight:700">
                                            <i class="bi bi-download me-1"></i>Download
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Action buttons --}}
                <div class="d-flex gap-2 flex-wrap">

                    {{-- Escalated up to this queue, unassigned: Acknowledge into the queue --}}
                    @if($isUnassigned)
                        <form method="POST" action="{{ route('supervisor.tickets.acknowledge', $ticket) }}">
                            @csrf
                            <button type="submit" class="btn-acknowledge">
                                <i class="bi bi-eye me-1"></i>Acknowledge
                            </button>
                        </form>
                    @endif

                    {{-- Awaiting Classification: classify & assign to an IT Admin --}}
                    @if($isAwaitingClassification)
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
                                    'workloadClassId' => $ticket->workload_class_id,
                                ]) }})">
                            <i class="bi bi-tags me-1"></i>{{ $ticket->subcategory_name ? 'Review & Assign' : 'Classify & Assign' }}
                        </button>
                    @endif

                    {{-- Pending Reclassification: Approve / Reject --}}
                    @if($ticket->reclassificationRequests->contains('status', 'pending'))
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
                        <button class="btn-cancel-modal"
                                onclick="openRejectReclassModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-x-lg me-1"></i>Reject
                        </button>
                    @endif

                    {{-- Assigned, not yet acknowledged: view-only, only the assigned admin can acknowledge --}}
                    @if($isAwaitingAdminAck)
                        <span class="resolve-info px-3 py-2">
                            <i class="bi bi-hourglass-split me-1"></i>
                            Waiting for {{ $ticket->assignedTo->name ?? 'the assigned admin' }} to acknowledge
                        </span>
                    @endif

                    {{-- Assigned, acknowledged: view-only, only the assigned admin can start work
                         (starting the SLA clock is their call, not the supervisor's) --}}
                    @if($isReadyForSlaStart)
                        <span class="resolve-info px-3 py-2">
                            <i class="bi bi-play-circle me-1"></i>
                            Waiting for {{ $ticket->assignedTo->name ?? 'the assigned admin' }} to start work
                        </span>
                    @endif

                    @php
                        // Resolving/pausing an in-progress ticket is the assignee's own
                        // call — the Supervisor only gets those actions on a ticket they
                        // themselves took over (adminTakeover()/adminStartSla()); an IT
                        // Admin's own ticket stays theirs to resolve/pause via their own
                        // dashboard, same "only the assignee" rule as isReadyForSlaStart
                        // above.
                        $isOwnTakeover = $ticket->assigned_to === Auth::id();
                    @endphp
                    {{-- In Progress Service Request / In Progress Service Report: Reassign + Resolve --}}
                    @if(in_array($ticket->status, ['In Progress Service Request', 'In Progress Service Report']))
                        <button class="btn-reassign"
                                onclick="openReassignModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-arrow-left-right me-1"></i>Reassign
                        </button>
                        <button class="btn-reassign"
                                onclick="openEscManagerModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-arrow-up-circle me-1"></i>Escalate to Manager
                        </button>
                        @if($isOwnTakeover)
                            @if($ticket->status === 'In Progress Service Request')
                                <form method="POST" action="{{ route('supervisor.tickets.start-report', $ticket) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn-resolve">
                                        <i class="bi bi-check2 me-1"></i>Mark Fixed
                                    </button>
                                </form>
                                <button class="btn-reassign"
                                        onclick="openPauseModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                                    <i class="bi bi-pause-circle me-1"></i>Pause — Need Info
                                </button>
                            @else
                                <button class="btn-resolve"
                                        onclick="openResolveModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}', '{{ $ticket->started_at?->toIso8601String() }}')">
                                    <i class="bi bi-file-earmark-text me-1"></i>Prepare Service Report
                                </button>
                            @endif
                        @else
                            <span class="resolve-info px-3 py-2">
                                <i class="bi bi-hourglass-split me-1"></i>
                                Only {{ $ticket->assignedTo->name ?? 'the assigned admin' }} can resolve this ticket
                            </span>
                        @endif
                    @endif

                    {{-- On Hold: waiting on the requestor — Resume Work only, and only for
                         whoever paused it (the assignee) --}}
                    @if($isOnHold)
                        @if($ticket->hold_reason)
                            <div class="w-100 mb-2 p-2 px-3 rounded" style="background:var(--ygl);font-size:12px;color:var(--gd)">
                                <i class="bi bi-pause-circle me-1"></i>
                                <strong>Waiting on requestor:</strong> {{ $ticket->hold_reason }}
                            </div>
                        @endif
                        @if($isOwnTakeover)
                            <form method="POST" action="{{ route('supervisor.tickets.resume', $ticket) }}">
                                @csrf
                                <button type="submit" class="btn-resolve">
                                    <i class="bi bi-play-circle me-1"></i>Resume Work
                                </button>
                            </form>
                        @else
                            <span class="resolve-info px-3 py-2">
                                <i class="bi bi-hourglass-split me-1"></i>
                                Waiting for {{ $ticket->assignedTo->name ?? 'the assigned admin' }} to resume work
                            </span>
                        @endif
                    @endif
                {{-- Report For Review: view the submitted PDF to check what the
                     specialist attached, then Validate Resolution / Request Revision --}}
                    @if($ticket->status === 'Report For Review')
                        <button type="button" class="btn-service-report"
                                onclick="openServiceReportPreview('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-file-earmark-pdf me-1"></i>View Service Report
                        </button>
                        @if($ticket->validated_at)
                            <span class="resolve-info px-3 py-2">
                                <i class="bi bi-check-circle-fill me-1"></i>Validated — awaiting requestor confirmation
                            </span>
                        @else
                            <button class="btn-resolve"
                                    onclick="openValidateModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                                <i class="bi bi-check-circle me-1"></i>Validate Resolution
                            </button>
                            <button class="btn-takeover"
                                    onclick="openAdminRevisionModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                                <i class="bi bi-arrow-counterclockwise me-1"></i>Request Revision
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

                    {{-- Closed: printable service report --}}
                    @if($ticket->status === 'Closed')
                        <button type="button" class="btn-service-report"
                                onclick="openServiceReportPreview('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-file-earmark-pdf me-1"></i>Service Report
                        </button>
                    @endif

                </div>
            </div>
        @empty
            <div class="ticket-card p-5 text-center">
                <div style="font-size:48px;opacity:.3">🎫</div>
                <div class="mt-3 font-brand fw-900" style="font-size:18px;color:var(--tm)">
                    No support requests found.
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
                                <span style="font-weight:400;color:var(--tm)">(defaults from the rule above — adjust if this support request needs different targets)</span>
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

                        <div class="mb-3 p-3 rounded" style="background:#e8f5ee;border:1px solid #a8ddc0">
                            <label class="d-flex align-items-center gap-2" style="cursor:pointer;font-weight:700;color:#1a5a3a">
                                <input type="checkbox" name="take_over" id="caTakeOver" value="1"
                                       style="width:16px;height:16px;cursor:pointer">
                                Take over this ticket myself
                            </label>
                            <div style="font-size:11.5px;color:#1a5a3a;margin-top:4px">
                                Keeps this ticket with you instead of assigning an IT Admin —
                                it goes straight to In Progress, same as taking over an escalated ticket.
                            </div>
                        </div>

                        <div id="caAssignWrap">
                            <label class="form-label mb-2">Assign IT Admin</label>
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
                        </div>

                        <div class="mt-3">
                            <label class="form-label">Notes (optional)</label>
                            <textarea class="form-control" name="notes" rows="2" placeholder="Context for the specialist…"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
                        <button type="button" class="btn-cancel-modal" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-confirm"><span id="caSubmitBtnText">Confirm Classification & Assignment</span></button>
                    </div>
                </form>
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
                            The ticket's classification will be updated to the IT Admin's proposal and
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
                            The ticket will be returned to the requesting IT Admin unchanged.
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

    {{-- Request Revision modal --}}
    <div class="modal fade" id="adminRevisionModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header-gd d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Request <em>Revision</em></h5>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <form method="POST" id="adminRevisionForm">
                    @csrf
                    <div class="modal-body px-4 py-4">
                        <div class="p-3 mb-3 rounded" style="background:#eee;color:#555;font-size:13px">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>
                            Ticket <strong id="adminRevisionRef"></strong> — this sends the resolution back to the
                            IT Admin instead of validating it. It reopens as In Progress Service Request with your reason attached.
                        </div>
                        <div>
                            <label class="form-label">
                                What needs to be revised? <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" name="revision_notes"
                                      rows="3" required
                                      placeholder="Be specific — this is what the IT Admin will see…"></textarea>
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
    {{-- Pause modal --}}
    <div class="modal fade" id="pauseModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header-gd d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Pause <em>Support Request</em></h5>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <form method="POST" id="pauseForm">
                    @csrf
                    <div class="modal-body px-4 py-4">
                        <div class="resolve-info p-3 mb-3">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            <strong id="pauseRef"></strong> — the requestor will be emailed this
                            reason right away. Work resumes once you click Resume Work.
                        </div>
                        <label class="form-label">What do you need from the requestor? <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="reason" rows="3" required
                                  placeholder="e.g. Which laptop unit needs the backup — the old one or the replacement?"></textarea>
                    </div>
                    <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
                        <button type="button" class="btn-cancel-modal" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-confirm">
                            <i class="bi bi-pause-circle me-1"></i>Pause &amp; Notify Requestor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    {{-- Resolve modal (Supervisor's own take-over ticket) --}}
    <div class="modal fade" id="supResolveModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header-gd d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Resolve <em>& Close</em></h5>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <form method="POST" id="supResolveForm" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body px-4 py-4">
                        <div class="resolve-info p-3 mb-3">
                            <i class="bi bi-check-circle me-1"></i>
                            Resolving <strong id="supResolveRef"></strong> — you have no reviewer
                            above you on a take-over ticket, so this self-approves straight
                            through to the requestor for confirmation.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Service Type <span class="text-danger">*</span></label>
                            <div class="d-flex gap-3 flex-wrap" style="font-size:13px;font-weight:600">
                                <label class="d-flex align-items-center gap-1" style="cursor:pointer">
                                    <input type="radio" name="service_type" value="Onsite" checked>Onsite
                                </label>
                                <label class="d-flex align-items-center gap-1" style="cursor:pointer">
                                    <input type="radio" name="service_type" value="Remote">Remote
                                </label>
                                <label class="d-flex align-items-center gap-1" style="cursor:pointer">
                                    <input type="radio" name="service_type" value="Preventive">Preventive
                                </label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Service Details / Action Taken <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="resolution_notes" rows="3" required
                                      placeholder="Describe exactly what was done to resolve the issue…"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Findings & Analysis <span style="font-weight:400;color:var(--tm)">(optional)</span></label>
                            <textarea class="form-control" name="findings" rows="2"
                                      placeholder="Root cause, diagnostics, what was found…"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Other Observation / Recommendation <span style="font-weight:400;color:var(--tm)">(optional)</span></label>
                            <textarea class="form-control" name="recommendation" rows="2"
                                      placeholder="Follow-up suggestions, preventive advice…"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Time spent</label>
                            <div class="p-2 px-3 rounded d-flex align-items-center gap-2"
                                 style="background:var(--ygl);font-weight:800;color:var(--gd)">
                                <i class="bi bi-stopwatch"></i>
                                <span id="supResolveTimeSpent">—</span>
                                <span style="font-weight:600;font-size:11px;color:var(--tm)">(since you took this ticket over)</span>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Supporting files <span style="font-weight:400;color:var(--tm)">(optional)</span></label>
                            <input type="file" class="form-control" id="supResolveAttachments" name="attachments[]"
                                   multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.txt">
                            <div style="font-size:11px;color:var(--tm);margin-top:4px">
                                Up to 5 files, 10MB each. Screenshots, logs, or documents that support the resolution.
                            </div>
                            <div id="supResolveAttachmentList" class="d-flex flex-column gap-1 mt-2"></div>
                        </div>
                    </div>
                    <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
                        <button type="button" class="btn-cancel-modal" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-confirm">
                            <i class="bi bi-check-circle me-1"></i>Submit Resolution
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
                    <h5 class="mb-0">New <em>Support</em> Request</h5>
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
                                        Support Request Number
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
                                    Support Request Summary
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
                                    Your support request will be assigned to an available IT Support Specialist.
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
                                Support request submitted!
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

    <x-service-report-modal />
    <x-attachment-preview-modal />
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
                const priColor = sub.priority === 'Critical' ? '#8b0000' : (sub.priority === 'High' ? '#e24b4a' : (sub.priority === 'Medium' ? '#f5c842' : '#4a7c4a'));
                $subList.append(`<div class="cat-sub-opt" data-rule-id="${sub.rule_id}" data-name="${sub.name}"
                     data-priority="${sub.priority}" data-response="${sub.response}" data-resolution="${sub.resolution}">
                    <div class="sub-check"></div>
                    <div style="flex:1">
                        <div>${sub.name}</div>
                        ${sub.description ? `<div style="font-size:11px;font-weight:400;color:var(--tm);margin-top:2px">${escapeHtmlChat(sub.description)}</div>` : ''}
                    </div>
                    <span style="font-size:10px;font-weight:800;color:${priColor}">${sub.priority} · ${sub.resolution}m SLA</span>
                </div>`);
            });
        }
        $('#caSubWrap').removeClass('d-none');
        $('#caOverrideWrap').addClass('d-none');
    });

    /* ── Dynamic SLA categories ── */
    const slaCategories = @json($slaCategoriesJson);
    const workloadClasses = @json($workloadClassesJson);
    const subCategoryMap = {};
    slaCategories.forEach(cat => { subCategoryMap[cat.name] = cat.subs; });


    /* ── Classify & Assign modal ── */
    window.openClassifyAssignModal = function (ticketId, ticketNumber, defaults) {
        $('#caTicketRef').text('#' + ticketNumber);
        $('#classifyAssignForm').attr('action', `{{ route('supervisor.tickets.admin-classify-assign', ['ticket' => '__ID__']) }}`.replace('__ID__', ticketId))
        $('#caSlaRuleId, #caTechId').val('');
        $('#caSubWrap, #caOverrideWrap').addClass('d-none');
        $('#caSubList').empty();
        $('#caResponseTime, #caResolutionTime').val('');
        $('#caCategoryList .cat-main-opt, #caTechList .tech-select-option').removeClass('selected');
        $('#caTakeOver').prop('checked', false);
        $('#caAssignWrap').removeClass('d-none');
        updateClassifyAssignSubmitLabel();

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

        // Reflect the workload class Helpdesk already picked, if any — set silently
        // (no 'change' trigger) so it doesn't clobber the response/resolution values
        // set from defaults below, which already carry any manual customization.
        if (defaults && defaults.workloadClassId) {
            $wcSelect.val(defaults.workloadClassId);
        }

        // Pre-select Helpdesk's classification, if this ticket already has one — a
        // fresh admin-only ticket routed straight in arrives already Classified, so
        // this saves re-picking the same category/subcategory from scratch. Still
        // fully editable — this just saves starting from blank.
        if (defaults && defaults.categoryId) {
            $('#caCategoryList .cat-main-opt').filter(function () {
                return $(this).data('cat-id') === defaults.categoryId;
            }).trigger('click');

            $('#caSubList .cat-sub-opt').filter(function () {
                return $(this).data('name') === defaults.subcategoryName;
            }).trigger('click');

            if (defaults.priority) $('#caPriority').val(defaults.priority);
            if (defaults.response) $('#caResponseTime').val(defaults.response);
            if (defaults.resolution) $('#caResolutionTime').val(defaults.resolution);
        }

        new bootstrap.Modal('#classifyAssignModal').show();
    };

    /* ── Approve / Reject Reclassification modals ── */
    window.openApproveReclassModal = function (ticketId, ticketNumber) {
        $('#arTicketRef').text('#' + ticketNumber);
        $('#approveReclassForm').attr('action', '/supervisor/tickets/' + ticketId + '/approve-reclassification');
        $('#approveReclassForm textarea[name="review_notes"]').val('');
        new bootstrap.Modal('#approveReclassModal').show();
    };

    window.openRejectReclassModal = function (ticketId, ticketNumber) {
        $('#rrTicketRef').text('#' + ticketNumber);
        $('#rejectReclassForm').attr('action', '/supervisor/tickets/' + ticketId + '/reject-reclassification');
        $('#rejectReclassForm textarea[name="review_notes"]').val('');
        new bootstrap.Modal('#rejectReclassModal').show();
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

    /* ── Search submits on Enter only (native form submit) — no more
           reloading the page mid-keystroke while the user is still typing. ── */

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

    /* ── Resolve modal (Supervisor's own take-over ticket) ── */
    let supResolveTimeSpentTimer = null;
    window.openResolveModal = function (ticketId, ticketNumber, startedAt) {
        $('#supResolveRef').text('#' + ticketNumber);
        $('#supResolveForm').attr('action', '/supervisor/tickets/' + ticketId + '/resolve');
        $('#supResolveAttachments').val('');
        $('#supResolveAttachmentList').empty();
        $('#supResolveForm textarea[name="resolution_notes"], #supResolveForm textarea[name="findings"], #supResolveForm textarea[name="recommendation"]').val('');
        $('#supResolveForm input[name="service_type"][value="Onsite"]').prop('checked', true);

        clearInterval(supResolveTimeSpentTimer);
        const startedMs = startedAt ? new Date(startedAt).getTime() : null;
        const renderTimeSpent = () => {
            if (!startedMs) { $('#supResolveTimeSpent').text('—'); return; }
            const totalMinutes = Math.max(0, Math.floor((Date.now() - startedMs) / 60000));
            const h = Math.floor(totalMinutes / 60);
            const m = totalMinutes % 60;
            $('#supResolveTimeSpent').text(h > 0 ? `${h}h ${m}m` : `${m}m`);
        };
        renderTimeSpent();
        supResolveTimeSpentTimer = setInterval(renderTimeSpent, 1000);

        new bootstrap.Modal('#supResolveModal').show();
    };

    $('#supResolveModal').on('hidden.bs.modal', function () {
        clearInterval(supResolveTimeSpentTimer);
    });

    /* ── Resolve modal attachment picker: client-side limits + preview list ── */
    const SUP_RESOLVE_MAX_ATTACHMENTS = 5;
    const SUP_RESOLVE_MAX_ATTACHMENT_MB = 10;

    $('#supResolveAttachments').on('change', function () {
        const files = Array.from(this.files);
        const list  = $('#supResolveAttachmentList').empty();

        if (files.length > SUP_RESOLVE_MAX_ATTACHMENTS) {
            alert(`You can attach up to ${SUP_RESOLVE_MAX_ATTACHMENTS} files. Only the first ${SUP_RESOLVE_MAX_ATTACHMENTS} will be kept.`);
        }

        const oversize = files.find(f => f.size > SUP_RESOLVE_MAX_ATTACHMENT_MB * 1024 * 1024);
        if (oversize) {
            alert(`"${oversize.name}" exceeds the ${SUP_RESOLVE_MAX_ATTACHMENT_MB}MB limit and will be removed.`);
        }

        const kept = files
            .filter(f => f.size <= SUP_RESOLVE_MAX_ATTACHMENT_MB * 1024 * 1024)
            .slice(0, SUP_RESOLVE_MAX_ATTACHMENTS);

        const dt = new DataTransfer();
        kept.forEach(f => dt.items.add(f));
        this.files = dt.files;

        kept.forEach(f => {
            const sizeKb = (f.size / 1024).toFixed(0);
            list.append(
                `<div style="font-size:12px;color:var(--tm)"><i class="bi bi-paperclip me-1"></i>${$('<div>').text(f.name).html()} <span style="color:var(--tm)">(${sizeKb} KB)</span></div>`
            );
        });
    });

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

            const ackLink  = doc.querySelector("a[href*='status=awaiting-administrator-ack']");
            const ackCount = ackLink ? parseInt((ackLink.querySelector('.badge-count') || {}).textContent || '0', 10) : 0;
            document.title = ackCount > 0 ? `For IT Admin Acknowledgment (${ackCount}) — Supervisor Dashboard — LGICT` : 'Supervisor Dashboard — LGICT';
            setFaviconBadge(ackCount);

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

    setFaviconBadge({{ $counts['awaiting_administrator_ack'] }});
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

    window.openAdminRevisionModal = function (ticketId, ticketNumber) {
        $('#adminRevisionRef').text('#' + ticketNumber);
        $('#adminRevisionForm').attr('action', '/supervisor/tickets/' + ticketId + '/request-revision');
        $('#adminRevisionForm textarea[name="revision_notes"]').val('');
        new bootstrap.Modal('#adminRevisionModal').show();
    };

    window.openEscManagerModal = function (ticketId, ticketNumber) {
        $('#escManagerRef').text('#' + ticketNumber);
        $('#escManagerForm').attr('action', '/supervisor/tickets/' + ticketId + '/escalate-manager');
        new bootstrap.Modal('#escManagerModal').show();
    };

    window.openPauseModal = function (ticketId, ticketNumber) {
        $('#pauseRef').text('#' + ticketNumber);
        $('#pauseForm').attr('action', '/supervisor/tickets/' + ticketId + '/pause');
        new bootstrap.Modal('#pauseModal').show();
    };





    $(document).on('click', '#caSubList .cat-sub-opt', function () {
        $('#caSubList .cat-sub-opt').removeClass('selected');
        $(this).addClass('selected');
        $('#caSlaRuleId').val($(this).data('rule-id'));

        // Prefill the override fields with the rule's defaults — admin supervisor can still edit them.
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

    function updateClassifyAssignSubmitLabel() {
        $('#caSubmitBtnText').text(
            $('#caTakeOver').is(':checked') ? 'Confirm Classification & Take Over' : 'Confirm Classification & Assignment'
        );
    }

    $(document).on('change', '#caTakeOver', function () {
        const takingOver = $(this).is(':checked');
        $('#caAssignWrap').toggleClass('d-none', takingOver);
        if (takingOver) {
            $('#caTechList .tech-select-option').removeClass('selected');
            $('#caTechId').val('');
        }
        updateClassifyAssignSubmitLabel();
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
        if (!$('#caTakeOver').is(':checked') && !$('#caTechId').val()) {
            e.preventDefault();
            alert('Please assign an IT Admin, or check "Take over this ticket myself".');
            return;
        }

        const $wc = $('#caWorkloadClass').find(':selected');
        const wcManual = $wc.data('manual') === 1 || $wc.data('manual') === '1';
        if ($('#caWorkloadClass').val() && wcManual && !$('#caResolutionTime').val()) {
            e.preventDefault();
            alert(`The "${$wc.text().trim()}" workload class has no fixed resolution target — enter the agreed resolution time in minutes.`);
        }
    });



</script>
@endsection