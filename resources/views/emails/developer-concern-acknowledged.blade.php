<x-mail::message>
# Concern Acknowledged — #{{ $concern->reference }}

Hi {{ $concern->submitter->name ?? 'there' }},

The Developer has acknowledged your concern and is looking into it.

- **Subject:** {{ $concern->subject }}
- **Category:** {{ $concern->category }}
- **Urgency:** {{ $concern->urgency }}

You'll get another email once it's closed.

<x-mail::button :url="route('developer-concerns.index')">
View My Concerns
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
