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
    });
})();
