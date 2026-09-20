@php
    $siteName = \App\Models\Setting::get('site_name', config('app.name'));
    $siteTagline = \App\Models\Setting::get('site_tagline');

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

<footer class="mt-16 bg-brand text-white">
    <div class="mx-auto grid max-w-7xl grid-cols-1 gap-8 px-4 py-10 sm:grid-cols-2 md:grid-cols-4 sm:px-6">
        <div>
            <a href="{{ url('/') }}" class="flex items-center gap-2">
                @if (\App\Models\Setting::get('site_icon'))
                    <img src="{{ \App\Models\Setting::get('site_icon') }}" alt="{{ $siteName }}" class="h-8 w-auto">
                @endif
                <span class="text-lg font-bold text-white">{{ $siteName }}</span>
            </a>
            @if ($siteTagline)
                <p class="mt-2 text-sm text-white/70">{{ $siteTagline }}</p>
            @endif
        </div>

        <div>
            <h3 class="mb-3 text-lg font-semibold text-white">{{ __('Quick Links') }}</h3>
            <ul class="space-y-2 text-sm">
                <li><a href="{{ route('home') }}" class="font-medium text-white/80 hover:underline hover:text-white">{{ __('Home') }}</a></li>
                <li><a href="{{ route('shop') }}" class="font-medium text-white/80 hover:underline hover:text-white">{{ __('Shop') }}</a></li>
                <li><a href="{{ route('favorites') }}" class="font-medium text-white/80 hover:underline hover:text-white">{{ __('My favorites') }}</a></li>
                @foreach (($menuItems ?? [])->take(5) as $menuItem)
                    <li><a href="{{ url($menuItem->url) }}" class="font-medium text-white/80 hover:underline hover:text-white">{{ $menuItem->label }}</a></li>
                @endforeach
            </ul>
        </div>

        <div>
            <h3 class="mb-3 text-lg font-semibold text-white">{{ __('Information') }}</h3>
            @if (($navPages ?? collect())->isNotEmpty())
                <ul class="space-y-2 text-sm">
                    @foreach ($navPages as $navPage)
                        <li><a href="{{ url($navPage->slug) }}" class="font-medium text-white/80 hover:underline hover:text-white">{{ $navPage->name }}</a></li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-white/70">{{ __('The information pages will appear here once they are published.') }}</p>
            @endif
        </div>

        <div>
            <h3 class="mb-3 text-lg font-semibold text-white">{{ __('Connect with us') }}</h3>
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
            <p class="mt-3 text-sm text-white/70">{{ __('We\'re always happy to hear from you.') }}</p>
        </div>
    </div>

    <div class="bg-brand-ink py-3">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 text-sm text-white/80 sm:flex-row sm:px-6">
            <p>&copy; {{ now()->setTimezone(display_timezone())->year }} {{ $siteName }}. {{ __('All rights reserved.') }}</p>
            <a href="https://codewarelimited.com" target="_blank" rel="noopener" class="font-semibold text-[#f7941d] hover:underline">
                {{ __('Developed by Codeware Limited') }}
            </a>
        </div>
    </div>
</footer>