{{--
    Product share row.

    The one thing worth getting right here is that these links share exactly
    what the page already advertises. Facebook, X and LinkedIn do not take a
    title or an image in the URL at all — they scrape the og:/twitter: tags off
    the page. So the card a visitor sees is decided by SeoResolver, and the only
    way this partial can produce a different one is by resolving differently.
    It resolves with the same arguments as partials.seo-meta, and SeoResolver
    memoises per request, so this is the identical SeoData object and costs
    nothing. Building the text from $product->name here would let the page and
    the share card drift apart the moment an admin tuned an og_title.

    Percent-encoding is not optional: the canonical is a full URL with a query
    string, and an unencoded & would split the share link into two parameters.
    http_build_query in RFC3986 mode does it properly, which is also why the
    params are built per-network rather than as one shared map — Facebook takes
    only u, WhatsApp takes the URL inside a single text blob, and Pinterest is
    the only one of the three that will render a passed image.

    Every network except the clipboard button is a plain <a href>, so sharing
    still works with no JavaScript. Only the popups need scripting, and they
    degrade to ordinary navigation when it is absent.

    The one thing this partial adds to the page's own identity is the ?ref= tag
    on the shared URL, and only for a signed-in customer: the referrer's own
    USR- code rides along, so the order it eventually produces is attributed to
    whoever sent the buyer (App\Support\Referral). A guest's links are the bare
    canonical — there is no account to credit, and inventing a ref for them
    would attribute their order to nobody while still paying the cost of the
    parameter on every share.
--}}
@php
    $share = \App\Support\Seo\SeoResolver::resolve(request(), $page ?? null, $title ?? null);

    // Tagging happens before anything else reads $shareUrl, so the social hrefs
    // and the clipboard button below are all working from the same tagged URL —
    // two variables holding "the same" link is how the copy button ends up
    // quietly dropping the ref the share buttons carry.
    $shareUrl = \App\Support\Referral::shareUrl($share->canonical);

    // 90 characters is roughly where WhatsApp and Telegram stop rendering the
    // preview cleanly, and a title that long is a bad <title> anyway.
    $shareText = \Illuminate\Support\Str::limit($share->ogTitle ?: $product->name, 90);

    $qs = fn (array $params) => http_build_query($params, '', '&', PHP_QUERY_RFC3986);

    // 'popup' is the one piece of per-network behaviour the markup needs, so it
    // is declared here next to the href rather than re-listed in the template:
    // the two lists drifting apart is how WhatsApp ends up behind a popup.
    $networks = [
        'facebook' => [
            'label' => 'Facebook',
            // No text param: Facebook takes the title and image from og:.
            'href' => 'https://www.facebook.com/sharer/sharer.php?'.$qs(['u' => $shareUrl]),
            'icon' => 'facebook',
            'hover' => 'hover:bg-[#0866ff]',
            'popup' => true,
        ],
        'twitter' => [
            'label' => 'X / Twitter',
            'href' => 'https://twitter.com/intent/tweet?'.$qs(['url' => $shareUrl, 'text' => $shareText]),
            'icon' => 'twitter',
            'hover' => 'hover:bg-black',
            'popup' => true,
        ],
        'whatsapp' => [
            'label' => 'WhatsApp',
            // wa.me deep-links to the app when it is installed and to web.whatsapp
            // when it is not, which the legacy send?text= form no longer does.
            // The URL has to ride inside text, so it is a blob, not two params.
            'href' => 'https://wa.me/?'.$qs(['text' => $shareText.' '.$shareUrl]),
            'icon' => 'whatsapp',
            'hover' => 'hover:bg-[#25D366]',
            'popup' => false,
        ],
        'telegram' => [
            'label' => 'Telegram',
            'href' => 'https://t.me/share/url?'.$qs(['url' => $shareUrl, 'text' => $shareText]),
            'icon' => 'telegram',
            'hover' => 'hover:bg-[#229ED9]',
            'popup' => false,
        ],
        'linkedin' => [
            'label' => 'LinkedIn',
            'href' => 'https://www.linkedin.com/sharing/share-offsite/?'.$qs(['url' => $shareUrl]),
            'icon' => 'linkedin',
            'hover' => 'hover:bg-[#0A66C2]',
            'popup' => true,
        ],
        'pinterest' => [
            'label' => 'Pinterest',
            'href' => 'https://www.pinterest.com/pin/create/button/?'.$qs(array_filter([
                'url' => $shareUrl,
                'description' => $shareText,
                // Pinterest renders nothing at all if media is absent, so the
                // button is only useful when the product actually has an image.
                'media' => $share->ogImage,
            ])),
            'icon' => 'pinterest',
            'hover' => 'hover:bg-[#e60023]',
            'popup' => true,
        ],
        'email' => [
            'label' => 'Email',
            // Not a query string on a web URL, so it is built by hand: mailto
            // takes subject and body, and a body that is only the link reads as
            // a spam forward rather than a recommendation.
            'href' => 'mailto:?'.$qs(['subject' => $shareText, 'body' => $shareText."\n\n".$shareUrl]),
            'icon' => 'email',
            'hover' => 'hover:bg-zinc-700',
            'popup' => false,
        ],
    ];
