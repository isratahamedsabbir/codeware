{{--
    Must be first in <head>. It decides whether the loader is raised on this
    visit, and that has to be decided before the body paints anything: the sheet
    is display:none by default and revealed by the pf-first-visit class this
    adds, so a flag applied after the loader element already exists would be a
    flash of the thing being removed.

    One decision, and one gate. `pf-loader-visited` records that this browser has
    seen the effect, and is set on the way past, so the first visit pays for the
    curtain and every visit after it loads straight onto the page.

    Both halves of the decision live here rather than split across this file and
    the loader's own markup, because a sheet that is raised by one and dismissed
    by the other is a page showing nothing if the two ever disagree — and the
    one failure this file exists to make impossible.

    The timings are pinned to the CRT animation's own delay and duration, which
    is the correct way to pick them when there is a long animation to avoid
    cutting off. MIN_MS is the floor (the swell starts at .2s and needs 1.2s to
    swell into the field), and MAX_MS is the ceiling for the case that actually
    happens in the field: a stalled image below the fold leaves a page that is
    readable long before `load` ever fires.

    Everything here is inline, and it stays inline for the reason the original
    did. A loader whose dismissal is an external file is a page showing nothing
    if that file 404s or is blocked, and it has to be up before anything else, so
    it cannot wait on a request of its own. The ceiling is armed before anything
    that can throw, so every path out of here ends with the page visible.

    The page underneath is never hidden — it is laid out and painted from the
    start, and this is an opaque sheet over it — so lifting costs one opacity
    change and no layout.
--}}
<script>
(function () {
    var KEY = 'pf-loader-visited';

    // The floor, not a runway: it exists so the CRT animation can play rather
    // than being cut off at one frame.
    var MIN_MS = 1450;

    // The ceiling, which is the one that matters. It fires whether or not `load`
    // ever does.
    var MAX_MS = 2600;

    var root = document.documentElement;
    var started = Date.now();
    var lifted = false;
    var ceiling = null;

    try {
        if (localStorage.getItem(KEY) === '1') return;
        localStorage.setItem(KEY, '1');
    } catch (e) {
        // No storage (private mode, hardened browsers): the loader cannot be
        // remembered anyway, so treat the visit as the first.
    }

    root.classList.add('pf-first-visit');

    function lift() {
        if (lifted) return;
        lifted = true;
        if (ceiling) clearTimeout(ceiling);

        var wait = Math.max(0, MIN_MS - (Date.now() - started));

        setTimeout(function () {
            // The mark-up may not be in the document, and a sheet that was never
            // raised has nothing to lift.
            var loader = document.querySelector('[data-pf-loader]');
            if (!loader) return;

            loader.classList.add('is-done');

            // Removed outright once it is transparent. An invisible sheet left in
            // the document is an element that can still take a click, and a
            // full-viewport one at that, for no benefit.
            setTimeout(function () {
                if (loader.parentNode) loader.parentNode.removeChild(loader);
            }, 1000);
        }, wait);
    }

    // Armed first, so it is the way out even if the branch below throws.
    ceiling = setTimeout(lift, MAX_MS);

    try {
        if (document.readyState === 'complete') {
            // One frame, so the page underneath has actually been painted before
            // the sheet starts fading off it — otherwise the reveal is a blank
            // frame.
            window.requestAnimationFrame(lift);
        } else {
            window.addEventListener('load', lift);
        }
    } catch (e) {
        // Nothing to do: the ceiling above is the way out.
    }
})();
</script>
