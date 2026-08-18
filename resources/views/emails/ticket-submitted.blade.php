<x-mail::message>
# Support Request Received — #{{ $ticket->ticket_number }}

Hi {{ $ticket->user->name ?? 'there' }},

We've received your request and it's now in the queue for review.

- **Subject:** {{ $ticket->subject }}
- **Describe the issue:** {{ $ticket->concern }}
- **Location:** {{ $ticket->location }}
- **Method:** {{ $ticket->method }}
- **Submitted:** {{ $ticket->created_at->format('M j, Y g:i A') }}

<x-mail::panel>
Double-check what you submitted above. Incomplete or unclear details can delay how quickly your request gets picked up — if you missed something, you can still add it from the support request's chat.
</x-mail::panel>

**What happens next:** Our ICT Team will review, schedule, and work on your request. You can check its progress anytime from your dashboard, where it moves through a simple set of stages — Submitted, Scheduled, In Progress, Awaiting Your Confirmation, and Closed. We'll email you again once it's ready for your confirmation — there's no need to keep checking in the meantime.

<x-mail::button :url="route('employee.tickets.show', $ticket)">
View Support Request
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
