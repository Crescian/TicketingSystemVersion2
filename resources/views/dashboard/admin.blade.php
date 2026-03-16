@extends('layouts.admin')

@section('title', 'IT Admin — Escalation Management')

{{-- ── Nav ── --}}
@section('nav-role-badge')
      <span class="role-badge-admin"><i class="bi bi-shield-fill me-1"></i>IT Admin</span>
@endsection
@section('avatar-initials', 'MA')
@section('nav-username', 'M. Aquino')

{{-- ── Hero ── --}}
@section('hero-title')
      <h1><strong>ADMIN</strong> <em>ESCALATION</em><br>MANAGEMENT</h1>
@endsection
@section('hero-subtitle', 'Review escalated tickets, reassign technicians, or resolve directly.')

@section('hero-stats')
      <div class="d-flex gap-2 flex-wrap">
        <div class="stat-pill esc"> <span class="num" id="cnt-esc">3</span><span class="lbl">Escalated</span></div>
        <div class="stat-pill open"><span class="num" id="cnt-wip">1</span><span class="lbl">Admin WIP</span></div>
        <div class="stat-pill all"> <span class="num" id="cnt-all">27</span><span class="lbl">All tickets</span></div>
        <div class="stat-pill done"><span class="num" id="cnt-res">21</span><span class="lbl">Resolved today</span></div>
      </div>
@endsection

{{-- ══ SIDEBAR ══ --}}
@section('sidebar')

      {{-- Admin Queue nav --}}
      <div class="sidebar-card mb-3">
        <div class="sidebar-head red"><i class="bi bi-shield-fill me-1"></i>Admin Queue</div>
        <ul class="list-group sidebar-menu rounded-0" id="sideNav">
          <li class="list-group-item active" data-filter="all">
            <i class="bi bi-grid"></i> All tickets
            <span class="badge-count dark">27</span>
          </li>
          <li class="list-group-item" data-filter="escalated">
            <i class="bi bi-exclamation-triangle"></i> Escalated
            <span class="badge-count red">3</span>
          </li>
          <li class="list-group-item" data-filter="admin-wip">
            <i class="bi bi-person-workspace"></i> Admin handling
            <span class="badge-count red">1</span>
          </li>
          <li class="list-group-item" data-filter="reassigned">
            <i class="bi bi-arrow-left-right"></i> Reassigned
            <span class="badge-count green">2</span>
          </li>
          <li class="list-group-item" data-filter="resolved">
            <i class="bi bi-check-circle"></i> Resolved
            <span class="badge-count green">21</span>
          </li>
        </ul>
      </div>

      {{-- All technicians --}}
      <div class="sidebar-card mb-3">
        <div class="sidebar-head dark">All Technicians</div>
        <div>
          <div class="tech-row">
            <div class="tech-av admin">MA</div>
            <div><div class="tech-name">M. Aquino (You)</div><div class="tech-load">1 escalation active</div></div>
            <div class="avail-dot busy"></div>
          </div>
          <div class="tech-row">
            <div class="tech-av normal">RB</div>
            <div><div class="tech-name">R. Buenaventura</div><div class="tech-load">2 active tickets</div></div>
            <div class="avail-dot busy"></div>
          </div>
          <div class="tech-row">
            <div class="tech-av normal">CL</div>
            <div><div class="tech-name">C. Lim</div><div class="tech-load">0 active tickets</div></div>
            <div class="avail-dot free"></div>
          </div>
          <div class="tech-row">
            <div class="tech-av normal">JP</div>
            <div><div class="tech-name">J. Pascual</div><div class="tech-load">1 active ticket</div></div>
            <div class="avail-dot free"></div>
          </div>
          <div class="tech-row">
            <div class="tech-av normal">AL</div>
            <div><div class="tech-name">A. Lacson</div><div class="tech-load">3 active tickets</div></div>
            <div class="avail-dot full"></div>
          </div>
          <div class="tech-row">
            <div class="tech-av normal">DM</div>
            <div><div class="tech-name">D. Magno</div><div class="tech-load">0 active tickets</div></div>
            <div class="avail-dot free"></div>
          </div>
        </div>
        <div class="p-2 px-3" style="font-size:11px;color:var(--tm);border-top:1px solid var(--bd)">
          <span class="me-3"><span class="avail-dot free d-inline-block me-1"></span>Available</span>
          <span class="me-3"><span class="avail-dot busy d-inline-block me-1"></span>Busy</span>
          <span><span class="avail-dot full d-inline-block me-1"></span>Full</span>
        </div>
      </div>

      {{-- System overview --}}
      <div class="sidebar-card">
        <div class="sidebar-head dark">System Overview</div>
        <div>
          <div class="sys-stat"><span style="font-size:13px;font-weight:600;color:var(--tm)">Avg resolution time</span><span class="sys-val ok">1.9h</span></div>
          <div class="sys-stat"><span style="font-size:13px;font-weight:600;color:var(--tm)">SLA breaches today</span><span class="sys-val danger">2</span></div>
          <div class="sys-stat"><span style="font-size:13px;font-weight:600;color:var(--tm)">Escalation rate</span><span class="sys-val warn">11%</span></div>
          <div class="sys-stat"><span style="font-size:13px;font-weight:600;color:var(--tm)">Customer satisfaction</span><span class="sys-val ok">4.7 ⭐</span></div>
          <div class="sys-stat"><span style="font-size:13px;font-weight:600;color:var(--tm)">Open tickets total</span><span class="sys-val ok">6</span></div>
        </div>
      </div>

