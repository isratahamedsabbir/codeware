{{--
    The chat bubble, as a static button that fetches the real thing the first
    time it is clicked.
--}}
@if ((bool) \App\Models\Setting::get('chat_widget_enabled', true))
    @php
        $chatWidgetColor = (string) \App\Models\Setting::get('chat_widget_color', '');
        $chatWidgetColor = preg_match('/^#[0-9a-fA-F]{6}$/', $chatWidgetColor) ? $chatWidgetColor : null;
    @endphp
    <div data-chat-widget-host data-chat-widget-url="{{ route('chat-widget.fragment') }}"
        @if ($chatWidgetColor) style="--color-primary: {{ $chatWidgetColor }}" @endif
    >
        {{-- Deliberately the same button the component renders, down to the
             icon and its classes, so nothing shifts at the moment the real one
             takes its place. --}}
        <div class="fixed right-5 bottom-5 z-50">
            <button type="button" data-chat-toggle
                class="flex size-14 items-center justify-center rounded-full bg-primary text-white shadow-lg transition hover:opacity-90"
                aria-label="{{ __('Open chat') }}">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" />
                </svg>
            </button>
        </div>
    </div>

    {{--
        Inline, and last, and it does one thing.

        The widget is a Livewire component built out of Flux form components, so
        rendering it properly costs Livewire's runtime (~255 KB) and Flux's
        (~131 KB). The page used to pay for both of those on every route, on
        every device, to serve a panel that almost nobody opens — and the
        portfolio and ecommerce themes both had @fluxScripts in every template
        purely because of it. So the page ships the button and this fetcher, and
        the runtimes arrive with the panel.

        It stays inline for the same reason the loader's script did: it is
        already in the HTML that arrived, so there is no request of its own
        that can fail between the visitor and the feature. It is also the only
        JavaScript on a storefront page that ships no Livewire component at all,
        so there is no bundle to put it in.

        Every path out of here ends the same way: the button is either replaced
        by the real widget or left exactly where it is, so a failed request
        costs a second click and never a broken page. The click is not replayed
        once the runtimes are up — the toggle is dispatched for the visitor, so
        the panel opens on the click they already made.
    --}}
    <script>
    (function () {
        var host = document.querySelector('[data-chat-widget-host]');

        if (!host) return;

        var requested = false;

        // Scripts put in with innerHTML do not run, so each tag is rebuilt as a
        // real element and appended. Copying the attributes across is the point
        // of the fragment handing back whole tags rather than URLs: Livewire
        // needs its CSRF token, update URI and module URI on the tag itself,
        // and duplicating that knowledge here is how it goes stale.
        function inject(tags, done) {
            var pending = tags.length;

            if (!pending) return done();

            tags.forEach(function (html) {
                var box = document.createElement('div');

                box.innerHTML = html;

                var source = box.querySelector('script[src]');
                var src = source && source.getAttribute('src');

                // Already on the page (another component pulled it in first) —
                // count it as arrived rather than waiting for a load that will
                // never fire.
                if (!src || document.querySelector('script[src="' + src + '"]')) {
                    if (--pending === 0) done();

                    return;
                }

                var el = document.createElement('script');

                Array.prototype.forEach.call(source.attributes, function (attribute) {
                    el.setAttribute(attribute.name, attribute.value);
                });

                el.onload = el.onerror = function () {
                    if (--pending === 0) done();
                };

                document.body.appendChild(el);
            });
        }

        function open() {
            var toggle = host.querySelector('[data-chat-toggle]');

            if (toggle) toggle.click();
        }

        host.addEventListener('click', function (event) {
            if (requested) return;
            if (!event.target.closest('[data-chat-toggle]')) return;

            requested = true;

            fetch(host.getAttribute('data-chat-widget-url'), {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            })
                .then(function (response) {
                    if (!response.ok) throw new Error('HTTP ' + response.status);

                    return response.json();
                })
                .then(function (data) {
                    // Switched off in the admin between page load and click:
                    // take the button away rather than leave one that does
                    // nothing.
                    if (!data.enabled) {
                        if (host.parentNode) host.parentNode.removeChild(host);

                        return;
                    }

                    host.innerHTML = data.html;

                    inject(data.scripts, open);
                })
                .catch(function () {
                    // Leave the bubble exactly as it is, so a second click can
                    // try again. A chat box that fails to open is an
                    // inconvenience; a page that lost its button is a bug.
                    requested = false;
                });
        });
    })();
    </script>
@endif
