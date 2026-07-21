<x-mail::message>
# Action Needed — #{{ $ticket->ticket_number }}

{{ $action }}

- **Subject:** {{ $ticket->subject }}
- **Describe the issue:** {{ $ticket->concern }}
- **Location:** {{ $ticket->location }}
- **Priority:** {{ $ticket->ticket_type }}

<x-mail::button :url="route($dashboardRoute)">
Open Dashboard
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
