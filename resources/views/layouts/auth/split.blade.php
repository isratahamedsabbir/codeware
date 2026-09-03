<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.head')
    <meta name="robots" content="noindex, nofollow">
    @if (filled($description ?? null))
        <meta name="description" content="{{ $description }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <style>
        /* ── Auth Layout: Brand-themed premium design ── */
        /* Primary: #1e7bc4 (blue) | Secondary: #7cc242 (green) | Base: sidebar navy */

        * { box-sizing: border-box; }

        body.auth-bg {
            font-family: 'Plus Jakarta Sans', 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
            min-height: 100vh;
            /* Sidebar navy as the dark base */
            background: #0d1d31;
            background-image:
                radial-gradient(ellipse 90% 65% at 50% -5%,  rgba(30, 123, 196, 0.55) 0%, transparent 65%),
                radial-gradient(ellipse 55% 45% at 92% 85%,  rgba(124, 194, 66, 0.30) 0%, transparent 60%),
                radial-gradient(ellipse 45% 40% at 5%  75%,  rgba(30, 123, 196, 0.22) 0%, transparent 55%);
            position: relative;
            overflow: hidden;
        }

        /* ── Subtle dot-grid overlay ── */
        .auth-grid {
            position: fixed;
            inset: 0;
            z-index: 0;
            background-image:
                radial-gradient(circle, rgba(255,255,255,0.06) 1px, transparent 1px);
            background-size: 32px 32px;
            pointer-events: none;
        }

        /* ── Large ambient glowing blobs (pseudo-elements on body) ── */
        .auth-bg::before {
            content: '';
            position: fixed;
            width: 700px; height: 700px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(30, 123, 196, 0.28) 0%, transparent 68%);
            top: -200px; left: 50%;
            transform: translateX(-50%);
            filter: blur(60px);
            pointer-events: none; z-index: 0;
            animation: blobFloat1 12s ease-in-out infinite alternate;
        }
        .auth-bg::after {
            content: '';
            position: fixed;
            width: 500px; height: 500px;
            border-radius: 45% 55% 60% 40% / 50% 40% 60% 50%;
            background: radial-gradient(circle, rgba(124, 194, 66, 0.22) 0%, transparent 70%);
            bottom: -150px; right: -100px;
            filter: blur(55px);
            pointer-events: none; z-index: 0;
            animation: blobFloat2 15s ease-in-out infinite alternate;
        }

        /* ── Decorative rings ── */
        .auth-ring {
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
        }
        /* Big primary-colored ring top-left */
        .auth-ring-1 {
            width: 520px; height: 520px;
            border: 1.5px solid rgba(30, 123, 196, 0.18);
            top: -180px; left: -180px;
            animation: spinSlow 40s linear infinite;
        }
        /* Dashed secondary ring bottom-right */
        .auth-ring-2 {
            width: 420px; height: 420px;
            border: 2px dashed rgba(124, 194, 66, 0.20);
            bottom: -150px; right: -130px;
            animation: spinSlow 30s linear infinite reverse;
        }
        /* Medium inner ring, top-right */
        .auth-ring-3 {
            width: 280px; height: 280px;
            border: 1px solid rgba(30, 123, 196, 0.14);
            top: -60px; right: -60px;
            animation: spinSlow 25s linear infinite;
        }
        /* Tiny accent ring, bottom-left */
        .auth-ring-4 {
            width: 180px; height: 180px;
            border: 1.5px solid rgba(124, 194, 66, 0.22);
            bottom: 60px; left: 40px;
            animation: spinSlow 20s linear infinite reverse;
        }

        /* Tick marks on the big ring (cross-hair feel) */
        .auth-ring-1::before,
        .auth-ring-1::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            background: rgba(30, 123, 196, 0.55);
        }
        .auth-ring-1::before { width: 10px; height: 10px; top: -5px; left: 50%; transform: translateX(-50%); }
        .auth-ring-1::after  { width: 7px;  height: 7px;  bottom: -4px; left: 50%; transform: translateX(-50%); }

        .auth-ring-2::before {
            content: '';
            position: absolute;
            width: 8px; height: 8px;
            border-radius: 50%;
            background: rgba(124, 194, 66, 0.6);
            top: -4px; left: 50%; transform: translateX(-50%);
        }

        /* ── Floating accent squares / diamonds ── */
        .auth-shape {
            position: fixed;
            pointer-events: none;
            z-index: 0;
            opacity: 0;
            animation: shapeFade 6s ease-in-out infinite;
        }
        .auth-shape-1 {
            width: 14px; height: 14px;
            border: 2px solid rgba(30, 123, 196, 0.5);
            border-radius: 3px;
            top: 22%; left: 8%;
            animation-delay: 0s;
            transform: rotate(20deg);
        }
        .auth-shape-2 {
            width: 10px; height: 10px;
            background: rgba(124, 194, 66, 0.45);
            border-radius: 2px;
            top: 68%; left: 6%;
            animation-delay: 1.5s;
            transform: rotate(45deg);
        }
        .auth-shape-3 {
            width: 12px; height: 12px;
            border: 2px solid rgba(124, 194, 66, 0.4);
            border-radius: 3px;
            top: 28%; right: 7%;
            animation-delay: 0.8s;
            transform: rotate(-15deg);
        }
        .auth-shape-4 {
            width: 8px; height: 8px;
            background: rgba(30, 123, 196, 0.5);
            border-radius: 50%;
            top: 78%; right: 12%;
            animation-delay: 2.2s;
        }
        .auth-shape-5 {
            width: 16px; height: 16px;
            border: 1.5px solid rgba(30, 123, 196, 0.35);
            border-radius: 50%;
            top: 50%; left: 14%;
            animation-delay: 3s;
        }

        /* ── Glass card ── */
        .auth-card {
            background: rgba(255, 255, 255, 0.07);
            border: 1px solid rgba(255, 255, 255, 0.14);
            box-shadow:
                0 0 0 1px rgba(30, 123, 196, 0.12),
                0 8px 32px  rgba(0, 0, 0, 0.40),
                0 40px 80px rgba(0, 0, 0, 0.28),
                inset 0 1px 0 rgba(255, 255, 255, 0.10);
            backdrop-filter: blur(28px) saturate(1.5);
            -webkit-backdrop-filter: blur(28px) saturate(1.5);
            position: relative;
            z-index: 1;
        }

        /* Top accent gradient line — blends primary → secondary */
        .auth-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: linear-gradient(
                90deg,
                transparent 0%,
                rgba(30, 123, 196, 0.9)  30%,
                rgba(124, 194, 66, 0.9)  70%,
                transparent 100%
            );
            border-radius: 2px 2px 0 0;
        }

        /* ── Logo glow ── */
        .auth-logo-wrap img {
            filter: drop-shadow(0 0 10px rgba(30, 123, 196, 0.45));
            transition: filter 0.3s ease;
        }
        .auth-logo-wrap img:hover {
            filter: drop-shadow(0 0 18px rgba(30, 123, 196, 0.75));
        }

        /* ── Footer ── */
        .auth-footer {
            color: rgba(179, 202, 224, 0.45);
            position: relative;
            z-index: 1;
        }

        /* ── Entry animation ── */
        .auth-animate {
            animation: fadeSlideUp 0.65s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        /* ── Keyframes ── */
        @keyframes blobFloat1 {
            0%   { transform: translateX(-50%) translateY(0px) scale(1); }
            100% { transform: translateX(-50%) translateY(40px) scale(1.06); }
        }
        @keyframes blobFloat2 {
            0%   { transform: scale(1)    rotate(0deg); }
            100% { transform: scale(1.12) rotate(20deg); }
        }
        @keyframes spinSlow {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
        }
        @keyframes shapeFade {
            0%, 100% { opacity: 0;    transform: translateY(0px)   rotate(var(--r, 0deg)); }
            40%, 60% { opacity: 1;    transform: translateY(-8px)  rotate(var(--r, 0deg)); }
        }
        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(28px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0)    scale(1); }
        }
    </style>
