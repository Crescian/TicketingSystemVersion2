<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Executive Dashboard — LGICT')</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;800;900&family=Nunito+Sans:wght@400;600;700&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <link rel="icon" type="image/png" href="{{ asset('img/LGICT.png') }}">
  <style>
    /* ══════════════════════════════════════════
     EXECUTIVE DESIGN TOKENS
  ══════════════════════════════════════════ */
    :root {
      /* Shared brand tokens */
      --gd: #1a3c1a;
      --gm: #2d5a2d;
      --gl: #4a7c4a;
      --yg: #c8e63c;
      --ygd: #a8c42c;
      --ygl: #e8f5b0;

      /* Executive dark palette */
      --ex-bg: #0d1117;
      --ex-card: #161b22;
      --ex-card2: #1c2330;
      --ex-bd: #30363d;
      --ex-txt: #e6edf3;
      --ex-muted: #7d8590;
      --ex-yg: #c8e63c;
      --ex-green: #3fb950;
      --ex-red: #f85149;
      --ex-amber: #d29922;
      --ex-blue: #58a6ff;
    }

    *,
    *::before,
    *::after {
      box-sizing: border-box;
    }

    body {
      font-family: 'Nunito Sans', sans-serif;
      background: var(--ex-bg);
      color: var(--ex-txt);
      margin: 0;
    }

    .font-brand {
      font-family: 'Nunito', sans-serif;
    }

    /* ── Sticky Topbar ── */
    #topbar {
      background: rgba(13, 17, 23, .95);
      border-bottom: 1px solid var(--ex-bd);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      position: sticky;
      top: 0;
      z-index: 100;
      padding: 0 28px;
      height: 56px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .top-logo {
      font-family: 'Nunito', sans-serif;
      font-weight: 900;
      font-size: 20px;
      color: var(--ex-txt);
      letter-spacing: -.5px;
      text-decoration: none;
    }

    .top-logo span {
      color: var(--ex-yg);
    }

    .exec-badge {
      background: linear-gradient(135deg, #2a4a2a, #3a6a1a);
      border: 1px solid rgba(200, 230, 60, .3);
      color: var(--ex-yg);
      font-size: 11px;
      font-weight: 800;
      padding: 4px 14px;
      border-radius: 20px;
      text-transform: uppercase;
      letter-spacing: .5px;
    }

    .top-date {
      font-size: 12px;
      color: var(--ex-muted);
    }

    /* Date range toggle */
    .date-range {
      display: flex;
      gap: 4px;
      background: var(--ex-card);
      border: 1px solid var(--ex-bd);
      border-radius: 50px;
      padding: 4px;
    }

    .dr-btn {
      padding: 5px 14px;
      border-radius: 50px;
      font-size: 12px;
      font-weight: 700;
      cursor: pointer;
      border: none;
      background: none;
      color: var(--ex-muted);
      transition: all .2s;
      font-family: 'Nunito Sans', sans-serif;
    }

    .dr-btn.active {
      background: var(--ex-yg);
      color: var(--ex-bg);
    }

    .dr-btn:hover:not(.active) {
      color: var(--ex-txt);
    }

    /* Top avatar */
    .top-avatar {
      width: 34px;
      height: 34px;
      background: linear-gradient(135deg, #2a4a2a, #4a7c4a);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--ex-yg);
      font-family: 'Nunito', sans-serif;
      font-weight: 900;
      font-size: 13px;
      border: 1.5px solid rgba(200, 230, 60, .3);
    }

    /* ── Topbar sign-out link ── */
    .btn-exec-signout {
      background: rgba(255, 255, 255, .08);
      color: var(--ex-muted) !important;
      padding: 5px 14px;
      border-radius: 20px;
      font-weight: 700;
      font-size: 12px;
      text-decoration: none;
      border: 1px solid var(--ex-bd);
      transition: background .2s, color .2s;
    }

    .btn-exec-signout:hover {
      background: rgba(255, 255, 255, .14);
      color: var(--ex-txt) !important;
    }

    /* ── Page wrapper (no sidebar) ── */
    .page {
      padding: 28px;
      max-width: 1380px;
      margin: 0 auto;
    }

    /* ── Greeting strip ── */
    .greeting-strip {
      background: linear-gradient(135deg, #0d1f0d 0%, #1a3c1a 50%, #0d1f2d 100%);
      border: 1px solid rgba(200, 230, 60, .12);
      border-radius: 20px;
      padding: 28px 32px;
      margin-bottom: 24px;
      position: relative;
      overflow: hidden;
    }

    .greeting-strip::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image:
        radial-gradient(circle at 90% 50%, rgba(200, 230, 60, .08) 0%, transparent 50%),
        linear-gradient(90deg, rgba(200, 230, 60, .03) 1px, transparent 1px),
        linear-gradient(rgba(200, 230, 60, .03) 1px, transparent 1px);
      background-size: auto, 48px 48px, 48px 48px;
      pointer-events: none;
    }

    .greeting-title {
      font-family: 'Nunito', sans-serif;
      font-weight: 900;
      font-size: 26px;
      color: #fff;
      text-transform: uppercase;
      letter-spacing: -.5px;
      line-height: 1;
    }

    .greeting-title em {
      font-style: normal;
      color: var(--ex-yg);
    }

    .greeting-sub {
      font-size: 14px;
      color: rgba(255, 255, 255, .5);
      margin-top: 4px;
    }

    .live-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(63, 185, 80, .15);
      border: 1px solid rgba(63, 185, 80, .3);
      border-radius: 50px;
      padding: 4px 12px;
      font-size: 11px;
      font-weight: 700;
      color: var(--ex-green);
      text-transform: uppercase;
      letter-spacing: .4px;
    }

    .live-dot {
      width: 6px;
      height: 6px;
      background: var(--ex-green);
      border-radius: 50%;
      animation: liveBlink 1.4s ease-in-out infinite;
    }

    @keyframes liveBlink {

      0%,
      100% {
        opacity: 1
      }

      50% {
        opacity: .3
      }
    }

    /* ── KPI Cards ── */
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 16px;
      margin-bottom: 24px;
    }

    .kpi-card {
      background: var(--ex-card);
      border: 1px solid var(--ex-bd);
      border-radius: 16px;
      padding: 20px 22px;
      position: relative;
      overflow: hidden;
      transition: transform .2s, border-color .2s;
      animation: fadeUp .4s ease both;
    }

    .kpi-card:hover {
      transform: translateY(-2px);
      border-color: rgba(200, 230, 60, .25);
    }

    .kpi-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 3px;
      border-radius: 16px 16px 0 0;
    }

    .kpi-card.yg::before {
      background: var(--ex-yg);
    }

    .kpi-card.green::before {
      background: var(--ex-green);
    }

    .kpi-card.red::before {
      background: var(--ex-red);
    }

    .kpi-card.amber::before {
      background: var(--ex-amber);
    }

    .kpi-card.blue::before {
      background: var(--ex-blue);
    }

    .kpi-icon {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      margin-bottom: 14px;
    }

    .kpi-icon.yg {
      background: rgba(200, 230, 60, .12);
      color: var(--ex-yg);
    }

    .kpi-icon.green {
      background: rgba(63, 185, 80, .12);
      color: var(--ex-green);
    }

    .kpi-icon.red {
      background: rgba(248, 81, 73, .12);
      color: var(--ex-red);
    }

    .kpi-icon.amber {
      background: rgba(210, 153, 34, .12);
      color: var(--ex-amber);
    }

    .kpi-icon.blue {
      background: rgba(88, 166, 255, .12);
      color: var(--ex-blue);
    }

    .kpi-value {
      font-family: 'Nunito', sans-serif;
      font-weight: 900;
      font-size: 36px;
      line-height: 1;
      color: var(--ex-txt);
      margin-bottom: 4px;
    }

    .kpi-label {
      font-size: 12px;
      color: var(--ex-muted);
      text-transform: uppercase;
      letter-spacing: .5px;
      font-weight: 700;
      margin-bottom: 10px;
    }

    .kpi-trend {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      font-size: 12px;
      font-weight: 700;
      border-radius: 20px;
      padding: 3px 8px;
    }

    .kpi-trend.up {
      background: rgba(63, 185, 80, .12);
      color: var(--ex-green);
    }

    .kpi-trend.down {
      background: rgba(248, 81, 73, .12);
      color: var(--ex-red);
    }

    .kpi-trend.flat {
      background: rgba(125, 133, 144, .12);
      color: var(--ex-muted);
    }

    .kpi-compare {
      font-size: 11px;
      color: var(--ex-muted);
      margin-top: 4px;
    }

    /* ── Section label ── */
    .section-label {
      font-family: 'Nunito', sans-serif;
      font-weight: 900;
      font-size: 13px;
      text-transform: uppercase;
      letter-spacing: .8px;
      color: var(--ex-muted);
      margin-bottom: 14px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .section-label::after {
      content: '';
      flex: 1;
      height: 1px;
      background: var(--ex-bd);
    }

    /* ── Chart cards ── */
    .chart-card {
      background: var(--ex-card);
      border: 1px solid var(--ex-bd);
      border-radius: 16px;
      padding: 20px 22px;
      animation: fadeUp .4s ease both;
      transition: border-color .2s;
    }

    .chart-card:hover {
      border-color: rgba(200, 230, 60, .15);
    }

    .chart-title {
      font-family: 'Nunito', sans-serif;
      font-weight: 800;
      font-size: 15px;
      color: var(--ex-txt);
      margin-bottom: 2px;
    }

    .chart-sub {
      font-size: 12px;
      color: var(--ex-muted);
      margin-bottom: 16px;
    }

    .chart-wrap {
      position: relative;
    }

    /* ── SLA gauge donuts ── */
    .sla-gauge-wrap {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 32px;
    }

    .gauge-item {
      text-align: center;
    }

    .gauge-ring {
      position: relative;
      width: 110px;
      height: 110px;
    }

    .gauge-ring canvas {
      width: 110px !important;
      height: 110px !important;
    }

    .gauge-label-center {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      text-align: center;
    }

    .gauge-pct {
      font-family: 'Nunito', sans-serif;
      font-weight: 900;
      font-size: 22px;
      line-height: 1;
    }

    .gauge-sub {
      font-size: 10px;
      color: var(--ex-muted);
      text-transform: uppercase;
      letter-spacing: .3px;
    }

    .gauge-name {
      font-size: 12px;
      font-weight: 700;
      color: var(--ex-muted);
      margin-top: 8px;
      text-transform: uppercase;
      letter-spacing: .3px;
    }

    /* ── Technician leaderboard ── */
    .lb-table {
      width: 100%;
      border-collapse: collapse;
    }

    .lb-table thead th {
      padding: 8px 12px;
      font-size: 11px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: .5px;
      color: var(--ex-muted);
      border-bottom: 1px solid var(--ex-bd);
      text-align: left;
    }

    .lb-table tbody tr {
      border-bottom: 1px solid rgba(48, 54, 61, .6);
      transition: background .15s;
    }

    .lb-table tbody tr:last-child {
      border-bottom: none;
    }

    .lb-table tbody tr:hover {
      background: rgba(200, 230, 60, .04);
    }

    .lb-table tbody td {
      padding: 10px 12px;
      font-size: 13px;
      vertical-align: middle;
    }

    .lb-rank {
      font-family: 'Nunito', sans-serif;
      font-weight: 900;
      font-size: 16px;
      color: var(--ex-muted);
      width: 28px;
    }

    .lb-rank.gold {
      color: #f5c842;
    }

    .lb-rank.silver {
      color: #aab8c4;
    }

    .lb-rank.bronze {
      color: #cd7f32;
    }

    .lb-av {
      width: 30px;
      height: 30px;
      border-radius: 50%;
      background: var(--ex-card2);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 11px;
      font-weight: 900;
      color: var(--ex-yg);
      font-family: 'Nunito', sans-serif;
      border: 1px solid var(--ex-bd);
      flex-shrink: 0;
    }

    .lb-name {
      font-weight: 700;
      font-size: 13px;
      color: var(--ex-txt);
    }

    .lb-role {
      font-size: 11px;
      color: var(--ex-muted);
    }

    .lb-bar-wrap {
      height: 6px;
      background: var(--ex-card2);
      border-radius: 4px;
      margin-top: 4px;
    }

    .lb-bar {
      height: 6px;
      border-radius: 4px;
      background: var(--ex-yg);
    }

    .lb-num {
      font-family: 'Nunito', sans-serif;
      font-weight: 900;
      font-size: 15px;
      color: var(--ex-txt);
      text-align: right;
      white-space: nowrap;
    }

    .lb-rating {
      font-size: 11px;
      color: var(--ex-muted);
      text-align: right;
    }

    /* ── Department heatmap ── */
    .dept-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 10px;
    }

    .dept-cell {
      background: var(--ex-card2);
      border: 1px solid var(--ex-bd);
      border-radius: 12px;
      padding: 14px 16px;
      transition: all .2s;
      cursor: default;
    }

    .dept-cell:hover {
      border-color: rgba(200, 230, 60, .25);
      transform: scale(1.02);
    }

    .dept-cell.hot {
      background: rgba(248, 81, 73, .08);
      border-color: rgba(248, 81, 73, .2);
    }

    .dept-cell.warm {
      background: rgba(210, 153, 34, .08);
      border-color: rgba(210, 153, 34, .2);
    }

    .dept-cell.cool {
      background: rgba(63, 185, 80, .08);
      border-color: rgba(63, 185, 80, .2);
    }

    .dept-name {
      font-family: 'Nunito', sans-serif;
      font-weight: 800;
      font-size: 13px;
      color: var(--ex-txt);
      margin-bottom: 6px;
    }

    .dept-count {
      font-family: 'Nunito', sans-serif;
      font-weight: 900;
      font-size: 24px;
      line-height: 1;
    }

    .dept-count.hot {
      color: var(--ex-red);
    }

    .dept-count.warm {
      color: var(--ex-amber);
    }

    .dept-count.cool {
      color: var(--ex-green);
    }

    .dept-label {
      font-size: 11px;
      color: var(--ex-muted);
    }

    /* ── Escalation items ── */
    .esc-item {
      padding: 12px 0;
      border-bottom: 1px solid rgba(48, 54, 61, .6);
      display: flex;
      align-items: flex-start;
      gap: 12px;
    }

    .esc-item:last-child {
      border-bottom: none;
      padding-bottom: 0;
    }

    .esc-icon {
      width: 34px;
      height: 34px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
      flex-shrink: 0;
    }

    .esc-icon.crit {
      background: rgba(248, 81, 73, .15);
      color: var(--ex-red);
    }

    .esc-icon.warn {
      background: rgba(210, 153, 34, .15);
      color: var(--ex-amber);
    }

    .esc-icon.info {
      background: rgba(88, 166, 255, .15);
      color: var(--ex-blue);
    }

    .esc-title {
      font-weight: 700;
      font-size: 13px;
      color: var(--ex-txt);
      line-height: 1.3;
    }

    .esc-meta {
      font-size: 11px;
      color: var(--ex-muted);
      margin-top: 2px;
    }

    .esc-badge {
      font-size: 10px;
      font-weight: 800;
      border-radius: 20px;
      padding: 3px 8px;
      white-space: nowrap;
      flex-shrink: 0;
    }

    .esc-badge.breach {
      background: rgba(248, 81, 73, .15);
      color: var(--ex-red);
      border: 1px solid rgba(248, 81, 73, .2);
    }

    .esc-badge.open {
      background: rgba(210, 153, 34, .15);
      color: var(--ex-amber);
      border: 1px solid rgba(210, 153, 34, .2);
    }

    .esc-badge.admin {
      background: rgba(88, 166, 255, .15);
      color: var(--ex-blue);
      border: 1px solid rgba(88, 166, 255, .2);
    }

    /* ── Period comparison strip ── */
    .comparison-strip {
      display: flex;
      background: var(--ex-card2);
      border-radius: 12px;
      overflow: hidden;
      border: 1px solid var(--ex-bd);
      margin-bottom: 16px;
    }

    .cmp-col {
      flex: 1;
      padding: 14px 16px;
      border-right: 1px solid var(--ex-bd);
    }

    .cmp-col:last-child {
      border-right: none;
    }

    .cmp-period {
      font-size: 10px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .5px;
      color: var(--ex-muted);
      margin-bottom: 4px;
    }

    .cmp-val {
      font-family: 'Nunito', sans-serif;
      font-weight: 900;
      font-size: 20px;
      color: var(--ex-txt);
    }

    .cmp-diff {
      font-size: 11px;
      font-weight: 700;
      margin-top: 2px;
    }

    .cmp-diff.better {
      color: var(--ex-green);
    }

    .cmp-diff.worse {
      color: var(--ex-red);
    }

    /* ── Animations ── */
    @keyframes fadeUp {
      from {
        opacity: 0;
        transform: translateY(14px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .kpi-card:nth-child(1) {
      animation-delay: .05s;
    }

    .kpi-card:nth-child(2) {
      animation-delay: .10s;
    }

    .kpi-card:nth-child(3) {
      animation-delay: .15s;
    }

    .kpi-card:nth-child(4) {
      animation-delay: .20s;
    }

    /* ── Page-specific overrides ── */
    @yield('styles')
  </style>
</head>

<body>

  {{-- ── EXECUTIVE TOPBAR ── --}}
  <div id="topbar">
    {{-- Left: logo + badge --}}
    <div class="d-flex align-items-center gap-3">
      <a href="#" class="top-logo">LG<span>ICT</span></a>
      <span class="exec-badge"><i class="bi bi-briefcase me-1"></i>Management View</span>
    </div>

    {{-- Right: date range + user + sign out --}}
    <div class="d-flex align-items-center gap-3">
      {{-- Date range toggle — functional in @yield('scripts') --}}
      <div class="date-range" id="dateRange">
        <button class="dr-btn" data-range="7D">7D</button>
        <button class="dr-btn active" data-range="30D">30D</button>
        <button class="dr-btn" data-range="90D">90D</button>
        <button class="dr-btn" data-range="YTD">YTD</button>
      </div>

      <span class="top-date"><i class="bi bi-calendar3 me-1"></i>{{ now()->format('F j, Y') }}</span>

      <div class="top-avatar">
        {{ strtoupper(collect(explode(' ', Auth::user()->name))
  ->map(fn($name) => substr($name, 0, 1))
  ->take(2)
  ->implode('')) }}
      </div>
      <a href="{{ route('profile') }}" class="d-flex align-items-center gap-2 text-decoration-none text-reset">

        <span style="font-size:14px;font-weight:700;color:var(--ex-txt)">
          {{ Auth::user()->name }}
        </span>
      </a>
      @auth
        <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
          class="btn-exec-signout">Sign Out</a>
        <form id="logout-form" method="POST" action="{{ route('logout') }}" class="d-none">@csrf</form>
      @else
        <a href="{{ route('login') }}" class="btn-exec-signout">Sign In</a>
      @endauth
    </div>
  </div>

  {{-- ── PAGE BODY (full-width, no sidebar) ── --}}
  <div class="page">
    @yield('content')
  </div>

  {{-- ── MODALS (if any) ── --}}
  @yield('modals')

  {{-- ── Scripts ── --}}
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>

  <script>
    /* ── Shared Chart.js global defaults ── */
    Chart.defaults.color = '#7d8590';
    Chart.defaults.borderColor = '#30363d';
    Chart.defaults.font.family = "'Nunito Sans', sans-serif";

    /* ── Shared: date range toggle ── */
    $(function () {
      $('#dateRange .dr-btn').on('click', function () {
        $('#dateRange .dr-btn').removeClass('active');
        $(this).addClass('active');
        // Fire a custom event so the view's script can react
        $(document).trigger('rangeChange', [$(this).data('range')]);
      });

      /* ── Shared: toast ── */
      window.showToast = function (msg, type) {
        const bg = type === 'success' ? '#1a3c1a' : '#3c1a1a';
        const $t = $('<div>').css({
          position: 'fixed', bottom: '28px', right: '28px',
          background: bg, color: '#c8e63c',
          fontFamily: 'Nunito,sans-serif', fontWeight: 800, fontSize: '14px',
          padding: '14px 22px', borderRadius: '50px', zIndex: 9999,
          boxShadow: '0 6px 24px rgba(0,0,0,.4)', opacity: 0,
          transition: 'opacity .3s', maxWidth: '420px',
          border: '1px solid rgba(200,230,60,.2)'
        }).text(msg);
        $('body').append($t);
        setTimeout(() => $t.css('opacity', 1), 10);
        setTimeout(() => $t.css('opacity', 0), 3200);
        setTimeout(() => $t.remove(), 3600);
      };
    });
  </script>

  @yield('scripts')

</body>

</html>