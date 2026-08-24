{{-- Cross-role ticket journey tracker — ICT-facing pages only (Employee's own
     detail page stays as-is). Renders the same 8-step lifecycle view regardless
     of which track (Helpdesk/L1, Support Specialist/L2, IT Admin/L3) currently
     holds the ticket, so "where is it" doesn't depend on picking the right
     dashboard tab. Requires $ticket with statusHistories and assignedTo.role
     eager-loaded. --}}
@props(['ticket'])

@php
    $t = $ticket;
    $status = $t->status;
    $histories = $t->statusHistories;

    $firstTime = function (string $newStatus) use ($histories) {
        $row = $histories->sortBy('changed_at')->first(fn ($h) => $h->new_status === $newStatus);
        return $row ? \Carbon\Carbon::parse($row->changed_at) : null;
    };

    $fmt = fn (?\Carbon\Carbon $c) => $c ? $c->timezone('Asia/Manila')->format('M d, g:i A') : '—';

    // ── Per-step reached time (real columns first, status-history fallback) ──
    $tSubmitted    = $t->created_at;
    $tAcknowledged = ($t->date_acknowledged && $t->time_acknowledged)
        ? \Carbon\Carbon::parse($t->date_acknowledged . ' ' . $t->time_acknowledged)
        : $firstTime('For Classification');
    $tClassified   = $t->subcategory_name ? $firstTime('Classified') : null;
    $tAssigned     = $t->assigned_to ? $firstTime('Assigned') : null;
    $tInProgress   = $t->started_at;
    $tReportReview = $t->report_started_at ?? $firstTime('Report For Review');
    $tConfirmed    = $firstTime('Requestor Confirmation');
    $tClosed       = $t->closed_at ?? $firstTime('Closed');

    // ── Which of the 8 visual steps is "current" vs "done" vs "future" ──
    $reportPhase = ['Closed Service Request', 'In Progress Service Report', 'Done Service Report', 'Report For Review'];
    $confirmPhase = ['Approved Service Report', 'Requestor Confirmation'];

    $stepIndex = match (true) {
        in_array($status, ['For Acknowledgment'], true) => 0,
        in_array($status, ['For Classification'], true) => 1,
        $status === 'Classified' => 2,
        $status === 'Assigned' => 3,
        $status === 'In Progress Service Request' => 4,
        in_array($status, $reportPhase, true) => 5,
        in_array($status, $confirmPhase, true) => 6,
        $status === 'Closed' => 7,
        $status === 'Escalated' => 4, // escalation happens mid-work — shown via ribbon, not a step of its own
        $status === 'Cancelled' => null,
        default => null,
    };

    $steps = [
        ['key' => 'submitted',  'label' => 'Submitted',     'time' => $fmt($tSubmitted)],
        ['key' => 'ack',        'label' => 'Acknowledged',  'time' => $fmt($tAcknowledged)],
        ['key' => 'classified', 'label' => 'Classified',    'time' => $fmt($tClassified)],
        ['key' => 'assigned',   'label' => 'Assigned',      'time' => $fmt($tAssigned)],
        ['key' => 'progress',   'label' => 'In Progress',   'time' => $tInProgress ? 'Started ' . $fmt($tInProgress) : '—'],
        ['key' => 'review',     'label' => 'Report Review', 'time' => $fmt($tReportReview)],
        ['key' => 'confirmed',  'label' => 'Confirmed',     'time' => $fmt($tConfirmed)],
        ['key' => 'closed',     'label' => 'Closed',        'time' => $fmt($tClosed)],
    ];
    foreach ($steps as $i => &$s) {
        $s['state'] = $stepIndex === null
            ? ($i === 0 ? 'done' : 'future') // Cancelled: only "Submitted" reads as done
            : ($i < $stepIndex ? 'done' : ($i === $stepIndex ? 'current' : 'future'));
    }
    unset($s);

    // ── Escalation ribbon ──
    $isCurrentlyEscalated = $status === 'Escalated';
    $escalationHistory = $histories->sortBy('changed_at')->first(fn ($h) => $h->new_status === 'Escalated');
    $wasEverEscalated = (bool) $escalationHistory;

    // ── Currently-with owner chip ──
    $tierMap = [
        'Helpdesk' => ['L1', 'tier-l1', 'HELPDESK'],
        'IT Support Specialist' => ['L2', 'tier-l2', 'SUPPORT SPECIALIST'],
        'Supervisor - Support Specialist' => ['L2', 'tier-l2', 'SUPERVISOR · L2'],
        'IT Admin' => ['L3', 'tier-l3', 'IT ADMIN'],
        'Supervisor - IT Admin' => ['L3', 'tier-l3', 'SUPERVISOR · L3'],
    ];
    $queueLabels = [
        'Helpdesk' => ['Helpdesk Queue', 'tier-l1', 'HELPDESK'],
        'Supervisor - Support Specialist' => ['Support Supervisor Queue', 'tier-l2', 'SUPERVISOR · L2'],
        'Supervisor - IT Admin' => ['Admin Supervisor Queue', 'tier-l3', 'SUPERVISOR · L3'],
    ];

    $ownerName = null; $ownerInitials = null; $tierClass = null; $tierLabel = null;
    if ($status === 'Closed' || $status === 'Cancelled') {
        $ownerName = null; // resolved/closed — no active owner to show
    } elseif ($t->assignedTo) {
        $roleName = $t->assignedTo->role?->role_name;
        [$tierShort, $tierClass, $tierLabel] = $tierMap[$roleName] ?? ['', '', $roleName ?? ''];
        $ownerName = $t->assignedTo->name;
        $parts = explode(' ', trim($ownerName));
        $ownerInitials = strtoupper(substr($parts[0] ?? '', 0, 1) . substr($parts[1] ?? '', 0, 1));
        $tierLabel = trim(($tierShort ? $tierShort . ' · ' : '') . $tierLabel);
    } elseif ($t->pending_role && isset($queueLabels[$t->pending_role])) {
        [$ownerName, $tierClass, $tierLabelRaw] = $queueLabels[$t->pending_role];
        $tierLabel = $tierLabelRaw;
    }