</head> 

<body class="min-h-screen auth-bg antialiased flex items-center justify-center p-4 sm:p-6 overflow-y-auto!"> 

    {{-- Background layer --}}
    <div class="auth-grid"></div> 

    {{-- Decorative rings --}}
    <div class="auth-ring auth-ring-1"></div>
    <div class="auth-ring auth-ring-2"></div>
    <div class="auth-ring auth-ring-3"></div>
    <div class="auth-ring auth-ring-4"></div>

    {{-- Floating shapes --}}
    <div class="auth-shape auth-shape-1"></div>
    <div class="auth-shape auth-shape-2"></div>
    <div class="auth-shape auth-shape-3"></div>
    <div class="auth-shape auth-shape-4"></div>
    <div class="auth-shape auth-shape-5"></div> 

    <div class="w-full max-w-[420px] flex flex-col gap-5 auth-animate">   

        {{-- Auth Card --}}
        <div class="auth-card rounded-2xl overflow-hidden">  

            {{-- Slot: login/register form --}}
            <div class="px-8 py-8">
                @php
                    $siteIcon = \App\Models\Setting::get('site_icon');
                @endphp
                <div class="auth-logo-wrap mb-6 flex justify-center"> 
                    <img src="{{ $siteIcon ?: '/default/logo.png' }}" alt="{{ config('app.name') }}"
                        class="max-w-[100px] block">
                </div> 
                {{ $slot }}
            </div> 

        </div>

        {{-- Footer --}}
        <p class="auth-footer text-center text-xs font-medium select-none">
            &copy; {{ date('Y') }} Codeware Limited. All rights reserved.
        </p>

    </div>

    @include('partials.auth-loader-overlay')

    @fluxScripts
</body>

</html>
