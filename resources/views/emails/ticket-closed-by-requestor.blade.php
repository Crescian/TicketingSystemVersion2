<x-mail::message>
# FYI — #{{ $ticket->ticket_number }} Closed

The requestor confirmed the resolution — no action needed from you.

- **Subject:** {{ $ticket->subject }}
- **Requestor:** {{ $ticket->user->name ?? 'N/A' }}
@if($ticket->resolvedBy)
- **Resolved By:** {{ $ticket->resolvedBy->name }}
@endif
@if($ticket->resolution_notes)
- **Resolution Notes:** {{ $ticket->resolution_notes }}
@endif

<x-mail::button :url="route('helpdesk.dashboard', ['status' => 'closed'])">
View Ticket
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