@endphp

<div class="tt-card">
    <div class="tt-head">
        <div>
            <div class="tt-title">
                <svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10h11M14 10l-3-3M14 10l-3 3"/><path d="M17 4v12"/></svg>
                Ticket Journey
            </div>
            <div class="tt-sub">Same view on every ICT dashboard — no matter who's currently holding it.</div>
        </div>

        @if($ownerName)
            <div class="tt-owner-chip">
                @if($ownerInitials)
                    <div class="tt-owner-av">{{ $ownerInitials }}</div>
                @else
                    <div class="tt-owner-av"><svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10h14"/></svg></div>
                @endif
                <div>
                    <div class="tt-owner-lbl">Currently With</div>
                    <div class="tt-owner-name">
                        {{ $ownerName }}
                        @if($tierClass)
                            <span class="tt-tier {{ $tierClass }}">{{ $tierLabel }}</span>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="tt-stepper">
        @foreach($steps as $i => $step)
            <div class="tt-step {{ $step['state'] }}">
                <div class="tt-step-circle">
                    @switch($step['key'])
                        @case('submitted')
                            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h6l3 3v11H6z"/><path d="M12 3v3h3"/><path d="M8 10h4M8 13h4"/></svg>
                            @break
                        @case('ack')
                            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 10s3-5.5 8-5.5S18 10 18 10s-3 5.5-8 5.5S2 10 2 10z"/><circle cx="10" cy="10" r="2"/></svg>
                            @break
                        @case('classified')
                            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 3l6 6-8 8H3v-6l8-8z"/><circle cx="13.5" cy="6.5" r="1"/></svg>
                            @break
                        @case('assigned')
                            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="7" r="3"/><path d="M2.5 17c0-3 2.5-5.5 5.5-5.5S13.5 14 13.5 17"/><path d="M14.5 8l1.5 1.5L19 6"/></svg>
                            @break
                        @case('progress')
                            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="10" cy="10" r="2.5"/><path d="M10 3v2M10 15v2M3 10h2M15 10h2M5 5l1.4 1.4M13.6 13.6L15 15M15 5l-1.4 1.4M6.4 13.6L5 15"/></svg>
                            @break
                        @case('review')
                            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h6l3 3v11H6z"/><path d="M12 3v3h3"/><path d="M8.5 12.5l1.2 1.2L12.5 11"/></svg>
                            @break
                        @case('confirmed')
                            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="10" cy="10" r="7"/><path d="M6.5 10l2.2 2.2L14 7.5"/></svg>
                            @break
                        @case('closed')
                            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9.5l3 3 3-3M2 12.5l3 3 3-3"/><path d="M9 9.5l3 3 6-6M9 12.5l3 3 6-6"/></svg>
                            @break
                    @endswitch
                </div>
                <div class="tt-step-label">{{ $step['label'] }}</div>
                <div class="tt-step-time">{{ $step['time'] }}</div>
            </div>
            @if($i < count($steps) - 1)
                <div class="tt-connector {{ $step['state'] === 'done' ? 'filled' : '' }}"></div>
            @endif
        @endforeach
    </div>

    @if($status === 'Cancelled')
        <div class="tt-ribbon tt-ribbon-cancelled">
            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="10" cy="10" r="7"/><path d="M7 7l6 6M13 7l-6 6"/></svg>
            This support request was cancelled — no longer in progress.
        </div>
    @elseif($isCurrentlyEscalated)
        <div class="tt-ribbon tt-ribbon-escalated">
            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 3l8 14H2z"/><path d="M10 8.5v3.5M10 14.5h.01"/></svg>
            Currently escalated — Level {{ $t->escalation_level }}.
            @if($t->assignedTo) Being routed to {{ $t->assignedTo->name }}. @endif
        </div>
    @elseif($wasEverEscalated)
        <div class="tt-ribbon tt-ribbon-escalated-muted">
            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 3l8 14H2z"/><path d="M10 8.5v3.5M10 14.5h.01"/></svg>
            Escalated at {{ $fmt(\Carbon\Carbon::parse($escalationHistory->changed_at)) }} — full trail below.
        </div>
    @endif
