{{--
    The page loader — the one sheet shown while this page's CSS, fonts and
    images come in. Included by the home page only: it is the curtain over a
    *landing*, and the blog and posts open on content without one.

    It is a full-viewport black sheet, styled as a CRT power-on — but a screen
    that parts from the middle: two solid black plates slide apart while a
    translucent white band swells behind them, so the portfolio beneath is
    revealed through a widening seam instead of sitting behind an opaque wall
    the whole time.

    Markup only. Every decision about whether this is ever shown belongs to
    loader-flag.blade.php, first in <head>: the sheet is display:none by
    default and is revealed only when that gate has decided the page is slow
    enough to be worth covering (the pf-slow-visit class) and that this browser
    has not seen the effect before (pf-first-visit). A fast first visit never
    renders it, and a returning visit never even considers it.

    The dismissal lives in the flag script too, rather than here, because the
    two halves of the same decision should not be able to disagree: the sheet
    and the thing that raises and lowers it are written side by side, and a
    loader that is up with nobody left to take it down is the one failure this
    file exists to make impossible.

    The page underneath is never hidden — it is laid out and painted from the
    start, and this is an opaque sheet over it — so lifting costs one opacity
    change and no layout.
--}}
<div data-pf-loader class="pf-loader" aria-hidden="true">
    <div class="pf-loader-crt-line"></div>
    <div class="pf-loader-crt-plate-top"></div>
    <div class="pf-loader-crt-plate-bottom"></div>
    <span class="pf-loader-crt-spark"></span>
</div>
