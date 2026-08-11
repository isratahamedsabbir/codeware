<x-mail::message>
# Application Received

Dear {{ $application->name }},

Thank you for applying to **{{ $application->job?->getTranslation('title', 'en', useFallbackLocale: true) ?? 'the position' }}** at {{ config('app.name') }}.

We have received your application and it is currently under review. Our team will carefully evaluate your profile and get back to you if your qualifications match our requirements.

**Application Details:**
- **Position:** {{ $application->job?->position ?? '—' }}
- **Applied:** {{ $application->created_at->format('d M Y') }}

We appreciate your interest in joining our team. Please do not reply to this email — it is sent automatically.

Thanks,<br>
{{ config('app.name') }} HR Team
</x-mail::message>
