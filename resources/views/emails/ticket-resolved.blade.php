<x-mail::message>
# Your Support Request Has Been Resolved — #{{ $ticket->ticket_number }}

Hi {{ $ticket->user->name ?? 'there' }},

Good news — **{{ $ticket->assignedTo->name ?? 'our support team' }}** has resolved your support request and it's now awaiting your confirmation before it can be closed.

- **Subject:** {{ $ticket->subject }}
- **Describe the issue:** {{ $ticket->concern }}
- **Location:** {{ $ticket->location }}

Please review the resolution and confirm it from the support request page. If the issue isn't fully fixed, let us know there instead of confirming.

<x-mail::button :url="route('employee.tickets.show', $ticket)">
Review & Confirm
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
