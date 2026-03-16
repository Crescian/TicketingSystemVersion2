@extends('layouts.app')

@section('title', 'My Work Queue — LGICT')

{{-- ── Nav ── --}}
@section('nav-role-badge')
  <span class="role-badge dark"><i class="bi bi-tools me-1"></i>IT Technician</span>
@endsection
@section('avatar-class', '')
@section('avatar-initials', 'RB')
@section('nav-username', 'R. Buenaventura')

{{-- ── Hero ── --}}
@section('hero-title')
  <h1>MY <em>WORK QUEUE</em></h1>
@endsection
@section('hero-subtitle', 'Accept, work on, and resolve tickets assigned to you.')

@section('hero-stats')
  {{-- Workload card --}}
  <div class="workload-card">
    <div class="wl-label">My workload today</div>
    <div class="wl-bar-wrap"><div class="wl-bar" style="width:60%"></div></div>
    <div class="wl-counts">
      <div class="wl-item"><span class="wn">1</span><span class="wl">New</span></div>
      <div class="wl-item"><span class="wn">2</span><span class="wl">Active</span></div>
      <div class="wl-item"><span class="wn">5</span><span class="wl">Done today</span></div>
    </div>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <div class="stat-pill danger"><span class="num">1</span><span class="lbl">New</span></div>
    <div class="stat-pill warn"> <span class="num">2</span><span class="lbl">In Progress</span></div>
    <div class="stat-pill">     <span class="num">5</span><span class="lbl">Resolved</span></div>
  </div>
@endsection

