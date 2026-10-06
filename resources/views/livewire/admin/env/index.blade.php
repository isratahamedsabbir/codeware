<div x-data="{
    activeTab: 'general',
    setTab(tab) { this.activeTab = tab; },
    init() {
        // A link into this page can point straight at one field, e.g.
        // .../developer-tools#VENDOR_URL â€” scroll it into view and flash it once rendered.
        if (location.hash) {
            const id = 'env-field-' + location.hash.slice(1);
            this.$nextTick(() => setTimeout(() => {
                const el = document.getElementById(id);
                if (! el) return;
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                el.classList.add('ring-2', 'ring-primary', 'rounded-lg');
                setTimeout(() => el.classList.remove('ring-2', 'ring-primary', 'rounded-lg'), 2000);
            }, 150));
        }
    }
}">
    <div class="max-w-[1600px] space-y-5">

        {{-- â”€â”€ Sticky tab bar â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
        <div class="sticky top-14 z-10 -mx-1 px-1">
            {{-- Same card-of-icon-tabs as Settings, each with a one-line summary. --}}
            @php
                $tabs = [
                    'general' => ['General', 'cog-6-tooth', 'App, maintenance, debug mode'],
                    'authentication' => ['Authentication', 'lock-closed', 'Google & Facebook login, reCAPTCHA, Turnstile'],
                    'integrations' => ['Integrations', 'puzzle-piece', 'Pixel, Maps, S3, Firebase'],
                ];
            @endphp
            <div role="tablist" aria-label="Developer Tools sections"
                class="flex gap-1 overflow-x-auto rounded-xl border border-zinc-200 bg-white/95 p-1.5 shadow-sm backdrop-blur dark:border-zinc-700 dark:bg-zinc-900/95">
                @foreach ($tabs as $tabKey => [$tabLabel, $tabIcon, $tabSummary])
                    <button type="button" role="tab" x-on:click="setTab('{{ $tabKey }}')"
                        x-bind:aria-selected="activeTab === '{{ $tabKey }}'"
                        x-bind:class="activeTab === '{{ $tabKey }}'
                            ? 'bg-primary/10 text-primary ring-1 ring-primary/20'
                            : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100'"
                        class="group flex min-w-44 flex-1 items-center gap-3 rounded-lg! px-3.5 py-2.5 text-left transition-colors">
                        <span x-bind:class="activeTab === '{{ $tabKey }}'
                                ? 'bg-primary text-white shadow-sm'
                                : 'bg-zinc-100 text-zinc-500 group-hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-400'"
                            class="flex size-9 shrink-0 items-center justify-center rounded-lg transition-colors">
                            <flux:icon :name="$tabIcon" variant="mini" class="size-4.5" />
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold">{{ __($tabLabel) }}</span>
                            <span class="block truncate text-[11px] font-normal text-zinc-400">{{ $tabSummary }}</span>
                        </span>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- â”€â”€ General tab â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
        <div x-show="activeTab === 'general'" x-cloak class="space-y-5">

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-start">
                {{-- Maintenance mode --}}
                <x-admin-section-card id="env-section-maintenance" header-border="border-zinc-100" icon="wrench" title="Maintenance Mode"
                    icon-color="{{ $maintenanceMode ? 'bg-red-500/10 text-red-600' : 'bg-primary/10 text-primary' }}"
                    description="Takes the public site offline for every visitor. The admin panel and login stay reachable either way."
                    class="w-full scroll-mt-24 {{ $maintenanceMode ? 'border-red-300! dark:border-red-800!' : '' }}">
                    <x-slot:titleActions>
                        <a href="{{ route('admin.developer-guide') }}#integration-maintenance"
                            title="What maintenance mode does — open the Developer Guide"
                            aria-label="What maintenance mode does — open the Developer Guide"
                            class="flex size-5 items-center justify-center text-zinc-400 transition-colors hover:text-primary">
                            <flux:icon.information-circle class="size-4" />
                        </a>
                    </x-slot:titleActions>
                    <x-slot:actions>
                        <flux:switch wire:model.live="maintenanceMode" aria-label="Maintenance mode" title="Take the public site offline" />
                    </x-slot:actions>
                </x-admin-section-card>

                {{-- Debug mode --}}
                <x-admin-section-card id="env-section-debug" header-border="border-zinc-100" icon="bug-ant" title="Debug Mode"
                    icon-color="{{ $debugMode ? 'bg-amber-500/10 text-amber-600' : 'bg-primary/10 text-primary' }}"
                    description="Shows full error details and stack traces to visitors. Leave this off in production."
                    class="w-full scroll-mt-24 {{ $debugMode ? 'border-amber-300! dark:border-amber-800!' : '' }}">
                    <x-slot:titleActions>
                        <a href="{{ route('admin.developer-guide') }}#integration-debug"
                            title="What debug mode does — open the Developer Guide"
                            aria-label="What debug mode does — open the Developer Guide"
                            class="flex size-5 items-center justify-center text-zinc-400 transition-colors hover:text-primary">
                            <flux:icon.information-circle class="size-4" />
                        </a>
                    </x-slot:titleActions>
                    <x-slot:actions>
                        <flux:switch wire:model.live="debugMode" aria-label="Debug mode" title="Show full error details to visitors" />
                    </x-slot:actions>
                </x-admin-section-card>
            </div>

            {{-- App --}}
            <x-admin-section-card id="env-section-app" class="scroll-mt-24" header-border="border-zinc-100" icon="rocket-launch" title="App"
                description="Core application identity, URLs and cache store.">
                <x-slot:titleActions>
                    <a href="{{ route('admin.developer-guide') }}#integration-app"
                        title="About this section — open the Developer Guide"
                        aria-label="About this section — open the Developer Guide"
                        class="flex size-5 items-center justify-center text-zinc-400 transition-colors hover:text-primary">
                        <flux:icon.information-circle class="size-4" />
                    </a>
                </x-slot:titleActions>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-5 gap-y-3">
                    @foreach ($this->envFields()['App'] as $key => $meta)
                        @include('livewire.admin.env.partials._env-field', ['key' => $key, 'meta' => $meta])
                    @endforeach
                </div>
            </x-admin-section-card>
        </div>

        {{-- â”€â”€ Authentication tab â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
        <div x-show="activeTab === 'authentication'" x-cloak class="space-y-5">
<div class="grid grid-cols-1 xl:grid-cols-2 gap-5 items-start"><div class="space-y-5">

            {{-- Google Login --}}
            <x-admin-section-card id="env-section-google-login" class="scroll-mt-24" header-border="border-zinc-100" icon="globe-alt" title="Google Login">
                <x-slot:titleActions>
                    <a href="{{ route('admin.developer-guide') }}#integration-google-login"
                        title="Where to get these — open the Developer Guide"
                        aria-label="Where to get these — open the Developer Guide"
                        class="flex size-5 items-center justify-center text-zinc-400 transition-colors hover:text-primary">
                        <flux:icon.information-circle class="size-4" />
                    </a>
                </x-slot:titleActions>
                <div class="grid grid-cols-1 gap-y-3">
                    @foreach ($this->envFields()['Google Login'] as $key => $meta)
                        @include('livewire.admin.env.partials._env-field', ['key' => $key, 'meta' => $meta])
                    @endforeach
                </div>
            </x-admin-section-card>

            {{-- Facebook Login --}}
            <x-admin-section-card id="env-section-facebook-login" class="scroll-mt-24" header-border="border-zinc-100" icon="chat-bubble-left-right" title="Facebook Login">
                <x-slot:titleActions>
                    <a href="{{ route('admin.developer-guide') }}#integration-facebook-login"
                        title="Where to get these — open the Developer Guide"
                        aria-label="Where to get these — open the Developer Guide"
                        class="flex size-5 items-center justify-center text-zinc-400 transition-colors hover:text-primary">
                        <flux:icon.information-circle class="size-4" />
                    </a>
                </x-slot:titleActions>
                <div class="grid grid-cols-1 gap-y-3">
                    @foreach ($this->envFields()['Facebook Login'] as $key => $meta)
                        @include('livewire.admin.env.partials._env-field', ['key' => $key, 'meta' => $meta])
                    @endforeach
                </div>
            </x-admin-section-card>

</div><div class="space-y-5">
            {{-- Which captcha is live. Both providers keep their own key cards below;
                 this picks the one the login forms actually use. --}}
            <x-admin-section-card id="env-section-captcha" class="scroll-mt-24" header-border="border-zinc-100" icon="shield-check" title="Active Captcha"
                description="Choose reCAPTCHA or Cloudflare Turnstile. Only the chosen one is shown and verified on logins whose role has the captcha switched on (Roles).">
                <div class="grid grid-cols-1 gap-y-3">
                    @foreach ($this->envFields()['Captcha'] as $key => $meta)
                        @include('livewire.admin.env.partials._env-field', ['key' => $key, 'meta' => $meta])
                    @endforeach
                </div>

                {{-- What is live right now (the saved setting, not the unsaved dropdown). --}}
                @php
                    $captchaLabel = \App\Support\Recaptcha::label();
                    $captchaKeysSet = filled(config('services.'.\App\Support\Recaptcha::provider().'.site_key'))
                        && filled(config('services.'.\App\Support\Recaptcha::provider().'.secret_key'));
                @endphp
                <p class="mt-3 flex items-center gap-2 text-sm {{ $captchaKeysSet ? 'text-emerald-700' : 'text-amber-700' }}">
                    <span class="inline-block size-2 rounded-full {{ $captchaKeysSet ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                    @if ($captchaKeysSet)
                        Live now: <strong>{{ $captchaLabel }}</strong> — this is the captcha logins ask for.
                    @else
                        Selected: <strong>{{ $captchaLabel }}</strong>, but its keys are missing below — no captcha is shown on logins yet.
                    @endif
                </p>
            </x-admin-section-card>

            {{-- reCAPTCHA --}}
            {{-- Credentials only. Which login forms show the widget is decided by the
                 per-role reCAPTCHA switches on Roles; this card holds the keys they
                 need. --}}
            <x-admin-section-card id="env-section-recaptcha" class="scroll-mt-24" header-border="border-zinc-100" icon="shield-check" title="reCAPTCHA"
                description="Used by any login whose role has reCAPTCHA switched on. Both keys are needed before it can verify anything.">
                <x-slot:titleActions>
                    <a href="{{ route('admin.developer-guide') }}#integration-recaptcha"
                        title="Where to get these — open the Developer Guide"
                        aria-label="Where to get these — open the Developer Guide"
                        class="flex size-5 items-center justify-center text-zinc-400 transition-colors hover:text-primary">
                        <flux:icon.information-circle class="size-4" />
                    </a>
                </x-slot:titleActions>
                <div class="grid grid-cols-1 gap-y-3">
                    @foreach ($this->envFields()['reCAPTCHA'] as $key => $meta)
                        @include('livewire.admin.env.partials._env-field', ['key' => $key, 'meta' => $meta])
                    @endforeach
                </div>
            </x-admin-section-card>

            {{-- Cloudflare Turnstile --}}
            {{-- Credentials only, like reCAPTCHA above. Once both keys are set it is
                 used instead of reCAPTCHA on every login that asks for a captcha
                 (same per-role switch on Roles). --}}
            <x-admin-section-card id="env-section-turnstile" class="scroll-mt-24" header-border="border-zinc-100" icon="shield-check" title="Cloudflare Turnstile"
                description="Used when Active Captcha is set to turnstile. Both keys are needed before it can verify anything.">
                <x-slot:titleActions>
                    <a href="{{ route('admin.developer-guide') }}#integration-turnstile"
                        title="Where to get these — open the Developer Guide"
                        aria-label="Where to get these — open the Developer Guide"
                        class="flex size-5 items-center justify-center text-zinc-400 transition-colors hover:text-primary">
                        <flux:icon.information-circle class="size-4" />
                    </a>
                </x-slot:titleActions>
                <div class="grid grid-cols-1 gap-y-3">
                    @foreach ($this->envFields()['Turnstile'] as $key => $meta)
                        @include('livewire.admin.env.partials._env-field', ['key' => $key, 'meta' => $meta])
                    @endforeach
                </div>
            </x-admin-section-card>
</div></div>
        </div>

        {{-- â”€â”€ Integrations tab â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
        <div x-show="activeTab === 'integrations'" x-cloak class="space-y-5">
<div class="grid grid-cols-1 xl:grid-cols-2 gap-5 items-start"><div class="space-y-5">

            {{-- Google Pixel --}}
            <x-admin-section-card id="env-section-pixel" class="scroll-mt-24" header-border="border-zinc-100" icon="chart-bar" title="Google Pixel"
                description="The Measurement/Pixel ID (e.g. G-XXXXXXXXXX or AW-XXXXXXXXX) exposed via the public settings API for the frontend to use.">
                <x-slot:titleActions>
                    <a href="{{ route('admin.developer-guide') }}#integration-pixel"
                        title="Integration guide — open the Developer Guide"
                        aria-label="Integration guide — open the Developer Guide"
                        class="flex size-5 items-center justify-center text-zinc-400 transition-colors hover:text-primary">
                        <flux:icon.information-circle class="size-4" />
                    </a>
                </x-slot:titleActions>
                <div class="max-w-xl">
                    <flux:field>
                        <flux:label>Google Pixel ID</flux:label>
                        <flux:input wire:model="settings.google_pixel_id" placeholder="G-XXXXXXXXXX" class="font-mono" />
                    </flux:field>
                </div>
            </x-admin-section-card>

            {{-- Google Maps --}}
            <x-admin-section-card id="env-section-google-maps" class="scroll-mt-24" header-border="border-zinc-100" icon="map" title="Google Maps"
                description="Used wherever the app needs to render a Google Map (e.g. store/branch locations).">
                <x-slot:titleActions>
                    <a href="{{ route('admin.developer-guide') }}#integration-google-maps"
                        title="Where to get this — open the Developer Guide"
                        aria-label="Where to get this — open the Developer Guide"
                        class="flex size-5 items-center justify-center text-zinc-400 transition-colors hover:text-primary">
                        <flux:icon.information-circle class="size-4" />
                    </a>
                </x-slot:titleActions>
                <div class="max-w-xl">
                    @foreach ($this->envFields()['Google Maps'] as $key => $meta)
                        @include('livewire.admin.env.partials._env-field', ['key' => $key, 'meta' => $meta])
                    @endforeach
                </div>

                @if (config('services.google_maps.api_key'))
                    <div wire:ignore
                        x-data
                        x-init="
                            const render = () => {
                                const center = { lat: 23.8103, lng: 90.4125 };
                                const map = new google.maps.Map($el, { center, zoom: 12 });
                                new google.maps.Marker({ position: center, map });
                            };
                            if (window.google?.maps) { render(); return; }
                            window.__gmapsPreviewCallbacks = window.__gmapsPreviewCallbacks || [];
                            window.__gmapsPreviewCallbacks.push(render);
                            // Google calls this global itself (not our callback param) when the
                            // key is rejected outright â€” wrong key, billing disabled, or (most
                            // commonly, since this often differs per environment) this domain
                            // isn't in the key's allowed HTTP referrers â€” so surface that here
                            // instead of leaving the box permanently blank with only a console
                            // warning to explain why.
                            window.gm_authFailure = () => {
                                $el.innerHTML = '<div class=\'flex items-center justify-center h-full text-center text-xs text-rose-500 px-4\'>{{ __('Google rejected this key â€” check that this domain is in the key\'s allowed HTTP referrers (Google Cloud Console â†’ Credentials), and that billing / the Maps JavaScript API are enabled.') }}</div>';
                            };
                            if (window.__gmapsPreviewLoading) return;
                            window.__gmapsPreviewLoading = true;
                            window.__gmapsPreviewReady = () => { window.__gmapsPreviewCallbacks.forEach(cb => cb()); window.__gmapsPreviewCallbacks = []; };
                            const script = document.createElement('script');
                            script.src = 'https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&callback=__gmapsPreviewReady';
                            script.async = true;
                            document.head.appendChild(script);
                        "
                        class="mt-4 h-56 rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-700"
                    ></div>
                    <flux:text class="text-xs text-zinc-500 mt-2">
                        {{ __('Live preview using the saved key, just to confirm it works â€” centered on a placeholder location. The maps used elsewhere in the app can point anywhere.') }}
                    </flux:text>
                @else
                    <div class="mt-4 flex flex-col items-center justify-center gap-2 h-56 rounded-lg border border-dashed border-zinc-300 dark:border-zinc-700 text-zinc-400 text-sm">
                        <flux:icon.map class="size-6" />
                        <span>{{ __('Save an API key above to preview the map here.') }}</span>
                    </div>
                @endif
            </x-admin-section-card>

</div><div class="space-y-5">
            {{-- AWS S3 --}}
            <x-admin-section-card id="env-section-aws-s3" class="scroll-mt-24" header-border="border-zinc-100" icon="cloud" title="AWS S3"
                description="Only needed if FILESYSTEM_DISK is set to s3 â€” otherwise uploads stay on local disk and these are unused.">
                <x-slot:titleActions>
                    <a href="{{ route('admin.developer-guide') }}#integration-aws-s3"
                        title="Where to get these — open the Developer Guide"
                        aria-label="Where to get these — open the Developer Guide"
                        class="flex size-5 items-center justify-center text-zinc-400 transition-colors hover:text-primary">
                        <flux:icon.information-circle class="size-4" />
                    </a>
                </x-slot:titleActions>
                <div class="grid grid-cols-1 gap-y-3">
                    @foreach ($this->envFields()['AWS S3'] as $key => $meta)
                        @include('livewire.admin.env.partials._env-field', ['key' => $key, 'meta' => $meta])
                    @endforeach
                </div>
            </x-admin-section-card>

            {{-- Firebase --}}
            <x-admin-section-card id="env-section-firebase" class="scroll-mt-24" header-border="border-zinc-100" icon="fire" title="Firebase"
                description="Service-account credentials for the Firebase Admin SDK (e.g. push notifications).">
                <x-slot:titleActions>
                    <a href="{{ route('admin.developer-guide') }}#integration-firebase"
                        title="Where to get this — open the Developer Guide"
                        aria-label="Where to get this — open the Developer Guide"
                        class="flex size-5 items-center justify-center text-zinc-400 transition-colors hover:text-primary">
                        <flux:icon.information-circle class="size-4" />
                    </a>
                </x-slot:titleActions>
                <div class="max-w-xl">
                    <flux:field>
                        <flux:label>{{ __('Service Account JSON Path') }}<x-field-hint :text="__($this->envFields()['Firebase']['FIREBASE_CREDENTIALS_PATH']['hint'])" /></flux:label>
                        <div class="flex items-center gap-2">
                            @if ($env['FIREBASE_CREDENTIALS_PATH'] ?? null)
                                @if (! $this->firebaseCredentialsExist())
                                    <span title="{{ __('File not found on the private storage disk.') }}">
                                        <flux:icon.exclamation-triangle class="size-5 text-red-500 shrink-0" />
                                    </span>
                                @else
                                    <span title="{{ __('File found.') }}">
                                        <flux:icon.check-circle class="size-5 text-emerald-500 shrink-0" />
                                    </span>
                                @endif
                            @endif
                            <flux:input wire:model.live="env.FIREBASE_CREDENTIALS_PATH" placeholder="firebase-service-account.json" class="font-mono" />
                        </div>
                        <flux:error name="env.FIREBASE_CREDENTIALS_PATH" />
                    </flux:field>
                </div>
            </x-admin-section-card>

            {{-- CMS Editor --}}
            <x-admin-section-card id="env-section-cms-editor" class="scroll-mt-24" header-border="border-zinc-100" icon="pencil-square" title="CMS Editor"
                description="Base URL of the Next.js Puck editor this admin panel opens for visual editing.">
                <x-slot:titleActions>
                    <a href="{{ route('admin.developer-guide') }}#integration-cms-editor"
                        title="What this controls — open the Developer Guide"
                        aria-label="What this controls — open the Developer Guide"
                        class="flex size-5 items-center justify-center text-zinc-400 transition-colors hover:text-primary">
                        <flux:icon.information-circle class="size-4" />
                    </a>
                </x-slot:titleActions>
                <div class="max-w-xl">
                    @foreach ($this->envFields()['CMS Editor'] as $key => $meta)
                        @include('livewire.admin.env.partials._env-field', ['key' => $key, 'meta' => $meta])
                    @endforeach
                </div>
            </x-admin-section-card>
</div></div>

            {{-- Fallback: any future env group added without a hand-built card above --}}
            @php $manuallyRenderedGroups = ['App', 'Google Login', 'Facebook Login', 'Captcha', 'reCAPTCHA', 'Turnstile', 'Google Maps', 'AWS S3', 'Firebase', 'CMS Editor']; @endphp
            @foreach ($this->envFields() as $groupLabel => $fields)
                @continue(in_array($groupLabel, $manuallyRenderedGroups, true))
                <x-admin-section-card header-border="border-zinc-100" icon="rocket-launch" title="{{ __($groupLabel) }}">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach ($fields as $key => $meta)
                            @include('livewire.admin.env.partials._env-field', ['key' => $key, 'meta' => $meta])
                        @endforeach
                    </div>
                </x-admin-section-card>
            @endforeach
        </div>

        {{-- â”€â”€ Sticky save bar â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
        <div
            class="sticky bottom-0 z-10 flex flex-col gap-3 rounded-[5px] border border-zinc-200 bg-white/95 px-5 py-3.5 shadow-[0_-4px_16px_-8px_rgba(0,0,0,0.15)] backdrop-blur dark:border-zinc-700 dark:bg-zinc-800/90 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-xs text-zinc-400">
                Changes are written to the live <span class="font-mono text-xs">.env</span> file and the configuration cache is cleared once you save.
            </p>
            <div class="flex items-center gap-2.5 shrink-0">
                <flux:button variant="primary" wire:click="confirmSaveEnv" wire:loading.attr="disabled" size="sm">
                    <span wire:loading.remove>Save Environment Settings</span>
                    <span wire:loading>Savingâ€¦</span>
                </flux:button>
            </div>
        </div>
    </div>

    {{-- Env save confirmation --}}
    <flux:modal name="env-save-confirm" class="md:w-96"
        x-on:open-modal.window="if ($event.detail.name === 'env-save-confirm') $flux.modal('env-save-confirm').show()"
        x-on:close-modal.window="if ($event.detail.name === 'env-save-confirm') $flux.modal('env-save-confirm').close()">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-amber-50 flex items-center justify-center shrink-0">
                    <flux:icon.exclamation-triangle class="w-5 h-5 text-amber-500" />
                </div>
                <flux:heading>{{ __('Save environment settings?') }}</flux:heading>
            </div>
            <flux:text class="text-sm text-zinc-500">
                {{ __('This overwrites the live .env file and clears the configuration cache. If a value is wrong â€” especially the database credentials â€” the site may stop working until it is corrected.') }}
            </flux:text>
            <div class="flex gap-2 pt-1">
                <button wire:click="saveEnv" wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 px-4 h-8 text-sm font-medium rounded-lg text-white bg-amber-600 hover:bg-amber-700 transition-colors border-none cursor-pointer">
                    {{ __('Save anyway') }}
                </button>
                <flux:modal.close>
                    <flux:button size="sm" variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</div>
