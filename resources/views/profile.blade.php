@extends('layouts.app')

@section('title', 'My Profile — LGICT')

@section('nav-role-badge')
    @php $roleName = Auth::user()->role?->role_name ?? 'Employee'; @endphp
    <span class="role-badge"><i class="bi bi-person me-1"></i>{{ $roleName }}</span>
@endsection
@section('avatar-initials',
    strtoupper(substr(Auth::user()->name, 0, 1)) .
    strtoupper(substr(Auth::user()->name, strpos(Auth::user()->name, ' ') + 1, 1))
)
@section('nav-username', Auth::user()->name)

@section('hero-title')
    <h1>MY <em>PROFILE</em></h1>
@endsection
@section('hero-subtitle', 'View your account details and manage your password.')

@section('hero-stats')
    @php
        $user      = Auth::user()->load('role', 'department');
        $roleName  = $user->role?->role_name ?? 'Employee';
        $myTickets = \App\Models\Tickets::where('users_id', $user->id)->count();
        $myResolved = \App\Models\Tickets::where('users_id', $user->id)->where('status', 'Resolved')->count();
        $assignedResolved = \App\Models\Tickets::where('assigned_to', $user->id)->where('status', 'Resolved')->count();
        $avgRating = \Illuminate\Support\Facades\DB::table('ticket_feed_backs')
            ->whereIn('ticket_id', \App\Models\Tickets::where('assigned_to', $user->id)->pluck('id'))
            ->avg('rating');
    @endphp
    <div class="d-flex gap-2 flex-wrap">
        @if($roleName === 'IT Technician')
            <div class="stat-pill">
                <span class="num">{{ $assignedResolved }}</span>
                <span class="lbl">Resolved</span>
            </div>
            <div class="stat-pill warn">
                <span class="num">{{ $avgRating ? number_format($avgRating, 1) : 'N/A' }}</span>
                <span class="lbl">Avg Rating</span>
            </div>
        @elseif($roleName === 'Employee')
            <div class="stat-pill">
                <span class="num">{{ $myTickets }}</span>
                <span class="lbl">My Tickets</span>
            </div>
            <div class="stat-pill">
                <span class="num">{{ $myResolved }}</span>
                <span class="lbl">Resolved</span>
            </div>
        @else
            <div class="stat-pill">
                <span class="num">{{ \App\Models\Tickets::count() }}</span>
                <span class="lbl">Total Tickets</span>
            </div>
            <div class="stat-pill">
                <span class="num">{{ \App\Models\Tickets::where('status', 'Resolved')->count() }}</span>
                <span class="lbl">Resolved</span>
            </div>
        @endif
        <div class="stat-pill">
            <span class="num">{{ $user->created_at->diffInDays(now()) }}</span>
            <span class="lbl">Days Active</span>
        </div>
    </div>
@endsection

