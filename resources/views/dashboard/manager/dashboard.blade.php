@extends('layouts.app')

@section('title', 'Manager Dashboard — LGICT')

@section('nav-role-badge')
    <span class="role-badge"><i class="bi bi-award-fill me-1"></i>Manager</span>
@endsection
@section('avatar-initials',
    strtoupper(substr(Auth::user()->name, 0, 1)) .
    strtoupper(substr(Auth::user()->name, strpos(Auth::user()->name, ' ') + 1, 1))
)
@section('nav-username', Auth::user()->name)

@section('hero-title')
    <h1>Manager <em>QUEUE</em></h1>
@endsection
@section('hero-subtitle', 'Acknowledge and resolve support requests escalated from Admin Supervisors.')

@section('hero-stats')
    <div class="d-flex gap-2 flex-wrap">
        <div class="stat-pill warn">
            <span class="num">{{ $counts['awaiting_manager'] }}</span>
            <span class="lbl">Awaiting You</span>
        </div>
        <div class="stat-pill">
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
    .btn-acknowledge { background:var(--ygl); color:var(--gd); font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; padding:6px 14px; border-radius:20px; border:1.5px solid var(--bd); cursor:pointer; transition:all .2s; }
    .btn-acknowledge:hover { border-color:var(--gl); }
    .btn-resolve  { background:#e8f5ee; color:#1a5a3a; font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; padding:6px 14px; border-radius:20px; border:1.5px solid #a8ddc0; cursor:pointer; transition:all .2s; }
    .btn-resolve:hover  { background:#c8ead8; }
    .btn-service-report { background:#e8f5ee; color:#1a5a3a; font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; padding:6px 14px; border-radius:20px; border:1.5px solid #a8ddc0; cursor:pointer; transition:all .2s; }
    .btn-service-report:hover { background:#c8ead8; }
    .resolve-info { background:var(--ygl); border-radius:10px; font-size:13px; color:var(--gd); }

    .esc-level { background:#fde8e8; color:#8b1a1a; font-size:11px; font-weight:800; border-radius:20px; padding:3px 10px; display:inline-flex; align-items:center; }
    .esc-timeline { background:#fde8e8; border-radius:10px; padding:12px 14px; }
    .etl-item { display:flex; gap:10px; font-size:12px; padding-bottom:8px; }
    .etl-item:last-child { padding-bottom:0; }
    .etl-dot { width:8px; height:8px; border-radius:50%; background:#8b1a1a; flex-shrink:0; margin-top:4px; }
    .etl-time { color:#8b1a1a; font-weight:700; min-width:70px; }
    .etl-text { color:#5a1a1a; font-weight:600; }

    .pagination { flex-wrap: wrap; justify-content: center; gap: 6px; }
    .pagination li { margin: 2px; }
    .pagination .page-link { border-radius: 8px !important; padding: 6px 12px; font-size: 13px; }
@endsection

{{-- ══ SIDEBAR ══ --}}
@section('sidebar')

    <div class="sidebar-card mb-3">
        <div class="sidebar-head">Queue</div>
        <ul class="list-group sidebar-menu rounded-0">
            @php
                $sideItems = [
                    ['key' => 'active',           'icon' => 'bi-grid',            'label' => 'Active',        'count' => $counts['active']],
                    ['key' => 'awaiting-manager', 'icon' => 'bi-inbox',           'label' => 'Awaiting You',  'count' => $counts['awaiting_manager']],
                    ['key' => 'in-progress',      'icon' => 'bi-gear-fill',       'label' => 'In Progress',   'count' => $counts['in_progress']],
                    ['key' => 'on-hold',          'icon' => 'bi-pause-circle',    'label' => 'On Hold',       'count' => $counts['on_hold']],
                    ['key' => 'closed',           'icon' => 'bi-check-circle',    'label' => 'Closed',        'count' => $counts['closed']],
                ];
            @endphp
            @foreach($sideItems as $item)
                <li class="list-group-item {{ $status === $item['key'] ? 'active' : '' }}">
                    <a href="{{ route('executive.tickets.index', ['status' => $item['key']]) }}"
                       class="d-flex justify-content-between align-items-center text-decoration-none">
                        <span><i class="bi {{ $item['icon'] }} me-2"></i>{{ $item['label'] }}</span>
                        <span class="badge-count">{{ $item['count'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="sidebar-card">
        <div class="sidebar-head">Reports</div>
        <ul class="list-group sidebar-menu rounded-0">
            <li class="list-group-item">
                <a href="{{ route('executive.dashboard') }}"
                   class="d-flex justify-content-between align-items-center text-decoration-none">
                    <span><i class="bi bi-bar-chart-line me-2"></i>Executive Dashboard</span>
                    <i class="bi bi-arrow-right"></i>
                </a>
            </li>
        </ul>
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

    {{-- Controls --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <span class="font-brand fw-900" style="font-size:22px">
            @php
                $labels = [
                    'active'           => 'Active',
                    'awaiting-manager' => 'Awaiting You',
                    'in-progress'      => 'In Progress',
                    'on-hold'          => 'On Hold',
                    'closed'           => 'Closed',
                ];
            @endphp
            {{ $labels[$status] ?? 'Active' }}
        </span>
        <form method="GET" action="{{ route('executive.tickets.index') }}"
              class="d-flex gap-2 flex-wrap" id="searchForm">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="search-wrap">
                <i class="bi bi-search" style="color:var(--tm)"></i>
                <input type="text" name="search" id="searchInput"
                       placeholder="Search support requests…"
                       value="{{ $search }}" autocomplete="off">
            </div>
            <select class="sort-select" name="sort" onchange="this.form.submit()">
                <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>Newest first</option>
                <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>Oldest first</option>
            </select>
        </form>
    </div>

    {{-- Tab pills --}}
    <div class="d-flex flex-wrap gap-2 mb-3">
        @php
            $tabs = [
                'active'           => ['label' => 'Active',       'count' => $counts['active']],
                'awaiting-manager' => ['label' => 'Awaiting You', 'count' => $counts['awaiting_manager']],
                'in-progress'      => ['label' => 'In Progress',  'count' => $counts['in_progress']],
                'on-hold'          => ['label' => 'On Hold',      'count' => $counts['on_hold']],
                'closed'           => ['label' => 'Closed',       'count' => $counts['closed']],
            ];
        @endphp
        @foreach($tabs as $key => $tab)
            <a href="{{ route('executive.tickets.index', array_merge(request()->except('status'), ['status' => $key])) }}"
               class="tab-pill {{ $status === $key ? 'active' : '' }}">
                {{ $tab['label'] }} ({{ $tab['count'] }})
            </a>
        @endforeach
    </div>

    {{-- Ticket list --}}
    <div class="d-flex flex-column gap-3" id="ticketList">

        @forelse($tickets as $ticket)
            @php
                $cardClass = match($ticket->status) {
                    'For Acknowledgment'      => 'unassigned',
                    'In Progress Service Request'   => 'in-progress',
                    'In Progress Service Report' => 'in-progress',
                    'On Hold'               => 'unassigned',
                    'Closed'                => 'closed',
                    default                 => 'unassigned'
                };
                $badgeClass = match($ticket->status) {
                    'For Acknowledgment'      => 'badge-unassigned',
                    'In Progress Service Request'   => 'badge-in-progress',
                    'In Progress Service Report' => 'badge-in-progress',
                    'On Hold'               => 'badge-unassigned',
                    default                 => ''
                };
                // In Progress Service Report is isolated from the underlying "actively
                // fixing it" In Progress Service Request status (see TicketReportProgress) — real
                // status now, not a derived label.
                $badgeLabel = match($ticket->status) {
                    'For Acknowledgment'      => '<i class="bi bi-inbox me-1"></i>Awaiting You',
                    'In Progress Service Request'   => '<i class="bi bi-gear-fill me-1"></i>In Progress',
                    'In Progress Service Report' => '<i class="bi bi-file-earmark-text me-1"></i>Preparing Report',
                    'On Hold'               => '<i class="bi bi-pause-circle me-1"></i>On Hold',
                    'Closed'                => '<i class="bi bi-check-circle-fill me-1"></i>Closed',
                    default                 => '● ' . $ticket->status
                };
                $priorityClass = match($ticket->ticket_type) {
                    'Critical' => 'pri-critical',
                    'High'     => 'pri-high',
                    'Medium'   => 'pri-medium',
                    'Low'      => 'pri-low',
                    default    => ''
                };
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
                                Escalation Level {{ $ticket->escalation_level }}
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
                        <span class="meta-item"><i class="bi bi-person"></i> {{ $ticket->user->name }}</span>
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
                </div>

                {{-- Action buttons --}}
                <div class="d-flex gap-2 flex-wrap">

                    {{-- For Acknowledgment: Acknowledge --}}
                    @if($ticket->status === 'For Acknowledgment')
                        <form method="POST" action="{{ route('executive.tickets.acknowledge', $ticket) }}">
                            @csrf
                            <button type="submit" class="btn-acknowledge">
                                <i class="bi bi-eye me-1"></i>Acknowledge
                            </button>
                        </form>
                    @endif

                    {{-- Fix is done — mark it so, separate from writing up the report. --}}
                    @if($ticket->status === 'In Progress Service Request')
                        <form method="POST" action="{{ route('executive.tickets.start-report', $ticket) }}">
                            @csrf
                            <button type="submit" class="btn-resolve">
                                <i class="bi bi-check2 me-1"></i>Mark Fixed
                            </button>
                        </form>
                        <button type="button" class="btn-acknowledge"
                                onclick="openPauseModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-pause-circle me-1"></i>Pause — Need Info
                        </button>
                    @endif
                    {{-- Fix already marked done — now prepare & submit the service report. --}}
                    @if($ticket->status === 'In Progress Service Report')
                        <button class="btn-resolve"
                                onclick="openResolveModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-file-earmark-text me-1"></i>Prepare Service Report
                        </button>
                    @endif

                    {{-- On Hold: waiting on the requestor — Resume Work only --}}
                    @if($ticket->status === 'On Hold')
                        @if($ticket->hold_reason)
                            <div class="w-100 mb-2 p-2 px-3 rounded" style="background:var(--ygl);font-size:12px;color:var(--gd)">
                                <i class="bi bi-pause-circle me-1"></i>
                                <strong>Waiting on requestor:</strong> {{ $ticket->hold_reason }}
                            </div>
                        @endif
                        <form method="POST" action="{{ route('executive.tickets.resume', $ticket) }}">
                            @csrf
                            <button type="submit" class="btn-resolve">
                                <i class="bi bi-play-circle me-1"></i>Resume Work
                            </button>
                        </form>
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
        <div class="d-flex justify-content-center mt-4">
            {{ $tickets->onEachSide(1)->links('pagination::bootstrap-5') }}
        </div>
    @endif

@endsection

{{-- ══ MODALS ══ --}}
@section('modals')

    {{-- Resolve modal --}}
    <div class="modal fade" id="resolveModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header-gd d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Resolve <em>Support Request</em></h5>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <form method="POST" id="resolveForm">
                    @csrf
                    <div class="modal-body px-4 py-4">
                        <div class="resolve-info p-3 mb-3">
                            <i class="bi bi-check-circle me-1"></i>
                            Resolving <strong id="resolveRef"></strong> sends it straight to the requestor for confirmation — Helpdesk is notified by email.
                        </div>
                        <label class="form-label">Resolution summary <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="resolution_notes" rows="3" required
                                  placeholder="Describe how this was resolved…"></textarea>
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

    <x-service-report-modal />
@endsection

@section('scripts')
<script>
$(function () {

    /* ── Search submits on Enter only (native form submit) — no more
           reloading the page mid-keystroke while the user is still typing. ── */

    /* ── Resolve modal ── */
    window.openResolveModal = function (ticketId, ticketNumber) {
        $('#resolveRef').text('#' + ticketNumber);
        $('#resolveForm').attr('action', '/executive/tickets/' + ticketId + '/resolve');
        new bootstrap.Modal('#resolveModal').show();
    };

    /* ── Pause modal ── */
    window.openPauseModal = function (ticketId, ticketNumber) {
        $('#pauseRef').text('#' + ticketNumber);
        $('#pauseForm').attr('action', '/executive/tickets/' + ticketId + '/pause');
        new bootstrap.Modal('#pauseModal').show();
    };

});
</script>
@endsection
