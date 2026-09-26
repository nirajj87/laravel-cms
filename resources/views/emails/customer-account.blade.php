<x-mail::message>
# Welcome to {{ $tenant->name }}

Your customer account is ready. Use these details to sign in:

**Email:** {{ $customer->email }}  
**Password:** {{ $plainPassword }}

<x-mail::button :url="$loginUrl">
Sign in
</x-mail::button>

Or open this link: {{ $loginUrl }}

Thanks,<br>
{{ $tenant->name }}
</x-mail::message>