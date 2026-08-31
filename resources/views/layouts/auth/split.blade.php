<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.head')
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <style>
        .auth-bg {
            background: radial-gradient(ellipse at 60% 0%, rgba(124, 194, 66, 0.07) 0%, transparent 60%),
                radial-gradient(ellipse at 10% 100%, rgba(59, 130, 246, 0.06) 0%, transparent 55%),
                #f8fafc;
        }

        .auth-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow:
                0 1px 3px rgba(0, 0, 0, 0.04),
                0 8px 32px rgba(0, 0, 0, 0.07),
                0 32px 64px rgba(0, 0, 0, 0.04);
        }
    </style>
</head>

<body class="min-h-screen auth-bg antialiased font-sans flex items-center justify-center p-4 sm:p-6">

    <div class="w-full max-w-[420px] flex flex-col gap-5">

        {{-- Auth Card --}}
        <div class="auth-card rounded-2xl overflow-hidden">



            {{-- Slot: login/register form --}}
            <div class="px-8 py-7">
                @php
                    $siteIcon = \App\Models\Setting::get('site_icon');
                @endphp
                <img src="{{ $siteIcon ?: '/default/logo.png' }}" alt="{{ config('app.name') }}"
                    class="h-7 w-auto block mx-auto mb-6">
                {{ $slot }}
            </div>

        </div>

        {{-- Footer --}}
        <p class="text-center text-xs text-slate-400 font-medium select-none">
            &copy; {{ date('Y') }} Codeware Limited. All rights reserved.
        </p>

    </div>

    @include('partials.auth-loader-overlay')

    @fluxScripts
</body>

</html>
