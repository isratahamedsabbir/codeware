@php
    $siteName = \App\Models\Setting::get('site_name', config('app.name'));
    $siteTagline = \App\Models\Setting::get('site_tagline');
    $contactAddress = \App\Models\Setting::get('contact_address');
    $contactPhone = \App\Models\Setting::get('contact_phone');
    $contactEmail = \App\Models\Setting::get('contact_email');

    $informationMenu = \App\Support\Frontend::informationMenu();
    $quickLinks = \App\Support\Frontend::quickLinks();

    $socials = collect([
        'facebook' => 'Facebook',
        'twitter' => 'Twitter / X',
        'instagram' => 'Instagram',
        'youtube' => 'YouTube',
        'linkedin' => 'LinkedIn',
    ])->map(fn ($label, $platform) => ['url' => \App\Models\SocialLink::url($platform), 'label' => $label])
      ->filter(fn ($social) => filled($social['url']));

    $socialStyles = [
        'facebook' => 'bg-[#0866ff]',
        'twitter' => 'bg-[#1f1f1f]',
        'youtube' => 'bg-[#ff0033]',
        'linkedin' => 'bg-[#0a66c2]',
        'instagram' => 'bg-[linear-gradient(90deg,#740ff3,#f502c8,#f72a25,#f79003,#f70f58)]',
    ];
@endphp

