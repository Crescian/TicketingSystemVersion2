@extends('layouts.app')

@section('title', 'Work Hours Calendar — LGICT')

@section('nav-role-badge')
    @if($isOwnCalendar)
        <span class="role-badge dark"><i class="bi bi-tools me-1"></i>IT Support Specialist</span>
    @else
        <span class="role-badge"><i class="bi bi-person-check-fill me-1"></i>Supervisor</span>
    @endif
@endsection
@section('avatar-initials',
    strtoupper(substr(Auth::user()->name, 0, 1)) .
    strtoupper(substr(Auth::user()->name, strpos(Auth::user()->name, ' ') + 1, 1))
)
@section('nav-username', Auth::user()->name)

@section('hero-title')
    <h1>{{ $isOwnCalendar ? 'My Work Hours' : 'Work Hours' }} <em>CALENDAR</em></h1>
@endsection
@section('hero-subtitle', $isOwnCalendar
    ? 'Your schedule, workload, and availability.'
    : "Real-time visibility into each specialist's availability, workload, and schedule.")

@section('hero-stats')
    <div class="d-flex gap-2 flex-wrap">
        @unless($isOwnCalendar)
            <div class="stat-pill">
                <span class="num">{{ $technicians->count() }}</span>
                <span class="lbl">Specialists</span>
            </div>
        @endunless
        @if($view === 'day')
            <div class="stat-pill warn">
                <span class="num">{{ $rows->sum(fn($r) => $r['stats']['ticket_count']) }}</span>
                <span class="lbl">Tickets This Day</span>
            </div>
            <div class="stat-pill">
                <span class="num">{{ $rows->sum(fn($r) => $r['stats']['overtime_minutes']) > 0 ? floor($rows->sum(fn($r) => $r['stats']['overtime_minutes']) / 60) . 'h' : 0 }}</span>
                <span class="lbl">Overtime</span>
            </div>
            <div class="stat-pill">
                <span class="num">{{ $rows->where('on_leave', true)->count() }}</span>
                <span class="lbl">On Leave</span>
            </div>
        @endif
    </div>
@endsection

@section('sidebar')
    @if($isOwnCalendar)
        <div class="sidebar-card mb-3">
            <ul class="list-group sidebar-menu rounded-0">
                <li class="list-group-item">
                    <a href="{{ route('technician.dashboard') }}" class="d-flex align-items-center gap-2 text-decoration-none w-100">
                        <i class="bi bi-grid me-1"></i>My Work Queue
                    </a>
                </li>
                <li class="list-group-item active">
                    <a href="{{ route('technician.calendar') }}" class="d-flex align-items-center gap-2 text-decoration-none w-100">
                        <i class="bi bi-calendar3-week me-1"></i>My Work Hours Calendar
                    </a>
                </li>
            </ul>
        </div>
    @else
        <div class="sidebar-card mb-3">
            <ul class="list-group sidebar-menu rounded-0">
                <li class="list-group-item">
                    <a href="{{ route('supervisor.support.dashboard') }}" class="d-flex align-items-center gap-2 text-decoration-none w-100">
                        <i class="bi bi-grid me-1"></i>Ticket Queue
                    </a>
                </li>
                <li class="list-group-item active">
                    <a href="{{ route('supervisor.support.calendar') }}" class="d-flex align-items-center gap-2 text-decoration-none w-100">
                        <i class="bi bi-calendar3-week me-1"></i>Work Hours Calendar
                    </a>
                </li>
            </ul>
        </div>

        <div class="sidebar-card mb-3">
            <div class="sidebar-head"><i class="bi bi-gear me-1"></i>Settings</div>
            <ul class="list-group sidebar-menu rounded-0">
                <li class="list-group-item">
                    <a href="{{ route('portal.sla-rules.index') }}" class="d-flex align-items-center gap-2 text-decoration-none w-100">
                        <i class="bi bi-stopwatch me-1"></i>SLA Rules
                    </a>
                </li>
                <li class="list-group-item">
                    <a href="{{ route('portal.holidays.index') }}" class="d-flex align-items-center gap-2 text-decoration-none w-100">
                        <i class="bi bi-calendar-x me-1"></i>Holidays
                    </a>
                </li>
                <li class="list-group-item">
                    <a href="{{ route('portal.leaves.index') }}" class="d-flex align-items-center gap-2 text-decoration-none w-100">
                        <i class="bi bi-calendar-minus me-1"></i>Leave
                    </a>
                </li>
            </ul>
        </div>
    @endif

    <div class="sidebar-card">
        <div class="sidebar-head">Legend</div>
        <div class="p-2 px-3" style="font-size:11px;color:var(--tm)">
            <div class="mb-1"><span class="legend-dot normal"></span> On-time task</div>
            <div class="mb-1"><span class="legend-dot overtime"></span> Overtime task</div>
            <div class="mb-1"><span class="legend-dot lunch"></span> Lunch (12-1 PM)</div>
            <div><span class="legend-dot leave"></span> Approved leave</div>
        </div>
    </div>