@endsection

{{-- ══ MAIN CONTENT ══ --}}
@section('content')

      {{-- Controls --}}
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <span class="font-brand fw-900" style="font-size:22px" id="listTitle">All Tickets</span>
        <div class="d-flex gap-2 flex-wrap">
          <div class="search-wrap">
            <i class="bi bi-search" style="color:var(--tm)"></i>
            <input type="text" id="searchInput" placeholder="Search tickets…">
          </div>
          <select class="sort-select">
            <option>Escalated first</option>
            <option>Newest first</option>
            <option>Priority</option>
          </select>
        </div>
      </div>

      {{-- Tab pills --}}
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="tab-pill active" data-filter="all">All (27)</span>
        <span class="tab-pill red-pill" data-filter="escalated">Escalated (3)</span>
        <span class="tab-pill red-pill" data-filter="admin-wip">Admin Handling (1)</span>
        <span class="tab-pill" data-filter="reassigned">Reassigned (2)</span>
        <span class="tab-pill" data-filter="resolved">Resolved (21)</span>
      </div>

      {{-- Ticket cards --}}
      <div class="d-flex flex-column gap-3" id="ticketList">

        {{-- Card 1: ESCALATED — Level 1, SLA breach --}}
        <div class="ticket-card escalated p-3" data-status="escalated" data-priority="high">
          <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <span class="ticket-id">#TKT-2025-0048</span>
              <span class="badge-type">Hardware</span>
              <span class="meta-item"><span class="priority-dot pri-high"></span> High</span>
              <span class="esc-level"><i class="bi bi-arrow-up me-1"></i>Escalation Level 1</span>
            </div>
            <span class="badge-status bs-esc"><i class="bi bi-exclamation-triangle me-1"></i>Awaiting Admin Action</span>
          </div>

          <div class="ticket-title mb-1">Laptop won't turn on after Windows update</div>
          <div class="ticket-desc mb-3">ThinkPad X1 Carbon stuck on black screen after Windows 11 update. Hard reset unsuccessful — power LED blinks 3× then stops. First tech could not resolve after 4 hrs.</div>

          <div class="esc-timeline mb-3">
            <div class="fw-800 mb-2" style="font-size:12px;color:var(--rd);text-transform:uppercase;letter-spacing:.4px">
              <i class="bi bi-exclamation-triangle-fill me-1"></i>Escalation History
            </div>
            <div class="etl-item">
              <div class="etl-dot"></div>
              <div><span class="etl-time">Mar 10, 9:00 AM</span><span class="etl-text ms-2">Assigned to R. Buenaventura</span></div>
            </div>
            <div class="etl-item">
              <div class="etl-dot"></div>
              <div><span class="etl-time">Mar 10, 1:00 PM</span><span class="etl-text ms-2">Escalated — tech could not diagnose. Reason: black screen, no POST signal</span></div>
            </div>
          </div>

          <div class="d-flex flex-wrap gap-3 mb-3">
            <span class="meta-item"><i class="bi bi-person"></i> Juan Dela Cruz</span>
            <span class="meta-item"><i class="bi bi-building"></i> Finance</span>
            <span class="meta-item"><i class="bi bi-laptop"></i> ThinkPad X1 — LT-00432</span>
            <span class="meta-item"><i class="bi bi-clock" style="color:var(--rd)"></i><span style="color:var(--rd);font-weight:700">72 hrs open — SLA breach</span></span>
            <div class="d-flex gap-2 ms-auto flex-wrap">
              <span class="tech-chip prev"><span class="tc-av normal">RB</span>Prev: R. Buenaventura</span>
            </div>
          </div>

          <div class="d-flex gap-2 flex-wrap">
            <button class="btn-reassign-a" onclick="openReassignModal('TKT-2025-0048')"><i class="bi bi-person-plus me-1"></i>Reassign to New Tech</button>
            <button class="btn-takeover"   onclick="openTakeoverModal('TKT-2025-0048')"><i class="bi bi-person-workspace me-1"></i>Take Over Directly</button>
            <button class="btn-resolve-a"  onclick="openResolveModal('TKT-2025-0048')"><i class="bi bi-check-circle me-1"></i>Resolve Directly</button>
            <button class="btn-view-hist"  onclick="openHistoryModal('TKT-2025-0048')"><i class="bi bi-clock-history me-1"></i>View Full History</button>
          </div>
        </div>

        {{-- Card 2: ESCALATED — multi-user network failure --}}
        <div class="ticket-card escalated p-3" data-status="escalated" data-priority="high">
          <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <span class="ticket-id">#TKT-2025-0061</span>
              <span class="badge-type">Network</span>
              <span class="meta-item"><span class="priority-dot pri-high"></span> High</span>
              <span class="esc-level"><i class="bi bi-arrow-up me-1"></i>Escalation Level 1</span>
            </div>
            <span class="badge-status bs-esc"><i class="bi bi-exclamation-triangle me-1"></i>Awaiting Admin Action</span>
          </div>

          <div class="ticket-title mb-1">Floor 3 network switch failure — 12 users offline</div>
          <div class="ticket-desc mb-3">Network switch on floor 3, north wing has failed. 12 employees unable to access intranet and shared drives. Issue beyond single-tech scope — requires hardware replacement.</div>

          <div class="esc-timeline mb-3">
            <div class="fw-800 mb-2" style="font-size:12px;color:var(--rd);text-transform:uppercase;letter-spacing:.4px">
              <i class="bi bi-exclamation-triangle-fill me-1"></i>Escalation History
            </div>
            <div class="etl-item">
              <div class="etl-dot"></div>
              <div><span class="etl-time">Today, 8:30 AM</span><span class="etl-text ms-2">Assigned to J. Pascual</span></div>
            </div>
            <div class="etl-item">
              <div class="etl-dot"></div>
              <div><span class="etl-time">Today, 10:00 AM</span><span class="etl-text ms-2">Escalated — hardware issue affecting multiple users, requires switch replacement</span></div>
            </div>
          </div>

          <div class="d-flex flex-wrap gap-3 mb-3">
            <span class="meta-item"><i class="bi bi-people"></i> 12 affected users</span>
            <span class="meta-item"><i class="bi bi-geo-alt"></i> Floor 3, North Wing</span>
            <span class="meta-item"><i class="bi bi-hdd-network"></i> Network</span>
            <span class="meta-item"><i class="bi bi-clock" style="color:var(--rd)"></i><span style="color:var(--rd);font-weight:700">1.5 hrs open</span></span>
            <div class="d-flex gap-2 ms-auto">
              <span class="tech-chip prev"><span class="tc-av normal">JP</span>Prev: J. Pascual</span>
            </div>
          </div>

          <div class="d-flex gap-2 flex-wrap">
            <button class="btn-reassign-a" onclick="openReassignModal('TKT-2025-0061')"><i class="bi bi-person-plus me-1"></i>Reassign to New Tech</button>
            <button class="btn-takeover"   onclick="openTakeoverModal('TKT-2025-0061')"><i class="bi bi-person-workspace me-1"></i>Take Over Directly</button>
            <button class="btn-resolve-a"  onclick="openResolveModal('TKT-2025-0061')"><i class="bi bi-check-circle me-1"></i>Resolve Directly</button>
            <button class="btn-view-hist"  onclick="openHistoryModal('TKT-2025-0061')"><i class="bi bi-clock-history me-1"></i>View Full History</button>
          </div>
        </div>

        {{-- Card 3: ADMIN WIP — admin took over --}}
        <div class="ticket-card admin-wip p-3" data-status="admin-wip" data-priority="high">
          <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <span class="ticket-id">#TKT-2025-0059</span>
              <span class="badge-type">Software</span>
              <span class="meta-item"><span class="priority-dot pri-high"></span> High</span>
              <span class="esc-level"><i class="bi bi-shield-fill me-1"></i>Admin handling</span>
            </div>
            <span class="badge-status bs-admin-wip"><i class="bi bi-person-workspace me-1"></i>Admin — In Progress</span>
          </div>

          <div class="ticket-title mb-1">ERP system inaccessible after server migration</div>
          <div class="ticket-desc mb-3">Entire Finance department unable to access the ERP system (SAP) after last night's server migration. Login fails with database connection error. Affects payroll processing deadline.</div>

          <div class="esc-timeline mb-3">
            <div class="fw-800 mb-2" style="font-size:12px;color:var(--rd);text-transform:uppercase;letter-spacing:.4px">
              <i class="bi bi-clock-history me-1"></i>Admin Activity
            </div>
            <div class="etl-item">
              <div class="etl-dot"></div>
              <div><span class="etl-time">Today, 7:00 AM</span><span class="etl-text ms-2">Ticket escalated to admin</span></div>
            </div>
            <div class="etl-item">
              <div class="etl-dot"></div>
              <div><span class="etl-time">Today, 7:45 AM</span><span class="etl-text ms-2">M. Aquino took over — reviewing server migration logs</span></div>
            </div>
            <div class="etl-item">
              <div class="etl-dot" style="background:var(--yg)"></div>
              <div><span class="etl-time" style="color:var(--gm)">Now</span><span class="etl-text ms-2">Reconfiguring SAP connection strings to new server IP</span></div>
            </div>
          </div>

          <div class="d-flex flex-wrap gap-3 mb-3">
            <span class="meta-item"><i class="bi bi-people"></i> Finance dept (8 users)</span>
            <span class="meta-item"><i class="bi bi-server"></i> SAP ERP</span>
            <span class="meta-item"><i class="bi bi-clock"></i> Working for 2h 15m</span>
            <span class="tech-chip curr ms-auto"><span class="tc-av admin">MA</span>Admin: M. Aquino</span>
          </div>

          <div class="d-flex gap-2 flex-wrap">
            <button class="btn-reassign-a" onclick="openReassignModal('TKT-2025-0059')"><i class="bi bi-arrow-left-right me-1"></i>Reassign Instead</button>
            <button class="btn-resolve-a"  onclick="openResolveModal('TKT-2025-0059')"><i class="bi bi-check-circle me-1"></i>Mark Resolved</button>
            <button class="btn-view-hist"  onclick="openHistoryModal('TKT-2025-0059')"><i class="bi bi-clock-history me-1"></i>View Full History</button>
          </div>
        </div>

        {{-- Card 4: REASSIGNED by admin --}}
        <div class="ticket-card reassigned p-3" data-status="reassigned" data-priority="medium">
          <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <span class="ticket-id">#TKT-2025-0053</span>
              <span class="badge-type">Network</span>
              <span class="meta-item"><span class="priority-dot pri-medium"></span> Medium</span>
            </div>
            <span class="badge-status bs-reassigned"><i class="bi bi-arrow-left-right me-1"></i>Reassigned by Admin</span>
          </div>

          <div class="ticket-title mb-1">Unable to connect to Wi-Fi on floor 3</div>
          <div class="ticket-desc mb-3">Laptop not detecting CORP-WIFI-F3. Multiple colleagues on same floor affected. Admin reassigned after original tech was unavailable.</div>

          <div class="d-flex flex-wrap gap-3 mb-3">
            <span class="meta-item"><i class="bi bi-person"></i> Carlos Mendez</span>
            <span class="meta-item"><i class="bi bi-building"></i> Sales</span>
            <span class="meta-item"><i class="bi bi-laptop"></i> Laptop</span>
            <span class="meta-item"><i class="bi bi-calendar3"></i> Reassigned 1 hr ago</span>
            <div class="d-flex gap-2 ms-auto flex-wrap">
              <span class="tech-chip prev"><span class="tc-av normal">RB</span>Prev: R. Buenaventura</span>
              <span class="tech-chip curr"><span class="tc-av normal">DM</span>Now: D. Magno</span>
            </div>
          </div>

          <div class="d-flex gap-2 flex-wrap">
            <button class="btn-reassign-a" onclick="openReassignModal('TKT-2025-0053')"><i class="bi bi-arrow-left-right me-1"></i>Reassign Again</button>
            <button class="btn-resolve-a"  onclick="openResolveModal('TKT-2025-0053')"><i class="bi bi-check-circle me-1"></i>Resolve Directly</button>
            <button class="btn-view-hist"  onclick="openHistoryModal('TKT-2025-0053')"><i class="bi bi-clock-history me-1"></i>View History</button>
          </div>
        </div>

        {{-- Card 5: RESOLVED by admin --}}
        <div class="ticket-card resolved p-3" data-status="resolved" data-priority="low">
          <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <span class="ticket-id">#TKT-2025-0044</span>
              <span class="badge-type">Account</span>
              <span class="meta-item"><span class="priority-dot pri-low"></span> Low</span>
            </div>
            <span class="badge-status bs-resolved"><i class="bi bi-check-circle me-1"></i>Resolved</span>
          </div>

          <div class="ticket-title mb-1">Domain admin access required for IT audit</div>
          <div class="ticket-desc mb-2">Temporary domain admin access needed for external IT audit team. Required coordination with Active Directory — beyond regular tech scope.</div>

          <div class="d-flex flex-wrap gap-3">
            <span class="meta-item"><i class="bi bi-person"></i> IT Audit Team</span>
            <span class="meta-item"><i class="bi bi-building"></i> Compliance</span>
            <span class="meta-item"><i class="bi bi-clock"></i> Resolved in 45 min</span>
            <span class="meta-item"><i class="bi bi-calendar3"></i> Mar 11, 2025</span>
            <span class="tech-chip curr ms-auto"><span class="tc-av admin">MA</span>Resolved by Admin</span>
          </div>
        </div>

      </div>{{-- /ticketList --}}

