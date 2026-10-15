<x-mail::message>
# New Developer Concern — #{{ $concern->reference }}

{{ $concern->submitter->name ?? 'An ICT staff member' }}@if($concern->submitter?->role) ({{ $concern->submitter->role->role_name }})@endif sent you a concern:

<x-mail::panel>
**{{ $concern->subject }}**

{{ $concern->details }}
</x-mail::panel>

- **Category:** {{ $concern->category }}
- **Urgency:** {{ $concern->urgency }}

<x-mail::button :url="route('developer.dashboard')">
Open Developer Inbox
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
