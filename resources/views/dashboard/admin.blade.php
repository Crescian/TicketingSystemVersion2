@extends('layouts.admin')

@section('title', ($counts['awaiting_ack'] > 0 ? 'For Acknowledgment (' . $counts['awaiting_ack'] . ') — ' : '') . 'IT Admin — My Support Requests')

@section('nav-role-badge')
    <span class="role-badge-admin">
        <i class="bi bi-shield-fill me-1"></i>IT Admin
    </span>
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
    <h1><strong>ADMIN</strong> <em>SUPPORT REQUEST</em><br>WORKSPACE</h1>
@endsection
@section('hero-subtitle', 'Acknowledge, work, and resolve support requests assigned to you.')

@section('hero-stats')
    <div class="d-flex gap-2 flex-wrap">
        <div class="stat-pill esc">
            <span class="num">{{ $counts['awaiting_ack'] }}</span>
            <span class="lbl">For Acknowledgment</span>
        </div>
        <div class="stat-pill warn">
            <span class="num">{{ $counts['ready_start'] }}</span>
            <span class="lbl">Start Admin Request</span>
        </div>
        <div class="stat-pill open">
            <span class="num">{{ $counts['in_progress'] }}</span>
            <span class="lbl">In Progress Service Request</span>
        </div>
        <div class="stat-pill open">
            <span class="num">{{ $counts['in_progress_report'] }}</span>
            <span class="lbl">In Progress Service Report</span>
        </div>
        <div class="stat-pill esc">
            <span class="num">{{ $counts['escalated'] }}</span>
            <span class="lbl">Escalated</span>
        </div>
        <div class="stat-pill warn">
            <span class="num">{{ $counts['report_for_review'] }}</span>
            <span class="lbl">Report For Review</span>
        </div>
        <div class="stat-pill warn">
            <span class="num">{{ $counts['awaiting_requestor'] }}</span>
            <span class="lbl">Requestor Confirmation</span>
        </div>
        <div class="stat-pill done">
            <span class="num">{{ $counts['closed'] }}</span>
            <span class="lbl">Closed</span>
        </div>
    </div>
@endsection

@section('styles')
    {{-- Category/subcategory picker for the Request Re-classification modal —
         same rules used by the Classify & Assign modal on the other dashboards. --}}
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

    .pagination { flex-wrap: wrap; justify-content: center; gap: 6px; }
    .pagination li { margin: 2px; }
    .pagination .page-link { border-radius: 8px !important; padding: 6px 12px; font-size: 13px; }
    @media (max-width: 768px) {
        .pagination { font-size: 12px; }
        .pagination .page-link { padding: 4px 8px; }
    }
@endsection