@endsection

{{-- ══ MODALS ══ --}}
@section('modals')

    {{-- Reassign modal --}}
    <div class="modal fade" id="reassignModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-hdr-dark d-flex align-items-center justify-content-between">
            <h5 class="mb-0">Reassign <em>to New Tech</em></h5>
            <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
          </div>
          <div class="modal-body px-4 py-4">
            <div class="info-box-red p-3 mb-3">
              <i class="bi bi-info-circle me-1"></i>
              Ticket <strong id="reassignRef">#TKT-2025-0000</strong> — Admin is reassigning to a different technician. The new tech will receive full ticket history.
            </div>
            <label class="form-label mb-2">Select new technician</label>
            <div class="d-flex flex-column gap-2 mb-3">
              <div class="tech-select-option" data-tech="RB" data-name="R. Buenaventura">
                <div class="d-flex align-items-center gap-2 mb-1"><div class="tc-av normal">RB</div><div><div class="ts-name">R. Buenaventura</div><div class="ts-load">2 active tickets</div></div><span class="badge-count dark ms-auto">Busy</span></div>
                <div class="load-bar-wrap"><div class="load-bar busy" style="width:66%"></div></div>
              </div>
              <div class="tech-select-option selected" data-tech="CL" data-name="C. Lim">
                <div class="d-flex align-items-center gap-2 mb-1"><div class="tc-av normal">CL</div><div><div class="ts-name">C. Lim</div><div class="ts-load">0 active tickets</div></div><span class="badge-count green ms-auto">Available</span></div>
                <div class="load-bar-wrap"><div class="load-bar" style="width:0%"></div></div>
              </div>
              <div class="tech-select-option" data-tech="JP" data-name="J. Pascual">
                <div class="d-flex align-items-center gap-2 mb-1"><div class="tc-av normal">JP</div><div><div class="ts-name">J. Pascual</div><div class="ts-load">1 active ticket</div></div><span class="badge-count green ms-auto">Available</span></div>
                <div class="load-bar-wrap"><div class="load-bar" style="width:33%"></div></div>
              </div>
              <div class="tech-select-option" data-tech="DM" data-name="D. Magno">
                <div class="d-flex align-items-center gap-2 mb-1"><div class="tc-av normal">DM</div><div><div class="ts-name">D. Magno</div><div class="ts-load">0 active tickets</div></div><span class="badge-count green ms-auto">Available</span></div>
                <div class="load-bar-wrap"><div class="load-bar" style="width:0%"></div></div>
              </div>
            </div>
            <label class="form-label">Reassignment notes</label>
            <textarea class="form-control" rows="2" placeholder="Instructions or context for the new technician…"></textarea>
          </div>
          <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
            <button class="btn-cancel-modal" data-bs-dismiss="modal">Cancel</button>
            <button class="btn-confirm" id="btnConfirmReassign"><i class="bi bi-person-plus me-1"></i>Confirm Reassignment</button>
          </div>
        </div>
      </div>
    </div>

    {{-- Take over modal --}}
    <div class="modal fade" id="takeoverModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-hdr-red d-flex align-items-center justify-content-between">
            <h5 class="mb-0">Take Over <em>Directly</em></h5>
            <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
          </div>
          <div class="modal-body px-4 py-4">
            <div class="info-box-red p-3 mb-3">
              <i class="bi bi-shield-fill me-1"></i>
              You are taking personal ownership of <strong id="takeoverRef">#TKT-2025-0000</strong>. This will assign it directly to you as IT Admin.
            </div>
            <div class="mb-3">
              <label class="form-label">Reason for taking over</label>
              <select class="form-select">
                <option>Requires admin-level system access</option>
                <option>Critical business impact — time sensitive</option>
                <option>No available technicians</option>
                <option>Sensitive data involved</option>
                <option>Vendor coordination required</option>
              </select>
            </div>
            <div>
              <label class="form-label">Initial assessment</label>
              <textarea class="form-control" rows="2" placeholder="Briefly describe your plan of action…"></textarea>
            </div>
          </div>
          <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
            <button class="btn-cancel-modal" data-bs-dismiss="modal">Cancel</button>
            <button class="btn-confirm red" id="btnConfirmTakeover"><i class="bi bi-person-workspace me-1"></i>Take Over Ticket</button>
          </div>
        </div>
      </div>
    </div>

    {{-- Resolve modal --}}
    <div class="modal fade" id="resolveModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-hdr-dark d-flex align-items-center justify-content-between">
            <h5 class="mb-0">Resolve <em>Directly</em></h5>
            <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
          </div>
          <div class="modal-body px-4 py-4">
            <div class="info-box-green p-3 mb-3">
              <i class="bi bi-check-circle me-1"></i>
              Admin resolving <strong id="resolveRef">#TKT-2025-0000</strong> — helpdesk will be notified to confirm with the customer.
            </div>
            <div class="mb-3">
              <label class="form-label">Resolution summary <span class="text-danger">*</span></label>
              <textarea class="form-control" rows="3" placeholder="Describe what was done, root cause, and how it was resolved…"></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label">Root cause category</label>
              <select class="form-select">
                <option>Hardware failure — replacement required</option>
                <option>Software / configuration error</option>
                <option>Network infrastructure issue</option>
                <option>User access / permissions</option>
                <option>Third-party vendor issue</option>
                <option>Human error</option>
              </select>
            </div>
            <div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="notifyCustomer" checked>
                <label class="form-check-label" for="notifyCustomer" style="font-size:13px;font-weight:600">Notify customer via email</label>
              </div>
              <div class="form-check mt-1">
                <input class="form-check-input" type="checkbox" id="notifyHelpdesk" checked>
                <label class="form-check-label" for="notifyHelpdesk" style="font-size:13px;font-weight:600">Notify helpdesk to close ticket</label>
              </div>
            </div>
          </div>
          <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
            <button class="btn-cancel-modal" data-bs-dismiss="modal">Cancel</button>
            <button class="btn-confirm" id="btnConfirmResolve"><i class="bi bi-check-circle me-1"></i>Confirm Resolution</button>
          </div>
        </div>
      </div>
    </div>

    {{-- History modal --}}
    <div class="modal fade" id="historyModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-hdr-dark d-flex align-items-center justify-content-between">
            <h5 class="mb-0">Full Ticket <em>History</em></h5>
            <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
          </div>
          <div class="modal-body px-4 py-4">
            <div class="mb-3 p-2 px-3 rounded" style="background:var(--ygl);font-size:13px">
              <strong id="historyRef">#TKT-2025-0000</strong> — Complete audit trail of all status changes and actions.
            </div>
            <div id="historyTimeline">
              <div class="hist-item">
                <div class="hist-icon assigned"><i class="bi bi-plus-circle"></i></div>
                <div>
                  <div class="hist-time">Mar 10, 2025 — 9:00 AM</div>
                  <div class="hist-title">Ticket created by customer</div>
                  <div class="hist-desc">Juan Dela Cruz submitted a hardware support request. Subject: "Laptop won't turn on after Windows update"</div>
                </div>
              </div>
              <div class="hist-item">
                <div class="hist-icon assigned"><i class="bi bi-person-plus"></i></div>
                <div>
                  <div class="hist-time">Mar 10, 2025 — 9:15 AM</div>
                  <div class="hist-title">Assigned to R. Buenaventura by Helpdesk</div>
                  <div class="hist-desc">Helpdesk (Maria S.) acknowledged and assigned to available technician R. Buenaventura.</div>
                </div>
              </div>
              <div class="hist-item">
                <div class="hist-icon accepted"><i class="bi bi-check"></i></div>
                <div>
                  <div class="hist-time">Mar 10, 2025 — 9:30 AM</div>
                  <div class="hist-title">Ticket accepted by R. Buenaventura</div>
                  <div class="hist-desc">Estimated completion: 1–2 hours. Initial notes: "Will check BIOS and boot sequence."</div>
                </div>
              </div>
              <div class="hist-item">
                <div class="hist-icon working"><i class="bi bi-tools"></i></div>
                <div>
                  <div class="hist-time">Mar 10, 2025 — 10:00 AM</div>
                  <div class="hist-title">Status updated — In Progress</div>
                  <div class="hist-desc">Tech note: "Attempted safe mode boot — no display signal. Testing external monitor next."</div>
                </div>
              </div>
              <div class="hist-item">
                <div class="hist-icon working"><i class="bi bi-tools"></i></div>
                <div>
                  <div class="hist-time">Mar 10, 2025 — 12:00 PM</div>
                  <div class="hist-title">Status updated — Still investigating</div>
                  <div class="hist-desc">Tech note: "External monitor shows no signal either. Possible GPU or motherboard failure. Reinstalling display drivers attempted — no success."</div>
                </div>
              </div>
              <div class="hist-item">
                <div class="hist-icon escalated"><i class="bi bi-exclamation-triangle"></i></div>
                <div>
                  <div class="hist-time">Mar 10, 2025 — 1:00 PM</div>
                  <div class="hist-title">Escalated to IT Admin by R. Buenaventura</div>
                  <div class="hist-desc">Reason: "Hardware fault beyond tech scope — likely GPU failure requiring board-level repair or unit replacement."</div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer border-top px-4 py-3 d-flex justify-content-end">
            <button class="btn-confirm" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

