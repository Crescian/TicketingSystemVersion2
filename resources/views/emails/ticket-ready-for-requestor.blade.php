<x-mail::message>
# FYI — #{{ $ticket->ticket_number }} Resolved

{{ $note }} The requestor has already been emailed and asked to confirm — no action needed from you.

- **Subject:** {{ $ticket->subject }}
- **Requestor:** {{ $ticket->user->name ?? 'N/A' }}
@if($ticket->resolution_notes)
- **Resolution Notes:** {{ $ticket->resolution_notes }}
@endif

<x-mail::button :url="route('helpdesk.dashboard', ['status' => 'awaiting-requestor'])">
View Ticket
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
