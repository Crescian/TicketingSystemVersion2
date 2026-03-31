<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'LGICT Support')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link
        href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;800;900&family=Nunito+Sans:wght@400;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('img/LGICT.png') }}">
    <script>
        // ── Request browser notification permission on page load
        if ('Notification' in window) {
            if (Notification.permission === 'default') {
                // Ask after a short delay so it doesn't feel intrusive
                setTimeout(() => {
                    Notification.requestPermission().then(permission => {
                        if (permission === 'granted') {
                            showBrowserNotif(
                                '🔔 Notifications enabled!',
                                'You will now receive updates for your tickets.'
                            );
                        }
                    });
                }, 3000);
            }
        }

        // ── Helper to show a browser notification
        function showBrowserNotif(title, body, options = {}) {
            if (!('Notification' in window)) return;
            if (Notification.permission !== 'granted') return;
            if (document.hasFocus()) return; // Only show when tab is NOT focused

            const notif = new Notification(title, {
                body: body,
                icon: '/favicon.ico',  // your app icon
                badge: '/favicon.ico',
                tag: options.tag || 'lgict-ticket',
                ...options
            });

            // Click notification → focus the tab
            notif.onclick = function () {
                window.focus();
                if (options.url) window.location.href = options.url;
                notif.close();
            };

            // Auto close after 6 seconds
            setTimeout(() => notif.close(), 6000);
        }
    </script>
    <style>
        /* ══════════════════════════════════════════
     SHARED DESIGN TOKENS & BASE STYLES
  ══════════════════════════════════════════ */
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
        }

        body {
            font-family: 'Nunito Sans', sans-serif;
            background: var(--cr);
            color: var(--gd);
            margin: 0;
        }

        .font-brand {
            font-family: 'Nunito', sans-serif;
        }

        /* ── Topbar ── */
        #topbar {
            background: var(--ygl);
            font-size: 13px;
            border-bottom: 1px solid #d4e8b0;
        }

        #topbar a {
            color: var(--gd);
            font-weight: 600;
            text-decoration: none;
        }

        .btn-topbar-action {
            background: var(--gd);
            color: #fff !important;
            padding: 4px 16px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 13px;
            text-decoration: none;
        }

        /* ── Navbar ── */
        .navbar {
            background: #fff;
            border-bottom: 1px solid #eee;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .05);
            padding-top: 0;
            padding-bottom: 0;
        }

        .navbar-brand {
            font-family: 'Nunito', sans-serif;
            font-weight: 900;
            font-size: 22px;
            color: var(--gd);
            letter-spacing: -.5px;
            text-decoration: none;
        }

        .navbar-brand span {
            color: var(--gl);
        }

        /* Role badge — colour set per view via @yield('role-badge-style')
        */ .role-badge {
            font-size: 11px;
            font-weight: 800;
            padding: 3px 12px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: .4px;
            background: var(--ygl);
            color: var(--gd);
        }

        .role-badge.dark {
            background: var(--gd);
            color: var(--yg);
        }

        /* Avatar chip — colour overridden per view */
        .avatar-chip {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Nunito', sans-serif;
            font-weight: 900;
            font-size: 13px;
            background: var(--gd);
            color: var(--yg);
        }

        /* ── Hero strip ── */
        #hero {
            background: var(--gd);
            background-image:
                radial-gradient(circle at 80% 50%, rgba(74, 124, 74, .35) 0%, transparent 60%),
                radial-gradient(circle at 10% 80%, rgba(200, 230, 60, .08) 0%, transparent 40%);
            padding: 1.5rem 1.5rem;
        }

        #hero h1 {
            font-family: 'Nunito', sans-serif;
            font-weight: 900;
            font-size: 34px;
            text-transform: uppercase;
            letter-spacing: -.5px;
            line-height: 1.05;
            color: #fff;
            margin-bottom: .25rem;
        }

        #hero h1 em {
            font-style: normal;
            color: var(--yg);
        }

        #hero .hero-sub {
            color: rgba(255, 255, 255, .6);
            font-size: 14px;
            margin: 0;
        }

        /* Stat pills */
        .stat-pill {
            background: rgba(255, 255, 255, .08);
            border: 1px solid rgba(255, 255, 255, .14);
            border-radius: 14px;
            padding: 14px 20px;
            text-align: center;
            min-width: 90px;
        }

        .stat-pill .num {
            font-family: 'Nunito', sans-serif;
            font-weight: 900;
            font-size: 26px;
            color: var(--yg);
            display: block;
            line-height: 1;
        }

        .stat-pill .lbl {
            font-size: 10px;
            color: rgba(255, 255, 255, .55);
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-top: 3px;
            display: block;
        }

        .stat-pill.warn .num {
            color: #fde8a0;
        }

        .stat-pill.danger .num {
            color: #ffaaaa;
        }

        /* ── Sidebar shared styles ── */
        .sidebar-card {
            background: #fff;
            border-radius: 16px;
            border: 1.5px solid var(--bd);
            overflow: hidden;
        }

        .sidebar-head {
            background: var(--gd);
            padding: 12px 18px;
            font-family: 'Nunito', sans-serif;
            font-weight: 800;
            font-size: 12px;
            color: var(--yg);
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .sidebar-menu .list-group-item {
            border: none;
            border-bottom: 1px solid var(--bd);
            font-weight: 600;
            font-size: 14px;
            color: var(--gd);
            padding: 0;
            /* ← remove padding from li */
            transition: background .15s;
            border-radius: 0 !important;
            cursor: pointer;
        }

        .sidebar-menu .list-group-item a {
            padding: 11px 18px;
            /* ← put padding on the anchor instead */
            color: var(--gd);
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            text-decoration: none;
            transition: background .15s;
        }

        .sidebar-menu .list-group-item:last-child {
            border-bottom: none;
        }

        .sidebar-menu .list-group-item:hover,
        .sidebar-menu .list-group-item a:hover {
            background: var(--ygl);
            color: var(--gd);
        }

        .sidebar-menu .list-group-item.active a {
            background: var(--ygl);
            border-left: 4px solid var(--yg);
            font-weight: 700;
        }

        .sidebar-menu .badge-count {
            background: var(--gd);
            color: var(--yg);
            font-size: 11px;
            border-radius: 20px;
            padding: 2px 8px;
            margin-left: auto;
            font-weight: 800;
        }

        .sidebar-menu .badge-count.red {
            background: #8b1a1a;
            color: #fde8e8;
        }

        /* ── Ticket card shared styles ── */
        .ticket-card {
            background: #fff;
            border-radius: 16px;
            border: 1.5px solid var(--bd);
            transition: transform .2s, box-shadow .2s, border-color .2s;
            position: relative;
            overflow: hidden;
            animation: fadeUp .35s ease both;
        }

        .ticket-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(26, 60, 26, .10);
            border-color: #c8d8b8;
        }

        .ticket-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            border-radius: 4px 0 0 4px;
        }

        .ticket-id {
            font-family: 'Nunito', sans-serif;
            font-weight: 900;
            font-size: 12px;
            color: var(--tm);
            letter-spacing: .5px;
        }

        .ticket-title {
            font-family: 'Nunito', sans-serif;
            font-weight: 800;
            font-size: 15px;
            line-height: 1.3;
        }

        .ticket-desc {
            font-size: 13px;
            color: var(--tm);
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .badge-type {
            background: var(--ygl);
            color: var(--gd);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .3px;
            border-radius: 20px;
            padding: 3px 10px;
        }

        .badge-status {
            font-family: 'Nunito', sans-serif;
            font-weight: 800;
            font-size: 11px;
            border-radius: 20px;
            padding: 4px 12px;
        }

        .meta-item {
            font-size: 12px;
            color: var(--tm);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .priority-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }

        .pri-high {
            background: #e24b4a;
        }

        .pri-medium {
            background: #f5c842;
        }

        .pri-low {
            background: #4a7c4a;
        }

        .tech-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--ygl);
            border-radius: 20px;
            padding: 3px 10px 3px 4px;
            font-size: 12px;
            font-weight: 700;
            color: var(--gd);
        }

        .tc-av {
            width: 22px;
            height: 22px;
            background: var(--gd);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 900;
            color: var(--yg);
            font-family: 'Nunito', sans-serif;
        }

        .esc-banner {
            background: #fde8e8;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            color: #8b1a1a;
        }

        /* Badge statuses */
        .badge-open {
            background: var(--ygl);
            color: var(--gm);
        }

        .badge-in-progress {
            background: #fff4cc;
            color: #7a5a00;
        }

        .badge-escalated {
            background: #fde8e8;
            color: #8b1a1a;
        }

        .badge-resolved {
            background: #e8f5ee;
            color: #1a5a3a;
        }

        .badge-unassigned {
            background: #fde8e8;
            color: #8b1a1a;
        }

        .badge-new {
            background: #fde8e8;
            color: #8b1a1a;
        }

        .badge-accepted {
            background: #fff4cc;
            color: #7a5a00;
        }

        /* Ticket left border colours */
        .ticket-card.open::before {
            background: var(--gl);
        }

        .ticket-card.in-progress::before {
            background: #f5c842;
        }

        .ticket-card.escalated::before {
            background: #e24b4a;
        }

        .ticket-card.resolved::before {
            background: var(--gl);
            opacity: .5;
        }

        .ticket-card.unassigned::before {
            background: #e24b4a;
        }

        .ticket-card.new-assigned::before {
            background: #e24b4a;
        }

        .ticket-card.accepted::before {
            background: #f5c842;
        }

        /* ── Search & sort ── */
        .search-wrap {
            background: #fff;
            border: 1.5px solid var(--bd);
            border-radius: 50px;
            padding: 7px 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .search-wrap input {
            border: none;
            outline: none;
            font-size: 13px;
            background: transparent;
            color: var(--gd);
            width: 180px;
        }

        .sort-select {
            border: 1.5px solid var(--bd);
            border-radius: 50px;
            font-size: 13px;
            color: var(--gd);
            background: #fff;
            padding: 7px 14px;
            outline: none;
        }

        /* ── Tab pills ── */
        .tab-pill {
            padding: 7px 16px;
            border-radius: 50px;
            font-family: 'Nunito', sans-serif;
            font-weight: 800;
            font-size: 13px;
            cursor: pointer;
            border: 1.5px solid var(--bd);
            background: #fff;
            color: var(--tm);
            transition: all .2s;
            user-select: none;
        }

        .tab-pill:hover {
            border-color: var(--gl);
            color: var(--gd);
        }

        .tab-pill.active {
            background: var(--gd);
            color: var(--yg);
            border-color: var(--gd);
        }

        /* ── Modal shared styles ── */
        .modal-content {
            border-radius: 20px;
            overflow: hidden;
            border: none;
        }

        .modal-header-gd {
            background: var(--gd);
            padding: 20px 28px;
        }

        .modal-header-gd h5 {
            font-family: 'Nunito', sans-serif;
            font-weight: 900;
            font-size: 19px;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: -.3px;
            margin: 0;
        }

        .modal-header-gd h5 em {
            font-style: normal;
            color: var(--yg);
        }

        .btn-close-w {
            background: rgba(255, 255, 255, .15);
            border: none;
            color: #fff;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            font-size: 15px;
            cursor: pointer;
            transition: background .2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-close-w:hover {
            background: rgba(255, 255, 255, .28);
        }

        .modal label {
            font-size: 13px;
            font-weight: 700;
            color: var(--gd);
            margin-bottom: 5px;
        }

        .modal .form-control,
        .modal .form-select {
            border: 1.5px solid var(--bd);
            border-radius: 10px;
            font-size: 14px;
            background: var(--cr);
            color: var(--gd);
            padding: 10px 14px;
        }

        .modal .form-control:focus,
        .modal .form-select:focus {
            border-color: var(--gl);
            box-shadow: 0 0 0 3px rgba(74, 124, 74, .12);
            background: #fff;
        }

        .info-box-green {
            background: var(--ygl);
            border-radius: 10px;
            font-size: 13px;
            color: var(--gd);
        }

        .info-box-red {
            background: #fde8e8;
            border-radius: 10px;
            font-size: 13px;
            color: #8b1a1a;
        }

        .btn-confirm {
            background: var(--gd);
            color: var(--yg);
            font-family: 'Nunito', sans-serif;
            font-weight: 900;
            font-size: 14px;
            padding: 11px 28px;
            border-radius: 50px;
            border: none;
            transition: all .2s;
            cursor: pointer;
        }

        .btn-confirm:hover {
            background: var(--gm);
        }

        .btn-cancel-modal {
            background: none;
            border: 1.5px solid var(--bd);
            color: var(--tm);
            font-family: 'Nunito', sans-serif;
            font-weight: 800;
            font-size: 14px;
            padding: 10px 22px;
            border-radius: 50px;
            transition: all .2s;
            cursor: pointer;
        }

        .btn-cancel-modal:hover {
            border-color: var(--gl);
            color: var(--gd);
        }

        /* ── Filter sidebar card ── */
        .filter-card label {
            font-size: 12px;
            font-weight: 700;
            color: var(--tm);
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .filter-card .form-select,
        .filter-card .form-control {
            border: 1.5px solid var(--bd);
            border-radius: 8px;
            font-size: 13px;
            background: var(--cr);
            color: var(--gd);
        }

        .filter-card .form-select:focus,
        .filter-card .form-control:focus {
            border-color: var(--gl);
            box-shadow: 0 0 0 3px rgba(74, 124, 74, .12);
        }

        .btn-filter {
            background: var(--gd);
            color: var(--yg);
            font-family: 'Nunito', sans-serif;
            font-weight: 800;
            font-size: 14px;
            border-radius: 10px;
            border: none;
        }

        .btn-filter:hover {
            background: var(--gm);
            color: var(--yg);
        }

        /* ── Animations ── */
        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(16px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .ticket-card:nth-child(1) {
            animation-delay: .04s;
        }

        .ticket-card:nth-child(2) {
            animation-delay: .10s;
        }

        .ticket-card:nth-child(3) {
            animation-delay: .16s;
        }

        .ticket-card:nth-child(4) {
            animation-delay: .22s;
        }

        .ticket-card:nth-child(5) {
            animation-delay: .28s;
        }

        .ticket-card:nth-child(6) {
            animation-delay: .34s;
        }

        /* ── Page-specific styles injected per view ── */
        @yield('styles')
    </style>
</head>

<body>

    {{-- ── TOPBAR ── --}}
    <div id="topbar" class="d-flex justify-content-end align-items-center gap-3 px-4 py-1">
        <a href="#">Help &amp; more info <i class="bi bi-chevron-down"></i></a>
        @auth
            <a href="{{ route('logout') }}"
                onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                class="btn-topbar-action">Sign Out</a>
            <form id="logout-form" method="POST" action="{{ route('logout') }}" class="d-none">@csrf</form>
        @else
            <a href="{{ route('login') }}" class="btn-topbar-action">Sign In</a>
        @endauth
    </div>
    {{-- ── NAVBAR ── --}}
    <nav class="navbar sticky-top px-4">
        <div class="container-fluid px-0">
            <a class="navbar-brand" href="{{ 
                match (Auth::user()->role?->role_name) {
        'Helpdesk' => route('helpdesk.dashboard'),
        'IT Support Specialist' => route('technician.dashboard'),
        'IT Admin' => route('admin.dashboard'),
        'Manager' => route('executive.dashboard'),
        default => route('employee.tickets.index'),
    }
            }}">LG<span>ICTicketingSystem</span></a>
            <div class="ms-auto d-flex align-items-center gap-2">
                @yield('nav-role-badge')
                <a href="{{ route('profile') }}"
                    class="d-flex align-items-center gap-2 text-decoration-none text-reset">
                    <div class="avatar-chip @yield('avatar-class')">@yield('avatar-initials')</div>
                    <span class="fw-bold" style="font-size:14px">@yield('nav-username')</span>
                </a>
            </div>
        </div>
    </nav>

    {{-- ── HERO ── --}}
    <div id="hero">
        <div class="container-fluid" style="max-width:1160px">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                {{-- Left: title + subtitle --}}
                <div>
                    @yield('hero-title')
                    <p class="hero-sub">@yield('hero-subtitle')</p>
                </div>
                {{-- Middle / Right: stats or workload --}}
                @yield('hero-stats')
                {{-- Optional CTA button --}}
                @yield('hero-cta')
            </div>
        </div>
    </div>

    {{-- ── PAGE BODY ── --}}
    <div class="container-fluid py-4 px-4" style="max-width:1160px">
        <div class="row g-4">

            {{-- ── SIDEBAR ── --}}
            <div class="col-lg-3">
                @yield('sidebar')
            </div>

            {{-- ── MAIN CONTENT ── --}}
            <div class="col-lg-9">
                @yield('content')
            </div>

        </div>
    </div>

    {{-- ── MODALS ── --}}
    @yield('modals')

    {{-- ── Scripts ── --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>

    {{-- Shared JS helpers --}}
    <script>
        /* ── Shared: tab + sidebar filter ── */
        function setFilter(val, labelMap) {
            const label = labelMap || { all: 'All Tickets' };
            $('#listTitle').text(label[val] || 'All Tickets');
            $('.tab-pill').removeClass('active').filter('[data-filter="' + val + '"]').addClass('active');
            $('#sideNav .list-group-item').removeClass('active').filter('[data-filter="' + val + '"]').addClass('active');
            $('#ticketList .ticket-card').each(function () {
                $(this).toggle(val === 'all' || $(this).data('status') === val);
            });
        }

        /* ── Shared: search ── */
        $(function () {
            $('#searchInput').on('input', function () {
                const q = $(this).val().toLowerCase();
                $('#ticketList .ticket-card').each(function () {
                    $(this).toggle($(this).text().toLowerCase().includes(q));
                });
            });

            /* ── Shared: toast ── */
            window.showToast = function (msg, type) {
                const bg = type === 'success' ? 'var(--gd)' : '#8b3a00';
                const $t = $('<div>').css({
                    position: 'fixed', bottom: '28px', right: '28px',
                    background: bg, color: 'var(--yg)',
                    fontFamily: 'Nunito,sans-serif', fontWeight: 800, fontSize: '14px',
                    padding: '14px 22px', borderRadius: '50px', zIndex: 9999,
                    boxShadow: '0 6px 24px rgba(0,0,0,.2)', opacity: 0, transition: 'opacity .3s'
                }).text(msg);
                $('body').append($t);
                setTimeout(() => $t.css('opacity', 1), 10);
                setTimeout(() => $t.css('opacity', 0), 3200);
                setTimeout(() => $t.remove(), 3600);
            };
        });
    </script>

    @yield('scripts')
    @auth
        <script>
            /* ══ BROWSER PUSH NOTIFICATIONS ══ */

            let lastNotifTime = new Date().toISOString();
            let notifPollTimer = null;

            // ── Request permission on load
            if ('Notification' in window && Notification.permission === 'default') {
                setTimeout(() => {
                    Notification.requestPermission();
                }, 3000);
            }

            // ── Show a browser notification
            function showBrowserNotif(title, body, url, tag) {
                if (!('Notification' in window)) return;
                if (Notification.permission !== 'granted') return;

                const notif = new Notification(title, {
                    body: body,
                    icon: '/favicon.ico',
                    badge: '/favicon.ico',
                    tag: tag || 'lgict',
                });

                notif.onclick = function () {
                    window.focus();
                    if (url) window.location.href = url;
                    notif.close();
                };

                setTimeout(() => notif.close(), 6000);
            }

            // ── Poll for new notifications every 30 seconds
            function pollNotifications() {
                if (document.hidden) return; // Skip if tab hidden

                fetch(`/notifications/poll?since=${encodeURIComponent(lastNotifTime)}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                })
                    .then(r => r.json())
                    .then(data => {
                        // Update the server time for next poll
                        lastNotifTime = data.server_time;

                        // Show browser notification for each new event
                        data.notifications.forEach(notif => {
                            showBrowserNotif(
                                notif.title,
                                notif.body,
                                notif.url,
                                notif.tag
                            );
                        });
                    })
                    .catch(() => { }); // Silent fail
            }

            // ── Start polling every 30 seconds
            notifPollTimer = setInterval(pollNotifications, 30000);

            // ── Pause when tab hidden, resume + instant check when visible
            document.addEventListener('visibilitychange', function () {
                if (document.hidden) {
                    clearInterval(notifPollTimer);
                } else {
                    pollNotifications();
                    notifPollTimer = setInterval(pollNotifications, 30000);
                }
            });
        </script>
    @endauth
</body>

</html>