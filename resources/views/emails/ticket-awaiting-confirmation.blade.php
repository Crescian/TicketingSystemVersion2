<x-mail::message>
# Action Needed: Confirm Your Resolved Support Request — #{{ $ticket->ticket_number }}

Hi {{ $ticket->user->name ?? 'there' }},

@if($isReminder ?? false)
This is a reminder — your request is still awaiting your confirmation so we can close it out.
@else
The completed service report for your request has been reviewed, and it's now ready for your confirmation.
@endif

- **Subject:** {{ $ticket->subject }}
@if($ticket->resolution_notes)
- **Resolution Notes:** {{ $ticket->resolution_notes }}
@endif
@if($ticket->assignedTo)
- **Resolved By:** {{ $ticket->assignedTo->name }}
@endif

<x-mail::panel>
Please review the resolution and confirm it from your dashboard. Once confirmed, this support request will be closed.
</x-mail::panel>

<x-mail::button :url="route('employee.tickets.show', $ticket)">
Confirm Resolution
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
