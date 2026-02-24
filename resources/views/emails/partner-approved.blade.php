<x-mail::message>
# Welcome to {{ config('app.name') }}, {{ $partner->name }}!

Your partner account has been **approved**. We're excited to have you on board!

To get started, please set your password by clicking the button below:

<x-mail::button :url="$resetUrl" color="success">
Set Your Password
</x-mail::button>

## Your Account Details

- **Email:** {{ $partner->email }}
- **Role:** Partner
- **Status:** Approved

<x-mail::panel>
**Important:** This link will expire in 60 minutes for security reasons.
</x-mail::panel>

If you didn't request this account, please ignore this email.

Thanks,<br>
{{ config('app.name') }} Team

---

<x-mail::subcopy>
Having trouble clicking the button? Copy and paste this URL into your browser:
{{ $resetUrl }}
</x-mail::subcopy>
</x-mail::message>
