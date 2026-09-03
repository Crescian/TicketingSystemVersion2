<x-mail::message>
# We Need More Information — #{{ $ticket->ticket_number }}

Hi {{ $ticket->user->name ?? 'there' }},

Work on your support request has been paused while we wait to hear back from
you. Here's what's needed:

<x-mail::panel>
{{ $reason }}
</x-mail::panel>

- **Subject:** {{ $ticket->subject }}
@if($ticket->assignedTo)
- **Handled By:** {{ $ticket->assignedTo->name }}
@endif

Once you reply, work will resume right away — no need to submit a new request.

<x-mail::button :url="route('employee.tickets.show', $ticket)">
View Ticket
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
