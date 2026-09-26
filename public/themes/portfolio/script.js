// Portfolio theme — self-contained assets, not part of the main app.js bundle.
(function () {
    var STORAGE_KEY = 'pf-theme';

    // Types out any [data-typewriter] element's own text content once on load,
    // preserving any markup already inside it (e.g. a gradient-colored name span).
    function typeElement(el) {
        var nodes = Array.prototype.slice.call(el.childNodes).map(function (node) {
            return { node: node, text: node.textContent };
        });
        var speed = Number(el.dataset.typewriterSpeed) || 45;

        el.innerHTML = '';
        el.style.visibility = 'visible';

        var cursor = document.createElement('span');
        cursor.className = 'pf-cursor';
        cursor.style.height = '1em';

        var queue = [];
        nodes.forEach(function (entry) {
            var chars = entry.text.split('');
            var wrapper = entry.node.nodeType === 1 ? entry.node.cloneNode(false) : null;
            if (wrapper) {
                wrapper.textContent = '';
                el.appendChild(wrapper);
            }
            chars.forEach(function (ch) {
                queue.push({ char: ch, target: wrapper || el });
            });
        });

        el.appendChild(cursor);

        var i = 0;
        (function step() {
            if (i < queue.length) {
                queue[i].target.appendChild(document.createTextNode(queue[i].char));
                i += 1;
                setTimeout(step, speed);
            }
        })();
    }

    function applyStoredTheme() {
        var stored = null;
        try { stored = localStorage.getItem(STORAGE_KEY); } catch (e) {}

        if (stored === 'light') {
            document.body.classList.add('pf-light');
        }
    }

    // Runs a callback on scroll at most once per frame. Several features below
    // all need the scroll position; without this each adds its own listener and
    // the page pays for three rAF-throttled reads per frame instead of one.
    var scrollHandlers = [];
    var scrollQueued = false;

    function onScroll(fn) {
        scrollHandlers.push(fn);
    }

    function flushScroll() {
        scrollQueued = false;
        for (var i = 0; i < scrollHandlers.length; i++) {
            scrollHandlers[i]();
        }
    }

    window.addEventListener('scroll', function () {
        if (scrollQueued) return;
        scrollQueued = true;
        window.requestAnimationFrame(flushScroll);
    }, { passive: true });

    // Marks the nav link whose section is currently in view. The portfolio is a
    // single page, so a link is never "the current URL" the way it would be on
    // a multi-page site — this is what tells you where you are while scrolling.
    // The header's own height is the offset, so a section counts as current
    // once it clears the fixed bar rather than when it first peeks above it.
    function bindSectionSpy() {
        var links = Array.prototype.slice.call(document.querySelectorAll('[data-pf-nav-link]'));
        if (!links.length) return;

        var header = document.querySelector('.pf-header');
        var bySection = {};

        links.forEach(function (link) {
            var id = link.getAttribute('data-pf-nav-link');
            var target = id && document.getElementById(id);
            if (target) bySection[id] = target;
        });

        var ids = Object.keys(bySection);
        if (!ids.length) return;

        function highlight() {
            var offset = (header ? header.offsetHeight : 0) + 24;
            var currentId = ids[0];

            ids.forEach(function (id) {
                if (bySection[id].getBoundingClientRect().top - offset <= 0) currentId = id;
            });

            // At the very bottom no section is "in view" any more (short trailing
            // sections can't reach the top), so pin the last one instead.
            if (window.innerHeight + window.scrollY >= document.body.scrollHeight - 2) {
                currentId = ids[ids.length - 1];
            }

            links.forEach(function (link) {
                link.classList.toggle('is-active', link.getAttribute('data-pf-nav-link') === currentId);
            });
        }

        onScroll(highlight);
        window.addEventListener('resize', highlight, { passive: true });
        highlight();
    }

    // Fades each [data-pf-reveal] block in the first time it enters the viewport.
    //
    // The hidden state lives in CSS, so it has to be undone before anything is
    // observed — hence the .is-reveal-ready class flipped on <body> first. If
    // this script never runs (or IntersectionObserver is missing) that class is
    // never added, nothing is ever hidden, and the page is simply static. A
    // portfolio that renders blank because an animation library 404'd is not a
    // risk worth taking for a fade.
    function bindReveal() {
        var targets = Array.prototype.slice.call(document.querySelectorAll('[data-pf-reveal]'));
        if (!targets.length) return;

        if (!('IntersectionObserver' in window) || prefersReducedMotion()) {
            targets.forEach(function (el) { el.classList.add('is-visible'); });
            return;
        }

        document.body.classList.add('is-reveal-ready');

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            });
        }, {
            // Fire a little before the element's top edge reaches the bottom of
            // the viewport, so the motion is already underway by the time the
            // block is properly in view rather than starting on arrival.
            rootMargin: '0px 0px -12% 0px',
            threshold: 0.05,
        });

        targets.forEach(function (el) { observer.observe(el); });
    }

    // The 2px gradient hairline under the header showing how far down the page
    // you are. Driven by a transform rather than a width so it stays on the
    // compositor for the whole scroll.
    function bindScrollProgress() {
        var bar = document.querySelector('[data-pf-scroll-progress]');
        if (!bar) return;

        function update() {
            var scrollable = document.body.scrollHeight - window.innerHeight;
            var ratio = scrollable > 0 ? window.scrollY / scrollable : 0;
            bar.style.transform = 'scaleX(' + Math.min(1, Math.max(0, ratio)) + ')';
        }

        onScroll(update);
        window.addEventListener('resize', update, { passive: true });
        update();
    }

    // Condenses the header once the page has scrolled off the hero, so the bar
    // stops competing with the content it sits above.
    function bindHeaderShrink() {
        var header = document.querySelector('.pf-header');
        if (!header) return;

        function update() {
            header.classList.toggle('is-scrolled', window.scrollY > 24);
        }

        onScroll(update);
        update();
    }

    function bindThemeToggle() {
        var toggle = document.querySelector('[data-pf-theme-toggle]');
        if (!toggle) return;

        toggle.addEventListener('click', function () {
            var isLight = document.body.classList.toggle('pf-light');
            try { localStorage.setItem(STORAGE_KEY, isLight ? 'light' : 'dark'); } catch (e) {}
        });
    }

    function prefersReducedMotion() {
        return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    applyStoredTheme();

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-typewriter]').forEach(typeElement);
        bindThemeToggle();
        bindReveal();
        bindSectionSpy();
        bindScrollProgress();
        bindHeaderShrink();
    });
})();
