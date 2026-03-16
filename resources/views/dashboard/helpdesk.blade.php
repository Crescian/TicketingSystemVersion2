@extends('layouts.app')

@section('title', 'Helpdesk Dashboard — LGICT')

{{-- ── Nav ── --}}
@section('nav-role-badge')
  <span class="role-badge"><i class="bi bi-headset me-1"></i>Helpdesk</span>
@endsection
@section('avatar-initials', 'HD')
@section('nav-username', 'Maria S.')

{{-- ── Hero ── --}}
@section('hero-title')
  <h1>HELPDESK <em>DASHBOARD</em></h1>
@endsection
@section('hero-subtitle', 'Acknowledge, assign, and track all incoming support tickets.')

@section('hero-stats')
  <div class="d-flex gap-2 flex-wrap">
    <div class="stat-pill danger"><span class="num" id="cnt-unassigned">4</span><span class="lbl">Unassigned</span></div>
    <div class="stat-pill warn"> <span class="num" id="cnt-inprog">3</span><span class="lbl">In Progress</span></div>
    <div class="stat-pill warn"> <span class="num" id="cnt-esc">2</span><span class="lbl">Escalated</span></div>
    <div class="stat-pill">     <span class="num" id="cnt-res">18</span><span class="lbl">Resolved</span></div>
  </div>
@endsection