@endsection

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
        <div class="d-flex gap-2">
            @foreach(['day' => 'Day', 'week' => 'Week', 'month' => 'Month'] as $v => $label)
                <a href="{{ route($routeName, array_merge(request()->except('view'), ['view' => $v])) }}"
                   class="tab-pill {{ $view === $v ? 'active' : '' }}">{{ $label }}</a>
            @endforeach
        </div>

        <div class="d-flex align-items-center gap-2">
            <a class="cal-nav-btn" href="{{ route($routeName, array_merge(request()->except('date'), ['date' => $nav['prev']])) }}">
                <i class="bi bi-chevron-left"></i>
            </a>
            <a class="cal-nav-btn" href="{{ route($routeName, array_merge(request()->except('date'), ['date' => $nav['today']])) }}">
                Today
            </a>
            <a class="cal-nav-btn" href="{{ route($routeName, array_merge(request()->except('date'), ['date' => $nav['next']])) }}">
                <i class="bi bi-chevron-right"></i>
            </a>
        </div>

        @unless($isOwnCalendar)
            <form method="GET" action="{{ route($routeName) }}" class="d-flex gap-2">
                <input type="hidden" name="view" value="{{ $view }}">
                <input type="hidden" name="date" value="{{ $date->format('Y-m-d') }}">
                <select class="sort-select" name="technician_id" onchange="this.form.submit()">
                    <option value="">All Technicians</option>
                    @foreach($allTechnicians as $t)
                        <option value="{{ $t->id }}" {{ request('technician_id') === $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </form>
        @endunless
    </div>

    <div class="font-brand fw-900 mb-3" style="font-size:20px">
        @if($view === 'day')
            {{ $date->format('l, M d, Y') }}
        @elseif($view === 'week')
            {{ $columns->first()?->format('M d') }} – {{ $columns->last()?->format('M d, Y') }}
        @else
            {{ $date->format('F Y') }}
        @endif
    </div>

    @if($view === 'day')
        {{-- Day view: one timeline row per technician --}}
        @forelse($rows as $row)
            @php
                $tech = $row['technician'];
                $initials = strtoupper(substr($tech->name, 0, 1)) .
                            strtoupper(substr($tech->name, strpos($tech->name, ' ') + 1, 1));
            @endphp
            <div class="timeline-card {{ $row['on_leave'] ? 'on-leave' : '' }}">
                <div class="timeline-head">
                    <div class="tech-av-lg">{{ $initials }}</div>
                    <div class="tech-name">{{ $tech->name }}</div>
                    @if($row['on_leave'])
                        <span class="badge-status" style="background:#eee;color:#888;margin-left:8px">
                            <i class="bi bi-calendar-minus me-1"></i>On Approved Leave
                        </span>
                    @endif
                </div>

                @if(!$row['on_leave'])
                    <div class="timeline-row">
                        <div class="timeline-block lunch" style="left:{{ $row['lunch_left_pct'] }}%; width:{{ $row['lunch_width_pct'] }}%" title="Lunch"></div>
                        @foreach($row['blocks'] as $b)
                            <a href="#" onclick="return false;"
                               class="timeline-block {{ $b['is_overtime'] ? 'overtime' : 'normal' }}"
                               style="left:{{ $b['left_pct'] }}%; width:{{ $b['width_pct'] }}%"
                               title="#{{ $b['ticket']->ticket_number }} — {{ $b['ticket']->subject }} ({{ $b['ticket']->scheduled_start->timezone('Asia/Manila')->format('g:i A') }}–{{ $b['ticket']->scheduled_end->timezone('Asia/Manila')->format('g:i A') }})">
                                <span class="tb-label">#{{ $b['ticket']->ticket_number }}</span>
                            </a>
                        @endforeach
                    </div>
                    <div class="timeline-axis-labels">
                        <span>{{ $row['axis_start']->timezone('Asia/Manila')->format('g:i A') }}</span>
                        <span>{{ $row['axis_end']->timezone('Asia/Manila')->format('g:i A') }}</span>
                    </div>

                    <div class="timeline-stats">
                        <span>Allocated: {{ floor($row['stats']['allocated_minutes'] / 60) }}h {{ $row['stats']['allocated_minutes'] % 60 }}m</span>
                        <span>Remaining: {{ floor($row['stats']['remaining_minutes'] / 60) }}h {{ $row['stats']['remaining_minutes'] % 60 }}m</span>
                        <span>Tickets: {{ $row['stats']['ticket_count'] }}</span>
                        <span>Overtime: {{ floor($row['stats']['overtime_minutes'] / 60) }}h {{ $row['stats']['overtime_minutes'] % 60 }}m</span>
                        <span class="workload-pct {{ $row['stats']['workload_pct'] >= 100 ? 'over' : '' }}">{{ $row['stats']['workload_pct'] }}% workload</span>
                    </div>
                @endif
            </div>
        @empty
            <div class="p-4 text-center" style="color:var(--tm)">No technicians found.</div>
        @endforelse
    @elseif($view === 'week')
        {{-- Week view: workload heatmap matrix — only 5 columns, never wide enough to need scrolling --}}
        <div class="table-responsive">
            <table class="heatmap-table">
                <thead>
                    <tr>
                        <th>Technician</th>
                        @foreach($columns as $col)
                            <th>{{ $col->format('D, M j') }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td class="heatmap-tech">{{ $row['technician']->name }}</td>
                            @foreach($row['cells'] as $cell)
                                <td>
                                    <a class="heatmap-cell {{ $cell['level'] }}"
                                       href="{{ route($routeName, ['view' => 'day', 'date' => $cell['date']->format('Y-m-d'), 'technician_id' => $row['technician']->id]) }}"
                                       title="{{ $cell['date']->format('M j') }} — {{ $cell['ticket_count'] }} ticket(s)">
                                        @if($cell['on_leave'])
                                            <span class="hc-leave">Leave</span>
                                        @else
                                            <span class="hc-pct">{{ $cell['workload_pct'] }}%</span>
                                            <span class="hc-count">{{ $cell['ticket_count'] }} tix</span>
                                        @endif
                                    </a>
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="99" class="p-4 text-center" style="color:var(--tm)">No technicians found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        {{-- Month view: a real Mon-Fri calendar grid per technician, week-rows
             stacked vertically — a single wide table here would need ~22 columns
             and force horizontal scrolling, so this uses page space downward instead. --}}
        @forelse($rows as $row)
            @php
                $weeks = $row['cells']->groupBy(fn ($cell) => $cell['date']->copy()->startOfWeek(\Carbon\Carbon::MONDAY)->format('Y-m-d'));
            @endphp
            <div class="month-tech-block">
                <div class="month-tech-head">{{ $row['technician']->name }}</div>
                <div class="month-grid month-grid-header">
                    <div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div>
                </div>
                @foreach($weeks as $week)
                    @php $weekByDow = $week->keyBy(fn ($c) => $c['date']->dayOfWeekIso); @endphp
                    <div class="month-grid">
                        @for($dow = 1; $dow <= 5; $dow++)
                            @if(isset($weekByDow[$dow]))
                                @php $cell = $weekByDow[$dow]; @endphp
                                <a class="month-day-cell heatmap-cell {{ $cell['level'] }}"
                                   href="{{ route($routeName, ['view' => 'day', 'date' => $cell['date']->format('Y-m-d'), 'technician_id' => $row['technician']->id]) }}"
                                   title="{{ $cell['date']->format('M j') }} — {{ $cell['ticket_count'] }} ticket(s)">
                                    <span class="month-day-num">{{ $cell['date']->format('j') }}</span>
                                    @if($cell['on_leave'])
                                        <span class="hc-leave">Leave</span>
                                    @else
                                        <span class="hc-pct">{{ $cell['workload_pct'] }}%</span>
                                        <span class="hc-count">{{ $cell['ticket_count'] }} tix</span>
                                    @endif
                                </a>
                            @else
                                <div class="month-day-cell empty"></div>
                            @endif
                        @endfor
                    </div>
                @endforeach
            </div>
        @empty
            <div class="p-4 text-center" style="color:var(--tm)">No technicians found.</div>
        @endforelse
    @endif

@endsection

@section('styles')
    .tech-av-lg { width:30px; height:30px; background:var(--gd); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:900; color:var(--yg); font-family:'Nunito',sans-serif; flex-shrink:0; }
    .tech-name { font-weight:700; font-size:13px; }

    .cal-nav-btn { background:none; border:1.5px solid var(--bd); color:var(--gd); font-family:'Nunito',sans-serif; font-weight:800; font-size:13px; padding:7px 14px; border-radius:50px; cursor:pointer; transition:all .2s; text-decoration:none; display:inline-flex; align-items:center; }
    .cal-nav-btn:hover { border-color:var(--gl); background:var(--ygl); color:var(--gd); }

    .legend-dot { display:inline-block; width:10px; height:10px; border-radius:3px; margin-right:4px; vertical-align:middle; }
    .legend-dot.normal { background:#8fd98f; border:1px solid #4caf50; }
    .legend-dot.overtime { background:#f0a0a0; border:1px solid #e24b4a; }
    .legend-dot.lunch { background:repeating-linear-gradient(45deg,#e0e0e0,#e0e0e0 3px,#ececec 3px,#ececec 6px); }
    .legend-dot.leave { background:repeating-linear-gradient(45deg,#eee,#eee 3px,#ddd 3px,#ddd 6px); }

    /* ── Day view timeline ── */
    .timeline-card { border:1.5px solid var(--bd); border-radius:16px; padding:16px; margin-bottom:14px; background:var(--cr); }
    .timeline-card.on-leave { background:repeating-linear-gradient(45deg,var(--cr),var(--cr) 10px,#f3f3f3 10px,#f3f3f3 20px); }
    .timeline-head { display:flex; align-items:center; gap:10px; margin-bottom:10px; }
    .timeline-row { position:relative; height:44px; background:var(--ygl); border-radius:8px; overflow:visible; margin:8px 0; }
    .timeline-block { position:absolute; top:2px; bottom:2px; border-radius:6px; overflow:hidden; text-decoration:none; display:flex; align-items:center; }
    .timeline-block.lunch { background:repeating-linear-gradient(45deg,#e0e0e0,#e0e0e0 6px,#ececec 6px,#ececec 12px); z-index:1; }
    .timeline-block.normal { background:#8fd98f; border:1px solid #4caf50; z-index:2; cursor:default; }
    .timeline-block.overtime { background:#f0a0a0; border:1px solid #e24b4a; z-index:2; cursor:default; }
    .tb-label { font-size:10px; font-weight:700; color:#1a4a1a; padding:0 4px; white-space:nowrap; overflow:hidden; }
    .timeline-block.overtime .tb-label { color:#7a1a1a; }
    .timeline-axis-labels { display:flex; justify-content:space-between; font-size:11px; color:var(--tm); font-weight:700; }
    .timeline-stats { display:flex; gap:16px; flex-wrap:wrap; font-size:12px; color:var(--tm); margin-top:8px; }
    .workload-pct { font-weight:800; color:var(--gd); }
    .workload-pct.over { color:var(--rd); }

    /* ── Week/Month heatmap ── */
    .heatmap-table { width:100%; border-collapse:separate; border-spacing:4px; }
    .heatmap-tech { font-weight:800; font-size:13px; color:var(--gd); white-space:nowrap; padding-right:10px; }
    .heatmap-table th { font-size:10px; font-weight:800; color:var(--tm); text-transform:uppercase; letter-spacing:.4px; padding:4px; white-space:nowrap; }
    .heatmap-cell { display:flex; flex-direction:column; align-items:center; justify-content:center; border-radius:8px; padding:8px 4px; text-decoration:none; min-width:64px; min-height:44px; transition:transform .15s; }
    .heatmap-cell:hover { transform:scale(1.05); }
    .heatmap-cell.green  { background:#d4f0d4; color:#1a5a3a; }
    .heatmap-cell.yellow { background:#fdf0c8; color:#8a6d1a; }
    .heatmap-cell.red    { background:#fde0e0; color:#8b1a1a; }
    .heatmap-cell.leave  { background:repeating-linear-gradient(45deg,#eee,#eee 6px,#e0e0e0 6px,#e0e0e0 12px); color:#888; }
    .hc-pct { font-weight:800; font-size:13px; }
    .hc-count { font-size:10px; opacity:.75; }
    .hc-leave { font-size:11px; font-weight:700; }

    /* ── Month view: real Mon-Fri calendar grid per technician, stacked vertically —
         always 5 columns wide so it never needs horizontal scrolling regardless of
         how many technicians or weeks are shown. ── */
    .month-tech-block { margin-bottom:22px; }
    .month-tech-head { font-weight:800; font-size:14px; color:var(--gd); margin-bottom:8px; }
    .month-grid { display:grid; grid-template-columns:repeat(5, 1fr); gap:6px; margin-bottom:6px; }
    .month-grid-header { font-size:10px; font-weight:800; color:var(--tm); text-transform:uppercase; letter-spacing:.4px; text-align:center; margin-bottom:2px; }
    .month-day-cell { display:flex; flex-direction:column; align-items:center; justify-content:center; border-radius:8px; padding:8px 4px; text-decoration:none; min-height:56px; min-width:0; transition:transform .15s; position:relative; }
    .month-day-cell:hover { transform:scale(1.03); }
    .month-day-cell.empty { background:transparent; }
    .month-day-num { font-size:10px; font-weight:700; opacity:.6; position:absolute; top:4px; left:6px; }
@endsection
