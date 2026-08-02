@extends('layouts.admin')

@section('title', 'Holidays — LGICT')

@section('nav-role-badge')
    @php $canManageOrg = in_array(Auth::user()->role?->role_name, ['Helpdesk', 'IT Admin', 'Supervisor - IT Admin']); @endphp
    <span class="role-badge-admin"><i class="bi bi-shield-fill me-1"></i>{{ Auth::user()->role?->role_name ?? 'IT Admin' }}</span>
    @if($canManageOrg)
        <a href="{{ route('portal.users.index') }}" style="text-decoration:none">
            <span class="role-badge-admin"><i class="bi bi-people me-1"></i>Users</span>
        </a>
    @endif
@endsection
@section('avatar-initials',
    strtoupper(substr(Auth::user()->name, 0, 1)) .
    strtoupper(substr(Auth::user()->name, strpos(Auth::user()->name, ' ') + 1, 1))
)
@section('nav-username', Auth::user()->name)

@section('hero-title')
    <h1><strong>HOLIDAY</strong> <em>CALENDAR</em></h1>
@endsection
@section('hero-subtitle', 'Org-wide non-working days — skipped by scheduling alongside weekends.')

@section('hero-stats')
    <div class="d-flex gap-2 flex-wrap">
        <div class="stat-pill open">
            <span class="num">{{ $holidays->where('is_active', true)->count() }}</span>
            <span class="lbl">Active</span>
        </div>
        <div class="stat-pill all">
            <span class="num">{{ $holidays->count() }}</span>
            <span class="lbl">Total</span>
        </div>
    </div>
@endsection