{{-- ── Page-specific styles ── --}}
@section('styles')
  /* Tech availability list */
  .tech-row { padding:10px 16px; border-bottom:1px solid var(--bd); display:flex; align-items:center; gap:10px; font-size:13px; }
  .tech-row:last-child { border-bottom:none; }
  .tech-av-lg { width:30px; height:30px; background:var(--gd); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:900; color:var(--yg); font-family:'Nunito',sans-serif; flex-shrink:0; }
  .tech-name { font-weight:700; font-size:13px; }
  .tech-load { font-size:11px; color:var(--tm); }
  .avail-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; margin-left:auto; }
  .avail-dot.free { background:#4a7c4a; }
  .avail-dot.busy { background:#f5c842; }
  .avail-dot.full { background:#e24b4a; }

  /* Action buttons on cards */
  .btn-assign   { background:var(--gd); color:var(--yg); font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; padding:6px 14px; border-radius:20px; border:none; cursor:pointer; transition:background .2s; }
  .btn-assign:hover { background:var(--gm); }
  .btn-reassign { background:var(--ygl); color:var(--gd); font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; padding:6px 14px; border-radius:20px; border:1.5px solid var(--bd); cursor:pointer; transition:all .2s; }
  .btn-reassign:hover { border-color:var(--gl); background:#d8eda0; }
  .btn-escalate { background:#fde8e8; color:#8b1a1a; font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; padding:6px 14px; border-radius:20px; border:1.5px solid #f0c0c0; cursor:pointer; transition:all .2s; }
  .btn-escalate:hover { background:#f8c8c8; }
  .btn-resolve  { background:#e8f5ee; color:#1a5a3a; font-family:'Nunito',sans-serif; font-weight:800; font-size:12px; padding:6px 14px; border-radius:20px; border:1.5px solid #a8ddc0; cursor:pointer; transition:all .2s; }
  .btn-resolve:hover  { background:#c8ead8; }

  /* Tech select options inside modal */
  .tech-select-option { border:1.5px solid var(--bd); border-radius:12px; padding:12px 14px; cursor:pointer; transition:all .2s; background:var(--cr); }
  .tech-select-option:hover { border-color:var(--gl); background:var(--ygl); }
  .tech-select-option.selected { border-color:var(--gd); background:var(--ygl); box-shadow:0 0 0 2px var(--yg); }
  .ts-name { font-family:'Nunito',sans-serif; font-weight:800; font-size:14px; }
  .ts-load { font-size:12px; color:var(--tm); }
  .load-bar-wrap { height:6px; background:var(--bd); border-radius:4px; margin-top:6px; }
  .load-bar { height:6px; border-radius:4px; background:var(--gl); }
  .load-bar.busy { background:#f5c842; }
  .load-bar.full { background:#e24b4a; }

  .resolve-info { background:var(--ygl); border-radius:10px; font-size:13px; color:var(--gd); }
@endsection

{{-- ══ SIDEBAR ══ --}}
@section('sidebar')
  <div class="sidebar-card mb-3">
    <div class="sidebar-head">Queue</div>
    <ul class="list-group sidebar-menu rounded-0" id="sideNav">
      <li class="list-group-item active" data-filter="all"><i class="bi bi-grid"></i> All tickets<span class="badge-count">27</span></li>
      <li class="list-group-item" data-filter="unassigned"><i class="bi bi-inbox"></i> Unassigned<span class="badge-count">4</span></li>
      <li class="list-group-item" data-filter="in-progress"><i class="bi bi-arrow-repeat"></i> In Progress<span class="badge-count">3</span></li>
      <li class="list-group-item" data-filter="escalated"><i class="bi bi-exclamation-triangle"></i> Escalated<span class="badge-count">2</span></li>
      <li class="list-group-item" data-filter="resolved"><i class="bi bi-check-circle"></i> Resolved<span class="badge-count">18</span></li>
    </ul>
  </div>

  <div class="sidebar-card">
    <div class="sidebar-head">IT Technicians</div>
    <div>
      <div class="tech-row">
        <div class="tech-av-lg">RB</div>
        <div><div class="tech-name">R. Buenaventura</div><div class="tech-load">2 active tickets</div></div>
        <div class="avail-dot busy" title="Busy"></div>
      </div>
      <div class="tech-row">
        <div class="tech-av-lg">CL</div>
        <div><div class="tech-name">C. Lim</div><div class="tech-load">0 active tickets</div></div>
        <div class="avail-dot free" title="Available"></div>
      </div>
      <div class="tech-row">
        <div class="tech-av-lg">JP</div>
        <div><div class="tech-name">J. Pascual</div><div class="tech-load">1 active ticket</div></div>
        <div class="avail-dot free" title="Available"></div>
      </div>
      <div class="tech-row">
        <div class="tech-av-lg">AL</div>
        <div><div class="tech-name">A. Lacson</div><div class="tech-load">3 active tickets</div></div>
        <div class="avail-dot full" title="At capacity"></div>
      </div>
      <div class="tech-row">
        <div class="tech-av-lg">DM</div>
        <div><div class="tech-name">D. Magno</div><div class="tech-load">0 active tickets</div></div>
        <div class="avail-dot free" title="Available"></div>
      </div>
    </div>
    <div class="p-2 px-3" style="font-size:11px;color:var(--tm);border-top:1px solid var(--bd)">
      <span class="me-3"><span class="avail-dot free d-inline-block me-1"></span>Available</span>
      <span class="me-3"><span class="avail-dot busy d-inline-block me-1"></span>Busy</span>
      <span><span class="avail-dot full d-inline-block me-1"></span>Full</span>
    </div>
  </div>
@endsection

{{-- ══ MAIN CONTENT ══ --}}
@section('content')
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <span class="font-brand fw-900" style="font-size:22px" id="listTitle">All Tickets</span>
    <div class="d-flex gap-2 flex-wrap">
      <div class="search-wrap"><i class="bi bi-search" style="color:var(--tm)"></i><input type="text" id="searchInput" placeholder="Search by name, ID, issue…"></div>
      <select class="sort-select"><option>Newest first</option><option>Oldest first</option><option>Priority</option></select>
    </div>
  </div>

  <div class="d-flex flex-wrap gap-2 mb-3" id="tabRow">
    <span class="tab-pill active" data-filter="all">All (27)</span>
    <span class="tab-pill" data-filter="unassigned">Unassigned (4)</span>
    <span class="tab-pill" data-filter="in-progress">In Progress (3)</span>
    <span class="tab-pill" data-filter="escalated">Escalated (2)</span>
    <span class="tab-pill" data-filter="resolved">Resolved (18)</span>
  </div>

  <div class="d-flex flex-column gap-3" id="ticketList">

    {{-- Card 1 — Unassigned High --}}
    <div class="ticket-card unassigned p-3" data-status="unassigned" data-priority="high">
      <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <span class="ticket-id">#TKT-2025-0058</span>
          <span class="badge-type">Hardware</span>
          <span class="meta-item"><span class="priority-dot pri-high"></span> High</span>
        </div>
        <span class="badge-status badge-unassigned"><i class="bi bi-inbox me-1"></i>Unassigned</span>
      </div>
      <div class="ticket-title mb-1">Laptop screen cracked after accidental drop</div>
      <div class="ticket-desc mb-2">Screen is shattered on the bottom-left corner. Device still powers on but display is unusable. Urgently needs replacement for daily work tasks.</div>
      <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
        <span class="meta-item"><i class="bi bi-person"></i> Juan Dela Cruz</span>
        <span class="meta-item"><i class="bi bi-building"></i> Finance</span>
        <span class="meta-item"><i class="bi bi-laptop"></i> Laptop</span>
        <span class="meta-item"><i class="bi bi-calendar3"></i> 30 min ago</span>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <button class="btn-assign" onclick="openAssignModal('TKT-2025-0058', false)"><i class="bi bi-person-plus me-1"></i>Assign Technician</button>
      </div>
    </div>

    {{-- Card 2 — Unassigned Medium --}}
    <div class="ticket-card unassigned p-3" data-status="unassigned" data-priority="medium">
      <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <span class="ticket-id">#TKT-2025-0057</span>
          <span class="badge-type">Software</span>
          <span class="meta-item"><span class="priority-dot pri-medium"></span> Medium</span>
        </div>
        <span class="badge-status badge-unassigned"><i class="bi bi-inbox me-1"></i>Unassigned</span>
      </div>
      <div class="ticket-title mb-1">Adobe Acrobat license expired — cannot open PDFs</div>
      <div class="ticket-desc mb-2">License expired yesterday. Unable to open or edit any PDF files needed for contract approvals. Affects team productivity.</div>
      <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
        <span class="meta-item"><i class="bi bi-person"></i> Ana Reyes</span>
        <span class="meta-item"><i class="bi bi-building"></i> Legal</span>
        <span class="meta-item"><i class="bi bi-laptop"></i> Laptop</span>
        <span class="meta-item"><i class="bi bi-calendar3"></i> 1 hr ago</span>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <button class="btn-assign" onclick="openAssignModal('TKT-2025-0057', false)"><i class="bi bi-person-plus me-1"></i>Assign Technician</button>
      </div>
    </div>

    {{-- Card 3 — In Progress --}}
    <div class="ticket-card in-progress p-3" data-status="in-progress" data-priority="medium">
      <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <span class="ticket-id">#TKT-2025-0055</span>
          <span class="badge-type">Network</span>
          <span class="meta-item"><span class="priority-dot pri-medium"></span> Medium</span>
        </div>
        <span class="badge-status badge-in-progress"><i class="bi bi-arrow-repeat me-1"></i>In Progress</span>
      </div>
      <div class="ticket-title mb-1">Cannot connect to VPN from home office</div>
      <div class="ticket-desc mb-2">VPN client throws error code 691 on Windows 11. Remote work is completely blocked. Issue started after OS update.</div>
      <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
        <span class="meta-item"><i class="bi bi-person"></i> Ben Santos</span>
        <span class="meta-item"><i class="bi bi-building"></i> Operations</span>
        <span class="meta-item"><i class="bi bi-laptop"></i> Laptop</span>
        <span class="meta-item"><i class="bi bi-calendar3"></i> 3 hrs ago</span>
        <span class="tech-chip ms-auto"><span class="tc-av">JP</span>J. Pascual</span>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <button class="btn-reassign" onclick="openAssignModal('TKT-2025-0055', true)"><i class="bi bi-arrow-left-right me-1"></i>Reassign</button>
        <button class="btn-escalate" onclick="openEscalateModal('TKT-2025-0055')"><i class="bi bi-exclamation-triangle me-1"></i>Escalate</button>
        <button class="btn-resolve"  onclick="openResolveModal('TKT-2025-0055')"><i class="bi bi-check-circle me-1"></i>Mark Resolved</button>
      </div>
    </div>

    {{-- Card 4 — Escalated --}}
    <div class="ticket-card escalated p-3" data-status="escalated" data-priority="high">
      <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <span class="ticket-id">#TKT-2025-0048</span>
          <span class="badge-type">Hardware</span>
          <span class="meta-item"><span class="priority-dot pri-high"></span> High</span>
        </div>
        <span class="badge-status badge-escalated"><i class="bi bi-exclamation-triangle me-1"></i>Escalated</span>
      </div>
      <div class="ticket-title mb-1">Laptop won't turn on after Windows update</div>
      <div class="ticket-desc mb-2">ThinkPad X1 stuck on black screen post-update. Hard reset unsuccessful. First tech could not resolve.</div>
      <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
        <span class="meta-item"><i class="bi bi-person"></i> Juan Dela Cruz</span>
        <span class="meta-item"><i class="bi bi-building"></i> Finance</span>
        <span class="meta-item"><i class="bi bi-calendar3"></i> 3 days ago</span>
        <span class="tech-chip ms-auto"><span class="tc-av" style="background:#8b1a1a">MA</span>IT Admin</span>
      </div>
      <div class="esc-banner p-2 mb-3"><i class="bi bi-exclamation-triangle-fill me-1"></i>Escalated to IT Admin — level 1 unresolved. Awaiting admin resolution or reassignment.</div>
      <div class="d-flex gap-2 flex-wrap">
        <button class="btn-reassign" onclick="openAssignModal('TKT-2025-0048', true)"><i class="bi bi-person-plus me-1"></i>Reassign to New Tech</button>
        <button class="btn-resolve"  onclick="openResolveModal('TKT-2025-0048')"><i class="bi bi-check-circle me-1"></i>Mark Resolved</button>
      </div>
    </div>

    {{-- Card 5 — Resolved --}}
    <div class="ticket-card resolved p-3" data-status="resolved" data-priority="low">
      <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <span class="ticket-id">#TKT-2025-0040</span>
          <span class="badge-type">Account</span>
          <span class="meta-item"><span class="priority-dot pri-low"></span> Low</span>
        </div>
        <span class="badge-status badge-resolved"><i class="bi bi-check-circle me-1"></i>Resolved</span>
      </div>
      <div class="ticket-title mb-1">Password reset for MS365 account</div>
      <div class="ticket-desc mb-2">Locked out of MS365 after too many failed login attempts. Needed urgent access for EOD report submission.</div>
      <div class="d-flex flex-wrap align-items-center gap-3">
        <span class="meta-item"><i class="bi bi-person"></i> Lea Cruz</span>
        <span class="meta-item"><i class="bi bi-building"></i> HR</span>
        <span class="meta-item"><i class="bi bi-clock"></i> Resolved in 2 hrs</span>
        <span class="meta-item"><i class="bi bi-calendar3"></i> Mar 10</span>
        <span class="tech-chip ms-auto"><span class="tc-av">CL</span>C. Lim</span>
      </div>
    </div>

  </div>{{-- /ticketList --}}
@endsection

{{-- ══ MODALS ══ --}}
@section('modals')

{{-- Assign / Reassign modal --}}
<div class="modal fade" id="assignModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header-gd d-flex align-items-center justify-content-between">
        <h5 class="mb-0" id="assignModalTitle">Assign <em>Technician</em></h5><button class="btn-close-w" data-bs-dismiss="modal">✕</button>
      </div>
      <div class="modal-body px-4 py-4">
        <div class="mb-3 p-2 px-3 rounded" style="background:var(--ygl);font-size:13px;color:var(--gd)">
          <strong id="assignTicketRef">#TKT-2025-0000</strong> — Select an available technician below.
        </div>
        <label class="form-label mb-2">Choose technician</label>
        <div class="d-flex flex-column gap-2">
          <div class="tech-select-option" data-tech="RB" data-name="R. Buenaventura">
            <div class="d-flex align-items-center gap-2 mb-1"><div class="tc-av">RB</div><div><div class="ts-name">R. Buenaventura</div><div class="ts-load">2 active tickets</div></div><span class="ms-auto" style="background:#fff4cc;color:#7a5a00;font-size:11px;font-weight:800;border-radius:20px;padding:2px 8px">Busy</span></div>
            <div class="load-bar-wrap"><div class="load-bar busy" style="width:66%"></div></div>
          </div>
          <div class="tech-select-option selected" data-tech="CL" data-name="C. Lim">
            <div class="d-flex align-items-center gap-2 mb-1"><div class="tc-av">CL</div><div><div class="ts-name">C. Lim</div><div class="ts-load">0 active tickets</div></div><span class="ms-auto" style="background:var(--ygl);color:var(--gm);font-size:11px;font-weight:800;border-radius:20px;padding:2px 8px">Available</span></div>
            <div class="load-bar-wrap"><div class="load-bar" style="width:0%"></div></div>
          </div>
          <div class="tech-select-option" data-tech="JP" data-name="J. Pascual">
            <div class="d-flex align-items-center gap-2 mb-1"><div class="tc-av">JP</div><div><div class="ts-name">J. Pascual</div><div class="ts-load">1 active ticket</div></div><span class="ms-auto" style="background:var(--ygl);color:var(--gm);font-size:11px;font-weight:800;border-radius:20px;padding:2px 8px">Available</span></div>
            <div class="load-bar-wrap"><div class="load-bar" style="width:33%"></div></div>
          </div>
          <div class="tech-select-option" data-tech="DM" data-name="D. Magno">
            <div class="d-flex align-items-center gap-2 mb-1"><div class="tc-av">DM</div><div><div class="ts-name">D. Magno</div><div class="ts-load">0 active tickets</div></div><span class="ms-auto" style="background:var(--ygl);color:var(--gm);font-size:11px;font-weight:800;border-radius:20px;padding:2px 8px">Available</span></div>
            <div class="load-bar-wrap"><div class="load-bar" style="width:0%"></div></div>
          </div>
        </div>
        <div class="mt-3"><label class="form-label">Note to technician (optional)</label><textarea class="form-control" rows="2" placeholder="Add any context or instructions…"></textarea></div>
      </div>
      <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
        <button class="btn-cancel-modal" data-bs-dismiss="modal">Cancel</button>
        <button class="btn-confirm" id="btnConfirmAssign"><i class="bi bi-check-lg me-1"></i>Confirm Assignment</button>
      </div>
    </div>
  </div>
</div>

{{-- Escalate modal --}}
<div class="modal fade" id="escalateModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header-gd d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Escalate to <em>IT Admin</em></h5><button class="btn-close-w" data-bs-dismiss="modal">✕</button>
      </div>
      <div class="modal-body px-4 py-4">
        <div class="esc-banner p-3 mb-3"><i class="bi bi-exclamation-triangle-fill me-1"></i><strong id="escalateRef">#TKT-2025-0000</strong> — This ticket will be escalated to IT Admin and marked as level 2.</div>
        <div class="mb-3"><label class="form-label">Reason for escalation <span class="text-danger">*</span></label><select class="form-select"><option value="">Select a reason…</option><option>Technician unable to resolve — hardware issue beyond scope</option><option>Technician unable to resolve — requires admin access</option><option>Issue affecting multiple users</option><option>Repeated failure after reassignment</option><option>Customer requested escalation</option></select></div>
        <div><label class="form-label">Escalation notes</label><textarea class="form-control" rows="3" placeholder="Describe what was already attempted and why escalation is needed…"></textarea></div>
      </div>
      <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
        <button class="btn-cancel-modal" data-bs-dismiss="modal">Cancel</button>
        <button class="btn-confirm" style="background:#8b1a1a" id="btnConfirmEsc"><i class="bi bi-exclamation-triangle me-1"></i>Confirm Escalation</button>
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
        <div class="resolve-info p-3 mb-3"><i class="bi bi-check-circle me-1"></i>Ticket <strong id="resolveRef">#TKT-2025-0000</strong> — Confirm resolution and notify the customer.</div>
        <div class="mb-3"><label class="form-label">Resolution summary <span class="text-danger">*</span></label><textarea class="form-control" rows="3" placeholder="Describe how the issue was resolved…"></textarea></div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="notifyCustomer" checked>
          <label class="form-check-label" for="notifyCustomer" style="font-size:13px;font-weight:600">Notify customer by email that ticket is resolved</label>
        </div>
      </div>
      <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
        <button class="btn-cancel-modal" data-bs-dismiss="modal">Cancel</button>
        <button class="btn-confirm" id="btnConfirmResolve"><i class="bi bi-check-circle me-1"></i>Mark as Resolved</button>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
$(function () {
  const filterLabels = { all:'All Tickets', unassigned:'Unassigned', 'in-progress':'In Progress', escalated:'Escalated', resolved:'Resolved' };
  $('.tab-pill').on('click', function () { setFilter($(this).data('filter'), filterLabels); });
  $('#sideNav .list-group-item').on('click', function () { setFilter($(this).data('filter'), filterLabels); });

  $(document).on('click', '.tech-select-option', function () {
    $(this).siblings().removeClass('selected'); $(this).addClass('selected');
  });

  let currentTicket = '';

  window.openAssignModal = function (id, isReassign) {
    currentTicket = id;
    $('#assignTicketRef').text('#' + id);
    $('#assignModalTitle').html(isReassign ? 'Reassign <em>Technician</em>' : 'Assign <em>Technician</em>');
    new bootstrap.Modal('#assignModal').show();
  };
  window.openEscalateModal = function (id) {
    currentTicket = id;
    $('#escalateRef').text('#' + id);
    new bootstrap.Modal('#escalateModal').show();
  };
  window.openResolveModal = function (id) {
    currentTicket = id;
    $('#resolveRef').text('#' + id);
    new bootstrap.Modal('#resolveModal').show();
  };

  $('#btnConfirmAssign').on('click', function () {
    const name = $('.tech-select-option.selected').data('name') || 'C. Lim';
    bootstrap.Modal.getInstance('#assignModal').hide();
    showToast('Ticket #' + currentTicket + ' assigned to ' + name + '.', 'success');
  });
  $('#btnConfirmEsc').on('click', function () {
    bootstrap.Modal.getInstance('#escalateModal').hide();
    showToast('Ticket #' + currentTicket + ' escalated to IT Admin.', 'warn');
  });
  $('#btnConfirmResolve').on('click', function () {
    bootstrap.Modal.getInstance('#resolveModal').hide();
    showToast('Ticket #' + currentTicket + ' marked as resolved. Customer notified.', 'success');
  });
});
</script>
@endsection