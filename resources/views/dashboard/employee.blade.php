@extends('layouts.app')

@section('title', 'My Support Requests — LGICT')

{{-- ── Nav ── --}}
@section('nav-role-badge')
@endsection
@section('avatar-initials',
    strtoupper(substr(Auth::user()->name, 0, 1)) .
    strtoupper(substr(Auth::user()->name, strpos(Auth::user()->name, ' ') + 1, 1))
)
@section('nav-username', explode(' ', Auth::user()->name)[0] . ' ' . strtoupper(substr(explode(' ', Auth::user()->name)[1] ?? '', 0, 1)) . '.')

{{-- ── Hero ── --}}
@section('hero-title')
    <h1>MY <em>SUPPORT</em><br>REQUESTS</h1>
@endsection
@section('hero-subtitle', 'Track your requests and get IT help fast.')

@section('hero-stats')
    <div class="d-flex gap-2 flex-wrap">
        <div class="stat-pill">
            <span class="num" id="cnt-open">{{ $counts['submitted'] }}</span>
            <span class="lbl">Submitted</span>
        </div>
        <div class="stat-pill">
            <span class="num" id="cnt-class">{{ $counts['scheduled'] }}</span>
            <span class="lbl">Scheduled</span>
        </div>
        <div class="stat-pill warn">
            <span class="num" id="cnt-prog">{{ $counts['in_progress'] }}</span>
            <span class="lbl">In Progress</span>
        </div>
        <div class="stat-pill info">
            <span class="num" id="cnt-await">{{ $counts['awaiting_requestor'] }}</span>
            <span class="lbl">Awaiting Your Confirmation</span>
        </div>
        <div class="stat-pill">
            <span class="num" id="cnt-done">{{ $counts['closed'] }}</span>
            <span class="lbl">Closed</span>
        </div>
    </div>
@endsection

@section('hero-cta')
    <button class="btn-news" data-bs-toggle="modal" data-bs-target="#ticketModal">
        <i class="bi bi-plus-lg me-1"></i> New Request
    </button>
@endsection

