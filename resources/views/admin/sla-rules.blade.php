@extends('layouts.admin')

@section('title', 'SLA Rules — LGICT')

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
    <h1><strong>SLA</strong> <em>RULES</em></h1>
@endsection
@section('hero-subtitle', 'Define per-subcategory response and resolution time targets.')

@section('hero-stats')
    <div class="d-flex gap-2 flex-wrap">
        <div class="stat-pill done">
            <span class="num">{{ $slaRate }}%</span>
            <span class="lbl">Compliance</span>
        </div>
        <div class="stat-pill esc">
            <span class="num">{{ $breachedAll }}</span>
            <span class="lbl">Breached</span>
        </div>
        <div class="stat-pill open">
            <span class="num">{{ $categories->count() }}</span>
            <span class="lbl">Categories</span>
        </div>
        <div class="stat-pill all">
            <span class="num">{{ $categories->sum(fn($c) => $c->rules->count()) }}</span>
            <span class="lbl">Rules</span>
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
            <li class="list-group-item">
                <a href="{{ route('admin.settings') }}" class="d-flex align-items-center gap-2 text-decoration-none w-100">
                    <i class="bi bi-building me-1"></i>Organization
                </a>
            </li>
            <li class="list-group-item active">
                <a href="{{ route('admin.sla-rules.index') }}" class="d-flex align-items-center gap-2 text-decoration-none w-100">
                    <i class="bi bi-stopwatch me-1"></i>SLA Rules
                </a>
            </li>
            <li class="list-group-item">
                <a href="{{ route('admin.audit-log') }}" class="d-flex align-items-center gap-2 text-decoration-none w-100">
                    <i class="bi bi-journal-text me-1"></i>Audit Log
                </a>
            </li>
        </ul>
    </div>

    {{-- Priority live counts --}}
    <div class="sidebar-card mb-3">
        <div class="sidebar-head dark">Live Open Tickets</div>
        <div class="p-3 d-flex flex-column gap-2">
            @foreach(['High' => ['#e24b4a','#fde8e8'], 'Medium' => ['#f5c842','#fff4cc'], 'Low' => ['#4a7c4a','#d4f0d4']] as $pri => $colors)
                <div style="background:{{ $colors[1] }};border-radius:10px;padding:10px 12px;display:flex;align-items:center;justify-content:space-between">
                    <div style="font-size:12px;font-weight:800;color:{{ $colors[0] }}">{{ $pri }}</div>
                    <div style="font-family:'Nunito',sans-serif;font-weight:900;font-size:18px;color:var(--gd)">{{ $priorityCounts[$pri] }}</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Time legend --}}
    <div class="sidebar-card">
        <div class="sidebar-head dark">Time Guide</div>
        <div class="p-3 d-flex flex-column gap-1" style="font-size:12px;color:var(--tm);font-weight:600">
            <div class="d-flex justify-content-between"><span>30 minutes</span><span class="fw-800 text-dark">= 30 min</span></div>
            <div class="d-flex justify-content-between"><span>1 hour</span><span class="fw-800 text-dark">= 60 min</span></div>
            <div class="d-flex justify-content-between"><span>2 hours</span><span class="fw-800 text-dark">= 120 min</span></div>
            <div class="d-flex justify-content-between"><span>4 hours</span><span class="fw-800 text-dark">= 240 min</span></div>
            <div class="d-flex justify-content-between"><span>8 hours</span><span class="fw-800 text-dark">= 480 min</span></div>
            <div class="d-flex justify-content-between"><span>1 day</span><span class="fw-800 text-dark">= 1440 min</span></div>
            <div class="d-flex justify-content-between"><span>3 days</span><span class="fw-800 text-dark">= 4320 min</span></div>
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
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-3">
            <i class="bi bi-exclamation-circle me-2"></i>{{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ── SLA Compliance bar ── --}}
    <div class="sla-overview mb-4">
        <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
            <div>
                <div style="font-family:'Nunito',sans-serif;font-weight:900;font-size:14px;color:var(--rd)">
                    <i class="bi bi-speedometer2 me-1"></i>SLA Compliance — {{ now()->format('F Y') }}
                </div>
                <div style="font-size:12px;color:var(--tm);font-weight:600">{{ $totalTickets }} total tickets this month</div>
            </div>
            <div class="sla-rate {{ $slaRate >= 90 ? 'good' : ($slaRate >= 70 ? 'warn' : 'bad') }}">
                {{ $slaRate }}%
            </div>
        </div>
        <div class="sla-bar-wrap">
            <div class="sla-bar-fill {{ $slaRate >= 90 ? 'good' : ($slaRate >= 70 ? 'warn' : 'bad') }}"
                 style="width:{{ $slaRate }}%"></div>
        </div>
        <div class="d-flex justify-content-between mt-1">
            <span style="font-size:11px;color:var(--tm);font-weight:600">0%</span>
            <span style="font-size:11px;color:var(--gm);font-weight:700">Target: 90%</span>
            <span style="font-size:11px;color:var(--tm);font-weight:600">100%</span>
        </div>
    </div>

    {{-- ── Two-column layout ── --}}
    <div class="sla-main-layout">

        {{-- ══ LEFT PANEL — Add Category + Add Rule ══ --}}
        <div class="sla-left">

            {{-- Add Category --}}
            <div class="sla-panel mb-4" id="catFormCard">
                <div class="sla-panel-head">
                    <div class="sph-icon" style="background:var(--rd)"><i class="bi bi-folder-plus"></i></div>
                    <div>
                        <div class="sph-title" id="catFormTitle">Add Category</div>
                        <div class="sph-sub">e.g. Hardware, Software, Network</div>
                    </div>
                </div>
                <form method="POST" id="catForm" action="{{ route('admin.sla-rules.category.store') }}">
                    @csrf
                    <input type="hidden" id="catMethod" name="_method" value="POST">

                    <div class="sf-field">
                        <label>Category Name <span class="req">*</span></label>
                        <input type="text" class="form-control" name="name" id="catName"
                               placeholder="e.g. Hardware" required>
                    </div>

                    <div class="field-row">
                        <div class="sf-field flex-1">
                            <label>Icon <span style="font-weight:600;font-size:10px;color:var(--tm)">(Bootstrap icon)</span></label>
                            <div style="position:relative">
                                <input type="text" class="form-control" name="icon" id="catIcon"
                                       placeholder="bi-laptop" oninput="previewIcon(this.value)">
                                <span id="iconPreview" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);font-size:18px;color:var(--gd)"></span>
                            </div>
                        </div>
                        <div class="sf-field" style="width:80px">
                            <label>Color</label>
                            <input type="color" class="form-control" name="color" id="catColor"
                                   value="#1a4a8a" style="height:42px;padding:4px 8px;cursor:pointer">
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-2">
                        <button type="submit" class="btn-save"><i class="bi bi-check-lg me-1"></i><span id="catSaveText">Add Category</span></button>
                        <button type="button" class="btn-cancel d-none" id="catCancelBtn" onclick="resetCatForm()">Cancel</button>
                    </div>
                </form>
            </div>

            {{-- Add SLA Rule --}}
            <div class="sla-panel" id="ruleFormCard">
                <div class="sla-panel-head">
                    <div class="sph-icon" style="background:var(--gd)"><i class="bi bi-stopwatch"></i></div>
                    <div>
                        <div class="sph-title" id="ruleFormTitle">Add SLA Rule</div>
                        <div class="sph-sub">Assign to a subcategory</div>
                    </div>
                </div>
                <form method="POST" id="ruleForm" action="{{ route('admin.sla-rules.rule.store') }}">
                    @csrf
                    <input type="hidden" id="ruleMethod" name="_method" value="POST">

                    {{-- Category --}}
                    <div class="sf-field">
                        <label>Category <span class="req">*</span></label>
                        <select class="form-select" name="sla_category_id" id="ruleCategoryId" required
                                onchange="updateSubcategoryHint()">
                            <option value="">— Select Category —</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Subcategory --}}
                    <div class="sf-field">
                        <label>Subcategory Name <span class="req">*</span></label>
                        <input type="text" class="form-control" name="subcategory_name" id="ruleSubcat"
                               placeholder="e.g. Laptop" required list="subcatSuggestions">
                        <datalist id="subcatSuggestions"></datalist>
                        <div style="font-size:11px;color:var(--tm);font-weight:600;margin-top:4px" id="subcatHint"></div>
                    </div>

                    {{-- Priority --}}
                    <div class="sf-field">
                        <label>Priority <span class="req">*</span></label>
                        <div class="d-flex gap-2">
                            @foreach(['High' => '#e24b4a', 'Medium' => '#f5c842', 'Low' => '#4a7c4a'] as $pri => $color)
                                <div class="pri-chip {{ strtolower($pri) }}" data-val="{{ $pri }}"
                                     onclick="pickPriority('{{ $pri }}', this)">
                                    <span class="pc-dot" style="background:{{ $color }}"></span>{{ $pri }}
                                </div>
                            @endforeach
                        </div>
                        <input type="hidden" name="priority" id="rulePriority" required>
                    </div>

                    {{-- Response time --}}
                    <div class="sf-field">
                        <label>First Response Time <span class="req">*</span></label>
                        <div class="time-wrap">
                            <input type="number" class="form-control" name="response_time_minutes"
                                   id="ruleResponse" min="5" max="43200" step="5"
                                   placeholder="30" required oninput="syncTimeUnit('ruleResponse', 'ruleResponseUnit'); updateRulePreview()">
                            <span class="time-unit-badge" id="ruleResponseUnit">30 mins</span>
                        </div>
                        <div class="quick-wrap">
                            @foreach([30=>'30m', 60=>'1h', 120=>'2h', 240=>'4h', 480=>'8h', 1440=>'1d'] as $mins => $label)
                                <button type="button" class="qbtn" onclick="setTimeMins('ruleResponse', {{ $mins }}, 'ruleResponseUnit', this)">{{ $label }}</button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Resolution time --}}
                    <div class="sf-field">
                        <label>Resolution Time <span class="req">*</span></label>
                        <div class="time-wrap">
                            <input type="number" class="form-control" name="resolution_time_minutes"
                                   id="ruleResolution" min="5" max="43200" step="5"
                                   placeholder="240" required oninput="syncTimeUnit('ruleResolution', 'ruleResolutionUnit'); updateRulePreview()">
                            <span class="time-unit-badge" id="ruleResolutionUnit">4 hrs</span>
                        </div>
                        <div class="quick-wrap">
                            @foreach([60=>'1h', 240=>'4h', 480=>'8h', 1440=>'1d', 2880=>'2d', 4320=>'3d', 10080=>'1wk'] as $mins => $label)
                                <button type="button" class="qbtn" onclick="setTimeMins('ruleResolution', {{ $mins }}, 'ruleResolutionUnit', this)">{{ $label }}</button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Description --}}
                    <div class="sf-field">
                        <label>Description <span style="font-weight:600;font-size:10px;color:var(--tm)">(optional)</span></label>
                        <textarea class="form-control" name="description" id="ruleDescription"
                                  rows="2" placeholder="Notes…"></textarea>
                    </div>

                    {{-- Preview --}}
                    <div class="rule-preview" id="rulePreview">
                        <div style="font-size:10px;font-weight:800;color:var(--tm);text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px">Preview</div>
                        <div class="d-flex gap-2 flex-wrap">
                            <span class="prev-chip blue"><i class="bi bi-bell me-1"></i>Response: <strong id="prevResp">—</strong></span>
                            <span class="prev-chip green"><i class="bi bi-check-circle me-1"></i>Resolve: <strong id="prevRes">—</strong></span>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn-save"><i class="bi bi-check-lg me-1"></i><span id="ruleSaveText">Add Rule</span></button>
                        <button type="button" class="btn-cancel d-none" id="ruleCancelBtn" onclick="resetRuleForm()">Cancel</button>
                    </div>
                </form>
            </div>

        </div>

        {{-- ══ RIGHT PANEL — Categories & Rules ══ --}}
        <div class="sla-right">

            @if($categories->isEmpty())
                <div class="sla-empty-state">
                    <div style="font-size:48px;opacity:.25">⏱️</div>
                    <div class="font-brand fw-900 mt-3" style="font-size:18px;color:var(--tm)">No categories yet</div>
                    <div style="font-size:13px;color:var(--tm);margin-top:4px">
                        Start by adding a category (e.g. Hardware) on the left.
                    </div>
                </div>
            @else
                @foreach($categories as $category)
                    @php
                        $totalRules  = $category->rules->count();
                        $activeRules = $category->rules->where('is_active', true)->count();
                    @endphp

                    <div class="cat-block mb-4" id="cat-{{ $category->id }}">

                        {{-- Category header --}}
                        <div class="cat-header" style="border-left:4px solid {{ $category->color ?? '#1a4a8a' }}">
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <div class="cat-icon-wrap" style="background:{{ $category->color ?? '#1a4a8a' }}20;color:{{ $category->color ?? '#1a4a8a' }}">
                                    <i class="bi {{ $category->icon ?? 'bi-tag' }}"></i>
                                </div>
                                <div>
                                    <div class="cat-name">{{ $category->name }}</div>
                                    <div class="cat-meta">
                                        {{ $totalRules }} {{ Str::plural('rule', $totalRules) }}
                                        · {{ $activeRules }} active
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex gap-2 align-items-center">
                                <button class="btn-cat-action edit"
                                        onclick="editCategory('{{ $category->id }}','{{ addslashes($category->name) }}','{{ $category->icon }}','{{ $category->color }}')"
                                        title="Edit category">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" action="{{ route('admin.sla-rules.category.destroy', $category) }}"
                                      onsubmit="return confirm('Delete category \'{{ $category->name }}\' and ALL its SLA rules?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-cat-action del" title="Delete category">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                <button class="btn-cat-add"
                                        onclick="quickAddRule('{{ $category->id }}','{{ addslashes($category->name) }}')"
                                        title="Add rule to this category">
                                    <i class="bi bi-plus-lg me-1"></i>Add Rule
                                </button>
                            </div>
                        </div>

                        {{-- Rules table --}}
                        @if($category->rules->isEmpty())
                            <div class="cat-empty">
                                <i class="bi bi-stopwatch me-2"></i>
                                No SLA rules yet.
                                <button class="btn-link-add"
                                        onclick="quickAddRule('{{ $category->id }}', '{{ addslashes($category->name) }}')">
                                    Add the first rule →
                                </button>
                            </div>
                        @else
                            <div class="rules-table-wrap">
                                <table class="rules-table">
                                    <thead>
                                        <tr>
                                            <th>Subcategory</th>
                                            <th>Priority</th>
                                            <th>First Response</th>
                                            <th>Resolution</th>
                                            <th>Status</th>
                                            <th style="text-align:right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($category->rules as $rule)
                                            @php
                                                $priColor = match($rule->priority) { 'High' => '#e24b4a', 'Medium' => '#f5c842', 'Low' => '#4a7c4a', default => 'var(--tm)' };
                                                $priBg    = match($rule->priority) { 'High' => '#fde8e8', 'Medium' => '#fff4cc', 'Low' => '#d4f0d4', default => 'var(--bd)' };
                                            @endphp
                                            <tr class="{{ !$rule->is_active ? 'row-inactive' : '' }}" id="rule-row-{{ $rule->id }}">
                                                <td>
                                                    <div class="sub-name">{{ $rule->subcategory_name }}</div>
                                                    @if($rule->description)
                                                        <div class="sub-desc">{{ Str::limit($rule->description, 40) }}</div>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="pri-badge" style="background:{{ $priBg }};color:{{ $priColor }}">
                                                        ● {{ $rule->priority }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="time-badge resp">
                                                        <i class="bi bi-bell me-1"></i>
                                                        {{ \App\Models\SlaRule::formatMinutes($rule->response_time_minutes) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="time-badge res">
                                                        <i class="bi bi-check-circle me-1"></i>
                                                        {{ \App\Models\SlaRule::formatMinutes($rule->resolution_time_minutes) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <form method="POST" action="{{ route('admin.sla-rules.rule.toggle', $rule) }}">
                                                        @csrf @method('PATCH')
                                                        <button type="submit" class="toggle-btn {{ $rule->is_active ? 'active' : 'inactive' }}">
                                                            {{ $rule->is_active ? '● Active' : '○ Inactive' }}
                                                        </button>
                                                    </form>
                                                </td>
                                                <td style="text-align:right">
                                                    <div class="d-flex gap-1 justify-content-end">
                                                        <button class="btn-row-action edit"
                                                                onclick="editRule(
                                                                    '{{ $rule->id }}',
                                                                    '{{ $rule->sla_category_id }}',
                                                                    '{{ addslashes($rule->subcategory_name) }}',
                                                                    '{{ $rule->priority }}',
                                                                    {{ $rule->response_time_minutes }},
                                                                    {{ $rule->resolution_time_minutes }},
                                                                    '{{ addslashes($rule->description ?? '') }}'
                                                                )"
                                                                title="Edit">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <form method="POST" action="{{ route('admin.sla-rules.rule.destroy', $rule) }}"
                                                              onsubmit="return confirm('Delete this SLA rule?')">
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
                @endforeach
            @endif

        </div>

    </div>

@endsection

@section('styles')
    /* ── SLA Overview ── */
    .sla-overview { background:var(--cr); border:1.5px solid var(--bd); border-radius:20px; padding:20px 24px; }
    .sla-rate { font-family:'Nunito',sans-serif; font-weight:900; font-size:26px; padding:5px 18px; border-radius:50px; }
    .sla-rate.good { background:#d4f0d4; color:#1a5a3a; }
    .sla-rate.warn { background:#fff4cc; color:#7a5a00; }
    .sla-rate.bad  { background:#fde8e8; color:#8b1a1a; }
    .sla-bar-wrap { height:10px; background:var(--bd); border-radius:10px; overflow:hidden; }
    .sla-bar-fill { height:10px; border-radius:10px; transition:width .6s ease; }
    .sla-bar-fill.good { background:linear-gradient(90deg,#3fb950,#c8e63c); }
    .sla-bar-fill.warn { background:linear-gradient(90deg,#f5c842,#d29922); }
    .sla-bar-fill.bad  { background:linear-gradient(90deg,#f85149,#d29922); }

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
    .field-row { display:flex; gap:10px; }
    .flex-1 { flex:1; }

    /* ── Priority chips ── */
    .pri-chip { flex:1; padding:7px 6px; border:1.5px solid var(--bd); border-radius:10px; text-align:center; cursor:pointer; font-size:12px; font-weight:800; font-family:'Nunito',sans-serif; display:flex; align-items:center; justify-content:center; gap:6px; color:var(--tm); background:var(--cr); transition:all .2s; }
    .pri-chip:hover { border-color:var(--gl); background:var(--ygl); }
    .pc-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
    .pri-chip.selected.high   { background:#fde8e8; border-color:#e24b4a; color:#8b1a1a; }
    .pri-chip.selected.medium { background:#fff4cc; border-color:#f5c842; color:#7a5a00; }
    .pri-chip.selected.low    { background:#d4f0d4; border-color:#4a7c4a; color:#1a5a3a; }

    /* ── Time input ── */
    .time-wrap { position:relative; display:flex; align-items:center; gap:8px; }
    .time-wrap .form-control { flex:1; }
    .time-unit-badge { font-size:11px; font-weight:800; background:var(--ygl); color:var(--gd); border-radius:20px; padding:3px 10px; white-space:nowrap; flex-shrink:0; }
    .quick-wrap { display:flex; gap:4px; flex-wrap:wrap; margin-top:6px; }
    .qbtn { background:var(--ygl); border:1.5px solid var(--bd); border-radius:20px; padding:3px 10px; font-size:11px; font-weight:800; color:var(--gd); cursor:pointer; transition:all .2s; font-family:'Nunito',sans-serif; }
    .qbtn:hover { background:var(--yg); border-color:var(--yg); }
    .qbtn.active { background:var(--gd); color:var(--yg); border-color:var(--gd); }

    /* ── Preview ── */
    .rule-preview { background:var(--ygl); border-radius:12px; padding:10px 14px; }
    .prev-chip { font-size:12px; font-weight:700; padding:4px 12px; border-radius:20px; display:inline-flex; align-items:center; }
    .prev-chip.blue  { background:rgba(88,166,255,.12); color:#1a4a8a; }
    .prev-chip.green { background:rgba(63,185,80,.12); color:#1a5a3a; }

    /* ── Buttons ── */
    .btn-save { background:var(--gd); color:var(--yg); font-family:'Nunito',sans-serif; font-weight:900; font-size:13px; padding:9px 22px; border-radius:50px; border:none; cursor:pointer; transition:all .2s; display:inline-flex; align-items:center; gap:5px; }
    .btn-save:hover { background:var(--gm); }
    .btn-cancel { background:none; border:1.5px solid var(--bd); color:var(--tm); font-family:'Nunito',sans-serif; font-weight:800; font-size:13px; padding:9px 18px; border-radius:50px; cursor:pointer; transition:all .2s; }
    .btn-cancel:hover { border-color:var(--rd); color:var(--rd); }

    /* ── Category block ── */
    .cat-block { background:var(--cr); border:1.5px solid var(--bd); border-radius:20px; overflow:hidden; }
    .cat-header { background:var(--cr); padding:16px 20px; border-bottom:1.5px solid var(--bd); display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
    .cat-icon-wrap { width:38px; height:38px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; }
    .cat-name { font-family:'Nunito',sans-serif; font-weight:900; font-size:16px; color:var(--gd); }
    .cat-meta { font-size:12px; color:var(--tm); font-weight:600; margin-top:2px; }

    .btn-cat-action { width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:12px; cursor:pointer; border:1.5px solid; transition:all .2s; }
    .btn-cat-action.edit { background:var(--ygl); color:var(--gd); border-color:var(--bd); }
    .btn-cat-action.edit:hover { background:var(--yg); }
    .btn-cat-action.del  { background:#fde8e8; color:var(--rd); border-color:#f0c0c0; }
    .btn-cat-action.del:hover  { background:#f8c8c8; }
    .btn-cat-add { background:var(--gd); color:var(--yg); font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; padding:6px 14px; border-radius:20px; border:none; cursor:pointer; transition:background .2s; display:inline-flex; align-items:center; }
    .btn-cat-add:hover { background:var(--gm); }

    .cat-empty { padding:20px; text-align:center; font-size:13px; color:var(--tm); font-weight:600; }
    .btn-link-add { background:none; border:none; color:var(--gd); font-weight:800; cursor:pointer; font-size:13px; text-decoration:underline; }

    /* ── Rules table ── */
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
    .sub-desc { font-size:11px; color:var(--tm); font-weight:600; margin-top:2px; }

    .pri-badge { font-size:11px; font-weight:800; border-radius:20px; padding:3px 10px; font-family:'Nunito',sans-serif; white-space:nowrap; }
    .time-badge { font-size:12px; font-weight:700; border-radius:20px; padding:3px 10px; display:inline-flex; align-items:center; white-space:nowrap; }
    .time-badge.resp { background:#e8eeff; color:#2a4ab0; }
    .time-badge.res  { background:#d4f0d4; color:#1a5a3a; }

    .toggle-btn { font-size:11px; font-weight:800; border-radius:20px; padding:3px 10px; border:none; cursor:pointer; font-family:'Nunito',sans-serif; transition:all .2s; }
    .toggle-btn.active   { background:#d4f0d4; color:#1a5a3a; }
    .toggle-btn.inactive { background:var(--bd); color:var(--tm); }
    .toggle-btn:hover { opacity:.8; }

    .btn-row-action { width:28px; height:28px; border-radius:7px; display:flex; align-items:center; justify-content:center; font-size:12px; cursor:pointer; border:1.5px solid; transition:all .2s; }
    .btn-row-action.edit { background:var(--ygl); color:var(--gd); border-color:var(--bd); }
    .btn-row-action.edit:hover { background:var(--yg); }
    .btn-row-action.del  { background:#fde8e8; color:var(--rd); border-color:#f0c0c0; }
    .btn-row-action.del:hover  { background:#f8c8c8; }

    /* ── Empty state ── */
    .sla-empty-state { text-align:center; padding:60px 20px; background:var(--cr); border:1.5px solid var(--bd); border-radius:20px; }
@endsection

@section('scripts')
<script>

/* ── Format minutes to readable string ── */
function fmtMins(m) {
    if (!m || m <= 0) return '—';
    m = parseFloat(m);
    if (m < 60)   return m + ' min' + (m !== 1 ? 's' : '');
    const h = m / 60;
    if (h < 24) {
        const hh = Math.floor(h), mm = Math.round((h - hh) * 60);
        return hh + 'h' + (mm > 0 ? ' ' + mm + 'm' : '');
    }
    const d = h / 24, dd = Math.floor(d), hh2 = Math.round((d - dd) * 24);
    return dd + 'd' + (hh2 > 0 ? ' ' + hh2 + 'h' : '');
}

/* ── Sync time unit badge ── */
function syncTimeUnit(inputId, badgeId) {
    const val   = parseFloat(document.getElementById(inputId).value) || 0;
    const badge = document.getElementById(badgeId);
    badge.textContent = fmtMins(val) || '—';
}

/* ── Set quick time ── */
function setTimeMins(inputId, mins, badgeId, btn) {
    document.getElementById(inputId).value = mins;
    syncTimeUnit(inputId, badgeId);
    // Clear other active in same group
    btn.closest('.quick-wrap').querySelectorAll('.qbtn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    updateRulePreview();
}

/* ── Update rule preview ── */
function updateRulePreview() {
    const resp = document.getElementById('ruleResponse').value;
    const res  = document.getElementById('ruleResolution').value;
    document.getElementById('prevResp').textContent = fmtMins(resp);
    document.getElementById('prevRes').textContent  = fmtMins(res);
}

/* ── Priority pick ── */
function pickPriority(val, el) {
    document.querySelectorAll('#ruleFormCard .pri-chip').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('rulePriority').value = val;
}

/* ── Preview icon ── */
function previewIcon(val) {
    const el = document.getElementById('iconPreview');
    el.className = 'bi ' + (val || '');
}

/* ── Subcategory suggestions based on selected category ── */
const subcatMap = {!! json_encode($categories->pluck('name', 'id')->toArray()) !!};
const builtinSubs = {
    'Hardware':       ['Laptop','Desktop','Monitors','Printers','Scanner','HDD','Flash drive','Servers','Keyboards','Projector','Speaker'],
    'Software':       ['OS upgrade','Software installation / update','ERP Vendor Request','ERP Error','App Troubleshooting','File Management'],
    'Network':        ['Network Device Configuration','Project - cabling setup','Single cabling setup','Shared file cannot be accessed'],
    'Access Request': ['BC License request','Access to shared folder','Email creation','Remote access request','WiFi access'],
};

function updateSubcategoryHint() {
    const sel    = document.getElementById('ruleCategoryId');
    const catName = sel.options[sel.selectedIndex]?.text || '';
    const hint   = document.getElementById('subcatHint');
    const dl     = document.getElementById('subcatSuggestions');

    // Clear datalist
    dl.innerHTML = '';

    const subs = builtinSubs[catName] || [];
    subs.forEach(s => {
        const opt = document.createElement('option');
        opt.value = s;
        dl.appendChild(opt);
    });

    hint.textContent = subs.length ? `${subs.length} suggestions available ↑` : '';
}

/* ── Quick-add rule: pre-select category ── */
function quickAddRule(catId, catName) {
    document.getElementById('ruleCategoryId').value = catId;
    updateSubcategoryHint();
    document.getElementById('ruleFormCard').scrollIntoView({ behavior:'smooth', block:'start' });
    document.getElementById('ruleSubcat').focus();
}

/* ── Edit category ── */
function editCategory(id, name, icon, color) {
    document.getElementById('catForm').action    = `/admin/sla-rules/categories/${id}`;
    document.getElementById('catMethod').value   = 'PUT';
    document.getElementById('catName').value     = name;
    document.getElementById('catIcon').value     = icon || '';
    document.getElementById('catColor').value    = color || '#1a4a8a';
    previewIcon(icon);
    document.getElementById('catFormTitle').textContent = 'Edit Category';
    document.getElementById('catSaveText').textContent  = 'Update';
    document.getElementById('catCancelBtn').classList.remove('d-none');
    document.getElementById('catFormCard').classList.add('editing');
    document.getElementById('catFormCard').scrollIntoView({ behavior:'smooth', block:'start' });
    document.getElementById('catName').focus();
}

function resetCatForm() {
    document.getElementById('catForm').action           = '{{ route("admin.sla-rules.category.store") }}';
    document.getElementById('catMethod').value          = 'POST';
    document.getElementById('catName').value            = '';
    document.getElementById('catIcon').value            = '';
    document.getElementById('catColor').value           = '#1a4a8a';
    document.getElementById('catFormTitle').textContent = 'Add Category';
    document.getElementById('catSaveText').textContent  = 'Add Category';
    document.getElementById('catCancelBtn').classList.add('d-none');
    document.getElementById('catFormCard').classList.remove('editing');
    document.getElementById('iconPreview').className    = '';
}

/* ── Edit rule ── */
function editRule(id, catId, subcat, priority, respMins, resMins, desc) {
    document.getElementById('ruleForm').action          = `/admin/sla-rules/rules/${id}`;
    document.getElementById('ruleMethod').value         = 'PUT';
    document.getElementById('ruleCategoryId').value     = catId;
    document.getElementById('ruleSubcat').value         = subcat;
    document.getElementById('ruleResponse').value       = respMins;
    document.getElementById('ruleResolution').value     = resMins;
    document.getElementById('ruleDescription').value    = desc;

    syncTimeUnit('ruleResponse',   'ruleResponseUnit');
    syncTimeUnit('ruleResolution', 'ruleResolutionUnit');

    // Pick priority
    document.querySelectorAll('#ruleFormCard .pri-chip').forEach(c => c.classList.remove('selected'));
    const pc = document.querySelector(`#ruleFormCard .pri-chip[data-val="${priority}"]`);
    if (pc) pc.classList.add('selected');
    document.getElementById('rulePriority').value = priority;

    updateSubcategoryHint();
    updateRulePreview();

    document.getElementById('ruleFormTitle').textContent = 'Edit SLA Rule';
    document.getElementById('ruleSaveText').textContent  = 'Update Rule';
    document.getElementById('ruleCancelBtn').classList.remove('d-none');
    document.getElementById('ruleFormCard').classList.add('editing');

    // Clear quick buttons
    document.querySelectorAll('.qbtn').forEach(b => b.classList.remove('active'));

    document.getElementById('ruleFormCard').scrollIntoView({ behavior:'smooth', block:'start' });
}

function resetRuleForm() {
    document.getElementById('ruleForm').action          = '{{ route("admin.sla-rules.rule.store") }}';
    document.getElementById('ruleMethod').value         = 'POST';
    document.getElementById('ruleCategoryId').value     = '';
    document.getElementById('ruleSubcat').value         = '';
    document.getElementById('ruleResponse').value       = '';
    document.getElementById('ruleResolution').value     = '';
    document.getElementById('ruleDescription').value    = '';
    document.getElementById('rulePriority').value       = '';
    document.querySelectorAll('#ruleFormCard .pri-chip').forEach(c => c.classList.remove('selected'));
    document.getElementById('ruleFormTitle').textContent = 'Add SLA Rule';
    document.getElementById('ruleSaveText').textContent  = 'Add Rule';
    document.getElementById('ruleCancelBtn').classList.add('d-none');
    document.getElementById('ruleFormCard').classList.remove('editing');
    document.getElementById('ruleResponseUnit').textContent   = '—';
    document.getElementById('ruleResolutionUnit').textContent = '—';
    document.getElementById('prevResp').textContent           = '—';
    document.getElementById('prevRes').textContent            = '—';
    document.querySelectorAll('.qbtn').forEach(b => b.classList.remove('active'));
    document.getElementById('subcatHint').textContent = '';
}

</script>
@endsection