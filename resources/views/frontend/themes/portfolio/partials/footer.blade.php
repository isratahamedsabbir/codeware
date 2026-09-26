{{--
    Portfolio theme footer — owned by the one-pager (home.blade.php), which is
    the only page this theme ships. Kept as a partial so the markup lives in one
    place rather than inline in the one view that uses it.

    Expects $siteName and $socials from the including view. $socials is a list of
    ['abbr' => 'FB', 'url' => '...'] entries (already filtered down to the
    platforms that actually have a URL, see SocialLink::url()).
--}}
<footer class="border-t border-(--pf-border) px-6 py-10">
    <div class="mx-auto flex max-w-6xl flex-col items-center gap-4 text-center sm:flex-row sm:justify-between sm:text-left">
        <p class="pf-mono text-xs text-(--pf-text-muted)">&copy; {{ now()->setTimezone(display_timezone())->year }} {{ $siteName }}. {{ __('All rights reserved.') }}</p>
        @if ($socials->isNotEmpty())
            <div class="flex gap-3">
                @foreach ($socials as $social)
                    <a href="{{ $social['url'] }}" target="_blank" rel="noopener" aria-label="{{ $social['abbr'] }}" class="pf-social-icon pf-mono text-[11px] font-bold">
                        {{ $social['abbr'] }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</footer>
