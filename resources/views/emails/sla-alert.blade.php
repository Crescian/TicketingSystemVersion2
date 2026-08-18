@php
    $seconds = $ticket->slaSecondsRemaining();
    $abs = abs($seconds ?? 0);
    $h = intdiv($abs, 3600);
    $m = intdiv($abs % 3600, 60);
    $duration = ($h > 0 ? $h . 'h ' : '') . $m . 'm';
@endphp
<x-mail::message>
@if($level === 'breached')
# SLA Breached — #{{ $ticket->ticket_number }}

This support request has exceeded its SLA resolution deadline and is still **{{ $ticket->status }}**.
It is currently overdue by **{{ $duration }}**.
@else
# SLA At Risk — #{{ $ticket->ticket_number }}

This support request has reached 75% of its SLA resolution time and is still **{{ $ticket->status }}**.
It has **{{ $duration }}** remaining before the SLA deadline.
@endif

- **Subject:** {{ $ticket->subject }}
- **Describe the issue:** {{ $ticket->concern }}
- **Location:** {{ $ticket->location }}
- **Priority:** {{ $ticket->ticket_type }}
- **Assigned to:** {{ $ticket->assignedTo?->name ?? 'Unassigned' }}
- **Started at:** {{ $ticket->started_at?->format('M j, Y g:i A') }}
- **SLA due:** {{ $ticket->sla_due_at?->format('M j, Y g:i A') }}

<x-mail::button :url="route($ticket->assignedTo?->role?->role_name === 'IT Admin' ? 'supervisor.dashboard' : 'supervisor.support.dashboard')">
View in Supervisor Queue
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
