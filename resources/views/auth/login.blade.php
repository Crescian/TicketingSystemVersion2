@extends('layouts.guest')

@section('title', 'Sign In — LGICT Support')

@section('content')

  {{-- ── REDIRECT OVERLAY ── --}}
  <div class="redirect-overlay" id="redirectOverlay">
    <div class="redirect-icon" id="rdIcon">✅</div>
    <div class="redirect-role" id="rdRole">Customer</div>
    <div class="redirect-msg" id="rdMsg">Welcome back!</div>
    <div class="redirect-sub" id="rdSub">Redirecting to your dashboard…</div>
    <div class="redirect-bar-wrap">
      <div class="redirect-bar" id="rdBar"></div>
    </div>
  </div>

  <div class="login-wrap">

    {{-- ══ LEFT PANEL ══ --}}
    <div class="left-panel">
      <div class="deco-circle"></div>
      <div class="deco-circle c2"></div>
      <div class="deco-circle c3"></div>

      <a href="#" class="left-logo">Support Request<span> System</span></a>

      <div class="left-main">
        <div class="left-eyebrow">
          <div class="dot"></div>IT Support Portal
        </div>
        <h1 class="left-headline">ONE LOGIN.<br>YOUR <em>ROLE.</em><br>YOUR <em>TOOLS.</em></h1>
        <p class="left-desc">Sign in with your company account. We'll automatically take you to the right dashboard based
          on your role.</p>

        <div class="role-cards">
          <div class="role-card">
            <div class="role-icon customer"><i class="bi bi-person"></i></div>
            <div>
              <div class="role-title">Customer (Employee)</div>
              <div class="role-desc">Submit and track your IT support requests</div>
            </div>
            
          </div>
          <div class="role-card">
            <div class="role-icon helpdesk"><i class="bi bi-headset"></i></div>
            <div>
              <div class="role-title">Helpdesk</div>
              <div class="role-desc">Manage the queue, assign technicians</div>
            </div>
            
          </div>
          <div class="role-card">
            <div class="role-icon tech"><i class="bi bi-tools"></i></div>
            <div>
              <div class="role-title">IT Support Specialist</div>
              <div class="role-desc">Accept, work on, and resolve tickets</div>
            </div>
            
          </div>
          <div class="role-card">
            <div class="role-icon admin"><i class="bi bi-shield-fill"></i></div>
            <div>
              <div class="role-title">IT Admin</div>
              <div class="role-desc">Handle escalations, full system oversight</div>
            </div>
            
          </div>
        </div>
      </div>

      <div class="left-footer">© 2026 ONEICT Internal Support System. All rights reserved. Developed by CML</div>
    </div>

    {{-- ══ RIGHT PANEL ══ --}}
    <div class="right-panel">
      <div class="form-box">

        <div class="form-eyebrow">Welcome back</div>
        <h2 class="form-title">Sign in to<br><em>Support Request System</em></h2>
        <p class="form-sub">Use your company email and password. You'll be redirected to your role's dashboard
          automatically.</p>

        {{-- ── Laravel Validation Errors ── --}}
        @if ($errors->any())
          <div class="error-box show">
            <i class="bi bi-exclamation-circle-fill" style="flex-shrink:0;margin-top:1px"></i>
            <ul>
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        {{-- ── Session Status (e.g. after logout) ── --}}
        @if (session('status'))
          <div
            style="background:#e8f5e9;border:1.5px solid #a5d6a7;border-radius:12px;padding:12px 16px;font-size:13px;font-weight:600;color:var(--gd);display:flex;align-items:center;gap:10px;margin-bottom:18px;">
            <i class="bi bi-check-circle-fill" style="color:var(--gl)"></i>
            {{ session('status') }}
          </div>
        @endif


        {{-- ── Laravel Login Form ── --}}
        <form method="POST" action="{{ route('login') }}" id="loginForm" novalidate>
          @csrf

          {{-- Email --}}
          <div class="field-wrap">
            <label class="field-label" for="email">Company Email</label>
            <div class="input-wrap">
              <input type="email" class="form-input @error('email') is-invalid @enderror" id="email" name="email"
                value="{{ old('email') }}" placeholder="you@leoniogroup.com" autocomplete="email" autofocus required>
              <i class="bi bi-envelope input-icon"></i>
            </div>
            @error('email')
              <div class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</div>
            @enderror
          </div>

          {{-- Password --}}
          <div class="field-wrap">
            <label class="field-label" for="password">Password</label>
            <div class="input-wrap">
              <input type="password" class="form-input @error('password') is-invalid @enderror" id="password"
                name="password" placeholder="Enter your password" autocomplete="current-password"
                style="padding-right:44px" required>
              <i class="bi bi-lock input-icon"></i>
              <button class="pw-toggle" id="pwToggle" type="button" tabindex="-1">
                <i class="bi bi-eye" id="pwIcon"></i>
              </button>
            </div>
            @error('password')
              <div class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</div>
            @enderror
          </div>

          {{-- Remember Me + Forgot Password --}}
          <div class="form-row">
            <label class="remember-label">
              <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
              Remember me
            </label>
            @if (Route::has('password.request'))
              <a href="{{ route('password.request') }}" class="forgot-link">Forgot password?</a>
            @endif
          </div>

          {{-- Submit --}}
          <button type="submit" class="btn-login" id="btnLogin">
            <span class="spinner"></span>
            <span class="btn-text"><i class="bi bi-arrow-right-circle me-1"></i>Sign In</span>
          </button>

        </form>

        <div style="display:flex;align-items:center;gap:12px;margin:20px 0;color:var(--tm);font-size:12px;font-weight:600;">
          <div style="flex:1;height:1px;background:var(--bd,#e0e0e0);"></div>
          OR
          <div style="flex:1;height:1px;background:var(--bd,#e0e0e0);"></div>
        </div>

        <a href="{{ route('auth.microsoft.redirect') }}" class="btn-login"
          style="display:flex;align-items:center;justify-content:center;gap:10px;text-decoration:none;background:#fff;color:#5e5e5e;border:1.5px solid #d0d0d0;">
          <svg width="18" height="18" viewBox="0 0 21 21" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <rect x="1" y="1" width="9" height="9" fill="#f25022" />
            <rect x="11" y="1" width="9" height="9" fill="#7fba00" />
            <rect x="1" y="11" width="9" height="9" fill="#00a4ef" />
            <rect x="11" y="11" width="9" height="9" fill="#ffb900" />
          </svg>
          Sign in with Microsoft
        </a>

        <p style="text-align:center;font-size:12px;color:var(--tm)">
          Having trouble? Contact your IT Helpdesk or email
          <a href="mailto:icthelpdesk@leoniogroup.com"
            style="color:var(--gl);font-weight:700">icthelpdesk@leoniogroup.com</a>
        </p>

                {{-- Return to Landing --}}
        <!-- <div class="text-center mt-3">
            <a href="http://usermgmt.development.com/" style="font-size:12.5px;font-weight:700;color:var(--tm);text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:color .2s;"
            onmouseover="this.style.color='var(--nl)'" onmouseout="this.style.color='var(--tm)'">
                <i class="bi bi-arrow-left"></i> Return to Home
            </a>
        </div> -->
      </div>
      <div class="right-footer">LGICT Support System v2.5 &nbsp;·&nbsp; Secure internal access</div>
    </div>

  </div>