{{-- ── Page-specific styles ── --}}
@section('styles')
  /* Workload card */
  .workload-card { background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.14); border-radius:14px; padding:16px 20px; min-width:220px; }
  .wl-label { font-size:11px; color:rgba(255,255,255,.55); text-transform:uppercase; letter-spacing:.5px; margin-bottom:8px; }
  .wl-bar-wrap { height:8px; background:rgba(255,255,255,.15); border-radius:4px; margin-bottom:8px; }
  .wl-bar  { height:8px; border-radius:4px; background:var(--yg); }
  .wl-counts { display:flex; gap:16px; }
  .wl-item .wn { font-family:'Nunito',sans-serif; font-weight:900; font-size:22px; color:var(--yg); display:block; line-height:1; }
  .wl-item .wl { font-size:10px; color:rgba(255,255,255,.5); text-transform:uppercase; letter-spacing:.4px; }

  /* Progress strip */
  .progress-strip { display:flex; align-items:center; margin-bottom:14px; }
  .ps-step { flex:1; text-align:center; }
  .ps-dot { width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 4px; font-size:12px; font-weight:900; font-family:'Nunito',sans-serif; border:2px solid var(--bd); background:#fff; color:var(--tm); transition:all .3s; }
  .ps-dot.done   { background:var(--gd); color:var(--yg); border-color:var(--gd); }
  .ps-dot.active { background:var(--yg); color:var(--gd); border-color:var(--yg); }
  .ps-lbl { font-size:10px; font-weight:700; color:var(--tm); text-transform:uppercase; letter-spacing:.3px; }
  .ps-lbl.done   { color:var(--gd); }
  .ps-lbl.active { color:var(--gd); font-weight:800; }
  .ps-line { flex:1; height:2px; background:var(--bd); transition:background .3s; }
  .ps-line.done  { background:var(--gd); }

  /* Workflow checklist */
  .checklist-item { padding:10px 16px; border-bottom:1px solid var(--bd); display:flex; align-items:flex-start; gap:10px; font-size:13px; }
  .checklist-item:last-child { border-bottom:none; }
  .ck-icon { width:22px; height:22px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:11px; flex-shrink:0; margin-top:1px; }
  .ck-icon.done    { background:var(--ygl); color:var(--gd); }
  .ck-icon.active  { background:var(--gd); color:var(--yg); }
  .ck-icon.pending { background:var(--bd); color:var(--tm); }
  .ck-text { font-weight:600; line-height:1.4; }
  .ck-sub  { font-size:11px; color:var(--tm); margin-top:2px; }

  /* Timeline */
  .timeline { border-left:2px solid var(--bd); padding-left:14px; margin-top:4px; }
  .tl-item  { position:relative; padding-bottom:10px; font-size:12px; color:var(--tm); }
  .tl-item:last-child { padding-bottom:0; }
  .tl-item::before { content:''; position:absolute; left:-19px; top:4px; width:8px; height:8px; border-radius:50%; background:var(--bd); border:2px solid #fff; }
  .tl-item.done::before   { background:var(--gl); }
  .tl-item.active::before { background:var(--yg); }
  .tl-time { font-size:11px; color:var(--tm); margin-bottom:1px; }
  .tl-text { font-weight:600; color:var(--gd); }

  /* Action buttons */
  .btn-accept    { background:var(--gd); color:var(--yg); font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; padding:7px 16px; border-radius:20px; border:none; cursor:pointer; transition:all .2s; }
  .btn-accept:hover { background:var(--gm); }
  .btn-start     { background:var(--yg); color:var(--gd); font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; padding:7px 16px; border-radius:20px; border:none; cursor:pointer; transition:all .2s; }
  .btn-start:hover { background:var(--ygd); }
  .btn-update    { background:var(--ygl); color:var(--gd); font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; padding:7px 16px; border-radius:20px; border:1.5px solid var(--bd); cursor:pointer; transition:all .2s; }
  .btn-update:hover { background:#d8eda0; border-color:var(--gl); }
  .btn-resolve-t { background:#e8f5ee; color:#1a5a3a; font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; padding:7px 16px; border-radius:20px; border:1.5px solid #a8ddc0; cursor:pointer; transition:all .2s; }
  .btn-resolve-t:hover { background:#c8ead8; }
  .btn-escalate-t{ background:#fde8e8; color:#8b1a1a; font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; padding:7px 16px; border-radius:20px; border:1.5px solid #f0c0c0; cursor:pointer; transition:all .2s; }
  .btn-escalate-t:hover { background:#f8c8c8; }
  .btn-decline   { background:none; color:var(--tm); font-family:'Nunito',sans-serif; font-weight:700; font-size:12px; padding:7px 14px; border-radius:20px; border:1.5px solid var(--bd); cursor:pointer; transition:all .2s; }
  .btn-decline:hover { border-color:#e24b4a; color:#8b1a1a; }

  /* Status opts inside update modal */
  .status-opts { display:flex; flex-direction:column; gap:8px; }
  .status-opt { border:1.5px solid var(--bd); border-radius:12px; padding:12px 16px; cursor:pointer; transition:all .2s; background:var(--cr); display:flex; align-items:center; gap:12px; }
  .status-opt:hover { border-color:var(--gl); background:var(--ygl); }
  .status-opt.selected { border-color:var(--gd); background:var(--ygl); box-shadow:0 0 0 2px var(--yg); }
  .status-opt .so-dot   { width:12px; height:12px; border-radius:50%; flex-shrink:0; }
  .status-opt .so-label { font-weight:800; font-size:14px; font-family:'Nunito',sans-serif; }
  .status-opt .so-desc  { font-size:12px; color:var(--tm); }
@endsection

{{-- ══ SIDEBAR ══ --}}
@section('sidebar')
  <div class="sidebar-card mb-3">
    <div class="sidebar-head">My Queue</div>
    <ul class="list-group sidebar-menu rounded-0" id="sideNav">
      <li class="list-group-item active" data-filter="all"><i class="bi bi-grid"></i> All tickets<span class="badge-count">8</span></li>
      <li class="list-group-item" data-filter="new-assigned"><i class="bi bi-bell"></i> New assigned<span class="badge-count red">1</span></li>
      <li class="list-group-item" data-filter="accepted"><i class="bi bi-hourglass-split"></i> Accepted<span class="badge-count">1</span></li>
      <li class="list-group-item" data-filter="in-progress"><i class="bi bi-arrow-repeat"></i> In Progress<span class="badge-count">1</span></li>
      <li class="list-group-item" data-filter="escalated"><i class="bi bi-exclamation-triangle"></i> Escalated<span class="badge-count red">1</span></li>
      <li class="list-group-item" data-filter="resolved"><i class="bi bi-check-circle"></i> Resolved<span class="badge-count">5</span></li>
    </ul>
  </div>

  <div class="sidebar-card mb-3">
    <div class="sidebar-head">Workflow Steps</div>
    <div>
      <div class="checklist-item"><div class="ck-icon done"><i class="bi bi-check"></i></div><div><div class="ck-text">Receive assignment</div><div class="ck-sub">Helpdesk assigns ticket to you</div></div></div>
      <div class="checklist-item"><div class="ck-icon active"><i class="bi bi-arrow-right"></i></div><div><div class="ck-text">Accept request</div><div class="ck-sub">Review and accept the ticket</div></div></div>
      <div class="checklist-item"><div class="ck-icon pending">3</div><div><div class="ck-text">Work on ticket</div><div class="ck-sub">Fix the hardware / software issue</div></div></div>
      <div class="checklist-item"><div class="ck-icon pending">4</div><div><div class="ck-text">Resolve or escalate</div><div class="ck-sub">Close if done, escalate if not</div></div></div>
      <div class="checklist-item"><div class="ck-icon pending">5</div><div><div class="ck-text">Helpdesk notifies customer</div><div class="ck-sub">Customer confirms resolution</div></div></div>
    </div>
  </div>

  <div class="sidebar-card">
    <div class="sidebar-head">This Week</div>
    <div class="p-3 d-flex flex-column gap-2">
      <div class="d-flex justify-content-between"><span style="font-size:13px;font-weight:600;color:var(--tm)">Tickets resolved</span><span class="font-brand fw-900" style="font-size:18px">18</span></div>
      <div class="d-flex justify-content-between"><span style="font-size:13px;font-weight:600;color:var(--tm)">Avg resolution time</span><span class="font-brand fw-900" style="font-size:18px">1.8h</span></div>
      <div class="d-flex justify-content-between"><span style="font-size:13px;font-weight:600;color:var(--tm)">Escalated</span><span class="font-brand fw-900" style="font-size:18px;color:#8b1a1a">2</span></div>
      <div class="d-flex justify-content-between"><span style="font-size:13px;font-weight:600;color:var(--tm)">Customer rating</span><span class="font-brand fw-900" style="font-size:18px">4.8 ⭐</span></div>
    </div>
  </div>
@endsection

{{-- ══ MAIN CONTENT ══ --}}
@section('content')
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <span class="font-brand fw-900" style="font-size:22px" id="listTitle">All Tickets</span>
    <div class="d-flex gap-2 flex-wrap">
      <div class="search-wrap"><i class="bi bi-search" style="color:var(--tm)"></i><input type="text" id="searchInput" placeholder="Search tickets…"></div>
      <select class="sort-select"><option>Priority first</option><option>Newest first</option><option>Oldest first</option></select>
    </div>
  </div>

  <div class="d-flex flex-wrap gap-2 mb-3" id="tabRow">
    <span class="tab-pill active" data-filter="all">All (8)</span>
    <span class="tab-pill" data-filter="new-assigned">New (1)</span>
    <span class="tab-pill" data-filter="accepted">Accepted (1)</span>
    <span class="tab-pill" data-filter="in-progress">In Progress (1)</span>
    <span class="tab-pill" data-filter="escalated">Escalated (1)</span>
    <span class="tab-pill" data-filter="resolved">Resolved (5)</span>
  </div>

  <div class="d-flex flex-column gap-3" id="ticketList">

    {{-- Card 1: NEW ASSIGNED --}}
    <div class="ticket-card new-assigned p-3" data-status="new-assigned" data-priority="high">
      <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <span class="ticket-id">#TKT-2025-0058</span>
          <span class="badge-type">Hardware</span>
          <span class="meta-item"><span class="priority-dot pri-high"></span> High priority</span>
        </div>
        <span class="badge-status badge-new"><i class="bi bi-bell me-1"></i>New — Action Required</span>
      </div>
      <div class="progress-strip mb-3">
        <div class="ps-step"><div class="ps-dot done"><i class="bi bi-check"></i></div><div class="ps-lbl done">Assigned</div></div>
        <div class="ps-line"></div>
        <div class="ps-step"><div class="ps-dot active">2</div><div class="ps-lbl active">Accept</div></div>
        <div class="ps-line"></div>
        <div class="ps-step"><div class="ps-dot">3</div><div class="ps-lbl">Working</div></div>
        <div class="ps-line"></div>
        <div class="ps-step"><div class="ps-dot">4</div><div class="ps-lbl">Resolve</div></div>
      </div>
      <div class="ticket-title mb-1">Laptop screen cracked after accidental drop</div>
      <div class="ticket-desc mb-3">Screen shattered at bottom-left corner. Device powers on but display is unusable. Customer needs urgent replacement for daily work.</div>
      <div class="d-flex flex-wrap gap-3 mb-3">
        <span class="meta-item"><i class="bi bi-person"></i> Juan Dela Cruz</span>
        <span class="meta-item"><i class="bi bi-building"></i> Finance</span>
        <span class="meta-item"><i class="bi bi-laptop"></i> Laptop — LT-00432</span>
        <span class="meta-item"><i class="bi bi-geo-alt"></i> Floor 3, Desk C5</span>
        <span class="meta-item"><i class="bi bi-calendar3"></i> Assigned 30 min ago</span>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <button class="btn-accept" onclick="openAcceptModal('TKT-2025-0058')"><i class="bi bi-check-circle me-1"></i>Accept Ticket</button>
        <button class="btn-decline" onclick="openDeclineModal('TKT-2025-0058')"><i class="bi bi-x-circle me-1"></i>Decline</button>
      </div>
    </div>

    {{-- Card 2: IN PROGRESS --}}
    <div class="ticket-card in-progress p-3" data-status="in-progress" data-priority="medium">
      <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <span class="ticket-id">#TKT-2025-0051</span>
          <span class="badge-type">Software</span>
          <span class="meta-item"><span class="priority-dot pri-medium"></span> Medium priority</span>
        </div>
        <span class="badge-status badge-in-progress"><i class="bi bi-arrow-repeat me-1"></i>In Progress</span>
      </div>
      <div class="progress-strip mb-3">
        <div class="ps-step"><div class="ps-dot done"><i class="bi bi-check"></i></div><div class="ps-lbl done">Assigned</div></div>
        <div class="ps-line done"></div>
        <div class="ps-step"><div class="ps-dot done"><i class="bi bi-check"></i></div><div class="ps-lbl done">Accept</div></div>
        <div class="ps-line done"></div>
        <div class="ps-step"><div class="ps-dot done"><i class="bi bi-check"></i></div><div class="ps-lbl done">Working</div></div>
        <div class="ps-line"></div>
        <div class="ps-step"><div class="ps-dot active">4</div><div class="ps-lbl active">Resolve</div></div>
      </div>
      <div class="ticket-title mb-1">MS Teams stuck on splash screen</div>
      <div class="ticket-desc mb-2">Microsoft Teams fails to load past splash screen. Reinstall attempted with no success.</div>
      <div class="timeline mb-3">
        <div class="tl-item done"><div class="tl-time">10:15 AM</div><div class="tl-text">Ticket accepted and reviewed</div></div>
        <div class="tl-item done"><div class="tl-time">10:30 AM</div><div class="tl-text">Attempted reinstall of Teams — no change</div></div>
        <div class="tl-item active"><div class="tl-time">Now</div><div class="tl-text">Checking Office 365 license validity and tenant config</div></div>
      </div>
      <div class="d-flex flex-wrap gap-3 mb-3">
        <span class="meta-item"><i class="bi bi-person"></i> Cris Torres</span>
        <span class="meta-item"><i class="bi bi-building"></i> Marketing</span>
        <span class="meta-item"><i class="bi bi-clock"></i> Working for 1h 20m</span>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <button class="btn-update" onclick="openUpdateModal('TKT-2025-0051','update')"><i class="bi bi-pencil me-1"></i>Add Update</button>
        <button class="btn-resolve-t" onclick="openResolveModal('TKT-2025-0051')"><i class="bi bi-check-circle me-1"></i>Mark Resolved</button>
        <button class="btn-escalate-t" onclick="openEscModal('TKT-2025-0051')"><i class="bi bi-exclamation-triangle me-1"></i>Escalate</button>
      </div>
    </div>

    {{-- Card 3: ESCALATED --}}
    <div class="ticket-card escalated p-3" data-status="escalated" data-priority="high">
      <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <span class="ticket-id">#TKT-2025-0048</span>
          <span class="badge-type">Hardware</span>
          <span class="meta-item"><span class="priority-dot pri-high"></span> High priority</span>
        </div>
        <span class="badge-status badge-escalated"><i class="bi bi-exclamation-triangle me-1"></i>Escalated</span>
      </div>
      <div class="ticket-title mb-1">Laptop won't turn on after Windows update</div>
      <div class="ticket-desc mb-2">ThinkPad X1 stuck on black screen post-update. Issue beyond tech scope.</div>
      <div class="esc-banner p-2 mb-3"><i class="bi bi-exclamation-triangle-fill me-1"></i>Escalated to IT Admin — awaiting admin action. No further action required from you on this ticket.</div>
      <div class="d-flex flex-wrap gap-3">
        <span class="meta-item"><i class="bi bi-person"></i> Juan Dela Cruz</span>
        <span class="meta-item"><i class="bi bi-calendar3"></i> Escalated 6 hrs ago</span>
        <span class="meta-item"><i class="bi bi-shield-exclamation" style="color:#8b1a1a"></i><span style="color:#8b1a1a;font-weight:700">IT Admin assigned</span></span>
      </div>
    </div>

    {{-- Card 4: RESOLVED --}}
    <div class="ticket-card resolved p-3" data-status="resolved" data-priority="low">
      <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <span class="ticket-id">#TKT-2025-0040</span>
          <span class="badge-type">Account</span>
        </div>
        <span class="badge-status badge-resolved"><i class="bi bi-check-circle me-1"></i>Resolved</span>
      </div>
      <div class="ticket-title mb-1">Password reset for MS365 account</div>
      <div class="ticket-desc mb-2">Locked out of MS365. Reset performed via admin portal — access restored within 20 minutes.</div>
      <div class="d-flex flex-wrap gap-3">
        <span class="meta-item"><i class="bi bi-person"></i> Lea Cruz</span>
        <span class="meta-item"><i class="bi bi-clock"></i> Resolved in 2 hrs</span>
        <span class="meta-item"><i class="bi bi-calendar3"></i> Mar 10, 2025</span>
        <span class="meta-item"><i class="bi bi-star-fill" style="color:#f5c842"></i> 5 / 5 feedback</span>
      </div>
    </div>

  </div>{{-- /ticketList --}}
@endsection

{{-- ══ MODALS ══ --}}
@section('modals')

{{-- Accept modal --}}
<div class="modal fade" id="acceptModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header-gd d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Accept <em>Ticket</em></h5><button class="btn-close-w" data-bs-dismiss="modal">✕</button>
      </div>
      <div class="modal-body px-4 py-4">
        <div class="info-box-green p-3 mb-3"><i class="bi bi-check-circle me-1"></i>Accepting <strong id="acceptRef">#TKT-2025-0000</strong>. Status will update to <strong>Accepted</strong> and helpdesk will be notified.</div>
        <div class="mb-3"><label class="form-label">Estimated completion time</label><select class="form-select"><option>Within 1 hour</option><option>1 – 2 hours</option><option>Half day</option><option>Full day</option><option>Requires parts / escalation</option></select></div>
        <div><label class="form-label">Initial notes (optional)</label><textarea class="form-control" rows="2" placeholder="Any initial observations or plan of action…"></textarea></div>
      </div>
      <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
        <button class="btn-cancel-modal" data-bs-dismiss="modal">Cancel</button>
        <button class="btn-confirm" id="btnConfirmAccept"><i class="bi bi-check-circle me-1"></i>Accept Ticket</button>
      </div>
    </div>
  </div>
</div>

{{-- Decline modal --}}
<div class="modal fade" id="declineModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header-gd d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Decline <em>Ticket</em></h5><button class="btn-close-w" data-bs-dismiss="modal">✕</button>
      </div>
      <div class="modal-body px-4 py-4">
        <div class="info-box-red p-3 mb-3"><i class="bi bi-exclamation-triangle me-1"></i>Declining <strong id="declineRef">#TKT-2025-0000</strong> will return it to the helpdesk queue for reassignment.</div>
        <div><label class="form-label">Reason for declining <span class="text-danger">*</span></label><select class="form-select"><option value="">Select a reason…</option><option>At full capacity</option><option>Outside my technical expertise</option><option>Requires hardware parts not available</option><option>Conflict of schedule</option><option>Other</option></select></div>
      </div>
      <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
        <button class="btn-cancel-modal" data-bs-dismiss="modal">Cancel</button>
        <button class="btn-confirm" style="background:#8b1a1a" id="btnConfirmDecline"><i class="bi bi-x-circle me-1"></i>Decline Ticket</button>
      </div>
    </div>
  </div>
</div>

{{-- Update modal --}}
<div class="modal fade" id="updateModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header-gd d-flex align-items-center justify-content-between">
        <h5 class="mb-0" id="updateModalTitle">Update <em>Status</em></h5><button class="btn-close-w" data-bs-dismiss="modal">✕</button>
      </div>
      <div class="modal-body px-4 py-4">
        <p style="font-size:13px;color:var(--tm)" class="mb-3">Ticket <strong id="updateRef" style="color:var(--gd)">#TKT-2025-0000</strong> — Log your progress below.</p>
        <div class="mb-3"><label class="form-label">What have you done so far?</label><textarea class="form-control" rows="3" placeholder="Describe the steps you've taken, findings, or current status…"></textarea></div>
        <div class="mb-1"><label class="form-label">Current status</label>
          <div class="status-opts">
            <div class="status-opt" data-val="investigating"><span class="so-dot" style="background:#f5c842"></span><div><div class="so-label">Investigating</div><div class="so-desc">Still diagnosing the root cause</div></div></div>
            <div class="status-opt selected" data-val="in-progress"><span class="so-dot" style="background:var(--yg)"></span><div><div class="so-label">Actively working</div><div class="so-desc">Fix is underway</div></div></div>
            <div class="status-opt" data-val="waiting-parts"><span class="so-dot" style="background:#d85a30"></span><div><div class="so-label">Waiting for parts / access</div><div class="so-desc">Blocked, pending external resource</div></div></div>
          </div>
        </div>
      </div>
      <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
        <button class="btn-cancel-modal" data-bs-dismiss="modal">Cancel</button>
        <button class="btn-confirm" id="btnConfirmUpdate"><i class="bi bi-arrow-up-circle me-1"></i>Save Update</button>
      </div>
    </div>
  </div>
</div>

{{-- Resolve modal --}}
<div class="modal fade" id="resolveModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header-gd d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Mark as <em>Resolved</em></h5><button class="btn-close-w" data-bs-dismiss="modal">✕</button>
      </div>
      <div class="modal-body px-4 py-4">
        <div class="info-box-green p-3 mb-3"><i class="bi bi-check-circle me-1"></i>Resolving <strong id="resolveRef">#TKT-2025-0000</strong> — helpdesk will confirm with the customer.</div>
        <div class="mb-3"><label class="form-label">Resolution summary <span class="text-danger">*</span></label><textarea class="form-control" rows="3" placeholder="Describe exactly what was done to resolve the issue…"></textarea></div>
        <div><label class="form-label">Time spent</label><select class="form-select"><option>Less than 30 min</option><option>30 – 60 min</option><option selected>1 – 2 hours</option><option>2 – 4 hours</option><option>More than 4 hours</option></select></div>
      </div>
      <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
        <button class="btn-cancel-modal" data-bs-dismiss="modal">Cancel</button>
        <button class="btn-confirm" id="btnConfirmResolve"><i class="bi bi-check-circle me-1"></i>Submit Resolution</button>
      </div>
    </div>
  </div>
</div>

{{-- Escalate modal --}}
<div class="modal fade" id="escModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header-gd d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Escalate to <em>IT Admin</em></h5><button class="btn-close-w" data-bs-dismiss="modal">✕</button>
      </div>
      <div class="modal-body px-4 py-4">
        <div class="info-box-red p-3 mb-3"><i class="bi bi-exclamation-triangle-fill me-1"></i><strong id="escRef">#TKT-2025-0000</strong> — Escalating means you cannot resolve this. IT Admin will take over.</div>
        <div class="mb-3"><label class="form-label">Reason for escalation <span class="text-danger">*</span></label><select class="form-select"><option value="">Select a reason…</option><option>Issue requires admin-level system access</option><option>Hardware fault beyond repair scope</option><option>Requires vendor / manufacturer intervention</option><option>Issue affects multiple users / systems</option><option>Cannot diagnose root cause</option></select></div>
        <div><label class="form-label">What you have already tried <span class="text-danger">*</span></label><textarea class="form-control" rows="3" placeholder="List all steps taken so the admin has full context…"></textarea></div>
      </div>
      <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
        <button class="btn-cancel-modal" data-bs-dismiss="modal">Cancel</button>
        <button class="btn-confirm" style="background:#8b1a1a" id="btnConfirmEsc"><i class="bi bi-exclamation-triangle me-1"></i>Confirm Escalation</button>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
$(function () {
  const filterLabels = { all:'All Tickets', 'new-assigned':'New — Action Required', accepted:'Accepted', 'in-progress':'In Progress', escalated:'Escalated', resolved:'Resolved' };
  $('.tab-pill').on('click', function () { setFilter($(this).data('filter'), filterLabels); });
  $('#sideNav .list-group-item').on('click', function () { setFilter($(this).data('filter'), filterLabels); });

  $(document).on('click', '.status-opt', function () { $(this).siblings().removeClass('selected'); $(this).addClass('selected'); });

  let currentTicket = '';
  function setRef(id) { currentTicket = id; return '#' + id; }

  window.openAcceptModal  = function (id) { $('#acceptRef').text(setRef(id));  new bootstrap.Modal('#acceptModal').show(); };
  window.openDeclineModal = function (id) { $('#declineRef').text(setRef(id)); new bootstrap.Modal('#declineModal').show(); };
  window.openUpdateModal  = function (id, mode) {
    setRef(id); $('#updateRef').text('#'+id);
    $('#updateModalTitle').html(mode==='start' ? 'Start <em>Working</em>' : 'Add <em>Update</em>');
    new bootstrap.Modal('#updateModal').show();
  };
  window.openResolveModal = function (id) { $('#resolveRef').text(setRef(id)); new bootstrap.Modal('#resolveModal').show(); };
  window.openEscModal     = function (id) { $('#escRef').text(setRef(id));     new bootstrap.Modal('#escModal').show(); };

  $('#btnConfirmAccept').on('click',  function () { bootstrap.Modal.getInstance('#acceptModal').hide();  showToast('Ticket #'+currentTicket+' accepted.', 'success'); });
  $('#btnConfirmDecline').on('click', function () { bootstrap.Modal.getInstance('#declineModal').hide(); showToast('Ticket #'+currentTicket+' declined. Returned to helpdesk.', 'warn'); });
  $('#btnConfirmUpdate').on('click',  function () { bootstrap.Modal.getInstance('#updateModal').hide();  showToast('Update logged for #'+currentTicket+'.', 'success'); });
  $('#btnConfirmResolve').on('click', function () { bootstrap.Modal.getInstance('#resolveModal').hide(); showToast('Ticket #'+currentTicket+' resolved. Helpdesk notified.', 'success'); });
  $('#btnConfirmEsc').on('click',     function () { bootstrap.Modal.getInstance('#escModal').hide();     showToast('Ticket #'+currentTicket+' escalated to IT Admin.', 'warn'); });
});
</script>
@endsection