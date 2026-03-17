<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'LGICT Support')</title>
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;800;900&family=Nunito+Sans:wght@400;600;700&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <style>
    :root {
      --gd: #1a3c1a;
      --gm: #2d5a2d;
      --gl: #4a7c4a;
      --yg: #c8e63c;
      --ygd: #a8c42c;
      --ygl: #e8f5b0;
      --cr: #f5f0e8;
      --bd: #e2ddd4;
      --tm: #5a7a5a;
    }

    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html,
    body {
      height: 100%;
    }

    body {
      font-family: 'Nunito Sans', sans-serif;
      background: var(--cr);
      color: var(--gd);
      overflow: hidden;
    }

    /* ── Layout ── */
    .login-wrap {
      display: flex;
      height: 100vh;
      width: 100vw;
    }

    /* ── LEFT PANEL ── */
    .left-panel {
      width: 52%;
      background: var(--gd);
      background-image:
        radial-gradient(circle at 30% 20%, rgba(74, 124, 74, .5) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(200, 230, 60, .12) 0%, transparent 45%),
        radial-gradient(circle at 10% 90%, rgba(45, 90, 45, .8) 0%, transparent 40%);
      position: relative;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 48px 56px;
      overflow: hidden;
    }

    .left-panel::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image:
        linear-gradient(rgba(200, 230, 60, .04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(200, 230, 60, .04) 1px, transparent 1px);
      background-size: 48px 48px;
      pointer-events: none;
    }

    .deco-circle {
      position: absolute;
      width: 480px;
      height: 480px;
      border-radius: 50%;
      border: 1px solid rgba(200, 230, 60, .08);
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      animation: pulseRing 4s ease-in-out infinite;
    }

    .deco-circle.c2 {
      width: 340px;
      height: 340px;
      border-color: rgba(200, 230, 60, .12);
      animation-delay: 1s;
    }

    .deco-circle.c3 {
      width: 200px;
      height: 200px;
      border-color: rgba(200, 230, 60, .18);
      animation-delay: 2s;
    }

    @keyframes pulseRing {

      0%,
      100% {
        opacity: .6;
        transform: translate(-50%, -50%) scale(1);
      }

      50% {
        opacity: 1;
        transform: translate(-50%, -50%) scale(1.04);
      }
    }

    .left-logo {
      font-family: 'Nunito', sans-serif;
      font-weight: 900;
      font-size: 26px;
      color: #fff;
      letter-spacing: -.5px;
      position: relative;
      z-index: 1;
      text-decoration: none;
    }

    .left-logo span {
      color: var(--yg);
    }

    .left-main {
      position: relative;
      z-index: 1;
    }

    .left-eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(200, 230, 60, .12);
      border: 1px solid rgba(200, 230, 60, .25);
      border-radius: 50px;
      padding: 6px 16px;
      font-size: 12px;
      font-weight: 700;
      color: var(--yg);
      letter-spacing: .5px;
      text-transform: uppercase;
      margin-bottom: 24px;
    }

    .left-eyebrow .dot {
      width: 6px;
      height: 6px;
      background: var(--yg);
      border-radius: 50%;
      animation: blink 1.8s ease-in-out infinite;
    }

    @keyframes blink {

      0%,
      100% {
        opacity: 1
      }

      50% {
        opacity: .3
      }
    }

    .left-headline {
      font-family: 'Nunito', sans-serif;
      font-weight: 900;
      font-size: clamp(32px, 3.5vw, 48px);
      color: #fff;
      line-height: 1.05;
      letter-spacing: -.5px;
      text-transform: uppercase;
      margin-bottom: 20px;
    }

    .left-headline em {
      font-style: normal;
      color: var(--yg);
    }

    .left-desc {
      font-size: 15px;
      color: rgba(255, 255, 255, .55);
      line-height: 1.6;
      max-width: 380px;
      margin-bottom: 36px;
    }

    .role-cards {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .role-card {
      display: flex;
      align-items: center;
      gap: 14px;
      background: rgba(255, 255, 255, .05);
      border: 1px solid rgba(255, 255, 255, .1);
      border-radius: 14px;
      padding: 13px 18px;
      transition: background .2s, border-color .2s, transform .2s;
      animation: slideIn .5s ease both;
    }

    .role-card:hover {
      background: rgba(255, 255, 255, .09);
      border-color: rgba(200, 230, 60, .3);
      transform: translateX(4px);
    }

    .role-card:nth-child(1) {
      animation-delay: .1s
    }

    .role-card:nth-child(2) {
      animation-delay: .2s
    }

    .role-card:nth-child(3) {
      animation-delay: .3s
    }

    .role-card:nth-child(4) {
      animation-delay: .4s
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateX(-16px)
      }

      to {
        opacity: 1;
        transform: translateX(0)
      }
    }

    .role-icon {
      width: 38px;
      height: 38px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 16px;
      flex-shrink: 0;
    }

    .role-icon.customer {
      background: rgba(200, 230, 60, .15);
      color: var(--yg);
    }

    .role-icon.helpdesk {
      background: rgba(74, 124, 74, .25);
      color: #8fe08f;
    }

    .role-icon.tech {
      background: rgba(245, 200, 66, .15);
      color: #f5c842;
    }

    .role-icon.admin {
      background: rgba(224, 75, 74, .15);
      color: #ff8888;
    }

    .role-title {
      font-family: 'Nunito', sans-serif;
      font-weight: 800;
      font-size: 13px;
      color: #fff;
    }

    .role-desc {
      font-size: 11px;
      color: rgba(255, 255, 255, .45);
      margin-top: 1px;
    }

    .role-arrow {
      margin-left: auto;
      color: rgba(255, 255, 255, .2);
      font-size: 14px;
      transition: color .2s;
    }

    .role-card:hover .role-arrow {
      color: var(--yg);
    }

    .left-footer {
      position: relative;
      z-index: 1;
      font-size: 12px;
      color: rgba(255, 255, 255, .3);
    }

    /* ── RIGHT PANEL ── */
    .right-panel {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px 48px;
      background: var(--cr);
      position: relative;
      overflow-y: auto;
    }

    .right-panel::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image: radial-gradient(circle at 80% 10%, rgba(200, 230, 60, .07) 0%, transparent 40%),
        radial-gradient(circle at 20% 90%, rgba(26, 60, 26, .04) 0%, transparent 40%);
      pointer-events: none;
    }

    .form-box {
      width: 100%;
      max-width: 420px;
      position: relative;
      z-index: 1;
      animation: fadeUp .5s ease both;
    }

    @keyframes fadeUp {
      from {
        opacity: 0;
        transform: translateY(20px)
      }

      to {
        opacity: 1;
        transform: translateY(0)
      }
    }

    .form-eyebrow {
      font-size: 12px;
      font-weight: 700;
      color: var(--tm);
      text-transform: uppercase;
      letter-spacing: .8px;
      margin-bottom: 8px;
    }

    .form-title {
      font-family: 'Nunito', sans-serif;
      font-weight: 900;
      font-size: 30px;
      color: var(--gd);
      letter-spacing: -.5px;
      line-height: 1.1;
      margin-bottom: 6px;
    }

    .form-title em {
      font-style: normal;
      color: var(--gl);
    }

    .form-sub {
      font-size: 14px;
      color: var(--tm);
      margin-bottom: 32px;
      line-height: 1.5;
    }

    .field-wrap {
      margin-bottom: 18px;
    }

    .field-label {
      display: block;
      font-size: 13px;
      font-weight: 700;
      color: var(--gd);
      margin-bottom: 7px;
    }

    .input-wrap {
      position: relative;
    }

    .input-icon {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--tm);
      font-size: 15px;
      pointer-events: none;
      transition: color .2s;
    }

    .form-input {
      width: 100%;
      border: 1.5px solid var(--bd);
      border-radius: 12px;
      padding: 13px 14px 13px 42px;
      font-size: 14px;
      font-family: 'Nunito Sans', sans-serif;
      background: #fff;
      color: var(--gd);
      outline: none;
      transition: border-color .2s, box-shadow .2s;
    }

    .form-input:focus {
      border-color: var(--gl);
      box-shadow: 0 0 0 3px rgba(74, 124, 74, .12);
    }

    .input-wrap:focus-within .input-icon {
      color: var(--gl);
    }

    /* Input error state */
    .form-input.is-invalid {
      border-color: #dc3545 !important;
      box-shadow: 0 0 0 3px rgba(220, 53, 69, .12) !important;
    }

    .field-error {
      font-size: 12px;
      color: #dc3545;
      font-weight: 600;
      margin-top: 5px;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .pw-toggle {
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: var(--tm);
      font-size: 15px;
      cursor: pointer;
      padding: 0;
      transition: color .2s;
    }

    .pw-toggle:hover {
      color: var(--gd);
    }

    .form-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 24px;
    }

    .remember-label {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 13px;
      font-weight: 600;
      color: var(--tm);
      cursor: pointer;
      user-select: none;
    }

    .remember-label input[type="checkbox"] {
      accent-color: var(--gd);
      width: 15px;
      height: 15px;
      cursor: pointer;
    }

    .forgot-link {
      font-size: 13px;
      font-weight: 700;
      color: var(--gl);
      text-decoration: none;
    }

    .forgot-link:hover {
      color: var(--gd);
      text-decoration: underline;
    }

    .btn-login {
      width: 100%;
      background: var(--gd);
      color: var(--yg);
      font-family: 'Nunito', sans-serif;
      font-weight: 900;
      font-size: 15px;
      padding: 15px;
      border-radius: 50px;
      border: none;
      cursor: pointer;
      transition: background .2s, transform .15s, box-shadow .2s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      letter-spacing: .3px;
      margin-bottom: 20px;
      box-shadow: 0 4px 16px rgba(26, 60, 26, .18);
    }

    .btn-login:hover {
      background: var(--gm);
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(26, 60, 26, .25);
    }

    .btn-login:active {
      transform: translateY(0);
    }

    .btn-login .spinner {
      display: none;
      width: 18px;
      height: 18px;
      border: 2.5px solid rgba(200, 230, 60, .3);
      border-top-color: var(--yg);
      border-radius: 50%;
      animation: spin .7s linear infinite;
    }

    .btn-login.loading .spinner {
      display: inline-block;
    }

    .btn-login.loading .btn-text {
      display: none;
    }

    @keyframes spin {
      to {
        transform: rotate(360deg);
      }
    }

    .divider {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 20px;
    }

    .divider-line {
      flex: 1;
      height: 1px;
      background: var(--bd);
    }

    .divider-text {
      font-size: 12px;
      color: var(--tm);
      font-weight: 600;
      white-space: nowrap;
    }

    .role-preview {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-bottom: 28px;
    }

    .rp-chip {
      display: flex;
      align-items: center;
      gap: 6px;
      background: #fff;
      border: 1.5px solid var(--bd);
      border-radius: 50px;
      padding: 5px 12px 5px 8px;
      font-size: 12px;
      font-weight: 700;
      color: var(--tm);
      cursor: pointer;
      transition: all .2s;
      user-select: none;
    }

    .rp-chip:hover {
      border-color: var(--gl);
      color: var(--gd);
      background: var(--ygl);
    }

    .rp-chip.active {
      background: var(--ygl);
      border-color: var(--gd);
      color: var(--gd);
    }

    .rp-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
    }

    .rp-dot.customer {
      background: var(--yg);
    }

    .rp-dot.helpdesk {
      background: #8fe08f;
    }

    .rp-dot.tech {
      background: #f5c842;
    }

    .rp-dot.admin {
      background: #ff8888;
    }

    /* Laravel validation errors alert */
    .error-box {
      background: #fde8e8;
      border: 1.5px solid #f0c0c0;
      border-radius: 12px;
      padding: 12px 16px;
      font-size: 13px;
      font-weight: 600;
      color: #8b1a1a;
      display: none;
      align-items: flex-start;
      gap: 10px;
      margin-bottom: 18px;
    }

    .error-box.show {
      display: flex;
      animation: shakeX .4s ease;
    }

    .error-box ul {
      margin: 0;
      padding: 0 0 0 4px;
      list-style: none;
    }

    .error-box ul li+li {
      margin-top: 4px;
    }

    @keyframes shakeX {

      0%,
      100% {
        transform: translateX(0)
      }

      20% {
        transform: translateX(-6px)
      }

      40% {
        transform: translateX(6px)
      }

      60% {
        transform: translateX(-4px)
      }

      80% {
        transform: translateX(4px)
      }
    }

    /* Success redirect overlay */
    .redirect-overlay {
      position: fixed;
      inset: 0;
      background: var(--gd);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      z-index: 9999;
      opacity: 0;
      pointer-events: none;
      transition: opacity .4s;
    }

    .redirect-overlay.show {
      opacity: 1;
      pointer-events: all;
    }

    .redirect-icon {
      font-size: 48px;
      margin-bottom: 16px;
      animation: popIn .5s ease .3s both;
    }

    .redirect-role {
      font-family: 'Nunito', sans-serif;
      font-weight: 900;
      font-size: 13px;
      color: var(--yg);
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-bottom: 8px;
      animation: popIn .5s ease .4s both;
    }

    .redirect-msg {
      font-family: 'Nunito', sans-serif;
      font-weight: 900;
      font-size: 24px;
      color: #fff;
      text-transform: uppercase;
      letter-spacing: -.3px;
      animation: popIn .5s ease .5s both;
    }

    .redirect-sub {
      font-size: 14px;
      color: rgba(255, 255, 255, .5);
      margin-top: 8px;
      animation: popIn .5s ease .6s both;
    }

    .redirect-bar-wrap {
      width: 200px;
      height: 4px;
      background: rgba(255, 255, 255, .12);
      border-radius: 4px;
      margin-top: 24px;
      animation: popIn .5s ease .7s both;
      overflow: hidden;
    }

    .redirect-bar {
      height: 4px;
      background: var(--yg);
      border-radius: 4px;
      width: 0;
      transition: width 1.8s linear;
    }

    @keyframes popIn {
      from {
        opacity: 0;
        transform: scale(.8)
      }

      to {
        opacity: 1;
        transform: scale(1)
      }
    }

    .right-footer {
      position: absolute;
      bottom: 20px;
      left: 0;
      right: 0;
      text-align: center;
      font-size: 12px;
      color: var(--bd);
    }

    @media (max-width: 860px) {
      body {
        overflow: auto;
      }

      .login-wrap {
        flex-direction: column;
        height: auto;
        min-height: 100vh;
      }

      .left-panel {
        width: 100%;
        padding: 36px 28px 32px;
        min-height: auto;
      }

      .deco-circle {
        display: none;
      }

      .role-cards {
        display: none;
      }

      .right-panel {
        padding: 40px 28px 60px;
      }
    }

    @yield('styles')
  </style>
</head>

<body>

  @yield('content')

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
  @yield('scripts')

</body>

</html>