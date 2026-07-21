@extends('layouts.admin')

@section('title', 'IT Admin — My Tickets')

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
    <h1><strong>ADMIN</strong> <em>TICKET</em><br>WORKSPACE</h1>
@endsection
@section('hero-subtitle', 'Acknowledge, work, and resolve tickets assigned to you.')

@section('hero-stats')
    <div class="d-flex gap-2 flex-wrap">
        <div class="stat-pill esc">
            <span class="num">{{ $counts['awaiting_ack'] }}</span>
            <span class="lbl">Awaiting Ack.</span>
        </div>
        <div class="stat-pill warn">
            <span class="num">{{ $counts['ready_start'] }}</span>
            <span class="lbl">Ready to Start</span>
        </div>
        <div class="stat-pill open">
            <span class="num">{{ $counts['in_progress'] }}</span>
            <span class="lbl">In Progress</span>
        </div>
        <div class="stat-pill done">
            <span class="num">{{ $counts['closed'] }}</span>
            <span class="lbl">Closed</span>
        </div>
    </div>
@endsection

{{-- ══ SIDEBAR ══ --}}
@section('sidebar')

    {{-- Queue nav --}}
    <div class="sidebar-card mb-3">
        <div class="sidebar-head red">
            <i class="bi bi-shield-fill me-1"></i>My Queue
        </div>
        <ul class="list-group sidebar-menu rounded-0">
            @php
                $sideItems = [
                    ['key' => 'active',       'icon' => 'bi-grid',              'label' => 'Active',            'count' => $counts['active'],       'cls' => 'dark'],
                    ['key' => 'awaiting-ack', 'icon' => 'bi-hourglass-split',   'label' => 'Awaiting Ack.',     'count' => $counts['awaiting_ack'], 'cls' => 'red'],
                    ['key' => 'ready-start',  'icon' => 'bi-stopwatch',        'label' => 'Ready to Start',    'count' => $counts['ready_start'],  'cls' => 'red'],
                    ['key' => 'in-progress',  'icon' => 'bi-gear-fill',        'label' => 'In Progress',       'count' => $counts['in_progress'],  'cls' => 'green'],
                    ['key' => 'closed',       'icon' => 'bi-check-circle',     'label' => 'Closed',            'count' => $counts['closed'],       'cls' => 'green'],
                ];
            @endphp
            @foreach($sideItems as $item)
                <li class="list-group-item {{ $status === $item['key'] ? 'active' : '' }}">
                    <a href="{{ route('admin.dashboard', ['status' => $item['key']]) }}"
                       class="d-flex justify-content-between align-items-center text-decoration-none">
                        <span><i class="bi {{ $item['icon'] }} me-2"></i>{{ $item['label'] }}</span>
                        <span class="badge-count {{ $item['cls'] }}">{{ $item['count'] }}</span>
                    </a>
                </li>
            @endforeach
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
                    <div class="tech-load">{{ $selfActive }} active ticket{{ $selfActive !== 1 ? 's' : '' }}</div>
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
                            {{ $tech->active_tickets }} active ticket{{ $tech->active_tickets !== 1 ? 's' : '' }}
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
                <span style="font-size:13px;font-weight:600;color:var(--tm)">Open tickets total</span>
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

    {{-- Controls --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <span class="font-brand fw-900" style="font-size:22px">
            @php
                $labels = [
                    'active'       => 'Active Tickets',
                    'awaiting-ack' => 'Awaiting Acknowledgement',
                    'ready-start'  => 'Ready to Start',
                    'in-progress'  => 'In Progress',
                    'closed'       => 'Closed Tickets',
                ];
            @endphp
            {{ $labels[$status] ?? 'Active Tickets' }}
        </span>
        <form method="GET" action="{{ route('admin.dashboard') }}"
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
                'awaiting-ack' => ['label' => 'Awaiting Ack.', 'count' => $counts['awaiting_ack'], 'red' => true],
                'ready-start'  => ['label' => 'Ready to Start','count' => $counts['ready_start'],  'red' => true],
                'in-progress'  => ['label' => 'In Progress',   'count' => $counts['in_progress'],  'red' => false],
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
                $cardClass = match($ticket->status) {
                    'Awaiting Administrator Acknowledgement' => 'awaiting-ack',
                    'Awaiting Administrator SLA Start'       => 'ready-start',
                    'Admin In Progress'                      => 'admin-progress',
                    'Closed'                                 => 'closed',
                    default                                  => 'awaiting-ack'
                };
                $badgeClass = match($ticket->status) {
                    'Awaiting Administrator Acknowledgement' => 'bs-await-ack',
                    'Awaiting Administrator SLA Start'       => 'bs-ready-start',
                    'Admin In Progress'                      => 'bs-admin-progress',
                    'Closed'                                 => 'bs-closed',
                    default                                  => ''
                };
                $badgeLabel = match($ticket->status) {
                    'Awaiting Administrator Acknowledgement' => '<i class="bi bi-hourglass-split me-1"></i>Awaiting Your Ack.',
                    'Awaiting Administrator SLA Start'       => '<i class="bi bi-stopwatch me-1"></i>Ready to Start',
                    'Admin In Progress'                      => '<i class="bi bi-gear-fill me-1"></i>In Progress',
                    'Closed'                                 => '<i class="bi bi-check-circle me-1"></i>Closed',
                    default                                  => '● ' . $ticket->status
                };
                $priorityClass = match($ticket->ticket_type) {
                    'High'   => 'pri-high',
                    'Medium' => 'pri-medium',
                    'Low'    => 'pri-low',
                    default  => ''
                };

                $hoursOpen   = $ticket->created_at->diffInHours(now());
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
                <div class="ticket-desc mb-3">{{ Str::limit($ticket->concern, 150) }}</div>

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
                </div>

                {{-- Action buttons --}}
                <div class="d-flex gap-2 flex-wrap">

                    {{-- Awaiting Administrator Acknowledgement --}}
                    @if($ticket->status === 'Awaiting Administrator Acknowledgement')
                        <button class="btn-resolve-a"
                                onclick="openAckModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-check2-circle me-1"></i>Acknowledge
                        </button>
                        <button class="btn-cancel-modal"
                                onclick="openDeclineModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-x-circle me-1"></i>Decline
                        </button>
                    @endif

                    {{-- Awaiting Administrator SLA Start --}}
                    @if($ticket->status === 'Awaiting Administrator SLA Start')
                        <button class="btn-resolve-a"
                                onclick="openStartModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-play-circle me-1"></i>Start Work
                        </button>
                        <button class="btn-reassign-a"
                                onclick="openReassignModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-arrow-left-right me-1"></i>Reassign
                        </button>
                        <button class="btn-cancel-modal"
                                onclick="openDeclineModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-x-circle me-1"></i>Decline
                        </button>
                    @endif

                    {{-- Admin In Progress --}}
                    @if($ticket->status === 'Admin In Progress')
                        <button class="btn-reassign-a"
                                onclick="openReassignModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-arrow-left-right me-1"></i>Reassign
                        </button>
                        <button class="btn-resolve-a"
                                onclick="openResolveModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-check-circle me-1"></i>Resolve & Close
                        </button>
                        <button class="btn-cancel-modal"
                                onclick="openEscModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-exclamation-triangle me-1"></i>Escalate
                        </button>
                    @endif

                    {{-- Available on every non-closed status --}}
                    @if($ticket->status !== 'Closed')
                        <button class="btn-chat"
                                onclick="openAdminChatModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-chat-dots me-1"></i>Message
                            @php $unread = \App\Models\TicketMessage::where('ticket_id', $ticket->id)
                                ->where('sender_id', '!=', Auth::id())
                                ->where('is_read', false)->count(); @endphp
                            @if($unread > 0)
                                <span class="chat-count-badge" id="badge-{{ $ticket->id }}">{{ $unread }}</span>
                            @endif
                        </button>
                    @endif

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
                    No tickets in this view.
                </div>
                <div style="font-size:13px;color:var(--tm);margin-top:4px">
                    You're all caught up.
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
                        <div class="mb-3">
                            <label class="form-label">Estimated time to resolve <span class="text-danger">*</span></label>
                            <select class="form-select" name="estimated_time" required>
                                <option value="Under 1 hour">Under 1 hour</option>
                                <option value="1-2 hours">1-2 hours</option>
                                <option value="2-4 hours">2-4 hours</option>
                                <option value="4-8 hours">4-8 hours</option>
                                <option value="More than 1 day">More than 1 day</option>
                            </select>
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
                    <h5 class="mb-0">Decline <em>Ticket</em></h5>
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
                                  placeholder="Why are you declining this ticket?"></textarea>
                    </div>
                    <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
                        <button type="button" class="btn-cancel-modal" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-confirm red">
                            <i class="bi bi-x-circle me-1"></i>Decline Ticket
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
                <div class="modal-hdr-dark d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Reassign <em>to Another Admin</em></h5>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <form method="POST" id="reassignForm">
                    @csrf
                    <div class="modal-body px-4 py-4">
                        <div class="info-box-red p-3 mb-3">
                            <i class="bi bi-info-circle me-1"></i>
                            Ticket <strong id="reassignRef"></strong> — reassigning to a different IT Admin.
                        </div>
                        <label class="form-label mb-2">Select IT Admin</label>
                        <div class="d-flex flex-column gap-2 mb-3" id="techListReassign">
                            @foreach($technicians as $tech)
                                @php
                                    $initials = strtoupper(substr($tech->name, 0, 1)) .
                                                strtoupper(substr($tech->name, strpos($tech->name, ' ') + 1, 1));
                                    $isFull   = $tech->availability === 'full';
                                    $loadPct  = min(100, $tech->active_tickets * 25);
                                    $barClass = match($tech->availability) { 'busy' => 'busy', 'full' => 'full', default => '' };
                                    $badge = match($tech->availability) {
                                        'free' => ['cls' => 'green', 'label' => 'Available'],
                                        'busy' => ['cls' => 'dark',  'label' => 'Busy'],
                                        'full' => ['cls' => 'red',   'label' => 'Full'],
                                    };
                                @endphp
                                <div class="tech-select-option {{ $isFull ? 'disabled' : '' }} {{ $loop->first && !$isFull ? 'selected' : '' }}"
                                     data-tech-id="{{ $tech->id }}" data-tech-name="{{ $tech->name }}">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <div class="tc-av normal">{{ $initials }}</div>
                                        <div>
                                            <div class="ts-name">{{ $tech->name }}</div>
                                            <div class="ts-load">
                                                {{ $tech->active_tickets }} active ticket{{ $tech->active_tickets !== 1 ? 's' : '' }}
                                            </div>
                                        </div>
                                        <span class="badge-count {{ $badge['cls'] }} ms-auto">{{ $badge['label'] }}</span>
                                    </div>
                                    <div class="load-bar-wrap">
                                        <div class="load-bar {{ $barClass }}" style="width:{{ $loadPct }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <input type="hidden" name="technician_id" id="selectedReassignTechId"
                               value="{{ $technicians->where('availability', '!=', 'full')->first()?->id }}">
                        <label class="form-label">Reassignment notes</label>
                        <textarea class="form-control" name="notes" rows="2"
                                  placeholder="Instructions or context…"></textarea>
                    </div>
                    <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
                        <button type="button" class="btn-cancel-modal" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-confirm">
                            <i class="bi bi-person-plus me-1"></i>Confirm Reassignment
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
                <form method="POST" id="resolveForm">
                    @csrf
                    <div class="modal-body px-4 py-4">
                        <div class="info-box-green p-3 mb-3">
                            <i class="bi bi-check-circle me-1"></i>
                            Resolving <strong id="resolveRef"></strong> will close the ticket immediately.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Resolution summary <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="resolution_notes" rows="3" required
                                      placeholder="Describe what was done, root cause, and how it was resolved…"></textarea>
                        </div>
                        <div>
                            <label class="form-label">Root cause category</label>
                            <select class="form-select" name="root_cause" required>
                                <option value="Hardware failure — replacement required">Hardware failure — replacement required</option>
                                <option value="Software / configuration error">Software / configuration error</option>
                                <option value="Network infrastructure issue">Network infrastructure issue</option>
                                <option value="User access / permissions">User access / permissions</option>
                                <option value="Third-party vendor issue">Third-party vendor issue</option>
                                <option value="Human error">Human error</option>
                            </select>
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
                    <h5 class="mb-0">Full Ticket <em>History</em></h5>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <div class="modal-body px-4 py-4">
                    <div class="mb-3 p-2 px-3 rounded" style="background:var(--ygl);font-size:13px">
                        <strong id="historyRef"></strong> — Complete audit trail of all status changes and actions.
                    </div>
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
    $(document).on('click', '.tech-select-option:not(.disabled)', function () {
        $(this).closest('#techListReassign').find('.tech-select-option').removeClass('selected');
        $(this).addClass('selected');
        $('#selectedReassignTechId').val($(this).data('tech-id'));
    });

    /* ── Acknowledge modal ── */
    window.openAckModal = function (ticketId, ticketNumber) {
        $('#ackRef').text('#' + ticketNumber);
        $('#ackForm').attr('action', '/admin/tickets/' + ticketId + '/acknowledge');
        new bootstrap.Modal('#ackModal').show();
    };

    /* ── Start modal ── */
    window.openStartModal = function (ticketId, ticketNumber) {
        $('#startRef').text('#' + ticketNumber);
        $('#startForm').attr('action', '/admin/tickets/' + ticketId + '/start');
        new bootstrap.Modal('#startModal').show();
    };

    /* ── Decline modal ── */
    window.openDeclineModal = function (ticketId, ticketNumber) {
        $('#declineRef').text('#' + ticketNumber);
        $('#declineForm').attr('action', '/admin/tickets/' + ticketId + '/decline');
        new bootstrap.Modal('#declineModal').show();
    };

    /* ── Reassign modal ── */
    window.openReassignModal = function (ticketId, ticketNumber) {
        $('#reassignRef').text('#' + ticketNumber);
        $('#reassignForm').attr('action', '/admin/tickets/' + ticketId + '/reassign');
        new bootstrap.Modal('#reassignModal').show();
    };

    /* ── Resolve modal ── */
    window.openResolveModal = function (ticketId, ticketNumber) {
        $('#resolveRef').text('#' + ticketNumber);
        $('#resolveForm').attr('action', '/admin/tickets/' + ticketId + '/resolve');
        new bootstrap.Modal('#resolveModal').show();
    };

    /* ── Escalate modal ── */
    window.openEscModal = function (ticketId, ticketNumber) {
        $('#escRef').text('#' + ticketNumber);
        $('#escForm').attr('action', '/admin/tickets/' + ticketId + '/escalate');
        new bootstrap.Modal('#escModal').show();
    };

    /* ── History modal ── */
    window.openHistoryModal = function (ticketId, ticketNumber) {
        $('#historyRef').text('#' + ticketNumber);
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
                const histories = data.status_histories || [];
                if (!histories.length) {
                    $('#historyTimeline').html('<div style="color:var(--tm);font-size:13px">No history available.</div>');
                    return;
                }
                const iconMap = {
                    'Awaiting Administrator Acknowledgement': { icon: 'bi-hourglass-split', cls: 'assigned'  },
                    'Awaiting Administrator SLA Start':       { icon: 'bi-stopwatch',        cls: 'working'   },
                    'Admin In Progress':                      { icon: 'bi-gear-fill',        cls: 'working'   },
                    'Closed':                                 { icon: 'bi-check-circle',     cls: 'resolved'  },
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

window.openAdminChatModal = function (ticketId, ticketNumber) {
    currentAdminChatTicketId = ticketId;
    $('#adminChatTicketRef').text('#' + ticketNumber);
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