@endphp

<div class="mt-6 flex flex-wrap items-center gap-3">
    <span class="text-sm font-medium text-zinc-500">{{ __('Share') }}</span>

    <div class="flex flex-wrap items-center gap-1.5">
        @foreach ($networks as $key => $network)
            {{-- Only the networks that open a share sheet on their own domain
                 get a popup. WhatsApp, Telegram and mailto hand off to an app
                 or the mail client, and a popup window in front of that is a
                 worse experience than letting the page navigate. --}}
            <a href="{{ $network['href'] }}"
                {{-- Popup and target both follow the network's own flag: a
                     share sheet on a third-party domain wants a small window,
                     while a wa.me deep link or a mailto hands off to another
                     app, and target="_blank" on those leaves a blank tab
                     behind or stops the mail client being reached at all. --}}
                @if ($network['popup'])
                    data-share-popup
                    target="_blank" rel="noopener noreferrer nofollow"
                @endif
                aria-label="{{ __('Share on :network', ['network' => $network['label']]) }}"
                title="{{ __('Share on :network', ['network' => $network['label']]) }}"
                class="flex h-9 w-9 items-center justify-center rounded-full bg-zinc-100 text-zinc-500 transition duration-200 hover:scale-105 hover:text-white {{ $network['hover'] }}">
                @if ($network['icon'] === 'facebook')
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4" aria-hidden="true"><path d="M15 3h3v4h-3c-1.1 0-2 .9-2 2v2h4l-1 4h-3v7h-4v-7H7v-4h3V9c0-3 2-6 5-6Z"/></svg>
                @elseif ($network['icon'] === 'twitter')
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4" aria-hidden="true"><path d="M17.7 3h3.1l-6.8 7.8L22 21h-6.3l-4.9-6.4L5 21H1.9l7.3-8.3L2 3h6.4l4.4 5.8L17.7 3Zm-1.1 16h1.7L7.5 4.7H5.6L16.6 19Z"/></svg>
                @elseif ($network['icon'] === 'whatsapp')
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
                @elseif ($network['icon'] === 'telegram')
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4" aria-hidden="true"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                @elseif ($network['icon'] === 'linkedin')
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4" aria-hidden="true"><path d="M4.98 3.5A2.49 2.49 0 0 0 2.5 6a2.49 2.49 0 0 0 2.48 2.5A2.49 2.49 0 0 0 7.46 6a2.49 2.49 0 0 0-2.48-2.5ZM3 11h4v9H3v-9Zm6 0h3.8v1.3h.1c.5-.9 1.7-1.9 3.5-1.9 3.7 0 4.6 2.4 4.6 5.6V20h-4v-4.7c0-1.1 0-2.6-1.6-2.6s-1.8 1.2-1.8 2.5V20H9v-9Z"/></svg>
                @elseif ($network['icon'] === 'pinterest')
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4" aria-hidden="true"><path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.174-.105-.949-.199-2.403.041-3.439.219-1.392 1.402-5.957 1.402-5.957s-.358-.72-.358-1.781c0-1.668.967-2.914 2.171-2.914 1.023 0 1.518.769 1.518 1.69 0 1.029-.654 2.568-.994 3.995-.283 1.194.599 2.169 1.777 2.169 2.133 0 3.772-2.249 3.772-5.495 0-2.873-2.064-4.882-5.012-4.882-3.414 0-5.418 2.561-5.418 5.207 0 1.031.397 2.138.893 2.738a.36.36 0 0 1 .083.345l-.333 1.36c-.053.22-.174.267-.402.161-1.499-.698-2.436-2.889-2.436-4.649 0-3.785 2.75-7.262 7.929-7.262 4.163 0 7.398 2.967 7.398 6.931 0 4.136-2.607 7.464-6.227 7.464-1.216 0-2.359-.631-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146 1.124.347 2.317.536 3.554.536 6.62 0 11.99-5.367 11.99-11.987C24.007 5.367 18.637.001 12.017.001z"/></svg>
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4" aria-hidden="true"><path d="M3 5h18a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Zm1.4 2 7.6 4.6L19.6 7H4.4Z"/></svg>
                @endif
            </a>
        @endforeach

        {{-- A button rather than a link, because it has no destination to
             navigate to. execCommand is the fallback for the insecure-context
             case: clipboard.writeText is undefined on plain http, which is still
             a realistic way to reach a staging site. --}}
        <button type="button" data-share-copy="{{ $shareUrl }}"
            aria-label="{{ __('Copy link') }}" title="{{ __('Copy link') }}"
            class="flex items-center gap-1.5 rounded-full bg-zinc-100 px-3 py-2 text-xs font-medium text-zinc-500 transition duration-200 hover:bg-sf-heading hover:text-white">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4" aria-hidden="true"><path d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244"/></svg>
            <span data-share-copy-label>{{ __('Copy link') }}</span>
        </button>
    </div>
