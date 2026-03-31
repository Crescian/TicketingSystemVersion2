@extends('layouts.admin')

@section('title', 'Organization Settings — LGICT')

@section('nav-role-badge')
    <span class="role-badge-admin"><i class="bi bi-shield-fill me-1"></i>IT Admin</span>
    <a href="{{ route('admin.users.index') }}" style="text-decoration:none">
        <span class="role-badge-admin"><i class="bi bi-people me-1"></i>Users</span>
    </a>
@endsection
@section('avatar-initials',
    strtoupper(substr(Auth::user()->name, 0, 1)) .
    strtoupper(substr(Auth::user()->name, strpos(Auth::user()->name, ' ') + 1, 1))
)
@section('nav-username', Auth::user()->name)

@section('hero-title')
    <h1><strong>ORG</strong> <em>SETTINGS</em></h1>
@endsection
@section('hero-subtitle', 'Manage business units, companies, and departments.')

@section('hero-stats')
    <div class="d-flex gap-2 flex-wrap">
        <div class="stat-pill esc">
            <span class="num">{{ $businessUnits->count() }}</span>
            <span class="lbl">Business Units</span>
        </div>
        <div class="stat-pill open">
            <span class="num">{{ $companies->count() }}</span>
            <span class="lbl">Companies</span>
        </div>
        <div class="stat-pill all">
            <span class="num">{{ $departments->count() }}</span>
            <span class="lbl">Departments</span>
        </div>
    </div>
@endsection

@section('sidebar')
    <div class="sidebar-card mb-3">
        <div class="sidebar-head red"><i class="bi bi-gear me-1"></i>Settings</div>
        <ul class="list-group sidebar-menu rounded-0">
            <li class="list-group-item">
                <a href="{{ route('admin.users.index') }}" class="d-flex align-items-center gap-2 text-decoration-none w-100">
                    <i class="bi bi-people me-1"></i>User
                </a>
            </li>
            <li class="list-group-item active">
                <a href="{{ route('admin.settings') }}" class="d-flex align-items-center gap-2 text-decoration-none w-100">
                    <i class="bi bi-building me-1"></i>Organization
                </a>
            </li>
            <li class="list-group-item">
                <a class="nav-link" href="{{ route('admin.sla-rules.index') }}">
                    <i class="bi bi-clock-history"></i>SLA Rules
                </a>
            </li>
            <li class="list-group-item">
                <a href="{{ route('admin.audit-log') }}" class="d-flex align-items-center gap-2 text-decoration-none w-100">
                    <i class="bi bi-journal-text me-1"></i>Audit Log
                </a>
            </li>
        </ul>
    </div>

    {{-- Hierarchy overview --}}
    <div class="sidebar-card">
        <div class="sidebar-head dark">Hierarchy</div>
        <div class="p-3">
            <div style="font-size:12px;color:var(--tm);font-weight:600;line-height:2">
                <div style="color:var(--rd);font-weight:800">🏢 Business Unit</div>
                <div style="padding-left:14px;border-left:2px solid var(--bd)">
                    <div style="color:var(--gd);font-weight:800">🏭 Company</div>
                    <div style="padding-left:14px;border-left:2px solid var(--bd)">
                        <div style="color:var(--tm);font-weight:800">🗂️ Department</div>
                        <div style="padding-left:14px;border-left:2px solid var(--bd)">
                            <div>👤 Users</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-3" style="font-size:11px;color:var(--tm);font-weight:600;background:var(--ygl);padding:8px 12px;border-radius:8px">
                <i class="bi bi-info-circle me-1" style="color:var(--gd)"></i>
                Deleting a parent will fail if children are assigned to it.
            </div>
        </div>
    </div>
