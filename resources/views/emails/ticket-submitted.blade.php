<x-mail::message>
# Ticket Received — #{{ $ticket->ticket_number }}

Hi {{ $ticket->user->name ?? 'there' }},

We've received your request and it's now in the queue for review.

- **Subject:** {{ $ticket->subject }}
- **Describe the issue:** {{ $ticket->concern }}
- **Location:** {{ $ticket->location }}
- **Method:** {{ $ticket->method }}
- **Priority:** {{ $ticket->ticket_type }}
- **Submitted:** {{ $ticket->created_at->format('M j, Y g:i A') }}

We'll notify you again once it's resolved. You can check its status anytime from your dashboard.

<x-mail::button :url="route('employee.tickets.show', $ticket)">
View Ticket
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
