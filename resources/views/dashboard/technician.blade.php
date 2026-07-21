@extends('layouts.app')

@section('title', 'My Work Queue — LGICT')

@section('nav-role-badge')
    <span class="role-badge dark"><i class="bi bi-tools me-1"></i>IT Support Specialist</span>
@endsection
@section(
    'avatar-initials',
    strtoupper(substr(Auth::user()->name, 0, 1)) .
    strtoupper(substr(Auth::user()->name, strpos(Auth::user()->name, ' ') + 1, 1))
)
@section('nav-username', Auth::user()->name)



{{-- Fix the hero title typo --}}
@section('hero-title')
    <h1>MY <em>WORK QUEUE</em></h1>
@endsection
@section('hero-subtitle', 'Acknowledge, start, and resolve tickets assigned to you.')

@section('hero-stats')
      <div class="workload-card">
          <div class="wl-label">My workload today</div>
          @php
            $total = $counts['awaiting_ack'] + $counts['ready_start'] + $counts['in_progress'] + $counts['escalated'];
            $maxLoad = 5;
            $loadPct = min(100, ($total / $maxLoad) * 100);
          @endphp
          <div class="wl-bar-wrap">
              <div class="wl-bar" style="width:{{ $loadPct }}%"></div>
          </div>
          <div class="wl-counts">
              <div class="wl-item">
                  <span class="wn">{{ $counts['awaiting_ack'] }}</span>
                  <span class="wl">New</span>
              </div>
              <div class="wl-item">
                  <span class="wn">{{ $counts['in_progress'] }}</span>
                  <span class="wl">Active</span>
              </div>
              <div class="wl-item">
                  <span class="wn">{{ $counts['closed'] }}</span>
                  <span class="wl">Closed</span>
              </div>
          </div>
      </div>
      <div class="d-flex gap-2 flex-wrap">
          <div class="stat-pill danger">
              <span class="num">{{ $counts['awaiting_ack'] }}</span>
              <span class="lbl">Awaiting Ack.</span>
          </div>
          <div class="stat-pill warn">
              <span class="num">{{ $counts['ready_start'] }}</span>
              <span class="lbl">Starts SLA</span>
          </div>
          <div class="stat-pill warn">
              <span class="num">{{ $counts['in_progress'] }}</span>
              <span class="lbl">In Progress</span>
          </div>
          <div class="stat-pill">
              <span class="num">{{ $counts['closed'] }}</span>
              <span class="lbl">Closed</span>
          </div>
      </div>
@endsection

