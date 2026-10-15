{{-- One IT Support Specialist row for the sidebar availability roster — shared
     by the Helpdesk and Supervisor (Support) dashboards. Line 1 is ticket
     workload (always meaningful, even after hours); line 2 + bar are the
     TicketScheduler free-time capacity, which only means something while the
     business day is open. Expects $tech annotated by the controller with
     in_progress_count / queued_count / on_hold_count, availability,
     schedule_status, free_minutes_today, free_time_label, free_window_today. --}}
@php
    $initials = strtoupper(substr($tech->name, 0, 1)) .
                strtoupper(substr($tech->name, strpos($tech->name, ' ') + 1, 1));

    $workload = collect([
        $tech->in_progress_count ? $tech->in_progress_count . ' in progress' : null,
        $tech->queued_count ? $tech->queued_count . ' queued' : null,
        $tech->on_hold_count ? $tech->on_hold_count . ' on hold' : null,
    ])->filter()->implode(' · ') ?: 'No open tickets';

    $window = $tech->free_window_today ?? null;
    $timeLabel = $window
        ? $tech->free_time_label . ' (' . $window['start']->timezone('Asia/Manila')->format('g:i A')
            . ' – ' . $window['end']->timezone('Asia/Manila')->format('g:i A') . ')'
        : $tech->free_time_label;

    $showBar = $tech->schedule_status !== 'on_leave'
        && \App\Services\TicketScheduler::isWithinBusinessHours();
    // Bar fills with how much of the 8am-5pm day is still OPEN (same as the
    // assign modal) — a long bar reads as "lots of room left".
    $freePct = min(100, round($tech->free_minutes_today / \App\Services\TicketScheduler::WORKING_MINUTES_PER_DAY * 100));
    $barClass = in_array($tech->availability, ['busy', 'full'], true) ? $tech->availability : '';

    $dotTitle = match ($tech->schedule_status) {
        'overtime' => 'Overtime',
        'on_leave' => 'On approved leave',
        default => ucfirst($tech->availability),
    } . ' · ' . $tech->free_time_label;
@endphp
<div class="tech-row">
    <div class="tech-av-lg">{{ $initials }}</div>
    <div class="flex-grow-1" style="min-width:0">
        <div class="tech-name">{{ $tech->name }}</div>
        <div class="tech-load">{{ $workload }}</div>
        <div class="tech-load">{{ $timeLabel }}</div>
        @if($showBar)
            <div class="load-bar-wrap"><div class="load-bar {{ $barClass }}" style="width:{{ $freePct }}%"></div></div>
        @endif
    </div>
    <div class="avail-dot {{ $tech->availability }}" title="{{ $dotTitle }}"></div>
</div>