</div>

<script>
    (function () {
        // The row is included once per product page, but a guard costs a line
        // and makes a double render (a Livewire swap that re-emits the markup)
        // harmless instead of double-bound.
        if (window.__pfShareBound) return;
        window.__pfShareBound = true;

        // target="_blank" on a share sheet opens a new tab with no opener, which
        // the destination's own JS is allowed to reach, and a full tab for
        // something that is a one-click form. A popup is the right shape; the
        // feature that forces it is the opener, hence noopener on the window.
        document.addEventListener('click', function (event) {
            var trigger = event.target.closest('[data-share-popup]');

            if (! trigger) return;

            var win = window.open(
                trigger.href,
                'pf-share',
                'popup=yes,width=640,height=520,scrollbars=yes,resizable=yes,top=' +
                    Math.max(0, Math.round((window.screen.height - 520) / 2)) + ',left=' +
                    Math.max(0, Math.round((window.screen.width - 640) / 2))
            );

            // A blocked popup is a normal outcome (mid-click, or no JS), not an
            // error worth surfacing. When it is blocked the click is left to
            // its default so the share still opens, in a tab.
            if (win) {
                win.opener = null;
                event.preventDefault();
            }
        });

        document.addEventListener('click', function (event) {
            var button = event.target.closest('[data-share-copy]');

            if (! button) return;

            var label = button.querySelector('[data-share-copy-label]');
            var value = button.getAttribute('data-share-copy');
            var original = label ? label.textContent : '';
            var temp = document.createElement('textarea');

            function done(ok, message) {
                if (! label) return;

                label.textContent = message;
                clearTimeout(button._shareTimer);
                button._shareTimer = setTimeout(function () {
                    label.textContent = original;
                }, 1800);
            }

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(value).then(
                    function () { done(true, @js(__('Copied'))); },
                    function () { fallback(); }
                );

                return;
            }

            fallback();

            function fallback() {
                // Needs to be in the document and momentarily not-readonly to
                // be selectable, and a fixed off-screen position so the page
                // does not jump to the bottom on iOS when it focuses.
                temp.value = value;
                temp.setAttribute('readonly', '');
                temp.style.cssText = 'position:fixed;top:0;left:0;opacity:0;pointer-events:none';
                document.body.appendChild(temp);
                temp.select();
                temp.setSelectionRange(0, value.length);

                var ok = false;

                try {
                    ok = document.execCommand('copy');
                } catch (e) {
                    ok = false;
                }

                document.body.removeChild(temp);
                done(ok, ok ? @js(__('Copied')) : @js(__('Press Ctrl+C')));
            }
        });
    })();
</script>
