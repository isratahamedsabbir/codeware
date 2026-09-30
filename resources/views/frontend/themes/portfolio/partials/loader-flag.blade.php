{{--
    Must be first in <head>. It makes the two decisions that decide whether the
    loader is raised at all, and both have to be made before the body paints
    anything.

    1. Has this browser been here before? `pf-loader-visited` is the gate, and
       it is set on the way past so a returning visitor never pays for the
       effect twice.

    2. Is the page actually slow? This is the one that matters, and it is new.

    The loader used to be armed by the first-visit flag alone, and it held the
    page for MIN_MS (1450) so its CRT animation could finish. That is a cost
    paid on every first visit — and a first visit is precisely what PageSpeed,
    Lighthouse and the Chrome UX Report measure, because they all run with a
    clean profile and so see the curtain on every single run. A fast phone got
    a second and a half of black screen in exchange for an animation; a slow
    one got that second and a half added to First Contentful Paint, which is
    the number the whole exercise was about.

    So the sheet is raised only when raising it is the cheaper option: the
    document is still not ready SLOW_MS after first paint, which means the
    visitor was going to be looking at a half-drawn page either way and the
    curtain is covering something that has nothing to show. Below that line the
    page arrives inside SLOW_MS and the loader is never in the way at all.

    Everything here is inline, and it stays inline for the reason the original
    did. A loader whose dismissal is an external file is a page showing nothing
    if that file 404s or is blocked, and it has to be up before anything else,
    so it cannot wait on a request of its own. Both exits are armed before
    anything that can throw, and every one of them ends with the page visible.
--}}
<script>
(function () {
    var KEY = 'pf-loader-visited';

    // How long the page gets to be ready on its own before the sheet is worth
    // raising. Generous enough that a normal 4G page never sees the loader at
    // all, and small enough that a visitor on a bad connection is not watching
    // a blank screen wondering whether the site is down.
    var SLOW_MS = 700;

    // The way out once the sheet is up. `load` waits on every subresource —
    // including a tracking pixel far below the fold — so a page that is
    // readable long before that request finishes still needs a ceiling.
    var MAX_MS = 5000;

    // The sheet is up for at least this long, so a page that became ready just
    // after the check still gets to see the effect rather than one frame of it.
    var SHOW_MIN_MS = 420;

    var root = document.documentElement;
    var seen = false;

    try {
        seen = localStorage.getItem(KEY) === '1';
        if (!seen) localStorage.setItem(KEY, '1');
    } catch (e) {
        // No storage (private mode, hardened browsers): the loader cannot be
        // remembered anyway, so treat the visit as the first.
    }

    // A browser that has been here before has seen the effect and does not
    // need it again.
    if (seen) return;

    root.classList.add('pf-first-visit');

    var raised = false;
    var lifted = false;
    var ceiling = null;

    function lift() {
        if (lifted) return;
        lifted = true;
        if (ceiling) clearTimeout(ceiling);

        // The sheet may never have been raised (a fast page, or a <body> this
        // script ran ahead of), and there is nothing to lift in that case.
        var loader = document.querySelector('[data-pf-loader]');
        if (!loader) return;

        loader.classList.add('is-done');

        // Removed outright once it is transparent. An invisible sheet left in
        // the document is an element that can still take a click, and a
        // full-viewport one at that, for no benefit.
        setTimeout(function () {
            if (loader.parentNode) loader.parentNode.removeChild(loader);
        }, 1000);
    }

    function raise() {
        if (raised || lifted) return;
        raised = true;
        root.classList.add('pf-slow-visit');

        if (document.readyState === 'complete') {
            lift();
        } else {
            window.addEventListener('load', lift);
        }

        ceiling = setTimeout(lift, MAX_MS);
    }

    // Armed last, and outside everything above, so there is no path where the
    // timer is set and the exits are not.
    setTimeout(function () {
        // Ready inside the window: the page is already painted, so a curtain
        // over it now would delay a page that needed no help.
        if (document.readyState === 'complete') return;

        raise();

        // The floor. `load` still gets to lift it first if the page finishes
        // inside SHOW_MIN_MS — lift() is idempotent, so whichever path arrives
        // first is the one that runs.
        setTimeout(lift, SHOW_MIN_MS);
    }, SLOW_MS);
})();
</script>
