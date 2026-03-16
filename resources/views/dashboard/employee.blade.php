@extends('layouts.app')

@section('title', 'My Support Tickets — LGICT')

{{-- ── Nav ── --}}
@section('nav-role-badge')
  {{-- Employees have no role badge, just avatar --}}
@endsection
@section('avatar-initials', 'JD')
@section('nav-username', 'Juan D.')

{{-- ── Hero ── --}}
@section('hero-title')
  <h1>MY <em>SUPPORT</em><br>TICKETS</h1>
@endsection
@section('hero-subtitle', 'Track your requests and get IT help fast.')

@section('hero-stats')
  <div class="d-flex gap-2 flex-wrap">
    <div class="stat-pill"><span class="num" id="cnt-open">3</span><span class="lbl">Open</span></div>
    <div class="stat-pill warn"><span class="num" id="cnt-prog">1</span><span class="lbl">In Progress</span></div>
    <div class="stat-pill"><span class="num" id="cnt-done">12</span><span class="lbl">Resolved</span></div>
  </div>
@endsection

@section('hero-cta')
  <button class="btn-new" data-bs-toggle="modal" data-bs-target="#ticketModal">
    <i class="bi bi-plus-lg me-1"></i> New Ticket
  </button>
@endsection

{{-- ── Page-specific styles ── --}}
@section('styles')
  /* ── Employee: New Ticket button ── */
  .btn-new {
    background: var(--yg); color: var(--gd);
    font-family: 'Nunito', sans-serif; font-weight: 900; font-size: 15px;
    padding: 13px 28px; border-radius: 50px; border: none;
    transition: background .2s, transform .15s; white-space: nowrap;
  }
  .btn-new:hover { background: var(--ygd); transform: translateY(-2px); }

  /* ── Modal: step wizard ── */
  .step-ind { display: flex; align-items: center; }
  .step-item { display: flex; align-items: center; gap: 8px; flex: 1; }
  .step-num { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-family: 'Nunito', sans-serif; font-weight: 900; font-size: 13px; background: var(--bd); color: var(--tm); flex-shrink: 0; transition: all .3s; }
  .step-item.active .step-num { background: var(--gd); color: var(--yg); }
  .step-item.done   .step-num { background: var(--yg); color: var(--gd); }
  .step-lbl { font-size: 12px; font-weight: 700; color: var(--tm); white-space: nowrap; }
  .step-item.active .step-lbl { color: var(--gd); }
  .step-line { flex: 1; height: 2px; background: var(--bd); margin: 0 8px; transition: background .3s; }
  .step-line.done { background: var(--yg); }

  /* Device grid */
  .device-opt { border: 1.5px solid var(--bd); border-radius: 12px; padding: 14px 8px; text-align: center; cursor: pointer; transition: all .2s; background: var(--cr); user-select: none; }
  .device-opt:hover { border-color: var(--gl); background: var(--ygl); }
  .device-opt.selected { border-color: var(--gd); background: var(--ygl); box-shadow: 0 0 0 2px var(--yg); }
  .device-opt .d-icon { font-size: 26px; display: block; margin-bottom: 6px; }
  .device-opt .d-lbl  { font-size: 12px; font-weight: 700; }

  /* Priority */
  .pri-opt { flex: 1; padding: 10px; border: 1.5px solid var(--bd); border-radius: 10px; text-align: center; cursor: pointer; transition: all .2s; background: var(--cr); }
  .pri-dot { width: 10px; height: 10px; border-radius: 50%; margin: 0 auto 6px; }
  .pri-lbl { font-size: 12px; font-weight: 700; color: var(--tm); }
  .pri-opt.low    .pri-dot { background: #4a7c4a; }
  .pri-opt.medium .pri-dot { background: #f5c842; }
  .pri-opt.high   .pri-dot { background: #e24b4a; }
  .pri-opt.selected { border-color: var(--gd); background: var(--ygl); }
  .pri-opt.selected .pri-lbl { color: var(--gd); }

  /* Review */
  .review-box    { background: var(--ygl); border-radius: 12px; }
  .review-detail { border: 1.5px solid var(--bd); border-radius: 12px; }
  .review-lbl    { font-size: 11px; font-weight: 700; color: var(--tm); text-transform: uppercase; letter-spacing: .4px; }

  .btn-back-modal  { background: none; border: 1.5px solid var(--bd); color: var(--tm); font-family: 'Nunito', sans-serif; font-weight: 800; font-size: 14px; padding: 10px 22px; border-radius: 50px; transition: all .2s; }
  .btn-back-modal:hover { border-color: var(--gl); color: var(--gd); }
  .btn-continue    { background: var(--gd); color: var(--yg); font-family: 'Nunito', sans-serif; font-weight: 900; font-size: 14px; padding: 11px 28px; border-radius: 50px; border: none; transition: all .2s; }
  .btn-continue:hover { background: var(--gm); transform: translateY(-1px); }
  .btn-submit-ticket { background: var(--yg); color: var(--gd); font-family: 'Nunito', sans-serif; font-weight: 900; font-size: 14px; padding: 11px 28px; border-radius: 50px; border: none; transition: all .2s; }
  .btn-submit-ticket:hover { background: var(--ygd); }

  /* Success */
  .success-icon { width: 72px; height: 72px; background: var(--ygl); border-radius: 50%; font-size: 36px; }
  .ticket-ref   { background: var(--gd); color: var(--yg); font-family: 'Nunito', sans-serif; font-weight: 900; font-size: 20px; padding: 10px 28px; border-radius: 50px; letter-spacing: 1px; display: inline-block; }
@endsection

{{-- ══ SIDEBAR ══ --}}
@section('sidebar')
  {{-- Nav menu --}}
  <div class="sidebar-card mb-3">
    <div class="sidebar-head">My Tickets</div>
    <ul class="list-group sidebar-menu rounded-0" id="sideNav">
      <li class="list-group-item active" data-filter="all">
        <i class="bi bi-grid"></i> All tickets
        <span class="badge-count">16</span>
      </li>
      <li class="list-group-item" data-filter="open">
        <i class="bi bi-circle"></i> Open
        <span class="badge-count">3</span>
      </li>
      <li class="list-group-item" data-filter="in-progress">
        <i class="bi bi-arrow-repeat"></i> In progress
        <span class="badge-count">1</span>
      </li>
      <li class="list-group-item" data-filter="escalated">
        <i class="bi bi-exclamation-triangle"></i> Escalated
        <span class="badge-count">1</span>
      </li>
      <li class="list-group-item" data-filter="resolved">
        <i class="bi bi-check-circle"></i> Resolved
        <span class="badge-count">12</span>
      </li>
    </ul>
  </div>

  {{-- Filter card --}}
  <div class="sidebar-card filter-card">
    <div class="sidebar-head">Filter</div>
    <div class="p-3 d-flex flex-column gap-2">
      <div>
        <label class="form-label mb-1">Category</label>
        <select class="form-select form-select-sm" id="fCategory">
          <option value="">All categories</option>
          <option>Hardware</option>
          <option>Software</option>
          <option>Network</option>
          <option>Account</option>
        </select>
      </div>
      <div>
        <label class="form-label mb-1">Device type</label>
        <select class="form-select form-select-sm" id="fDevice">
          <option value="">All devices</option>
          <option>Laptop</option>
          <option>Desktop</option>
          <option>Mobile</option>
          <option>Printer</option>
        </select>
      </div>
      <div>
        <label class="form-label mb-1">From date</label>
        <input type="date" class="form-control form-control-sm" id="fDate">
      </div>
      <button class="btn btn-filter w-100 mt-1" id="applyFilter">Apply filters</button>
    </div>
  </div>
@endsection

{{-- ══ MAIN CONTENT ══ --}}
@section('content')
  {{-- Controls --}}
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <span class="font-brand fw-900" style="font-size:22px" id="listTitle">All Tickets</span>
    <div class="d-flex gap-2">
      <div class="search-wrap">
        <i class="bi bi-search" style="color:var(--tm)"></i>
        <input type="text" id="searchInput" placeholder="Search tickets…">
      </div>
      <select class="sort-select" id="sortSelect">
        <option>Newest first</option>
        <option>Oldest first</option>
        <option>Priority</option>
      </select>
    </div>
  </div>

  {{-- Tab pills --}}
  <div class="d-flex flex-wrap gap-2 mb-3" id="tabRow">
    <span class="tab-pill active" data-filter="all">All (16)</span>
    <span class="tab-pill" data-filter="open">Open (3)</span>
    <span class="tab-pill" data-filter="in-progress">In Progress (1)</span>
    <span class="tab-pill" data-filter="escalated">Escalated (1)</span>
    <span class="tab-pill" data-filter="resolved">Resolved (12)</span>
  </div>

  {{-- Ticket cards --}}
  <div class="d-flex flex-column gap-3" id="ticketList">

    <div class="ticket-card escalated p-3" data-status="escalated" data-category="Hardware" data-device="Laptop">
      <div class="d-flex justify-content-between align-items-start mb-2">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <span class="ticket-id">#TKT-2025-0048</span>
          <span class="badge-type">Hardware</span>
        </div>
        <span class="badge-status badge-escalated">⚠ Escalated</span>
      </div>
      <div class="ticket-title mb-1">Laptop won't turn on after Windows update</div>
      <div class="ticket-desc mb-3">My ThinkPad X1 Carbon is stuck on a black screen after the latest Windows 11 update. Hard reset does not help — the power LED blinks three times then stops.</div>
      <div class="d-flex flex-wrap align-items-center gap-3">
        <span class="meta-item"><i class="bi bi-calendar3"></i> 3 days ago</span>
        <span class="meta-item"><i class="bi bi-laptop"></i> Laptop</span>
        <span class="meta-item"><i class="bi bi-clock"></i> 72 hrs open</span>
        <span class="tech-chip ms-auto"><span class="tc-av">MA</span>IT Admin</span>
      </div>
      <div class="esc-banner mt-2 p-2">
        <i class="bi bi-exclamation-triangle-fill me-1"></i>
        Escalated to IT Admin — first tech could not resolve. Reassigned 6 hrs ago.
      </div>
    </div>

    <div class="ticket-card in-progress p-3" data-status="in-progress" data-category="Software" data-device="Laptop">
      <div class="d-flex justify-content-between align-items-start mb-2">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <span class="ticket-id">#TKT-2025-0051</span>
          <span class="badge-type">Software</span>
        </div>
        <span class="badge-status badge-in-progress">⟳ In Progress</span>
      </div>
      <div class="ticket-title mb-1">MS Teams not loading — stuck on splash screen</div>
      <div class="ticket-desc mb-3">Microsoft Teams has been stuck on the loading screen since this morning. Reinstalling did not fix it. Other Office apps work fine.</div>
      <div class="d-flex flex-wrap align-items-center gap-3">
        <span class="meta-item"><i class="bi bi-calendar3"></i> Today</span>
        <span class="meta-item"><i class="bi bi-laptop"></i> Laptop</span>
        <span class="meta-item"><i class="bi bi-clock"></i> 4 hrs open</span>
        <span class="tech-chip ms-auto"><span class="tc-av">RB</span>R. Buenaventura</span>
      </div>
    </div>

    <div class="ticket-card open p-3" data-status="open" data-category="Network" data-device="Laptop">
      <div class="d-flex justify-content-between align-items-start mb-2">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <span class="ticket-id">#TKT-2025-0053</span>
          <span class="badge-type">Network</span>
        </div>
        <span class="badge-status badge-open">● Open</span>
      </div>
      <div class="ticket-title mb-1">Unable to connect to office Wi-Fi on floor 3</div>
      <div class="ticket-desc mb-3">My laptop is not detecting CORP-WIFI-F3. Other colleagues on the same floor have the same issue. Worked fine yesterday afternoon.</div>
      <div class="d-flex flex-wrap align-items-center gap-3">
        <span class="meta-item"><i class="bi bi-calendar3"></i> 1 day ago</span>
        <span class="meta-item"><i class="bi bi-laptop"></i> Laptop</span>
        <span class="meta-item"><i class="bi bi-geo-alt"></i> Floor 3, North Wing</span>
        <span class="tech-chip ms-auto"><span class="tc-av" style="background:#888">—</span>Unassigned</span>
      </div>
    </div>

    <div class="ticket-card resolved p-3" data-status="resolved" data-category="Account" data-device="Laptop">
      <div class="d-flex justify-content-between align-items-start mb-2">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <span class="ticket-id">#TKT-2025-0040</span>
          <span class="badge-type">Account</span>
        </div>
        <span class="badge-status badge-resolved">✓ Resolved</span>
      </div>
      <div class="ticket-title mb-1">Password reset for MS365 account</div>
      <div class="ticket-desc mb-3">Locked out of MS365 after too many incorrect login attempts. Needed urgent access for EOD report submission.</div>
      <div class="d-flex flex-wrap align-items-center gap-3">
        <span class="meta-item"><i class="bi bi-calendar3"></i> Mar 10</span>
        <span class="meta-item"><i class="bi bi-clock"></i> Resolved in 2 hrs</span>
        <span class="tech-chip ms-auto"><span class="tc-av">CL</span>C. Lim</span>
      </div>
    </div>

  </div>{{-- /ticketList --}}
@endsection

{{-- ══ NEW TICKET MODAL ══ --}}
@section('modals')
  <div class="modal fade" id="ticketModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header-gd d-flex align-items-center justify-content-between">
          <h5 class="mb-0">New <em>Support</em> Ticket</h5>
          <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
        </div>
        <div class="modal-body px-4 pt-4 pb-2">
          <div class="step-ind mb-4">
            <div class="step-item active" id="si1"><div class="step-num">1</div><span class="step-lbl">Issue type</span></div>
            <div class="step-line" id="sl1"></div>
            <div class="step-item" id="si2"><div class="step-num">2</div><span class="step-lbl">Details</span></div>
            <div class="step-line" id="sl2"></div>
            <div class="step-item" id="si3"><div class="step-num">3</div><span class="step-lbl">Review</span></div>
          </div>

          {{-- Step 1 --}}
          <div class="form-step" id="fs1">
            <div class="mb-3">
              <label class="form-label">Select device type <span class="text-danger">*</span></label>
              <div class="row g-2">
                <div class="col-3"><div class="device-opt" data-device="Mobile"><span class="d-icon">📱</span><span class="d-lbl">Mobile</span></div></div>
                <div class="col-3"><div class="device-opt selected" data-device="Laptop"><span class="d-icon">💻</span><span class="d-lbl">Laptop</span></div></div>
                <div class="col-3"><div class="device-opt" data-device="Desktop"><span class="d-icon">🖥</span><span class="d-lbl">Desktop</span></div></div>
                <div class="col-3"><div class="device-opt" data-device="Printer"><span class="d-icon">🖨</span><span class="d-lbl">Printer</span></div></div>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Request category <span class="text-danger">*</span></label>
              <select class="form-select" id="mCategory">
                <option value="">Choose a category…</option>
                <option>Hardware — physical damage or malfunction</option>
                <option>Software — app crash or installation issue</option>
                <option>Network — connectivity or VPN</option>
                <option>Account — login, password, or access</option>
                <option>Other</option>
              </select>
            </div>
            <div class="mb-1">
              <label class="form-label">Priority</label>
              <div class="d-flex gap-2">
                <div class="pri-opt low" data-pri="Low"><div class="pri-dot"></div><div class="pri-lbl">Low</div></div>
                <div class="pri-opt medium selected" data-pri="Medium"><div class="pri-dot"></div><div class="pri-lbl">Medium</div></div>
                <div class="pri-opt high" data-pri="High"><div class="pri-dot"></div><div class="pri-lbl">High</div></div>
              </div>
            </div>
          </div>

          {{-- Step 2 --}}
          <div class="form-step d-none" id="fs2">
            <div class="mb-3">
              <label class="form-label">Subject <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="mSubject" placeholder="Brief description of the issue…">
            </div>
            <div class="mb-3">
              <label class="form-label">Describe the issue <span class="text-danger">*</span></label>
              <textarea class="form-control" id="mDesc" rows="4" placeholder="What happened, when it started, what you've already tried…"></textarea>
            </div>
            <div class="row g-3 mb-3">
              <div class="col-6">
                <label class="form-label">Asset tag / serial no.</label>
                <input type="text" class="form-control" placeholder="e.g. LT-00432">
              </div>
              <div class="col-6">
                <label class="form-label">Location</label>
                <input type="text" class="form-control" placeholder="e.g. Floor 3, Desk B12">
              </div>
            </div>
            <div class="mb-1">
              <label class="form-label">Your department</label>
              <select class="form-select" id="mDept">
                <option>Finance</option><option>Marketing</option><option>Operations</option>
                <option>HR</option><option>IT</option><option>Sales</option>
              </select>
            </div>
          </div>

          {{-- Step 3 --}}
          <div class="form-step d-none" id="fs3">
            <div class="review-box p-3 mb-3">
              <div class="font-brand fw-900 mb-3" style="font-size:14px;color:var(--gd);text-transform:uppercase;letter-spacing:.5px">Ticket Summary</div>
              <div class="row g-3">
                <div class="col-6"><div class="review-lbl">Device</div><div class="fw-700" id="rv-device">💻 Laptop</div></div>
                <div class="col-6"><div class="review-lbl">Category</div><div class="fw-700" id="rv-cat">—</div></div>
                <div class="col-6"><div class="review-lbl">Priority</div><div class="fw-700" id="rv-pri">⚡ Medium</div></div>
                <div class="col-6"><div class="review-lbl">Department</div><div class="fw-700" id="rv-dept">—</div></div>
              </div>
            </div>
            <div class="review-detail p-3">
              <div class="review-lbl mb-1">Subject</div>
              <div class="font-brand fw-800 mb-3" style="font-size:15px" id="rv-subject">—</div>
              <div class="review-lbl mb-1">What happens next</div>
              <div style="font-size:13px;color:var(--tm)">Your ticket will be assigned to an available IT technician. Average first response: <strong style="color:var(--gd)">under 2 hours</strong>.</div>
            </div>
          </div>

          {{-- Success --}}
          <div class="form-step d-none text-center py-3" id="fsSuccess">
            <div class="success-icon d-flex align-items-center justify-content-center mx-auto mb-3">✅</div>
            <h5 class="font-brand fw-900 mb-1" style="font-size:22px">Ticket submitted!</h5>
            <p class="mb-2" style="color:var(--tm)">Your request has been received.<br>Helpdesk will assign a technician shortly.</p>
            <div class="ticket-ref my-3">TKT-2025-0056</div>
            <p style="color:var(--tm);font-size:13px">Track progress from your dashboard.<br>You'll be notified when the status changes.</p>
          </div>
        </div>

        <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between" id="mFooter">
          <button class="btn-back-modal" id="btnBack">← Back</button>
          <button class="btn-continue" id="btnNext">Continue →</button>
        </div>
      </div>
    </div>
  </div>
@endsection

@section('scripts')
  <script>
  $(function () {
    const filterLabels = { all:'All Tickets', open:'Open', 'in-progress':'In Progress', escalated:'Escalated', resolved:'Resolved' };
    $('.tab-pill').on('click', function () { setFilter($(this).data('filter'), filterLabels); });
    $('#sideNav .list-group-item').on('click', function () { setFilter($(this).data('filter'), filterLabels); });

    $('#applyFilter').on('click', function () {
      const cat = $('#fCategory').val().toLowerCase();
      const dev = $('#fDevice').val().toLowerCase();
      $('#ticketList .ticket-card').each(function () {
        const cMatch = !cat || $(this).data('category').toLowerCase() === cat;
        const dMatch = !dev || $(this).data('device').toLowerCase() === dev;
        $(this).toggle(cMatch && dMatch);
      });
    });

    /* ── Step wizard ── */
    let step = 1;
    const steps = ['fs1','fs2','fs3','fsSuccess'];
    function showStep(n) {
      step = n;
      steps.forEach((id,i) => $('#'+id).toggleClass('d-none', i !== n-1));
      for (let i = 1; i <= 3; i++) {
        $('#si'+i).toggleClass('active', i===n).toggleClass('done', i<n);
        if (i < 3) $('#sl'+i).toggleClass('done', i<n);
      }
      $('#btnBack').css('visibility', n > 1 ? 'visible' : 'hidden');
      if (n === 3) {
        const dIcons = { Mobile:'📱', Laptop:'💻', Desktop:'🖥', Printer:'🖨' };
        const dev = $('.device-opt.selected').data('device') || 'Laptop';
        $('#rv-device').text((dIcons[dev]||'💻')+' '+dev);
        $('#rv-cat').text($('#mCategory').val().split(' — ')[0] || '—');
        $('#rv-pri').text('⚡ '+($('.pri-opt.selected').data('pri')||'Medium'));
        $('#rv-dept').text($('#mDept').val());
        $('#rv-subject').text($('#mSubject').val() || '—');
        $('#btnNext').removeClass('btn-continue').addClass('btn-submit-ticket').text('Submit ticket');
      } else if (n === 4) {
        $('#mFooter').hide();
      } else {
        $('#btnNext').removeClass('btn-submit-ticket').addClass('btn-continue').text('Continue →');
      }
    }
    $('#btnNext').on('click', function () { showStep(step < 4 ? step+1 : 4); });
    $('#btnBack').on('click', function () { if (step > 1) showStep(step-1); });
    $('#ticketModal').on('show.bs.modal', function () {
      $('#mFooter').show();
      showStep(1);
      $('#mCategory').val(''); $('#mSubject, #mDesc').val('');
      $('.device-opt').removeClass('selected').filter('[data-device="Laptop"]').addClass('selected');
      $('.pri-opt').removeClass('selected').filter('.medium').addClass('selected');
    });
    $(document).on('click', '.device-opt', function () {
      $(this).closest('.row').find('.device-opt').removeClass('selected'); $(this).addClass('selected');
    });
    $(document).on('click', '.pri-opt', function () {
      $(this).siblings().removeClass('selected'); $(this).addClass('selected');
    });
  });
  </script>
@endsection