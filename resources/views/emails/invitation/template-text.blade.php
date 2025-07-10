@if($isResend)
=== RESENT INVITATION ===
This is a resent invitation. Your previous invitation may have expired or been missed.

@endif
{{ config('app.name', 'Analytics Hub') }} - User Invitation
{{ str_repeat('=', 50) }}

Hello {{ $user->first_name }},

You have been invited to join {{ config('app.name', 'Analytics Hub') }} by {{ $sender->full_name }}.

@if($customMessage)
Message from {{ $sender->first_name }}:
{{ str_repeat('-', 30) }}
{{ $customMessage }}
{{ str_repeat('-', 30) }}

@endif
@if($textContent)
{{ $textContent }}
@else
Your Login Credentials:
{{ str_repeat('-', 25) }}
Email: {{ $user->email }}
Username: {{ $user->username }}
Temporary Password: {{ $temporaryPassword }}

Login URL: {{ route('login') }}

Getting Started:
1. Visit the login URL above
2. Log in using your email/username and temporary password
3. You'll be prompted to create a new secure password
4. Complete your profile setup

IMPORTANT SECURITY INFORMATION:
- Change your password immediately after first login
- This temporary password expires in 7 days
- Never share your credentials with anyone
- Contact support if you didn't expect this invitation
@endif

If you need assistance, contact {{ $sender->full_name }} at {{ $sender->email }} or our support team.

Welcome aboard!
The {{ config('app.name', 'Analytics Hub') }} Team

{{ str_repeat('=', 50) }}
This invitation was sent by {{ $sender->full_name }} ({{ $sender->email }}) on {{ now()->format('F j, Y \a\t g:i A') }}.
© {{ date('Y') }} {{ config('app.name', 'Analytics Hub') }}. All rights reserved.
@if(config('app.url'))
{{ config('app.url') }}
@endif