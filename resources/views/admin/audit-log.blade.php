@extends('layouts.admin')

@section('title', 'Audit Log — LGICT Admin')

@section('nav-role-badge')
    <span class="role-badge-admin"><i class="bi bi-shield-fill me-1"></i>IT Admin</span>
@endsection
@section('avatar-initials',
    strtoupper(substr(Auth::user()->name, 0, 1)) .
    strtoupper(substr(Auth::user()->name, strpos(Auth::user()->name, ' ') + 1, 1))
)
@section('nav-username', Auth::user()->name)

@section('hero-title')
    <h1>AUDIT <em>LOG</em></h1>
@endsection
@section('hero-subtitle', 'Full system activity trail — every action, who did it, and when.')

@section('hero-stats')
    <div class="d-flex gap-2 flex-wrap">
        <div class="stat-pill">
            <span class="num">{{ $counts['today'] }}</span>
            <span class="lbl">Today</span>
        </div>
        <div class="stat-pill warn">
            <span class="num">{{ $counts['week'] }}</span>
            <span class="lbl">This Week</span>
        </div>
        <div class="stat-pill red">
            <span class="num">{{ $counts['critical'] }}</span>
            <span class="lbl">Critical</span>
        </div>
        <div class="stat-pill">
            <span class="num">{{ number_format($counts['all_time']) }}</span>
            <span class="lbl">All Time</span>
        </div>
    </div>
@endsection

