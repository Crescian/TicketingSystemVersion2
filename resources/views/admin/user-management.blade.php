@extends('layouts.admin')

@section('title', 'User Management — LGICT')

@section('nav-role-badge')
    <span class="role-badge-admin"><i class="bi bi-shield-fill me-1"></i>IT Admin</span>
@endsection
@section('avatar-initials',
    strtoupper(substr(Auth::user()->name, 0, 1)) .
    strtoupper(substr(Auth::user()->name, strpos(Auth::user()->name, ' ') + 1, 1))
)
@section('nav-username', Auth::user()->name)

@section('hero-title')
    <h1>USER <em>MANAGEMENT</em></h1>
@endsection
@section('hero-subtitle', 'Add, edit, deactivate users and manage their roles across the system.')

@section('hero-stats')
    <div class="d-flex gap-2 flex-wrap text-white">
        <div class="stat-pill">
            <span class="num">{{ $counts['total'] }}</span>
            <span class="lbl">Total Users</span>
        </div>
        <div class="stat-pill">
            <span class="num">{{ $counts['active'] }}</span>
            <span class="lbl">Active</span>
        </div>
        <div class="stat-pill red">
            <span class="num">{{ $counts['inactive'] }}</span>
            <span class="lbl">Inactive</span>
        </div>
        <div class="stat-pill warn">
            <span class="num">{{ $counts['techs'] }}</span>
            <span class="lbl">IT Techs</span>
        </div>
    </div>
@endsection