@endsection

@section('scripts')
    <script>
    $(function () {
      const filterLabels = {
        all:         'All Tickets',
        escalated:   'Escalated Tickets',
        'admin-wip': 'Admin Handling',
        reassigned:  'Reassigned by Admin',
        resolved:    'Resolved Tickets'
      };

      $('.tab-pill').on('click', function () { setFilter($(this).data('filter'), filterLabels); });
      $('#sideNav .list-group-item').on('click', function () { setFilter($(this).data('filter'), filterLabels); });

      let cur = '';
      function setRef(id) { cur = id; return '#' + id; }

      /* ── Reassign ── */
      window.openReassignModal = function (id) {
        $('#reassignRef').text(setRef(id));
        new bootstrap.Modal('#reassignModal').show();
      };
      $('#btnConfirmReassign').on('click', function () {
        const name = $('.tech-select-option.selected .ts-name').first().text() || 'C. Lim';
        bootstrap.Modal.getInstance('#reassignModal').hide();
        showToast('Ticket #' + cur + ' reassigned to ' + name + '. Helpdesk notified.', 'success');
      });

      /* ── Take over ── */
      window.openTakeoverModal = function (id) {
        $('#takeoverRef').text(setRef(id));
        new bootstrap.Modal('#takeoverModal').show();
      };
      $('#btnConfirmTakeover').on('click', function () {
        bootstrap.Modal.getInstance('#takeoverModal').hide();
        showToast('You have taken ownership of #' + cur + '. Status set to Admin — In Progress.', 'warn');
      });

      /* ── Resolve ── */
      window.openResolveModal = function (id) {
        $('#resolveRef').text(setRef(id));
        new bootstrap.Modal('#resolveModal').show();
      };
      $('#btnConfirmResolve').on('click', function () {
        bootstrap.Modal.getInstance('#resolveModal').hide();
        showToast('Ticket #' + cur + ' resolved. Helpdesk and customer notified.', 'success');
      });

      /* ── History ── */
      window.openHistoryModal = function (id) {
        $('#historyRef').text(setRef(id));
        new bootstrap.Modal('#historyModal').show();
      };
    });
    </script>
@endsection