</div>

@once
<style>
    .tt-card { background: var(--cr); border: 1.5px solid var(--bd); border-radius: 20px; padding: 24px 28px 20px; margin-bottom: 20px; }
    .tt-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
    .tt-title { font-family: 'Nunito', sans-serif; font-weight: 900; font-size: 14px; text-transform: uppercase; letter-spacing: .5px; color: var(--gd); display: flex; align-items: center; gap: 8px; }
    .tt-sub { font-size: 11px; color: var(--tm); font-weight: 600; margin-top: 3px; }

    .tt-owner-chip { display: flex; align-items: center; gap: 10px; background: #fff; border: 1.5px solid var(--bd); border-radius: 50px; padding: 6px 16px 6px 6px; }
    .tt-owner-av { width: 32px; height: 32px; border-radius: 50%; background: var(--gd); color: var(--yg); display: flex; align-items: center; justify-content: center; font-family: 'Nunito', sans-serif; font-weight: 900; font-size: 11px; flex-shrink: 0; }
    .tt-owner-lbl { font-size: 9px; font-weight: 800; color: var(--tm); text-transform: uppercase; letter-spacing: .5px; }
    .tt-owner-name { font-family: 'Nunito', sans-serif; font-weight: 800; font-size: 13px; color: var(--gd); }
    .tt-tier { font-size: 9px; font-weight: 900; padding: 2px 8px; border-radius: 20px; margin-left: 4px; letter-spacing: .3px; white-space: nowrap; }
    .tt-tier.tier-l1 { background: #e8f5ee; color: #1a5a3a; }
    .tt-tier.tier-l2 { background: #fff4cc; color: #7a5a00; }
    .tt-tier.tier-l3 { background: #fde8e8; color: #8b1a1a; }

    .tt-stepper { display: flex; align-items: flex-start; overflow-x: auto; padding-bottom: 2px; }
    .tt-step { display: flex; flex-direction: column; align-items: center; flex: 1; min-width: 78px; }
    .tt-step-circle { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all .2s; }
    .tt-step-circle svg { width: 16px; height: 16px; }
    .tt-step-label { font-size: 10.5px; font-weight: 800; color: var(--tm); text-align: center; margin-top: 8px; line-height: 1.3; padding: 0 4px; }
    .tt-step-time { font-size: 9.5px; color: var(--tm); font-weight: 600; margin-top: 3px; opacity: .75; }

    .tt-step.done .tt-step-circle { background: var(--gl); border: 2px solid var(--gm); }
    .tt-step.done .tt-step-circle svg { stroke: #fff; }
    .tt-step.done .tt-step-label { color: var(--gd); }

    .tt-step.current .tt-step-circle { background: var(--yg); border: 2px solid var(--gd); box-shadow: 0 0 0 4px rgba(200,230,60,.28); animation: ttPulse 2s ease-in-out infinite; }
    .tt-step.current .tt-step-circle svg { stroke: var(--gd); }
    .tt-step.current .tt-step-label { color: var(--gd); font-weight: 900; }

    .tt-step.future .tt-step-circle { background: #fff; border: 2px solid var(--bd); }
    .tt-step.future .tt-step-circle svg { stroke: var(--bd); }
    .tt-step.future .tt-step-label { color: #a8a49a; }

    @media (prefers-reduced-motion: reduce) { .tt-step.current .tt-step-circle { animation: none; } }
    @keyframes ttPulse {
        0%, 100% { box-shadow: 0 0 0 4px rgba(200,230,60,.28); }
        50%      { box-shadow: 0 0 0 7px rgba(200,230,60,.12); }
    }

    .tt-connector { flex: 1 1 0; height: 2px; margin-top: 17px; background: var(--bd); min-width: 14px; }
    .tt-connector.filled { background: var(--gl); }

    .tt-ribbon { display: flex; align-items: center; gap: 8px; border-radius: 10px; padding: 9px 14px; font-size: 12px; font-weight: 700; margin-top: 18px; }
    .tt-ribbon svg { width: 15px; height: 15px; flex-shrink: 0; }
    .tt-ribbon-escalated { background: #fde8e8; border: 1px solid #f0c0c0; color: #8b1a1a; }
    .tt-ribbon-escalated svg { stroke: #8b1a1a; }
    .tt-ribbon-escalated-muted { background: var(--cr, #f5f0e8); border: 1px solid var(--bd); color: var(--tm); }
    .tt-ribbon-escalated-muted svg { stroke: var(--tm); }
    .tt-ribbon-cancelled { background: #eee; border: 1px solid #ddd; color: #555; }
    .tt-ribbon-cancelled svg { stroke: #555; }
</style>
@endonce