@section('styles')
    /* ── Settings Sidebar ── */
    .settings-sidebar { background:#fff; border-radius:16px; border:1.5px solid var(--bd); overflow:hidden; }
    .settings-nav .nav-item { border-bottom:1px solid var(--bd); }
    .settings-nav .nav-item:last-child { border-bottom:none; }
    .settings-nav .nav-link { padding:11px 18px; font-weight:600; font-size:14px; color:var(--gd); display:flex; align-items:center; gap:10px; border-radius:0 !important; transition:background .15s; }
    .settings-nav .nav-link:hover { background:var(--ygl); }
    .settings-nav .nav-link.active { background:var(--ygl); border-left:4px solid var(--yg); font-weight:700; }
    .settings-nav .nav-link .badge-count { background:var(--gd); color:var(--yg); font-size:11px; border-radius:20px; padding:2px 8px; margin-left:auto; font-weight:800; }

    /* ── Toolbar ── */
    .toolbar { background:#fff; border-radius:14px; border:1.5px solid var(--bd); padding:14px 18px; }
    .filter-select { border:1.5px solid var(--bd); border-radius:50px; font-size:13px; color:var(--gd); background:#fff; padding:8px 14px; outline:none; cursor:pointer; }
    .filter-select:focus { border-color:var(--gl); }
    .btn-add-user { background:var(--gd); color:var(--yg); font-family:'Nunito',sans-serif; font-weight:900; font-size:13px; padding:9px 20px; border-radius:50px; border:none; cursor:pointer; transition:all .2s; white-space:nowrap; }
    .btn-add-user:hover { background:var(--gm); transform:translateY(-1px); }

    /* ── User table ── */
    .user-table-wrap { background:#fff; border-radius:16px; border:1.5px solid var(--bd); overflow:hidden; }
    .user-table-scroll { overflow-x:auto; }
    .user-table { width:100%; border-collapse:collapse; min-width:900px; }
    .user-table thead tr { background:var(--gd); }
    .user-table thead th { padding:12px 16px; font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; color:var(--yg); text-transform:uppercase; letter-spacing:.5px; border:none; white-space:nowrap; }
    .user-table tbody tr { border-bottom:1px solid var(--bd); transition:background .15s; }
    .user-table tbody tr:last-child { border-bottom:none; }
    .user-table tbody tr:hover { background:var(--ygl); }
    .user-table tbody td { padding:11px 16px; font-size:13px; vertical-align:middle; }

    /* User avatar */
    .user-av { width:34px; height:34px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-family:'Nunito',sans-serif; font-weight:900; font-size:12px; flex-shrink:0; }
    .av-employee  { background:var(--ygl); color:var(--gd); }
    .av-helpdesk  { background:#d4f0d4; color:var(--gm); }
    .av-tech      { background:#fff4cc; color:#7a5a00; }
    .av-admin     { background:var(--rdl); color:var(--rd); }
    .av-executive { background:#e8e0ff; color:#4a1a8a; }

    .user-name  { font-family:'Nunito',sans-serif; font-weight:800; font-size:14px; color:var(--gd); }
    .user-email { font-size:12px; color:var(--tm); margin-top:1px; }

    /* Role chips */
    .role-chip { display:inline-flex; align-items:center; gap:5px; border-radius:20px; padding:4px 10px; font-size:11px; font-weight:800; white-space:nowrap; }
    .role-chip.employee  { background:var(--ygl); color:var(--gm); }
    .role-chip.helpdesk  { background:#d4f0d4; color:var(--gd); }
    .role-chip.tech      { background:#fff4cc; color:#7a5a00; }
    .role-chip.admin     { background:var(--rdl); color:var(--rd); }
    .role-chip.executive { background:#e8e0ff; color:#4a1a8a; }
    .role-dot { width:6px; height:6px; border-radius:50%; flex-shrink:0; }
    .role-dot.employee  { background:var(--yg); }
    .role-dot.helpdesk  { background:#4a7c4a; }
    .role-dot.tech      { background:#f5c842; }
    .role-dot.admin     { background:#e24b4a; }
    .role-dot.executive { background:#9b59b6; }

    /* Status chips */
    .status-chip { display:inline-flex; align-items:center; gap:5px; border-radius:20px; padding:4px 10px; font-size:11px; font-weight:800; }
    .status-chip.active   { background:#e8f5ee; color:#1a5a3a; }
    .status-chip.inactive { background:var(--rdl); color:var(--rd); }
    .status-dot { width:6px; height:6px; border-radius:50%; }
    .status-dot.active   { background:#2ecc71; }
    .status-dot.inactive { background:#e24b4a; }

    /* Action buttons */
    .btn-edit, .btn-deact, .btn-activ, .btn-pw-reset {
        display:inline-flex; align-items:center; gap:3px;
        font-size:11px; font-weight:700; padding:4px 10px;
        border-radius:20px; cursor:pointer; transition:all .2s;
        white-space:nowrap; line-height:1.4;
    }
    .btn-edit     { background:var(--ygl); color:var(--gd); border:1.5px solid var(--bd); }
    .btn-edit:hover { border-color:var(--gl); background:#d8eda0; }
    .btn-deact    { background:var(--rdl); color:var(--rd); border:1.5px solid #f0c0c0; }
    .btn-deact:hover { background:#f8c8c8; }
    .btn-activ    { background:#e8f5ee; color:#1a5a3a; border:1.5px solid #a8ddc0; }
    .btn-activ:hover { background:#c8ead8; }
    .btn-pw-reset { background:#fff; color:var(--tm); border:1.5px solid var(--bd); }
    .btn-pw-reset:hover { border-color:var(--gl); color:var(--gd); }
    .actions-wrap { display:flex; align-items:center; gap:4px; justify-content:flex-end; flex-wrap:nowrap; }
    .user-table td:last-child { white-space:nowrap; }

    /* Role opts in modal */
    .role-opts { display:flex; flex-wrap:wrap; gap:8px; }
    .role-opt { flex:1; min-width:90px; border:1.5px solid var(--bd); border-radius:12px; padding:10px 8px; cursor:pointer; transition:all .2s; background:var(--cr); text-align:center; user-select:none; }
    .role-opt:hover { border-color:var(--gl); background:var(--ygl); }
    .role-opt.selected { border-color:var(--gd); background:var(--ygl); box-shadow:0 0 0 2px var(--yg); }
    .role-opt .ro-icon { font-size:18px; display:block; margin-bottom:4px; }
    .role-opt .ro-lbl  { font-family:'Nunito',sans-serif; font-weight:800; font-size:11px; color:var(--gd); }

    /* Status toggle */
    .status-toggle { display:flex; gap:8px; }
    .st-opt { flex:1; border:1.5px solid var(--bd); border-radius:10px; padding:10px; text-align:center; cursor:pointer; transition:all .2s; background:var(--cr); font-family:'Nunito',sans-serif; font-weight:800; font-size:13px; color:var(--tm); }
    .st-opt.active-sel   { border-color:#2ecc71; background:#e8f5ee; color:#1a5a3a; }
    .st-opt.inactive-sel { border-color:#e24b4a; background:var(--rdl); color:var(--rd); }

    /* Avatar preview */
    .avatar-preview { width:64px; height:64px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-family:'Nunito',sans-serif; font-weight:900; font-size:22px; margin:0 auto 16px; }

    /* Empty state */
    .empty-state { padding:48px; text-align:center; }
    .empty-icon  { font-size:40px; margin-bottom:12px; opacity:.4; }
    .empty-title { font-family:'Nunito',sans-serif; font-weight:800; font-size:16px; }
    .empty-sub   { font-size:13px; color:var(--tm); margin-top:4px; }
@endsection

{{-- ══ SIDEBAR ══ --}}
@section('sidebar')
    <div class="settings-sidebar mb-3">
        <div class="sidebar-head"><i class="bi bi-gear me-1"></i>Settings</div>
        <ul class="nav flex-column settings-nav">
            <li class="nav-item">
                <a class="nav-link active" href="{{ route('admin.users.index') }}">
                    <i class="bi bi-people"></i>Users
                    <span class="badge-count">{{ $counts['total'] }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.settings') }}">
                    <i class="bi bi-building"></i>Organization
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.sla-rules.index') }}">
                    <i class="bi bi-clock-history"></i>SLA Rules
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.audit-log') }}">
                    <i class="bi bi-journal-text"></i>Audit Log
                </a>
            </li>
        </ul>
    </div>
@endsection

{{-- ══ MAIN CONTENT ══ --}}
@section('content')

    {{-- Alerts --}}
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

    {{-- Toolbar --}}
    <form method="GET" action="{{ route('admin.users.index') }}"
          class="toolbar d-flex flex-wrap align-items-center gap-3 mb-3"
          id="filterForm">
        <div class="search-wrap flex-grow-1" style="max-width:280px">
            <i class="bi bi-search" style="color:var(--tm)"></i>
            <input type="text" name="search" id="searchInput"
                   placeholder="Search by name, email, role…"
                   value="{{ $search }}" autocomplete="off">
        </div>
        <select class="filter-select" name="role" onchange="this.form.submit()">
            <option value="">All Roles</option>
            @foreach($roles as $r)
                <option value="{{ $r->role_name }}"
                    {{ $role === $r->role_name ? 'selected' : '' }}>
                    {{ $r->role_name }}
                </option>
            @endforeach
        </select>
        <select class="filter-select" name="dept" onchange="this.form.submit()">
            <option value="">All Departments</option>
            @foreach($departments as $d)
                <option value="{{ $d->department_name }}"
                    {{ $dept === $d->department_name ? 'selected' : '' }}>
                    {{ $d->department_name }}
                </option>
            @endforeach
        </select>
        <select class="filter-select" name="status" onchange="this.form.submit()">
            <option value="">All Status</option>
            <option value="active"   {{ $status === 'active'   ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
        <button type="button" class="btn-add-user ms-auto"
                onclick="openAddModal()">
            <i class="bi bi-person-plus-fill me-1"></i>Add User
        </button>
    </form>

    {{-- Tab pills --}}
    <div class="d-flex flex-wrap gap-2 mb-3">
        @php
            $tabs = [
                ''              => ['label' => 'All Users',     'count' => $counts['total']],
                'Employee'      => ['label' => 'Employees',     'count' => $roleCounts['Employee']],
                'Helpdesk'      => ['label' => 'Helpdesk',      'count' => $roleCounts['Helpdesk']],
                'IT Support Specialist' => ['label' => 'IT Tech',       'count' => $roleCounts['IT Support Specialist']],
                'IT Admin'      => ['label' => 'Admin',         'count' => $roleCounts['IT Admin']],
                'Executive'     => ['label' => 'Executive',     'count' => $roleCounts['Executive']],
            ];
        @endphp
        @foreach($tabs as $key => $tab)
            <a href="{{ route('admin.users.index', array_merge(request()->except('role'), ['role' => $key])) }}"
               class="tab-pill {{ $role === $key ? 'active' : '' }}">
                {{ $tab['label'] }} ({{ $tab['count'] }})
            </a>
        @endforeach
    </div>

    {{-- User table --}}
    <div class="user-table-wrap">
        <div class="user-table-scroll">
            <table class="user-table">
                <thead>
                    <tr>
                        <th style="width:40px">
                            <input type="checkbox" id="selectAll"
                                   style="accent-color:var(--yg)">
                        </th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Department</th>
                        <th>Position</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th style="text-align:right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        @php
                            $nameParts = explode(' ', $user->name);
                            $initials  = strtoupper(substr($nameParts[0], 0, 1)) .
                                         strtoupper(substr($nameParts[count($nameParts) - 1], 0, 1));

                            $roleName  = strtolower(str_replace(' ', '-', $user->role?->role_name ?? 'employee'));
                            $roleSlug  = match($user->role?->role_name) {
                                'IT Support Specialist' => 'tech',
                                'IT Admin'      => 'admin',
                                'Helpdesk'      => 'helpdesk',
                                'Executive'     => 'executive',
                                default         => 'employee'
                            };
                            $roleIcon  = match($user->role?->role_name) {
                                'IT Support Specialist' => '🔧',
                                'IT Admin'      => '🛡️',
                                'Helpdesk'      => '🎧',
                                'Executive'     => '👔',
                                default         => '🧑‍💼'
                            };
                        @endphp
                        <tr>
                            <td>
                                <input type="checkbox" class="row-check"
                                       style="accent-color:var(--gd)">
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="user-av av-{{ $roleSlug }}">
                                        {{ $initials }}
                                    </div>
                                    <div>
                                        <div class="user-name">{{ $user->name }}</div>
                                        <div class="user-email">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="role-chip {{ $roleSlug }}">
                                    <span class="role-dot {{ $roleSlug }}"></span>
                                    {{ $roleIcon }} {{ $user->role?->role_name ?? 'N/A' }}
                                </span>
                            </td>
                            <td>{{ $user->department?->department_name ?? '—' }}</td>
                            <td style="color:var(--tm);font-size:13px">
                                {{ $user->position ?? '—' }}
                            </td>
                            <td>
                                <span class="status-chip {{ $user->active ? 'active' : 'inactive' }}">
                                    <span class="status-dot {{ $user->active ? 'active' : 'inactive' }}"></span>
                                    {{ $user->active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td style="color:var(--tm);font-size:13px">
                                {{ $user->created_at->format('M d, Y') }}
                            </td>
                            <td>
                                <div class="actions-wrap">
                                    <button class="btn-edit"
                                            onclick="openEditModal('{{ $user->id }}')">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                    <button class="btn-pw-reset"
                                            onclick="openResetModal('{{ $user->id }}', '{{ $user->name }}')">
                                        <i class="bi bi-key"></i> Reset
                                    </button>
                                    @if($user->active)
                                        <button class="btn-deact"
                                                onclick="openDeactModal('{{ $user->id }}', '{{ $user->name }}')">
                                            <i class="bi bi-x-circle"></i> Deactivate
                                        </button>
                                    @else
                                        <form method="POST"
                                              action="{{ route('admin.users.reactivate', $user) }}"
                                              style="display:inline">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn-activ">
                                                <i class="bi bi-check-circle"></i> Reactivate
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <div class="empty-icon">
                                        <i class="bi bi-people"></i>
                                    </div>
                                    <div class="empty-title">No users found</div>
                                    <div class="empty-sub">
                                        Try adjusting your filters or search term.
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div style="padding:14px 18px;border-top:1px solid var(--bd);display:flex;align-items:center;justify-content:space-between;font-size:13px;color:var(--tm)">
            <span>
                Showing {{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }}
                of {{ $users->total() }} users
            </span>
            {{ $users->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
    </div>

@endsection

{{-- ══ MODALS ══ --}}
@section('modals')

    {{-- Add / Edit User Modal --}}
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-hdr-dark d-flex align-items-center justify-content-between">
                    <h5 class="mb-0" id="userModalTitle">Add <em>New User</em></h5>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <form method="POST" id="userForm" action="{{ route('admin.users.store') }}">
                    @csrf
                    <input type="hidden" name="_method" id="formMethod" value="POST">

                    <div class="modal-body px-4 py-4">

                        {{-- Avatar preview --}}
                        <div class="avatar-preview av-employee" id="avatarPreview">JD</div>

                        {{-- Name --}}
                        <div class="row g-3 mb-3">
                            <div class="col-12">
                                <label class="form-label">
                                    Full name <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" name="name"
                                    id="mName" placeholder="Juan Dela Cruz">
                            </div>
                        </div>

                        {{-- Email --}}
                        <div class="mb-3">
                            <label class="form-label">
                                Company email <span class="text-danger">*</span>
                            </label>
                            <div class="position-relative">
                                <i class="bi bi-envelope"
                                style="position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--tm)"></i>
                                <input type="email" class="form-control"
                                    name="email" id="mEmail"
                                    placeholder="juan@lgict.gov.ph"
                                    style="padding-left:38px">
                            </div>
                        </div>

                        {{-- Business Unit → Company → Department cascade --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">
                                    Business Unit <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="mBusinessUnit"
                                        onchange="filterCompanies()">
                                    <option value="">Select business unit…</option>
                                    @foreach($businessUnits as $bu)
                                        <option value="{{ $bu->id }}">
                                            {{ $bu->business_units_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">
                                    Company <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="mCompany"
                                        onchange="filterDepartments()" disabled>
                                    <option value="">Select company…</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">
                                    Department <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" name="department_id"
                                        id="mDept" disabled>
                                    <option value="">Select department…</option>
                                </select>
                            </div>
                        </div>

                        {{-- Position --}}
                        <div class="mb-3">
                            <label class="form-label">Job position</label>
                            <input type="text" class="form-control"
                                name="position" id="mPosition"
                                placeholder="e.g. Financial Analyst">
                        </div>

                        {{-- Role --}}
                        <div class="mb-3">
                            <label class="form-label d-block">
                                Assign role <span class="text-danger">*</span>
                            </label>
                            <input type="hidden" name="role_id" id="mRoleId">
                            <div class="role-opts">
                                @foreach($roles as $r)
                                    @php
                                        $icon = match($r->role_name) {
                                            'IT Support Specialist' => '🔧',
                                            'IT Admin'             => '🛡️',
                                            'Helpdesk'             => '🎧',
                                            'Executive'            => '👔',
                                            default                => '🧑‍💼'
                                        };
                                    @endphp
                                    <div class="role-opt"
                                        data-role-id="{{ $r->id }}"
                                        data-role-name="{{ $r->role_name }}">
                                        <span class="ro-icon">{{ $icon }}</span>
                                        <span class="ro-lbl">{{ $r->role_name }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Status --}}
                        <div class="mb-3">
                            <label class="form-label d-block">Account status</label>
                            <input type="hidden" name="active" id="mActive" value="1">
                            <div class="status-toggle">
                                <div class="st-opt active-sel" data-status="1">
                                    <i class="bi bi-check-circle me-1"></i>Active
                                </div>
                                <div class="st-opt" data-status="0">
                                    <i class="bi bi-x-circle me-1"></i>Inactive
                                </div>
                            </div>
                        </div>

                        <div class="p-3 rounded" id="addPasswordNote"
                            style="background:var(--ygl);font-size:13px;color:var(--gd)">
                            <i class="bi bi-info-circle me-1"></i>
                            Default password is <strong>"password"</strong>.
                            User should change it on first login.
                        </div>

                    </div>
                    <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
                        <button type="button" class="btn-cancel-modal"
                                data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-confirm">
                            <i class="bi bi-check-circle me-1"></i>Save User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Reset Password Modal --}}
    <div class="modal fade" id="resetPwModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-hdr-dark d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Reset <em>Password</em></h5>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <form method="POST" id="resetForm">
                    @csrf @method('PATCH')
                    <div class="modal-body px-4 py-4">
                        <div class="p-3 rounded mb-3"
                             style="background:var(--ygl);font-size:13px;color:var(--gd)">
                            <i class="bi bi-key me-1"></i>
                            Resetting password for
                            <strong id="resetUserName"></strong>.
                            A temporary password will be sent to their email.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reason for reset (optional)</label>
                            <select class="form-select" name="reason">
                                <option value="">Select reason…</option>
                                <option value="User forgot password">User forgot password</option>
                                <option value="Account compromised">Account compromised</option>
                                <option value="Routine security reset">Routine security reset</option>
                                <option value="User request">User request</option>
                            </select>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                                   id="forceChange" checked style="accent-color:var(--gd)">
                            <label class="form-check-label" for="forceChange"
                                   style="font-size:13px;font-weight:600">
                                Force password change on next login
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
                        <button type="button" class="btn-cancel-modal"
                                data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-confirm">
                            <i class="bi bi-key me-1"></i>Send Reset
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Deactivate Modal --}}
    <div class="modal fade" id="deactModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-hdr-red d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Deactivate <em>User</em></h5>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
                <form method="POST" id="deactForm">
                    @csrf @method('PATCH')
                    <div class="modal-body px-4 py-4">
                        <div class="p-3 rounded mb-3"
                             style="background:var(--rdl);font-size:13px;color:var(--rd)">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            Deactivating <strong id="deactUserName"></strong> will block
                            their login. Their ticket history will be preserved.
                            You can reactivate them at any time.
                        </div>
                        <div>
                            <label class="form-label">Reason for deactivation</label>
                            <select class="form-select" name="reason">
                                <option value="">Select reason…</option>
                                <option value="Employee resigned">Employee resigned</option>
                                <option value="Employee terminated">Employee terminated</option>
                                <option value="Long-term leave">Long-term leave</option>
                                <option value="Security concern">Security concern</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
                        <button type="button" class="btn-cancel-modal"
                                data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-confirm red">
                            <i class="bi bi-x-circle me-1"></i>Deactivate User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
<script>

/* ── All companies and departments passed from PHP ── */
const allCompanies   = @json($companiesData);
const allDepartments = @json($departmentsData);

/* ── Filter companies by selected business unit ── */
function filterCompanies() {
    const buId     = document.getElementById('mBusinessUnit').value;
    const compSel  = document.getElementById('mCompany');
    const deptSel  = document.getElementById('mDept');

    // Reset downstream
    compSel.innerHTML = '<option value="">Select company…</option>';
    deptSel.innerHTML = '<option value="">Select department…</option>';
    compSel.disabled  = !buId;
    deptSel.disabled  = true;

    if (!buId) return;

    allCompanies
        .filter(c => c.business_unit_id === buId)
        .forEach(c => {
            const opt  = document.createElement('option');
            opt.value  = c.id;
            opt.text   = c.name;
            compSel.appendChild(opt);
        });
}

/* ── Filter departments by selected company ── */
function filterDepartments() {
    const compId  = document.getElementById('mCompany').value;
    const deptSel = document.getElementById('mDept');

    deptSel.innerHTML = '<option value="">Select department…</option>';
    deptSel.disabled  = !compId;

    if (!compId) return;

    allDepartments
        .filter(d => d.company_id === compId)
        .forEach(d => {
            const opt  = document.createElement('option');
            opt.value  = d.id;
            opt.text   = d.name;
            deptSel.appendChild(opt);
        });
}

/* ── Pre-select BU → Company → Dept when editing ── */
function preselectOrgHierarchy(buId, companyId, deptId) {
    // Set BU
    document.getElementById('mBusinessUnit').value = buId || '';
    filterCompanies();

    // Set Company after populating
    if (companyId) {
        document.getElementById('mCompany').value = companyId;
        filterDepartments();
    }

    // Set Department
    if (deptId) {
        document.getElementById('mDept').value = deptId;
    }
}

$(function () {

    /* ── Search debounce ── */
    let searchTimer;
    $('#searchInput').on('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => $('#filterForm').submit(), 500);
    });

    /* ── Select all checkbox ── */
    $('#selectAll').on('change', function () {
        $('.row-check').prop('checked', $(this).is(':checked'));
    });

    /* ── Role opt selection ── */
    $(document).on('click', '.role-opt', function () {
        $(this).siblings().removeClass('selected');
        $(this).addClass('selected');
        $('#mRoleId').val($(this).data('role-id'));
        updateAvatarPreview();
    });

    /* ── Status toggle ── */
    $(document).on('click', '.st-opt', function () {
        $(this).siblings().removeClass('active-sel inactive-sel');
        const val = $(this).data('status');
        $(this).addClass(val === '1' ? 'active-sel' : 'inactive-sel');
        $('#mActive').val(val);
    });

    /* ── Avatar preview ── */
    $('#mName').on('input', updateAvatarPreview);

    function updateAvatarPreview() {
        const name  = $('#mName').val().trim() || 'JD';
        const parts = name.split(' ');
        const ini   = parts.length >= 2
            ? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
            : (parts[0][0] || 'J').toUpperCase();
        const role  = $('.role-opt.selected').data('role-name') || 'Employee';
        const cls   = {
            'Employee':             'av-employee',
            'Helpdesk':             'av-helpdesk',
            'IT Support Specialist':'av-tech',
            'IT Admin':             'av-admin',
            'Executive':            'av-executive'
        }[role] || 'av-employee';
        $('#avatarPreview').text(ini).attr('class', 'avatar-preview ' + cls);
    }

    /* ── Reset modal fields ── */
    function resetModalFields() {
        $('#mName, #mEmail, #mPosition').val('');
        $('#mRoleId').val('');
        $('#mActive').val('1');
        $('.role-opt').removeClass('selected');
        $('.st-opt').removeClass('active-sel inactive-sel')
                    .filter('[data-status="1"]').addClass('active-sel');

        // Reset cascades
        document.getElementById('mBusinessUnit').value = '';
        document.getElementById('mCompany').innerHTML  = '<option value="">Select company…</option>';
        document.getElementById('mDept').innerHTML     = '<option value="">Select department…</option>';
        document.getElementById('mCompany').disabled   = true;
        document.getElementById('mDept').disabled      = true;

        updateAvatarPreview();
    }

    /* ── Open Add Modal ── */
    window.openAddModal = function () {
        $('#userModalTitle').html('Add <em>New User</em>');
        $('#userForm').attr('action', '{{ route('admin.users.store') }}');
        $('#formMethod').val('POST');
        $('#addPasswordNote').show();
        resetModalFields();
        new bootstrap.Modal('#userModal').show();
    };

    /* ── Open Edit Modal ── */
    window.openEditModal = function (userId) {
        fetch('/admin/users/' + userId)
            .then(r => r.json())
            .then(u => {
                $('#userModalTitle').html('Edit <em>User</em>');
                $('#userForm').attr('action', '/admin/users/' + userId);
                $('#formMethod').val('PUT');
                $('#mName').val(u.name);
                $('#mEmail').val(u.email);
                $('#mPosition').val(u.position ?? '');
                $('#mRoleId').val(u.role_id);
                $('#mActive').val(u.active ? '1' : '0');

                // Select role opt
                $('.role-opt').removeClass('selected');
                $(`.role-opt[data-role-id="${u.role_id}"]`).addClass('selected');

                // Select status
                $('.st-opt').removeClass('active-sel inactive-sel');
                $(`.st-opt[data-status="${u.active ? '1' : '0'}"]`)
                    .addClass(u.active ? 'active-sel' : 'inactive-sel');

                // ── Pre-select BU → Company → Department
                const buId     = u.department?.company?.business_unit_id
                               ?? u.department?.company?.businessUnit?.id
                               ?? null;
                const compId   = u.department?.companies_id
                               ?? u.department?.company_id
                               ?? null;
                const deptId   = u.department_id;

                preselectOrgHierarchy(buId, compId, deptId);

                $('#addPasswordNote').hide();
                updateAvatarPreview();
                new bootstrap.Modal('#userModal').show();
            })
            .catch(err => console.error('Load user error:', err));
    };

    /* ── Open Reset Modal ── */
    window.openResetModal = function (userId, userName) {
        $('#resetUserName').text(userName);
        $('#resetForm').attr('action', '/admin/users/' + userId + '/reset-password');
        new bootstrap.Modal('#resetPwModal').show();
    };

    /* ── Open Deactivate Modal ── */
    window.openDeactModal = function (userId, userName) {
        $('#deactUserName').text(userName);
        $('#deactForm').attr('action', '/admin/users/' + userId + '/deactivate');
        new bootstrap.Modal('#deactModal').show();
    };

});
</script>
@endsection