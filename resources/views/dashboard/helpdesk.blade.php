@extends('layouts.app')

@section('title', ($counts['new_request'] > 0 ? 'For Acknowledgment (' . $counts['new_request'] . ') — ' : '') . 'Helpdesk Dashboard — LGICT')

@section('nav-role-badge')
    <span class="role-badge"><i class="bi bi-headset me-1"></i>Helpdesk</span>
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
    <h1>HELPDESK <em>DASHBOARD</em></h1>
@endsection
@section('hero-subtitle', 'Acknowledge, assign, and track all incoming support tickets.')

@section('hero-stats')
    <div class="d-flex gap-2 flex-wrap">
        <div class="stat-pill danger">
            <span class="num">{{ $counts['new_request'] }}</span>
            <span class="lbl">For Acknowledgment</span>
        </div>
        <div class="stat-pill warn">
            <span class="num">{{ $counts['for_classification'] }}</span>
            <span class="lbl">For Classification</span>
        </div>
        <div class="stat-pill warn">
            <span class="num">{{ $counts['awaiting_supervisor'] }}</span>
            <span class="lbl">Supervisor Assignment</span>
        </div>
        <div class="stat-pill warn">
            <span class="num">{{ $counts['in_progress'] }}</span>
            <span class="lbl">In Progress Service Request</span>
        </div>
        <div class="stat-pill">
            <span class="num">{{ $counts['escalated'] }}</span>
            <span class="lbl">Escalated</span>
        </div>
        <div class="stat-pill">
            <span class="num">{{ $counts['pending_supervisor_approval'] }}</span>
            <span class="lbl">Report For Review</span>
        </div>
        <div class="stat-pill">
            <span class="num">{{ $counts['closed'] }}</span>
            <span class="lbl">Closed</span>
        </div>
    </div>
@endsection

@section('hero-cta')
    <button class="btn-new" data-bs-toggle="modal" data-bs-target="#ticketModal">
        <i class="bi bi-plus-lg me-1"></i> New Request
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

    /* ── Pending-closure attention banner ── */
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

   /* ── Modal: step wizard ── */
/* ── Compact Step Wizard ── */
.step-ind{
    display:flex;
    align-items:center;
    justify-content:center;
    max-width:420px;
    margin:0 auto 1.25rem;
}

.step-item{
    display:flex;
    align-items:center;
    gap:8px;
    flex:0 0 auto;
}

.step-num{
    width:28px;
    height:28px;
    border-radius:50%;

    display:flex;
    align-items:center;
    justify-content:center;

    font-size:14px;
    font-weight:800;

    background:var(--bd);
    color:var(--tm);

    transition:.25s;
}

.step-item.active .step-num{
    background:var(--gd);
    color:var(--yg);
}

.step-item.done .step-num{
    background:var(--yg);
    color:var(--gd);
}

.step-lbl{
    font-size:12px;
    font-weight:700;
    color:var(--tm);
}

.step-item.active .step-lbl{
    color:var(--gd);
}

.step-line{
    width:110px;
    height:2px;
    background:var(--bd);
    border-radius:999px;
    margin:0 14px;
}