@section('styles')
    .profile-grid { display: grid; grid-template-columns: 300px 1fr; gap: 24px; align-items: start; }
    @media (max-width: 900px) { .profile-grid { grid-template-columns: 1fr; } }

    /* ── Profile card ── */
    .profile-card { background: var(--cr); border: 1.5px solid var(--bd); border-radius: 20px; overflow: hidden; }
    .profile-card-top { background: var(--gd); padding: 32px 24px 36px; text-align: center; position: relative; }
    .profile-card-top::after { content: ''; position: absolute; bottom: -1px; left: 0; right: 0; height: 24px; background: var(--cr); border-radius: 24px 24px 0 0; }
    .profile-avatar-lg { width: 88px; height: 88px; border-radius: 50%; background: var(--yg); color: var(--gd); font-family: 'Nunito', sans-serif; font-weight: 900; font-size: 32px; display: flex; align-items: center; justify-content: center; margin: 0 auto 14px; border: 4px solid rgba(255,255,255,.15); position: relative; z-index: 1; }
    .profile-name { font-family: 'Nunito', sans-serif; font-weight: 900; font-size: 19px; color: var(--yg); margin-bottom: 6px; position: relative; z-index: 1; }
    .profile-role-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: 800; font-family: 'Nunito', sans-serif; position: relative; z-index: 1; }
    .role-employee { background: rgba(200,230,60,.15); color: var(--yg); }
    .role-helpdesk { background: rgba(63,185,80,.15); color: #3fb950; }
    .role-tech     { background: rgba(245,200,66,.15); color: #f5c842; }
    .role-admin    { background: rgba(248,81,73,.15); color: #f85149; }
    .role-manager  { background: rgba(88,166,255,.15); color: #58a6ff; }

    .profile-info-list { padding: 8px 20px 20px; }
    .profile-info-item { display: flex; align-items: flex-start; gap: 12px; padding: 11px 0; border-bottom: 1px solid var(--bd); }
    .profile-info-item:last-child { border-bottom: none; }
    .pi-icon { width: 30px; height: 30px; background: var(--ygl); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--gd); font-size: 13px; flex-shrink: 0; margin-top: 2px; }
    .pi-label { font-size: 10px; font-weight: 700; color: var(--tm); text-transform: uppercase; letter-spacing: .4px; margin-bottom: 2px; }
    .pi-value { font-size: 13px; font-weight: 700; color: var(--gd); word-break: break-all; }
    .pi-active { display: inline-flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 800; padding: 3px 10px; border-radius: 20px; }
    .pi-active.on  { background: #d4f0d4; color: #1a5a3a; }
    .pi-active.off { background: #fde8e8; color: #8b1a1a; }

    /* ── Panels ── */
    .panel-section { background: var(--cr); border: 1.5px solid var(--bd); border-radius: 20px; overflow: hidden; margin-bottom: 20px; }
    .panel-section:last-child { margin-bottom: 0; }
    .panel-head { padding: 16px 22px; border-bottom: 1.5px solid var(--bd); display: flex; align-items: center; gap: 12px; }
    .panel-head-icon { width: 34px; height: 34px; background: var(--gd); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--yg); font-size: 15px; flex-shrink: 0; }
    .panel-head-title { font-family: 'Nunito', sans-serif; font-weight: 900; font-size: 15px; color: var(--gd); }
    .panel-head-sub { font-size: 12px; color: var(--tm); font-weight: 600; }
    .panel-body { padding: 22px; }

    /* ── Form fields ── */
    .field-group { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
    .field-group.full { grid-template-columns: 1fr; }
    @media (max-width: 640px) { .field-group { grid-template-columns: 1fr; } }
    .field-wrap label { font-size: 11px; font-weight: 800; color: var(--tm); text-transform: uppercase; letter-spacing: .4px; margin-bottom: 6px; display: block; }
    .field-wrap .form-control, .field-wrap .form-select { border: 1.5px solid var(--bd); border-radius: 10px; padding: 10px 14px; font-size: 13px; font-weight: 600; color: var(--gd); background: var(--cr); transition: border-color .2s; }
    .field-wrap .form-control:focus, .field-wrap .form-select:focus { border-color: var(--gl); background: #fff; box-shadow: none; }
    .field-wrap .form-control[readonly] { background: var(--ygl); color: var(--tm); cursor: not-allowed; }

    /* ── Password ── */
    .pw-wrap { position: relative; }
    .pw-wrap .form-control { padding-right: 44px; }
    .pw-toggle { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--tm); cursor: pointer; padding: 0; font-size: 15px; line-height: 1; }
    .pw-toggle:hover { color: var(--gd); }
    .pw-strength-bar { height: 4px; border-radius: 4px; background: var(--bd); margin-top: 6px; overflow: hidden; }
    .pw-strength-fill { height: 4px; border-radius: 4px; transition: width .3s, background .3s; width: 0%; }
    .pw-rules { display: grid; grid-template-columns: 1fr 1fr; gap: 4px; margin-top: 8px; }
    .pw-rule { font-size: 11px; font-weight: 600; color: var(--tm); display: flex; align-items: center; gap: 5px; transition: color .2s; }
    .pw-rule.pass { color: var(--gm); }
    .pw-rule i { font-size: 10px; }

    /* ── Buttons ── */
    .btn-change-pw { background: var(--yg); color: var(--gd); font-family: 'Nunito', sans-serif; font-weight: 900; font-size: 14px; padding: 11px 28px; border-radius: 50px; border: none; cursor: pointer; transition: all .2s; display: inline-flex; align-items: center; gap: 6px; }
    .btn-change-pw:hover { background: var(--ygd); }
    .btn-change-pw:disabled { opacity: .5; cursor: not-allowed; }

    /* ── Stats grid ── */
    .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
    .stat-box { background: var(--ygl); border-radius: 12px; padding: 14px; text-align: center; }
    .stat-box .sb-num { font-family: 'Nunito', sans-serif; font-weight: 900; font-size: 26px; color: var(--gd); line-height: 1; }
    .stat-box .sb-lbl { font-size: 10px; font-weight: 700; color: var(--tm); text-transform: uppercase; letter-spacing: .4px; margin-top: 4px; }
    .stat-box.span2 { grid-column: span 3; }

    /* ── Activity ── */
    .activity-item { display: flex; gap: 12px; padding: 11px 0; border-bottom: 1px solid var(--bd); }
    .activity-item:last-child { border-bottom: none; }
    .act-dot { width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; flex-shrink: 0; }
    .act-dot.open      { background: var(--ygl); color: var(--gd); }
    .act-dot.progress  { background: #fff4cc; color: #7a5a00; }
    .act-dot.escalated { background: #fde8e8; color: #8b1a1a; }
    .act-dot.resolved  { background: #d4f0d4; color: #1a5a3a; }
    .act-dot.cancelled { background: var(--bd); color: var(--tm); }
    .act-title { font-size: 13px; font-weight: 700; color: var(--gd); }
    .act-meta  { font-size: 11px; color: var(--tm); font-weight: 600; margin-top: 2px; }
    .act-badge { font-size: 10px; font-weight: 800; border-radius: 20px; padding: 2px 8px; margin-left: 6px; }

    /* ── Accent bar ── */
    .accent-bar { height: 4px; width: 100%; }
    .accent-employee { background: linear-gradient(90deg, var(--yg), var(--gl)); }
    .accent-helpdesk { background: linear-gradient(90deg, #3fb950, #58a6ff); }
    .accent-tech     { background: linear-gradient(90deg, #f5c842, #d29922); }
    .accent-admin    { background: linear-gradient(90deg, #f85149, #d29922); }
    .accent-manager  { background: linear-gradient(90deg, #58a6ff, #c8e63c); }
@endsection

@section('sidebar')
    @php
        $user     = Auth::user()->load('role', 'department');
        $roleName = $user->role?->role_name ?? 'Employee';
    @endphp
    <div class="sidebar-card mb-3">
        <div class="sidebar-head">Navigation</div>
        <ul class="list-group sidebar-menu rounded-0">
            <li class="list-group-item active">
                <a href="#" class="d-flex align-items-center gap-2 text-decoration-none w-100">
                    <i class="bi bi-person-circle me-1"></i> My Profile
                </a>
            </li>
            <li class="list-group-item">
                <a href="#pwSection" class="d-flex align-items-center gap-2 text-decoration-none w-100">
                    <i class="bi bi-shield-lock me-1"></i> Change Password
                </a>
            </li>
            <li class="list-group-item">
                <a href="#activitySection" class="d-flex align-items-center gap-2 text-decoration-none w-100">
                    <i class="bi bi-clock-history me-1"></i> Recent Activity
                </a>
            </li>
        </ul>
    </div>
    <div class="sidebar-card">
        <div class="sidebar-head">Quick Info</div>
        <div class="p-3 d-flex flex-column gap-3">
            <div>
                <div style="font-size:11px;font-weight:700;color:var(--tm);text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px">Role</div>
                <div style="font-size:13px;font-weight:800;color:var(--gd)">{{ $roleName }}</div>
            </div>
            <div>
                <div style="font-size:11px;font-weight:700;color:var(--tm);text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px">Department</div>
                <div style="font-size:13px;font-weight:800;color:var(--gd)">{{ $user->department?->department_name ?? '—' }}</div>
            </div>
            <div>
                <div style="font-size:11px;font-weight:700;color:var(--tm);text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px">Member Since</div>
                <div style="font-size:13px;font-weight:800;color:var(--gd)">{{ $user->created_at->format('M d, Y') }}</div>
            </div>
            <div>
                <div style="font-size:11px;font-weight:700;color:var(--tm);text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px">Status</div>
                <span class="pi-active {{ $user->active ? 'on' : 'off' }}">
                    <i class="bi bi-circle-fill" style="font-size:7px"></i>
                    {{ $user->active ? 'Active' : 'Inactive' }}
                </span>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @php
        $user     = Auth::user()->load('role', 'department.company.businessUnit');
        $roleName = $user->role?->role_name ?? 'Employee';
        $roleSlug = match($roleName) {
            'IT Technician' => 'tech',
            'Helpdesk'      => 'helpdesk',
            'IT Admin'      => 'admin',
            'Manager'       => 'manager',
            default         => 'employee',
        };
        $initials = strtoupper(substr($user->name, 0, 1)) .
                    strtoupper(substr($user->name, strpos($user->name, ' ') + 1, 1));
        $totalAssigned  = \App\Models\Tickets::where('assigned_to', $user->id)->count();
        $totalSubmitted = \App\Models\Tickets::where('users_id', $user->id)->count();
        $totalResolved  = \App\Models\Tickets::where('assigned_to', $user->id)->where('status', 'Resolved')->count();
        $totalEsc       = \App\Models\Tickets::where('assigned_to', $user->id)->where('status', 'Escalated')->count();
        $avgRating      = \Illuminate\Support\Facades\DB::table('ticket_feed_backs')
            ->whereIn('ticket_id', \App\Models\Tickets::where('assigned_to', $user->id)->pluck('id'))
            ->avg('rating');
        $recentTickets  = \App\Models\Tickets::with('assignedTo', 'user')
            ->where(function($q) use ($user, $roleName) {
                if ($roleName === 'IT Technician') {
                    $q->where('assigned_to', $user->id);
                } elseif ($roleName === 'Employee') {
                    $q->where('users_id', $user->id);
                }
                // Helpdesk, Admin, Manager see all recent
            })
            ->latest()
            ->limit(5)
            ->get();
    @endphp

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
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-3">
            <i class="bi bi-exclamation-circle me-2"></i>{{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="profile-grid">

        {{-- ══ LEFT — Profile Card ══ --}}
        <div>
            <div class="profile-card">
                <div class="accent-bar accent-{{ $roleSlug }}"></div>
                <div class="profile-card-top">
                    <div class="profile-avatar-lg">{{ $initials }}</div>
                    <div class="profile-name">{{ $user->name }}</div>
                    <span class="profile-role-badge role-{{ $roleSlug }}">
                        <i class="bi bi-{{ match($roleName) {
                            'IT Technician' => 'tools',
                            'Helpdesk'      => 'headset',
                            'IT Admin'      => 'shield-fill',
                            'Manager'       => 'bar-chart-line',
                            default         => 'person-fill'
                        } }}"></i>
                        {{ $roleName }}
                    </span>
                </div>
                <div class="profile-info-list">
                    <div class="profile-info-item">
                        <div class="pi-icon"><i class="bi bi-envelope"></i></div>
                        <div>
                            <div class="pi-label">Email</div>
                            <div class="pi-value">{{ $user->email }}</div>
                        </div>
                    </div>
                    {{-- <div class="profile-info-item">
                        <div class="pi-icon"><i class="bi bi-briefcase"></i></div>
                        <div>
                            <div class="pi-label">Position</div>
                            <div class="pi-value">{{ $user->position ?? '—' }}</div>
                        </div>
                    </div> --}}
                    {{-- <div class="profile-info-item">
                        <div class="pi-icon"><i class="bi bi-building"></i></div>
                        <div>
                            <div class="pi-label">Department</div>
                            <div class="pi-value">{{ $user->department?->department_name ?? '—' }}</div>
                        </div>
                    </div> --}}
                    <div class="profile-info-item">
                        <div class="pi-icon"><i class="bi bi-diagram-3"></i></div>
                        <div>
                            <div class="pi-label">Company</div>
                            <div class="pi-value">{{ $user->department?->company?->company_name ?? '—' }}</div>
                        </div>
                    </div>
                    <div class="profile-info-item">
                        <div class="pi-icon"><i class="bi bi-calendar3"></i></div>
                        <div>
                            <div class="pi-label">Member Since</div>
                            <div class="pi-value">{{ $user->created_at->format('F d, Y') }}</div>
                        </div>
                    </div>
                    <div class="profile-info-item">
                        <div class="pi-icon">
                            <i class="bi bi-circle-fill" style="font-size:9px;color:{{ $user->active ? '#3fb950' : '#e24b4a' }}"></i>
                        </div>
                        <div>
                            <div class="pi-label">Account Status</div>
                            <span class="pi-active {{ $user->active ? 'on' : 'off' }}">
                                {{ $user->active ? '● Active' : '● Inactive' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stats card (role-specific) --}}
            @if($roleName === 'IT Technician' || $roleName === 'Helpdesk')
                <div class="panel-section mt-4">
                    <div class="panel-head">
                        <div class="panel-head-icon"><i class="bi bi-bar-chart"></i></div>
                        <div>
                            <div class="panel-head-title">My Performance</div>
                            <div class="panel-head-sub">Lifetime stats</div>
                        </div>
                    </div>
                    <div class="panel-body" style="padding-top:16px">
                        <div class="stats-grid">
                            <div class="stat-box">
                                <div class="sb-num">{{ $totalAssigned }}</div>
                                <div class="sb-lbl">Assigned</div>
                            </div>
                            <div class="stat-box">
                                <div class="sb-num">{{ $totalResolved }}</div>
                                <div class="sb-lbl">Resolved</div>
                            </div>
                            <div class="stat-box">
                                <div class="sb-num">{{ $totalEsc }}</div>
                                <div class="sb-lbl">Escalated</div>
                            </div>
                            <div class="stat-box span2">
                                <div class="sb-num">
                                    {{ $avgRating ? number_format($avgRating, 1) . ' ⭐' : 'N/A' }}
                                </div>
                                <div class="sb-lbl">Avg Customer Rating</div>
                            </div>
                        </div>
                    </div>
                </div>
            @elseif($roleName === 'Employee')
                <div class="panel-section mt-4">
                    <div class="panel-head">
                        <div class="panel-head-icon"><i class="bi bi-ticket-perforated"></i></div>
                        <div>
                            <div class="panel-head-title">My Tickets</div>
                            <div class="panel-head-sub">Lifetime summary</div>
                        </div>
                    </div>
                    <div class="panel-body" style="padding-top:16px">
                        <div class="stats-grid">
                            <div class="stat-box">
                                <div class="sb-num">{{ $totalSubmitted }}</div>
                                <div class="sb-lbl">Submitted</div>
                            </div>
                            <div class="stat-box">
                                <div class="sb-num">{{ \App\Models\Tickets::where('users_id', $user->id)->where('status', 'Resolved')->count() }}</div>
                                <div class="sb-lbl">Resolved</div>
                            </div>
                            <div class="stat-box">
                                <div class="sb-num">{{ \App\Models\Tickets::where('users_id', $user->id)->where('status', 'Open')->count() }}</div>
                                <div class="sb-lbl">Open</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- ══ RIGHT — Panels ══ --}}
        <div>

            {{-- ── Account Info ── --}}
            <div class="panel-section">
                <div class="panel-head">
                    <div class="panel-head-icon"><i class="bi bi-person-fill"></i></div>
                    <div>
                        <div class="panel-head-title">Account Information</div>
                        <div class="panel-head-sub">Your personal details on record</div>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="field-group">
                        <div class="field-wrap">
                            <label>Full Name</label>
                            <input type="text" class="form-control" value="{{ $user->name }}" readonly>
                        </div>
                        <div class="field-wrap">
                            <label>Email Address</label>
                            <input type="email" class="form-control" value="{{ $user->email }}" readonly>
                        </div>
                    </div>
                    <div class="field-group">
                        <div class="field-wrap">
                            <label>Role</label>
                            <input type="text" class="form-control" value="{{ $roleName }}" readonly>
                        </div>
                        <div class="field-wrap">
                            <label>Position</label>
                            <input type="text" class="form-control" value="{{ $user->position ?? '—' }}" readonly>
                        </div>
                    </div>
                    <div class="field-group">
                        <div class="field-wrap">
                            <label>Department</label>
                            <input type="text" class="form-control" value="{{ $user->department?->department_name ?? '—' }}" readonly>
                        </div>
                        <div class="field-wrap">
                            <label>Company</label>
                            <input type="text" class="form-control" value="{{ $user->department?->company?->company_name ?? '—' }}" readonly>
                        </div>
                    </div>
                    <div class="field-group full">
                        <div class="field-wrap">
                            <label>Business Unit</label>
                            <input type="text" class="form-control"
                                   value="{{ $user->department?->company?->businessUnit?->business_units_name ?? '—' }}" readonly>
                        </div>
                    </div>
                    <div style="padding:12px 16px;background:var(--ygl);border-radius:10px;font-size:12px;color:var(--tm);font-weight:600;display:flex;align-items:center;gap:8px">
                        <i class="bi bi-info-circle" style="color:var(--gd)"></i>
                        To update your information, please contact your IT Administrator.
                    </div>
                </div>
            </div>

            {{-- ── Change Password ── --}}
            <div class="panel-section" id="pwSection">
                <div class="panel-head">
                    <div class="panel-head-icon" style="background:var(--yg);color:var(--gd)">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>
                    <div>
                        <div class="panel-head-title">Change Password</div>
                        <div class="panel-head-sub">Keep your account secure with a strong password</div>
                    </div>
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route('profile.password') }}" id="pwForm">
                        @csrf
                        @method('PUT')

                        <div class="field-group full mb-3">
                            <div class="field-wrap">
                                <label>Current Password <span class="text-danger">*</span></label>
                                <div class="pw-wrap">
                                    <input type="password" class="form-control" name="current_password"
                                           id="currentPw" placeholder="Enter your current password"
                                           autocomplete="current-password"
                                           oninput="checkFormReady()">
                                    <button type="button" class="pw-toggle" onclick="togglePw('currentPw', this)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="field-group">
                            <div class="field-wrap">
                                <label>New Password <span class="text-danger">*</span></label>
                                <div class="pw-wrap">
                                    <input type="password" class="form-control" name="password"
                                           id="newPw" placeholder="Min. 8 characters"
                                           autocomplete="new-password"
                                           oninput="checkStrength(this.value); checkMatch(); checkFormReady()">
                                    <button type="button" class="pw-toggle" onclick="togglePw('newPw', this)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="pw-strength-bar">
                                    <div class="pw-strength-fill" id="pwFill"></div>
                                </div>
                                <div class="pw-rules">
                                    <div class="pw-rule" id="rule-len"><i class="bi bi-x-circle"></i> 8+ characters</div>
                                    <div class="pw-rule" id="rule-upper"><i class="bi bi-x-circle"></i> Uppercase letter</div>
                                    <div class="pw-rule" id="rule-num"><i class="bi bi-x-circle"></i> Number</div>
                                    <div class="pw-rule" id="rule-special"><i class="bi bi-x-circle"></i> Special character</div>
                                </div>
                            </div>
                            <div class="field-wrap">
                                <label>Confirm New Password <span class="text-danger">*</span></label>
                                <div class="pw-wrap">
                                    <input type="password" class="form-control" name="password_confirmation"
                                           id="confirmPw" placeholder="Re-enter new password"
                                           autocomplete="new-password"
                                           oninput="checkMatch(); checkFormReady()">
                                    <button type="button" class="pw-toggle" onclick="togglePw('confirmPw', this)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div id="matchMsg" style="font-size:11px;font-weight:700;margin-top:6px;min-height:18px"></div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-2">
                            <button type="submit" class="btn-change-pw" id="btnChangePw" disabled>
                                <i class="bi bi-shield-check me-1"></i>Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ── Recent Activity ── --}}
            <div class="panel-section" id="activitySection">
                <div class="panel-head">
                    <div class="panel-head-icon" style="background:#1a3c1a">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div>
                        <div class="panel-head-title">Recent Activity</div>
                        <div class="panel-head-sub">Your latest 5 ticket interactions</div>
                    </div>
                </div>
                <div class="panel-body" style="padding-top:8px;padding-bottom:8px">
                    @forelse($recentTickets as $ticket)
                        @php
                            $dotClass = match($ticket->status) {
                                'Open'        => 'open',
                                'In Progress' => 'progress',
                                'Escalated'   => 'escalated',
                                'Resolved'    => 'resolved',
                                default       => 'cancelled',
                            };
                            $dotIcon = match($ticket->status) {
                                'Open'        => 'bi-circle',
                                'In Progress' => 'bi-arrow-repeat',
                                'Escalated'   => 'bi-exclamation-triangle',
                                'Resolved'    => 'bi-check-circle',
                                default       => 'bi-x-circle',
                            };
                            $badgeBg = match($ticket->status) {
                                'Open'        => 'var(--ygl)',
                                'In Progress' => '#fff4cc',
                                'Escalated'   => '#fde8e8',
                                'Resolved'    => '#d4f0d4',
                                default       => 'var(--bd)',
                            };
                            $badgeColor = match($ticket->status) {
                                'Open'        => 'var(--gd)',
                                'In Progress' => '#7a5a00',
                                'Escalated'   => '#8b1a1a',
                                'Resolved'    => '#1a5a3a',
                                default       => 'var(--tm)',
                            };
                        @endphp
                        <div class="activity-item">
                            <div class="act-dot {{ $dotClass }}">
                                <i class="bi {{ $dotIcon }}"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="act-title">
                                    #{{ $ticket->ticket_number }} — {{ Str::limit($ticket->subject, 45) }}
                                    <span class="act-badge"
                                          style="background:{{ $badgeBg }};color:{{ $badgeColor }}">
                                        {{ $ticket->status }}
                                    </span>
                                </div>
                                <div class="act-meta">
                                    {{ $ticket->request_category }}
                                    · {{ $ticket->created_at->diffForHumans() }}
                                    @if($ticket->assignedTo && $roleName === 'Employee')
                                        · Assigned to {{ $ticket->assignedTo->name }}
                                    @elseif($ticket->user && $roleName !== 'Employee')
                                        · From {{ $ticket->user->name }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4" style="color:var(--tm)">
                            <i class="bi bi-clock-history" style="font-size:32px;opacity:.3;display:block;margin-bottom:8px"></i>
                            <p style="font-size:13px;font-weight:600;margin:0">No recent activity yet.</p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
@endsection

@section('scripts')
<script>

/* ── Toggle password visibility ── */
function togglePw(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type     = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type     = 'password';
        icon.className = 'bi bi-eye';
    }
}

/* ── Password strength ── */
function checkStrength(val) {
    const rules = {
        len:     val.length >= 8,
        upper:   /[A-Z]/.test(val),
        num:     /[0-9]/.test(val),
        special: /[^A-Za-z0-9]/.test(val),
    };
    const passed  = Object.values(rules).filter(Boolean).length;
    const colors  = ['', '#e24b4a', '#f5c842', '#f5c842', '#3fb950'];
    const widths  = ['0%', '25%', '50%', '75%', '100%'];
    const fill    = document.getElementById('pwFill');
    fill.style.width      = widths[passed];
    fill.style.background = colors[passed];

    Object.entries(rules).forEach(([key, pass]) => {
        const el   = document.getElementById('rule-' + key);
        const icon = el.querySelector('i');
        el.classList.toggle('pass', pass);
        icon.className = pass ? 'bi bi-check-circle-fill' : 'bi bi-x-circle';
    });
}

/* ── Password match ── */
function checkMatch() {
    const newPw  = document.getElementById('newPw').value;
    const confPw = document.getElementById('confirmPw').value;
    const msg    = document.getElementById('matchMsg');
    if (!confPw) { msg.textContent = ''; return; }
    if (newPw === confPw) {
        msg.innerHTML  = '✅ Passwords match';
        msg.style.color = '#3fb950';
    } else {
        msg.innerHTML  = '❌ Passwords do not match';
        msg.style.color = '#e24b4a';
    }
}

/* ── Enable submit when all valid ── */
function checkFormReady() {
    const curPw  = document.getElementById('currentPw').value.trim();
    const newPw  = document.getElementById('newPw').value;
    const confPw = document.getElementById('confirmPw').value;
    const btn    = document.getElementById('btnChangePw');
    btn.disabled = !(curPw && newPw.length >= 8 && newPw === confPw);
}

/* ── Smooth scroll for sidebar ── */
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});

</script>
@endsection