{{-- ── Page-specific styles ── --}}
@section('styles')
    /* ── Employee: New Ticket button ── */
    .btn-news {
        background: var(--yg); color: var(--gd);
        font-family: 'Nunito', sans-serif; font-weight: 900; font-size: 15px;
        padding: 13px 28px; border-radius: 50px; border: none;
        transition: background .2s, transform .15s; white-space: nowrap;
    }
    .btn-news:hover { background: var(--ygd); transform: translateY(-2px); }

    /* ── Onboarding modal ── */
    .onboard-icon {
        width: 34px; height: 34px; border-radius: 10px; flex-shrink: 0;
        background: var(--ygl); color: var(--gd);
        display: flex; align-items: center; justify-content: center; font-size: 15px;
    }

    /* ── Available IT panel ── */
    .it-avail-row { display:flex; align-items:center; gap:8px; padding:6px 8px; border-radius:10px; }
    .it-avail-row:hover { background:var(--ygl); }
    .it-avail-av { width:30px; height:30px; border-radius:50%; flex-shrink:0; background:var(--gd); color:var(--yg); font-family:'Nunito',sans-serif; font-weight:800; font-size:11px; display:flex; align-items:center; justify-content:center; }
    .it-avail-info { display:flex; flex-direction:column; flex:1; min-width:0; }
    .it-avail-name { font-family:'Nunito',sans-serif; font-weight:800; font-size:12.5px; color:var(--gd); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .it-avail-role { font-size:10.5px; color:var(--tm); font-weight:600; }
    .it-avail-status { display:flex; align-items:center; gap:5px; font-size:10.5px; font-weight:800; text-transform:uppercase; letter-spacing:.3px; flex-shrink:0; }
    .it-avail-status.online  { color:#1a7a3a; }
    .it-avail-status.offline { color:var(--tm); }
    .it-avail-dot { width:8px; height:8px; border-radius:50%; background:#b8b8a8; }
    .it-avail-status.online .it-avail-dot { background:#2ecc71; box-shadow:0 0 0 0 rgba(46,204,113,.6); animation:itAvailPulse 2s infinite; }
    @keyframes itAvailPulse {
        0%   { box-shadow:0 0 0 0 rgba(46,204,113,.5); }
        70%  { box-shadow:0 0 0 5px rgba(46,204,113,0); }
        100% { box-shadow:0 0 0 0 rgba(46,204,113,0); }
    }
    .it-avail-empty { font-size:12.5px; color:var(--tm); padding:6px 8px; }

    /* ── Awaiting-you attention banner ── */
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

    /* ── Ticket card: lifecycle tracker ── */
    .progress-strip { display:flex; align-items:center; margin-bottom:14px; }
    .ps-step { flex:1; text-align:center; }
    .ps-dot { width:26px; height:26px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 4px; font-size:11px; font-weight:900; font-family:'Nunito',sans-serif; border:2px solid var(--bd); background:#fff; color:var(--tm); transition:all .3s; }
    .ps-dot.done   { background:var(--gd); color:var(--yg); border-color:var(--gd); }
    .ps-dot.active { background:var(--yg); color:var(--gd); border-color:var(--yg); }
    .ps-dot.failed { background:#e24b4a; color:#fff; border-color:#e24b4a; }
    .ps-lbl { font-size:9px; font-weight:700; color:var(--tm); text-transform:uppercase; letter-spacing:.3px; }
    .ps-lbl.done   { color:var(--gd); }
    .ps-lbl.active { color:var(--gd); font-weight:800; }
    .ps-lbl.failed { color:#e24b4a; font-weight:800; }
    .ps-line { flex:1; height:2px; background:var(--bd); transition:background .3s; }
    .ps-line.done  { background:var(--gd); }

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

    /* Success */
    .success-icon { width: 72px; height: 72px; background: var(--ygl); border-radius: 50%; font-size: 36px; }
    .ticket-ref   { background: var(--gd); color: var(--yg); font-family: 'Nunito', sans-serif; font-weight: 900; font-size: 20px; padding: 10px 28px; border-radius: 50px; letter-spacing: 1px; display: inline-block; }

    /* Cancel button */
    .btn-cancel-ticket { background: none; border: 1.5px solid #e24b4a; color: #e24b4a; font-family: 'Nunito', sans-serif; font-weight: 800; font-size: 13px; padding: 7px 18px; border-radius: 50px; transition: all .2s; cursor: pointer; }
    .btn-cancel-ticket:hover { background: #e24b4a; color: #fff; }

    /* View details button */
    .btn-view-detail { background: none; border: 1.5px solid var(--bd); color: var(--tm); font-family: 'Nunito', sans-serif; font-weight: 800; font-size: 13px; padding: 7px 18px; border-radius: 50px; transition: all .2s; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
    .btn-view-detail:hover { border-color: var(--gl); color: var(--gd); }
    /* ── Service Report button (same green as resolve actions system-wide) ── */
    .btn-service-report { background:#e8f5ee; color:#1a5a3a; font-family:'Nunito',sans-serif; font-weight:800; font-size:13px; padding:7px 18px; border-radius:50px; border:1.5px solid #a8ddc0; cursor:pointer; transition:all .2s; text-decoration:none; display:inline-flex; align-items:center; gap:4px; }
    .btn-service-report:hover { background:#c8ead8; }
    /* ── Chat button ── */
    .btn-chat { background:#e8eeff; color:#2a4ab0; font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; padding:7px 16px; border-radius:20px; border:1.5px solid #b8c8ff; cursor:pointer; transition:all .2s; display:inline-flex; align-items:center; gap:5px; }
    .btn-chat:hover { background:#d0dcff; border-color:#8898dd; }
    .chat-count-badge { background:#e24b4a; color:#fff; font-size:10px; font-weight:900; border-radius:20px; padding:1px 6px; font-family:'Nunito',sans-serif; min-width:18px; text-align:center; }
    .btn-acknowledge { background:#e0f5e0; color:#1e6b1e; font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; padding:7px 16px; border-radius:20px; border:1.5px solid #a8dba8; cursor:pointer; transition:all .2s; display:inline-flex; align-items:center; gap:5px; }
    .btn-acknowledge:hover { background:#c8ecc8; border-color:#7fc97f; }
    .btn-rate { background:#fff4cc; color:#7a5a00; font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; padding:7px 16px; border-radius:20px; border:1.5px solid #f5c842; cursor:pointer; transition:all .2s; display:inline-flex; align-items:center; gap:5px; }
    .btn-rate:hover { background:#ffe999; border-color:#e0b020; }

    .rated-chip { background:#fff9e6; color:#7a5a00; font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; padding:7px 16px; border-radius:20px; border:1.5px solid #f5c842; display:inline-flex; align-items:center; gap:4px; }

    /* ── Star rating ── */
    .star-rating { display:flex; flex-direction:row-reverse; gap:4px; justify-content:flex-end; }
    .star-rating input { display:none; }
    .star-rating label { font-size:28px; color:#e2ddd4; cursor:pointer; transition:color .15s; line-height:1; }
    .star-rating input:checked ~ label,
    .star-rating label:hover,
    .star-rating label:hover ~ label { color:#f5c842; }
    .feedback-submitted { background:var(--ygl); border-radius:12px; padding:12px 16px; font-size:13px; }
    /* ── Silent refresh pulse ── */
    @keyframes badgePulse {
        0%   { transform: scale(1); }
        50%  { transform: scale(1.3); background: var(--yg); color: var(--gd); }
        100% { transform: scale(1); }
    }
    .badge-pulse {
        animation: badgePulse .6s ease;
    }
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
@endsection

{{-- ══ SIDEBAR ══ --}}
@section('sidebar')
    {{-- Available IT — who's online right now, from Helpdesk, IT Support
         Specialist, IT Admin, and their Supervisors. Refreshed every 30s via
         /employee/it-team/presence. --}}
    <div class="sidebar-card mb-3">
        <div class="sidebar-head">Available IT</div>
        <div class="p-2 d-flex flex-column gap-1" id="itTeamList">
            @forelse($itTeam as $member)
                @php
                    $mParts = explode(' ', $member->name);
                    $mInitials = strtoupper(substr($mParts[0], 0, 1)) .
                                 strtoupper(substr($mParts[count($mParts) - 1], 0, 1));
                @endphp
                <div class="it-avail-row">
                    <span class="it-avail-av">{{ $mInitials }}</span>
                    <span class="it-avail-info">
                        <span class="it-avail-name">{{ $member->name }}</span>
                        <span class="it-avail-role">{{ $member->role?->role_name ?? 'N/A' }}</span>
                    </span>
                    <span class="it-avail-status {{ $member->online ? 'online' : 'offline' }}">
                        <span class="it-avail-dot"></span>{{ $member->online ? 'Online' : 'Offline' }}
                    </span>
                </div>
            @empty
                <div class="it-avail-empty">No IT staff on record.</div>
            @endforelse
        </div>
    </div>

    {{-- Nav menu --}}
    <div class="sidebar-card mb-3">
        <div class="sidebar-head">My Support Requests</div>
        <ul class="list-group sidebar-menu rounded-0" id="sideNav">
            <li class="list-group-item {{ $status === 'all' ? 'active' : '' }}">
                <a href="{{ route($routePrefix.'tickets.index', ['status' => 'all']) }}" class="d-flex justify-content-between align-items-center text-decoration-none w-100">
                    <span><i class="bi bi-grid me-2"></i>All support requests</span>
                    <span class="badge-count">{{ $counts['all'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'submitted' ? 'active' : '' }}">
                <a href="{{ route($routePrefix.'tickets.index', ['status' => 'submitted']) }}" class="d-flex justify-content-between align-items-center text-decoration-none w-100">
                    <span><i class="bi bi-circle me-2"></i>Submitted</span>
                    <span class="badge-count">{{ $counts['submitted'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'scheduled' ? 'active' : '' }}">
                <a href="{{ route($routePrefix.'tickets.index', ['status' => 'scheduled']) }}" class="d-flex justify-content-between align-items-center text-decoration-none w-100">
                    <span><i class="bi bi-diagram-3 me-2"></i>Scheduled</span>
                    <span class="badge-count">{{ $counts['scheduled'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'in_progress' ? 'active' : '' }}">
                <a href="{{ route($routePrefix.'tickets.index', ['status' => 'in_progress']) }}" class="d-flex justify-content-between align-items-center text-decoration-none w-100">
                    <span><i class="bi bi-arrow-repeat me-2"></i>In progress</span>
                    <span class="badge-count">{{ $counts['in_progress'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'awaiting_requestor' ? 'active' : '' }}">
                <a href="{{ route($routePrefix.'tickets.index', ['status' => 'awaiting_requestor']) }}" class="d-flex justify-content-between align-items-center text-decoration-none w-100">
                    <span><i class="bi bi-hourglass-split me-2"></i>Awaiting Your Confirmation</span>
                    <span class="badge-count">{{ $counts['awaiting_requestor'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'closed' ? 'active' : '' }}">
                <a href="{{ route($routePrefix.'tickets.index', ['status' => 'closed']) }}" class="d-flex justify-content-between align-items-center text-decoration-none w-100">
                    <span><i class="bi bi-check-circle me-2"></i>Closed</span>
                    <span class="badge-count">{{ $counts['closed'] }}</span>
                </a>
            </li>
        </ul>
    </div>

    {{-- Filter card --}}
    <div class="sidebar-card filter-card">
        <div class="sidebar-head">Filter</div>
        <form method="GET" action="{{ route($routePrefix.'tickets.index') }}" class="p-3 d-flex flex-column gap-2">
            <input type="hidden" name="status" value="{{ $status }}">
            <div>
                <label class="form-label mb-1">Category</label>
                <select class="form-select form-select-sm" name="category">
                    <option value="">All categories</option>
                    @foreach($slaCategories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category') === $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label mb-1">From date</label>
                <input type="date" class="form-control form-control-sm"
                       name="from_date" value="{{ request('from_date') }}">
            </div>
            <div>
                <label class="form-label mb-1">To date</label>
                <input type="date" class="form-control form-control-sm"
                       name="to_date" value="{{ request('to_date') }}">
            </div>
            <button type="submit" class="btn btn-filter w-100 mt-1">
                Apply filters
            </button>
            @if(request('category') || request('from_date') || request('to_date'))
                <a href="{{ route($routePrefix.'tickets.index', ['status' => $status]) }}"
                   class="btn btn-sm btn-outline-secondary w-100">
                    Clear filters
                </a>
            @endif
        </form>
    </div>
@endsection

{{-- ══ MAIN CONTENT ══ --}}
@section('content')

    {{-- Awaiting-you attention banner — resolved tickets need the employee to
         acknowledge before they can close, so this stays up (not dismissible)
         for as long as any ticket is sitting in that state. --}}
    @if($counts['awaiting_requestor'] > 0)
        <a href="{{ route($routePrefix.'tickets.index', ['status' => 'awaiting_requestor']) }}"
           class="awaiting-banner">
            <span class="aw-icon"><i class="bi bi-exclamation-lg"></i></span>
            <span>
                <div class="aw-title">
                    {{ $counts['awaiting_requestor'] }}
                    {{ Str::plural('support request', $counts['awaiting_requestor']) }} awaiting your acknowledgment
                </div>
                <div class="aw-sub">Confirm the resolution so we can close {{ $counts['awaiting_requestor'] === 1 ? 'it' : 'them' }} out.</div>
            </span>
            <span class="aw-cta">Review Now <i class="bi bi-arrow-right ms-1"></i></span>
        </a>
    @endif

    {{-- Success / Error alerts --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Controls --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <span class="font-brand fw-900" style="font-size:22px" id="listTitle">
            @php
                $labels = [
                    'all'                        => 'All Support Requests',
                    'submitted'                  => 'Submitted',
                    'scheduled'                  => 'Scheduled',
                    'in_progress'                => 'In Progress',
                    'awaiting_requestor'         => 'Awaiting Your Confirmation',
                    'closed'                     => 'Closed',
                    'cancelled'                  => 'Cancelled',
                ];
            @endphp
            {{ $labels[$status] ?? 'All Support Requests' }}
        </span>
        <form method="GET" action="{{ route($routePrefix.'tickets.index') }}"
              class="d-flex gap-2" id="searchForm">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="search-wrap">
                <i class="bi bi-search" style="color:var(--tm)"></i>
                <input type="text" name="search" id="searchInput"
                       placeholder="Search support requests…"
                       value="{{ $search }}" autocomplete="off">
            </div>
            <select class="sort-select" name="sort" onchange="this.form.submit()">
                <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Newest first</option>
                <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Oldest first</option>
            </select>
        </form>
    </div>

    {{-- Tab pills --}}
    <div class="d-flex flex-wrap gap-2 mb-3" id="tabRow">
        @php
            $tabs = [
                'all'                        => ['label' => 'All',                    'count' => $counts['all']],
                'submitted'                  => ['label' => 'Submitted',              'count' => $counts['submitted']],
                'scheduled'                  => ['label' => 'Scheduled',              'count' => $counts['scheduled']],
                'in_progress'                => ['label' => 'In Progress',            'count' => $counts['in_progress']],
                'awaiting_requestor'         => ['label' => 'Awaiting Your Confirmation', 'count' => $counts['awaiting_requestor']],
                'closed'                     => ['label' => 'Closed',                 'count' => $counts['closed']],
                'cancelled'                  => ['label' => 'Cancelled',              'count' => $counts['cancelled']],
            ];
        @endphp
        @foreach($tabs as $key => $tab)
            <a href="{{ route($routePrefix.'tickets.index', array_merge(request()->except('status'), ['status' => $key])) }}"
               class="tab-pill {{ $status === $key ? 'active' : '' }}">
                {{ $tab['label'] }} ({{ $tab['count'] }})
            </a>
        @endforeach
    </div>

    {{-- Ticket cards --}}
    <div class="d-flex flex-column gap-3" id="ticketList">

        @forelse($tickets as $ticket)
            @php
                // ── Full lifecycle tracker: Submitted -> Scheduled -> In Progress ->
                // Awaiting Your Confirmation -> Closed. Deliberately simplified — the
                // employee side doesn't show the detailed internal workflow (see
                // App\Support\TicketStatus for the 12 internal statuses staff work with).
                //
                // MUST STAY IN SYNC with App\Support\TicketTrackerStep::highestReached() —
                // same statuses, same step numbers. Change one, change the other. The
                // status badge on the card (below) is derived from these same phase
                // flags, not the raw internal status, so the badge text and the
                // tracker underneath it never disagree.
                $isCancelled = $ticket->status === 'Cancelled';

                // Mirrors TicketTrackerStep::SCHEDULED_STATUSES.
                $isScheduledPhase = $ticket->status === 'Assigned';
                // Mirrors TicketTrackerStep::IN_PROGRESS_STATUSES.
                $isInProgressPhase = in_array($ticket->status, [
                    'In Progress Service Request',
                    'Closed Service Request',
                    'In Progress Service Report',
                    'Done Service Report',
                    'Report For Review',
                    'Approved Service Report',
                    'Escalated',
                ], true);
                $isAwaitingRequestorT = $ticket->status === 'Requestor Confirmation';
                $isClosedT = $ticket->status === 'Closed';

                // Mirrors TicketTrackerStep::highestReached() — the highest step (1-5)
                // this ticket has reached, given its current status.
                $highestStep = match(true) {
                    $isClosedT => 5,
                    $isAwaitingRequestorT => 4,
                    $isInProgressPhase => 3,
                    $isScheduledPhase => 2,
                    default => 1,
                };

                // Card badge — same 5-phase vocabulary as the tracker below, not the
                // raw internal status (e.g. never shows "Report For Review" or
                // "Escalated" — those all read as "In Progress" here, same as they
                // do in the tracker).
                $badgeText = match(true) {
                    $isCancelled => 'Cancelled',
                    $isClosedT => 'Closed',
                    $isAwaitingRequestorT => 'Awaiting Your Confirmation',
                    $isInProgressPhase => 'In Progress',
                    $isScheduledPhase => 'Scheduled',
                    default => 'Submitted',
                };
                $statusClass = match(true) {
                    $isCancelled => 'cancelled',
                    $isClosedT => 'closed',
                    $isAwaitingRequestorT => 'awaiting-requestor',
                    $isInProgressPhase => 'in-progress',
                    $isScheduledPhase => 'in-progress',
                    default => 'open',
                };
                $badgeClass = match(true) {
                    $isCancelled => 'badge-cancelled',
                    $isClosedT => 'badge-closed',
                    $isAwaitingRequestorT => 'badge-awaiting',
                    $isInProgressPhase => 'badge-in-progress',
                    $isScheduledPhase => 'badge-in-progress',
                    default => 'badge-open',
                };
                $badgeIcon = match(true) {
                    $isCancelled => '✕',
                    $isClosedT => '✓',
                    $isAwaitingRequestorT => '⏳',
                    $isInProgressPhase => '⟳',
                    $isScheduledPhase => '→',
                    default => '●',
                };
                $initials = $ticket->assignedTo
                    ? strtoupper(substr($ticket->assignedTo->name, 0, 1)) . strtoupper(substr($ticket->assignedTo->name, strpos($ticket->assignedTo->name, ' ') + 1, 1))
                    : '—';

                // Steps 2-4: 'done' once passed, 'active' at the current step, '' if not
                // reached yet. Step 5 (Closed) is terminal — it's marked 'done' the instant
                // it's reached rather than ever showing 'active', mirroring how a ticket never
                // "sits" at Closed the way it sits at earlier steps.
                $stepClass = fn (int $step) => match(true) {
                    $step === 5 => $highestStep >= 5 ? 'done' : '',
                    $highestStep > $step => 'done',
                    $highestStep === $step => 'active',
                    default => '',
                };

                $tStep2 = $stepClass(2);
                $tStep3 = $stepClass(3);
                $tStep4 = $stepClass(4);
                $tStep5 = $stepClass(5);
                $tLine1 = $tStep2 !== '' ? 'done' : '';
                $tLine2 = $tStep3 !== '' ? 'done' : '';
                $tLine3 = $tStep4 !== '' ? 'done' : '';
                $tLine4 = $tStep5 !== '' ? 'done' : '';

                // ── Ticket was investigated but couldn't be technically fixed — jumps
                //    straight from Escalated to Requestor Confirmation, so the failure
                //    marker belongs on "In Progress", the step it passes through on its
                //    way there.
                $isUnresolved = $ticket->cannot_resolve && $tStep3 !== '';
            @endphp

            <div class="ticket-card {{ $statusClass }} p-3"
                 data-status="{{ strtolower($ticket->status) }}"
                 data-category="{{ $ticket->request_category }}">

                {{-- Header --}}
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="ticket-id">#{{ $ticket->ticket_number }}</span>
                        <span class="badge-type">{{ $ticket->request_category }}</span>
                    </div>
                    <span class="badge-status {{ $badgeClass }}">
                        {{ $badgeIcon }} {{ $badgeText }}
                    </span>
                </div>

                {{-- Lifecycle tracker --}}
                @if(!$isCancelled)
                    <div class="progress-strip mb-3">
                        <div class="ps-step">
                            <div class="ps-dot done"><i class="bi bi-check"></i></div>
                            <div class="ps-lbl done">Submitted</div>
                        </div>
                        <div class="ps-line {{ $tLine1 }}"></div>
                        <div class="ps-step">
                            <div class="ps-dot {{ $tStep2 }}">
                                @if($tStep2 === 'done') <i class="bi bi-check"></i> @else 2 @endif
                            </div>
                            <div class="ps-lbl {{ $tStep2 }}">Scheduled</div>
                        </div>
                        <div class="ps-line {{ $tLine2 }}"></div>
                        <div class="ps-step">
                            @if($isUnresolved)
                                <div class="ps-dot failed"><i class="bi bi-x-lg"></i></div>
                                <div class="ps-lbl failed">Unresolved</div>
                            @else
                                <div class="ps-dot {{ $tStep3 }}">
                                    @if($tStep3 === 'done') <i class="bi bi-check"></i> @else 3 @endif
                                </div>
                                <div class="ps-lbl {{ $tStep3 }}">In Progress</div>
                            @endif
                        </div>
                        <div class="ps-line {{ $tLine3 }}"></div>
                        <div class="ps-step">
                            <div class="ps-dot {{ $tStep4 }}">
                                @if($tStep4 === 'done') <i class="bi bi-check"></i> @else 4 @endif
                            </div>
                            <div class="ps-lbl {{ $tStep4 }}">Awaiting Your Confirmation</div>
                        </div>
                        <div class="ps-line {{ $tLine4 }}"></div>
                        <div class="ps-step">
                            <div class="ps-dot {{ $tStep5 }}">
                                @if($tStep5 === 'done') <i class="bi bi-check"></i> @else 5 @endif
                            </div>
                            <div class="ps-lbl {{ $tStep5 }}">Closed</div>
                        </div>
                    </div>
                @endif

                {{-- Classification & assignment — shown once Helpdesk/Supervisor have
                     classified the ticket and assigned it to an ICT Support Specialist. --}}
                @if($ticket->subcategory_name)
                    <div class="mb-3 p-2 d-flex flex-wrap gap-3"
                         style="background:var(--ygl);border-radius:10px;border:1px solid var(--gl);font-size:12px">
                        <span><strong>Category:</strong> {{ $ticket->slaCategory->name ?? '—' }}</span>
                        <span><strong>Subtask:</strong> {{ $ticket->subcategory_name }}</span>
                        @if($ticket->assignedTo)
                            <span><strong>ICT Support Specialist:</strong> {{ $ticket->assignedTo->name }}</span>
                        @endif
                    </div>
                @endif

                {{-- Cannot Resolve findings (visible once the ticket is marked Unresolved) --}}
                @if($ticket->cannot_resolve)
                    <div class="mb-3 p-3" style="background:#fde8e8;border-radius:12px;border:1px solid #f0c0c0">
                        <div style="font-size:11px;font-weight:800;color:#8b1a1a;text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px">
                            <i class="bi bi-x-octagon me-1"></i>Findings &amp; Analysis
                        </div>
                        <div style="font-size:13px;color:#8b1a1a;margin-bottom:{{ $ticket->cannot_resolve_recommendation ? '10px' : '0' }}">
                            {{ $ticket->cannot_resolve_findings }}
                        </div>
                        @if($ticket->cannot_resolve_recommendation)
                            <div style="font-size:11px;font-weight:800;color:#8b1a1a;text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px">
                                Recommendation
                            </div>
                            <div style="font-size:13px;color:#8b1a1a">
                                {{ $ticket->cannot_resolve_recommendation }}
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Title & description --}}
                <div class="ticket-title mb-1">{{ $ticket->subject }}</div>
                <div class="ticket-desc mb-3">
                    {{ Str::limit($ticket->concern, 150) }}
                </div>

                {{-- Meta --}}
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="meta-item">
                        <i class="bi bi-calendar3"></i>
                        {{ $ticket->created_at->diffForHumans() }}
                    </span>
                    @if($ticket->asset)
                        <span class="meta-item">
                            <i class="bi bi-laptop"></i> {{ $ticket->asset }}
                        </span>
                    @endif
                    @if($ticket->location)
                        <span class="meta-item">
                            <i class="bi bi-geo-alt"></i> {{ $ticket->location }}
                        </span>
                    @endif

                    @if($ticket->expected_start_label ?? null)
                        <span class="meta-item" style="background:var(--ygl);border-radius:20px;padding:4px 12px;font-weight:800">
                            <i class="bi bi-clock"></i>
                            {{ $ticket->expected_start_label === 'Being worked on now' ? $ticket->expected_start_label : 'Expected to start: ' . $ticket->expected_start_label }}
                        </span>
                    @endif

                    @if($ticket->status === 'Closed' && $ticket->started_at)
                        <span class="meta-item">
                            <i class="bi bi-play-circle"></i>
                            Started {{ $ticket->started_at->format('M d, g:i A') }}
                        </span>
                    @endif
                    @if($ticket->status === 'Closed' && $ticket->resolved_at)
                        <span class="meta-item">
                            <i class="bi bi-flag"></i>
                            Resolved {{ $ticket->resolved_at->format('M d, g:i A') }}
                        </span>
                    @endif
                    @if($ticket->status === 'Closed' && $ticket->actualResolutionTime())
                        <span class="meta-item">
                            <i class="bi bi-stopwatch"></i>
                            Actual resolution time: {{ $ticket->actualResolutionTime() }}
                        </span>
                    @endif

                    {{-- Assigned tech chip --}}
                    @if($ticket->assignedTo)
                        <span class="tech-chip ms-auto">
                            <span class="tc-av">{{ $initials }}</span>
                            {{ $ticket->assignedTo->name }}
                        </span>
                    @else
                        <span class="tech-chip ms-auto">
                            <span class="tc-av" style="background:#888">—</span>
                            Unassigned
                        </span>
                    @endif
                </div>

                {{-- Escalation banner --}}
                @if($ticket->status === 'Escalated')
                    <div class="esc-banner mt-2 p-2">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        Escalated to IT Admin — Level {{ $ticket->escalation_level }}.
                        @if($ticket->assignedTo)
                            Currently handled by {{ $ticket->assignedTo->name }}.
                        @endif
                    </div>
                @endif

                {{-- Action buttons --}}
                <div class="d-flex gap-2 mt-3 flex-wrap">
                    <a href="{{ route($routePrefix.'tickets.show', $ticket) }}"
                    class="btn-view-detail">
                        <i class="bi bi-eye me-1"></i>View Details
                    </a>

                    @if($ticket->status === 'Closed')
                        <button type="button" class="btn-service-report"
                                onclick="openServiceReportPreview('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-file-earmark-pdf me-1"></i>Service Report
                        </button>
                    @endif

                    @if($ticket->status === 'For Acknowledgment' && is_null($ticket->date_acknowledged))
                        <button class="btn-cancel-ticket"
                                onclick="confirmCancel('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </button>
                    @endif

                    {{-- ── Chat button (all active tickets) ── --}}
                    @if(!in_array($ticket->status, ['Cancelled']))
                        <button class="btn-chat"
                                onclick="openEmpChatModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-chat-dots me-1"></i>Message
                            @php
                                $unread = \App\Models\TicketMessage::where('ticket_id', $ticket->id)
                                    ->where('sender_id', '!=', Auth::id())
                                    ->where('is_read', false)->count();
                            @endphp
                            @if($unread > 0)
                                <span class="chat-count-badge" id="badge-{{ $ticket->id }}">
                                    {{ $unread }}
                                </span>
                            @endif
                        </button>
                    @endif

                    @if($ticket->status === 'Requestor Confirmation')
                        <form method="POST" action="{{ route($routePrefix.'tickets.acknowledge', $ticket) }}" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn-acknowledge">
                                <i class="bi bi-check2-circle me-1"></i>Acknowledge & Close
                            </button>
                        </form>
                    @endif
                    {{-- ── Rate & Feedback (resolved only, not yet rated) ── --}}
                    @if($ticket->status === 'Closed' && !$ticket->feedback)
                        <button class="btn-rate"
                                onclick="openFeedbackModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-star me-1"></i>Rate & Feedback
                        </button>
                    @elseif($ticket->status === 'Closed' && $ticket->feedback)
                        <span class="rated-chip">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="bi bi-star{{ $i <= $ticket->feedback->rating ? '-fill' : '' }}"></i>
                            @endfor
                            {{ $ticket->feedback->rating }}/5 — Rated
                        </span>
                    @endif
                </div>

            </div>
        @empty
            <div class="ticket-card p-5 text-center">
                <div style="font-size:48px;opacity:.3">🎫</div>
                <div class="mt-3 font-brand fw-900"
                     style="font-size:18px;color:var(--tm)">
                    No support requests found.
                </div>
                <div style="font-size:13px;color:var(--tm);margin-top:4px">
                    @if($status !== 'all')
                        You have no {{ $status }} support requests.
                    @else
                        You haven't submitted any support requests yet.
                    @endif
                </div>
            </div>
        @endforelse

    </div>

    {{-- Pagination --}}
    @if($tickets->hasPages())
        <div class="mt-4">{{ $tickets->onEachSide(1)->links('pagination::bootstrap-5') }}</div>
    @endif

@endsection

{{-- ══ NEW TICKET MODAL ══ --}}
@section('modals')

    {{-- Welcome / Onboarding Modal — one-time, shown until the employee
         dismisses it (users.onboarded_at gets stamped, see
         TicketsController::completeOnboarding()). --}}
    @if($showOnboarding)
        <div class="modal fade" id="onboardingModal" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header-gd d-flex align-items-center justify-content-between">
                        <h5 class="mb-0">Welcome to the New <em>IT Support System</em></h5>
                        <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                    </div>
                    <div class="modal-body px-4 py-4">
                        <p style="font-size:13px;color:var(--tm);margin-bottom:18px">
                            This replaces email/walk-in IT requests — here's what's different:
                        </p>
                        <div class="d-flex flex-column gap-3">
                            <div class="d-flex align-items-start gap-3">
                                <div class="onboard-icon"><i class="bi bi-stopwatch"></i></div>
                                <div>
                                    <div style="font-weight:800;font-size:13px">Know what to expect</div>
                                    <div style="font-size:12px;color:var(--tm)">Every support request gets a response and resolution target the moment it's submitted.</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-start gap-3">
                                <div class="onboard-icon"><i class="bi bi-activity"></i></div>
                                <div>
                                    <div style="font-weight:800;font-size:13px">Track it live</div>
                                    <div style="font-size:12px;color:var(--tm)">Watch your support request move from submitted, to assigned, to resolved — right from this dashboard.</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-start gap-3">
                                <div class="onboard-icon"><i class="bi bi-chat-dots"></i></div>
                                <div>
                                    <div style="font-weight:800;font-size:13px">Message your specialist</div>
                                    <div style="font-size:12px;color:var(--tm)">No more back-and-forth emails — chat directly on the support request and get notified the moment it changes.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top px-4 py-3">
                        <button type="button" class="btn-submit-ticket w-100" data-bs-dismiss="modal">
                            Got it, let's go
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Help & More Info Modal — detailed guide on how the support request
         system works, opened from the "Help & more info" link in the topbar
         (see resources/views/layouts/app.blade.php). --}}
    <div class="modal fade" id="helpInfoModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header-gd d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">How <em>Support Requests</em> Work</h5>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <div class="modal-body px-4 py-4" style="max-height:70vh;overflow-y:auto">

                    <p style="font-size:13px;color:var(--tm);margin-bottom:20px">
                        Every support request you submit moves through five simple stages, from the moment you send it to the moment it's closed. Here's what each stage means and what happens behind the scenes.
                    </p>

                    {{-- Visual stage tracker — same look as the tracker on each request card --}}
                    <div class="progress-strip" style="margin-bottom:6px">
                        <div class="ps-step">
                            <div class="ps-dot done"><i class="bi bi-check"></i></div>
                            <div class="ps-lbl done">Submitted</div>
                        </div>
                        <div class="ps-line done"></div>
                        <div class="ps-step">
                            <div class="ps-dot done"><i class="bi bi-check"></i></div>
                            <div class="ps-lbl done">Scheduled</div>
                        </div>
                        <div class="ps-line done"></div>
                        <div class="ps-step">
                            <div class="ps-dot done"><i class="bi bi-check"></i></div>
                            <div class="ps-lbl done">In Progress</div>
                        </div>
                        <div class="ps-line done"></div>
                        <div class="ps-step">
                            <div class="ps-dot done"><i class="bi bi-check"></i></div>
                            <div class="ps-lbl done">Awaiting Your Confirmation</div>
                        </div>
                        <div class="ps-line done"></div>
                        <div class="ps-step">
                            <div class="ps-dot done"><i class="bi bi-check"></i></div>
                            <div class="ps-lbl done">Closed</div>
                        </div>
                    </div>
                    <div style="font-size:10px;color:var(--tm);text-align:center;margin-bottom:22px">
                        This is the exact same tracker shown on every one of your support request cards.
                    </div>

                    {{-- Stage-by-stage breakdown --}}
                    <div style="font-size:11px;font-weight:800;color:var(--gd);text-transform:uppercase;letter-spacing:.4px;margin-bottom:10px">
                        <i class="bi bi-list-check me-1"></i>What each stage means
                    </div>
                    <div class="d-flex flex-column gap-3 mb-4">
                        <div class="d-flex align-items-start gap-3">
                            <div class="onboard-icon"><i class="bi bi-send-check"></i></div>
                            <div>
                                <div style="font-weight:800;font-size:13px">1. Submitted</div>
                                <div style="font-size:12px;color:var(--tm)">
                                    Your request has been received. Our Helpdesk team reviews it, confirms they've got it, and works out the right category and who should handle it — a support specialist, an IT admin, or a manager, depending on the issue.
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3">
                            <div class="onboard-icon"><i class="bi bi-calendar-check"></i></div>
                            <div>
                                <div style="font-weight:800;font-size:13px">2. Scheduled</div>
                                <div style="font-size:12px;color:var(--tm)">
                                    A specific IT specialist has been assigned to your request, with a planned start time based on their current workload. Work hasn't started yet, but it's on someone's queue.
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3">
                            <div class="onboard-icon"><i class="bi bi-gear-fill"></i></div>
                            <div>
                                <div style="font-weight:800;font-size:13px">3. In Progress</div>
                                <div style="font-size:12px;color:var(--tm)">
                                    Your specialist is actively working on it. This stage also covers writing up the service report once the fix is done and reviewing that report — your request stays at "In Progress" through all of that, so there's nothing extra you need to do in the meantime.
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3">
                            <div class="onboard-icon"><i class="bi bi-hourglass-split"></i></div>
                            <div>
                                <div style="font-weight:800;font-size:13px">4. Awaiting Your Confirmation</div>
                                <div style="font-size:12px;color:var(--tm)">
                                    The work is done and the service report has been reviewed. Now it's your turn — review what was done and confirm it from your dashboard so the request can be closed.
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3">
                            <div class="onboard-icon"><i class="bi bi-check-circle-fill"></i></div>
                            <div>
                                <div style="font-weight:800;font-size:13px">5. Closed</div>
                                <div style="font-size:12px;color:var(--tm)">
                                    You've confirmed the resolution and the request is complete. It stays in your history if you ever need to look back at it.
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Emails --}}
                    <div style="font-size:11px;font-weight:800;color:var(--gd);text-transform:uppercase;letter-spacing:.4px;margin-bottom:10px">
                        <i class="bi bi-envelope me-1"></i>Emails you'll receive
                    </div>
                    <div class="mb-3 p-3" style="background:var(--ygl);border-radius:12px;border:1px solid var(--gl)">
                        <div class="d-flex align-items-start gap-2 mb-2">
                            <i class="bi bi-1-circle-fill" style="color:var(--gd);margin-top:2px"></i>
                            <div style="font-size:12px;color:var(--gd)">
                                <strong>Right after you submit</strong> — a confirmation that we received your request, plus an overview of what happens next.
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-2-circle-fill" style="color:var(--gd);margin-top:2px"></i>
                            <div style="font-size:12px;color:var(--gd)">
                                <strong>When it's ready for you</strong> — once the completed service report has been reviewed, moving your request to "Awaiting Your Confirmation."
                            </div>
                        </div>
                    </div>
                    <div style="font-size:11px;color:var(--tm);margin-bottom:20px">
                        That's it — just two emails. We keep it that way so your inbox isn't flooded; you can always check the live status anytime from this dashboard.
                    </div>

                    {{-- Tips --}}
                    <div style="font-size:11px;font-weight:800;color:var(--gd);text-transform:uppercase;letter-spacing:.4px;margin-bottom:10px">
                        <i class="bi bi-lightbulb me-1"></i>Good to know
                    </div>
                    <ul style="font-size:12px;color:var(--tm);padding-left:18px;margin-bottom:0">
                        <li class="mb-2">Use the <strong>chat</strong> on your request instead of emailing back and forth — your specialist gets notified instantly.</li>
                        <li class="mb-2">You can <strong>cancel</strong> a request yourself only while it's still at the "Submitted" stage, before it's scheduled to a specialist.</li>
                        <li class="mb-2">The tabs on your dashboard (Submitted, Scheduled, In Progress, Awaiting Your Confirmation, Closed) match the tracker above exactly — click any of them to filter your list.</li>
                        <li>Once a request reaches "Awaiting Your Confirmation," it stays there until you confirm — please review and confirm promptly so it can be closed out.</li>
                    </ul>

                </div>
                <div class="modal-footer border-top px-4 py-3">
                    <button type="button" class="btn-submit-ticket w-100" data-bs-dismiss="modal">
                        Got it
                    </button>
                </div>
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
                <form method="POST" action="{{ route($routePrefix.'tickets.store') }}" id="ticketForm" enctype="multipart/form-data">
                    @csrf
                    {{-- No priority/category fields here — this form has no picker for either.
                         Every self-filed ticket is created at a fixed baseline priority
                         server-side (TicketsController::DEFAULT_PRIORITY); Helpdesk/Supervisor
                         assign the real category and priority during classification. --}}
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

                            {{-- Ticket Number (read-only, auto-generated) — hidden on the employee
                                 modal per request; purely decorative, no form field involved. --}}
                            <div class="mb-3 p-3 rounded d-flex align-items-center gap-3 d-none"
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

                            {{-- Requestor (auto-filled, read-only) — hidden on the employee modal;
                                 the users_id hidden input still submits the value. --}}
                            <div class="mb-3 d-none">
                                <label class="form-label">Requestor</label>
                                <input type="text" class="form-control" value="{{ $requestor->name }}" readonly
                                    style="background:var(--ygl);color:var(--gd);font-weight:700">
                            </div>
                            <input type="hidden" id="users_id" name="users_id" value="{{ $requestor->id }}">

                            {{-- Auto-filled fields — hidden on the employee modal; inputs still
                                 render (display:none) so their name/value still submit with the form. --}}
                            <div class="row g-3 mb-3 d-none">
                                <div class="col-12">
                                    <label class="form-label">Position</label>
                                    <input type="text" class="form-control" id="mPosition" name="position"
                                        value="{{ $requestor->position }}" readonly
                                        style="background:var(--ygl);color:var(--gd);font-weight:700">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Business Unit</label>
                                    <input type="text" class="form-control" id="mBU" name="business_unit"
                                        value="{{ $requestor->business_units_name }}" readonly
                                        style="background:var(--ygl);color:var(--gd);font-weight:700">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Company</label>
                                    <input type="text" class="form-control" id="mCompany" name="company"
                                        value="{{ $requestor->company_name }}" readonly
                                        style="background:var(--ygl);color:var(--gd);font-weight:700">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Department</label>
                                    <input type="text" class="form-control" id="mDepartment" name="department"
                                        value="{{ $requestor->department_name }}" readonly
                                        style="background:var(--ygl);color:var(--gd);font-weight:700">
                                </div>
                            </div>

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
                            <div class="mb-3">
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

                            <div class="mb-1">
                                <label class="form-label">Supporting files <span style="font-weight:400;color:var(--tm)">(optional)</span></label>
                                <input type="file" class="form-control" id="mAttachments" name="attachments[]"
                                       multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.txt">
                                <div style="font-size:11px;color:var(--tm);margin-top:4px">
                                    Up to 5 files, 10MB each. Screenshots, documents, or logs that help explain the issue.
                                </div>
                                <div id="attachmentList" class="d-flex flex-column gap-1 mt-2"></div>
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

                                <div class="mb-2"><b>Date Acknowledged:</b> <span id="rv-ack-date">—</span></div>
                                <div class="mb-2"><b>Time Acknowledged:</b> <span id="rv-ack-time">—</span></div>

                                <hr>

                                <div class="mb-2"><b>Location:</b> <span id="rv-location">—</span></div>
                                <div class="mb-2"><b>Attachments:</b> <span id="rv-attachments">None</span></div>

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
                            <div class="text-start p-3 mb-3 rounded" style="background:var(--ygl);font-size:12.5px;color:var(--gd)">
                                <i class="bi bi-info-circle-fill me-1"></i>
                                <strong>Double-check what you submitted.</strong>
                                Incomplete or unclear details can delay how quickly your request gets picked up —
                                if you missed something, add it now from your support request's chat.
                            </div>
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

    {{-- Cancel Confirmation Modal --}}
    <div class="modal fade" id="cancelModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header-gd d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Cancel <em>Support Request</em></h5>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <div class="modal-body px-4 py-4">
                    <div class="p-3 mb-3 rounded"
                         style="background:rgba(226,75,74,.1);border:1px solid rgba(226,75,74,.3);color:#e24b4a;font-size:13px;font-weight:600">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Are you sure you want to cancel support request
                        <strong id="cancelTicketRef"></strong>?
                        This action cannot be undone.
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
                    <button class="btn-back-modal" data-bs-dismiss="modal">Go Back</button>
                    <form id="cancelForm" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn-cancel-ticket"
                                style="padding:10px 24px">
                            <i class="bi bi-x-circle me-1"></i>Yes, Cancel Support Request
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    {{-- ── Employee Chat Modal ── --}}
    <div class="modal fade" id="empChatModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width:480px">
            <div class="modal-content" style="border-radius:20px;overflow:hidden;border:none">
                <div class="modal-header-gd d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="mb-0">
                            <i class="bi bi-chat-dots-fill me-2" style="color:var(--yg)"></i>
                            Messages — <em id="empChatTicketRef">#TKT-0000</em>
                        </h5>
                    </div>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>

                <div id="empChatMessages"
                     style="height:360px;overflow-y:auto;padding:16px;background:#f8f8f4;display:flex;flex-direction:column;gap:12px;scroll-behavior:smooth">
                    <div class="text-center py-4" style="color:var(--tm);font-size:13px">
                        <div class="spinner-border spinner-border-sm me-2"></div>
                        Loading messages…
                    </div>
                </div>

                <div style="border-top:1.5px solid var(--bd);padding:12px 16px;background:#fff">
                    <div style="font-size:10px;font-weight:800;background:var(--ygl);color:var(--gd);border-radius:4px;padding:2px 8px;display:inline-block;margin-bottom:8px;text-transform:uppercase;letter-spacing:.3px">
                        {{ Auth::user()->role?->role_name ?? 'You' }}
                    </div>
                    <div class="d-flex gap-2 align-items-end">
                        <textarea id="empChatInput"
                                  placeholder="Type a message… (Enter to send)"
                                  rows="1"
                                  style="flex:1;border:1.5px solid var(--bd);border-radius:20px;padding:9px 14px;font-size:13px;resize:none;outline:none;font-family:'Nunito Sans',sans-serif;max-height:80px;overflow-y:auto;color:var(--gd);background:var(--cr);transition:border-color .2s"
                                  onkeydown="handleEmpChatKey(event)"
                                  onfocus="this.style.borderColor='var(--gl)';this.style.background='#fff'"
                                  onblur="this.style.borderColor='var(--bd)';this.style.background='var(--cr)'"></textarea>
                        <button onclick="sendEmpMessage()"
                                style="width:38px;height:38px;background:var(--gd);color:var(--yg);border:none;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:14px;flex-shrink:0">
                            <i class="bi bi-send-fill"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Feedback / Rating Modal ── --}}
    <div class="modal fade" id="feedbackModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header-gd d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Rate <em>Your Experience</em></h5>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <form method="POST" id="feedbackForm">
                    @csrf
                    <div class="modal-body px-4 py-4">

                        {{-- Ticket reference --}}
                        <div class="p-3 rounded mb-4"
                             style="background:var(--ygl);font-size:13px;color:var(--gd)">
                            <i class="bi bi-check-circle me-1"></i>
                            Ticket <strong id="feedbackTicketRef"></strong> has been resolved.
                            Please rate your experience.
                        </div>

                        {{-- Star rating --}}
                        <div class="mb-4 text-center">
                            <div class="mb-2"
                                 style="font-size:13px;font-weight:700;color:var(--gd)">
                                How satisfied are you with the resolution?
                            </div>
                            <div class="star-rating" id="starRating"
                                 style="justify-content:center">
                                <input type="radio" id="s5" name="rating" value="5">
                                <label for="s5" title="5 stars">★</label>
                                <input type="radio" id="s4" name="rating" value="4">
                                <label for="s4" title="4 stars">★</label>
                                <input type="radio" id="s3" name="rating" value="3">
                                <label for="s3" title="3 stars">★</label>
                                <input type="radio" id="s2" name="rating" value="2">
                                <label for="s2" title="2 stars">★</label>
                                <input type="radio" id="s1" name="rating" value="1">
                                <label for="s1" title="1 star">★</label>
                            </div>
                            <div id="ratingLabel"
                                 style="font-size:13px;font-weight:700;color:var(--tm);margin-top:8px">
                                Click a star to rate
                            </div>
                        </div>

                        {{-- Comments --}}
                        <div>
                            <label class="form-label">
                                Additional comments (optional)
                            </label>
                            <textarea class="form-control" name="comments" rows="3"
                                      placeholder="Tell us about your experience with the IT support team…"></textarea>
                        </div>

                    </div>
                    <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
                        <button type="button" class="btn-back-modal"
                                data-bs-dismiss="modal">Maybe Later</button>
                        <button type="submit" class="btn-submit-ticket"
                                id="btnSubmitFeedback" disabled>
                            <i class="bi bi-star me-1"></i>Submit Rating
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
    /* ── Dynamic SLA categories from DB ── */
    const slaCategories = @json($slaCategoriesJson);

    // Build a quick lookup: category name → subcategories
    const subCategoryMap = {};
    slaCategories.forEach(cat => {
        subCategoryMap[cat.name] = cat.subs;
    });

    /* ── Priority auto-suggest based on selected subcategory ── */
    const priorityMap = {};
    slaCategories.forEach(cat => {
        cat.subs.forEach(sub => {
            // Key = "CategoryName — SubcategoryName"
            priorityMap[cat.name + ' — ' + sub.name] = sub.priority;
        });
    });

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
                </div>
            `);
        } else {
            subs.forEach(sub => {
                const priColor = sub.priority === 'High' ? '#e24b4a' : (sub.priority === 'Medium' ? '#f5c842' : '#4a7c4a');
                const priBg    = sub.priority === 'High' ? '#fde8e8' : (sub.priority === 'Medium' ? '#fff4cc' : '#d4f0d4');
                $list.append(`
                    <div class="cat-sub-opt" data-sub="${sub.name}" data-priority="${sub.priority}">
                        <div class="sub-check"></div>
                        <span style="flex:1">${sub.name}</span>
                        <span style="font-size:10px;font-weight:800;background:${priBg};color:${priColor};border-radius:20px;padding:2px 8px;flex-shrink:0">
                            ${sub.priority}
                        </span>
                    </div>
                `);
            });
        }

        $('#subCategoryWrap').removeClass('d-none');
        $('#hCategory').val('');
    });

    /* ── Sub category selection — auto-set priority ── */
    $(document).on('click', '.cat-sub-opt', function () {
        $('.cat-sub-opt').removeClass('selected');
        $(this).addClass('selected');
        $(this).find('.sub-check').html('<i class="bi bi-check"></i>');
        $('.cat-sub-opt:not(.selected) .sub-check').html('');

        const mainCat  = $('.cat-main-opt.selected').data('cat') || '';
        const subCat   = $(this).data('sub')      || '';
        const priority = $(this).data('priority') || '';

        // ── Store as "Hardware — Laptop"
        $('#hCategory').val(mainCat + ' — ' + subCat);

        // ── Auto-select priority based on SLA rule
        if (priority) {
            $('.pri-opt').removeClass('selected');
            $(`.pri-opt[data-pri="${priority}"]`).addClass('selected');
            $('#hTicketType').val(priority);
        }
    });

    /* ══ GLOBAL CHAT FUNCTIONS — must be outside $(function(){}) ══ */

    let currentEmpChatTicketId = null;
    let empChatPollInterval    = null;

    window.openEmpChatModal = function (ticketId, ticketNumber) {
        currentEmpChatTicketId = ticketId;
        $('#empChatTicketRef').text('#' + ticketNumber);
        $('#empChatMessages').html(`
            <div class="text-center py-4" style="color:var(--tm);font-size:13px">
                <div class="spinner-border spinner-border-sm me-2"></div>
                Loading messages…
            </div>
        `);

        // ── Clear unread badge immediately on click
        $('#badge-' + ticketId).remove();

        new bootstrap.Modal('#empChatModal').show();
        loadEmpChatMessages();

        clearInterval(empChatPollInterval);
        empChatPollInterval = setInterval(loadEmpChatMessages, 3000);
    };

    window.sendEmpMessage = function () {
        const input = document.getElementById('empChatInput');
        const msg   = input.value.trim();
        if (!msg || !currentEmpChatTicketId) return;

        input.value = '';
        input.style.height = 'auto';

        fetch(`/tickets/${currentEmpChatTicketId}/messages`, {
            method:  'POST',
            headers: {
                'Content-Type':     'application/json',
                'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ message: msg }),
        })
        .then(r => r.json())
        .then(() => loadEmpChatMessages())
        .catch(err => console.error('Send error:', err));
    };

    window.handleEmpChatKey = function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            window.sendEmpMessage();
        }
        const ta = document.getElementById('empChatInput');
        setTimeout(() => {
            ta.style.height = 'auto';
            ta.style.height = Math.min(ta.scrollHeight, 80) + 'px';
        }, 0);
    };

    function loadEmpChatMessages() {
        if (!currentEmpChatTicketId) return;

        fetch(`/tickets/${currentEmpChatTicketId}/messages`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept':           'application/json',
            }
        })
        .then(r => r.json())
        .then(data => {
            const msgs = data.messages;
            const $box = document.getElementById('empChatMessages');
            if (!$box) return;

            const prevCount = $box.querySelectorAll('[data-msg-id]').length;

            if (!msgs || !msgs.length) {
                $box.innerHTML = `
                    <div class="text-center py-4" style="color:var(--tm)">
                        <i class="bi bi-chat-dots" style="font-size:32px;opacity:.3;display:block;margin-bottom:8px"></i>
                        <p style="font-size:13px;font-weight:600;margin:0">
                            No messages yet.<br>Start the conversation!
                        </p>
                    </div>`;
                return;
            }

            if (msgs.length === prevCount) return;

            // ── Remove badge since messages are now read
            $('#badge-' + currentEmpChatTicketId).remove();

            const avColors = {
                'IT Admin':      '#fde8e8',
                'IT Support Specialist': '#fff4cc',
                'Helpdesk':      '#d4f0d4',
                'Manager':       '#e8e0ff',
                'Employee':      '#e8f5b0',
            };
            const avTextColors = {
                'IT Admin':      '#8b1a1a',
                'IT Support Specialist': '#7a5a00',
                'Helpdesk':      '#2d5a2d',
                'Manager':       '#4a1a8a',
                'Employee':      '#1a3c1a',
            };

            let html = '';
            msgs.forEach(msg => {
                const avBg   = avColors[msg.role]    || '#e8f5b0';
                const avText = avTextColors[msg.role] || '#1a3c1a';
                const isMe   = msg.is_me;

                html += `
                    <div data-msg-id="${msg.id}"
                        style="display:flex;gap:8px;align-items:flex-end;${isMe ? 'flex-direction:row-reverse' : ''}">
                        <div style="width:28px;height:28px;border-radius:50%;background:${avBg};color:${avText};display:flex;align-items:center;justify-content:center;font-family:'Nunito',sans-serif;font-weight:900;font-size:10px;flex-shrink:0">
                            ${msg.initials}
                        </div>
                        <div style="max-width:75%">
                            <div style="font-size:10px;font-weight:700;color:var(--tm);margin-bottom:3px;${isMe ? 'text-align:right' : ''}">
                                ${isMe ? 'You' : escEmpHtml(msg.sender)}
                                <span style="font-size:9px;background:${avBg};color:${avText};border-radius:4px;padding:1px 5px;margin-left:4px;text-transform:uppercase;letter-spacing:.3px;font-weight:800">
                                    ${msg.role || 'User'}
                                </span>
                            </div>
                            <div style="padding:9px 13px;border-radius:16px;font-size:13px;line-height:1.5;word-break:break-word;${isMe
                                ? 'background:var(--gd);color:var(--yg);border-bottom-right-radius:4px'
                                : 'background:#fff;color:var(--gd);border-bottom-left-radius:4px;border:1.5px solid var(--bd)'}">
                                ${escEmpHtml(msg.message)}
                            </div>
                            <div style="font-size:10px;color:var(--tm);margin-top:3px;font-weight:600;${isMe ? 'text-align:right' : ''}">
                                ${msg.time_ago}
                            </div>
                        </div>
                    </div>
                `;
            });

            $box.innerHTML = html;
            $box.scrollTop = $box.scrollHeight;
        })
        .catch(err => console.error('Chat error:', err));
    }

    function escEmpHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    /* ══ FEEDBACK RATING LABELS ══ */
    const ratingLabels = {
        1: '😞 Poor — Issue not fully resolved',
        2: '😕 Fair — Partially resolved',
        3: '😐 Okay — Resolved but could be better',
        4: '😊 Good — Satisfied with the resolution',
        5: '🤩 Excellent — Outstanding support!'
    };

    window.openFeedbackModal = function (ticketId, ticketNumber) {
        $('#feedbackTicketRef').text('#' + ticketNumber);
        $('#feedbackForm').attr('action', '{{ url(rtrim($routePrefix, '.') . '/tickets') }}/' + ticketId + '/feedback');

        // Reset stars and button
        $('input[name="rating"]').prop('checked', false);
        $('#ratingLabel').text('Click a star to rate').css('color', 'var(--tm)');
        $('#btnSubmitFeedback').prop('disabled', true);
        $('textarea[name="comments"]').val('');

        new bootstrap.Modal('#feedbackModal').show();
    };

    /* ══ DOM-READY — everything that needs the DOM ══ */
    $(function () {

        /* ── Search debounce ── */
        let searchTimer;
        $('#searchInput').on('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => $('#searchForm').submit(), 500);
        });

        /* ── Cancel modal ── */
        window.confirmCancel = function (ticketId, ticketNumber) {
            $('#cancelTicketRef').text('#' + ticketNumber);
            $('#cancelForm').attr('action', '{{ url(rtrim($routePrefix, '.') . '/tickets') }}/' + ticketId + '/cancel');
            new bootstrap.Modal('#cancelModal').show();
        };

        /* ── Star rating ── */
        $(document).on('change', 'input[name="rating"]', function () {
            const val = parseInt($(this).val());
            $('#ratingLabel').text(ratingLabels[val] || '').css('color', 'var(--gd)');
            $('#btnSubmitFeedback').prop('disabled', false);
        });

        /* ── Stop polling when chat modal closes ── */
        $('#empChatModal').on('hidden.bs.modal', function () {
            clearInterval(empChatPollInterval);
            currentEmpChatTicketId = null;
        });

        /* ── Step wizard ── */
        let step = 1;
        const stepIds = ['fs2', 'fs3', 'fsSuccess']; // Details, Review, Success

        function showStep(n) {
            step = n;
            stepIds.forEach((id, i) => {
                $('#' + id).toggleClass('d-none', i !== n - 1);
            });

            $('#si1').toggleClass('active', n === 1).toggleClass('done', n > 1);
            $('#si2').toggleClass('active', n === 2).toggleClass('done', n > 2);
            $('#sl1').toggleClass('done', n > 1);

            $('#btnBack').css('visibility', n > 1 && n < 3 ? 'visible' : 'hidden');

            if (n === 2) {
                // Populate review screen from step 1 inputs
                $('#rv-subject').text($('#mSubject').val() || '—');
                $('#rv-desc').text($('#mDesc').val() || '—');
                $('#rv-details').text($('#mDetails').val() || '—');
                $('#rv-requestor').text($('input[name="users_id"]').prevAll('input.form-control').val() || $('.mb-3 input.form-control[readonly]').first().val() || '—');
                $('#rv-position').text($('#mPosition').val() || '—');
                $('#rv-bu').text($('#mBU').val() || '—');
                $('#rv-company').text($('#mCompany').val() || '—');
                $('#rv-department').text($('#mDepartment').val() || '—');
                $('#rv-location').text($('#mLocation').val() || '—');
                $('#rv-subject-preview').text($('#mSubject').val() || '—');
                $('#rv-desc-preview').text($('#mDesc').val() || '—');

                const attachedFiles = document.getElementById('mAttachments').files;
                $('#rv-attachments').text(
                    attachedFiles.length
                        ? attachedFiles.length + ' file' + (attachedFiles.length > 1 ? 's' : '')
                        : 'None'
                );

                $('#btnNext')
                    .removeClass('btn-continue')
                    .addClass('btn-submit-ticket')
                    .text('Submit support request');
            } else if (n === 3) {
                $('#mFooter').hide();
            } else {
                $('#btnNext')
                    .removeClass('btn-submit-ticket')
                    .addClass('btn-continue')
                    .text('Continue →');
            }
        }

        /* ── Attachment picker: client-side limits + preview list ── */
        const MAX_ATTACHMENTS = 5;
        const MAX_ATTACHMENT_MB = 10;

        $('#mAttachments').on('change', function () {
            const files = Array.from(this.files);
            const list  = $('#attachmentList').empty();

            if (files.length > MAX_ATTACHMENTS) {
                alert(`You can attach up to ${MAX_ATTACHMENTS} files. Only the first ${MAX_ATTACHMENTS} will be kept.`);
            }

            const oversize = files.find(f => f.size > MAX_ATTACHMENT_MB * 1024 * 1024);
            if (oversize) {
                alert(`"${oversize.name}" exceeds the ${MAX_ATTACHMENT_MB}MB limit and will be removed.`);
            }

            const kept = files
                .filter(f => f.size <= MAX_ATTACHMENT_MB * 1024 * 1024)
                .slice(0, MAX_ATTACHMENTS);

            // Rebuild the input's file list to only the valid/kept files
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

        /* ── Next / Submit ── */
        $('#btnNext').on('click', function () {

            if (step === 1) {
                // Validate Details panel
                if (!$('#mSubject').val().trim()) { alert('Please enter a subject.'); return; }
                if (!$('#mDesc').val().trim())    { alert('Please describe the issue.'); return; }
                if (!$('#users_id').val())      { alert('Please select a requestor.'); return; }

                $('#hAsset').val($('#mAsset').val());
                $('#hLocation').val($('#mLocation').val());

                showStep(2); // now review

            } else if (step === 2) {

                const mainCat = $('.cat-main-opt.selected').data('cat') || '';
                const subCat  = $('.cat-sub-opt.selected').data('sub')  || '';
                const pri     = $('.pri-opt.selected').data('pri')      || 'Medium';

                $('#hCategory').val(mainCat + ' — ' + subCat);
                $('#hTicketType').val(pri);

                const formData = new FormData(document.getElementById('ticketForm'));

                $.ajax({
                    url: $('#ticketForm').attr('action'),
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        $('#newTicketRef').text(response.ticket_number);
                        showStep(3);

                        setTimeout(() => {
                            window.location.href = '{{ route($routePrefix.'tickets.index') }}';
                        }, 3000);
                    },
                    error: function (xhr) {
                        const errors = xhr.responseJSON?.errors;
                        alert(errors ? Object.values(errors).flat().join('\n') : 'Something went wrong. Please try again.');
                    }
                });
            }
        });

        $('#btnBack').on('click', function () {
            if (step > 1 && step < 4) showStep(step - 1);
        });

        /* ── Reset modal on open ── */
        $('#ticketModal').on('show.bs.modal', function () {
            $('#mFooter').show();
            showStep(1);

            // Reset hidden fields
            $('#hCategory').val('');
            $('#hTicketType').val('Medium');
            $('#hAsset').val('');
            $('#hLocation').val('');

            // Reset category UI
            $('.cat-main-opt').removeClass('selected');
            $('.cat-sub-opt').removeClass('selected');
            $('#subCategoryWrap').addClass('d-none');
            $('#subCategoryList').empty();

            // Reset form fields
            $('#mSubject, #mDesc, #mDetails').val('');
            $('#mAsset, #mLocation').val('');

            // Reset attachments
            $('#mAttachments').val('');
            $('#attachmentList').empty();

            // Reset priority
            $('.pri-opt').removeClass('selected')
                .filter('.medium').addClass('selected');
        });

        /* ── Device & priority selection ── */
        $(document).on('click', '.device-opt', function () {
            $(this).closest('.row').find('.device-opt').removeClass('selected');
            $(this).addClass('selected');
        });
        $(document).on('click', '.pri-opt', function () {
            $(this).siblings().removeClass('selected');
            $(this).addClass('selected');
        });

    });

        /* ── Smart silent background refresh ── */
        let silentRefreshTimer = null;
        let isModalOpen        = false;

        function silentRefresh() {
            // Don't refresh if modal is open or tab is hidden
            if (isModalOpen || document.hidden) return;
            // Don't refresh if user is typing
            const active = document.activeElement;
            if (active && active.matches('input, textarea, select')) return;

            fetch(window.location.href, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept':           'text/html',
                }
            })
            .then(r => r.text())
            .then(html => {
                const parser = new DOMParser();
                const doc    = parser.parseFromString(html, 'text/html');

                // ── Ticket list
                const newList = doc.getElementById('ticketList');
                const curList = document.getElementById('ticketList');
                if (newList && curList) {
                    curList.innerHTML = newList.innerHTML;
                }

                // ── Available IT — online/offline status
                const newIt = doc.getElementById('itTeamList');
                const curIt = document.getElementById('itTeamList');
                if (newIt && curIt) {
                    curIt.innerHTML = newIt.innerHTML;
                }

                // ── Badge counts — only update if changed
                doc.querySelectorAll('.badge-count').forEach((newEl, i) => {
                    const curEl = document.querySelectorAll('.badge-count')[i];
                    if (curEl && curEl.textContent.trim() !== newEl.textContent.trim()) {
                        curEl.textContent = newEl.textContent;
                        curEl.classList.add('badge-pulse');
                        setTimeout(() => curEl.classList.remove('badge-pulse'), 600);
                    }
                });

                // ── Stat pill numbers
                doc.querySelectorAll('.stat-pill .num').forEach((newEl, i) => {
                    const curEl = document.querySelectorAll('.stat-pill .num')[i];
                    if (curEl && curEl.textContent.trim() !== newEl.textContent.trim()) {
                        curEl.textContent = newEl.textContent;
                    }
                });

                // ── Tab pill counts
                doc.querySelectorAll('.tab-pill').forEach((newEl, i) => {
                    const curEl = document.querySelectorAll('.tab-pill')[i];
                    if (curEl && curEl.textContent.trim() !== newEl.textContent.trim()) {
                        curEl.textContent = newEl.textContent;
                    }
                });
            })
            .catch(() => {}); // Silent fail
        }

        // ── Run every 30 seconds
        silentRefreshTimer = setInterval(silentRefresh, 30000);

        // ── Pause when any modal opens
        document.addEventListener('show.bs.modal', () => { isModalOpen = true; });
        document.addEventListener('hidden.bs.modal', () => { isModalOpen = false; });

        // ── Pause when tab is hidden, resume when visible
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                clearInterval(silentRefreshTimer);
            } else {
                silentRefresh(); // Refresh immediately when tab becomes visible
                silentRefreshTimer = setInterval(silentRefresh, 30000);
            }
        });

    /* ── Onboarding modal — shows once; any dismissal (button, X, backdrop,
           Escape) marks it seen so it never shows again for this employee. ── */
    @if($showOnboarding)
    (function () {
        const modalEl = document.getElementById('onboardingModal');
        if (!modalEl) return;

        new bootstrap.Modal(modalEl).show();

        modalEl.addEventListener('hidden.bs.modal', function () {
            fetch('{{ route('employee.onboarding.complete') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            }).catch(() => {}); // best-effort — worst case it just shows again next login
        }, { once: true });
    })();
    @endif
</script>
@endsection