.step-line.done{
    background:var(--yg);
}
    
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
    .btn-service-report { background:#e8f5ee; color:#1a5a3a; font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; padding:6px 14px; border-radius:20px; border:1.5px solid #a8ddc0; cursor:pointer; transition:all .2s; }
    .btn-service-report:hover { background:#c8ead8; }
    .hd-status-opts { display:flex; flex-direction:column; gap:8px; }
    .hd-status-opt { border:1.5px solid var(--bd); border-radius:12px; padding:12px 16px; cursor:pointer; transition:all .2s; background:var(--cr); display:flex; align-items:center; gap:12px; }
    .hd-status-opt:hover { border-color:var(--gl); background:var(--ygl); }
    .hd-status-opt.selected { border-color:var(--gd); background:var(--ygl); box-shadow:0 0 0 2px var(--yg); }
    .hd-status-opt .so-dot   { width:12px; height:12px; border-radius:50%; flex-shrink:0; }
    .hd-status-opt .so-label { font-weight:800; font-size:14px; font-family:'Nunito',sans-serif; }
    .hd-status-opt .so-desc  { font-size:12px; color:var(--tm); }
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
    .btn-view-details { background:none; color:var(--tm); font-family:'Nunito',sans-serif; font-weight:700; font-size:12px; padding:6px 14px; border-radius:20px; border:1.5px solid var(--bd); cursor:pointer; transition:all .2s; text-decoration:none; display:inline-flex; align-items:center; gap:5px; }
    .btn-view-details:hover { border-color:var(--gl); color:var(--gd); }
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

    .pagination {
        flex-wrap: wrap;
        justify-content: center;
        gap: 6px;
    }

    .pagination li {
        margin: 2px;
    }

    .pagination .page-link {
        border-radius: 8px !important;
        padding: 6px 12px;
        font-size: 13px;
    }
    @media (max-width: 768px) {
        .pagination {
            font-size: 12px;
        }

        .pagination .page-link {
            padding: 4px 8px;
        }
    }

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
                <a href="{{ route('helpdesk.dashboard', ['status' => 'active']) }}"
                   class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-grid-fill me-2"></i>Active</span>
                    <span class="badge-count">{{ $counts['active'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'new-request' ? 'active' : '' }} {{ $counts['new_request'] > 0 ? 'queue-glow' : '' }}">
                <a href="{{ route('helpdesk.dashboard', ['status' => 'new-request']) }}"
                   class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-grid me-2"></i>New Requests</span>
                    <span class="badge-count">{{ $counts['new_request'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'for-classification' ? 'active' : '' }}">
                <a href="{{ route('helpdesk.dashboard', ['status' => 'for-classification']) }}"
                   class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-tags me-2"></i>For Classification</span>
                    <span class="badge-count">{{ $counts['for_classification'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'awaiting-supervisor' ? 'active' : '' }}">
                <a href="{{ route('helpdesk.dashboard', ['status' => 'awaiting-supervisor']) }}"
                   class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-inbox me-2"></i>Supervisor Assignment</span>
                    <span class="badge-count">{{ $counts['awaiting_supervisor'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'in-progress' ? 'active' : '' }}">
                <a href="{{ route('helpdesk.dashboard', ['status' => 'in-progress']) }}"
                   class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-headset me-2"></i>In Progress Service Request</span>
                    <span class="badge-count">{{ $counts['in_progress'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'l1-preparing-report' ? 'active' : '' }}">
                <a href="{{ route('helpdesk.dashboard', ['status' => 'l1-preparing-report']) }}"
                   class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-file-earmark-text me-2"></i>In Progress Service Report</span>
                    <span class="badge-count">{{ $counts['l1_preparing_report'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'escalated' ? 'active' : '' }}">
                <a href="{{ route('helpdesk.dashboard', ['status' => 'escalated']) }}"
                   class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-exclamation-triangle me-2"></i>Escalated</span>
                    <span class="badge-count">{{ $counts['escalated'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'pending-supervisor-approval' ? 'active' : '' }}">
                <a href="{{ route('helpdesk.dashboard', ['status' => 'pending-supervisor-approval']) }}"
                   class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-clock-history me-2"></i>Report For Review</span>
                    <span class="badge-count">{{ $counts['pending_supervisor_approval'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'awaiting-requestor' ? 'active' : '' }}">
                <a href="{{ route('helpdesk.dashboard', ['status' => 'awaiting-requestor']) }}"
                   class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-exclamation-triangle me-2"></i>Requestor Confirmation</span>
                    <span class="badge-count">{{ $counts['awaiting_requestor'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'closed' ? 'active' : '' }}">
                <a href="{{ route('helpdesk.dashboard', ['status' => 'closed']) }}"
                   class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-check-circle me-2"></i>Closed</span>
                    <span class="badge-count">{{ $counts['closed'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'cancelled' ? 'active' : '' }}">
                <a href="{{ route('helpdesk.dashboard', ['status' => 'cancelled']) }}"
                   class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-x-circle me-2"></i>Cancelled</span>
                    <span class="badge-count">{{ $counts['cancelled'] }}</span>
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

    {{-- IT Admin + Supervisor availability — based on how many tickets each
         currently has open (no real time-slot scheduling on this track yet),
         unlike the free-time label above for IT Support Specialists. --}}
    <div class="sidebar-card mt-3">
        <div class="sidebar-head">IT Admin Availability</div>
        <div>
            @forelse($itAdmins as $admin)
                @php
                    $adminInitials = strtoupper(substr($admin->name, 0, 1)) .
                                strtoupper(substr($admin->name, strpos($admin->name, ' ') + 1, 1));
                @endphp
                <div class="tech-row">
                    <div class="tech-av-lg">{{ $adminInitials }}</div>
                    <div>
                        <div class="tech-name">{{ $admin->name }}</div>
                        <div class="tech-load">{{ $admin->role?->role_name }} — {{ $admin->active_tickets }} open ticket{{ $admin->active_tickets === 1 ? '' : 's' }}</div>
                    </div>
                    <div class="avail-dot {{ $admin->availability }}"
                         title="{{ ucfirst($admin->availability) }}"></div>
                </div>
            @empty
                <div class="p-3" style="font-size:13px;color:var(--tm)">
                    No IT Admins found.
                </div>
            @endforelse
        </div>
        <div class="p-2 px-3" style="font-size:11px;color:var(--tm);border-top:1px solid var(--bd)">
            <span class="me-3"><span class="avail-dot free d-inline-block me-1"></span>Free</span>
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
                // Every one of these tabs shares the same underlying 12-status
                // value with at least one other tab now (see App\Support\TicketStatus)
                // — the labels below are deliberately distinct per tab (what makes
                // THIS bucket different: who's investigating, who it's with) rather
                // than the literal status string, so the tab bar doesn't show the
                // same label five times over.
                $labels = [
                    'active' => 'Active',
                    'new-request' => 'For Acknowledgment', 'for-classification' => 'For Classification', 'awaiting-supervisor' => 'Supervisor Assignment',
                    'in-progress' => 'In Progress Service Request', 'l1-preparing-report' => 'In Progress Service Report', 'escalated' => 'Escalated', 'pending-supervisor-approval' => 'Report For Review',
                    'awaiting-requestor' => 'Requestor Confirmation',
                    'closed' => 'Closed', 'cancelled' => 'Cancelled',
                ];
            @endphp
            {{ $labels[$status] ?? 'All Support Requests' }}
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
                'active'              => ['label' => 'Active',              'count' => $counts['active']],
                'new-request'         => ['label' => 'For Acknowledgment',         'count' => $counts['new_request']],
                'for-classification'  => ['label' => 'For Classification',  'count' => $counts['for_classification']],
                'awaiting-supervisor'  => ['label' => 'Supervisor Assignment',  'count' => $counts['awaiting_supervisor']],
                'in-progress' => ['label' => 'In Progress Service Request', 'count' => $counts['in_progress']],
                'l1-preparing-report' => ['label' => 'In Progress Service Report', 'count' => $counts['l1_preparing_report']],
                'escalated'   => ['label' => 'Escalated',   'count' => $counts['escalated']],
                'pending-supervisor-approval' => ['label' => 'Report For Review', 'count' => $counts['pending_supervisor_approval']],
                'awaiting-requestor'   => ['label' => 'Requestor Confirmation',   'count' => $counts['awaiting_requestor']],
                'closed'    => ['label' => 'Closed',    'count' => $counts['closed']],
                'cancelled' => ['label' => 'Cancelled', 'count' => $counts['cancelled']],
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
                $needsAck      = is_null($ticket->date_acknowledged) && $ticket->status === 'For Acknowledgment';
                // Classify is the only action after acknowledging — no separate
                // "Start L1" claim/investigate step (removed: it never changed
                // what happened next, Classify was always still required either
                // way, so it was just a confusing extra button). acknowledge()
                // moves status to For Classification (Helpdesk-only), so that's
                // what gates Classify now instead of For Acknowledgment.
                $needsClassify = $ticket->status === 'For Classification';
                $canCancel     = in_array($ticket->status, ['For Acknowledgment', 'For Classification'], true);
                // Classified & kept for L1 self-resolve via classify()'s "handle_myself"
                // option. Which track a ticket belongs to is now disambiguated via
                // assignedTo.role rather than a dedicated status string (see
                // App\Support\TicketStatus), so the two tracks are told apart by
                // assigned_to, not by the status value itself.
                // ── Isolated into two deliberate actions (see TicketReportProgress):
                //    mark the fix done first, then separately prepare & submit the
                //    service report — not one combined submit.
                $canMarkFixed     = $ticket->status === 'In Progress Service Request' && $ticket->assigned_to === Auth::id();
                $canPrepareReport = $ticket->status === 'In Progress Service Report' && $ticket->assigned_to === Auth::id();
                $canResolveMyself = $canMarkFixed || $canPrepareReport;

                // ── Draft "Service Details / Action Taken" from this agent's own Add
                //    Update log — same convention as Technician\TicketController::update():
                //    old_status === new_status marks a progress-update entry (vs. the
                //    classify()/startReport() transitions into 'In Progress Service Request' or
                //    'In Progress Service Report').
                $progressDraft = $canResolveMyself
                    ? $ticket->statusHistories
                        ->filter(fn($h) => $h->old_status === $h->new_status
                            && in_array($h->old_status, ['In Progress Service Request', 'In Progress Service Report'], true))
                        ->sortBy('changed_at')
                        ->map(fn($h) => '- ' . \Carbon\Carbon::parse($h->changed_at)->timezone('Asia/Manila')->format('M d, g:i A') . ': ' . $h->notes)
                        ->implode("\n")
                    : '';

                // 'For Acknowledgment' folded into 'For Acknowledgment'; 'Closed Service
                // Request' is a transient pass-through en route to drafting the report
                // (see startReport() cascade) so it reads as in-progress here; 'Done Service
                // Report'/'Report For Review'/'Approved Service Report' all read as
                // "with the supervisor for review/approval" from Helpdesk's point of view.
                $cardClass = match(true) {
                    $needsAck                                     => 'unassigned',
                    $ticket->status === 'For Acknowledgment'      => 'new-request',
                    $ticket->status === 'For Classification'       => 'new-request',
                    $ticket->status === 'Classified'               => 'new-request',
                    $ticket->status === 'Assigned'                 => 'new-request',
                    $ticket->status === 'In Progress Service Request' => 'in-progress',
                    $ticket->status === 'Closed Service Request'  => 'in-progress',
                    $ticket->status === 'In Progress Service Report'  => 'in-progress',
                    $ticket->status === 'Escalated'   => 'escalated',
                    $ticket->status === 'Done Service Report'      => 'pending-supervisor-approval',
                    $ticket->status === 'Report For Review'        => 'pending-supervisor-approval',
                    $ticket->status === 'Approved Service Report'  => 'pending-supervisor-approval',
                    $ticket->status === 'Requestor Confirmation'   => 'awaiting-requestor',
                    $ticket->status === 'Closed'   => 'closed',
                    $ticket->status === 'Cancelled'   => 'closed',
                    default                           => 'new-request'
                };
                $badgeClass = match(true) {
                    $needsAck                                     => 'badge-unassigned',
                    $ticket->status === 'For Acknowledgment'      => 'badge-new-request',
                    $ticket->status === 'For Classification'       => 'badge-new-request',
                    $ticket->status === 'Classified'               => 'badge-new-request',
                    $ticket->status === 'Assigned'                 => 'badge-new-request',
                    $ticket->status === 'In Progress Service Request' => 'badge-in-progress',
                    $ticket->status === 'Closed Service Request'  => 'badge-in-progress',
                    $ticket->status === 'In Progress Service Report'  => 'badge-in-progress',
                    $ticket->status === 'Escalated'   => 'badge-escalated',
                    $ticket->status === 'Done Service Report'      => 'badge-pending-supervisor-approval',
                    $ticket->status === 'Report For Review'        => 'badge-pending-supervisor-approval',
                    $ticket->status === 'Approved Service Report'  => 'badge-pending-supervisor-approval',
                    $ticket->status === 'Requestor Confirmation'   => 'badge-awaiting-requestor',
                    $ticket->status === 'Closed'   => 'badge-closed',
                    $ticket->status === 'Cancelled'   => 'badge-closed',
                    default                           => 'badge-new-request'
                };
                $badgeLabel = match(true) {
                    $needsAck                                => '<i class="bi bi-inbox me-1"></i>Unassigned',
                    $ticket->status === 'For Acknowledgment'  => '<i class="bi bi-plus-circle me-1"></i>For Acknowledgment',
                    $ticket->status === 'For Classification'  => '<i class="bi bi-tags me-1"></i>For Classification',
                    $ticket->status === 'Classified'           => '<i class="bi bi-tags me-1"></i>Classified',
                    $ticket->status === 'Assigned'             => '<i class="bi bi-person-check me-1"></i>Assigned',
                    // In Progress Service Report is isolated from the underlying "actively
                    // fixing it" statuses (see TicketReportProgress) — same in-progress
                    // bucket/tab, different badge text.
                    $ticket->status === 'In Progress Service Report'
                        => '<i class="bi bi-file-earmark-text me-1"></i>Preparing Report',
                    $ticket->status === 'In Progress Service Request' => '<i class="bi bi-gear-fill me-1"></i>In Progress',
                    $ticket->status === 'Closed Service Request' => '<i class="bi bi-gear-fill me-1"></i>In Progress',
                    $ticket->status === 'Escalated'   => '<i class="bi bi-exclamation-triangle-fill me-1"></i>Escalated',
                    $ticket->status === 'Done Service Report'      => '<i class="bi bi-clock-history me-1"></i>Done Service Report',
                    $ticket->status === 'Report For Review'        => '<i class="bi bi-clock-history me-1"></i>Report For Review',
                    $ticket->status === 'Approved Service Report'  => '<i class="bi bi-clock-history me-1"></i>Approved Service Report',
                    $ticket->status === 'Requestor Confirmation'   => '<i class="bi bi-person-check me-1"></i>Requestor Confirmation',
                    $ticket->status === 'Closed'    => '<i class="bi bi-check-circle-fill me-1"></i>Closed',
                    $ticket->status === 'Cancelled'    => '<i class="bi bi-x-circle-fill me-1"></i>Cancelled',
                    default                           => '● For Acknowledgment'
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
                                        {{ \Carbon\Carbon::parse($history->changed_at)->timezone('Asia/Manila')->format('M d, g:i A') }}
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
                    {{-- ── SLA Status indicator — the resolution clock is still running
                         while drafting the report, it only stops at Done Service Report
                         (see TicketReportProgress). ── --}}
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

                    <a href="{{ route('helpdesk.tickets.show', $ticket) }}" class="btn-view-details">
                        <i class="bi bi-eye me-1"></i>View Support Request Details
                    </a>

                    {{-- Not yet acknowledged --}}
                    @if($needsAck)
                        <form method="POST" action="{{ route('helpdesk.tickets.acknowledge', $ticket) }}">
                            @csrf
                            <button type="submit" class="btn-acknowledge">
                                <i class="bi bi-eye me-1"></i>Acknowledge
                            </button>
                        </form>
                    @endif

                    {{-- Acknowledged --}}
                    @if($needsClassify)
                        <button type="button" class="btn-assign"
                                onclick="openClassifyModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-tags me-1"></i>Classify
                        </button>
                    @endif

                    {{-- Classified and kept for L1 self-resolve — see classify()'s
                         "handle_myself" option. --}}
                    @if($canResolveMyself)
                        <button type="button" class="btn-acknowledge"
                                onclick="openHelpdeskUpdateModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-pencil me-1"></i>Add Update
                        </button>
                    @endif
                    {{-- Fix is done — mark it so, separate from writing up the report. --}}
                    @if($canMarkFixed)
                        <form method="POST" action="{{ route('helpdesk.tickets.start-report', $ticket) }}">
                            @csrf
                            <button type="submit" class="btn-resolve">
                                <i class="bi bi-check2 me-1"></i>Mark Fixed
                            </button>
                        </form>
                    @endif
                    {{-- Fix already marked done — now prepare & submit the service report. --}}
                    @if($canPrepareReport)
                        <button type="button" class="btn-resolve"
                                data-progress-draft="{{ $progressDraft }}"
                                onclick="openHelpdeskResolveModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}', this.dataset.progressDraft)">
                            <i class="bi bi-file-earmark-text me-1"></i>Prepare Service Report
                        </button>
                    @endif

                    {{-- Cancel: available any time before classification --}}
                    @if($canCancel)
                        <button type="button" class="btn-escalate"
                                onclick="openCancelModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </button>
                    @endif

                    @if($needsAck || $needsClassify || $canResolveMyself)
                        {{-- ── Chat button ── --}}
                        <button class="btn-chat"
                                onclick="openChatModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}', {{ Illuminate\Support\Js::from($ticket->user->name ?? 'Unknown') }}, {{ Illuminate\Support\Js::from($ticket->subject) }}, {{ Illuminate\Support\Js::from($ticket->concern) }})">
                            <i class="bi bi-chat-dots me-1"></i>Message
                            @php $unread = $ticket->unreadMessages()->count(); @endphp
                            @if($unread > 0)
                                <span class="chat-count-badge" id="badge-{{ $ticket->id }}">{{ $unread }}</span>
                            @endif
                        </button>
                    @endif

                    {{-- 'For Acknowledgment' folded into 'For Acknowledgment' — its Message
                         button is now already covered by the combined queue-stage block above
                         ($needsAck / $needsClassify), so the dedicated branch that used to
                         live here is now redundant and has been removed. --}}
                    {{-- In Progress Service Request / Closed Service Request / In Progress
                         Service Report, not self-assigned to this Helpdesk agent (self-assigned
                         tickets already get their Message button above, alongside Add Update /
                         Resolve — see $canResolveMyself). --}}
                    @if(in_array($ticket->status, ['In Progress Service Request', 'Closed Service Request', 'In Progress Service Report']) && $ticket->assigned_to !== Auth::id())
                        {{-- ── Chat button ── --}}
                        <button class="btn-chat"
                                onclick="openChatModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}', {{ Illuminate\Support\Js::from($ticket->user->name ?? 'Unknown') }}, {{ Illuminate\Support\Js::from($ticket->subject) }}, {{ Illuminate\Support\Js::from($ticket->concern) }})">
                            <i class="bi bi-chat-dots me-1"></i>Message
                            @php $unread = $ticket->unreadMessages()->count(); @endphp
                            @if($unread > 0)
                                <span class="chat-count-badge" id="badge-{{ $ticket->id }}">{{ $unread }}</span>
                            @endif
                        </button>
                    @endif

                    {{-- Escalated: Message --}}
                    @if($ticket->status === 'Escalated')
                        {{-- ── Chat button ── --}}
                        <button class="btn-chat"
                                onclick="openChatModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}', {{ Illuminate\Support\Js::from($ticket->user->name ?? 'Unknown') }}, {{ Illuminate\Support\Js::from($ticket->subject) }}, {{ Illuminate\Support\Js::from($ticket->concern) }})">
                            <i class="bi bi-chat-dots me-1"></i>Message
                            @php $unread = $ticket->unreadMessages()->count(); @endphp
                            @if($unread > 0)
                                <span class="chat-count-badge" id="badge-{{ $ticket->id }}">{{ $unread }}</span>
                            @endif
                        </button>
                    @endif

                    {{-- Report For Review: Message. Done Service Report is a transient
                         pass-through status (cascades straight to Report For Review, see
                         resolve()) so the actual resting "with the supervisor" state to key
                         off is Report For Review. --}}
                    @if($ticket->status === 'Report For Review')
                        <button class="btn-chat"
                                onclick="openChatModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}', {{ Illuminate\Support\Js::from($ticket->user->name ?? 'Unknown') }}, {{ Illuminate\Support\Js::from($ticket->subject) }}, {{ Illuminate\Support\Js::from($ticket->concern) }})">
                            <i class="bi bi-chat-dots me-1"></i>Message
                            @php $unread = $ticket->unreadMessages()->count(); @endphp
                            @if($unread > 0)
                                <span class="chat-count-badge" id="badge-{{ $ticket->id }}">{{ $unread }}</span>
                            @endif
                        </button>
                    @endif

                    @if($ticket->status === 'Requestor Confirmation')
                        <button class="btn-chat"
                                onclick="openChatModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}', {{ Illuminate\Support\Js::from($ticket->user->name ?? 'Unknown') }}, {{ Illuminate\Support\Js::from($ticket->subject) }}, {{ Illuminate\Support\Js::from($ticket->concern) }})">
                            <i class="bi bi-chat-dots me-1"></i>Message
                            @php $unread = $ticket->unreadMessages()->count(); @endphp
                            @if($unread > 0)
                                <span class="chat-count-badge" id="badge-{{ $ticket->id }}">{{ $unread }}</span>
                            @endif
                        </button>
                    @endif

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
                                <span class="step-lbl">Details</span>
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
                                        ['value' => 'Microsoft Teams Chat', 'icon' => 'bi-microsoft-teams',   'color' => '#6264a7', 'bg' => '#e8e9f5'],
                                        ['value' => 'Microsoft Teams Call', 'icon' => 'bi-camera-video-fill', 'color' => '#6264a7', 'bg' => '#e8e9f5'],
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
                                <label class="form-label">Additional details</label>
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
    {{-- Acknowledge & Classify modal --}}
    <div class="modal fade" id="classifyModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header-gd d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Classify — <em id="acTicketRef">#TKT-0000</em></h5>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <form method="POST" id="classifyForm">
                    @csrf
                    <input type="hidden" name="sla_rule_id" id="acSlaRuleId">

                    <div class="modal-body px-4 py-4">
                        <div class="mb-3 p-2 px-3 rounded"
                             style="background:var(--ygl);font-size:13px;color:var(--gd)">
                            Pick the category that best matches the requester's concern. The Supervisor
                            can still review and change this before assigning a technician.
                        </div>

                        <label class="form-label mb-2">Category</label>
                        <div class="d-flex flex-wrap gap-2 mb-3" id="acCategoryList"></div>

                        <div id="acSubWrap" class="d-none">
                            <label class="form-label mb-2">Subcategory & Priority</label>
                            <div class="d-flex flex-column gap-2 mb-3" id="acSubList"></div>
                        </div>

                        <div id="acOverrideWrap" class="d-none mb-3">
                            <label class="form-label mb-2">
                                SLA & Priority
                                <span style="font-weight:400;color:var(--tm)">(defaults from the rule above — adjust if needed)</span>
                            </label>

                            <div class="mb-2">
                                <label class="form-label" style="font-size:11px">
                                    Workload Class
                                    <span style="font-weight:400;color:var(--tm)">(optional — overrides response/resolution time below)</span>
                                </label>
                                <select class="form-select form-select-sm" name="workload_class_id" id="acWorkloadClass">
                                    <option value="">— None —</option>
                                    @foreach($workloadClasses as $wc)
                                        <option value="{{ $wc->id }}"
                                                data-response="{{ $wc->response_minutes }}"
                                                data-resolution="{{ $wc->resolution_minutes }}"
                                                data-manual="{{ $wc->requires_manual_resolution ? '1' : '0' }}">
                                            {{ $wc->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div id="acWorkloadManualHint" class="d-none" style="font-size:11px;color:var(--tm);font-weight:600;margin-top:4px">
                                    <i class="bi bi-info-circle me-1"></i>This class has no fixed resolution target — enter the agreed resolution time below.
                                </div>
                            </div>

                            <div class="d-flex gap-2 flex-wrap">
                                <div style="flex:1;min-width:110px">
                                    <label class="form-label" style="font-size:11px">Priority</label>
                                    <select class="form-select form-select-sm" name="priority" id="acPriority">
                                        <option value="Critical">Critical</option>
                                        <option value="High">High</option>
                                        <option value="Medium">Medium</option>
                                        <option value="Low">Low</option>
                                    </select>
                                </div>
                                <div style="flex:1;min-width:130px">
                                    <label class="form-label" style="font-size:11px">Response Time (min)</label>
                                    <input type="number" class="form-control form-control-sm" name="response_time_minutes"
                                           id="acResponseTime" min="5" max="43200">
                                </div>
                                <div style="flex:1;min-width:130px">
                                    <label class="form-label" style="font-size:11px">Resolution Time (min)</label>
                                    <input type="number" class="form-control form-control-sm" name="resolution_time_minutes"
                                           id="acResolutionTime" min="5" max="43200">
                                </div>
                            </div>
                        </div>

                        <div id="acHandleMyselfWrap" class="d-none mb-3 p-3 rounded"
                             style="background:#e8f5ee;border:1px solid #a8ddc0">
                            <label class="d-flex align-items-center gap-2" style="cursor:pointer;font-weight:700;color:#1a5a3a">
                                <input type="checkbox" name="handle_myself" id="acHandleMyself" value="1"
                                       style="width:16px;height:16px;cursor:pointer">
                                Handle this myself (L1)
                            </label>
                            <div style="font-size:11.5px;color:#1a5a3a;margin-top:4px">
                                This subcategory is marked as Helpdesk-resolvable. Checking this keeps the
                                support request with you — it goes straight to In Progress instead of the Supervisor's
                                assignment queue.
                            </div>
                        </div>

                        <div id="acAdminOnlyWrap" class="d-none mb-3 p-3 rounded"
                             style="background:#eef0ff;border:1px solid #b8bcf0">
                            <div style="font-weight:700;color:#2a2a8a">
                                <i class="bi bi-shield-lock me-1"></i>L3-only subcategory
                            </div>
                            <div style="font-size:11.5px;color:#2a2a8a;margin-top:4px">
                                This subcategory is marked Admin-only. This ticket will go straight to the
                                Supervisor - IT Admin queue for classification & assignment — it will not pass
                                through the Support Supervisor.
                            </div>
                        </div>

                        <div class="mt-3" id="acNotesWrap">
                            <label class="form-label">Notes (optional)</label>
                            <textarea class="form-control" name="notes" rows="2"
                                      placeholder="Context for the Supervisor…"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
                        <button type="button" class="btn-cancel-modal" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-confirm">
                            <i class="bi bi-check-lg me-1"></i><span id="acSubmitBtnText">Suggest Classification for Supervisor</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Helpdesk Add Update modal (L1 self-resolve — progress log while In Progress) --}}
    <div class="modal fade" id="helpdeskUpdateModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header-gd d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Add <em>Update</em></h5>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <form method="POST" id="helpdeskUpdateForm" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body px-4 py-4">
                        <p style="font-size:13px;color:var(--tm)" class="mb-3">
                            Ticket <strong id="hdUpdateRef" style="color:var(--gd)"></strong> —
                            Log your progress below.
                        </p>
                        <div class="mb-3">
                            <label class="form-label">What have you done so far?</label>
                            <textarea class="form-control" name="progress_notes" rows="3"
                                      required
                                      placeholder="Describe the steps you've taken, findings, or current status…"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Current work status</label>
                            <div class="hd-status-opts">
                                <div class="hd-status-opt selected" data-val="Investigating">
                                    <span class="so-dot" style="background:#f5c842"></span>
                                    <div>
                                        <div class="so-label">Investigating</div>
                                        <div class="so-desc">Still diagnosing the root cause</div>
                                    </div>
                                </div>
                                <div class="hd-status-opt" data-val="Actively working">
                                    <span class="so-dot" style="background:var(--yg)"></span>
                                    <div>
                                        <div class="so-label">Actively working</div>
                                        <div class="so-desc">Fix is underway</div>
                                    </div>
                                </div>
                                <div class="hd-status-opt" data-val="Waiting for parts or access">
                                    <span class="so-dot" style="background:#d85a30"></span>
                                    <div>
                                        <div class="so-label">Waiting for parts / access</div>
                                        <div class="so-desc">Blocked, pending external resource</div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="work_status" id="hdWorkStatusVal"
                                   value="Investigating">
                        </div>
                        <div>
                            <label class="form-label">Evidence files <span style="font-weight:400;color:var(--tm)">(optional)</span></label>
                            <input type="file" class="form-control" id="hdUAttachments" name="attachments[]"
                                   multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.txt">
                            <div style="font-size:11px;color:var(--tm);margin-top:4px">
                                Up to 5 files, 10MB each. Screenshots or logs showing progress so far.
                            </div>
                            <div id="hdUpdateAttachmentList" class="d-flex flex-column gap-1 mt-2"></div>
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

    {{-- Helpdesk Resolve modal (L1 quick fix — resolved without escalation) --}}
    <div class="modal fade" id="helpdeskResolveModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header-gd d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Resolve — <em id="hdResolveRef">#TKT-0000</em></h5>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <form method="POST" id="helpdeskResolveForm" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body px-4 py-4">
                        <div class="info-box-green p-3 mb-3">
                            <i class="bi bi-check-circle me-1"></i>
                            Resolving this support request directly — this ends L1 handling and
                            sends it to your Supervisor for approval. It will move to
                            <strong>Closed</strong> once fully approved.
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Service Type <span class="text-danger">*</span></label>
                            <div class="d-flex gap-3 flex-wrap" style="font-size:13px;font-weight:600">
                                <label class="d-flex align-items-center gap-1" style="cursor:pointer">
                                    <input type="radio" name="service_type" value="Onsite">Onsite
                                </label>
                                <label class="d-flex align-items-center gap-1" style="cursor:pointer">
                                    <input type="radio" name="service_type" value="Remote" checked>Remote
                                </label>
                                <label class="d-flex align-items-center gap-1" style="cursor:pointer">
                                    <input type="radio" name="service_type" value="Preventive">Preventive
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Service Details / Action Taken <span class="text-danger">*</span>
                                <span id="hdResolveDraftHint" class="d-none" style="font-weight:400;color:var(--tm)">
                                    — pre-filled from your Add Update log, edit as needed
                                </span>
                            </label>
                            <textarea class="form-control" name="resolution_notes" rows="3" required
                                      placeholder="Describe exactly what was done to resolve the issue…"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Findings &amp; Analysis <span style="font-weight:400;color:var(--tm)">(optional)</span></label>
                            <textarea class="form-control" name="findings" rows="2"
                                      placeholder="Root cause, diagnostics, what was found…"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Other Observation / Recommendation <span style="font-weight:400;color:var(--tm)">(optional)</span></label>
                            <textarea class="form-control" name="recommendation" rows="2"
                                      placeholder="Follow-up suggestions, preventive advice…"></textarea>
                        </div>

                        <div>
                            <label class="form-label">Supporting files <span style="font-weight:400;color:var(--tm)">(optional)</span></label>
                            <input type="file" class="form-control" id="hdRAttachments" name="attachments[]"
                                   multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.txt">
                            <div style="font-size:11px;color:var(--tm);margin-top:4px">
                                Up to 5 files, 10MB each. Screenshots, logs, or documents that support the resolution.
                            </div>
                            <div id="hdResolveAttachmentList" class="d-flex flex-column gap-1 mt-2"></div>
                        </div>
                    </div>
                    <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
                        <button type="button" class="btn-cancel-modal" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-confirm">
                            <i class="bi bi-check-circle me-1"></i>Confirm Resolution
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Cancel Ticket modal --}}
    <div class="modal fade" id="cancelTicketModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header-gd d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Cancel Support Request — <em id="cancelTicketRef">#TKT-0000</em></h5>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <form method="POST" id="cancelTicketForm">
                    @csrf
                    <div class="modal-body px-4 py-4">
                        <div class="esc-banner p-2 mb-3">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            This will cancel the support request before classification. This cannot be undone from here.
                        </div>
                        <div>
                            <label class="form-label">
                                Reason for cancellation <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" name="reason" rows="3" required
                                      placeholder="e.g. duplicate request, spam, resolved by employee directly…"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
                        <button type="button" class="btn-cancel-modal" data-bs-dismiss="modal">Back</button>
                        <button type="submit" class="btn-confirm" style="background:#8b1a1a">
                            <i class="bi bi-x-circle me-1"></i>Confirm Cancellation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Assign / Reassign modal --}}
    {{-- <div class="modal fade" id="assignModal" tabindex="-1">
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
                                                {{ $tech->active_tickets }} active support request{{ $tech->active_tickets !== 1 ? 's' : '' }}
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
    </div> --}}

    {{-- Escalate modal --}}
    {{-- <div class="modal fade" id="escalateModal" tabindex="-1">
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
    </div> --}}

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
                <div style="padding:10px 16px;background:var(--ygl);border-bottom:1.5px solid var(--bd);font-size:12px">
                    <div style="font-weight:800;color:var(--gd)">
                        <i class="bi bi-person-fill me-1"></i><span id="chatRequestor">—</span>
                    </div>
                    <div style="font-weight:700;color:var(--tm);margin-top:2px" id="chatSubject"></div>
                    <div style="color:var(--tm);margin-top:2px;max-height:54px;overflow-y:auto" id="chatConcern"></div>
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

window.openChatModal = function (ticketId, ticketNumber, requestorName, subject, concern) {
    currentChatTicketId = ticketId;
    $('#badge-' + ticketId).remove();
    $('#chatTicketRef').text('#' + ticketNumber);
    $('#chatRequestor').text(requestorName || '—');
    $('#chatSubject').text(subject || '');
    $('#chatConcern').text(concern || '');
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
    // $(document).on('click', '.cat-main-opt', function () {
    //     $('.cat-main-opt').removeClass('selected');
    //     $(this).addClass('selected');

    //     const catName = $(this).data('cat');
    //     const subs    = subCategoryMap[catName] || [];
    //     const $list   = $('#subCategoryList').empty();

    //     if (subs.length === 0) {
    //         $list.append(`
    //             <div style="font-size:12px;color:var(--tm);font-weight:600;padding:8px 12px;background:var(--ygl);border-radius:8px">
    //                 <i class="bi bi-info-circle me-1"></i>
    //                 No subcategories defined yet for this category. Contact IT Admin.
    //             </div>`);
    //     } else {
    //         subs.forEach(sub => {
    //             const priColor = sub.priority === 'High' ? '#e24b4a' : (sub.priority === 'Medium' ? '#f5c842' : '#4a7c4a');
    //             const priBg    = sub.priority === 'High' ? '#fde8e8' : (sub.priority === 'Medium' ? '#fff4cc' : '#d4f0d4');
    //             $list.append(`
    //                 <div class="cat-sub-opt" data-sub="${sub.name}" data-priority="${sub.priority}">
    //                     <div class="sub-check"></div>
    //                     <span style="flex:1">${sub.name}</span>
    //                     <span style="font-size:10px;font-weight:800;background:${priBg};color:${priColor};border-radius:20px;padding:2px 8px;flex-shrink:0">${sub.priority}</span>
    //                 </div>`);
    //         });
    //     }
    //     $('#subCategoryWrap').removeClass('d-none');
    //     $('#hCategory').val('');
    // });

    $(document).on('click', '.cat-main-opt', function () {
        $('.cat-main-opt').removeClass('selected');
        $(this).addClass('selected');
    });
    /* ── Sub category selection ── */
    
    $(document).on('click', '.cat-sub-opt', function () {
        $('.cat-sub-opt').removeClass('selected');
        $(this).addClass('selected');
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

    /* ── Classify modal ── */
    window.openClassifyModal = function (ticketId, ticketNumber) {
        $('#acTicketRef').text('#' + ticketNumber);
        $('#classifyForm').attr('action', '/helpdesk/tickets/' + ticketId + '/classify');
        $('#acSlaRuleId').val('');
        $('#acSubWrap, #acOverrideWrap').addClass('d-none');
        $('#acSubList').empty();
        $('#acResponseTime, #acResolutionTime').val('');
        $('#acWorkloadClass').val('');
        $('#acWorkloadManualHint').addClass('d-none');
        $('#acHandleMyself').prop('checked', false);
        $('#acHandleMyselfWrap').addClass('d-none');
        $('#acAdminOnlyWrap').addClass('d-none');
        $('#acCategoryList .cat-main-opt').removeClass('selected');
        updateClassifySubmitLabel();

        const $catList = $('#acCategoryList').empty();
        slaCategories.forEach(cat => {
            $catList.append(`<div class="cat-main-opt" data-cat-id="${cat.id}">${cat.name}</div>`);
        });

        new bootstrap.Modal('#classifyModal').show();
    };

    // ── Helpdesk's classification isn't final — the Supervisor's own Classify &
    // Assign step can freely override it (see SupervisorDashboardController), so
    // the submit button says so instead of implying the call is Helpdesk's alone.
    // Only the "Handle this myself (L1)" path is actually final, since Helpdesk
    // is committing to work it themselves rather than routing it onward.
    function updateClassifySubmitLabel() {
        const handleMyself = $('#acHandleMyself').is(':checked');
        const isAdminOnly = !$('#acAdminOnlyWrap').hasClass('d-none');

        const label = handleMyself
            ? 'Classify & Keep for Myself'
            : (isAdminOnly ? 'Suggest Classification for Supervisor - IT Admin' : 'Suggest Classification for Supervisor');

        $('#acSubmitBtnText').text(label);
    }

    $(document).on('change', '#acHandleMyself', updateClassifySubmitLabel);

    /* ── Helpdesk Add Update modal (L1 self-resolve progress log) ── */
    $(document).on('click', '.hd-status-opt', function () {
        $('.hd-status-opt').removeClass('selected');
        $(this).addClass('selected');
        $('#hdWorkStatusVal').val($(this).data('val'));
    });

    window.openHelpdeskUpdateModal = function (ticketId, ticketNumber) {
        $('#hdUpdateRef').text('#' + ticketNumber);
        $('#helpdeskUpdateForm').attr('action', '/helpdesk/tickets/' + ticketId + '/update');
        $('.hd-status-opt').removeClass('selected');
        $('.hd-status-opt[data-val="Investigating"]').addClass('selected');
        $('#hdWorkStatusVal').val('Investigating');
        $('#hdUAttachments').val('');
        $('#hdUpdateAttachmentList').empty();
        new bootstrap.Modal('#helpdeskUpdateModal').show();
    };

    /* ── Add Update modal attachment picker: client-side limits + preview list ── */
    const HD_UPDATE_MAX_ATTACHMENTS = 5;
    const HD_UPDATE_MAX_ATTACHMENT_MB = 10;

    function hdBindAttachmentPicker(inputSelector, listSelector, maxFiles, maxMb) {
        $(inputSelector).on('change', function () {
            const files = Array.from(this.files);
            const list  = $(listSelector).empty();

            if (files.length > maxFiles) {
                alert(`You can attach up to ${maxFiles} files. Only the first ${maxFiles} will be kept.`);
            }

            const oversize = files.find(f => f.size > maxMb * 1024 * 1024);
            if (oversize) {
                alert(`"${oversize.name}" exceeds the ${maxMb}MB limit and will be removed.`);
            }

            const kept = files
                .filter(f => f.size <= maxMb * 1024 * 1024)
                .slice(0, maxFiles);

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
    }

    hdBindAttachmentPicker('#hdUAttachments', '#hdUpdateAttachmentList', HD_UPDATE_MAX_ATTACHMENTS, HD_UPDATE_MAX_ATTACHMENT_MB);
    hdBindAttachmentPicker('#hdRAttachments', '#hdResolveAttachmentList', HD_UPDATE_MAX_ATTACHMENTS, HD_UPDATE_MAX_ATTACHMENT_MB);

    /* ── Helpdesk Resolve modal (L1 quick fix) ── */
    window.openHelpdeskResolveModal = function (ticketId, ticketNumber, progressDraft) {
        $('#hdResolveRef').text('#' + ticketNumber);
        $('#helpdeskResolveForm').attr('action', '/helpdesk/tickets/' + ticketId + '/resolve');
        $('#hdRAttachments').val('');
        $('#hdResolveAttachmentList').empty();
        // Pre-fill from the agent's own "Add Update" log — still fully editable,
        // just saves retyping what they already reported while working the ticket.
        $('#helpdeskResolveForm textarea[name="resolution_notes"]').val(progressDraft || '');
        $('#hdResolveDraftHint').toggleClass('d-none', !progressDraft);
        $('#helpdeskResolveForm textarea[name="findings"], #helpdeskResolveForm textarea[name="recommendation"]').val('');
        $('#helpdeskResolveForm input[name="service_type"][value="Remote"]').prop('checked', true);
        new bootstrap.Modal('#helpdeskResolveModal').show();
    };

    /* ── Cancel Ticket modal ── */
    window.openCancelModal = function (ticketId, ticketNumber) {
        $('#cancelTicketRef').text('#' + ticketNumber);
        $('#cancelTicketForm').attr('action', '/helpdesk/tickets/' + ticketId + '/cancel');
        $('#cancelTicketForm textarea[name="reason"]').val('');
        new bootstrap.Modal('#cancelTicketModal').show();
    };

    $(document).on('click', '#acCategoryList .cat-main-opt', function () {
        $('#acCategoryList .cat-main-opt').removeClass('selected');
        $(this).addClass('selected');

        const catId = $(this).data('cat-id');
        const cat = slaCategories.find(c => c.id === catId);
        const $subList = $('#acSubList').empty();

        if (!cat || !cat.subs.length) {
            $subList.append(`<div style="font-size:12px;color:var(--tm)">No SLA rules defined for this category yet.</div>`);
        } else {
            cat.subs.forEach(sub => {
                const priColor = sub.priority === 'Critical' ? '#8b0000' : (sub.priority === 'High' ? '#e24b4a' : (sub.priority === 'Medium' ? '#f5c842' : '#4a7c4a'));
                $subList.append(`<div class="cat-sub-opt" data-rule-id="${sub.rule_id}"
                     data-priority="${sub.priority}" data-response="${sub.response}" data-resolution="${sub.resolution}"
                     data-helpdesk-resolvable="${sub.helpdesk_resolvable ? '1' : '0'}"
                     data-admin-only="${sub.admin_only ? '1' : '0'}">
                    <div class="sub-check"></div>
                    <div style="flex:1">
                        <div>${sub.name}</div>
                        ${sub.description ? `<div style="font-size:11px;font-weight:400;color:var(--tm);margin-top:2px">${escapeHtmlChat(sub.description)}</div>` : ''}
                    </div>
                    <span style="font-size:10px;font-weight:800;color:${priColor}">${sub.priority} · ${sub.resolution}m SLA</span>
                </div>`);
            });
        }
        $('#acSubWrap').removeClass('d-none');
        $('#acOverrideWrap').addClass('d-none');
        $('#acHandleMyself').prop('checked', false);
        $('#acHandleMyselfWrap').addClass('d-none');
        $('#acAdminOnlyWrap').addClass('d-none');
        updateClassifySubmitLabel();
    });

    $(document).on('click', '#acSubList .cat-sub-opt', function () {
        $('#acSubList .cat-sub-opt').removeClass('selected');
        $(this).addClass('selected');
        $('#acSlaRuleId').val($(this).data('rule-id'));

        // Prefill the override fields with the rule's defaults — Helpdesk can still edit them.
        $('#acPriority').val($(this).data('priority'));
        $('#acResponseTime').val($(this).data('response'));
        $('#acResolutionTime').val($(this).data('resolution'));
        $('#acOverrideWrap').removeClass('d-none');

        // "Handle this myself" only offered when IT Admin marked this exact
        // subcategory as Helpdesk-resolvable — see SlaRule::helpdesk_resolvable.
        const isHelpdeskResolvable = $(this).data('helpdesk-resolvable') === 1 || $(this).data('helpdesk-resolvable') === '1';
        $('#acHandleMyself').prop('checked', false);
        $('#acHandleMyselfWrap').toggleClass('d-none', !isHelpdeskResolvable);

        // L3-only subcategory — informational, not a choice. See SlaRule::admin_only.
        const isAdminOnly = $(this).data('admin-only') === 1 || $(this).data('admin-only') === '1';
        $('#acAdminOnlyWrap').toggleClass('d-none', !isAdminOnly);
        updateClassifySubmitLabel();
    });

    /* ── Workload class — auto-fills response/resolution, requires manual entry
           for classes with no fixed resolution target (Project/Planned, Vendor). ── */
    $(document).on('change', '#acWorkloadClass', function () {
        const $opt = $(this).find(':selected');
        const isManual = $opt.data('manual') === 1 || $opt.data('manual') === '1';

        if (!$(this).val()) {
            $('#acWorkloadManualHint').addClass('d-none');
            $('#acResolutionTime').prop('readonly', false);
            return;
        }

        $('#acResponseTime').val($opt.data('response'));

        if (isManual) {
            $('#acResolutionTime').val('').prop('readonly', false).trigger('focus');
            $('#acWorkloadManualHint').removeClass('d-none');
        } else {
            $('#acResolutionTime').val($opt.data('resolution')).prop('readonly', false);
            $('#acWorkloadManualHint').addClass('d-none');
        }
    });

    $('#classifyForm').on('submit', function (e) {
        if (!$('#acSlaRuleId').val()) {
            e.preventDefault();
            alert('Please select a subcategory.');
            return;
        }
        const $wc = $('#acWorkloadClass').find(':selected');
        const wcManual = $wc.data('manual') === 1 || $wc.data('manual') === '1';
        if ($('#acWorkloadClass').val() && wcManual && !$('#acResolutionTime').val()) {
            e.preventDefault();
            alert(`The "${$wc.text().trim()}" workload class has no fixed resolution target — enter the agreed resolution time in minutes.`);
        }
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
                        window.location.href = '{{ route("helpdesk.dashboard") }}';
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

        const ackLink  = doc.querySelector("a[href*='status=new-request']");
        const ackCount = ackLink ? parseInt((ackLink.querySelector('.badge-count') || {}).textContent || '0', 10) : 0;
        document.title = ackCount > 0 ? `For Acknowledgment (${ackCount}) — Helpdesk Dashboard — LGICT` : 'Helpdesk Dashboard — LGICT';
        setFaviconBadge(ackCount);

        const newList = doc.getElementById('ticketList');
        const curList = document.getElementById('ticketList');
        if (newList && curList) curList.innerHTML = newList.innerHTML;

        doc.querySelectorAll('.badge-count').forEach((newEl, i) => {
            const curEl = document.querySelectorAll('.badge-count')[i];
            if (!curEl) return;

            const newLi = newEl.closest('li.list-group-item');
            const curLi = curEl.closest('li.list-group-item');
            if (newLi && curLi) curLi.classList.toggle('queue-glow', newLi.classList.contains('queue-glow'));

            if (curEl.textContent.trim() !== newEl.textContent.trim()) {
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

setFaviconBadge({{ $counts['new_request'] }});
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