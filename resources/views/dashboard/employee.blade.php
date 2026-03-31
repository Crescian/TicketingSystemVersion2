@extends('layouts.app')

@section('title', 'My Support Tickets — LGICT')

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
    <h1>MY <em>SUPPORT</em><br>TICKETS</h1>
@endsection
@section('hero-subtitle', 'Track your requests and get IT help fast.')

@section('hero-stats')
    <div class="d-flex gap-2 flex-wrap">
        <div class="stat-pill">
            <span class="num" id="cnt-open">{{ $counts['open'] }}</span>
            <span class="lbl">Open</span>
        </div>
        <div class="stat-pill warn">
            <span class="num" id="cnt-prog">{{ $counts['in_progress'] }}</span>
            <span class="lbl">In Progress</span>
        </div>
        <div class="stat-pill">
            <span class="num" id="cnt-esc">{{ $counts['escalated'] }}</span>
            <span class="lbl">Escalated</span>
        </div>
        <div class="stat-pill">
            <span class="num" id="cnt-done">{{ $counts['resolved'] }}</span>
            <span class="lbl">Resolved</span>
        </div>
    </div>
@endsection

@section('hero-cta')
    <button class="btn-new" data-bs-toggle="modal" data-bs-target="#ticketModal">
        <i class="bi bi-plus-lg me-1"></i> New Ticket
    </button>
@endsection