{{-- ══ SIDEBAR ══ --}}
@section('sidebar')

    <x-recent-tickets-widget />

    {{-- Queue nav --}}
    <div class="sidebar-card mb-3">
        <div class="sidebar-head red">
            <i class="bi bi-shield-fill me-1"></i>My Queue
        </div>
        <ul class="list-group sidebar-menu rounded-0">
            @php
                $sideItems = [
                    ['key' => 'active',       'icon' => 'bi-grid',              'label' => 'Active',            'count' => $counts['active'],       'cls' => 'dark'],
                    ['key' => 'awaiting-ack', 'icon' => 'bi-hourglass-split',   'label' => 'For Acknowledgment',     'count' => $counts['awaiting_ack'], 'cls' => 'red', 'glow' => true],
                    ['key' => 'ready-start',  'icon' => 'bi-stopwatch',        'label' => 'Start Admin Request',    'count' => $counts['ready_start'],  'cls' => 'red'],
                    ['key' => 'in-progress',  'icon' => 'bi-gear-fill',        'label' => 'In Progress Service Request',       'count' => $counts['in_progress'],  'cls' => 'green'],
                    ['key' => 'in-progress-report', 'icon' => 'bi-file-earmark-text', 'label' => 'In Progress Service Report', 'count' => $counts['in_progress_report'], 'cls' => 'green'],
                    ['key' => 'escalated', 'icon' => 'bi-exclamation-triangle', 'label' => 'Escalated', 'count' => $counts['escalated'], 'cls' => 'red'],
                    ['key' => 'report-for-review', 'icon' => 'bi-clock-history', 'label' => 'Report For Review', 'count' => $counts['report_for_review'], 'cls' => 'green'],
                    ['key' => 'awaiting-requestor', 'icon' => 'bi-person-check', 'label' => 'Requestor Confirmation', 'count' => $counts['awaiting_requestor'], 'cls' => 'green'],
                    ['key' => 'closed',       'icon' => 'bi-check-circle',     'label' => 'Closed',            'count' => $counts['closed'],       'cls' => 'green'],
                ];
            @endphp
            @foreach($sideItems as $item)
                <li class="list-group-item {{ $status === $item['key'] ? 'active' : '' }} {{ (($item['glow'] ?? false) && $item['count'] > 0) ? 'queue-glow' : '' }}">
                    <a href="{{ route('admin.dashboard', ['status' => $item['key']]) }}"
                       class="d-flex justify-content-between align-items-center text-decoration-none">
                        <span><i class="bi {{ $item['icon'] }} me-2"></i>{{ $item['label'] }}</span>
                        <span class="badge-count {{ $item['cls'] }}">{{ $item['count'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>

    {{-- Work Hours Calendar --}}
    <div class="sidebar-card mb-3">
        <ul class="list-group sidebar-menu rounded-0">
            <li class="list-group-item">
                <a href="{{ route('admin.calendar') }}" class="d-flex align-items-center gap-2 text-decoration-none w-100">
                    <i class="bi bi-calendar3-week me-1"></i>My Work Hours Calendar
                </a>
            </li>
        </ul>
    </div>

    {{-- Peer IT Admins --}}
    <div class="sidebar-card mb-3">
        <div class="sidebar-head dark">IT Admin Team</div>
        <div>
            @php
                $selfInitials = strtoupper(substr(Auth::user()->name, 0, 1)) .
                    strtoupper(substr(Auth::user()->name, strpos(Auth::user()->name, ' ') + 1, 1));
                $selfActive = $counts['in_progress'];
            @endphp
            <div class="tech-row">
                <div class="tech-av admin">{{ $selfInitials }}</div>
                <div>
                    <div class="tech-name">{{ Auth::user()->name }} (You)</div>
                    <div class="tech-load">{{ $selfActive }} active support request{{ $selfActive !== 1 ? 's' : '' }}</div>
                </div>
                <div class="avail-dot {{ $selfActive > 0 ? 'busy' : 'free' }}"></div>
            </div>

            @foreach($technicians as $tech)
                @php
                    $initials = strtoupper(substr($tech->name, 0, 1)) .
                                strtoupper(substr($tech->name, strpos($tech->name, ' ') + 1, 1));
                @endphp
                <div class="tech-row">
                    <div class="tech-av normal">{{ $initials }}</div>
                    <div>
                        <div class="tech-name">{{ $tech->name }}</div>
                        <div class="tech-load">
                            {{ $tech->active_tickets }} active support request{{ $tech->active_tickets !== 1 ? 's' : '' }}
                        </div>
                    </div>
                    <div class="avail-dot {{ $tech->availability }}"></div>
                </div>
            @endforeach
        </div>
        <div class="p-2 px-3"
             style="font-size:11px;color:var(--tm);border-top:1px solid var(--bd)">
            <span class="me-3"><span class="avail-dot free d-inline-block me-1"></span>Available</span>
            <span class="me-3"><span class="avail-dot busy d-inline-block me-1"></span>Busy</span>
            <span><span class="avail-dot full d-inline-block me-1"></span>Full</span>
        </div>
    </div>

    {{-- System overview --}}
    <div class="sidebar-card">
        <div class="sidebar-head dark">This Week</div>
        <div>
            <div class="sys-stat">
                <span style="font-size:13px;font-weight:600;color:var(--tm)">Avg resolution (today)</span>
                <span class="sys-val ok">
                    {{ $systemStats['avg_resolution'] ? $systemStats['avg_resolution'] . 'h' : 'N/A' }}
                </span>
            </div>
            <div class="sys-stat">
                <span style="font-size:13px;font-weight:600;color:var(--tm)">Open support requests total</span>
                <span class="sys-val ok">{{ $systemStats['total_open'] }}</span>
            </div>
            <div class="sys-stat">
                <span style="font-size:13px;font-weight:600;color:var(--tm)">Satisfaction (today)</span>
                <span class="sys-val ok">
                    {{ $systemStats['avg_rating'] ? number_format($systemStats['avg_rating'], 1) . ' ⭐' : 'N/A' }}
                </span>
            </div>
            <div class="sys-stat">
                <span style="font-size:13px;font-weight:600;color:var(--tm)">Resolved this week</span>
                <span class="sys-val ok">{{ $weekStats['resolved'] }}</span>
            </div>
            <div class="sys-stat">
                <span style="font-size:13px;font-weight:600;color:var(--tm)">Avg time (week)</span>
                <span class="sys-val ok">
                    {{ $weekStats['avg_time'] ? number_format($weekStats['avg_time'], 1) . 'h' : 'N/A' }}
                </span>
            </div>
        </div>
    </div>

@endsection

{{-- ══ MAIN CONTENT ══ --}}
@section('content')

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
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-3">
            <i class="bi bi-exclamation-circle me-2"></i>
            @foreach($errors->all() as $error)
                {{ $error }}@if(!$loop->last)<br>@endif
            @endforeach
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @include('partials.schedule-conflict-modal')

    {{-- Awaiting-you attention banner — assigned tickets you haven't
         acknowledged yet stay flagged here (not dismissible) until you do. --}}
    @if($counts['awaiting_ack'] > 0)
        <a href="{{ route('admin.dashboard', ['status' => 'awaiting-ack']) }}"
           class="awaiting-banner">
            <span class="aw-icon"><i class="bi bi-hourglass-split"></i></span>
            <span>
                <div class="aw-title">
                    {{ $counts['awaiting_ack'] }}
                    {{ Str::plural('support request', $counts['awaiting_ack']) }} awaiting your acknowledgment
                </div>
                <div class="aw-sub">Acknowledge {{ $counts['awaiting_ack'] === 1 ? 'it' : 'them' }} so you can start work.</div>
            </span>
            <span class="aw-cta">Review Now <i class="bi bi-arrow-right ms-1"></i></span>
        </a>
    @endif

    {{-- Controls --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <span class="font-brand fw-900" style="font-size:22px">
            @php
                $labels = [
                    'active'       => 'Active Support Requests',
                    'awaiting-ack' => 'For Acknowledgment',
                    'ready-start'  => 'Start Admin Request',
                    'in-progress'  => 'In Progress Service Request',
                    'in-progress-report' => 'In Progress Service Report',
                    'escalated' => 'Escalated',
                    'report-for-review' => 'Report For Review',
                    'awaiting-requestor' => 'Requestor Confirmation',
                    'closed'       => 'Closed Support Requests',
                ];
            @endphp
            {{ $labels[$status] ?? 'Active Support Requests' }}
        </span>
        <form method="GET" action="{{ route('admin.dashboard') }}"
              class="d-flex gap-2 flex-wrap" id="searchForm">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="search-wrap">
                <i class="bi bi-search" style="color:var(--tm)"></i>
                <input type="text" name="search" id="searchInput"
                       placeholder="Search support requests…"
                       value="{{ $search }}" autocomplete="off">
            </div>
            <select class="sort-select" name="sort" onchange="this.form.submit()">
                <option value="priority" {{ $sort === 'priority' ? 'selected' : '' }}>Priority first</option>
                <option value="newest"   {{ $sort === 'newest'   ? 'selected' : '' }}>Newest first</option>
                <option value="oldest"   {{ $sort === 'oldest'   ? 'selected' : '' }}>Oldest first</option>
            </select>
        </form>
    </div>

    {{-- Tab pills --}}
    <div class="d-flex flex-wrap gap-2 mb-3">
        @php
            $tabs = [
                'active'       => ['label' => 'Active',       'count' => $counts['active'],       'red' => false],
                'awaiting-ack' => ['label' => 'For Acknowledgment', 'count' => $counts['awaiting_ack'], 'red' => true],
                'ready-start'  => ['label' => 'Start Admin Request','count' => $counts['ready_start'],  'red' => true],
                'in-progress'  => ['label' => 'In Progress Service Request',   'count' => $counts['in_progress'],  'red' => false],
                'in-progress-report' => ['label' => 'In Progress Service Report', 'count' => $counts['in_progress_report'], 'red' => false],
                'escalated' => ['label' => 'Escalated', 'count' => $counts['escalated'], 'red' => true],
                'report-for-review' => ['label' => 'Report For Review', 'count' => $counts['report_for_review'], 'red' => false],
                'awaiting-requestor' => ['label' => 'Requestor Confirmation', 'count' => $counts['awaiting_requestor'], 'red' => false],
                'closed'       => ['label' => 'Closed',        'count' => $counts['closed'],       'red' => false],
            ];
        @endphp
        @foreach($tabs as $key => $tab)
            <a href="{{ route('admin.dashboard', array_merge(request()->except('status'), ['status' => $key])) }}"
               class="tab-pill {{ $tab['red'] ? 'red-pill' : '' }} {{ $status === $key ? 'active' : '' }}">
                {{ $tab['label'] }} ({{ $tab['count'] }})
            </a>
        @endforeach
    </div>

    {{-- Ticket list --}}
    <div class="d-flex flex-column gap-3" id="ticketList">

        @forelse($tickets as $ticket)
            @php
                // 'Awaiting Administrator Acknowledgement' and 'Awaiting Administrator SLA
                // Start' both collapsed into the single 'Assigned' status (see
                // App\Support\TicketStatus) — acknowledging no longer moves status, it only
                // stamps tech_acknowledged_at (same field/pattern the Technician track uses),
                // so the two are now told apart by that timestamp instead of by status string.
                $isAwaitingAck = $ticket->status === 'Assigned' && is_null($ticket->tech_acknowledged_at);
                $isReadyStart  = $ticket->status === 'Assigned' && !is_null($ticket->tech_acknowledged_at);
                $isAdminInProgress = in_array($ticket->status, ['In Progress Service Request', 'In Progress Service Report'], true);

                $cardClass = match(true) {
                    $isAwaitingAck                                     => 'awaiting-ack',
                    $isReadyStart                                      => 'ready-start',
                    $ticket->status === 'In Progress Service Request'  => 'admin-progress',
                    $ticket->status === 'Closed Service Request'       => 'admin-progress',
                    $ticket->status === 'In Progress Service Report'   => 'admin-progress',
                    $ticket->status === 'Done Service Report'          => 'admin-progress',
                    $ticket->status === 'Report For Review'            => 'admin-progress',
                    $ticket->status === 'Approved Service Report'      => 'admin-progress',
                    $ticket->status === 'Requestor Confirmation'       => 'admin-progress',
                    $ticket->status === 'Closed'                       => 'closed',
                    default                                            => 'awaiting-ack'
                };
                $badgeClass = match(true) {
                    $isAwaitingAck                                     => 'bs-await-ack',
                    $isReadyStart                                      => 'bs-ready-start',
                    $ticket->status === 'In Progress Service Request'  => 'bs-admin-progress',
                    $ticket->status === 'Closed Service Request'       => 'bs-admin-progress',
                    $ticket->status === 'In Progress Service Report'   => 'bs-admin-progress',
                    $ticket->status === 'Done Service Report'          => 'bs-admin-progress',
                    $ticket->status === 'Report For Review'            => 'bs-admin-progress',
                    $ticket->status === 'Approved Service Report'      => 'bs-admin-progress',
                    $ticket->status === 'Requestor Confirmation'       => 'bs-admin-progress',
                    $ticket->status === 'Closed'                       => 'bs-closed',
                    default                                            => ''
                };
                // In Progress Service Report / Done Service Report / Report For Review /
                // Approved Service Report are isolated from the underlying "actively fixing
                // it" In Progress Service Request status (see TicketReportProgress) — real
                // statuses now, not a derived label.
                $badgeLabel = match(true) {
                    $isAwaitingAck                                     => '<i class="bi bi-hourglass-split me-1"></i>Awaiting Your Ack.',
                    $isReadyStart                                      => '<i class="bi bi-stopwatch me-1"></i>Start Admin Request',
                    $ticket->status === 'In Progress Service Request'  => '<i class="bi bi-gear-fill me-1"></i>In Progress Service Request',
                    $ticket->status === 'Closed Service Request'       => '<i class="bi bi-gear-fill me-1"></i>In Progress Service Request',
                    $ticket->status === 'In Progress Service Report'   => '<i class="bi bi-file-earmark-text me-1"></i>Preparing Report',
                    $ticket->status === 'Done Service Report'          => '<i class="bi bi-clock-history me-1"></i>Done Service Report',
                    $ticket->status === 'Report For Review'            => '<i class="bi bi-clock-history me-1"></i>Report For Review',
                    $ticket->status === 'Approved Service Report'      => '<i class="bi bi-clock-history me-1"></i>Approved Service Report',
                    $ticket->status === 'Requestor Confirmation'       => '<i class="bi bi-person-check me-1"></i>Requestor Confirmation',
                    $ticket->status === 'Closed'                       => '<i class="bi bi-check-circle me-1"></i>Closed',
                    default                                            => '● ' . $ticket->status
                };
                $priorityClass = match($ticket->ticket_type) {
                    'Critical' => 'pri-critical',
                    'High'     => 'pri-high',
                    'Medium'   => 'pri-medium',
                    'Low'      => 'pri-low',
                    default    => ''
                };

                $hoursOpen   = (int) $ticket->created_at->diffInHours(now());
                $isSlaBreach = $hoursOpen >= 24 && $ticket->status !== 'Closed';
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
                                Escalation Level {{ $ticket->escalation_level }}
                            </span>
                        @endif
                    </div>
                    <span class="badge-status {{ $badgeClass }}">{!! $badgeLabel !!}</span>
                </div>

                {{-- Title & desc --}}
                <div class="ticket-title mb-1">{{ $ticket->subject }}</div>
                <div class="ticket-desc mb-3">{{ $ticket->concern }}</div>

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
                <div class="d-flex flex-wrap gap-3 mb-3">
                    @if($ticket->user)
                        <span class="meta-item"><i class="bi bi-person"></i> {{ $ticket->user->name }}</span>
                    @endif
                    @if($ticket->user?->department)
                        <span class="meta-item">
                            <i class="bi bi-building"></i>
                            {{ $ticket->user->department->department_name }}
                        </span>
                    @endif
                    @if($ticket->asset)
                        <span class="meta-item"><i class="bi bi-laptop"></i> {{ $ticket->asset }}</span>
                    @endif
                    @if($ticket->location)
                        <span class="meta-item"><i class="bi bi-geo-alt"></i> {{ $ticket->location }}</span>
                    @endif
                    <span class="meta-item">
                        <i class="bi bi-clock" @if($isSlaBreach) style="color:var(--rd)" @endif></i>
                        <span @if($isSlaBreach) style="color:var(--rd);font-weight:700" @endif>
                            {{ $hoursOpen }}h open{{ $isSlaBreach ? ' — SLA breach' : '' }}
                        </span>
                    </span>

                    {{-- Scheduled start + estimated response/resolution windows —
                         same as the IT Support Specialist list, shown while the
                         request is Assigned (awaiting ack or ready to start). --}}
                    @if($ticket->status === 'Assigned' && $ticket->scheduled_start)
                        @php
                            $respMin = $ticket->effectiveResponseTimeMinutes();
                            $responseDue = $respMin ? $ticket->scheduled_start->copy()->addMinutes($respMin) : null;
                            $resolutionDue = $ticket->scheduled_end;
                        @endphp
                        <span class="meta-item" style="background:var(--ygl);border-radius:20px;padding:4px 12px;font-weight:800">
                            <i class="bi bi-clock"></i>
                            Scheduled to start {{ $ticket->scheduled_start->timezone('Asia/Manila')->format('g:i A, M d') }}
                            @if($ticket->is_overtime)<span style="color:#e24b4a"> (Overtime)</span>@endif
                        </span>
                        @if($responseDue)
                            <span class="meta-item" style="background:#e6f0ff;color:#1a4d8f;border-radius:20px;padding:4px 12px;font-weight:800"
                                  title="Estimated from the scheduled start time — the official SLA clock begins once you click Start">
                                <i class="bi bi-hourglass-split"></i>
                                Est. response by {{ $responseDue->timezone('Asia/Manila')->format('g:i A, M d') }}
                            </span>
                        @endif
                        @if($resolutionDue)
                            <span class="meta-item" style="background:#d4f0d4;color:#1a5a3a;border-radius:20px;padding:4px 12px;font-weight:800"
                                  title="Estimated from the scheduled start time — the official SLA clock begins once you click Start">
                                <i class="bi bi-check-circle"></i>
                                Est. resolution by {{ $resolutionDue->timezone('Asia/Manila')->format('g:i A, M d') }}
                            </span>
                        @endif
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

                    {{-- Assigned, not yet acknowledged --}}
                    @if($isAwaitingAck)
                        <button class="btn-resolve-a"
                                onclick="openAckModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-check2-circle me-1"></i>Acknowledge
                        </button>
                        <button class="btn-cancel-modal"
                                onclick="openDeclineModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-x-circle me-1"></i>Decline
                        </button>
                    @endif

                    {{-- Assigned, acknowledged — ready to start SLA --}}
                    @if($isReadyStart)
                        <button class="btn-resolve-a"
                                onclick="openStartModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}', '{{ $ticket->ticket_type }}', '{{ $ticket->effectiveResponseTimeMinutes() }}', '{{ $ticket->effectiveResolutionTimeMinutes() }}')">
                            <i class="bi bi-play-circle me-1"></i>Start Work
                        </button>
                        <button class="btn-cancel-modal"
                                onclick="openDeclineModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-x-circle me-1"></i>Decline
                        </button>
                    @endif

                    {{-- In Progress Service Request / In Progress Service Report — every
                         action here shares one button style (size + color) instead of each
                         action carrying its own accent, so the row reads as one uniform set.
                         Reassign is deliberately not offered here — only the Supervisor - IT
                         Admin can reassign a ticket to another admin. --}}
                    @if($isAdminInProgress)
                        {{-- Fix is done — mark it so, separate from writing up the report. --}}
                        @if($ticket->status === 'In Progress Service Request')
                            <form method="POST" action="{{ route('admin.tickets.start-report', $ticket) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn-admin-ip green">
                                    <i class="bi bi-check2 me-1"></i>Mark Fixed
                                </button>
                            </form>
                        @endif
                        {{-- Fix already marked done — now prepare & submit the service report. --}}
                        @if($ticket->status === 'In Progress Service Report')
                            <button class="btn-admin-ip green"
                                    onclick="openResolveModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}', '{{ $ticket->started_at?->toIso8601String() }}')">
                                <i class="bi bi-file-earmark-text me-1"></i>Prepare Service Report
                            </button>
                        @endif
                        <button class="btn-admin-ip red"
                                onclick="openEscModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-exclamation-triangle me-1"></i>Escalate
                        </button>
                        <button class="btn-admin-ip red"
                                onclick="openReclassifyModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-tags me-1"></i>Request Re-classification
                        </button>
                    @endif

                    {{-- Available on every non-closed status --}}
                    @if($ticket->status !== 'Closed')
                        <button class="btn-chat"
                                onclick="openAdminChatModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}', {{ Illuminate\Support\Js::from($ticket->user->name ?? 'Unknown') }}, {{ Illuminate\Support\Js::from($ticket->subject) }}, {{ Illuminate\Support\Js::from($ticket->concern) }})">
                            <i class="bi bi-chat-dots me-1"></i>Message
                            @php $unread = \App\Models\TicketMessage::where('ticket_id', $ticket->id)
                                ->where('sender_id', '!=', Auth::id())
                                ->where('is_read', false)->count(); @endphp
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

                    <a href="{{ route('admin.tickets.show', $ticket) }}" class="btn-view-hist" style="text-decoration:none">
                        <i class="bi bi-eye me-1"></i>View Support Request Details
                    </a>

                    <button class="btn-view-hist"
                            onclick="openHistoryModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                        <i class="bi bi-clock-history me-1"></i>View Full History
                    </button>
                </div>
            </div>
        @empty
            <div class="ticket-card p-5 text-center">
                <div style="font-size:48px;opacity:.3">✅</div>
                <div class="mt-3 font-brand fw-900" style="font-size:18px;color:var(--tm)">
                    No support requests in this view.
                </div>
                <div style="font-size:13px;color:var(--tm);margin-top:4px">
                    You're all caught up.
                </div>
            </div>
        @endforelse

    </div>

    @if($tickets->hasPages())
        <div class="mt-4">{{ $tickets->onEachSide(1)->links('pagination::bootstrap-5') }}</div>
    @endif

@endsection

{{-- ══ MODALS ══ --}}
@section('modals')

    {{-- Acknowledge modal --}}
    <div class="modal fade" id="ackModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-hdr-dark d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Acknowledge <em>Assignment</em></h5>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <form method="POST" id="ackForm">
                    @csrf
                    <div class="modal-body px-4 py-4">
                        <div class="info-box-green p-3 mb-3">
                            <i class="bi bi-check-circle me-1"></i>
                            Acknowledging <strong id="ackRef"></strong> confirms you've received this assignment.
                        </div>
                        <label class="form-label">Notes (optional)</label>
                        <textarea class="form-control" name="notes" rows="2"
                                  placeholder="Any initial notes…"></textarea>
                    </div>
                    <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
                        <button type="button" class="btn-cancel-modal" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-confirm">
                            <i class="bi bi-check2-circle me-1"></i>Acknowledge
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Start modal --}}
    <div class="modal fade" id="startModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-hdr-dark d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Start <em>Work</em></h5>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <form method="POST" id="startForm">
                    @csrf
                    <div class="modal-body px-4 py-4">
                        <div class="info-box-red p-3 mb-3">
                            <i class="bi bi-stopwatch me-1"></i>
                            Starting <strong id="startRef"></strong> begins the SLA resolution timer.
                        </div>
                        <div class="d-flex gap-2 flex-wrap mb-3">
                            <div style="flex:1;min-width:100px;background:var(--ygl);border-radius:10px;padding:10px 12px;text-align:center">
                                <div style="font-size:10px;font-weight:800;color:var(--tm);text-transform:uppercase;letter-spacing:.4px">Priority</div>
                                <div class="font-brand fw-900" id="startPriority" style="font-size:14px;color:var(--gd)">—</div>
                            </div>
                            <div style="flex:1;min-width:100px;background:var(--ygl);border-radius:10px;padding:10px 12px;text-align:center">
                                <div style="font-size:10px;font-weight:800;color:var(--tm);text-transform:uppercase;letter-spacing:.4px">Response Time</div>
                                <div class="font-brand fw-900" id="startResponseTime" style="font-size:14px;color:var(--gd)">—</div>
                            </div>
                            <div style="flex:1;min-width:100px;background:var(--ygl);border-radius:10px;padding:10px 12px;text-align:center">
                                <div style="font-size:10px;font-weight:800;color:var(--tm);text-transform:uppercase;letter-spacing:.4px">Resolution Time</div>
                                <div class="font-brand fw-900" id="startResolutionTime" style="font-size:14px;color:var(--gd)">—</div>
                            </div>
                        </div>
                        <label class="form-label">Notes (optional)</label>
                        <textarea class="form-control" name="notes" rows="2"
                                  placeholder="Plan of action…"></textarea>
                    </div>
                    <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
                        <button type="button" class="btn-cancel-modal" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-confirm red">
                            <i class="bi bi-play-circle me-1"></i>Start Work
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Decline modal --}}
    <div class="modal fade" id="declineModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-hdr-red d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Decline <em>Support Request</em></h5>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <form method="POST" id="declineForm">
                    @csrf
                    <div class="modal-body px-4 py-4">
                        <div class="info-box-red p-3 mb-3">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Declining <strong id="declineRef"></strong> returns it to the Admin Supervisor's classification queue.
                        </div>
                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="reason" rows="2" required
                                  placeholder="Why are you declining this support request?"></textarea>
                    </div>
                    <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
                        <button type="button" class="btn-cancel-modal" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-confirm red">
                            <i class="bi bi-x-circle me-1"></i>Decline Support Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Request Re-classification modal --}}
    <div class="modal fade" id="reclassifyModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-hdr-dark d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Request Re-classification — <em id="rcTicketRef">#TKT-0000</em></h5>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <form method="POST" id="reclassifyForm">
                    @csrf
                    <input type="hidden" name="sla_rule_id" id="rcSlaRuleId">

                    <div class="modal-body px-4 py-4">
                        <div class="info-box-red p-3 mb-3">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            This ticket will be paused and sent to your Admin Supervisor for approval. If
                            approved, it re-enters the classify &amp; assign queue with your proposed
                            category as the default. If rejected, it's returned to you unchanged.
                        </div>

                        <label class="form-label mb-2">Proposed Category</label>
                        <div class="d-flex flex-wrap gap-2 mb-3" id="rcCategoryList"></div>

                        <div id="rcSubWrap" class="d-none">
                            <label class="form-label mb-2">Subcategory &amp; Priority</label>
                            <div class="d-flex flex-column gap-2 mb-3" id="rcSubList"></div>
                        </div>

                        <div id="rcOverrideWrap" class="d-none mb-3">
                            <label class="form-label mb-2">
                                SLA &amp; Priority
                                <span style="font-weight:400;color:var(--tm)">(defaults from the rule above — adjust if needed)</span>
                            </label>
                            <div class="d-flex gap-2 flex-wrap">
                                <div style="flex:1;min-width:110px">
                                    <label class="form-label" style="font-size:11px">Priority</label>
                                    <select class="form-select form-select-sm" name="priority" id="rcPriority">
                                        <option value="Critical">Critical</option>
                                        <option value="High">High</option>
                                        <option value="Medium">Medium</option>
                                        <option value="Low">Low</option>
                                    </select>
                                </div>
                                <div style="flex:1;min-width:130px">
                                    <label class="form-label" style="font-size:11px">Response Time (min)</label>
                                    <input type="number" class="form-control form-control-sm" name="response_time_minutes"
                                           id="rcResponseTime" min="5" max="43200">
                                </div>
                                <div style="flex:1;min-width:130px">
                                    <label class="form-label" style="font-size:11px">Resolution Time (min)</label>
                                    <input type="number" class="form-control form-control-sm" name="resolution_time_minutes"
                                           id="rcResolutionTime" min="5" max="43200">
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="form-label">
                                Reason for re-classification <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" name="reason" rows="3" required
                                      placeholder="Explain why this ticket is miscategorized…"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
                        <button type="button" class="btn-cancel-modal" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-confirm red">
                            <i class="bi bi-tags me-1"></i>Submit for Approval
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
                <div class="modal-hdr-dark d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Resolve <em>& Close</em></h5>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <form method="POST" id="resolveForm" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body px-4 py-4">
                        <div class="info-box-green p-3 mb-3">
                            <i class="bi bi-check-circle me-1"></i>
                            Resolving <strong id="resolveRef"></strong> —
                            this sends the service report to your Supervisor for review. It will
                            move to <strong>Closed</strong> once fully validated.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                                Service Type <span class="text-danger">*</span>
                            </label>
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
                            <label class="form-label">Resolution summary <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="resolution_notes" rows="3" required
                                      placeholder="Describe what was done, root cause, and how it was resolved…"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Root cause category</label>
                            <select class="form-select" name="root_cause" required>
                                <option value="Hardware failure — replacement required">Hardware failure — replacement required</option>
                                <option value="Software / configuration error">Software / configuration error</option>
                                <option value="Network infrastructure issue">Network infrastructure issue</option>
                                <option value="User access / permissions">User access / permissions</option>
                                <option value="Third-party vendor issue">Third-party vendor issue</option>
                                <option value="Human error">Human error</option>
                                <option value="Others">Others</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                                Findings & Analysis <span style="font-weight:400;color:var(--tm)">(optional)</span>
                            </label>
                            <textarea class="form-control" name="findings"
                                      rows="2"
                                      placeholder="Root cause, diagnostics, what was found…"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                                Other Observation / Recommendation <span style="font-weight:400;color:var(--tm)">(optional)</span>
                            </label>
                            <textarea class="form-control" name="recommendation"
                                      rows="2"
                                      placeholder="Follow-up suggestions, preventive advice…"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Time spent</label>
                            <div class="p-2 px-3 rounded d-flex align-items-center gap-2"
                                 style="background:var(--ygl);font-weight:800;color:var(--gd)">
                                <i class="bi bi-stopwatch"></i>
                                <span id="resolveTimeSpent">—</span>
                                <span style="font-weight:600;font-size:11px;color:var(--tm)">(since you started this ticket)</span>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Supporting files <span style="font-weight:400;color:var(--tm)">(optional)</span></label>
                            <input type="file" class="form-control" id="rAttachments" name="attachments[]"
                                   multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.txt">
                            <div style="font-size:11px;color:var(--tm);margin-top:4px">
                                Up to 5 files, 10MB each. Screenshots, logs, or documents that support the resolution.
                            </div>
                            <div id="resolveAttachmentList" class="d-flex flex-column gap-1 mt-2"></div>
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

    {{-- Escalate modal --}}
    <div class="modal fade" id="escModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-hdr-red d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Escalate to <em>Admin Supervisor</em></h5>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <form method="POST" id="escForm">
                    @csrf
                    <div class="modal-body px-4 py-4">
                        <div class="info-box-red p-3 mb-3">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            <strong id="escRef"></strong> — Escalating hands this ticket back to your
                            Admin Supervisor for reclassification. It will be unassigned from you.
                        </div>
                        <label class="form-label">Reason for escalation <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="reason" rows="3" required
                                  placeholder="Why does this need Admin Supervisor attention?"></textarea>
                    </div>
                    <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
                        <button type="button" class="btn-cancel-modal" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-confirm red">
                            <i class="bi bi-exclamation-triangle me-1"></i>Confirm Escalation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- History modal --}}
    <div class="modal fade" id="historyModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-hdr-dark d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Full Support Request <em>History</em></h5>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <div class="modal-body px-4 py-4">
                    <div class="mb-3 p-2 px-3 rounded" style="background:var(--ygl);font-size:13px">
                        <strong id="historyRef"></strong> — Complete audit trail of all status changes and actions.
                    </div>
                    <div id="historyAttachments" class="mb-3"></div>
                    <div id="historyTimeline">
                        <div class="text-center py-4" style="color:var(--tm)">
                            <div class="spinner-border spinner-border-sm me-2"></div>
                            Loading history…
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3 d-flex justify-content-end">
                    <button class="btn-confirm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Admin Chat Modal (unchanged from before) --}}
    <div class="modal fade" id="adminChatModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width:480px">
            <div class="modal-content" style="border-radius:20px;overflow:hidden;border:none">
                <div class="modal-hdr-dark d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="mb-0">
                            <i class="bi bi-chat-dots-fill me-2" style="color:var(--yg)"></i>
                            Messages — <em id="adminChatTicketRef">#TKT-0000</em>
                        </h5>
                    </div>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <div style="padding:10px 16px;background:var(--ygl);border-bottom:1.5px solid var(--bd);font-size:12px">
                    <div style="font-weight:800;color:var(--gd)">
                        <i class="bi bi-person-fill me-1"></i><span id="adminChatRequestor">—</span>
                    </div>
                    <div style="font-weight:700;color:var(--tm);margin-top:2px" id="adminChatSubject"></div>
                    <div style="color:var(--tm);margin-top:2px;max-height:54px;overflow-y:auto" id="adminChatConcern"></div>
                </div>
                <div id="adminChatMessages"
                     style="height:360px;overflow-y:auto;padding:16px;background:#f8f8f4;display:flex;flex-direction:column;gap:12px;scroll-behavior:smooth">
                    <div class="text-center py-4" style="color:var(--tm);font-size:13px">
                        <div class="spinner-border spinner-border-sm me-2"></div>
                        Loading messages…
                    </div>
                </div>
                <div style="border-top:1.5px solid var(--bd);padding:12px 16px;background:#fff">
                    <div style="font-size:10px;font-weight:800;background:var(--rdl);color:var(--rd);border-radius:4px;padding:2px 8px;display:inline-block;margin-bottom:8px;text-transform:uppercase;letter-spacing:.3px">
                        IT Admin
                    </div>
                    <div class="d-flex gap-2 align-items-end">
                        <textarea id="adminChatInput" placeholder="Type a message… (Enter to send)" rows="1"
                                  style="flex:1;border:1.5px solid var(--bd);border-radius:20px;padding:9px 14px;font-size:13px;resize:none;outline:none;font-family:'Nunito Sans',sans-serif;max-height:80px;overflow-y:auto;color:var(--gd);background:var(--cr);transition:border-color .2s"
                                  onkeydown="handleAdminChatKey(event)"
                                  onfocus="this.style.borderColor='var(--gl)';this.style.background='#fff'"
                                  onblur="this.style.borderColor='var(--bd)';this.style.background='var(--cr)'"></textarea>
                        <button onclick="sendAdminMessage()"
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
$(function () {

    /* ── Search debounce ── */
    let searchTimer;
    $('#searchInput').on('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => $('#searchForm').submit(), 500);
    });

    /* ── Tech selection ── */
    $(document).on('click', '.att-preview-trigger', function () {
        openAttachmentPreview($(this).data('id'), $(this).data('name'), $(this).data('mime'));
    });

    /* ── Acknowledge modal ── */
    window.openAckModal = function (ticketId, ticketNumber) {
        $('#ackRef').text('#' + ticketNumber);
        $('#ackForm').attr('action', '/admin/tickets/' + ticketId + '/acknowledge');
        new bootstrap.Modal('#ackModal').show();
    };

    /* ── Start modal ── */
    window.openStartModal = function (ticketId, ticketNumber, priority, responseMinutes, resolutionMinutes) {
        $('#startRef').text('#' + ticketNumber);
        $('#startForm').attr('action', '/admin/tickets/' + ticketId + '/start');
        $('#startPriority').text(priority || '—');
        $('#startResponseTime').text(responseMinutes ? responseMinutes + 'm' : 'N/A');
        $('#startResolutionTime').text(resolutionMinutes ? resolutionMinutes + 'm' : 'N/A');
        new bootstrap.Modal('#startModal').show();
    };

    /* ── Decline modal ── */
    window.openDeclineModal = function (ticketId, ticketNumber) {
        $('#declineRef').text('#' + ticketNumber);
        $('#declineForm').attr('action', '/admin/tickets/' + ticketId + '/decline');
        new bootstrap.Modal('#declineModal').show();
    };

    /* ── Resolve modal ── */
    let resolveTimeSpentTimer = null;

    window.openResolveModal = function (ticketId, ticketNumber, startedAt) {
        $('#resolveRef').text('#' + ticketNumber);
        $('#resolveForm').attr('action', '/admin/tickets/' + ticketId + '/resolve');
        $('#rAttachments').val('');
        $('#resolveAttachmentList').empty();
        $('#resolveForm textarea[name="resolution_notes"], #resolveForm textarea[name="findings"], #resolveForm textarea[name="recommendation"]').val('');
        $('#resolveForm input[name="service_type"][value="Onsite"]').prop('checked', true);
        $('#resolveForm select[name="root_cause"]').prop('selectedIndex', 0);

        clearInterval(resolveTimeSpentTimer);
        const startedMs = startedAt ? new Date(startedAt).getTime() : null;

        const renderTimeSpent = () => {
            if (!startedMs) { $('#resolveTimeSpent').text('—'); return; }
            const totalMinutes = Math.max(0, Math.floor((Date.now() - startedMs) / 60000));
            const h = Math.floor(totalMinutes / 60);
            const m = totalMinutes % 60;
            $('#resolveTimeSpent').text(h > 0 ? `${h}h ${m}m` : `${m}m`);
        };

        renderTimeSpent();
        resolveTimeSpentTimer = setInterval(renderTimeSpent, 1000);

        new bootstrap.Modal('#resolveModal').show();
    };

    $('#resolveModal').on('hidden.bs.modal', function () {
        clearInterval(resolveTimeSpentTimer);
    });

    /* ── Resolve modal attachment picker: client-side limits + preview list ── */
    const RESOLVE_MAX_ATTACHMENTS = 5;
    const RESOLVE_MAX_ATTACHMENT_MB = 10;

    $('#rAttachments').on('change', function () {
        const files = Array.from(this.files);
        const list  = $('#resolveAttachmentList').empty();

        if (files.length > RESOLVE_MAX_ATTACHMENTS) {
            alert(`You can attach up to ${RESOLVE_MAX_ATTACHMENTS} files. Only the first ${RESOLVE_MAX_ATTACHMENTS} will be kept.`);
        }

        const oversize = files.find(f => f.size > RESOLVE_MAX_ATTACHMENT_MB * 1024 * 1024);
        if (oversize) {
            alert(`"${oversize.name}" exceeds the ${RESOLVE_MAX_ATTACHMENT_MB}MB limit and will be removed.`);
        }

        const kept = files
            .filter(f => f.size <= RESOLVE_MAX_ATTACHMENT_MB * 1024 * 1024)
            .slice(0, RESOLVE_MAX_ATTACHMENTS);

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

    /* ── Escalate modal ── */
    window.openEscModal = function (ticketId, ticketNumber) {
        $('#escRef').text('#' + ticketNumber);
        $('#escForm').attr('action', '/admin/tickets/' + ticketId + '/escalate');
        new bootstrap.Modal('#escModal').show();
    };

    /* ── Request Re-classification modal ── */
    const slaCategories = @json($slaCategoriesJson);

    window.openReclassifyModal = function (ticketId, ticketNumber) {
        $('#rcTicketRef').text('#' + ticketNumber);
        $('#reclassifyForm').attr('action', '/admin/tickets/' + ticketId + '/request-reclassification');
        $('#rcSlaRuleId').val('');
        $('#rcSubWrap, #rcOverrideWrap').addClass('d-none');
        $('#rcSubList').empty();
        $('#rcResponseTime, #rcResolutionTime').val('');
        $('#reclassifyForm textarea[name="reason"]').val('');
        $('#rcCategoryList .cat-main-opt').removeClass('selected');

        const $catList = $('#rcCategoryList').empty();
        slaCategories.forEach(cat => {
            $catList.append(`<div class="cat-main-opt" data-cat-id="${cat.id}">${cat.name}</div>`);
        });

        new bootstrap.Modal('#reclassifyModal').show();
    };

    $(document).on('click', '#rcCategoryList .cat-main-opt', function () {
        $('#rcCategoryList .cat-main-opt').removeClass('selected');
        $(this).addClass('selected');

        const catId = $(this).data('cat-id');
        const cat = slaCategories.find(c => c.id === catId);
        const $subList = $('#rcSubList').empty();

        if (!cat || !cat.subs.length) {
            $subList.append(`<div style="font-size:12px;color:var(--tm)">No SLA rules defined for this category yet.</div>`);
        } else {
            cat.subs.forEach(sub => {
                const priColor = sub.priority === 'Critical' ? '#8b0000' : (sub.priority === 'High' ? '#e24b4a' : (sub.priority === 'Medium' ? '#f5c842' : '#4a7c4a'));
                $subList.append(`<div class="cat-sub-opt" data-rule-id="${sub.rule_id}"
                     data-priority="${sub.priority}" data-response="${sub.response}" data-resolution="${sub.resolution}">
                    <div class="sub-check"></div>
                    <div style="flex:1">
                        <div>${sub.name}</div>
                        ${sub.description ? `<div style="font-size:11px;font-weight:400;color:var(--tm);margin-top:2px">${escAdminHtml(sub.description)}</div>` : ''}
                    </div>
                    <span style="font-size:10px;font-weight:800;color:${priColor}">${sub.priority} · ${sub.resolution}m SLA</span>
                </div>`);
            });
        }
        $('#rcSubWrap').removeClass('d-none');
        $('#rcOverrideWrap').addClass('d-none');
    });

    $(document).on('click', '#rcSubList .cat-sub-opt', function () {
        $('#rcSubList .cat-sub-opt').removeClass('selected');
        $(this).addClass('selected');
        $('#rcSlaRuleId').val($(this).data('rule-id'));

        $('#rcPriority').val($(this).data('priority'));
        $('#rcResponseTime').val($(this).data('response'));
        $('#rcResolutionTime').val($(this).data('resolution'));
        $('#rcOverrideWrap').removeClass('d-none');
    });

    $('#reclassifyForm').on('submit', function (e) {
        if (!$('#rcSlaRuleId').val()) {
            e.preventDefault();
            alert('Please select a subcategory.');
        }
    });

    /* ── History modal ── */
    window.openHistoryModal = function (ticketId, ticketNumber) {
        $('#historyRef').text('#' + ticketNumber);
        $('#historyAttachments').html('');
        $('#historyTimeline').html(`
            <div class="text-center py-4" style="color:var(--tm)">
                <div class="spinner-border spinner-border-sm me-2"></div>
                Loading history…
            </div>
        `);
        new bootstrap.Modal('#historyModal').show();

        fetch('/admin/tickets/' + ticketId + '/history')
            .then(r => r.json())
            .then(data => {
                const attachments = data.attachments || [];
                if (attachments.length) {
                    const formatSize = bytes => {
                        if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
                        if (bytes >= 1024) return (bytes / 1024).toFixed(1) + ' KB';
                        return bytes + ' B';
                    };
                    const viewableMimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'text/plain'];
                    let attHtml = `
                        <div style="font-size:12px;font-weight:800;color:var(--tm);margin-bottom:6px">
                            <i class="bi bi-paperclip me-1"></i>Attachments
                        </div>
                        <div class="d-flex flex-column gap-1">
                    `;
                    attachments.forEach(a => {
                        const safeName = escAdminHtml(a.original_name);
                        attHtml += `
                            <div class="d-flex align-items-center gap-2 p-2" style="background:var(--ygl);border-radius:8px;font-size:12px">
                                <i class="bi bi-file-earmark-text" style="color:var(--tm)"></i>
                                <span style="font-weight:600;color:var(--gd)">${safeName}</span>
                                <span style="color:var(--tm)">(${formatSize(a.size)})</span>
                                <div class="ms-auto d-flex gap-2">
                                    ${viewableMimes.includes(a.mime_type)
                                        ? `<button type="button" class="att-preview-trigger text-decoration-none border-0 bg-transparent p-0" data-id="${a.id}" data-name="${safeName}" data-mime="${a.mime_type}" style="color:var(--gd);font-weight:700"><i class="bi bi-eye me-1"></i>View</button>`
                                        : `<a href="/attachments/${a.id}/view" target="_blank" rel="noopener" class="text-decoration-none" style="color:var(--gd);font-weight:700"><i class="bi bi-box-arrow-up-right me-1"></i>Open in New Tab</a>`}
                                    <a href="/attachments/${a.id}/download" class="text-decoration-none" style="color:var(--tm);font-weight:700"><i class="bi bi-download me-1"></i>Download</a>
                                </div>
                            </div>
                        `;
                    });
                    attHtml += '</div>';
                    $('#historyAttachments').html(attHtml);
                }

                const histories = data.status_histories || [];
                if (!histories.length) {
                    $('#historyTimeline').html('<div style="color:var(--tm);font-size:13px">No history available.</div>');
                    return;
                }
                const iconMap = {
                    // Current status values (App\Support\TicketStatus) going forward.
                    'Assigned':                    { icon: 'bi-hourglass-split',   cls: 'assigned'  },
                    'In Progress Service Request': { icon: 'bi-gear-fill',         cls: 'working'   },
                    'Closed Service Request':      { icon: 'bi-gear-fill',         cls: 'working'   },
                    'In Progress Service Report':  { icon: 'bi-file-earmark-text', cls: 'working'   },
                    'Done Service Report':         { icon: 'bi-clock-history',     cls: 'working'   },
                    'Report For Review':           { icon: 'bi-clock-history',     cls: 'working'   },
                    'Approved Service Report':     { icon: 'bi-clock-history',     cls: 'working'   },
                    'Requestor Confirmation':      { icon: 'bi-person-check',      cls: 'working'   },
                    'Closed':                      { icon: 'bi-check-circle',      cls: 'resolved'  },
                    // Legacy status strings — pre-migration ticket_status_histories rows keep
                    // these forever (historical audit log, never rewritten), so map them too.
                    'Awaiting Administrator Acknowledgement': { icon: 'bi-hourglass-split', cls: 'assigned'  },
                    'Awaiting Administrator SLA Start':       { icon: 'bi-stopwatch',        cls: 'working'   },
                    'Admin In Progress':                      { icon: 'bi-gear-fill',        cls: 'working'   },
                };
                let html = '';
                histories.forEach(h => {
                    const map  = iconMap[h.new_status] || { icon: 'bi-circle', cls: 'assigned' };
                    const date = new Date(h.changed_at).toLocaleString('en-PH', {
                        month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit'
                    });
                    const by = h.changed_by?.name ?? 'System';
                    html += `
                        <div class="hist-item">
                            <div class="hist-icon ${map.cls}"><i class="bi ${map.icon}"></i></div>
                            <div>
                                <div class="hist-time">${date}</div>
                                <div class="hist-title">Status → <strong>${h.new_status}</strong> — by ${by}</div>
                                <div class="hist-desc">${h.notes ?? ''}</div>
                            </div>
                        </div>
                    `;
                });
                $('#historyTimeline').html(html);
            })
            .catch(() => {
                $('#historyTimeline').html('<div style="color:#e24b4a">Failed to load history.</div>');
            });
    };

    /* ── Stop polling when chat modal closes ── */
    $('#adminChatModal').on('hidden.bs.modal', function () {
        clearInterval(adminChatPollInterval);
        currentAdminChatTicketId = null;
    });

});

