{{-- Floating "Call us now" pill, bottom-left of every campaign landing page
     (landing/*.blade.php). Shown only when a contact phone is set. Its CSS lives
     here so it never depends on the Tailwind bundle having seen these classes.
     `lifted` raises it on a phone, for a page that keeps a sticky order bar
     along the bottom edge. --}}
@php
    $callPhone = \App\Models\Setting::get('contact_phone');
@endphp

@if ($callPhone)
    <style>
        .lp-call { position: fixed; left: 14px; bottom: 14px; z-index: 45; display: flex; align-items: center; gap: 10px; max-width: calc(100vw - 96px); padding: 5px 20px 5px 5px; border-radius: 999px; color: #fff; text-decoration: none; background: linear-gradient(180deg, #ff3b22 0%, #d40f0a 55%, #a80804 100%); box-shadow: 0 8px 22px rgba(168, 8, 4, .45), inset 0 1px 0 rgba(255, 255, 255, .35); transition: transform .2s ease; }
        .lp-call:hover { transform: scale(1.04); }
        .lp-call--lifted { bottom: 84px; }
        .lp-call__icon { position: relative; display: flex; flex: none; align-items: center; justify-content: center; width: 46px; height: 46px; border-radius: 50%; color: #d40f0a; background: radial-gradient(circle at 35% 30%, #fff 0%, #e9ecf1 60%, #c9ced8 100%); box-shadow: inset 0 -3px 6px rgba(0, 0, 0, .15), 0 2px 5px rgba(0, 0, 0, .25); animation: lp-call-ring 2.2s ease-out infinite; }
        .lp-call__icon svg { width: 24px; height: 24px; transform-origin: 50% 60%; animation: lp-call-wiggle 2.2s ease-in-out infinite; }
        .lp-call__text { display: flex; flex-direction: column; min-width: 0; line-height: 1.15; }
        .lp-call__label { font-size: 10px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; opacity: .92; }
        .lp-call__number { font-size: 16px; font-weight: 800; letter-spacing: .04em; white-space: nowrap; }
        @keyframes lp-call-ring { 0% { box-shadow: inset 0 -3px 6px rgba(0,0,0,.15), 0 0 0 0 rgba(255,255,255,.65); } 70%, 100% { box-shadow: inset 0 -3px 6px rgba(0,0,0,.15), 0 0 0 12px rgba(255,255,255,0); } }
        @keyframes lp-call-wiggle { 0%, 55%, 100% { transform: rotate(0); } 60% { transform: rotate(-14deg); } 70% { transform: rotate(12deg); } 80% { transform: rotate(-8deg); } 90% { transform: rotate(5deg); } }
        @media (min-width: 768px) {
            .lp-call { left: 22px; bottom: 22px; padding-right: 26px; }
            .lp-call--lifted { bottom: 22px; }
            .lp-call__icon { width: 54px; height: 54px; }
            .lp-call__icon svg { width: 28px; height: 28px; }
            .lp-call__label { font-size: 12px; }
            .lp-call__number { font-size: 22px; }
        }
        @media (prefers-reduced-motion: reduce) { .lp-call__icon, .lp-call__icon svg { animation: none; } .lp-call { transition: none; } }
    </style>

    <a href="tel:{{ preg_replace('/[^\d+]/', '', $callPhone) }}" class="lp-call {{ ($lifted ?? false) ? 'lp-call--lifted' : '' }}" aria-label="{{ __('Call us now!') }} {{ $callPhone }}">
        <span class="lp-call__icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" /></svg>
        </span>
        <span class="lp-call__text">
            <span class="lp-call__label">{{ __('Call us now!') }}</span>
            <span class="lp-call__number">{{ $callPhone }}</span>
        </span>
    </a>
@endif
