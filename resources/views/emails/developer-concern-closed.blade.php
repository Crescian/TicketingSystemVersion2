<x-mail::message>
# Concern Closed — #{{ $concern->reference }}

Hi {{ $concern->submitter->name ?? 'there' }},

The Developer has closed your concern **{{ $concern->subject }}**.

<x-mail::panel>
{{ $concern->closing_note }}
</x-mail::panel>

If the problem comes back, send a new concern from the Report to Developer page.

<x-mail::button :url="route('developer-concerns.index')">
View My Concerns
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