@section('sidebar')
    <div class="sidebar-card mb-3">
        <div class="sidebar-head red"><i class="bi bi-gear me-1"></i>Settings</div>
        <ul class="list-group sidebar-menu rounded-0">
            @if($canManageOrg)
                <li class="list-group-item">
                    <a href="{{ route('portal.users.index') }}" class="d-flex align-items-center gap-2 text-decoration-none w-100">
                        <i class="bi bi-people me-1"></i>User
                    </a>
                </li>
                <li class="list-group-item">
                    <a href="{{ route('portal.settings') }}" class="d-flex align-items-center gap-2 text-decoration-none w-100">
                        <i class="bi bi-building me-1"></i>Organization
                    </a>
                </li>
            @endif
            <li class="list-group-item">
                <a href="{{ route('portal.sla-rules.index') }}" class="d-flex align-items-center gap-2 text-decoration-none w-100">
                    <i class="bi bi-stopwatch me-1"></i>SLA Rules
                </a>
            </li>
            <li class="list-group-item active">
                <a href="{{ route('portal.holidays.index') }}" class="d-flex align-items-center gap-2 text-decoration-none w-100">
                    <i class="bi bi-calendar-x me-1"></i>Holidays
                </a>
            </li>
            @if($canManageOrg)
                <li class="list-group-item">
                    <a href="{{ route('portal.audit-log') }}" class="d-flex align-items-center gap-2 text-decoration-none w-100">
                        <i class="bi bi-journal-text me-1"></i>Audit Log
                    </a>
                </li>
            @endif
        </ul>
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
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-3">
            <i class="bi bi-exclamation-circle me-2"></i>{{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="sla-main-layout">
        <div class="sla-left">
            <div class="sla-panel" id="holFormCard">
                <div class="sla-panel-head">
                    <div class="sph-icon" style="background:var(--rd)"><i class="bi bi-calendar-x"></i></div>
                    <div>
                        <div class="sph-title" id="holFormTitle">Add Holiday</div>
                        <div class="sph-sub">Skipped by "Next Working Day" scheduling, same as weekends</div>
                    </div>
                </div>
                <form method="POST" id="holForm" action="{{ route('portal.holidays.store') }}">
                    @csrf
                    <input type="hidden" id="holMethod" name="_method" value="POST">

                    <div class="sf-field">
                        <label>Date <span class="req">*</span></label>
                        <input type="date" class="form-control" name="date" id="holDate" required>
                    </div>

                    <div class="sf-field">
                        <label>Name <span class="req">*</span></label>
                        <input type="text" class="form-control" name="name" id="holName"
                               placeholder="e.g. National Heroes Day" required>
                    </div>

                    <div class="sf-field">
                        <label>Type</label>
                        <select class="form-select" name="type" id="holType">
                            <option value="">— None —</option>
                            <option value="regular">Regular Holiday</option>
                            <option value="special-non-working">Special (Non-Working) Day</option>
                        </select>
                    </div>

                    <div class="d-flex gap-2 mt-2">
                        <button type="submit" class="btn-save"><i class="bi bi-check-lg me-1"></i><span id="holSaveText">Add Holiday</span></button>
                        <button type="button" class="btn-cancel d-none" id="holCancelBtn" onclick="resetHolForm()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="sla-right">
            <div class="sla-panel">
                <div class="sla-panel-head">
                    <div class="sph-icon" style="background:var(--rd)"><i class="bi bi-list-check"></i></div>
                    <div>
                        <div class="sph-title">Holidays</div>
                        <div class="sph-sub">Org-wide calendar — inactive entries are ignored by scheduling</div>
                    </div>
                </div>

                @if($holidays->isEmpty())
                    <div class="p-4 text-center" style="color:var(--tm);font-size:13px">
                        No holidays added yet.
                    </div>
                @else
                    <div class="table-responsive rules-table-wrap">
                        <table class="rules-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th style="text-align:right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($holidays as $holiday)
                                    <tr class="{{ !$holiday->is_active ? 'row-inactive' : '' }}">
                                        <td><div class="sub-name">{{ $holiday->date->format('M d, Y (D)') }}</div></td>
                                        <td>{{ $holiday->name }}</td>
                                        <td>
                                            @if($holiday->type)
                                                <span class="time-badge resp">{{ ucwords(str_replace('-', ' ', $holiday->type)) }}</span>
                                            @else
                                                <span style="color:var(--tm);font-size:12px">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <form method="POST" action="{{ route('portal.holidays.toggle', $holiday) }}">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="toggle-btn {{ $holiday->is_active ? 'active' : 'inactive' }}">
                                                    {{ $holiday->is_active ? '● Active' : '○ Inactive' }}
                                                </button>
                                            </form>
                                        </td>
                                        <td style="text-align:right">
                                            <div class="d-flex gap-1 justify-content-end">
                                                <button class="btn-row-action edit"
                                                        onclick="editHoliday(
                                                            '{{ route('portal.holidays.update', $holiday) }}',
                                                            '{{ $holiday->date->format('Y-m-d') }}',
                                                            '{{ addslashes($holiday->name) }}',
                                                            '{{ $holiday->type }}'
                                                        )"
                                                        title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form method="POST" action="{{ route('portal.holidays.destroy', $holiday) }}"
                                                      onsubmit="return confirm('Delete the &quot;{{ addslashes($holiday->name) }}&quot; holiday?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn-row-action del" title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection

@section('styles')
    /* ── Main layout ── */
    .sla-main-layout { display:grid; grid-template-columns:320px 1fr; gap:24px; align-items:start; }
    @media (max-width:900px) { .sla-main-layout { grid-template-columns:1fr; } }
    .sla-left { position:sticky; top:16px; }

    /* ── Panel ── */
    .sla-panel { background:var(--cr); border:1.5px solid var(--bd); border-radius:20px; padding:22px; }
    .sla-panel.editing { border-color:var(--yg); box-shadow:0 0 0 3px rgba(200,230,60,.12); }
    .sla-panel-head { display:flex; align-items:center; gap:12px; margin-bottom:18px; padding-bottom:16px; border-bottom:1.5px solid var(--bd); }
    .sph-icon { width:38px; height:38px; border-radius:12px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:17px; flex-shrink:0; }
    .sph-title { font-family:'Nunito',sans-serif; font-weight:900; font-size:15px; color:var(--gd); }
    .sph-sub   { font-size:11px; color:var(--tm); font-weight:600; }

    /* ── Form fields ── */
    .sf-field { margin-bottom:14px; }
    .sf-field label { font-size:10px; font-weight:800; color:var(--tm); text-transform:uppercase; letter-spacing:.4px; margin-bottom:5px; display:block; }
    .sf-field .form-control, .sf-field .form-select { border:1.5px solid var(--bd); border-radius:10px; padding:9px 13px; font-size:13px; font-weight:600; color:var(--gd); background:var(--cr); transition:border-color .2s; }
    .sf-field .form-control:focus, .sf-field .form-select:focus { border-color:var(--gl); background:#fff; box-shadow:none; }
    .req { color:var(--rd); }

    /* ── Buttons ── */
    .btn-save { background:var(--gd); color:var(--yg); font-family:'Nunito',sans-serif; font-weight:900; font-size:13px; padding:9px 22px; border-radius:50px; border:none; cursor:pointer; transition:all .2s; display:inline-flex; align-items:center; gap:5px; }
    .btn-save:hover { background:var(--gm); }
    .btn-cancel { background:none; border:1.5px solid var(--bd); color:var(--tm); font-family:'Nunito',sans-serif; font-weight:800; font-size:13px; padding:9px 18px; border-radius:50px; cursor:pointer; transition:all .2s; }
    .btn-cancel:hover { border-color:var(--rd); color:var(--rd); }

    /* ── Table ── */
    .rules-table-wrap { overflow-x:auto; }
    .rules-table { width:100%; border-collapse:collapse; }
    .rules-table thead tr { border-bottom:1.5px solid var(--bd); }
    .rules-table th { padding:10px 16px; font-size:10px; font-weight:800; color:var(--tm); text-transform:uppercase; letter-spacing:.4px; text-align:left; white-space:nowrap; }
    .rules-table tbody tr { border-bottom:1px solid var(--bd); transition:background .15s; }
    .rules-table tbody tr:last-child { border-bottom:none; }
    .rules-table tbody tr:hover { background:var(--ygl); }
    .rules-table td { padding:12px 16px; vertical-align:middle; }
    .row-inactive td { opacity:.5; }

    .sub-name { font-size:13px; font-weight:800; color:var(--gd); }

    .time-badge { font-size:12px; font-weight:700; border-radius:20px; padding:3px 10px; display:inline-flex; align-items:center; white-space:nowrap; }
    .time-badge.resp { background:#e8eeff; color:#2a4ab0; }

    .toggle-btn { font-size:11px; font-weight:800; border-radius:20px; padding:3px 10px; border:none; cursor:pointer; font-family:'Nunito',sans-serif; transition:all .2s; }
    .toggle-btn.active   { background:#d4f0d4; color:#1a5a3a; }
    .toggle-btn.inactive { background:var(--bd); color:var(--tm); }
    .toggle-btn:hover { opacity:.8; }

    .btn-row-action { width:28px; height:28px; border-radius:7px; display:flex; align-items:center; justify-content:center; font-size:12px; cursor:pointer; border:1.5px solid; transition:all .2s; }
    .btn-row-action.edit { background:var(--ygl); color:var(--gd); border-color:var(--bd); }
    .btn-row-action.edit:hover { background:var(--yg); }
    .btn-row-action.del  { background:#fde8e8; color:var(--rd); border-color:#f0c0c0; }
    .btn-row-action.del:hover  { background:#f8c8c8; }
@endsection

@section('scripts')
<script>
function editHoliday(updateUrl, date, name, type) {
    document.getElementById('holForm').action = updateUrl;
    document.getElementById('holMethod').value = 'PUT';
    document.getElementById('holDate').value = date;
    document.getElementById('holName').value = name;
    document.getElementById('holType').value = type || '';

    document.getElementById('holFormTitle').textContent = 'Edit Holiday';
    document.getElementById('holSaveText').textContent  = 'Update';
    document.getElementById('holCancelBtn').classList.remove('d-none');
    document.getElementById('holFormCard').classList.add('editing');
    document.getElementById('holFormCard').scrollIntoView({ behavior: 'smooth', block: 'start' });
    document.getElementById('holName').focus();
}

function resetHolForm() {
    document.getElementById('holForm').action = '{{ route("portal.holidays.store") }}';
    document.getElementById('holMethod').value = 'POST';
    document.getElementById('holDate').value = '';
    document.getElementById('holName').value = '';
    document.getElementById('holType').value = '';

    document.getElementById('holFormTitle').textContent = 'Add Holiday';
    document.getElementById('holSaveText').textContent  = 'Add Holiday';
    document.getElementById('holCancelBtn').classList.add('d-none');
    document.getElementById('holFormCard').classList.remove('editing');
}
</script>
@endsection
