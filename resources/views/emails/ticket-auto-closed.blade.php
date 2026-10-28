<x-mail::message>
# Support Request Automatically Closed — #{{ $ticket->ticket_number }}

Hi {{ $ticket->user->name ?? 'there' }},

Your resolved support request was awaiting your confirmation, but we didn't hear back within {{ \App\Console\Commands\AutoCloseStaleConfirmations::THRESHOLD_DAYS }} days — so the system has automatically confirmed and closed it. The service report is attached for your records.

- **Subject:** {{ $ticket->subject }}
@if($ticket->resolution_notes)
- **Resolution Notes:** {{ $ticket->resolution_notes }}
@endif
@if($ticket->assignedTo)
- **Resolved By:** {{ $ticket->assignedTo->name }}
@endif

<x-mail::panel>
If the issue isn't actually resolved, no problem — just submit a new support request and reference this ticket number.
</x-mail::panel>

<x-mail::button :url="route('employee.tickets.show', $ticket)">
View Ticket
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
