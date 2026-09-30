{{--
    The page loader — the sheet shown while this page's CSS, fonts and images come
    in. Included by the home page only: it is the curtain over a *landing*, and
    the blog and posts open on content without one.

    It is one ink-coloured sheet carrying the site's own mark, and a hairline
    that fills from the left while the page settles underneath. The mark is the
    portfolio's existing lockup — the same monogram and name the hero is built
    from — so the curtain is the site introducing itself rather than a device
    animation sitting on top of it.

    Markup only. Every decision about whether this is ever shown, and about when
    it goes away, belongs to loader-flag.blade.php, first in <head>: the sheet is
    display:none by default and is revealed only when that gate has decided this
    browser has not seen the effect before (the pf-first-visit class), and the
    timings that lift it are pinned to this animation's own duration. A returning
    visit never even considers it.

    The dismissal lives in the flag script rather than here, because the two
    halves of the same decision should not be able to disagree: the sheet and the
    thing that raises and lowers it are written side by side, and a loader that
    is up with nobody left to take it down is the one failure this file exists to
    make impossible.

    The page underneath is never hidden — it is laid out and painted from the
    start, and this is an opaque sheet over it — so lifting costs one opacity
    change and no layout.
--}}
<div data-pf-loader class="pf-loader" aria-hidden="true">
    <div class="pf-loader-mark">
        @if (filled($profile['monogram'] ?? null))
            <span class="pf-loader-monogram">{{ $profile['monogram'] }}</span>
            <span class="pf-loader-rule" aria-hidden="true"></span>
        @endif

        @if (filled($siteName ?? null))
            <span class="pf-loader-name">{{ $siteName }}</span>
        @endif
    </div>

    <div class="pf-loader-track" aria-hidden="true">
        <span class="pf-loader-bar"></span>
    </div>
</div>
