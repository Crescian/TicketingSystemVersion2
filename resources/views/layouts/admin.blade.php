<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'IT Admin — LGICT')</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;800;900&family=Nunito+Sans:wght@400;600;700&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <style>
    /* ══════════════════════════════════════════
     ADMIN DESIGN TOKENS
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
      --rd: #8b1a1a;
      --rdl: #fde8e8;
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

    /* ── Admin Topbar (dark, not cream) ── */
    #topbar {
      background: var(--gd);
      font-size: 13px;
      border-bottom: 2px solid rgba(200, 230, 60, .25);
    }

    #topbar span {
      color: rgba(255, 255, 255, .6);
    }

    #topbar strong {
      color: var(--yg);
      font-weight: 800;
    }

    .btn-signout {
      background: rgba(255, 255, 255, .12);
      color: #fff !important;
      padding: 4px 16px;
      border-radius: 20px;
      font-weight: 700;
      font-size: 12px;
      text-decoration: none;
      border: 1px solid rgba(255, 255, 255, .2);
      transition: background .2s;
    }

    .btn-signout:hover {
      background: rgba(255, 255, 255, .22);
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

    /* Admin role badge — red gradient */
    .role-badge-admin {
      background: linear-gradient(135deg, var(--rd), #c0392b);
      color: #fff;
      font-size: 11px;
      font-weight: 800;
      padding: 4px 14px;
      border-radius: 20px;
      text-transform: uppercase;
      letter-spacing: .5px;
      box-shadow: 0 2px 8px rgba(139, 26, 26, .3);
    }

    .avatar-chip-admin {
      width: 36px;
      height: 36px;
      background: linear-gradient(135deg, var(--rd), #c0392b);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-family: 'Nunito', sans-serif;
      font-weight: 900;
      font-size: 13px;
      box-shadow: 0 2px 8px rgba(139, 26, 26, .3);
    }

    /* ── Admin Hero (red-dark gradient) ── */
    #hero {
      background: linear-gradient(135deg, #1a0a0a 0%, #3c1a1a 40%, #1a3c1a 100%);
      background-image:
        radial-gradient(circle at 75% 40%, rgba(139, 26, 26, .5) 0%, transparent 50%),
        radial-gradient(circle at 20% 80%, rgba(200, 230, 60, .08) 0%, transparent 40%),
        linear-gradient(135deg, #1a0a0a 0%, #3c1a1a 40%, #1a3c1a 100%);
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

    #hero h1 strong {
      color: #ff8888;
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
      display: block;
      line-height: 1;
    }

    .stat-pill .lbl {
      font-size: 10px;
      color: rgba(255, 255, 255, .5);
      text-transform: uppercase;
      letter-spacing: .5px;
      margin-top: 3px;
      display: block;
    }

    .stat-pill.esc .num {
      color: #ff8888;
    }

    .stat-pill.open .num {
      color: #fde8a0;
    }

    .stat-pill.done .num {
      color: var(--yg);
    }

    .stat-pill.all .num {
      color: #fff;
    }

    /* ── Sidebar shared ── */
    .sidebar-card {
      background: #fff;
      border-radius: 16px;
      border: 1.5px solid var(--bd);
      overflow: hidden;
    }

    .sidebar-head {
      padding: 12px 18px;
      font-family: 'Nunito', sans-serif;
      font-weight: 800;
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: .5px;
    }

    .sidebar-head.red {
      background: linear-gradient(135deg, var(--rd), #c0392b);
      color: var(--yg);
    }

    .sidebar-head.dark {
      background: var(--gd);
      color: var(--yg);
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

    .badge-count {
      font-size: 11px;
      border-radius: 20px;
      padding: 2px 9px;
      margin-left: auto;
      font-weight: 800;
    }

    .badge-count.red {
      background: var(--rdl);
      color: var(--rd);
    }

    .badge-count.green {
      background: var(--ygl);
      color: var(--gm);
    }

    .badge-count.dark {
      background: var(--gd);
      color: var(--yg);
    }

    /* Tech panel rows */
    .tech-row {
      padding: 10px 16px;
      border-bottom: 1px solid var(--bd);
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 13px;
    }

    .tech-row:last-child {
      border-bottom: none;
    }

    .tech-av {
      width: 30px;
      height: 30px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 11px;
      font-weight: 900;
      font-family: 'Nunito', sans-serif;
      flex-shrink: 0;
    }

    .tech-av.normal {
      background: var(--gd);
      color: var(--yg);
    }

    .tech-av.admin {
      background: linear-gradient(135deg, var(--rd), #c0392b);
      color: #fff;
    }

    .tech-name {
      font-weight: 700;
      font-size: 13px;
    }

    .tech-load {
      font-size: 11px;
      color: var(--tm);
    }

    .avail-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      flex-shrink: 0;
      margin-left: auto;
    }

    .avail-dot.free {
      background: #4a7c4a;
    }

    .avail-dot.busy {
      background: #f5c842;
    }

    .avail-dot.full {
      background: #e24b4a;
    }

    /* System stats */
    .sys-stat {
      padding: 10px 16px;
      border-bottom: 1px solid var(--bd);
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 13px;
    }

    .sys-stat:last-child {
      border-bottom: none;
    }

    .sys-val {
      font-family: 'Nunito', sans-serif;
      font-weight: 900;
      font-size: 17px;
    }

    .sys-val.warn {
      color: #8b3a00;
    }

    .sys-val.danger {
      color: var(--rd);
    }

    .sys-val.ok {
      color: var(--gm);
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
      width: 170px;
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

    .tab-pill.red-pill {
      border-color: #f0c0c0;
      color: var(--rd);
      background: var(--rdl);
    }

    .tab-pill.red-pill.active {
      background: var(--rd);
      color: #fff;
      border-color: var(--rd);
    }

    /* ── Ticket cards ── */
    .ticket-card {
      background: #fff;
      border-radius: 16px;
      border: 1.5px solid var(--bd);
      transition: transform .2s, box-shadow .2s;
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
      width: 5px;
      border-radius: 5px 0 0 5px;
    }

    .ticket-card.escalated::before {
      background: var(--rd);
    }

    .ticket-card.reassigned::before {
      background: #d85a30;
    }

    .ticket-card.admin-wip::before {
      background: var(--yg);
    }

    .ticket-card.resolved::before {
      background: var(--gl);
      opacity: .5;
    }

    /* Escalation level badge */
    .esc-level {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      background: var(--rdl);
      border: 1px solid #f0c0c0;
      color: var(--rd);
      font-family: 'Nunito', sans-serif;
      font-weight: 900;
      font-size: 11px;
      padding: 3px 10px;
      border-radius: 20px;
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

    .bs-esc {
      background: var(--rdl);
      color: var(--rd);
    }

    .bs-reassigned {
      background: #fde8d0;
      color: #7a3a00;
    }

    .bs-admin-wip {
      background: var(--ygl);
      color: var(--gm);
    }

    .bs-resolved {
      background: #e8f5ee;
      color: #1a5a3a;
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
      border-radius: 20px;
      padding: 3px 10px 3px 4px;
      font-size: 12px;
      font-weight: 700;
    }

    .tech-chip.prev {
      background: #fde8d0;
      color: #7a3a00;
    }

    .tech-chip.curr {
      background: var(--ygl);
      color: var(--gd);
    }

    .tc-av {
      width: 22px;
      height: 22px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 10px;
      font-weight: 900;
      font-family: 'Nunito', sans-serif;
    }

    .tc-av.normal {
      background: var(--gd);
      color: var(--yg);
    }

    .tc-av.admin {
      background: var(--rd);
      color: #fff;
    }

    /* Escalation history timeline */
    .esc-timeline {
      background: var(--rdl);
      border-radius: 10px;
      padding: 12px 14px;
    }

    .etl-item {
      display: flex;
      gap: 10px;
      font-size: 12px;
      padding-bottom: 8px;
    }

    .etl-item:last-child {
      padding-bottom: 0;
    }

    .etl-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: var(--rd);
      flex-shrink: 0;
      margin-top: 4px;
    }

    .etl-time {
      color: var(--rd);
      font-weight: 700;
      min-width: 70px;
    }

    .etl-text {
      color: #5a1a1a;
      font-weight: 600;
    }

    /* ── Action buttons ── */
    .btn-reassign-a {
      background: var(--gd);
      color: var(--yg);
      font-family: 'Nunito', sans-serif;
      font-weight: 800;
      font-size: 12px;
      padding: 7px 16px;
      border-radius: 20px;
      border: none;
      cursor: pointer;
      transition: all .2s;
    }

    .btn-reassign-a:hover {
      background: var(--gm);
    }

    .btn-takeover {
      background: var(--rd);
      color: #fff;
      font-family: 'Nunito', sans-serif;
      font-weight: 800;
      font-size: 12px;
      padding: 7px 16px;
      border-radius: 20px;
      border: none;
      cursor: pointer;
      transition: all .2s;
    }

    .btn-takeover:hover {
      background: #a02020;
    }

    .btn-resolve-a {
      background: #e8f5ee;
      color: #1a5a3a;
      font-family: 'Nunito', sans-serif;
      font-weight: 800;
      font-size: 12px;
      padding: 7px 16px;
      border-radius: 20px;
      border: 1.5px solid #a8ddc0;
      cursor: pointer;
      transition: all .2s;
    }

    .btn-resolve-a:hover {
      background: #c8ead8;
    }

    .btn-view-hist {
      background: none;
      color: var(--tm);
      font-family: 'Nunito', sans-serif;
      font-weight: 700;
      font-size: 12px;
      padding: 7px 14px;
      border-radius: 20px;
      border: 1.5px solid var(--bd);
      cursor: pointer;
      transition: all .2s;
    }

    .btn-view-hist:hover {
      border-color: var(--gl);
      color: var(--gd);
    }

    /* ── Modals ── */
    .modal-content {
      border-radius: 20px;
      overflow: hidden;
      border: none;
    }

    .modal-hdr-red {
      background: linear-gradient(135deg, var(--rd), #b02020);
      padding: 20px 28px;
    }

    .modal-hdr-dark {
      background: var(--gd);
      padding: 20px 28px;
    }

    .modal-hdr-red h5,
    .modal-hdr-dark h5 {
      font-family: 'Nunito', sans-serif;
      font-weight: 900;
      font-size: 19px;
      color: #fff;
      text-transform: uppercase;
      letter-spacing: -.3px;
      margin: 0;
    }

    .modal-hdr-red h5 em,
    .modal-hdr-dark h5 em {
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

    .info-box-red {
      background: var(--rdl);
      border-radius: 10px;
      font-size: 13px;
      color: var(--rd);
    }

    .info-box-green {
      background: var(--ygl);
      border-radius: 10px;
      font-size: 13px;
      color: var(--gd);
    }

    /* Tech select in reassign modal */
    .tech-select-option {
      border: 1.5px solid var(--bd);
      border-radius: 12px;
      padding: 11px 14px;
      cursor: pointer;
      transition: all .2s;
      background: var(--cr);
    }

    .tech-select-option:hover {
      border-color: var(--gl);
      background: var(--ygl);
    }

    .tech-select-option.selected {
      border-color: var(--gd);
      background: var(--ygl);
      box-shadow: 0 0 0 2px var(--yg);
    }

    .ts-name {
      font-family: 'Nunito', sans-serif;
      font-weight: 800;
      font-size: 14px;
    }

    .ts-load {
      font-size: 12px;
      color: var(--tm);
    }

    .load-bar-wrap {
      height: 5px;
      background: var(--bd);
      border-radius: 4px;
      margin-top: 6px;
    }

    .load-bar {
      height: 5px;
      border-radius: 4px;
      background: var(--gl);
    }

    .load-bar.busy {
      background: #f5c842;
    }

    .load-bar.full {
      background: #e24b4a;
    }

    /* History timeline in modal */
    .hist-item {
      display: flex;
      gap: 14px;
      padding-bottom: 14px;
      position: relative;
    }

    .hist-item::after {
      content: '';
      position: absolute;
      left: 15px;
      top: 28px;
      bottom: 0;
      width: 2px;
      background: var(--bd);
    }

    .hist-item:last-child::after {
      display: none;
    }

    .hist-item:last-child {
      padding-bottom: 0;
    }

    .hist-icon {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 13px;
      flex-shrink: 0;
      z-index: 1;
    }

    .hist-icon.assigned {
      background: var(--ygl);
      color: var(--gm);
    }

    .hist-icon.accepted {
      background: #fff4cc;
      color: #7a5a00;
    }

    .hist-icon.working {
      background: var(--ygl);
      color: var(--gm);
    }

    .hist-icon.escalated {
      background: var(--rdl);
      color: var(--rd);
    }

    .hist-icon.resolved {
      background: #e8f5ee;
      color: #1a5a3a;
    }

    .hist-time {
      font-size: 11px;
      color: var(--tm);
      font-weight: 600;
    }

    .hist-title {
      font-family: 'Nunito', sans-serif;
      font-weight: 800;
      font-size: 14px;
      color: var(--gd);
    }

    .hist-desc {
      font-size: 12px;
      color: var(--tm);
      margin-top: 2px;
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

    .btn-confirm.red {
      background: var(--rd);
      color: #fff;
    }

    .btn-confirm.red:hover {
      background: #a02020;
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

    .btn-chat {
      background: #e8eeff;
      color: #2a4ab0;
      font-family: 'Nunito', sans-serif;
      font-weight: 800;
      font-size: 12px;
      padding: 7px 16px;
      border-radius: 20px;
      border: 1.5px solid #b8c8ff;
      cursor: pointer;
      transition: all .2s;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }

    .btn-chat:hover {
      background: #d0dcff;
      border-color: #8898dd;
    }

    .chat-count-badge {
      background: #e24b4a;
      color: #fff;
      font-size: 10px;
      font-weight: 900;
      border-radius: 20px;
      padding: 1px 6px;
      font-family: 'Nunito', sans-serif;
      min-width: 18px;
      text-align: center;
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

  {{-- ── ADMIN TOPBAR (dark) ── --}}
  <div id="topbar" class="d-flex justify-content-between align-items-center px-4 py-1">
    <span><strong>IT Admin Console</strong> — Full system access</span>
    <div class="d-flex align-items-center gap-3">
      <span style="color:rgba(255,255,255,.55);font-size:12px">
        <i class="bi bi-circle-fill me-1" style="color:#4aff4a;font-size:8px"></i>All systems operational
      </span>
      @auth
        <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
          class="btn-signout">Sign Out</a>
        <form id="logout-form" method="POST" action="{{ route('logout') }}" class="d-none">@csrf</form>
      @else
        <a href="{{ route('login') }}" class="btn-signout">Sign In</a>
      @endauth
    </div>
  </div>

  {{-- ── NAVBAR ── --}}
  <nav class="navbar sticky-top px-4 py-0">
    <div class="container-fluid px-0">
      <a class="navbar-brand" href="#">LG<span>ICT</span></a>
      <div class="ms-auto d-flex align-items-center gap-2">
        @yield('nav-role-badge')
        <div class="avatar-chip-admin">@yield('avatar-initials', 'MA')</div>
        <span class="fw-bold" style="font-size:14px">@yield('nav-username', 'M. Aquino')</span>
      </div>
    </div>
  </nav>

  {{-- ── HERO ── --}}
  <div id="hero">
    <div class="container-fluid" style="max-width:1160px">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
          @yield('hero-title')
          <p class="hero-sub">@yield('hero-subtitle')</p>
        </div>
        @yield('hero-stats')
        @yield('hero-cta')
      </div>
    </div>
  </div>

  {{-- ── PAGE BODY ── --}}
  <div class="container-fluid py-4 px-4" style="max-width:1160px">
    <div class="row g-4">

      {{-- Sidebar --}}
      <div class="col-lg-3">
        @yield('sidebar')
      </div>

      {{-- Main content --}}
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

  <script>
    /* ── Shared: filter ── */
    function setFilter(val, labelMap) {
      const label = labelMap || { all: 'All Tickets' };
      $('#listTitle').text(label[val] || 'All Tickets');
      $('.tab-pill').removeClass('active').filter('[data-filter="' + val + '"]').addClass('active');
      $('#sideNav .list-group-item').removeClass('active').filter('[data-filter="' + val + '"]').addClass('active');
      $('#ticketList .ticket-card').each(function () {
        $(this).toggle(val === 'all' || $(this).data('status') === val);
      });
    }

    $(function () {
      /* ── Shared: search ── */
      $('#searchInput').on('input', function () {
        const q = $(this).val().toLowerCase();
        $('#ticketList .ticket-card').each(function () {
          $(this).toggle($(this).text().toLowerCase().includes(q));
        });
      });

      /* ── Shared: tech select in modals ── */
      $(document).on('click', '.tech-select-option', function () {
        $(this).siblings().removeClass('selected');
        $(this).addClass('selected');
      });

      /* ── Shared: toast ── */
      window.showToast = function (msg, type) {
        const bg = type === 'success' ? 'var(--gd)' : 'var(--rd)';
        const $t = $('<div>').css({
          position: 'fixed', bottom: '28px', right: '28px',
          background: bg, color: 'var(--yg)',
          fontFamily: 'Nunito,sans-serif', fontWeight: 800, fontSize: '14px',
          padding: '14px 22px', borderRadius: '50px', zIndex: 9999,
          boxShadow: '0 6px 24px rgba(0,0,0,.25)', opacity: 0,
          transition: 'opacity .3s', maxWidth: '420px'
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