@endsection

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

    {{-- Tab pills --}}
    <div class="d-flex gap-2 mb-4" id="settingsTabs">
        <button class="settings-tab active" data-tab="business-units" onclick="switchTab('business-units')">
            <i class="bi bi-building me-1"></i>Business Units
            <span class="tab-count">{{ $businessUnits->count() }}</span>
        </button>
        <button class="settings-tab" data-tab="companies" onclick="switchTab('companies')">
            <i class="bi bi-buildings me-1"></i>Companies
            <span class="tab-count">{{ $companies->count() }}</span>
        </button>
        <button class="settings-tab" data-tab="departments" onclick="switchTab('departments')">
            <i class="bi bi-diagram-3 me-1"></i>Departments
            <span class="tab-count">{{ $departments->count() }}</span>
        </button>
    </div>

    {{-- ══════════════════════════════════ --}}
    {{-- TAB 1 — BUSINESS UNITS            --}}
    {{-- ══════════════════════════════════ --}}
    <div class="tab-panel" id="tab-business-units">
        <div class="settings-layout">

            {{-- Add Form --}}
            <div class="settings-form-card">
                <div class="sfh">
                    <div class="sfh-icon" style="background:var(--rd)"><i class="bi bi-building-add"></i></div>
                    <div>
                        <div class="sfh-title" id="buFormTitle">Add Business Unit</div>
                        <div class="sfh-sub">Create a new business unit</div>
                    </div>
                </div>
                <form method="POST" id="buForm" action="{{ route('admin.settings.bu.store') }}">
                    @csrf
                    <input type="hidden" id="buMethod" name="_method" value="POST">
                    <input type="hidden" id="buId" value="">
                    <div class="sf-field">
                        <label>Business Unit Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="business_units_name"
                               id="buName" placeholder="e.g. Leonio Group of Companies"
                               required>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn-sf-save" id="buSaveBtn">
                            <i class="bi bi-check-lg me-1"></i>Save
                        </button>
                        <button type="button" class="btn-sf-cancel d-none" id="buCancelBtn"
                                onclick="resetBuForm()">Cancel</button>
                    </div>
                </form>
            </div>

            {{-- List --}}
            <div>
                <div class="sf-list-head d-flex align-items-center justify-content-between mb-3">
                    <span class="font-brand fw-900" style="font-size:18px">Business Units</span>
                    <div class="search-wrap" style="width:220px">
                        <i class="bi bi-search" style="color:var(--tm)"></i>
                        <input type="text" id="searchBU" placeholder="Search…" oninput="filterList('buList', this.value)">
                    </div>
                </div>
                <div class="sf-list" id="buList">
                    @forelse($businessUnits as $bu)
                        <div class="sf-list-item" data-name="{{ strtolower($bu->business_units_name) }}">
                            <div class="sli-icon" style="background:#fde8e8;color:var(--rd)">
                                <i class="bi bi-building"></i>
                            </div>
                            <div class="sli-body">
                                <div class="sli-name">{{ $bu->business_units_name }}</div>
                                <div class="sli-meta">
                                    {{ $bu->companies_count }} {{ Str::plural('company', $bu->companies_count) }}
                                </div>
                            </div>
                            <div class="sli-actions">
                                <button class="btn-sli-edit"
                                        onclick="editBU('{{ $bu->id }}', '{{ addslashes($bu->business_units_name) }}')">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" action="{{ route('admin.settings.bu.destroy', $bu) }}"
                                      onsubmit="return confirm('Delete this business unit?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-sli-del">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="sf-empty">
                            <i class="bi bi-building" style="font-size:32px;opacity:.3;display:block;margin-bottom:8px"></i>
                            No business units yet. Add one above.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════ --}}
    {{-- TAB 2 — COMPANIES                 --}}
    {{-- ══════════════════════════════════ --}}
    <div class="tab-panel d-none" id="tab-companies">
        <div class="settings-layout">

            {{-- Add Form --}}
            <div class="settings-form-card">
                <div class="sfh">
                    <div class="sfh-icon" style="background:var(--gd)"><i class="bi bi-buildings"></i></div>
                    <div>
                        <div class="sfh-title" id="compFormTitle">Add Company</div>
                        <div class="sfh-sub">Create a new company</div>
                    </div>
                </div>
                <form method="POST" id="compForm" action="{{ route('admin.settings.company.store') }}">
                    @csrf
                    <input type="hidden" id="compMethod" name="_method" value="POST">
                    <input type="hidden" id="compId" value="">
                    <div class="sf-field">
                        <label>Company Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="company_name"
                               id="compName" placeholder="e.g. Leonio Land Inc."
                               required>
                    </div>
                    <div class="sf-field">
                        <label>Business Unit <span class="text-danger">*</span></label>
                        <select class="form-select" name="business_units_id" id="compBU" required>
                            <option value="">— Select Business Unit —</option>
                            @foreach($businessUnits as $bu)
                                <option value="{{ $bu->id }}">{{ $bu->business_units_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn-sf-save" id="compSaveBtn">
                            <i class="bi bi-check-lg me-1"></i>Save
                        </button>
                        <button type="button" class="btn-sf-cancel d-none" id="compCancelBtn"
                                onclick="resetCompForm()">Cancel</button>
                    </div>
                </form>
            </div>

            {{-- List --}}
            <div>
                <div class="sf-list-head d-flex align-items-center justify-content-between mb-3">
                    <span class="font-brand fw-900" style="font-size:18px">Companies</span>
                    <div class="search-wrap" style="width:220px">
                        <i class="bi bi-search" style="color:var(--tm)"></i>
                        <input type="text" id="searchComp" placeholder="Search…" oninput="filterList('compList', this.value)">
                    </div>
                </div>
                <div class="sf-list" id="compList">
                    @forelse($companies as $company)
                        <div class="sf-list-item" data-name="{{ strtolower($company->company_name) }}">
                            <div class="sli-icon" style="background:var(--ygl);color:var(--gd)">
                                <i class="bi bi-buildings"></i>
                            </div>
                            <div class="sli-body">
                                <div class="sli-name">{{ $company->company_name }}</div>
                                <div class="sli-meta">
                                    <span style="font-size:10px;background:#fde8e8;color:var(--rd);border-radius:4px;padding:1px 6px;font-weight:700;margin-right:4px">
                                        {{ $company->businessUnit?->business_units_name ?? '—' }}
                                    </span>
                                    {{ $company->departments_count }} {{ Str::plural('department', $company->departments_count) }}
                                </div>
                            </div>
                            <div class="sli-actions">
                                <button class="btn-sli-edit"
                                        onclick="editCompany('{{ $company->id }}', '{{ addslashes($company->company_name) }}', '{{ $company->business_units_id }}')">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" action="{{ route('admin.settings.company.destroy', $company) }}"
                                      onsubmit="return confirm('Delete this company?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-sli-del">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="sf-empty">
                            <i class="bi bi-buildings" style="font-size:32px;opacity:.3;display:block;margin-bottom:8px"></i>
                            No companies yet. Add one above.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════ --}}
    {{-- TAB 3 — DEPARTMENTS              --}}
    {{-- ══════════════════════════════════ --}}
    <div class="tab-panel d-none" id="tab-departments">
        <div class="settings-layout">

            {{-- Add Form --}}
            <div class="settings-form-card">
                <div class="sfh">
                    <div class="sfh-icon" style="background:#1a3c8a"><i class="bi bi-diagram-3"></i></div>
                    <div>
                        <div class="sfh-title" id="deptFormTitle">Add Department</div>
                        <div class="sfh-sub">Create a new department</div>
                    </div>
                </div>
                <form method="POST" id="deptForm" action="{{ route('admin.settings.dept.store') }}">
                    @csrf
                    <input type="hidden" id="deptMethod" name="_method" value="POST">
                    <input type="hidden" id="deptId" value="">
                    <div class="sf-field">
                        <label>Department Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="department_name"
                               id="deptName" placeholder="e.g. Information Technology"
                               required>
                    </div>
                    <div class="sf-field">
                        <label>Company <span class="text-danger">*</span></label>
                        <select class="form-select" name="companies_id" id="deptComp" required>
                            <option value="">— Select Company —</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}"
                                        data-bu="{{ $company->businessUnit?->business_units_name }}">
                                    {{ $company->company_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn-sf-save" id="deptSaveBtn">
                            <i class="bi bi-check-lg me-1"></i>Save
                        </button>
                        <button type="button" class="btn-sf-cancel d-none" id="deptCancelBtn"
                                onclick="resetDeptForm()">Cancel</button>
                    </div>
                </form>
            </div>

            {{-- List --}}
            <div>
                <div class="sf-list-head d-flex align-items-center justify-content-between mb-3">
                    <span class="font-brand fw-900" style="font-size:18px">Departments</span>
                    <div class="search-wrap" style="width:220px">
                        <i class="bi bi-search" style="color:var(--tm)"></i>
                        <input type="text" id="searchDept" placeholder="Search…" oninput="filterList('deptList', this.value)">
                    </div>
                </div>
                <div class="sf-list" id="deptList">
                    @forelse($departments as $dept)
                        <div class="sf-list-item" data-name="{{ strtolower($dept->department_name) }}">
                            <div class="sli-icon" style="background:#e8eeff;color:#2a4ab0">
                                <i class="bi bi-diagram-3"></i>
                            </div>
                            <div class="sli-body">
                                <div class="sli-name">{{ $dept->department_name }}</div>
                                <div class="sli-meta">
                                    <span style="font-size:10px;background:var(--ygl);color:var(--gd);border-radius:4px;padding:1px 6px;font-weight:700;margin-right:4px">
                                        {{ $dept->company?->company_name ?? '—' }}
                                    </span>
                                    @if($dept->company?->businessUnit)
                                        <span style="font-size:10px;background:#fde8e8;color:var(--rd);border-radius:4px;padding:1px 6px;font-weight:700;margin-right:4px">
                                            {{ $dept->company->businessUnit->business_units_name }}
                                        </span>
                                    @endif
                                    {{ $dept->users_count }} {{ Str::plural('user', $dept->users_count) }}
                                </div>
                            </div>
                            <div class="sli-actions">
                                <button class="btn-sli-edit"
                                        onclick="editDept('{{ $dept->id }}', '{{ addslashes($dept->department_name) }}', '{{ $dept->companies_id }}')">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" action="{{ route('admin.settings.dept.destroy', $dept) }}"
                                      onsubmit="return confirm('Delete this department?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-sli-del">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="sf-empty">
                            <i class="bi bi-diagram-3" style="font-size:32px;opacity:.3;display:block;margin-bottom:8px"></i>
                            No departments yet. Add one above.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

@endsection

@section('styles')
    /* ── Settings tabs ── */
    .settings-tab {
        background: var(--cr);
        border: 1.5px solid var(--bd);
        border-radius: 50px;
        padding: 8px 18px;
        font-family: 'Nunito', sans-serif;
        font-weight: 800;
        font-size: 13px;
        color: var(--tm);
        cursor: pointer;
        transition: all .2s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .settings-tab:hover { border-color: var(--gl); color: var(--gd); }
    .settings-tab.active { background: var(--gd); color: var(--yg); border-color: var(--gd); }
    .tab-count {
        background: rgba(255,255,255,.2);
        border-radius: 20px;
        padding: 0 7px;
        font-size: 11px;
        font-weight: 900;
    }
    .settings-tab:not(.active) .tab-count { background: var(--ygl); color: var(--gd); }

    /* ── Layout ── */
    .settings-layout {
        display: grid;
        grid-template-columns: 340px 1fr;
        gap: 24px;
        align-items: start;
    }
    @media (max-width: 900px) { .settings-layout { grid-template-columns: 1fr; } }

    /* ── Form card ── */
    .settings-form-card {
        background: var(--cr);
        border: 1.5px solid var(--bd);
        border-radius: 20px;
        padding: 24px;
        position: sticky;
        top: 20px;
    }
    .sfh {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 1.5px solid var(--bd);
    }
    .sfh-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 18px;
        flex-shrink: 0;
    }
    .sfh-title { font-family: 'Nunito', sans-serif; font-weight: 900; font-size: 16px; color: var(--gd); }
    .sfh-sub   { font-size: 12px; color: var(--tm); font-weight: 600; }

    .sf-field { margin-bottom: 16px; }
    .sf-field label { font-size: 11px; font-weight: 800; color: var(--tm); text-transform: uppercase; letter-spacing: .4px; margin-bottom: 6px; display: block; }
    .sf-field .form-control,
    .sf-field .form-select {
        border: 1.5px solid var(--bd);
        border-radius: 10px;
        padding: 10px 14px;
        font-size: 13px;
        font-weight: 600;
        color: var(--gd);
        background: var(--cr);
        transition: border-color .2s;
    }
    .sf-field .form-control:focus,
    .sf-field .form-select:focus { border-color: var(--gl); background: #fff; box-shadow: none; }

    .btn-sf-save {
        background: var(--gd);
        color: var(--yg);
        font-family: 'Nunito', sans-serif;
        font-weight: 900;
        font-size: 13px;
        padding: 9px 22px;
        border-radius: 50px;
        border: none;
        cursor: pointer;
        transition: all .2s;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .btn-sf-save:hover { background: var(--gm); }
    .btn-sf-cancel {
        background: none;
        border: 1.5px solid var(--bd);
        color: var(--tm);
        font-family: 'Nunito', sans-serif;
        font-weight: 800;
        font-size: 13px;
        padding: 9px 18px;
        border-radius: 50px;
        cursor: pointer;
        transition: all .2s;
    }
    .btn-sf-cancel:hover { border-color: var(--rd); color: var(--rd); }

    /* ── List ── */
    .sf-list {
        background: var(--cr);
        border: 1.5px solid var(--bd);
        border-radius: 20px;
        overflow: hidden;
    }
    .sf-list-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 18px;
        border-bottom: 1px solid var(--bd);
        transition: background .15s;
    }
    .sf-list-item:last-child { border-bottom: none; }
    .sf-list-item:hover { background: var(--ygl); }

    .sli-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        flex-shrink: 0;
    }
    .sli-body { flex: 1; min-width: 0; }
    .sli-name { font-size: 13px; font-weight: 800; color: var(--gd); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .sli-meta { font-size: 11px; color: var(--tm); font-weight: 600; margin-top: 3px; display: flex; align-items: center; gap: 4px; flex-wrap: wrap; }

    .sli-actions { display: flex; gap: 6px; flex-shrink: 0; }
    .btn-sli-edit {
        width: 30px; height: 30px;
        background: var(--ygl); color: var(--gd);
        border: 1.5px solid var(--bd);
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; font-size: 12px;
        transition: all .2s;
    }
    .btn-sli-edit:hover { background: var(--yg); border-color: var(--yg); }
    .btn-sli-del {
        width: 30px; height: 30px;
        background: #fde8e8; color: var(--rd);
        border: 1.5px solid #f0c0c0;
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; font-size: 12px;
        transition: all .2s;
    }
    .btn-sli-del:hover { background: #f8c8c8; }

    .sf-empty {
        text-align: center;
        padding: 40px 20px;
        color: var(--tm);
        font-size: 13px;
        font-weight: 600;
    }

    .sf-list-head { margin-bottom: 12px; }

    /* ── Edit mode highlight ── */
    .settings-form-card.editing {
        border-color: var(--yg);
        box-shadow: 0 0 0 3px rgba(200,230,60,.15);
    }
@endsection

@section('scripts')
<script>

/* ── Tab switching ── */
function switchTab(tab) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.add('d-none'));
    document.querySelectorAll('.settings-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.remove('d-none');
    document.querySelector(`[data-tab="${tab}"]`).classList.add('active');
}

/* ── Filter list ── */
function filterList(listId, query) {
    const q = query.toLowerCase();
    document.querySelectorAll('#' + listId + ' .sf-list-item').forEach(item => {
        item.style.display = item.dataset.name.includes(q) ? '' : 'none';
    });
}

/* ══ BUSINESS UNIT EDIT ══ */
function editBU(id, name) {
    document.getElementById('buId').value        = id;
    document.getElementById('buName').value      = name;
    document.getElementById('buMethod').value    = 'PUT';
    document.getElementById('buForm').action     = `/admin/settings/business-units/${id}`;
    document.getElementById('buFormTitle').textContent = 'Edit Business Unit';
    document.getElementById('buSaveBtn').innerHTML = '<i class="bi bi-check-lg me-1"></i>Update';
    document.getElementById('buCancelBtn').classList.remove('d-none');
    document.querySelector('.settings-form-card').classList.add('editing');
    document.getElementById('buName').focus();
    switchTab('business-units');
}
function resetBuForm() {
    document.getElementById('buId').value        = '';
    document.getElementById('buName').value      = '';
    document.getElementById('buMethod').value    = 'POST';
    document.getElementById('buForm').action     = '{{ route("admin.settings.bu.store") }}';
    document.getElementById('buFormTitle').textContent = 'Add Business Unit';
    document.getElementById('buSaveBtn').innerHTML = '<i class="bi bi-check-lg me-1"></i>Save';
    document.getElementById('buCancelBtn').classList.add('d-none');
    document.querySelector('.settings-form-card').classList.remove('editing');
}

/* ══ COMPANY EDIT ══ */
function editCompany(id, name, buId) {
    document.getElementById('compId').value         = id;
    document.getElementById('compName').value       = name;
    document.getElementById('compBU').value         = buId;
    document.getElementById('compMethod').value     = 'PUT';
    document.getElementById('compForm').action      = `/admin/settings/companies/${id}`;
    document.getElementById('compFormTitle').textContent = 'Edit Company';
    document.getElementById('compSaveBtn').innerHTML = '<i class="bi bi-check-lg me-1"></i>Update';
    document.getElementById('compCancelBtn').classList.remove('d-none');
    switchTab('companies');
    document.getElementById('compName').focus();
}
function resetCompForm() {
    document.getElementById('compId').value         = '';
    document.getElementById('compName').value       = '';
    document.getElementById('compBU').value         = '';
    document.getElementById('compMethod').value     = 'POST';
    document.getElementById('compForm').action      = '{{ route("admin.settings.company.store") }}';
    document.getElementById('compFormTitle').textContent = 'Add Company';
    document.getElementById('compSaveBtn').innerHTML = '<i class="bi bi-check-lg me-1"></i>Save';
    document.getElementById('compCancelBtn').classList.add('d-none');
}

/* ══ DEPARTMENT EDIT ══ */
function editDept(id, name, compId) {
    document.getElementById('deptId').value         = id;
    document.getElementById('deptName').value       = name;
    document.getElementById('deptComp').value       = compId;
    document.getElementById('deptMethod').value     = 'PUT';
    document.getElementById('deptForm').action      = `/admin/settings/departments/${id}`;
    document.getElementById('deptFormTitle').textContent = 'Edit Department';
    document.getElementById('deptSaveBtn').innerHTML = '<i class="bi bi-check-lg me-1"></i>Update';
    document.getElementById('deptCancelBtn').classList.remove('d-none');
    switchTab('departments');
    document.getElementById('deptName').focus();
}
function resetDeptForm() {
    document.getElementById('deptId').value         = '';
    document.getElementById('deptName').value       = '';
    document.getElementById('deptComp').value       = '';
    document.getElementById('deptMethod').value     = 'POST';
    document.getElementById('deptForm').action      = '{{ route("admin.settings.dept.store") }}';
    document.getElementById('deptFormTitle').textContent = 'Add Department';
    document.getElementById('deptSaveBtn').innerHTML = '<i class="bi bi-check-lg me-1"></i>Save';
    document.getElementById('deptCancelBtn').classList.add('d-none');
}

</script>
@endsection