/* ══ GLOBAL ADMIN CHAT FUNCTIONS ══ */
let currentAdminChatTicketId = null;
let adminChatPollInterval    = null;

window.openAdminChatModal = function (ticketId, ticketNumber, requestorName, subject, concern) {
    currentAdminChatTicketId = ticketId;
    $('#adminChatTicketRef').text('#' + ticketNumber);
    $('#adminChatRequestor').text(requestorName || '—');
    $('#adminChatSubject').text(subject || '');
    $('#adminChatConcern').text(concern || '');
    $('#badge-' + ticketId).remove();
    $('#adminChatMessages').html(`
        <div class="text-center py-4" style="color:var(--tm);font-size:13px">
            <div class="spinner-border spinner-border-sm me-2"></div>
            Loading messages…
        </div>
    `);
    new bootstrap.Modal('#adminChatModal').show();
    loadAdminChatMessages();
    clearInterval(adminChatPollInterval);
    adminChatPollInterval = setInterval(loadAdminChatMessages, 3000);
};

window.sendAdminMessage = function () {
    const input = document.getElementById('adminChatInput');
    const msg   = input.value.trim();
    if (!msg || !currentAdminChatTicketId) return;

    input.value = '';
    input.style.height = 'auto';

    fetch(`/tickets/${currentAdminChatTicketId}/messages`, {
        method:  'POST',
        headers: {
            'Content-Type':     'application/json',
            'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ message: msg }),
    })
    .then(r => r.json())
    .then(() => loadAdminChatMessages())
    .catch(err => console.error('Send error:', err));
};

window.handleAdminChatKey = function (e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        window.sendAdminMessage();
    }
    const ta = document.getElementById('adminChatInput');
    setTimeout(() => {
        ta.style.height = 'auto';
        ta.style.height = Math.min(ta.scrollHeight, 80) + 'px';
    }, 0);
};

