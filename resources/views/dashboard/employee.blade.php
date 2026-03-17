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
                <div class="d-flex gap-2 flex-wrap mt-3">
                    <a href="{{ route('employee.tickets.show', $ticket) }}"
                       class="btn-view-detail">
                        <i class="bi bi-eye"></i> View Details
                    </a>
                    @if(in_array($ticket->status, ['Open', 'In Progress']))
                        <button class="btn-cancel-ticket"
                                onclick="confirmCancel('{{ $ticket->id }}', '{{ $ticket->ticket_number }}')">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </button>
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
                            <div class="mb-3">
                                <label class="form-label">
                                    Select device type <span class="text-danger">*</span>
                                </label>
                                <div class="row g-2">
                                    <div class="col-3">
                                        <div class="device-opt" data-device="Mobile">
                                            <span class="d-icon">📱</span>
                                            <span class="d-lbl">Mobile</span>
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="device-opt selected" data-device="Laptop">
                                            <span class="d-icon">💻</span>
                                            <span class="d-lbl">Laptop</span>
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="device-opt" data-device="Desktop">
                                            <span class="d-icon">🖥</span>
                                            <span class="d-lbl">Desktop</span>
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="device-opt" data-device="Printer">
                                            <span class="d-icon">🖨</span>
                                            <span class="d-lbl">Printer</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">
                                    Request category <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="mCategory">
                                    <option value="">Choose a category…</option>
                                    <option value="Hardware">Hardware — physical damage or malfunction</option>
                                    <option value="Software">Software — app crash or installation issue</option>
                                    <option value="Network">Network — connectivity or VPN</option>
                                    <option value="Account">Account — login, password, or access</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
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
                                <label class="form-label">
                                    Subject <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="mSubject"
                                       name="subject"
                                       placeholder="Brief description of the issue…">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">
                                    Describe the issue <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" id="mDesc"
                                          name="concern" rows="3"
                                          placeholder="What happened, when it started, what you've already tried…"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">
                                    Additional details <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control"
                                          name="request_details" rows="2"
                                          placeholder="Steps to reproduce, error messages, etc…"></textarea>
                            </div>
                            <div class="row g-3 mb-1">
                                <div class="col-6">
                                    <label class="form-label">Asset tag / serial no.</label>
                                    <input type="text" class="form-control" id="mAsset"
                                           placeholder="e.g. LT-00432">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Location</label>
                                    <input type="text" class="form-control" id="mLocation"
                                           placeholder="e.g. Floor 3, Desk B12">
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
                                        <div class="review-lbl">Device</div>
                                        <div class="fw-700" id="rv-device">💻 Laptop</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="review-lbl">Category</div>
                                        <div class="fw-700" id="rv-cat">—</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="review-lbl">Priority</div>
                                        <div class="fw-700" id="rv-pri">⚡ Medium</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="review-lbl">Asset</div>
                                        <div class="fw-700" id="rv-asset">—</div>
                                    </div>
                                </div>
                            </div>
                            <div class="review-detail p-3">
                                <div class="review-lbl mb-1">Subject</div>
                                <div class="font-brand fw-800 mb-3"
                                     style="font-size:15px" id="rv-subject">—</div>
                                <div class="review-lbl mb-1">What happens next</div>
                                <div style="font-size:13px;color:var(--tm)">
                                    Your ticket will be assigned to an available IT technician.
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

    /* ── Cancel modal ── */
    window.confirmCancel = function (ticketId, ticketNumber) {
        $('#cancelTicketRef').text('#' + ticketNumber);
        $('#cancelForm').attr('action', '{{ url("employee/tickets") }}/' + ticketId + '/cancel');
        new bootstrap.Modal('#cancelModal').show();
    };

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
            // Populate review
            const dIcons = { Mobile: '📱', Laptop: '💻', Desktop: '🖥', Printer: '🖨' };
            const dev = $('.device-opt.selected').data('device') || 'Laptop';
            const cat = $('#mCategory').val() || '—';
            const pri = $('.pri-opt.selected').data('pri') || 'Medium';
            const asset = $('#mAsset').val() || '—';

            $('#rv-device').text((dIcons[dev] || '💻') + ' ' + dev);
            $('#rv-cat').text(cat);
            $('#rv-pri').text('⚡ ' + pri);
            $('#rv-asset').text(asset);
            $('#rv-subject').text($('#mSubject').val() || '—');

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

    /* ── Validate steps before continuing ── */
    $('#btnNext').on('click', function () {
        if (step === 1) {
            if (!$('#mCategory').val()) {
                alert('Please select a request category.');
                return;
            }
            // Sync hidden fields
            $('#hCategory').val($('#mCategory').val());
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
            // Sync hidden fields
            $('#hAsset').val($('#mAsset').val());
            $('#hLocation').val($('#mLocation').val());
            showStep(3);

        } else if (step === 3) {
            // Submit the form via AJAX
            const form = $('#ticketForm');
            $.ajax({
                url:  form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                success: function (response) {
                    $('#newTicketRef').text(response.ticket_number);
                    showStep(4);
                    // Reload counts after short delay
                    setTimeout(() => location.reload(), 3000);
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
        $('#mCategory').val('');
        $('#mSubject, #mDesc').val('');
        $('#mAsset, #mLocation').val('');
        $('.device-opt').removeClass('selected')
            .filter('[data-device="Laptop"]').addClass('selected');
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
</script>
@endsection