{{--
    Must be first in <head>: this decides whether the loader is shown on this
    visit, and it has to be decided before the body paints anything. The sheet
    is hidden by default and the pf-first-visit class is the only thing that
    reveals it, so a flag applied after the loader element exists would be a
    flash of the thing being removed — and a visitor whose flag is already set
    (everyone after the first) loads straight onto the page.
--}}
<script>
(function () {
    try {
        if (localStorage.getItem('pf-loader-visited')) return;
        localStorage.setItem('pf-loader-visited', '1');
    } catch (e) {
        // No storage (private mode, hardened browsers): the loader cannot be
        // remembered anyway, so treat the visit as the first — the machine
        // still gets to say it is starting once.
    }
    document.documentElement.classList.add('pf-first-visit');
})();
</script>