function loadAdminChatMessages() {
    if (!currentAdminChatTicketId) return;

    fetch(`/tickets/${currentAdminChatTicketId}/messages`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
    .then(data => {
        const msgs = data.messages;
        const $box = document.getElementById('adminChatMessages');
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

        const avColors = {
            'IT Admin': '#fde8e8', 'IT Support Specialist': '#fff4cc',
            'Helpdesk': '#d4f0d4', 'Executive': '#e8e0ff', 'Employee': '#e8f5b0',
        };
        const avTextColors = {
            'IT Admin': '#8b1a1a', 'IT Support Specialist': '#7a5a00',
            'Helpdesk': '#2d5a2d', 'Executive': '#4a1a8a', 'Employee': '#1a3c1a',
        };

        let html = '';
        msgs.forEach(msg => {
            const avBg   = avColors[msg.role]    || '#e8f5b0';
            const avText = avTextColors[msg.role] || '#1a3c1a';
            const isMe   = msg.is_me;

            html += `
                <div data-msg-id="${msg.id}" style="display:flex;gap:8px;align-items:flex-end;${isMe ? 'flex-direction:row-reverse' : ''}">
                    <div style="width:28px;height:28px;border-radius:50%;background:${avBg};color:${avText};display:flex;align-items:center;justify-content:center;font-family:'Nunito',sans-serif;font-weight:900;font-size:10px;flex-shrink:0">
                        ${msg.initials}
                    </div>
                    <div style="max-width:75%">
                        <div style="font-size:10px;font-weight:700;color:#5a7a5a;margin-bottom:3px;${isMe ? 'text-align:right' : ''}">
                            ${isMe ? 'You' : escAdminHtml(msg.sender)}
                            <span style="font-size:9px;background:${avBg};color:${avText};border-radius:4px;padding:1px 5px;margin-left:4px;text-transform:uppercase;letter-spacing:.3px;font-weight:800">
                                ${msg.role || 'User'}
                            </span>
                        </div>
                        <div style="padding:9px 13px;border-radius:16px;font-size:13px;line-height:1.5;word-break:break-word;${isMe
                            ? 'background:#1a3c1a;color:#c8e63c;border-bottom-right-radius:4px'
                            : 'background:#fff;color:#1a3c1a;border-bottom-left-radius:4px;border:1.5px solid #e2ddd4'}">
                            ${escAdminHtml(msg.message)}
                        </div>
                        <div style="font-size:10px;color:#5a7a5a;margin-top:3px;font-weight:600;${isMe ? 'text-align:right' : ''}">
                            ${msg.time_ago}
                        </div>
                    </div>
                </div>
            `;
        });

        $box.innerHTML = html;
        $box.scrollTop = $box.scrollHeight;
    })
    .catch(err => console.error('Chat load error:', err));
}

function escAdminHtml(str) {
    return String(str || '')
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

/* ── Smart silent background refresh ── */
let silentRefreshTimer = null;
let isModalOpen        = false;

function silentRefresh() {
    if (isModalOpen || document.hidden) return;
    const active = document.activeElement;
    if (active && active.matches('input, textarea, select')) return;

    fetch(window.location.href, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
    })
    .then(r => r.text())
    .then(html => {
        const parser = new DOMParser();
        const doc    = parser.parseFromString(html, 'text/html');

        const ackLink  = doc.querySelector("a[href*='status=awaiting-ack']");
        const ackCount = ackLink ? parseInt((ackLink.querySelector('.badge-count') || {}).textContent || '0', 10) : 0;
        document.title = ackCount > 0 ? `For Acknowledgment (${ackCount}) — IT Admin — My Support Requests` : 'IT Admin — My Support Requests';
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
            if (curEl && curEl.textContent.trim() !== newEl.textContent.trim()) {
                curEl.textContent = newEl.textContent;
            }
        });

        doc.querySelectorAll('.tab-pill').forEach((newEl, i) => {
            const curEl = document.querySelectorAll('.tab-pill')[i];
            if (curEl && curEl.textContent.trim() !== newEl.textContent.trim()) {
                curEl.textContent = newEl.textContent;
            }
        });
    })
    .catch(() => {});
}

setFaviconBadge({{ $counts['awaiting_ack'] }});
silentRefreshTimer = setInterval(silentRefresh, 30000);
document.addEventListener('show.bs.modal', () => { isModalOpen = true; });
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