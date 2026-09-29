{{--
    The page loader — the one sheet shown while this page's CSS, fonts and
    images come in. Included by the home page only: it is the curtain over a
    *landing*, and the blog and posts open on content without one.

    It is a full-viewport black sheet, styled as a CRT power-on — a white
    hairline at the centre that thickens into a full white field before the
    sheet fades. It appears once per browser: the first time this site is
    opened, and never again after — loader-flag.blade.php (first in <head>)
    is the gate, and the sheet is hidden by default and revealed only when
    that gate decides the machine is starting for the first time.

    Two rules shape everything here.

    First, the sheet is up from the first paint of the first visit and the
    timing lives in the inline script below, not in script.js. A loader
    dismissed by an external file is a page showing nothing at all if that
    file 404s, and this theme is written the other way round on purpose:
    bindReveal() in script.js adds the class that hides things, so an
    animation failure can never be the reason a page is blank. A loader
    inverts that by necessity — it has to be up before anything else — so
    the dismissal has to be somewhere it cannot be lost. The end of the
    document is that place: it has already arrived by definition, so there
    is no request left to fail.

    Second, every path through this file ends with the page visible:
      - first visit, JS on:    the script below lifts the sheet, on load or
                               on its own ceiling.
      - every later visit:     the gate said no, the sheet stays display:none,
                               and the mark-up passes by unseen.
      - JS error:              the same ceiling, because the timers are set
                               before anything that can throw, and the whole
                               body is in a try/catch.
    A stalled image is the case that actually happens in the field, and it is
    what the ceiling is for — the page is ready to be read long before some
    tracking pixel below the fold finishes.

    The page underneath is never hidden — it is laid out and painted from the
    start, and this is an opaque sheet over it — so lifting costs one opacity
    change and no layout.

    The timings are pinned to the CRT animation's own delay and duration, which
    is the correct way to pick them when there is a long animation to avoid
    cutting off. MIN_MS is the floor (the hairline starts at .2s and needs .9s
    to swell into the field), and MAX_MS is the ceiling for the case that
    actually happens in the field: a stalled image below the fold.
--}}
<div data-pf-loader class="pf-loader" aria-hidden="true">
    <div class="pf-loader-crt-line"></div>
    <span class="pf-loader-crt-spark"></span>
</div>

<script>
    (function () {
        var loader = document.querySelector('[data-pf-loader]');

        if (!loader) return;

        // The sheet lifts the instant the page is ready — MIN_MS is a floor, not
        // a runway: it exists so the CRT animation can play rather than being
        // cut off at one frame. The ceiling is the important one — it fires
        // whether or not `load` ever does, so a stalled image below the fold can
        // never leave the page covered for long.
        var MIN_MS = 1150;
        var MAX_MS = 2600;
        var started = Date.now();
        var lifted = false;

        function lift() {
            if (lifted) return;
            lifted = true;
            clearTimeout(ceiling);

            var wait = Math.max(0, MIN_MS - (Date.now() - started));

            setTimeout(function () {
                loader.classList.add('is-done');

                // Removed outright once it is transparent. An invisible sheet
                // left in the document is an element that can still take a
                // click, and a full-viewport one at that, for no benefit.
                setTimeout(function () {
                    if (loader.parentNode) loader.parentNode.removeChild(loader);
                }, 480);
            }, wait);
        }

        // Armed first, so it is the way out even if the branch below throws.
        var ceiling = setTimeout(lift, MAX_MS);

        try {
            if (document.readyState === 'complete') {
                // One frame, so the page underneath has actually been painted
                // before the sheet starts fading off it — otherwise the reveal
                // is a blank frame.
                window.requestAnimationFrame(lift);
            } else {
                window.addEventListener('load', lift);
            }
        } catch (e) {
            // Nothing to do: the ceiling above is the way out.
        }
    })();
</script>