@section('styles')
      .workload-card { background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.14); border-radius:14px; padding:16px 20px; min-width:220px; }
      .wl-label { font-size:11px; color:rgba(255,255,255,.55); text-transform:uppercase; letter-spacing:.5px; margin-bottom:8px; }
      .wl-bar-wrap { height:8px; background:rgba(255,255,255,.15); border-radius:4px; margin-bottom:8px; }
      .wl-bar  { height:8px; border-radius:4px; background:var(--yg); }
      .wl-counts { display:flex; gap:16px; }
      .wl-item .wn { font-family:'Nunito',sans-serif; font-weight:900; font-size:22px; color:var(--yg); display:block; line-height:1; }
      .wl-item .wl { font-size:10px; color:rgba(255,255,255,.5); text-transform:uppercase; letter-spacing:.4px; }

      .progress-strip { display:flex; align-items:center; margin-bottom:14px; }
      .ps-step { flex:1; text-align:center; }
      .ps-dot { width:26px; height:26px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 4px; font-size:11px; font-weight:900; font-family:'Nunito',sans-serif; border:2px solid var(--bd); background:#fff; color:var(--tm); transition:all .3s; }
      .ps-dot.done   { background:var(--gd); color:var(--yg); border-color:var(--gd); }
      .ps-dot.active { background:var(--yg); color:var(--gd); border-color:var(--yg); }
      .ps-lbl { font-size:9px; font-weight:700; color:var(--tm); text-transform:uppercase; letter-spacing:.3px; }
      .ps-lbl.done   { color:var(--gd); }
      .ps-lbl.active { color:var(--gd); font-weight:800; }
      .ps-line { flex:1; height:2px; background:var(--bd); transition:background .3s; }
      .ps-line.done  { background:var(--gd); }

      .checklist-item { padding:10px 16px; border-bottom:1px solid var(--bd); display:flex; align-items:flex-start; gap:10px; font-size:13px; }
      .checklist-item:last-child { border-bottom:none; }
      .ck-icon { width:22px; height:22px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:11px; flex-shrink:0; margin-top:1px; }
      .ck-icon.done    { background:var(--ygl); color:var(--gd); }
      .ck-icon.active  { background:var(--gd); color:var(--yg); }
      .ck-icon.pending { background:var(--bd); color:var(--tm); }
      .ck-text { font-weight:600; line-height:1.4; }
      .ck-sub  { font-size:11px; color:var(--tm); margin-top:2px; }

      .timeline { border-left:2px solid var(--bd); padding-left:14px; margin-top:4px; }
      .tl-item  { position:relative; padding-bottom:10px; font-size:12px; color:var(--tm); }
      .tl-item:last-child { padding-bottom:0; }
      .tl-item::before { content:''; position:absolute; left:-19px; top:4px; width:8px; height:8px; border-radius:50%; background:var(--bd); border:2px solid #fff; }
      .tl-item.done::before   { background:var(--gl); }
      .tl-item.active::before { background:var(--yg); }
      .tl-time { font-size:11px; color:var(--tm); margin-bottom:1px; }
      .tl-text { font-weight:600; color:var(--gd); }

      .btn-acknowledge-t { background:var(--ygl); color:var(--gd); font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; padding:7px 16px; border-radius:20px; border:1.5px solid var(--bd); cursor:pointer; transition:all .2s; }
      .btn-acknowledge-t:hover { border-color:var(--gl); background:#d8eda0; }
      .btn-start     { background:var(--yg); color:var(--gd); font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; padding:7px 16px; border-radius:20px; border:none; cursor:pointer; transition:all .2s; }
      .btn-start:hover { background:var(--ygd); }
      .btn-update    { background:var(--ygl); color:var(--gd); font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; padding:7px 16px; border-radius:20px; border:1.5px solid var(--bd); cursor:pointer; transition:all .2s; }
      .btn-update:hover { background:#d8eda0; border-color:var(--gl); }
      .btn-resolve-t { background:#e8f5ee; color:#1a5a3a; font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; padding:7px 16px; border-radius:20px; border:1.5px solid #a8ddc0; cursor:pointer; transition:all .2s; }
      .btn-resolve-t:hover { background:#c8ead8; }
      .btn-escalate-t { background:#fde8e8; color:#8b1a1a; font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; padding:7px 16px; border-radius:20px; border:1.5px solid #f0c0c0; cursor:pointer; transition:all .2s; }
      .btn-escalate-t:hover { background:#f8c8c8; }
      .btn-decline   { background:none; color:var(--tm); font-family:'Nunito',sans-serif; font-weight:700; font-size:12px; padding:7px 14px; border-radius:20px; border:1.5px solid var(--bd); cursor:pointer; transition:all .2s; }
      .btn-decline:hover { border-color:#e24b4a; color:#8b1a1a; }
      .status-opts { display:flex; flex-direction:column; gap:8px; }
      .status-opt { border:1.5px solid var(--bd); border-radius:12px; padding:12px 16px; cursor:pointer; transition:all .2s; background:var(--cr); display:flex; align-items:center; gap:12px; }
      .status-opt:hover { border-color:var(--gl); background:var(--ygl); }
      .status-opt.selected { border-color:var(--gd); background:var(--ygl); box-shadow:0 0 0 2px var(--yg); }
      .status-opt .so-dot   { width:12px; height:12px; border-radius:50%; flex-shrink:0; }
      .status-opt .so-label { font-weight:800; font-size:14px; font-family:'Nunito',sans-serif; }
      .status-opt .so-desc  { font-size:12px; color:var(--tm); }
      .btn-chat { background:#e8eeff; color:#2a4ab0; font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; padding:7px 16px; border-radius:20px; border:1.5px solid #b8c8ff; cursor:pointer; transition:all .2s; display:inline-flex; align-items:center; gap:5px; }
      .btn-chat:hover { background:#d0dcff; border-color:#8898dd; }
      .chat-count-badge { background:#e24b4a; color:#fff; font-size:10px; font-weight:900; border-radius:20px; padding:1px 6px; font-family:'Nunito',sans-serif; min-width:18px; text-align:center; }
      @keyframes badgePulse {
          0%   { transform: scale(1); }
          50%  { transform: scale(1.3); background: var(--yg); color: var(--gd); }
          100% { transform: scale(1); }
      }
      .badge-pulse { animation: badgePulse .6s ease; }
@endsection

{{-- ══ SIDEBAR ══ --}}
@section('sidebar')

      <div class="sidebar-card mb-3">
          <div class="sidebar-head">My Queue</div>
          <ul class="list-group sidebar-menu rounded-0">
              <li class="list-group-item {{ $status === 'active' ? 'active' : '' }}">
                  <a href="{{ route('technician.dashboard', ['status' => 'active']) }}"
                     class="d-flex justify-content-between align-items-center text-decoration-none">
                      <span><i class="bi bi-grid me-2"></i>My active queue</span>
                      <span class="badge-count">{{ $counts['active'] }}</span>
                  </a>
              </li>
              <li class="list-group-item {{ $status === 'awaiting-ack' ? 'active' : '' }}">
                  <a href="{{ route('technician.dashboard', ['status' => 'awaiting-ack']) }}"
                     class="d-flex justify-content-between align-items-center text-decoration-none">
                      <span><i class="bi bi-bell me-2"></i>Awaiting Acknowledgement</span>
                      <span class="badge-count red">{{ $counts['awaiting_ack'] }}</span>
                  </a>
              </li>
              <li class="list-group-item {{ $status === 'ready-start' ? 'active' : '' }}">
                  <a href="{{ route('technician.dashboard', ['status' => 'ready-start']) }}"
                     class="d-flex justify-content-between align-items-center text-decoration-none">
                      <span><i class="bi bi-eye me-2"></i>Starts SLA</span>
                      <span class="badge-count">{{ $counts['ready_start'] }}</span>
                  </a>
              </li>
              <li class="list-group-item {{ $status === 'in-progress' ? 'active' : '' }}">
                  <a href="{{ route('technician.dashboard', ['status' => 'in-progress']) }}"
                     class="d-flex justify-content-between align-items-center text-decoration-none">
                      <span><i class="bi bi-arrow-repeat me-2"></i>In Progress</span>
                      <span class="badge-count">{{ $counts['in_progress'] }}</span>
                  </a>
              </li>
              <li class="list-group-item {{ $status === 'escalated' ? 'active' : '' }}">
                  <a href="{{ route('technician.dashboard', ['status' => 'escalated']) }}"
                     class="d-flex justify-content-between align-items-center text-decoration-none">
                      <span><i class="bi bi-exclamation-triangle me-2"></i>Escalated</span>
                      <span class="badge-count red">{{ $counts['escalated'] }}</span>
                  </a>
              </li>
              <li class="list-group-item {{ $status === 'closed' ? 'active' : '' }}">
                  <a href="{{ route('technician.dashboard', ['status' => 'closed']) }}"
                     class="d-flex justify-content-between align-items-center text-decoration-none">
                      <span><i class="bi bi-check-circle me-2"></i>Closed</span>
                      <span class="badge-count">{{ $counts['closed'] }}</span>
                  </a>
              </li>
          </ul>
      </div>

      {{-- Workflow steps --}}
      <div class="sidebar-card mb-3">
          <div class="sidebar-head">Workflow Steps</div>
          <div>
              <div class="checklist-item">
                  <div class="ck-icon done"><i class="bi bi-check"></i></div>
                  <div><div class="ck-text">Receive assignment</div><div class="ck-sub">Supervisor assigns ticket to you</div></div>
              </div>
              <div class="checklist-item">
                  <div class="ck-icon {{ $counts['awaiting_ack'] > 0 ? 'active' : 'done' }}">
                      <i class="bi bi-arrow-right"></i>
                  </div>
                  <div><div class="ck-text">Acknowledge assignment</div><div class="ck-sub">Confirm you've seen the ticket</div></div>
              </div>
              <div class="checklist-item">
                  <div class="ck-icon {{ $counts['ready_start'] > 0 ? 'active' : 'pending' }}">3</div>
                  <div><div class="ck-text">Start ticket</div><div class="ck-sub">SLA resolution timer begins</div></div>
              </div>
              <div class="checklist-item">
                  <div class="ck-icon {{ $counts['in_progress'] > 0 ? 'active' : 'pending' }}">4</div>
                  <div><div class="ck-text">Resolve or escalate</div><div class="ck-sub">Close if done, escalate if not</div></div>
              </div>
              <div class="checklist-item">
                  <div class="ck-icon pending">5</div>
                  <div><div class="ck-text">Supervisor validates &amp; closes</div><div class="ck-sub">Ticket moves through validation until Closed</div></div>
              </div>
          </div>
      </div>

      {{-- Weekly stats --}}
      <div class="sidebar-card">
          <div class="sidebar-head">This Week</div>
          <div class="p-3 d-flex flex-column gap-2">
              <div class="d-flex justify-content-between">
                  <span style="font-size:13px;font-weight:600;color:var(--tm)">Tickets resolved</span>
                  <span class="font-brand fw-900" style="font-size:18px">{{ $weekStats['resolved'] }}</span>
              </div>
              <div class="d-flex justify-content-between">
                  <span style="font-size:13px;font-weight:600;color:var(--tm)">Avg resolution time</span>
                  <span class="font-brand fw-900" style="font-size:18px">
                      {{ $weekStats['avg_time'] ? number_format($weekStats['avg_time'], 1) . 'h' : 'N/A' }}
                  </span>
              </div>
              <div class="d-flex justify-content-between">
                  <span style="font-size:13px;font-weight:600;color:var(--tm)">Escalated</span>
                  <span class="font-brand fw-900" style="font-size:18px;color:#8b1a1a">
                      {{ $weekStats['escalated'] }}
                  </span>
              </div>
              <div class="d-flex justify-content-between">
                  <span style="font-size:13px;font-weight:600;color:var(--tm)">Customer rating</span>
                  <span class="font-brand fw-900" style="font-size:18px">
                      {{ $weekStats['avg_rating'] ? number_format($weekStats['avg_rating'], 1) . ' ⭐' : 'N/A' }}
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

      <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
          <span class="font-brand fw-900" style="font-size:22px">
              @php
                $labels = [
                    'active' => 'My Active Queue',
                    'awaiting-ack' => 'Awaiting Acknowledgement',
                    'ready-start' => 'Starts SLA — Ready to Start',
                    'in-progress' => 'In Progress',
                    'escalated' => 'Escalated',
                    'closed' => 'Closed',
                ];
              @endphp
              {{ $labels[$status] ?? 'My Active Queue' }}
          </span>
          <form method="GET" action="{{ route('technician.dashboard') }}"
                class="d-flex gap-2 flex-wrap" id="searchForm">
              <input type="hidden" name="status" value="{{ $status }}">
              <div class="search-wrap">
                  <i class="bi bi-search" style="color:var(--tm)"></i>
                  <input type="text" name="search" id="searchInput"
                         placeholder="Search tickets…"
                         value="{{ $search }}" autocomplete="off">
              </div>
              <select class="sort-select" name="sort" onchange="this.form.submit()">
                  <option value="priority" {{ $sort === 'priority' ? 'selected' : '' }}>Priority first</option>
                  <option value="newest"   {{ $sort === 'newest' ? 'selected' : '' }}>Newest first</option>
                  <option value="oldest"   {{ $sort === 'oldest' ? 'selected' : '' }}>Oldest first</option>
              </select>
          </form>
      </div>

      <div class="d-flex flex-wrap gap-2 mb-3">
          @php
            $tabs = [
                'active' => ['label' => 'Active Queue', 'count' => $counts['active']],
                'awaiting-ack' => ['label' => 'Awaiting Acknowledgement', 'count' => $counts['awaiting_ack']],
                'ready-start' => ['label' => 'Starts SLA', 'count' => $counts['ready_start']],
                'in-progress' => ['label' => 'In Progress', 'count' => $counts['in_progress']],
                'escalated' => ['label' => 'Escalated', 'count' => $counts['escalated']],
                'closed' => ['label' => 'Closed', 'count' => $counts['closed']],
            ];
          @endphp
          @foreach($tabs as $key => $tab)
            <a href="{{ route('technician.dashboard', array_merge(request()->except('status'), ['status' => $key])) }}"
               class="tab-pill {{ $status === $key ? 'active' : '' }}">
                {{ $tab['label'] }} ({{ $tab['count'] }})
            </a>
          @endforeach
      </div>

      {{-- Ticket list --}}
      <div class="d-flex flex-column gap-3" id="ticketList">

          @forelse($tickets as $ticket)
            @php
                $isNew        = $ticket->status === 'Awaiting Support Specialist Acknowledgement';
                $isAcknowledged = $ticket->status === 'Awaiting Start SLA';
                $isInProgress = $ticket->status === 'In Progress';
                $isEscalated  = $ticket->status === 'Escalated';
                $isClosed     = in_array($ticket->status, ['Closed', 'Pending Closure', 'Awaiting Requestor']);

                $cardClass = match (true) {
                    $isNew => 'new-assigned',
                    $isAcknowledged => 'Awaiting Start SLA',
                    $isInProgress => 'in-progress',
                    $isEscalated => 'escalated',
                    $isClosed => 'resolved',
                    default => 'open'
                };
                $priorityClass = match ($ticket->ticket_type) {
                    'High' => 'pri-high',
                    'Medium' => 'pri-medium',
                    'Low' => 'pri-low',
                    default => ''
                };

                // Progress strip state: Assigned → Acknowledge → Start → Resolve
                $step1 = 'done'; // Always assigned
                $step2 = match (true) { $isNew => 'active', default => 'done'};
                $step3 = match (true) {
                    $isNew => '',
                    $isAcknowledged => 'active',
                    default => 'done'
                };
                $step4 = match (true) {
                    $isClosed => 'done',
                    $isEscalated => 'active',
                    $isInProgress => 'active',
                    default => ''
                };
                $line1 = $isNew ? '' : 'done';
                $line2 = $isAcknowledged || $isInProgress || $isClosed || $isEscalated ? 'done' : '';
                $line3 = $isInProgress || $isClosed || $isEscalated ? 'done' : '';

                $recentHistory = $ticket->statusHistories->sortByDesc('changed_at')->take(3)->reverse();

                // ── SLA countdown (In Progress only) ──
                $slaSecondsLeft = $isInProgress ? $ticket->slaSecondsRemaining() : null;
                if ($slaSecondsLeft !== null) {
                    $slaBreached = $slaSecondsLeft <= 0;
                    $slaAtRisk   = !$slaBreached && $ticket->isSlaAtRisk();
                    $slaColor    = $slaBreached ? '#e24b4a' : ($slaAtRisk ? '#f5c842' : '#3fb950');
                    $slaBg       = $slaBreached ? '#fde8e8' : ($slaAtRisk ? '#fff4cc' : '#d4f0d4');
                    $slaIcon     = $slaBreached ? 'bi-exclamation-triangle-fill' : ($slaAtRisk ? 'bi-clock-history' : 'bi-stopwatch');
                    $abs = abs($slaSecondsLeft);
                    $h = intdiv($abs, 3600);
                    $m = intdiv($abs % 3600, 60);
                    $s = $abs % 60;
                    $slaText = ($h > 0 ? $h . 'h ' : '') . $m . 'm ' . $s . 's';
                    $slaLabel = $slaBreached ? 'Overdue by ' . $slaText : $slaText . ' left';
                }
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
                            {{ $ticket->ticket_type }} priority
                        </span>
                    </div>
                    @if($isNew)
                          <span class="badge-status badge-new">
                              <i class="bi bi-bell me-1"></i>Awaiting Acknowledgement
                          </span>
                    @elseif($isAcknowledged)
                          <span class="badge-status badge-acknowledged" style="background:var(--ygl);color:var(--gd)">
                              <i class="bi bi-eye me-1"></i>Starts SLA — Ready to Start
                          </span>
                    @elseif($isInProgress)
                          <span class="badge-status badge-in-progress">
                              <i class="bi bi-arrow-repeat me-1"></i>In Progress
                          </span>
                    @elseif($isEscalated)
                          <span class="badge-status badge-escalated">
                              <i class="bi bi-exclamation-triangle me-1"></i>Escalated
                          </span>
                    @elseif($isClosed)
                          <span class="badge-status badge-resolved">
                              <i class="bi bi-check-circle me-1"></i>Closed
                          </span>
                    @endif
                </div>

                {{-- Progress strip --}}
                @if(!$isClosed)
                      <div class="progress-strip mb-3">
                          <div class="ps-step">
                              <div class="ps-dot {{ $step1 }}"><i class="bi bi-check"></i></div>
                              <div class="ps-lbl {{ $step1 }}">Assigned</div>
                          </div>
                          <div class="ps-line {{ $line1 }}"></div>
                          <div class="ps-step">
                              <div class="ps-dot {{ $step2 }}">
                                  @if($step2 === 'done') <i class="bi bi-check"></i> @else 2 @endif
                              </div>
                              <div class="ps-lbl {{ $step2 }}">Acknowledge</div>
                          </div>
                          <div class="ps-line {{ $line2 }}"></div>
                          <div class="ps-step">
                              <div class="ps-dot {{ $step3 }}">
                                  @if($step3 === 'done') <i class="bi bi-check"></i> @else 3 @endif
                              </div>
                              <div class="ps-lbl {{ $step3 }}">Start</div>
                          </div>
                          <div class="ps-line {{ $line3 }}"></div>
                          <div class="ps-step">
                              <div class="ps-dot {{ $step4 }}">
                                  @if($step4 === 'done') <i class="bi bi-check"></i> @else 4 @endif
                              </div>
                              <div class="ps-lbl {{ $step4 }}">Resolve</div>
                          </div>
                      </div>
                @endif

                {{-- Title & desc --}}
                <div class="ticket-title mb-1">{{ $ticket->subject }}</div>
                <div class="ticket-desc mb-2">{{ Str::limit($ticket->concern, 140) }}</div>

                {{-- Timeline for In Progress tickets --}}
                @if($isInProgress && $recentHistory->count() > 0)
                      <div class="timeline mb-3">
                          @foreach($recentHistory as $history)
                            <div class="tl-item {{ $loop->last ? 'active' : 'done' }}">
                                <div class="tl-time">
                                    {{ \Carbon\Carbon::parse($history->changed_at)->format('g:i A') }}
                                </div>
                                <div class="tl-text">{{ $history->notes }}</div>
                            </div>
                          @endforeach
                      </div>
                @endif

                {{-- Meta --}}
                <div class="d-flex flex-wrap gap-3 mb-3">
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
                    @if($ticket->location)
                          <span class="meta-item">
                              <i class="bi bi-geo-alt"></i> {{ $ticket->location }}
                          </span>
                    @endif
                    @if($isInProgress && $ticket->sla_due_at)
                          <span class="sla-timer"
                                data-sla-timer
                                data-sla-due="{{ $ticket->sla_due_at->toIso8601String() }}"
                                style="background:{{ $slaBg }};color:{{ $slaColor }};font-size:12px;font-weight:800;border-radius:20px;padding:4px 12px;display:inline-flex;align-items:center;gap:5px;border:1px solid {{ $slaColor }}40">
                              <i class="bi {{ $slaIcon }}"></i>
                              <span class="sla-timer-text">{{ $slaLabel }}</span>
                          </span>
                    @elseif($isInProgress && $ticket->started_at)
                          <span class="meta-item">
                              <i class="bi bi-dash-circle"></i>
                              No SLA configured for this category
                          </span>
                    @elseif($isAcknowledged && $ticket->tech_acknowledged_at)
                          <span class="meta-item">
                              <i class="bi bi-eye"></i>
                              Acknowledged {{ \Carbon\Carbon::parse($ticket->tech_acknowledged_at)->diffForHumans() }}
                          </span>
                    @elseif($isNew)
                          <span class="meta-item">
                              <i class="bi bi-calendar3"></i>
                              Assigned {{ $ticket->updated_at->diffForHumans() }}
                          </span>
                    @elseif($isClosed && $ticket->resolved_at)
                          <span class="meta-item">
                              <i class="bi bi-clock-history"></i>
                              Closed {{ $ticket->resolved_at->diffForHumans() }}
                          </span>
                    @endif

                    @if($isClosed && $ticket->feedback)
                          <span class="meta-item">
                              <i class="bi bi-star-fill" style="color:#f5c842"></i>
                              {{ $ticket->feedback->rating }} / 5 feedback
                          </span>
                    @endif
                </div>

                {{-- Escalation banner --}}
                @if($isEscalated)
                      <div class="esc-banner p-2 mb-3">
                          <i class="bi bi-exclamation-triangle-fill me-1"></i>
                          Escalated to Supervisor — awaiting reassignment or takeover.
                          No further action required from you on this ticket.
                      </div>
                @endif

                {{-- Action buttons --}}
                <d  iv class="d-flex gap-2 flex-wrap">

                    {{-- New: Acknowledge + Decline --}}
                    @if($isNew)
                          <button class="btn-acknowledge-t"
                                  onclick="openAcknowledgeModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                              <i class="bi bi-eye me-1"></i>Acknowledge
                          </button>
                          <button class="btn-decline"
                                  onclick="openDeclineModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                              <i class="bi bi-x-circle me-1"></i>Decline
                          </button>
                    @endif

                    {{-- Acknowledged: Start Ticket + Decline --}}
                    @if($isAcknowledged)
                          <button class="btn-start"
                                  onclick="openStartModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                              <i class="bi bi-play-circle me-1"></i>Start Ticket
                          </button>
                          <button class="btn-decline"
                                  onclick="openDeclineModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                              <i class="bi bi-x-circle me-1"></i>Decline
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

                    @if($isInProgress || $isAcknowledged)
                        <button class="btn-chat"
                                onclick="openTechChatModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-chat-dots me-1"></i>Message
                            @php $unread = \App\Models\TicketMessage::where('ticket_id', $ticket->id)
                                ->where('sender_id', '!=', Auth::id())
                            ->where('is_read', false)->count(); @endphp
                            @if($unread > 0)
                                <span class="chat-count-badge" id="badge-{{ $ticket->id }}">{{ $unread }}</span>
                            @endif
                        </button>
                    @endif
                </div>
            </div>
          @empty
            <div class="ticket-card p-5 text-center">
                <div style="font-size:48px;opacity:.3">🛠️</div>
                <div class="mt-3 font-brand fw-900" style="font-size:18px;color:var(--tm)">
                    No tickets in this view.
                </div>
                <div style="font-size:13px;color:var(--tm);margin-top:4px">
                    Check another tab, or wait for your supervisor to assign new tickets.
                </div>
            </div>
          @endforelse

      </div>

      @if($tickets->hasPages())
        <div class="mt-4">{{ $tickets->links() }}</div>
      @endif

@endsection

{{-- ══ MODALS ══ --}}
@section('modals')

      {{-- Acknowledge modal (Step 1 — does NOT start SLA) --}}
      <div class="modal fade" id="acknowledgeModal" tabindex="-1">
          <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content">
                  <div class="modal-header-gd d-flex align-items-center justify-content-between">
                      <h5 class="mb-0">Acknowledge <em>Assignment</em></h5>
                      <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                  </div>
                  <form method="POST" id="acknowledgeForm">
                      @csrf
                      <div class="modal-body px-4 py-4">
                          <div class="info-box-green p-3 mb-3">
                              <i class="bi bi-eye me-1"></i>
                              Acknowledging <strong id="ackRef"></strong>.
                              This confirms you've seen the assignment — the SLA resolution
                              timer will not start until you press <strong>Start Ticket</strong>.
                          </div>
                          <div>
                              <label class="form-label">Notes (optional)</label>
                              <textarea class="form-control" name="notes" rows="2"
                                        placeholder="Any initial observations…"></textarea>
                          </div>
                      </div>
                      <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
                          <button type="button" class="btn-cancel-modal"
                                  data-bs-dismiss="modal">Cancel</button>
                          <button type="submit" class="btn-confirm">
                              <i class="bi bi-eye me-1"></i>Acknowledge
                          </button>
                      </div>
                  </form>
              </div>
          </div>
      </div>

      {{-- Start modal (Step 2 — SLA resolution timer starts here) --}}
      <div class="modal fade" id="startModal" tabindex="-1">
          <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content">
                  <div class="modal-header-gd d-flex align-items-center justify-content-between">
                      <h5 class="mb-0">Start <em>Ticket</em></h5>
                      <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                  </div>
                  <form method="POST" id="startForm">
                      @csrf
                      <div class="modal-body px-4 py-4">
                          <div class="info-box-green p-3 mb-3">
                              <i class="bi bi-play-circle me-1"></i>
                              Starting <strong id="startRef"></strong>.
                              Status will update to <strong>In Progress</strong> and the
                              SLA resolution timer begins now.
                          </div>
                          <div class="mb-3">
                              <label class="form-label">Estimated completion time</label>
                              <select class="form-select" name="estimated_time" required>
                                  <option value="Within 1 hour">Within 1 hour</option>
                                  <option value="1 – 2 hours">1 – 2 hours</option>
                                  <option value="Half day">Half day</option>
                                  <option value="Full day">Full day</option>
                                  <option value="Requires parts or escalation">Requires parts / escalation</option>
                              </select>
                          </div>
                          <div>
                              <label class="form-label">Initial notes (optional)</label>
                              <textarea class="form-control" name="notes" rows="2"
                                        placeholder="Plan of action…"></textarea>
                          </div>
                      </div>
                      <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
                          <button type="button" class="btn-cancel-modal"
                                  data-bs-dismiss="modal">Cancel</button>
                          <button type="submit" class="btn-confirm">
                              <i class="bi bi-play-circle me-1"></i>Start Ticket
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
                  <div class="modal-header-gd d-flex align-items-center justify-content-between">
                      <h5 class="mb-0">Decline <em>Ticket</em></h5>
                      <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                  </div>
                  <form method="POST" id="declineForm">
                      @csrf
                      <div class="modal-body px-4 py-4">
                          <div class="info-box-red p-3 mb-3">
                              <i class="bi bi-exclamation-triangle me-1"></i>
                              Declining <strong id="declineRef"></strong> will return it
                              to the supervisor queue for reassignment.
                          </div>
                          <div>
                              <label class="form-label">
                                  Reason for declining <span class="text-danger">*</span>
                              </label>
                              <select class="form-select" name="reason" required>
                                  <option value="">Select a reason…</option>
                                  <option value="At full capacity">At full capacity</option>
                                  <option value="Outside my technical expertise">Outside my technical expertise</option>
                                  <option value="Requires hardware parts not available">Requires hardware parts not available</option>
                                  <option value="Conflict of schedule">Conflict of schedule</option>
                                  <option value="Other">Other</option>
                              </select>
                          </div>
                      </div>
                      <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
                          <button type="button" class="btn-cancel-modal"
                                  data-bs-dismiss="modal">Cancel</button>
                          <button type="submit" class="btn-confirm" style="background:#8b1a1a">
                              <i class="bi bi-x-circle me-1"></i>Decline Ticket
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
                          <div class="info-box-green p-3 mb-3">
                              <i class="bi bi-check-circle me-1"></i>
                              Resolving <strong id="resolveRef"></strong> —
                              this ends the SLA resolution timer and sends the ticket
                              to your Supervisor for validation. It will move to
                              <strong>Closed</strong> once fully validated.
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

      {{-- Escalate modal --}}
      <div class="modal fade" id="escModal" tabindex="-1">
          <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content">
                  <div class="modal-header-gd d-flex align-items-center justify-content-between">
                      <h5 class="mb-0">Escalate to <em>Supervisor</em></h5>
                      <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                  </div>
                  <form method="POST" id="escForm">
                      @csrf
                      <div class="modal-body px-4 py-4">
                          <div class="info-box-red p-3 mb-3">
                              <i class="bi bi-exclamation-triangle-fill me-1"></i>
                              <strong id="escRef"></strong> — Escalating means you cannot resolve this.
                              Your Supervisor will reassign or take over. It stays in your
                              queue under <strong>Escalated</strong> and will show as
                              <strong>Closed</strong> once finished.
                          </div>
                          <div class="mb-3">
                              <label class="form-label">
                                  Reason for escalation <span class="text-danger">*</span>
                              </label>
                              <select class="form-select" name="reason" required>
                                  <option value="">Select a reason…</option>
                                  <option value="Issue requires admin-level system access">Issue requires admin-level system access</option>
                                  <option value="Hardware fault beyond repair scope">Hardware fault beyond repair scope</option>
                                  <option value="Requires vendor or manufacturer intervention">Requires vendor / manufacturer intervention</option>
                                  <option value="Issue affects multiple users or systems">Issue affects multiple users / systems</option>
                                  <option value="Cannot diagnose root cause">Cannot diagnose root cause</option>
                              </select>
                          </div>
                          <div>
                              <label class="form-label">
                                  What you have already tried <span class="text-danger">*</span>
                              </label>
                              <textarea class="form-control" name="already_tried"
                                        rows="3" required
                                        placeholder="List all steps taken so the supervisor has full context…"></textarea>
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
      <div class="modal fade" id="techChatModal" tabindex="-1">
          <div class="modal-dialog modal-dialog-centered" style="max-width:480px">
              <div class="modal-content" style="border-radius:20px;overflow:hidden;border:none">
                  <div class="modal-header-gd d-flex align-items-center justify-content-between">
                      <div>
                          <h5 class="mb-0">
                              <i class="bi bi-chat-dots-fill me-2" style="color:var(--yg)"></i>
                              Messages — <em id="techChatTicketRef">#TKT-0000</em>
                          </h5>
                      </div>
                      <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                  </div>

                  <div id="techChatMessages"
                      style="height:360px;overflow-y:auto;padding:16px;background:#f8f8f4;display:flex;flex-direction:column;gap:12px;scroll-behavior:smooth">
                      <div class="text-center py-4" style="color:var(--tm);font-size:13px">
                          <div class="spinner-border spinner-border-sm me-2"></div>
                          Loading messages…
                      </div>
                  </div>

                  <div style="border-top:1.5px solid var(--bd);padding:12px 16px;background:#fff">
                      <div style="font-size:10px;font-weight:800;background:#fff4cc;color:#7a5a00;border-radius:4px;padding:2px 8px;display:inline-block;margin-bottom:8px;text-transform:uppercase;letter-spacing:.3px">
                          IT Support Specialist
                      </div>
                      <div class="d-flex gap-2 align-items-end">
                          <textarea id="techChatInput"
                                      placeholder="Type a message… (Enter to send)"
                                      rows="1"
                                      style="flex:1;border:1.5px solid var(--bd);border-radius:20px;padding:9px 14px;font-size:13px;resize:none;outline:none;font-family:'Nunito Sans',sans-serif;max-height:80px;overflow-y:auto;color:var(--gd);background:var(--cr);transition:border-color .2s"
                                      onkeydown="handleTechChatKey(event)"
                                      onfocus="this.style.borderColor='var(--gl)';this.style.background='#fff'"
                                      onblur="this.style.borderColor='var(--bd)';this.style.background='var(--cr)'"></textarea>
                          <button onclick="sendTechMessage()"
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

    /* ══ GLOBAL CHAT FUNCTIONS ══ */
    let currentTechChatTicketId = null;
    let techChatPollInterval    = null;

    window.openTechChatModal = function (ticketId, ticketNumber) {
        currentTechChatTicketId = ticketId;
        $('#techChatTicketRef').text('#' + ticketNumber);
        $('#badge-' + ticketId).remove();
        $('#techChatMessages').html(`
            <div class="text-center py-4" style="color:var(--tm);font-size:13px">
                <div class="spinner-border spinner-border-sm me-2"></div>
                Loading messages…
            </div>
        `);
        new bootstrap.Modal('#techChatModal').show();
        loadTechChatMessages();
        clearInterval(techChatPollInterval);
        techChatPollInterval = setInterval(loadTechChatMessages, 3000);
    };

    window.sendTechMessage = function () {
        const input = document.getElementById('techChatInput');
        const msg   = input.value.trim();
        if (!msg || !currentTechChatTicketId) return;
        input.value = '';
        input.style.height = 'auto';
        fetch(`/tickets/${currentTechChatTicketId}/messages`, {
            method:  'POST',
            headers: {
                'Content-Type':     'application/json',
                'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ message: msg }),
        })
        .then(r => r.json())
        .then(() => loadTechChatMessages())
        .catch(err => console.error('Send error:', err));
    };

    window.handleTechChatKey = function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            window.sendTechMessage();
        }
        const ta = document.getElementById('techChatInput');
        setTimeout(() => {
            ta.style.height = 'auto';
            ta.style.height = Math.min(ta.scrollHeight, 80) + 'px';
        }, 0);
    };

    function loadTechChatMessages() {
        if (!currentTechChatTicketId) return;
        fetch(`/tickets/${currentTechChatTicketId}/messages`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
        .then(data => {
            const msgs = data.messages;
            const $box = document.getElementById('techChatMessages');
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
            const avColors = { 'IT Admin':'#fde8e8','IT Support Specialist':'#fff4cc','Helpdesk':'#d4f0d4','Supervisor - Support Specialist':'#fde8e8','Executive':'#e8e0ff','Employee':'#e8f5b0' };
            const avTextColors = { 'IT Admin':'#8b1a1a','IT Support Specialist':'#7a5a00','Helpdesk':'#2d5a2d','Supervisor - Support Specialist':'#8b1a1a','Executive':'#4a1a8a','Employee':'#1a3c1a' };
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
                                ${isMe ? 'You' : escTechHtml(msg.sender)}
                                <span style="font-size:9px;background:${avBg};color:${avText};border-radius:4px;padding:1px 5px;margin-left:4px;text-transform:uppercase;letter-spacing:.3px;font-weight:800">${msg.role || 'User'}</span>
                            </div>
                            <div style="padding:9px 13px;border-radius:16px;font-size:13px;line-height:1.5;word-break:break-word;${isMe ? 'background:var(--gd);color:var(--yg);border-bottom-right-radius:4px' : 'background:#fff;color:var(--gd);border-bottom-left-radius:4px;border:1.5px solid var(--bd)'}">
                                ${escTechHtml(msg.message)}
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

    function escTechHtml(str) {
        return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    /* ══ DOM-READY ══ */
    $(function () {

        let searchTimer;
        $('#searchInput').on('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => $('#searchForm').submit(), 500);
        });

        $(document).on('click', '.status-opt', function () {
            $(this).siblings().removeClass('selected');
            $(this).addClass('selected');
            $('#workStatusVal').val($(this).data('val'));
        });

        window.openAcknowledgeModal = function (ticketId, ticketNumber) {
            $('#ackRef').text('#' + ticketNumber);
            $('#acknowledgeForm').attr('action', '/technician/tickets/' + ticketId + '/acknowledge');
            new bootstrap.Modal('#acknowledgeModal').show();
        };

        window.openStartModal = function (ticketId, ticketNumber) {
            $('#startRef').text('#' + ticketNumber);
            $('#startForm').attr('action', '/technician/tickets/' + ticketId + '/start');
            new bootstrap.Modal('#startModal').show();
        };

        window.openDeclineModal = function (ticketId, ticketNumber) {
            $('#declineRef').text('#' + ticketNumber);
            $('#declineForm').attr('action', '/technician/tickets/' + ticketId + '/decline');
            new bootstrap.Modal('#declineModal').show();
        };

        window.openUpdateModal = function (ticketId, ticketNumber) {
            $('#updateRef').text('#' + ticketNumber);
            $('#updateForm').attr('action', '/technician/tickets/' + ticketId + '/update');
            $('.status-opt').removeClass('selected');
            $('.status-opt[data-val="Actively working"]').addClass('selected');
            $('#workStatusVal').val('Actively working');
            new bootstrap.Modal('#updateModal').show();
        };

        window.openResolveModal = function (ticketId, ticketNumber) {
            $('#resolveRef').text('#' + ticketNumber);
            $('#resolveForm').attr('action', '/technician/tickets/' + ticketId + '/resolve');
            new bootstrap.Modal('#resolveModal').show();
        };

        window.openEscModal = function (ticketId, ticketNumber) {
            $('#escRef').text('#' + ticketNumber);
            $('#escForm').attr('action', '/technician/tickets/' + ticketId + '/escalate');
            new bootstrap.Modal('#escModal').show();
        };

        $('#techChatModal').on('hidden.bs.modal', function () {
            clearInterval(techChatPollInterval);
            currentTechChatTicketId = null;
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

    /* ══ SLA COUNTDOWN TIMER ══ */
    function tickSlaTimers() {
        document.querySelectorAll('[data-sla-timer]').forEach(el => {
            const due = new Date(el.getAttribute('data-sla-due'));
            if (isNaN(due.getTime())) return;

            const diffMs = due.getTime() - Date.now();
            const breached = diffMs <= 0;
            const abs = Math.abs(Math.floor(diffMs / 1000));
            const h = Math.floor(abs / 3600);
            const m = Math.floor((abs % 3600) / 60);
            const s = abs % 60;
            const text = (h > 0 ? h + 'h ' : '') + m + 'm ' + s + 's';

            const textEl = el.querySelector('.sla-timer-text');
            if (textEl) textEl.textContent = breached ? ('Overdue by ' + text) : (text + ' left');

            if (breached) {
                el.style.background = '#fde8e8';
                el.style.color = '#e24b4a';
                el.style.borderColor = '#e24b4a40';
                const icon = el.querySelector('i');
                if (icon) icon.className = 'bi bi-exclamation-triangle-fill';
            }
        });
    }
    setInterval(tickSlaTimers, 1000);
    </script>
@endsection