<x-mail::message>
# Ticket #{{ $ticket->ticket_number }} — New {{ $messages->count() === 1 ? 'Message' : 'Messages' }}

{{ $messages->count() === 1 ? 'You have a new message' : 'You have new messages' }} on your support ticket. Reply in the ticket's chat to keep it moving.

- **Subject:** {{ $ticket->subject }}

<x-mail::panel>
@foreach ($messages as $message)
**{{ $message->sender->name ?? 'Support' }}** — {{ $message->created_at->timezone('Asia/Manila')->format('M d, g:i A') }}
{{ $message->message }}

@endforeach
</x-mail::panel>

<x-mail::button :url="route('employee.tickets.show', $ticket)">
Reply in Ticket
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
