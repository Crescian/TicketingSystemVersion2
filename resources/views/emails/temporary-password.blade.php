<x-mail::message>
# Your Password Has Been Reset

Hi {{ $user->name }},

An administrator has reset your account password. Use the temporary password below to log in, then change it as soon as possible.

<x-mail::panel>
{{ $temporaryPassword }}
</x-mail::panel>

<x-mail::button :url="route('login')">
Log In
</x-mail::button>

If you didn't expect this, contact your IT administrator.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
