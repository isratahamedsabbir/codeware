{{--
    Default theme footer — the copyright bar and the social links.

    A partial for the same reason as partials/header.blade.php: the theme's 404
    shares it, so the markup lives in one place. Reads its own settings.
--}}
@php
    $siteName = \App\Models\Setting::get('site_name', config('app.name'));

    $socials = collect([
        'facebook' => 'Facebook',
        'twitter' => 'Twitter / X',
        'instagram' => 'Instagram',
        'youtube' => 'YouTube',
        'linkedin' => 'LinkedIn',
    ])->map(fn ($label, $platform) => ['url' => \App\Models\SocialLink::url($platform), 'label' => $label])
      ->filter(fn ($social) => filled($social['url']));
@endphp

<footer class="border-t border-zinc-100 bg-zinc-50 px-6 py-10">
    <div class="mx-auto flex max-w-6xl flex-col items-center gap-4 text-center sm:flex-row sm:justify-between sm:text-left">
        <p class="text-sm text-zinc-500">&copy; {{ now()->setTimezone(display_timezone())->year }} {{ $siteName }}. {{ __('All rights reserved.') }}</p>
        @if ($socials->isNotEmpty())
            <div class="flex gap-4">
                @foreach ($socials as $social)
                    <a href="{{ $social['url'] }}" target="_blank" rel="noopener" class="text-sm text-zinc-500 hover:text-primary">
                        {{ $social['label'] }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</footer>