@section('styles')
    /* ── Settings Sidebar ── */
    .settings-sidebar { background:#fff; border-radius:16px; border:1.5px solid var(--bd); overflow:hidden; }
    .settings-nav .nav-item { border-bottom:1px solid var(--bd); }
    .settings-nav .nav-item:last-child { border-bottom:none; }
    .settings-nav .nav-link { padding:11px 16px; font-weight:600; font-size:13px; color:var(--gd); display:flex; align-items:center; gap:9px; border-radius:0 !important; transition:background .15s; text-decoration:none; }
    .settings-nav .nav-link:hover { background:var(--ygl); color:var(--gd); text-decoration:none; }
    .settings-nav .nav-link.active { background:var(--ygl); border-left:4px solid var(--yg); font-weight:700; color:var(--gd); }
    .settings-nav .badge-count { background:var(--gd); color:var(--yg); font-size:11px; border-radius:20px; padding:2px 8px; margin-left:auto; font-weight:800; }

    /* ── Toolbar ── */
    .toolbar { background:#fff; border-radius:14px; border:1.5px solid var(--bd); padding:14px 18px; }
    .filter-select { border:1.5px solid var(--bd); border-radius:50px; font-size:13px; color:var(--gd); background:#fff; padding:8px 14px; outline:none; cursor:pointer; }
    .filter-select:focus { border-color:var(--gl); }
    .btn-export { background:var(--gd); color:var(--yg); font-family:'Nunito',sans-serif; font-weight:900; font-size:13px; padding:9px 20px; border-radius:50px; border:none; cursor:pointer; transition:all .2s; display:flex; align-items:center; gap:6px; white-space:nowrap; text-decoration:none; }
    .btn-export:hover { background:var(--gm); transform:translateY(-1px); color:var(--yg); }

    /* ── Audit Table ── */
    .audit-table-wrap { background:#fff; border-radius:16px; border:1.5px solid var(--bd); overflow:hidden; }
    .audit-table-scroll { overflow-x:auto; }
    .audit-table { width:100%; border-collapse:collapse; min-width:960px; }
    .audit-table thead tr { background:var(--gd); }
    .audit-table thead th { padding:12px 16px; font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; color:var(--yg); text-transform:uppercase; letter-spacing:.5px; border:none; white-space:nowrap; }
    .audit-table tbody tr.data-row { border-bottom:1px solid var(--bd); transition:background .15s; animation:fadeUp .3s ease both; cursor:pointer; }
    .audit-table tbody tr.data-row:hover { background:#fffff5; }
    .audit-table tbody td { padding:11px 16px; font-size:13px; vertical-align:middle; }

    /* User avatars */
    .user-av { width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-family:'Nunito',sans-serif; font-weight:900; font-size:11px; flex-shrink:0; }
    .av-admin    { background:var(--rdl); color:var(--rd); }
    .av-tech     { background:#fff4cc; color:#7a5a00; }
    .av-helpdesk { background:#d4f0d4; color:var(--gm); }
    .av-employee { background:var(--ygl); color:var(--gd); }
    .user-name { font-family:'Nunito',sans-serif; font-weight:800; font-size:13px; color:var(--gd); }
    .user-role { font-size:11px; color:var(--tm); margin-top:1px; }

    /* Action chips */
    .action-chip { display:inline-flex; align-items:center; gap:5px; border-radius:20px; padding:4px 10px; font-size:11px; font-weight:800; white-space:nowrap; }
    .action-chip.create     { background:#e8f5ee; color:#1a5a3a; }
    .action-chip.update     { background:#fff4cc; color:#7a5a00; }
    .action-chip.delete     { background:var(--rdl); color:var(--rd); }
    .action-chip.login      { background:var(--ygl); color:var(--gm); }
    .action-chip.logout     { background:#f0f0f0; color:#555; }
    .action-chip.reset      { background:#e8eeff; color:#2a4ab0; }
    .action-chip.deactivate { background:var(--rdl); color:var(--rd); }
    .action-chip.reactivate { background:#e8f5ee; color:#1a5a3a; }
    .action-chip.escalate   { background:#fde8d0; color:#7a3a00; }
    .action-chip.resolve    { background:#e8f5ee; color:#1a5a3a; }

    .module-chip { display:inline-flex; align-items:center; gap:4px; border-radius:8px; padding:3px 9px; font-size:11px; font-weight:700; background:var(--cr); color:var(--tm); border:1px solid var(--bd); white-space:nowrap; }
    .meta-cell { font-size:12px; color:var(--tm); font-weight:600; }
    .meta-cell .ip { font-family:'Nunito',sans-serif; font-weight:700; color:var(--gd); }

    /* Severity dots */
    .sev-dot { width:8px; height:8px; border-radius:50%; display:inline-block; flex-shrink:0; }
    .sev-dot.info     { background:#4a7c4a; }
    .sev-dot.warning  { background:#f5c842; }
    .sev-dot.critical { background:#e24b4a; }

    /* Expand chevron */
    .expand-icon { font-size:11px; color:var(--tm); transition:transform .25s, color .2s; display:inline-block; }
    tr.data-row.expanded .expand-icon { transform:rotate(90deg); color:var(--gl); }

    /* Detail rows */
    .detail-row { display:none; }
    .detail-row.show { display:table-row; animation:fadeUp .2s ease both; }
    .detail-row td { padding:0 !important; border-bottom:2px solid var(--yg) !important; border-top:none !important; }
    .detail-inner { padding:16px 20px 18px 52px; background:#f8f8f4; }
    .detail-box { background:#fff; border-radius:12px; border:1.5px solid var(--bd); padding:16px 20px; }
    .detail-label { font-size:11px; font-weight:700; color:var(--tm); text-transform:uppercase; letter-spacing:.4px; margin-bottom:3px; }
    .detail-val { font-weight:600; color:var(--gd); word-break:break-all; font-size:13px; }

    /* Diff table */
    .diff-wrap { border-radius:10px; border:1.5px solid var(--bd); overflow:hidden; }
    .diff-header { display:grid; grid-template-columns:140px 1fr 1fr; background:var(--gd); }
    .diff-header span { padding:8px 12px; font-size:11px; font-weight:800; color:var(--yg); text-transform:uppercase; letter-spacing:.4px; }
    .diff-row { display:grid; grid-template-columns:140px 1fr 1fr; border-top:1px solid var(--bd); font-size:12px; }
    .diff-field  { padding:9px 12px; font-weight:700; color:var(--tm); background:var(--cr); border-right:1px solid var(--bd); }
    .diff-before { padding:9px 12px; color:#8b1a1a; background:#fff5f5; border-right:1px solid var(--bd); font-family:monospace; }
    .diff-after  { padding:9px 12px; color:#1a5a3a; background:#f0fff4; font-family:monospace; }

    /* Pagination */
    .pagination-wrap { padding:14px 18px; border-top:1px solid var(--bd); display:flex; align-items:center; justify-content:space-between; font-size:13px; color:var(--tm); }
    .pg-btn { width:30px; height:30px; border-radius:8px; border:1.5px solid var(--bd); background:#fff; color:var(--gd); font-size:13px; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all .2s; text-decoration:none; }
    .pg-btn:hover { border-color:var(--gl); background:var(--ygl); color:var(--gd); }
    .pg-btn.active { background:var(--gd); color:var(--yg); border-color:var(--gd); }
    .pg-btn:disabled { opacity:.4; cursor:not-allowed; }

    @keyframes fadeUp { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }
    .data-row:nth-child(1)  { animation-delay:.04s }
    .data-row:nth-child(3)  { animation-delay:.08s }
    .data-row:nth-child(5)  { animation-delay:.12s }
    .data-row:nth-child(7)  { animation-delay:.16s }
    .data-row:nth-child(9)  { animation-delay:.20s }
    .data-row:nth-child(11) { animation-delay:.24s }
@endsection

{{-- ══ SIDEBAR ══ --}}
@section('sidebar')
    <div class="settings-sidebar">
        <div class="sidebar-head"><i class="bi bi-gear me-1"></i>Settings</div>
        <ul class="nav flex-column settings-nav">
            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.users.index') }}">
                    <i class="bi bi-people"></i>Users
                    <span class="badge-count">{{ \App\Models\User::count() }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="bi bi-shield-check"></i>Roles
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="bi bi-building"></i>Departments
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="bi bi-tags"></i>Categories
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="bi bi-clock-history"></i>SLA Rules
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="{{ route('admin.audit-log') }}">
                    <i class="bi bi-journal-text"></i>Audit Log
                </a>
            </li>
        </ul>
    </div>
@endsection

{{-- ══ MAIN CONTENT ══ --}}
@section('content')

    {{-- Toolbar --}}
    <div class="toolbar d-flex flex-wrap align-items-center gap-3 mb-3">
      <form method="GET" action="{{ route('admin.audit-log') }}"
            class="d-flex flex-wrap align-items-center gap-3 w-100"
            id="filterForm">

          <div class="search-wrap flex-grow-1" style="max-width:260px">
              <i class="bi bi-search" style="color:var(--tm)"></i>
              <input type="text" name="search" id="searchInput"
                    placeholder="Search by user, ticket, notes…"
                    value="{{ $search }}" autocomplete="off">
          </div>

          <select class="filter-select" name="action" onchange="this.form.submit()">
              <option value="">All Actions</option>
              @foreach(['Create','Update','Escalate','Resolve','Cancel'] as $a)
                  <option value="{{ $a }}" {{ $action === $a ? 'selected' : '' }}>
                      {{ $a }}
                  </option>
              @endforeach
          </select>

          <select class="filter-select" name="severity" onchange="this.form.submit()">
              <option value="">All Severity</option>
              <option value="info"     {{ $severity === 'info'     ? 'selected' : '' }}>Info</option>
              <option value="warning"  {{ $severity === 'warning'  ? 'selected' : '' }}>Warning</option>
              <option value="critical" {{ $severity === 'critical' ? 'selected' : '' }}>Critical</option>
          </select>

          <input type="date" class="filter-select"
                name="date" value="{{ $date }}"
                style="border-radius:50px;padding:8px 14px"
                onchange="this.form.submit()">

          <a href="{{ route('admin.audit-log') }}" class="btn-export ms-auto">
              <i class="bi bi-download"></i> Export CSV
          </a>

      </form>
  </div>

    {{-- Tab pills --}}
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('admin.audit-log') }}"
          class="tab-pill {{ $severity === '' ? 'active' : '' }}">
            All Events ({{ number_format($counts['all_time']) }})
        </a>
        <a href="{{ route('admin.audit-log', ['severity' => 'info']) }}"
          class="tab-pill {{ $severity === 'info' ? 'active' : '' }}">
            Info
        </a>
        <a href="{{ route('admin.audit-log', ['severity' => 'warning']) }}"
          class="tab-pill {{ $severity === 'warning' ? 'active' : '' }}">
            Warning
        </a>
        <a href="{{ route('admin.audit-log', ['severity' => 'critical']) }}"
          class="tab-pill {{ $severity === 'critical' ? 'active' : '' }}">
            <span class="sev-dot critical"></span>
            Critical ({{ $counts['critical'] }})
        </a>
    </div>

    {{-- Audit Table --}}
    <div class="audit-table-wrap">
        <div class="audit-table-scroll">
            <table class="audit-table">
                <thead>
                    <tr>
                        <th style="width:36px"></th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Module</th>
                        <th>Description</th>
                        <th>IP Address</th>
                        <th>Severity</th>
                        <th>Date &amp; Time</th>
                    </tr>
                </thead>
                <tbody>
                  @forelse($logs as $index => $log)
                      @php
                          $severity    = App\Http\Controllers\Admin\AuditLogController::getSeverity($log->new_status);
                          $actionType  = App\Http\Controllers\Admin\AuditLogController::getActionType($log->new_status);
                          $actionLabel = App\Http\Controllers\Admin\AuditLogController::getActionLabel($log->new_status);
                          $actionIcon  = App\Http\Controllers\Admin\AuditLogController::getActionIcon($log->new_status);
                          $avatarClass = App\Http\Controllers\Admin\AuditLogController::getAvatarClass($log->changedBy?->role?->role_name);
                          $rowId       = 'row-' . $log->id;

                          $nameParts = explode(' ', $log->changedBy?->name ?? 'Unknown');
                          $initials  = strtoupper(substr($nameParts[0], 0, 1)) .
                                      strtoupper(substr(end($nameParts), 0, 1));

                          $sevColor = match($severity) {
                              'critical' => 'var(--rd)',
                              'warning'  => 'var(--gd)',
                              default    => 'var(--gd)',
                          };
                      @endphp

                      <tr class="data-row" onclick="toggleDetail('{{ $rowId }}')">
                          <td style="text-align:center">
                              <i class="bi bi-chevron-right expand-icon"
                                id="icon-{{ $rowId }}"></i>
                          </td>
                          <td>
                              <div class="d-flex align-items-center gap-2">
                                  <div class="user-av {{ $avatarClass }}">{{ $initials }}</div>
                                  <div>
                                      <div class="user-name">
                                          {{ $log->changedBy?->name ?? 'System' }}
                                      </div>
                                      <div class="user-role">
                                          {{ $log->changedBy?->role?->role_name ?? '—' }}
                                      </div>
                                  </div>
                              </div>
                          </td>
                          <td>
                              <span class="action-chip {{ $actionType }}">
                                  <i class="bi {{ $actionIcon }}"></i> {{ $actionLabel }}
                              </span>
                          </td>
                          <td>
                              <span class="module-chip">
                                  <i class="bi bi-ticket-perforated me-1"></i>Ticket
                              </span>
                          </td>
                          <td style="max-width:260px;color:var(--gd);font-weight:600">
                              @if($log->ticket)
                                  #{{ $log->ticket->ticket_number }} —
                              @endif
                              {{ Str::limit($log->notes, 60) }}
                          </td>
                          <td class="meta-cell">
                              <div class="ip">—</div>
                              <div style="font-size:11px">System</div>
                          </td>
                          <td>
                              <span class="d-flex align-items-center gap-2">
                                  <span class="sev-dot {{ $severity }}"></span>
                                  <span style="font-size:12px;font-weight:700;color:{{ $sevColor }}">
                                      {{ ucfirst($severity) }}
                                  </span>
                              </span>
                          </td>
                          <td class="meta-cell" style="white-space:nowrap">
                              <div style="font-weight:700;color:var(--gd)">
                                  {{ \Carbon\Carbon::parse($log->changed_at)->format('M d, Y') }}
                              </div>
                              <div>{{ \Carbon\Carbon::parse($log->changed_at)->format('h:i A') }}</div>
                          </td>
                      </tr>

                      {{-- Detail row --}}
                      <tr class="detail-row" id="{{ $rowId }}">
                          <td colspan="8">
                              <div class="detail-inner">
                                  <div class="detail-box">
                                      <div class="row g-3">
                                          <div class="col-12">
                                              <div class="detail-label">Full Description</div>
                                              <div class="detail-val">{{ $log->notes }}</div>
                                          </div>
                                          @if($log->old_status)
                                              <div class="col-12">
                                                  <div class="detail-label mb-2">Status Change</div>
                                                  <div class="diff-wrap">
                                                      <div class="diff-header">
                                                          <span>Field</span>
                                                          <span>Before</span>
                                                          <span>After</span>
                                                      </div>
                                                      <div class="diff-row">
                                                          <div class="diff-field">Status</div>
                                                          <div class="diff-before">{{ $log->old_status }}</div>
                                                          <div class="diff-after">{{ $log->new_status }}</div>
                                                      </div>
                                                  </div>
                                              </div>
                                          @endif
                                          <div class="col-md-4">
                                              <div class="detail-label">Ticket</div>
                                              <div class="detail-val">
                                                  #{{ $log->ticket?->ticket_number ?? '—' }}
                                              </div>
                                          </div>
                                          <div class="col-md-4">
                                              <div class="detail-label">Changed By</div>
                                              <div class="detail-val">
                                                  {{ $log->changedBy?->name ?? 'System' }}
                                              </div>
                                          </div>
                                          <div class="col-md-4">
                                              <div class="detail-label">Exact Timestamp</div>
                                              <div class="detail-val">
                                                  {{ \Carbon\Carbon::parse($log->changed_at)->format('D, M d Y — h:i:s A') }}
                                              </div>
                                          </div>
                                      </div>
                                  </div>
                              </div>
                          </td>
                      </tr>

                  @empty
                      <tr>
                          <td colspan="8">
                              <div class="empty-state text-center py-5">
                                  <div style="font-size:40px;opacity:.3">📋</div>
                                  <div class="mt-3 font-brand fw-900"
                                      style="font-size:18px;color:var(--tm)">
                                      No audit logs found.
                                  </div>
                                  <div style="font-size:13px;color:var(--tm);margin-top:4px">
                                      Try adjusting your filters.
                                  </div>
                              </div>
                          </td>
                      </tr>
                  @endforelse
              </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="pagination-wrap">
            <span>
                Showing {{ $logs->firstItem() ?? 0 }}–{{ $logs->lastItem() ?? 0 }}
                of {{ number_format($logs->total()) }} events
            </span>
            {{ $logs->withQueryString()->links('pagination::bootstrap-5') }}
        </div>

    </div>{{-- /audit-table-wrap --}}

@endsection

@section('scripts')
<script>

    /* ── Expand / collapse detail row ── */
    function toggleDetail(id) {
        const detailRow = document.getElementById(id);
        const dataRow   = detailRow.previousElementSibling;
        const isOpen    = detailRow.classList.contains('show');

        // Close all
        document.querySelectorAll('.detail-row.show').forEach(r => {
            r.classList.remove('show');
            r.previousElementSibling.classList.remove('expanded');
        });

        if (!isOpen) {
            detailRow.classList.add('show');
            dataRow.classList.add('expanded');
        }
    }

    /* ── Tab pills ── */
    $(function () {
        $('.tab-pill').on('click', function () {
            $('.tab-pill').removeClass('active');
            $(this).addClass('active');
            $('#severityFilter').val($(this).data('severity'));
        });

        /* ── Search debounce ── */
        let timer;
        $('#searchInput').on('input', function () {
            clearTimeout(timer);
            timer = setTimeout(() => {
                // wire to backend in next step
            }, 450);
        });
    });

</script>
@endsection