<footer class="mt-16 bg-sf-footer text-sf-footer-text">
    @if (\App\Support\Features::enabled('newsletter'))
        <div class="border-b border-sf-footer-text/10">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-4 py-8 sm:flex-row sm:gap-8 sm:px-6">
                <div class="text-center sm:text-left">
                    <h2 class="text-lg font-bold text-sf-footer-text">{{ __('Subscribe to our newsletter') }}</h2>
                    <p class="mt-1 text-sm text-sf-footer-text/70">{{ __('Get updates on new products and exclusive offers.') }}</p>
                </div>
                <div class="w-full sm:w-auto sm:min-w-[26rem]">
                    <livewire:frontend.newsletter-subscribe :key="'footer-newsletter'" />
                </div>
            </div>
        </div>
    @endif

    <div class="mx-auto grid max-w-7xl grid-cols-1 gap-8 px-4 py-10 sm:grid-cols-2 md:grid-cols-4 sm:px-6">
        <div>
            <a href="{{ url('/') }}" class="flex items-center gap-2">
                @if (\App\Models\Setting::get('site_icon'))
                    <img src="{{ \App\Models\Setting::get('site_icon') }}" alt="{{ $siteName }}" class="h-8 w-auto">
                @endif
                <span class="text-lg font-bold text-sf-footer-text">{{ $siteName }}</span>
            </a>
            @if ($siteTagline)
                <p class="mt-2 text-sm text-sf-footer-text/70">{{ $siteTagline }}</p>
            @endif
            @if ($contactAddress)
                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-sf-footer-text/70">{{ __('Address') }}: {{ $contactAddress }}</p>
            @endif
        </div>

        <div>
            <h3 class="mb-3 text-lg font-semibold text-sf-footer-text">{{ __('Quick Links') }}</h3>
            @if ($quickLinks->isNotEmpty())
                <ul class="space-y-2 text-sm">
                    @foreach ($quickLinks as $menuItem)
                        <li><a href="{{ url($menuItem->url) }}" class="font-medium text-sf-footer-text/80 hover:underline hover:text-sf-footer-text">{{ $menuItem->label }}</a></li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-sf-footer-text/70">{{ __('The quick links will appear here once they are published.') }}</p>
            @endif
        </div>

        <div>
            <h3 class="mb-3 text-lg font-semibold text-sf-footer-text">{{ __('Information') }}</h3>
            @if ($informationMenu->isNotEmpty())
                <ul class="space-y-2 text-sm">
                    @foreach ($informationMenu as $menuItem)
                        <li><a href="{{ url($menuItem->url) }}" class="font-medium text-sf-footer-text/80 hover:underline hover:text-sf-footer-text">{{ $menuItem->label }}</a></li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-sf-footer-text/70">{{ __('The information pages will appear here once they are published.') }}</p>
            @endif
        </div>

        <div>
            <h3 class="mb-3 text-lg font-semibold text-sf-footer-text">{{ __('Connect with us') }}</h3>
            @if ($socials->isNotEmpty())
                <div class="flex flex-wrap gap-2.5">
                    @foreach ($socials as $platform => $social)
                        <a href="{{ $social['url'] }}" target="_blank" rel="noopener"
                            aria-label="{{ $social['label'] }}"
                            title="{{ $social['label'] }}"
                            class="flex h-9 w-9 items-center justify-center rounded-full {{ $socialStyles[$platform] ?? 'bg-white/10' }} transition hover:scale-105">
                            @if ($platform === 'facebook')
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4"><path d="M15 3h3v4h-3c-1.1 0-2 .9-2 2v2h4l-1 4h-3v7h-4v-7H7v-4h3V9c0-3 2-6 5-6Z"/></svg>
                            @elseif ($platform === 'twitter')
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4"><path d="M17.7 3h3.1l-6.8 7.8L22 21h-6.3l-4.9-6.4L5 21H1.9l7.3-8.3L2 3h6.4l4.4 5.8L17.7 3Zm-1.1 16h1.7L7.5 4.7H5.6L16.6 19Z"/></svg>
                            @elseif ($platform === 'youtube')
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4"><path d="M23 7.5c0-1.7-1.2-3-2.7-3.2C18.5 4 15.3 4 12 4s-6.5 0-8.3.3C2.2 4.5 1 5.8 1 7.5 1 9.2 1 12 1 12s0 2.8 1 4.5c0 1.7 1.2 3 2.7 3.2C5.5 20 8.7 20 12 20s6.5 0 8.3-.3c1.5-.2 2.7-1.5 2.7-3.2 1-1.7 1-4.5 1-4.5s0-2.8-1-4.5ZM10 15.5v-7l6 3.5-6 3.5Z"/></svg>
                            @elseif ($platform === 'linkedin')
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4"><path d="M4.98 3.5A2.49 2.49 0 0 0 2.5 6a2.49 2.49 0 0 0 2.48 2.5A2.49 2.49 0 0 0 7.46 6a2.49 2.49 0 0 0-2.48-2.5ZM3 11h4v9H3v-9Zm6 0h3.8v1.3h.1c.5-.9 1.7-1.9 3.5-1.9 3.7 0 4.6 2.4 4.6 5.6V20h-4v-4.7c0-1.1 0-2.6-1.6-2.6s-1.8 1.2-1.8 2.5V20H9v-9Z"/></svg>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4"><path d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5Zm0 2a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3H7Zm5 4a4 4 0 1 1 0 8 4 4 0 0 1 0-8Zm0 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm5.5-3.5a1 1 0 1 1 0 2 1 1 0 0 1 0-2Z"/></svg>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
            @if ($contactPhone || $contactEmail)
                <div class="mt-3 flex flex-col gap-2 text-sm text-sf-footer-text/70">
                    @if ($contactPhone)
                        <a href="tel:{{ $contactPhone }}" class="inline-flex items-center gap-2 transition-colors hover:text-sf-footer-text">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                            </svg>
                            {{ $contactPhone }}
                        </a>
                    @endif
                    @if ($contactEmail)
                        <a href="mailto:{{ $contactEmail }}" class="inline-flex items-center gap-2 transition-colors hover:text-sf-footer-text">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                            </svg>
                            {{ $contactEmail }}
                        </a>
                    @endif
                </div>
            @endif
            <p class="mt-3 text-sm text-sf-footer-text/70">{{ __('We\'re always happy to hear from you.') }}</p>
        </div>
    </div>

    <div class="bg-sf-footer-bottom py-3">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 text-sm text-sf-footer-text/80 sm:flex-row sm:px-6">
            <p>&copy; {{ now()->setTimezone(display_timezone())->year }} {{ $siteName }}. {{ __('All rights reserved.') }}</p>
            <a href="https://codewarelimited.com" target="_blank" rel="noopener" class="font-semibold text-[#f7941d] hover:underline">
                {{ __('Developed by Codeware Limited') }}
            </a>
        </div>
    </div>
</footer>

<livewire:frontend.announce-popup />