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

        var queued = false;
        window.addEventListener('scroll', function () {
            if (queued) return;
            queued = true;
            window.requestAnimationFrame(function () {
                queued = false;
                highlight();
            });
        }, { passive: true });

        window.addEventListener('resize', highlight, { passive: true });
        highlight();
    }

    function bindThemeToggle() {
        var toggle = document.querySelector('[data-pf-theme-toggle]');
        if (!toggle) return;

        toggle.addEventListener('click', function () {
            var isLight = document.body.classList.toggle('pf-light');
            try { localStorage.setItem(STORAGE_KEY, isLight ? 'light' : 'dark'); } catch (e) {}
        });
    }

    applyStoredTheme();

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-typewriter]').forEach(typeElement);
        bindThemeToggle();
        bindSectionSpy();
    });
})();
