{{--
    The page loader — the one sheet shown while this page's CSS, fonts and
    images come in. Included by home/blog/post, the theme's three entry points.

    Two rules shape everything here.

    First, the sheet is visible from the first paint and the timing lives in the
    inline script below, not in script.js. A loader dismissed by an external
    file is a page showing nothing at all if that file 404s, and this theme is
    written the other way round on purpose: bindReveal() in script.js adds the
    class that hides things, so an animation failure can never be the reason a
    page is blank. A loader inverts that by necessity — it has to be up before
    anything else — so the dismissal has to be somewhere it cannot be lost. The
    end of the document is that place: it has already arrived by definition, so
    there is no request left to fail.

    Second, every path through this file ends with the page visible:
      - JS on:   the script below lifts the sheet, on load or on its own ceiling.
      - JS off:  the <noscript> block cancels the sheet outright.
      - JS error: the same ceiling, because the timers are set before anything
                  that can throw, and the whole body is in a try/catch.
    A stalled image is the case that actually happens in the field, and it is
    what the ceiling is for — the page is ready to be read long before some
    tracking pixel below the fold finishes.

    The page underneath is never hidden — it is laid out and painted from the
    start, and this is an opaque sheet over it — so lifting costs one opacity
    change and no layout.
--}}
<div data-pf-loader class="pf-loader" aria-hidden="true">
    <div class="pf-loader-inner">
        <span class="pf-loader-label">{{ \App\Models\Setting::get('site_name', config('app.name')) }}</span>
        <div class="pf-loader-track">
            <div class="pf-loader-bar"></div>
        </div>
    </div>
</div>

{{-- No JS at all: take the sheet away with the page, in one rule. --}}
<noscript>
    <style>.theme-portfolio .pf-loader { display: none; }</style>
</noscript>

<script>
    (function () {
        var loader = document.querySelector('[data-pf-loader]');

        if (!loader) return;

        // No artificial floor: the sheet lifts the instant the page is ready,
        // because holding it up any longer than that is pure added latency
        // with nothing rendering behind it. The ceiling is the important one
        // — it fires whether or not `load` ever does, so a stalled image
        // below the fold can never leave the page covered for long.
        var MIN_MS = 0;
        var MAX_MS = 1200;
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