@endsection

@section('scripts')
  <script>
    $(function () {
      // console.log(Notification.permission);
      // function testNotif() {
      //   if (!('Notification' in window)) return alert('Notifications not supported!');
      //   if (Notification.permission !== 'granted') return alert('Notifications not allowed!');

      //   new Notification('🔔 Test Notification', {
      //     body: 'This is a test from your ticketing system.',
      //     icon: '/favicon.ico'
      //   });
      // }
      // testNotif();

      /* ── Demo role chip autofill ── */
      const demoPw = 'demo1234';
      $('#demoChips .rp-chip').on('click', function () {
        const email = $(this).data('email');
        $('#demoChips .rp-chip').removeClass('active');
        $(this).addClass('active');
        $('#email').val(email).trigger('input');
        $('#password').val(demoPw).trigger('input');
        $('.form-input').removeClass('is-invalid');
        $('.field-error').hide();
        $('.error-box').removeClass('show');
      });

      /* ── Password toggle ── */
      $('#pwToggle').on('click', function () {
        const $inp = $('#password');
        const isText = $inp.attr('type') === 'text';
        $inp.attr('type', isText ? 'password' : 'text');
        $('#pwIcon').toggleClass('bi-eye', !isText).toggleClass('bi-eye-slash', isText);
      });

      /* ── Loading spinner on submit ── */
      $('#loginForm').on('submit', function () {
        const email = $('#email').val().trim();
        const pw = $('#password').val().trim();
        if (!email || !pw) return;
        $('#btnLogin').addClass('loading').prop('disabled', true);
      });

      /* ── Enter key triggers submit ── */
      $(document).on('keydown', function (e) {
        if (e.key === 'Enter' && !$(e.target).is('textarea')) {
          $('#loginForm').trigger('submit');
        }
      });

      /* ── Auto-focus first invalid field on page load ── */
      @if ($errors->any())
        setTimeout(function () {
          $('.form-input.is-invalid').first().focus();
        }, 300);
      @endif

                              });
  </script>
@endsection