{{-- ── Page-specific styles ── --}}
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

    /* Success */
    .success-icon { width: 72px; height: 72px; background: var(--ygl); border-radius: 50%; font-size: 36px; }
    .ticket-ref   { background: var(--gd); color: var(--yg); font-family: 'Nunito', sans-serif; font-weight: 900; font-size: 20px; padding: 10px 28px; border-radius: 50px; letter-spacing: 1px; display: inline-block; }

    /* Cancel button */
    .btn-cancel-ticket { background: none; border: 1.5px solid #e24b4a; color: #e24b4a; font-family: 'Nunito', sans-serif; font-weight: 800; font-size: 13px; padding: 7px 18px; border-radius: 50px; transition: all .2s; cursor: pointer; }
    .btn-cancel-ticket:hover { background: #e24b4a; color: #fff; }

    /* View details button */
    .btn-view-detail { background: none; border: 1.5px solid var(--bd); color: var(--tm); font-family: 'Nunito', sans-serif; font-weight: 800; font-size: 13px; padding: 7px 18px; border-radius: 50px; transition: all .2s; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
    .btn-view-detail:hover { border-color: var(--gl); color: var(--gd); }
    /* ── Chat button ── */
    .btn-chat { background:#e8eeff; color:#2a4ab0; font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; padding:7px 16px; border-radius:20px; border:1.5px solid #b8c8ff; cursor:pointer; transition:all .2s; display:inline-flex; align-items:center; gap:5px; }
    .btn-chat:hover { background:#d0dcff; border-color:#8898dd; }
    .chat-count-badge { background:#e24b4a; color:#fff; font-size:10px; font-weight:900; border-radius:20px; padding:1px 6px; font-family:'Nunito',sans-serif; min-width:18px; text-align:center; }

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
@endsection

{{-- ══ SIDEBAR ══ --}}
@section('sidebar')
    {{-- Nav menu --}}
    <div class="sidebar-card mb-3">
        <div class="sidebar-head">My Tickets</div>
        <ul class="list-group sidebar-menu rounded-0" id="sideNav">
            <li class="list-group-item {{ $status === 'all' ? 'active' : '' }}">
                <a href="{{ route('employee.tickets.index', ['status' => 'all']) }}" class="d-flex justify-content-between align-items-center text-decoration-none w-100">
                    <span><i class="bi bi-grid me-2"></i>All tickets</span>
                    <span class="badge-count">{{ $counts['all'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'open' ? 'active' : '' }}">
                <a href="{{ route('employee.tickets.index', ['status' => 'open']) }}" class="d-flex justify-content-between align-items-center text-decoration-none w-100">
                    <span><i class="bi bi-circle me-2"></i>Open</span>
                    <span class="badge-count">{{ $counts['open'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'in progress' ? 'active' : '' }}">
                <a href="{{ route('employee.tickets.index', ['status' => 'in progress']) }}" class="d-flex justify-content-between align-items-center text-decoration-none w-100">
                    <span><i class="bi bi-arrow-repeat me-2"></i>In progress</span>
                    <span class="badge-count">{{ $counts['in_progress'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'escalated' ? 'active' : '' }}">
                <a href="{{ route('employee.tickets.index', ['status' => 'escalated']) }}" class="d-flex justify-content-between align-items-center text-decoration-none w-100">
                    <span><i class="bi bi-exclamation-triangle me-2"></i>Escalated</span>
                    <span class="badge-count">{{ $counts['escalated'] }}</span>
                </a>
            </li>
            <li class="list-group-item {{ $status === 'resolved' ? 'active' : '' }}">
                <a href="{{ route('employee.tickets.index', ['status' => 'resolved']) }}" class="d-flex justify-content-between align-items-center text-decoration-none w-100">
                    <span><i class="bi bi-check-circle me-2"></i>Resolved</span>
                    <span class="badge-count">{{ $counts['resolved'] }}</span>
                </a>
            </li>
        </ul>
    </div>

    {{-- Filter card --}}
    <div class="sidebar-card filter-card">
        <div class="sidebar-head">Filter</div>
        <form method="GET" action="{{ route('employee.tickets.index') }}" class="p-3 d-flex flex-column gap-2">
            <input type="hidden" name="status" value="{{ $status }}">
            <div>
                <label class="form-label mb-1">Category</label>
                <select class="form-select form-select-sm" name="category">
                    <option value="">All categories</option>
                    @foreach(['Hardware','Software','Network','Account','Other'] as $cat)
                        <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>
                            {{ $cat }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label mb-1">From date</label>
                <input type="date" class="form-control form-control-sm"
                       name="from_date" value="{{ request('from_date') }}">
            </div>
            <button type="submit" class="btn btn-filter w-100 mt-1">
                Apply filters
            </button>
            @if(request('category') || request('from_date'))
                <a href="{{ route('employee.tickets.index', ['status' => $status]) }}"
                   class="btn btn-sm btn-outline-secondary w-100">
                    Clear filters
                </a>
            @endif
        </form>
    </div>
@endsection

{{-- ══ MAIN CONTENT ══ --}}
@section('content')

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
                    'all'         => 'All Tickets',
                    'open'        => 'Open Tickets',
                    'in progress' => 'In Progress',
                    'escalated'   => 'Escalated',
                    'resolved'    => 'Resolved',
                    'cancelled'   => 'Cancelled',
                ];
            @endphp
            {{ $labels[$status] ?? 'All Tickets' }}
        </span>
        <form method="GET" action="{{ route('employee.tickets.index') }}"
              class="d-flex gap-2" id="searchForm">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="search-wrap">
                <i class="bi bi-search" style="color:var(--tm)"></i>
                <input type="text" name="search" id="searchInput"
                       placeholder="Search tickets…"
                       value="{{ $search }}" autocomplete="off">
            </div>
            <select class="sort-select" name="sort" onchange="this.form.submit()">
                <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Newest first</option>
                <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Oldest first</option>
                <option value="priority" {{ request('sort') === 'priority' ? 'selected' : '' }}>Priority</option>
            </select>
        </form>
    </div>

    {{-- Tab pills --}}
    <div class="d-flex flex-wrap gap-2 mb-3" id="tabRow">
        @php
            $tabs = [
                'all'         => ['label' => 'All',         'count' => $counts['all']],
                'open'        => ['label' => 'Open',        'count' => $counts['open']],
                'in progress' => ['label' => 'In Progress', 'count' => $counts['in_progress']],
                'escalated'   => ['label' => 'Escalated',   'count' => $counts['escalated']],
                'resolved'    => ['label' => 'Resolved',    'count' => $counts['resolved']],
                'cancelled'   => ['label' => 'Cancelled',   'count' => $counts['cancelled']],
            ];
        @endphp
        @foreach($tabs as $key => $tab)
            <a href="{{ route('employee.tickets.index', array_merge(request()->except('status'), ['status' => $key])) }}"
               class="tab-pill {{ $status === $key ? 'active' : '' }}">
                {{ $tab['label'] }} ({{ $tab['count'] }})
            </a>
        @endforeach
    </div>

    {{-- Ticket cards --}}
    <div class="d-flex flex-column gap-3" id="ticketList">

        @forelse($tickets as $ticket)
            @php
                $statusClass = match($ticket->status) {
                    'Open'        => 'open',
                    'In Progress' => 'in-progress',
                    'Escalated'   => 'escalated',
                    'Resolved'    => 'resolved',
                    'Cancelled'   => 'cancelled',
                    default       => ''
                };
                $badgeClass = match($ticket->status) {
                    'Open'        => 'badge-open',
                    'In Progress' => 'badge-in-progress',
                    'Escalated'   => 'badge-escalated',
                    'Resolved'    => 'badge-resolved',
                    'Cancelled'   => 'badge-cancelled',
                    default       => ''
                };
                $badgeIcon = match($ticket->status) {
                    'Open'        => '●',
                    'In Progress' => '⟳',
                    'Escalated'   => '⚠',
                    'Resolved'    => '✓',
                    'Cancelled'   => '✕',
                    default       => ''
                };
                $initials = $ticket->assignedTo
                    ? strtoupper(substr($ticket->assignedTo->name, 0, 1)) . strtoupper(substr($ticket->assignedTo->name, strpos($ticket->assignedTo->name, ' ') + 1, 1))
                    : '—';
            @endphp

            <div class="ticket-card {{ $statusClass }} p-3"
                 data-status="{{ strtolower($ticket->status) }}"
                 data-category="{{ $ticket->request_category }}">

                {{-- Header --}}
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="ticket-id">#{{ $ticket->ticket_number }}</span>
                        <span class="badge-type">{{ $ticket->request_category }}</span>
                        @php
                            $priColor = match($ticket->ticket_type) {
                                'High'   => '#e24b4a',
                                'Medium' => '#f5c842',
                                'Low'    => '#4a7c4a',
                                default  => 'var(--tm)'
                            };
                        @endphp
                        <span class="meta-item" style="color:{{ $priColor }};font-weight:700">
                            ● {{ $ticket->ticket_type }}
                        </span>
                    </div>
                    <span class="badge-status {{ $badgeClass }}">
                        {{ $badgeIcon }} {{ $ticket->status }}
                    </span>
                </div>

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
                    @if($ticket->status === 'Resolved' && $ticket->resolved_at)
                        <span class="meta-item" style="color:var(--gm)">
                            <i class="bi bi-clock-history"></i>
                            Resolved {{ $ticket->resolved_at->diffForHumans() }}
                        </span>
                    @else
                        <span class="meta-item">
                            <i class="bi bi-clock"></i>
                            {{ $ticket->created_at->diffForHumans() }}
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
                    <a href="{{ route('employee.tickets.show', $ticket) }}"
                    class="btn-view-detail">
                        <i class="bi bi-eye me-1"></i>View Details
                    </a>

                    @if(in_array($ticket->status, ['Open', 'In Progress']))
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

                    {{-- ── Rate & Feedback (resolved only, not yet rated) ── --}}
                    @if($ticket->status === 'Resolved' && !$ticket->feedback)
                        <button class="btn-resolve" style="background:#fff4cc;color:#7a5a00;border:1.5px solid #f5c842"
                                onclick="openFeedbackModal('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-star me-1"></i>Rate & Feedback
                        </button>
                    @elseif($ticket->status === 'Resolved' && $ticket->feedback)
                        <span class="meta-item" style="color:#f5c842;font-weight:700">
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
                    No tickets found.
                </div>
                <div style="font-size:13px;color:var(--tm);margin-top:4px">
                    @if($status !== 'all')
                        You have no {{ $status }} tickets.
                    @else
                        You haven't submitted any tickets yet.
                    @endif
                </div>
                <button class="btn-new mt-3"
                        data-bs-toggle="modal"
                        data-bs-target="#ticketModal">
                    <i class="bi bi-plus-lg me-1"></i> Submit Your First Ticket
                </button>
            </div>
        @endforelse

    </div>

    {{-- Pagination --}}
    @if($tickets->hasPages())
        <div class="mt-4">{{ $tickets->links() }}</div>
    @endif

@endsection

{{-- ══ NEW TICKET MODAL ══ --}}
@section('modals')

    {{-- Submit Ticket Modal --}}
    <div class="modal fade" id="ticketModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header-gd d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">New <em>Support</em> Ticket</h5>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <form method="POST" action="{{ route('employee.tickets.store') }}" id="ticketForm">
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
                        <div class="form-step d-none" id="fs2">
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

    {{-- Cancel Confirmation Modal --}}
    <div class="modal fade" id="cancelModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header-gd d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Cancel <em>Ticket</em></h5>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <div class="modal-body px-4 py-4">
                    <div class="p-3 mb-3 rounded"
                         style="background:rgba(226,75,74,.1);border:1px solid rgba(226,75,74,.3);color:#e24b4a;font-size:13px;font-weight:600">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Are you sure you want to cancel ticket
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
                            <i class="bi bi-x-circle me-1"></i>Yes, Cancel Ticket
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
                        Employee
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
@endsection
@section('scripts')
<script>/* ── Dynamic SLA categories from DB ── */
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
        $('#feedbackForm').attr('action', '/employee/tickets/' + ticketId + '/feedback');

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
            $('#cancelForm').attr('action', '{{ url("employee/tickets") }}/' + ticketId + '/cancel');
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

        function showStep(n) {
            step = n;
            ['fs1','fs2','fs3','fsSuccess'].forEach((id, i) => {
                $('#' + id).toggleClass('d-none', i !== n - 1);
            });
            for (let i = 1; i <= 3; i++) {
                $('#si' + i).toggleClass('active', i === n).toggleClass('done', i < n);
                if (i < 3) $('#sl' + i).toggleClass('done', i < n);
            }
            $('#btnBack').css('visibility', n > 1 && n < 4 ? 'visible' : 'hidden');

            if (n === 3) {
                const mainCat = $('.cat-main-opt.selected').data('cat') || '—';
                const subCat  = $('.cat-sub-opt.selected').data('sub')  || '—';
                const pri     = $('.pri-opt.selected').data('pri')       || 'Medium';
                const asset   = $('#mAsset').val() || '—';

                $('#rv-device').text(mainCat);       // ← reuse rv-device for main category
                $('#rv-cat').text(subCat);           // ← sub category
                $('#rv-pri').text('⚡ ' + pri);
                $('#rv-asset').text(asset);
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

        /* ── Validate steps ── */
        $('#btnNext').on('click', function () {
           if (step === 1) {
                    if (!$('.cat-main-opt.selected').length) {
                        alert('Please select a category.');
                        return;
                    }
                    if (!$('.cat-sub-opt.selected').length) {
                        alert('Please select a specific issue.');
                        return;
                    }

                    // ── Sync hidden fields
                    const mainCat = $('.cat-main-opt.selected').data('cat') || '';
                    const subCat  = $('.cat-sub-opt.selected').data('sub')  || '';
                    $('#hCategory').val(mainCat + ' — ' + subCat);
                    $('#hTicketType').val($('.pri-opt.selected').data('pri') || 'Medium');

                    showStep(2);
                } else if (step === 2) {
                if (!$('#mSubject').val().trim()) {
                    alert('Please enter a subject.');
                    return;
                }
                if (!$('#mDesc').val().trim()) {
                    alert('Please describe the issue.');
                    return;
                }
                $('#hAsset').val($('#mAsset').val());
                $('#hLocation').val($('#mLocation').val());
                showStep(3);

            } else if (step === 3) {

                // ── Re-sync ALL hidden fields right before submit
                const mainCat = $('.cat-main-opt.selected').data('cat') || '';
                const subCat  = $('.cat-sub-opt.selected').data('sub')  || '';
                const pri     = $('.pri-opt.selected').data('pri')      || 'Medium';

                $('#hCategory').val(mainCat + ' — ' + subCat);
                $('#hTicketType').val(pri);
                $('#hAsset').val($('#mAsset').val());
                $('#hLocation').val($('#mLocation').val());

                // ── Final check
                if (!$('#hCategory').val().trim()) {
                    alert('Please go back and select a specific issue.');
                    return;
                }

                console.log('Submitting with:', {
                    request_category: $('#hCategory').val(),
                    ticket_type:      $('#hTicketType').val(),
                });

                const form = $('#ticketForm');
                $.ajax({
                    url:  form.attr('action'),
                    type: 'POST',
                    data: form.serialize(),
                    success: function (response) {
                        $('#newTicketRef').text(response.ticket_number);
                        showStep(4);

                        // ── Redirect to dashboard after 3 seconds, NOT location.reload()
                        setTimeout(() => {
                            window.location.href = '{{ route("employee.tickets.index") }}';
                        }, 3000);
                    },
                    error: function (xhr) {
                        const errors = xhr.responseJSON?.errors;
                        if (errors) {
                            alert(Object.values(errors).flat().join('\n'));
                        } else {
                            alert('Something went wrong. Please try again.');
                        }
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
</script>
@endsection