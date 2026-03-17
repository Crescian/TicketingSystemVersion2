@extends('layouts.admin')

@section('title', 'Audit Log — LGICT Admin')

{{-- ── Nav ── --}}
@section('nav-role-badge')
  <span class="role-badge-admin"><i class="bi bi-shield-fill me-1"></i>IT Admin</span>
@endsection
@section('avatar-initials', 'MA')
@section('nav-username', 'M. Aquino')

{{-- ── Same narrow sidebar + wide content as user management ── --}}
@section('sidebar-col', 'col-lg-2')
@section('content-col', 'col-lg-10')
@section('page-max-width', '1200px')

{{-- ── Hero ── --}}
@section('hero-title')
  <div style="font-size:12px;font-weight:700;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.8px;margin-bottom:6px">
    <i class="bi bi-gear me-1"></i>Settings
  </div>
  <h1>AUDIT <em>LOG</em></h1>
@endsection
@section('hero-subtitle', 'Full system activity trail — every action, who did it, and when.')

@section('hero-stats')
  <div class="d-flex gap-2 flex-wrap">
    <div class="stat-pill">          <span class="num">{{ $counts['today'] ?? 0 }}</span><span class="lbl">Today</span></div>
    <div class="stat-pill warn">     <span class="num">{{ $counts['this_week'] ?? 0 }}</span><span class="lbl">This Week</span></div>
    <div class="stat-pill red">      <span class="num">{{ $counts['critical'] ?? 0 }}</span><span class="lbl">Critical</span></div>
    <div class="stat-pill">          <span class="num">{{ $counts['total'] ?? 0 }}</span><span class="lbl">All Time</span></div>
  </div>
@endsection

