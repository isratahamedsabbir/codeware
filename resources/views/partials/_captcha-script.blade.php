{{-- Loads the active captcha provider (Turnstile or reCAPTCHA — see
     App\Support\Recaptcha::provider()) and exposes one function the login
     forms share: captchaToken(action) → Promise<string>. Include only inside
     @if (\App\Support\Recaptcha::enabled()). --}}
@if (\App\Support\Recaptcha::provider() === 'turnstile')
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit"></script>
    {{-- Hosts the widget; wire:ignore keeps Livewire from morphing it away. --}}
    <div id="captcha-turnstile" wire:ignore style="position:fixed;bottom:1rem;right:1rem;z-index:50"></div>
    <script>
        (function () {
            let widgetId = null;
            let settle = null;

            window.captchaToken = function (action) {
                return new Promise(function (resolve, reject) {
                    settle = { resolve: resolve, reject: reject };

                    turnstile.ready(function () {
                        if (widgetId === null) {
                            widgetId = turnstile.render('#captcha-turnstile', {
                                sitekey: @json(\App\Support\Recaptcha::siteKey()),
                                action: action,
                                execution: 'execute',
                                appearance: 'interaction-only',
                                callback: function (token) { settle && settle.resolve(token); },
                                'error-callback': function () { settle && settle.reject(new Error('turnstile')); },
                            });
                        } else {
                            turnstile.reset(widgetId);
                        }

                        turnstile.execute(widgetId);
                    });
                });
            };
        })();
    </script>
@else
    <script src="https://www.google.com/recaptcha/api.js?render={{ \App\Support\Recaptcha::siteKey() }}"></script>
    <script>
        window.captchaToken = function (action) {
            return new Promise(function (resolve) {
                grecaptcha.ready(function () {
                    grecaptcha.execute(@json(\App\Support\Recaptcha::siteKey()), { action: action }).then(resolve);
                });
            });
        };
    </script>
@endif
