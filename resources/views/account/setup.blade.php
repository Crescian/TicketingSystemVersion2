<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Finish Setting Up Your Account — LGICT Support</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;800;900&family=Nunito+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    @php
        $faviconV = file_exists(public_path('favicon.ico')) ? filemtime(public_path('favicon.ico')) : 1;
        $doneCount = collect($steps)->where('done', true)->count();
        $totalSteps = count($steps);
        $pct = (int) round($doneCount / $totalSteps * 100);
    @endphp
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v={{ $faviconV }}">

    <style>
        :root {
            --gd: #1a3c1a; --gm: #2d5a2d; --gl: #4a7c4a;
            --yg: #c8e63c; --ygd: #a8c42c; --ygl: #e8f5b0;
            --cr: #f5f0e8; --bd: #e2ddd4; --tm: #5a7a5a;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; }
        body {
            font-family: 'Nunito Sans', sans-serif;
            color: var(--gd);
            height: 100vh;
            background: var(--gd);
            background-image:
                radial-gradient(circle at 15% 10%, rgba(200, 230, 60, .10) 0%, transparent 45%),
                radial-gradient(circle at 90% 85%, rgba(74, 124, 74, .55) 0%, transparent 50%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow: hidden;
        }

        .setup-shell { width: 100%; max-width: 1180px; height: 100%; max-height: 780px; }
        .setup-card {
            background: var(--cr); border-radius: 26px; overflow: hidden;
            box-shadow: 0 24px 60px rgba(0,0,0,.28);
            display: flex; height: 100%;
        }

        /* ── Left: overview sidebar ── */
        .setup-left {
            width: 340px; flex-shrink: 0; color: #fff;
            background: linear-gradient(160deg, var(--gd), var(--gm) 70%);
            padding: 30px 28px; display: flex; flex-direction: column;
            overflow-y: auto;
        }
        .brand { display: flex; align-items: center; gap: 9px; color: var(--yg); font-family: 'Nunito', sans-serif; font-weight: 900; font-size: 14px; letter-spacing: .3px; margin-bottom: 26px; }
        .brand i { font-size: 18px; }

        .setup-left h1 { font-family: 'Nunito', sans-serif; font-weight: 900; font-size: 20px; color: #fff; margin-bottom: 8px; line-height: 1.25; }
        .setup-left .lede { font-size: 12.5px; color: rgba(255,255,255,.68); font-weight: 600; line-height: 1.5; margin-bottom: 20px; }

        .progress-track { height: 7px; border-radius: 20px; background: rgba(255,255,255,.15); overflow: hidden; }
        .progress-fill { height: 100%; background: var(--yg); border-radius: 20px; transition: width .4s ease; }
        .progress-label { margin-top: 8px; margin-bottom: 24px; font-size: 11px; font-weight: 800; color: var(--yg); text-transform: uppercase; letter-spacing: .5px; }

        .sb-steps-wrap { flex: 1; display: flex; flex-direction: column; justify-content: center; min-height: 0; }
        .sb-steps { display: flex; flex-direction: column; }
        .sb-row { display: flex; align-items: flex-start; gap: 11px; position: relative; padding-bottom: 15px; }
        .sb-row:last-child { padding-bottom: 0; }
        .sb-row::before { content: ''; position: absolute; left: 12px; top: 26px; bottom: -2px; width: 2px; background: rgba(255,255,255,.14); }
        .sb-row:last-child::before { display: none; }
        .sb-row.is-done::before { background: rgba(200,230,60,.45); }

        .sb-marker {
            width: 25px; height: 25px; border-radius: 50%; flex-shrink: 0; position: relative;
            display: flex; align-items: center; justify-content: center; font-size: 10.5px;
            border: 2px solid rgba(255,255,255,.22); background: rgba(255,255,255,.06); color: rgba(255,255,255,.55);
        }
        .sb-row.is-done .sb-marker { background: var(--yg); border-color: var(--yg); color: var(--gd); }
        .sb-row.is-current .sb-marker { background: #fff; border-color: #fff; color: var(--gd); box-shadow: 0 0 0 4px rgba(255,255,255,.14); }
        .sb-num { position: absolute; top: -4px; right: -5px; width: 14px; height: 14px; border-radius: 50%; background: var(--gd); color: #fff; font-size: 7.5px; font-weight: 800; display: flex; align-items: center; justify-content: center; border: 2px solid var(--gm); }

        .sb-main { flex: 1; padding-top: 3px; min-width: 0; }
        .sb-title { font-size: 12px; font-weight: 800; color: rgba(255,255,255,.5); display: block; }
        .sb-row.is-current .sb-title, .sb-row.is-done .sb-title { color: #fff; }
        .sb-badge { font-size: 8.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .3px; }
        .sb-row.is-done .sb-badge { color: var(--yg); }
        .sb-row.is-current .sb-badge { color: var(--yg); }
        .sb-row.is-pending .sb-badge { color: rgba(255,255,255,.35); }

        .logout-link { color: rgba(255,255,255,.55); font-size: 12px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; background: none; border: none; cursor: pointer; padding-top: 16px; border-top: 1px solid rgba(255,255,255,.12); }
        .logout-link:hover { color: #fff; }

        /* ── Right: active step content ── */
        .setup-right { flex: 1; display: flex; flex-direction: column; justify-content: center; padding: 32px 56px; overflow-y: auto; min-width: 0; }
        .right-inner { width: 100%; max-width: 620px; margin: 0 auto; }

        /* ── Note ── */
        .note-box {
            padding: 14px 18px; background: var(--ygl); margin-bottom: 18px;
            border-radius: 14px; display: flex; gap: 12px; align-items: flex-start;
        }
        .note-box i { color: var(--gd); font-size: 18px; margin-top: 1px; flex-shrink: 0; }
        .note-box .note-title { font-size: 14px; font-weight: 800; color: var(--gd); margin-bottom: 2px; }
        .note-box .note-text { font-size: 13.5px; color: #4a5a3f; font-weight: 600; line-height: 1.5; }
        .note-box.error-box { background: #fde8e8; }
        .note-box.error-box i { color: #8b1a1a; }
        .note-box.error-box .note-title, .note-box.error-box .note-text { color: #8b1a1a; }
        .note-box.warn-box { background: #fff4cc; }
        .note-box.warn-box i { color: #8a6d00; }
        .note-box.warn-box .note-title, .note-box.warn-box .note-text { color: #5a4700; }

        /* ── Action panel ── */
        .action-panel-head { display: flex; align-items: center; gap: 12px; margin-bottom: 18px; }
        .action-panel-icon { width: 40px; height: 40px; background: var(--gd); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--yg); font-size: 17px; flex-shrink: 0; }
        .action-panel-title { font-family: 'Nunito', sans-serif; font-weight: 900; font-size: 18px; color: var(--gd); }
        .action-panel-sub { font-size: 13px; color: var(--tm); font-weight: 600; }
        .panel-eyebrow { font-size: 11px; font-weight: 800; color: var(--gm); text-transform: uppercase; letter-spacing: .6px; margin-bottom: 3px; }

        .order-guide {
            display: flex; align-items: center; flex-wrap: wrap; gap: 8px;
            background: #fff; border: 1.5px solid var(--bd); border-radius: 12px;
            padding: 11px 16px; margin-bottom: 18px; font-size: 13px; font-weight: 700; color: var(--tm);
        }
        .order-guide .og-lead { font-weight: 800; color: var(--gd); margin-right: 2px; }
        .order-guide .og-step { display: inline-flex; align-items: center; gap: 5px; }
        .order-guide .og-num { width: 17px; height: 17px; border-radius: 50%; background: var(--gd); color: #fff; font-size: 9.5px; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; }
        .order-guide i.bi-arrow-right { font-size: 11px; color: var(--bd); }

        .field-group { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
        .field-group.full { grid-template-columns: 1fr; }
        .field-wrap label { font-size: 12px; font-weight: 800; color: var(--tm); text-transform: uppercase; letter-spacing: .4px; margin-bottom: 7px; display: flex; align-items: center; gap: 6px; }
        .field-wrap label .lbl-num { width: 16px; height: 16px; border-radius: 50%; background: var(--gd); color: #fff; font-size: 9px; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .field-wrap .form-control, .field-wrap .form-select { border: 1.5px solid var(--bd); border-radius: 10px; padding: 11px 14px; font-size: 14.5px; font-weight: 600; color: var(--gd); background: #fff; }
        .field-wrap .form-control:focus, .field-wrap .form-select:focus { border-color: var(--gl); box-shadow: none; }
        .field-wrap .form-select:disabled, .field-wrap .form-control:disabled { background: var(--ygl); color: var(--tm); cursor: not-allowed; }
        .field-hint { font-size: 12px; color: var(--tm); font-weight: 600; margin-top: 5px; display: flex; align-items: center; gap: 5px; }
        .field-hint i { font-size: 11px; }

        .contact-note {
            display: flex; align-items: flex-start; gap: 9px; font-size: 13px; font-weight: 600; color: var(--tm);
            line-height: 1.5; margin-bottom: 18px; padding: 11px 14px; background: #fff;
            border: 1.5px dashed var(--bd); border-radius: 10px;
        }
        .contact-note i { margin-top: 2px; flex-shrink: 0; font-size: 14px; }
        .contact-note a { color: var(--gm); font-weight: 800; }

        .pw-wrap { position: relative; }
        .pw-wrap .form-control { padding-right: 44px; }
        .pw-toggle { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--tm); cursor: pointer; padding: 0; font-size: 16px; line-height: 1; }
        .pw-toggle:hover { color: var(--gd); }
        .pw-strength-bar { height: 5px; border-radius: 4px; background: var(--bd); margin-top: 7px; overflow: hidden; }
        .pw-strength-fill { height: 5px; border-radius: 4px; transition: width .3s, background .3s; width: 0%; }
        .pw-rules { display: grid; grid-template-columns: 1fr 1fr; gap: 5px; margin-top: 9px; }
        .pw-rule { font-size: 12px; font-weight: 600; color: var(--tm); display: flex; align-items: center; gap: 5px; }
        .pw-rule.pass { color: var(--gm); }
        .pw-rule i { font-size: 10.5px; }

        .btn-panel { background: var(--yg); color: var(--gd); font-family: 'Nunito', sans-serif; font-weight: 900; font-size: 15px; padding: 12px 28px; border-radius: 50px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 7px; }
        .btn-panel:hover { background: var(--ygd); }
        .btn-panel:disabled { opacity: .5; cursor: not-allowed; }

        /* ── Completion state ── */
        .complete-box { text-align: center; padding: 10px 8px 4px; }
        .complete-icon { width: 68px; height: 68px; border-radius: 50%; background: var(--gm); color: #fff; font-size: 30px; display: flex; align-items: center; justify-content: center; margin: 0 auto 18px; }
        .complete-box h2 { font-family: 'Nunito', sans-serif; font-weight: 900; font-size: 22px; color: var(--gd); margin-bottom: 10px; }
        .complete-box p { font-size: 14.5px; color: var(--tm); font-weight: 600; margin-bottom: 24px; }
        .btn-dashboard {
            display: inline-flex; align-items: center; gap: 8px;
            background: var(--yg); color: var(--gd); font-family: 'Nunito', sans-serif; font-weight: 900;
            font-size: 15px; padding: 13px 30px; border-radius: 50px; text-decoration: none;
        }
        .btn-dashboard:hover { background: var(--ygd); color: var(--gd); }

        /* ── Small-screen fallback: stack, allow scroll ── */
        @media (max-width: 860px), (max-height: 700px) {
            body { height: auto; min-height: 100vh; overflow: auto; padding: 16px; align-items: flex-start; }
            .setup-shell { max-height: none; }
            .setup-card { flex-direction: column; height: auto; }
            .setup-left { width: 100%; overflow-y: visible; }
            .setup-right { overflow-y: visible; padding: 26px 24px; }
        }
    </style>
</head>
<body>
    <div class="setup-shell">
        <div class="setup-card">

            {{-- ══ LEFT — overview sidebar ══ --}}
            <div class="setup-left">
                <div class="brand"><i class="bi bi-headset"></i> LGICT Support</div>

                <h1>Welcome, {{ explode(' ', $user->name)[0] }} 👋</h1>
                <p class="lede">Let's finish setting up your account — it only takes a minute.</p>

                <div class="progress-track"><div class="progress-fill" style="width: {{ $pct }}%"></div></div>
                <div class="progress-label">{{ $doneCount }} of {{ $totalSteps }} steps complete</div>

                <div class="sb-steps-wrap">
                    <div class="sb-steps" style="display: flex; justify-content: center; align-items: center; margin-bottom: 30px; margin-right: 30px;">
                        @foreach($steps as $step)
                            <div class="sb-row is-{{ $step['status'] }}">
                                <div class="sb-marker">
                                    @if($step['status'] === 'done')
                                        <i class="bi bi-check-lg"></i>
                                    @else
                                        <i class="bi {{ $step['icon'] }}"></i>
                                    @endif
                                    <span class="sb-num">{{ $loop->iteration }}</span>
                                </div>
                                <div class="sb-main">
                                    <span class="sb-title">{{ $step['label'] }}</span><br>
                                    <span class="sb-badge">
                                        {{ $step['status'] === 'done' ? 'Completed' : ($step['status'] === 'current' ? 'In progress' : 'Pending') }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="logout-link"><i class="bi bi-box-arrow-right"></i> Log out</button>
                </form>
            </div>

            {{-- ══ RIGHT — active step content ══ --}}
            <div class="setup-right">
                <div class="right-inner">
                    @if(session('success'))
                        <div class="note-box" style="background:#d4f0d4">
                            <i class="bi bi-check-circle-fill" style="color:#1a5a3a"></i>
                            <div class="note-text" style="color:#1a5a3a">{{ session('success') }}</div>
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="note-box error-box">
                            <i class="bi bi-exclamation-circle-fill"></i>
                            <div class="note-text">{{ session('error') }}</div>
                        </div>
                    @endif
                    @if($errors->any())
                        <div class="note-box error-box">
                            <i class="bi bi-exclamation-circle-fill"></i>
                            <div class="note-text">{{ $errors->first() }}</div>
                        </div>
                    @endif

                    @if($allComplete)
                        <div class="complete-box">
                            <div class="complete-icon"><i class="bi bi-check-lg"></i></div>
                            <h2>All set — your account is ready!</h2>
                            <p>You've completed your personal information and changed your password.</p>
                            <a href="{{ route($user->dashboardRoute()) }}" class="btn-dashboard">
                                <i class="bi bi-speedometer2"></i> Continue to Dashboard
                            </a>
                        </div>
                    @else
                        <div class="note-box">
                            <i class="bi bi-shield-check"></i>
                            <div>
                                <div class="note-title">Nothing is lost</div>
                                <div class="note-text">
                                    @if($ticketCount > 0)
                                        Your {{ $ticketCount }} existing support {{ Str::plural('request', $ticketCount) }} {{ $ticketCount === 1 ? 'is' : 'are' }} still saved and won't be removed while you finish these steps.
                                    @else
                                        Any support requests you submit are always saved — completing these steps won't remove or affect them.
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- ── The one form that's actually actionable right now ── --}}
                        @if(!$orgComplete)
                            <div class="action-panel-head">
                                <div class="action-panel-icon"><i class="bi bi-person-fill"></i></div>
                                <div>
                                    <div class="panel-eyebrow">Steps 1–4 of 5</div>
                                    <div class="action-panel-title">Complete Your Personal Information</div>
                                    <div class="action-panel-sub">Fill these in order — each one unlocks the next</div>
                                </div>
                            </div>

                            <div class="order-guide">
                                <span class="og-lead">Order:</span>
                                <span class="og-step"><span class="og-num">1</span> Business Unit</span>
                                <i class="bi bi-arrow-right"></i>
                                <span class="og-step"><span class="og-num">2</span> Company</span>
                                <i class="bi bi-arrow-right"></i>
                                <span class="og-step"><span class="og-num">3</span> Department</span>
                                <i class="bi bi-arrow-right"></i>
                                <span class="og-step"><span class="og-num">4</span> Position</span>
                            </div>

                            <form method="POST" action="{{ route('profile.org-info') }}" id="orgInfoForm">
                                @csrf
                                @method('PUT')

                                <div class="field-group">
                                    <div class="field-wrap">
                                        <label><span class="lbl-num">1</span> Business Unit</label>
                                        <select class="form-select" id="sBusinessUnit" onchange="sFilterCompanies()">
                                            <option value="">Select business unit…</option>
                                            @foreach($businessUnits as $bu)
                                                <option value="{{ $bu->id }}">{{ $bu->business_units_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="field-wrap">
                                        <label><span class="lbl-num">2</span> Company</label>
                                        <select class="form-select" id="sCompany" onchange="sFilterDepartments()" disabled>
                                            <option value="">Select company…</option>
                                        </select>
                                        <div class="field-hint" id="companyHint"><i class="bi bi-lock-fill"></i> Select a Business Unit first</div>
                                    </div>
                                </div>
                                <div class="field-group">
                                    <div class="field-wrap">
                                        <label><span class="lbl-num">3</span> Department</label>
                                        <select class="form-select" name="department_id" id="sDept" disabled>
                                            <option value="">Select department…</option>
                                        </select>
                                        <div class="field-hint" id="deptHint"><i class="bi bi-lock-fill"></i> Select a Company first</div>
                                    </div>
                                    <div class="field-wrap">
                                        <label><span class="lbl-num">4</span> Position</label>
                                        <input type="text" class="form-control" name="position"
                                               value="{{ old('position', $user->position) }}"
                                               placeholder="e.g. Financial Analyst">
                                    </div>
                                </div>

                                <div class="contact-note">
                                    <i class="bi bi-info-circle"></i>
                                    <span>Can't find your Business Unit, Company, or Department in the list? Email <a href="mailto:icthelpdesk@leoniogroup.com">icthelpdesk@leoniogroup.com</a> or message us on MS Teams so we can add it to the system.</span>
                                </div>

                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn-panel">
                                        <i class="bi bi-check-circle me-1"></i>Save &amp; Continue
                                    </button>
                                </div>
                            </form>
                        @elseif(!$passwordComplete)
                            <div class="action-panel-head">
                                <div class="action-panel-icon" style="background:var(--yg);color:var(--gd)"><i class="bi bi-shield-lock-fill"></i></div>
                                <div>
                                    <div class="panel-eyebrow">Step 5 of 5</div>
                                    <div class="action-panel-title">Change Your Password</div>
                                    <div class="action-panel-sub">Replace your temporary password with one only you know</div>
                                </div>
                            </div>

                            <div class="note-box warn-box">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <div>
                                    <div class="note-title">First time logging in?</div>
                                    <div class="note-text">Your default password is <strong>password</strong>. Enter that as your current password below, then choose a new one.</div>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('profile.password') }}" id="pwForm">
                                @csrf
                                @method('PUT')

                                <div class="field-group full mb-2">
                                    <div class="field-wrap">
                                        <label>Current Password</label>
                                        <div class="pw-wrap">
                                            <input type="password" class="form-control" name="current_password"
                                                   id="currentPw" placeholder="Enter your current password"
                                                   autocomplete="current-password" oninput="checkFormReady()">
                                            <button type="button" class="pw-toggle" onclick="togglePw('currentPw', this)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="field-group">
                                    <div class="field-wrap">
                                        <label>New Password</label>
                                        <div class="pw-wrap">
                                            <input type="password" class="form-control" name="password"
                                                   id="newPw" placeholder="Min. 8 characters"
                                                   autocomplete="new-password"
                                                   oninput="checkStrength(this.value); checkMatch(); checkFormReady()">
                                            <button type="button" class="pw-toggle" onclick="togglePw('newPw', this)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                        <div class="pw-strength-bar"><div class="pw-strength-fill" id="pwFill"></div></div>
                                        <div class="pw-rules">
                                            <div class="pw-rule" id="rule-len"><i class="bi bi-x-circle"></i> 8+ characters</div>
                                            <div class="pw-rule" id="rule-upper"><i class="bi bi-x-circle"></i> Uppercase letter</div>
                                            <div class="pw-rule" id="rule-num"><i class="bi bi-x-circle"></i> Number</div>
                                            <div class="pw-rule" id="rule-special"><i class="bi bi-x-circle"></i> Special character</div>
                                        </div>
                                    </div>
                                    <div class="field-wrap">
                                        <label>Confirm New Password</label>
                                        <div class="pw-wrap">
                                            <input type="password" class="form-control" name="password_confirmation"
                                                   id="confirmPw" placeholder="Re-enter new password"
                                                   autocomplete="new-password" oninput="checkMatch(); checkFormReady()">
                                            <button type="button" class="pw-toggle" onclick="togglePw('confirmPw', this)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                        <div id="matchMsg" style="font-size:12.5px;font-weight:700;margin-top:6px;min-height:18px"></div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn-panel" id="btnChangePw" disabled>
                                        <i class="bi bi-shield-check me-1"></i>Update Password
                                    </button>
                                </div>
                            </form>
                        @endif
                    @endif
                </div>
            </div>

        </div>
    </div>

    <script>
        /* ── Business Unit → Company → Department cascade ── */
        const sAllCompanies   = @json($companiesData ?? []);
        const sAllDepartments = @json($departmentsData ?? []);

        function sFilterCompanies() {
            const buId      = document.getElementById('sBusinessUnit').value;
            const compSel   = document.getElementById('sCompany');
            const deptSel   = document.getElementById('sDept');
            const compHint  = document.getElementById('companyHint');
            const deptHint  = document.getElementById('deptHint');

            compSel.innerHTML = '<option value="">Select company…</option>';
            deptSel.innerHTML  = '<option value="">Select department…</option>';
            compSel.disabled  = !buId;
            deptSel.disabled  = true;
            if (compHint) compHint.style.display = buId ? 'none' : 'flex';
            if (deptHint) { deptHint.innerHTML = '<i class="bi bi-lock-fill"></i> Select a Company first'; deptHint.style.display = 'flex'; }

            if (!buId) return;

            sAllCompanies
                .filter(c => c.business_unit_id === buId)
                .forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.id;
                    opt.text  = c.name;
                    compSel.appendChild(opt);
                });
        }

        function sFilterDepartments() {
            const compId   = document.getElementById('sCompany').value;
            const deptSel  = document.getElementById('sDept');
            const deptHint = document.getElementById('deptHint');

            deptSel.innerHTML = '<option value="">Select department…</option>';
            deptSel.disabled  = !compId;
            if (deptHint) deptHint.style.display = compId ? 'none' : 'flex';

            if (!compId) return;

            sAllDepartments
                .filter(d => d.company_id === compId)
                .forEach(d => {
                    const opt = document.createElement('option');
                    opt.value = d.id;
                    opt.text  = d.name;
                    deptSel.appendChild(opt);
                });
        }

        /* ── Password visibility toggle ── */
        function togglePw(inputId, btn) {
            const input = document.getElementById(inputId);
            if (!input) return;
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                input.type = 'password';
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

        function checkFormReady() {
            const curPw  = document.getElementById('currentPw').value.trim();
            const newPw  = document.getElementById('newPw').value;
            const confPw = document.getElementById('confirmPw').value;
            const btn    = document.getElementById('btnChangePw');
            if (!btn) return;
            btn.disabled = !(curPw && newPw.length >= 8 && newPw === confPw);
        }
    </script>
</body>
</html>