{{-- ══════════════════════════════════════
   PAGE-SPECIFIC STYLES
══════════════════════════════════════ --}}
@section('styles')
  .stat-pill.red  .num { color:#ff8888; }
  .stat-pill.warn .num { color:#fde8a0; }

  /* ── Settings Sidebar (shared with user-management) ── */
  .settings-sidebar { background:#fff; border-radius:16px; border:1.5px solid var(--bd); overflow:hidden; position:sticky; top:80px; }
  .settings-nav .nav-item { border-bottom:1px solid var(--bd); }
  .settings-nav .nav-item:last-child { border-bottom:none; }
  .settings-nav .nav-link { padding:11px 16px; font-weight:600; font-size:13px; color:var(--gd); display:flex; align-items:center; gap:9px; border-radius:0 !important; transition:background .15s; text-decoration:none; }
  .settings-nav .nav-link:hover { background:var(--ygl); color:var(--gd); text-decoration:none; }
  .settings-nav .nav-link.active { background:var(--ygl); border-left:4px solid var(--yg); font-weight:700; color:var(--gd); }
  .settings-nav .badge-count { background:var(--gd); color:var(--yg); font-size:11px; border-radius:20px; padding:2px 8px; margin-left:auto; font-weight:800; }

  /* ── Toolbar ── */
  .toolbar { background:#fff; border-radius:14px; border:1.5px solid var(--bd); padding:14px 18px; }
  .search-wrap { background:var(--cr); border:1.5px solid var(--bd); border-radius:50px; padding:8px 16px; display:flex; align-items:center; gap:8px; min-width:200px; }
  .search-wrap input { border:none; outline:none; font-size:13px; background:transparent; color:var(--gd); width:100%; }
  .filter-select { border:1.5px solid var(--bd); border-radius:50px; font-size:13px; color:var(--gd); background:#fff; padding:8px 14px; outline:none; cursor:pointer; }
  .filter-select:focus { border-color:var(--gl); }

  /* Export button */
  .btn-export { background:var(--gd); color:var(--yg); font-family:'Nunito',sans-serif; font-weight:900; font-size:13px; padding:9px 20px; border-radius:50px; border:none; cursor:pointer; transition:all .2s; display:flex; align-items:center; gap:6px; white-space:nowrap; text-decoration:none; }
  .btn-export:hover { background:var(--gm); transform:translateY(-1px); color:var(--yg); }

  /* ── Audit Table ── */
  .audit-table-wrap { background:#fff; border-radius:16px; border:1.5px solid var(--bd); overflow:hidden; }
  .audit-table-scroll { overflow-x:auto; }
  .audit-table { width:100%; border-collapse:collapse; min-width:960px; }
  .audit-table thead tr { background:var(--gd); }
  .audit-table thead th { padding:12px 16px; font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; color:var(--yg); text-transform:uppercase; letter-spacing:.5px; border:none; white-space:nowrap; }
  .audit-table tbody tr { border-bottom:1px solid var(--bd); transition:background .15s; animation:fadeUp .3s ease both; }
  .audit-table tbody tr:last-child { border-bottom:none; }
  .audit-table tbody tr:hover { background:var(--ygl); cursor:pointer; }
  .audit-table tbody td { padding:11px 16px; font-size:13px; vertical-align:middle; }

  /* User avatar in table */
  .user-av { width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-family:'Nunito',sans-serif; font-weight:900; font-size:11px; flex-shrink:0; }
  .av-admin    { background:var(--rdl); color:var(--rd); }
  .av-tech     { background:#fff4cc; color:#7a5a00; }
  .av-helpdesk { background:#d4f0d4; color:var(--gm); }
  .av-customer { background:var(--ygl); color:var(--gd); }
  .user-name  { font-family:'Nunito',sans-serif; font-weight:800; font-size:13px; color:var(--gd); }
  .user-role  { font-size:11px; color:var(--tm); margin-top:1px; }

  /* Action type chips */
  .action-chip { display:inline-flex; align-items:center; gap:5px; border-radius:20px; padding:4px 10px; font-size:11px; font-weight:800; white-space:nowrap; }
  .action-chip.create   { background:#e8f5ee; color:#1a5a3a; }
  .action-chip.update   { background:#fff4cc; color:#7a5a00; }
  .action-chip.delete   { background:var(--rdl); color:var(--rd); }
  .action-chip.login    { background:var(--ygl); color:var(--gm); }
  .action-chip.logout   { background:#f0f0f0; color:#555; }
  .action-chip.reset    { background:#e8eeff; color:#2a4ab0; }
  .action-chip.deactivate { background:var(--rdl); color:var(--rd); }
  .action-chip.reactivate { background:#e8f5ee; color:#1a5a3a; }
  .action-chip.escalate { background:#fde8d0; color:#7a3a00; }
  .action-chip.resolve  { background:#e8f5ee; color:#1a5a3a; }

  /* Module badge */
  .module-chip { display:inline-flex; align-items:center; gap:4px; border-radius:8px; padding:3px 9px; font-size:11px; font-weight:700; background:var(--cr); color:var(--tm); border:1px solid var(--bd); white-space:nowrap; }

  /* IP / device */
  .meta-cell { font-size:12px; color:var(--tm); font-weight:600; }
  .meta-cell .ip { font-family:'Nunito',sans-serif; font-weight:700; color:var(--gd); }

  /* Severity dot */
  .sev-dot { width:8px; height:8px; border-radius:50%; display:inline-block; flex-shrink:0; }
  .sev-dot.info     { background:#4a7c4a; }
  .sev-dot.warning  { background:#f5c842; }
  .sev-dot.critical { background:#e24b4a; }

  /* Expandable detail row */
  .detail-row { display:none; background:#fafaf8; }
  .detail-row.show { display:table-row; }
  .detail-box { background:var(--cr); border-radius:10px; border:1.5px solid var(--bd); padding:14px 18px; font-size:13px; }
  .detail-label { font-size:11px; font-weight:700; color:var(--tm); text-transform:uppercase; letter-spacing:.4px; margin-bottom:3px; }
  .detail-val   { font-weight:600; color:var(--gd); word-break:break-all; }
  .detail-val.code { font-family:monospace; background:#fff; border-radius:6px; padding:8px 10px; font-size:12px; border:1px solid var(--bd); }

  /* Pagination */
  .pagination-wrap { padding:14px 18px; border-top:1px solid var(--bd); display:flex; align-items:center; justify-content:space-between; font-size:13px; color:var(--tm); }
  .pg-btn { width:30px; height:30px; border-radius:8px; border:1.5px solid var(--bd); background:#fff; color:var(--gd); font-size:13px; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all .2s; text-decoration:none; }
  .pg-btn:hover { border-color:var(--gl); background:var(--ygl); color:var(--gd); }
  .pg-btn.active { background:var(--gd); color:var(--yg); border-color:var(--gd); }
  .pg-btn:disabled { opacity:.4; cursor:not-allowed; }

  /* Empty state */
  .empty-state { padding:56px; text-align:center; }
  .empty-icon  { font-size:40px; margin-bottom:12px; opacity:.35; }
  .empty-title { font-family:'Nunito',sans-serif; font-weight:800; font-size:16px; color:var(--gd); }
  .empty-sub   { font-size:13px; color:var(--tm); margin-top:4px; }

  /* Detail modal */
  .modal-hdr-dark { background:var(--gd); padding:20px 28px; }
  .modal-hdr-dark h5 { font-family:'Nunito',sans-serif; font-weight:900; font-size:19px; color:#fff; text-transform:uppercase; letter-spacing:-.3px; margin:0; }
  .modal-hdr-dark h5 em { font-style:normal; color:var(--yg); }
  .diff-wrap { background:#f8f9fa; border-radius:10px; border:1.5px solid var(--bd); overflow:hidden; }
  .diff-row { display:grid; grid-template-columns:120px 1fr 1fr; border-bottom:1px solid var(--bd); font-size:12px; }
  .diff-row:last-child { border-bottom:none; }
  .diff-field { padding:8px 12px; font-weight:700; color:var(--tm); background:var(--cr); border-right:1px solid var(--bd); }
  .diff-before { padding:8px 12px; color:#8b1a1a; background:#fff5f5; border-right:1px solid var(--bd); font-family:monospace; }
  .diff-after  { padding:8px 12px; color:#1a5a3a; background:#f0fff4; font-family:monospace; }
  .diff-header { display:grid; grid-template-columns:120px 1fr 1fr; background:var(--gd); }
  .diff-header span { padding:8px 12px; font-size:11px; font-weight:800; color:var(--yg); text-transform:uppercase; letter-spacing:.4px; }

  @keyframes fadeUp { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }
@endsection

{{-- ══ SETTINGS SIDEBAR ══ --}}
@section('sidebar')
  <div class="settings-sidebar">
    <div class="sidebar-head dark"><i class="bi bi-gear me-1"></i>Settings</div>
    <ul class="nav flex-column settings-nav">
      <li class="nav-item">
        <a class="nav-link" href="{{ route('admin.users.index') }}">
          <i class="bi bi-people"></i>Users
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="{{ route('admin.roles.index') }}">
          <i class="bi bi-shield-check"></i>Roles
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="{{ route('admin.departments.index') }}">
          <i class="bi bi-building"></i>Departments
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="{{ route('admin.categories.index') }}">
          <i class="bi bi-tags"></i>Categories
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="{{ route('admin.sla.index') }}">
          <i class="bi bi-clock-history"></i>SLA Rules
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link active" href="{{ route('admin.audit.index') }}">
          <i class="bi bi-journal-text"></i>Audit Log
        </a>
      </li>
    </ul>
  </div>
@endsection

{{-- ══ MAIN CONTENT ══ --}}
@section('content')

  {{-- ── Session flash ── --}}
  @if (session('success'))
    <div class="alert d-flex align-items-center gap-2 mb-3"
         style="background:var(--ygl);border:1.5px solid #a5d6a7;border-radius:12px;padding:12px 16px;font-size:13px;font-weight:600;color:var(--gd)">
      <i class="bi bi-check-circle-fill" style="color:var(--gl)"></i>
      {{ session('success') }}
    </div>
  @endif

  {{-- ── Toolbar ── --}}
  <div class="toolbar d-flex flex-wrap align-items-center gap-3 mb-3">

    {{-- Search --}}
    <div class="search-wrap flex-grow-1" style="max-width:280px">
      <i class="bi bi-search" style="color:var(--tm)"></i>
      <input type="text" id="searchInput" placeholder="Search by user, action, module…"
             value="{{ request('search') }}"
             hx-get="{{ route('admin.audit.index') }}"
             hx-trigger="keyup changed delay:400ms"
             hx-target="#auditTableWrap"
             hx-include="[name]">
    </div>

    {{-- Action filter --}}
    <select class="filter-select" id="actionFilter" name="action"
            onchange="applyFilters()">
      <option value="">All Actions</option>
      <option value="create"     {{ request('action') === 'create'     ? 'selected' : '' }}>Create</option>
      <option value="update"     {{ request('action') === 'update'     ? 'selected' : '' }}>Update</option>
      <option value="delete"     {{ request('action') === 'delete'     ? 'selected' : '' }}>Delete</option>
      <option value="login"      {{ request('action') === 'login'      ? 'selected' : '' }}>Login</option>
      <option value="logout"     {{ request('action') === 'logout'     ? 'selected' : '' }}>Logout</option>
      <option value="reset"      {{ request('action') === 'reset'      ? 'selected' : '' }}>Password Reset</option>
      <option value="deactivate" {{ request('action') === 'deactivate' ? 'selected' : '' }}>Deactivate</option>
      <option value="reactivate" {{ request('action') === 'reactivate' ? 'selected' : '' }}>Reactivate</option>
      <option value="escalate"   {{ request('action') === 'escalate'   ? 'selected' : '' }}>Escalate</option>
      <option value="resolve"    {{ request('action') === 'resolve'    ? 'selected' : '' }}>Resolve</option>
    </select>

    {{-- Module filter --}}
    <select class="filter-select" id="moduleFilter" name="module"
            onchange="applyFilters()">
      <option value="">All Modules</option>
      <option value="User"       {{ request('module') === 'User'       ? 'selected' : '' }}>Users</option>
      <option value="Ticket"     {{ request('module') === 'Ticket'     ? 'selected' : '' }}>Tickets</option>
      <option value="Role"       {{ request('module') === 'Role'       ? 'selected' : '' }}>Roles</option>
      <option value="Department" {{ request('module') === 'Department' ? 'selected' : '' }}>Departments</option>
      <option value="Auth"       {{ request('module') === 'Auth'       ? 'selected' : '' }}>Auth</option>
    </select>

    {{-- Severity filter --}}
    <select class="filter-select" id="severityFilter" name="severity"
            onchange="applyFilters()">
      <option value="">All Severity</option>
      <option value="info"     {{ request('severity') === 'info'     ? 'selected' : '' }}>Info</option>
      <option value="warning"  {{ request('severity') === 'warning'  ? 'selected' : '' }}>Warning</option>
      <option value="critical" {{ request('severity') === 'critical' ? 'selected' : '' }}>Critical</option>
    </select>

    {{-- Date range --}}
    <input type="date" class="filter-select" id="dateFrom" name="date_from"
           value="{{ request('date_from') }}" onchange="applyFilters()"
           style="border-radius:50px;padding:8px 14px">

    {{-- Export button --}}
    <a href="{{ route('admin.audit.export', request()->query()) }}"
       class="btn-export ms-auto">
      <i class="bi bi-download"></i> Export CSV
    </a>

  </div>

  {{-- ── Tab pills ── --}}
  <div class="d-flex flex-wrap gap-2 mb-3" id="tabRow">
    <span class="tab-pill {{ !request('severity') ? 'active' : '' }}"
          onclick="setSeverity('')">All Events ({{ $counts['total'] ?? 0 }})</span>
    <span class="tab-pill {{ request('severity') === 'info' ? 'active' : '' }}"
          onclick="setSeverity('info')">Info</span>
    <span class="tab-pill {{ request('severity') === 'warning' ? 'active' : '' }}"
          onclick="setSeverity('warning')">Warning</span>
    <span class="tab-pill {{ request('severity') === 'critical' ? 'active' : '' }}"
          onclick="setSeverity('critical')">
      <span class="sev-dot critical me-1"></span>Critical ({{ $counts['critical'] ?? 0 }})
    </span>
  </div>

  {{-- ── Audit table ── --}}
  <div class="audit-table-wrap" id="auditTableWrap">
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
        <tbody id="auditTableBody">

          @forelse ($logs ?? [] as $log)

            {{-- ── Data row ── --}}
            <tr onclick="toggleDetail('detail-{{ $log->id }}')"
                style="animation-delay:{{ $loop->index * 0.04 }}s">

              {{-- Expand icon --}}
              <td style="text-align:center;color:var(--tm)">
                <i class="bi bi-chevron-right" id="icon-{{ $log->id }}"
                   style="font-size:11px;transition:transform .2s"></i>
              </td>

              {{-- User --}}
              <td>
                <div class="d-flex align-items-center gap-2">
                  @php
                    $avClass = match($log->user?->role?->role_name ?? '') {
                      'IT Admin'      => 'av-admin',
                      'IT Technician' => 'av-tech',
                      'Helpdesk'      => 'av-helpdesk',
                      default         => 'av-customer',
                    };
                    $initials = $log->user
                      ? strtoupper(substr($log->user->name, 0, 1) . substr(strrchr($log->user->name, ' '), 1, 1))
                      : 'SY';
                  @endphp
                  <div class="user-av {{ $avClass }}">{{ $initials }}</div>
                  <div>
                    <div class="user-name">{{ $log->user?->name ?? 'System' }}</div>
                    <div class="user-role">{{ $log->user?->role?->role_name ?? 'Automated' }}</div>
                  </div>
                </div>
              </td>

              {{-- Action chip --}}
              <td>
                @php
                  $actionIcon = match($log->action) {
                    'create'     => 'bi-plus-circle',
                    'update'     => 'bi-pencil',
                    'delete'     => 'bi-trash',
                    'login'      => 'bi-box-arrow-in-right',
                    'logout'     => 'bi-box-arrow-right',
                    'reset'      => 'bi-key',
                    'deactivate' => 'bi-x-circle',
                    'reactivate' => 'bi-check-circle',
                    'escalate'   => 'bi-exclamation-triangle',
                    'resolve'    => 'bi-check-all',
                    default      => 'bi-activity',
                  };
                @endphp
                <span class="action-chip {{ $log->action }}">
                  <i class="bi {{ $actionIcon }}"></i>
                  {{ ucfirst($log->action) }}
                </span>
              </td>

              {{-- Module --}}
              <td><span class="module-chip"><i class="bi bi-box me-1"></i>{{ $log->module }}</span></td>

              {{-- Description --}}
              <td style="max-width:260px;color:var(--gd);font-weight:600">
                {{ Str::limit($log->description, 60) }}
              </td>

              {{-- IP --}}
              <td class="meta-cell">
                <div class="ip">{{ $log->ip_address ?? '—' }}</div>
                <div style="font-size:11px">{{ $log->user_agent ? Str::limit($log->user_agent, 24) : '' }}</div>
              </td>

              {{-- Severity --}}
              <td>
                <span class="d-flex align-items-center gap-2">
                  <span class="sev-dot {{ $log->severity ?? 'info' }}"></span>
                  <span style="font-size:12px;font-weight:700;text-transform:capitalize;color:var(--gd)">
                    {{ ucfirst($log->severity ?? 'info') }}
                  </span>
                </span>
              </td>

              {{-- Timestamp --}}
              <td class="meta-cell" style="white-space:nowrap">
                <div style="font-weight:700;color:var(--gd)">{{ $log->created_at->format('M d, Y') }}</div>
                <div>{{ $log->created_at->format('h:i A') }}</div>
              </td>

            </tr>

            {{-- ── Expandable detail row ── --}}
            <tr class="detail-row" id="detail-{{ $log->id }}">
              <td colspan="8" style="padding:0 16px 14px 48px">
                <div class="detail-box">
                  <div class="row g-3">

                    {{-- Full description --}}
                    <div class="col-12">
                      <div class="detail-label">Full Description</div>
                      <div class="detail-val">{{ $log->description }}</div>
                    </div>

                    {{-- Changed fields diff (if update action) --}}
                    @if ($log->action === 'update' && $log->old_values && $log->new_values)
                      <div class="col-12">
                        <div class="detail-label mb-2">Changes Made</div>
                        <div class="diff-wrap">
                          <div class="diff-header">
                            <span>Field</span>
                            <span>Before</span>
                            <span>After</span>
                          </div>
                          @foreach (array_keys((array) $log->new_values) as $field)
                            <div class="diff-row">
                              <div class="diff-field">{{ ucfirst(str_replace('_', ' ', $field)) }}</div>
                              <div class="diff-before">{{ data_get($log->old_values, $field, '—') }}</div>
                              <div class="diff-after">{{ data_get($log->new_values, $field, '—') }}</div>
                            </div>
                          @endforeach
                        </div>
                      </div>
                    @endif

                    {{-- Metadata row --}}
                    <div class="col-md-3">
                      <div class="detail-label">Record ID</div>
                      <div class="detail-val">{{ $log->model_id ?? '—' }}</div>
                    </div>
                    <div class="col-md-3">
                      <div class="detail-label">IP Address</div>
                      <div class="detail-val">{{ $log->ip_address ?? '—' }}</div>
                    </div>
                    <div class="col-md-3">
                      <div class="detail-label">Browser / Agent</div>
                      <div class="detail-val">{{ $log->user_agent ?? '—' }}</div>
                    </div>
                    <div class="col-md-3">
                      <div class="detail-label">Exact Timestamp</div>
                      <div class="detail-val">{{ $log->created_at->format('D, M d Y — h:i:s A') }}</div>
                    </div>

                  </div>
                </div>
              </td>
            </tr>

          @empty

            {{-- Empty state --}}
            <tr>
              <td colspan="8" style="padding:0;border:none">
                <div class="empty-state">
                  <div class="empty-icon"><i class="bi bi-journal-x"></i></div>
                  <div class="empty-title">No audit logs found</div>
                  <div class="empty-sub">Try adjusting your filters or date range.</div>
                </div>
              </td>
            </tr>

          @endforelse

        </tbody>
      </table>
    </div>

    {{-- ── Pagination ── --}}
    @if (isset($logs) && $logs->hasPages())
      <div class="pagination-wrap">
        <span>
          Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }} events
        </span>
        <div class="d-flex align-items-center gap-1">
          {{-- Prev --}}
          @if ($logs->onFirstPage())
            <button class="pg-btn" disabled><i class="bi bi-chevron-left"></i></button>
          @else
            <a class="pg-btn" href="{{ $logs->previousPageUrl() . '&' . http_build_query(request()->except('page')) }}">
              <i class="bi bi-chevron-left"></i>
            </a>
          @endif

          {{-- Page numbers --}}
          @foreach ($logs->getUrlRange(max(1, $logs->currentPage() - 2), min($logs->lastPage(), $logs->currentPage() + 2)) as $page => $url)
            <a class="pg-btn {{ $page === $logs->currentPage() ? 'active' : '' }}"
               href="{{ $url . '&' . http_build_query(request()->except('page')) }}">
              {{ $page }}
            </a>
          @endforeach

          {{-- Next --}}
          @if ($logs->hasMorePages())
            <a class="pg-btn" href="{{ $logs->nextPageUrl() . '&' . http_build_query(request()->except('page')) }}">
              <i class="bi bi-chevron-right"></i>
            </a>
          @else
            <button class="pg-btn" disabled><i class="bi bi-chevron-right"></i></button>
          @endif
        </div>
      </div>
    @endif

  </div>{{-- /audit-table-wrap --}}

@endsection

{{-- ══ SCRIPTS ══ --}}
@section('scripts')
<script>
/* ── Expand / collapse detail rows ── */
function toggleDetail(id) {
  const row  = document.getElementById(id);
  const ticketId = id.replace('detail-', '');
  const icon = document.getElementById('icon-' + ticketId);
  const isOpen = row.classList.contains('show');

  // Close all open detail rows first
  document.querySelectorAll('.detail-row.show').forEach(r => r.classList.remove('show'));
  document.querySelectorAll('.bi-chevron-down').forEach(i => {
    i.classList.replace('bi-chevron-down', 'bi-chevron-right');
    i.style.transform = '';
  });

  if (!isOpen) {
    row.classList.add('show');
    icon.classList.replace('bi-chevron-right', 'bi-chevron-down');
  }
}

/* ── Apply all filters via form GET ── */
function applyFilters() {
  const params = new URLSearchParams({
    search:   document.getElementById('searchInput').value,
    action:   document.getElementById('actionFilter').value,
    module:   document.getElementById('moduleFilter').value,
    severity: document.getElementById('severityFilter').value,
    date_from:document.getElementById('dateFrom').value,
  });
  // Remove empty params
  [...params.keys()].forEach(k => { if (!params.get(k)) params.delete(k); });
  window.location.href = '{{ route('admin.audit.index') }}?' + params.toString();
}

/* ── Tab pill severity shortcut ── */
function setSeverity(val) {
  document.getElementById('severityFilter').value = val;
  applyFilters();
}

/* ── Live search with debounce ── */
let searchTimer;
document.getElementById('searchInput').addEventListener('input', function () {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(applyFilters, 450);
});
</script>
@endsection
