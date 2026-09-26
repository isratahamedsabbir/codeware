{{--
    The portfolio theme's social links, rendered as real brand marks.

    Replaces the two-letter abbreviations ("FB", "X", "IG") the theme used to
    print for each platform. This partial exists so the hero row, the contact
    grid, the footer and the 404 all resolve the same platform list the same way
    — the list used to be copy-pasted into three views, which is how the hero and
    the footer drifted apart.

    Expects $socials from the including view: a list of
    ['platform' => 'github', 'label' => 'GitHub', 'url' => '...'] entries,
    already filtered down to the platforms that actually have a URL (see
    SocialLink::url()). Built by the including view rather than here, because the
    header partial reads the same $socials.

    Variants:
        'row'   — inline circular icon buttons (hero, footer)
        'tiles' — labelled cards in a grid (the contact section)

    Usage:
        @include('frontend.themes.portfolio.partials.social-links', ['variant' => 'row'])
--}}
@php
    $variant = (string) ($variant ?? 'row');

    // The admin can add a platform this theme has no mark for; the icon partial
    // falls back to its initial, so the list needs no per-platform branching.
    $platformLabels = [
        'facebook' => 'Facebook',
        'twitter' => 'X',
        'instagram' => 'Instagram',
        'youtube' => 'YouTube',
        'linkedin' => 'LinkedIn',
        'tiktok' => 'TikTok',
        'github' => 'GitHub',
        'gitlab' => 'GitLab',
        'behance' => 'Behance',
        'dribbble' => 'Dribbble',
        'whatsapp' => 'WhatsApp',
        'telegram' => 'Telegram',
    ];
@endphp

@if ($socials->isNotEmpty())
    @if ($variant === 'tiles')
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            @foreach ($socials as $social)
                <a href="{{ $social['url'] }}" target="_blank" rel="noopener"
                    class="pf-connect-tile group flex items-center gap-3 rounded-xl p-3.5 text-left">
                    <span class="pf-connect-icon flex h-9 w-9 shrink-0 items-center justify-center rounded-lg">
                        @include('frontend.themes.portfolio.partials.social-icon', [
                            'platform' => $social['platform'],
                            'class' => 'h-[18px] w-[18px]',
                        ])
                    </span>
                    <span class="min-w-0">
                        <span class="pf-heading block truncate text-[13px] font-semibold">{{ $social['label'] }}</span>
                        <span class="pf-mono block truncate text-[10px] text-(--pf-text-muted)">Visit profile</span>
                    </span>
                </a>
            @endforeach
        </div>
    @else
        <div class="flex flex-wrap items-center gap-2.5">
            @foreach ($socials as $social)
                <a href="{{ $social['url'] }}" target="_blank" rel="noopener" title="{{ $social['label'] }}"
                    aria-label="{{ $social['label'] }}" class="pf-social-icon">
                    @include('frontend.themes.portfolio.partials.social-icon', [
                        'platform' => $social['platform'],
                        'class' => 'h-[18px] w-[18px]',
                    ])
                </a>
            @endforeach
        </div>
    @endif
@endif
