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
                    'authentication' => ['Authentication', 'lock-closed', 'Google & Facebook login, reCAPTCHA'],
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

        {{-- â”€â”€ Careful notice â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
        <div class="rounded-lg bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 text-sm dark:bg-amber-950 dark:border-amber-800 dark:text-amber-300">
            <strong>{{ __('Careful') }}:</strong>
            {{ __('These edit the live .env file this server runs on. A wrong value can take the site down until it is fixed. A backup of the current file is saved automatically before every change. Mail credentials live on the Email Templates page instead.') }}
        </div>

        {{-- â”€â”€ General tab â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
        <div x-show="activeTab === 'general'" x-cloak class="space-y-5">

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-start">
                {{-- Maintenance mode --}}
                <x-admin-section-card id="env-section-maintenance" header-border="border-zinc-100" icon="wrench" title="Maintenance Mode"
                    icon-color="{{ $maintenanceMode ? 'bg-red-500/10 text-red-600' : 'bg-primary/10 text-primary' }}"
                    description="Takes the public site offline for every visitor. The admin panel and login stay reachable either way."
                    class="w-full scroll-mt-24 {{ $maintenanceMode ? 'border-red-300! dark:border-red-800!' : '' }}">
                    <x-slot:actions>
                        <button type="button" wire:click="toggleMaintenanceMode" role="switch"
                            aria-checked="{{ $maintenanceMode ? 'true' : 'false' }}" title="Take the public site offline"
                            class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer items-center rounded-full transition-colors {{ $maintenanceMode ? 'bg-red-500' : 'bg-zinc-300 dark:bg-zinc-600' }}">
                            <span class="absolute left-0.5 top-0.5 size-4 rounded-full bg-white shadow transition-transform {{ $maintenanceMode ? 'translate-x-4' : '' }}"></span>
                        </button>
                    </x-slot:actions>
                </x-admin-section-card>

                {{-- Debug mode --}}
                <x-admin-section-card id="env-section-debug" header-border="border-zinc-100" icon="bug-ant" title="Debug Mode"
                    icon-color="{{ $debugMode ? 'bg-amber-500/10 text-amber-600' : 'bg-primary/10 text-primary' }}"
                    description="Shows full error details and stack traces to visitors. Leave this off in production."
                    class="w-full scroll-mt-24 {{ $debugMode ? 'border-amber-300! dark:border-amber-800!' : '' }}">
                    <x-slot:actions>
                        <button type="button" wire:click="toggleDebugMode" role="switch"
                            aria-checked="{{ $debugMode ? 'true' : 'false' }}" title="Show full error details to visitors"
                            class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer items-center rounded-full transition-colors {{ $debugMode ? 'bg-amber-500' : 'bg-zinc-300 dark:bg-zinc-600' }}">
                            <span class="absolute left-0.5 top-0.5 size-4 rounded-full bg-white shadow transition-transform {{ $debugMode ? 'translate-x-4' : '' }}"></span>
                        </button>
                    </x-slot:actions>
                </x-admin-section-card>
            </div>

            {{-- App --}}
            <x-admin-section-card id="env-section-app" class="scroll-mt-24" header-border="border-zinc-100" icon="rocket-launch" title="App"
                description="Core application identity, URLs and cache store.">
                <x-slot:actions>
                    <button type="button" wire:click="openInfo('app')" title="About this section"
                        class="inline-flex size-7 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-primary cursor-pointer">
                        <flux:icon.information-circle class="size-5" />
                    </button>
                </x-slot:actions>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-6 gap-y-5">
                    @foreach ($this->envFields()['App'] as $key => $meta)
                        @include('livewire.admin.env.partials.env-field', ['key' => $key, 'meta' => $meta])
                    @endforeach
                </div>
            </x-admin-section-card>
        </div>

        {{-- â”€â”€ Authentication tab â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
        <div x-show="activeTab === 'authentication'" x-cloak class="space-y-5">

            {{-- Google Login --}}
            <x-admin-section-card id="env-section-google-login" class="scroll-mt-24" header-border="border-zinc-100" icon="globe-alt" title="Google Login">
                <x-slot:actions>
                    <button type="button" wire:click="openInfo('google-login')" title="Where to get these"
                        class="inline-flex size-7 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-primary cursor-pointer">
                        <flux:icon.information-circle class="size-5" />
                    </button>
                </x-slot:actions>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-6 gap-y-5">
                    @foreach ($this->envFields()['Google Login'] as $key => $meta)
                        @include('livewire.admin.env.partials.env-field', ['key' => $key, 'meta' => $meta])
                    @endforeach
                </div>
            </x-admin-section-card>

            {{-- Facebook Login --}}
            <x-admin-section-card id="env-section-facebook-login" class="scroll-mt-24" header-border="border-zinc-100" icon="chat-bubble-left-right" title="Facebook Login">
                <x-slot:actions>
                    <button type="button" wire:click="openInfo('facebook-login')" title="Where to get these"
                        class="inline-flex size-7 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-primary cursor-pointer">
                        <flux:icon.information-circle class="size-5" />
                    </button>
                </x-slot:actions>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-6 gap-y-5">
                    @foreach ($this->envFields()['Facebook Login'] as $key => $meta)
                        @include('livewire.admin.env.partials.env-field', ['key' => $key, 'meta' => $meta])
                    @endforeach
                </div>
            </x-admin-section-card>

            {{-- reCAPTCHA --}}
            <x-admin-section-card id="env-section-recaptcha" class="scroll-mt-24" header-border="border-zinc-100" icon="shield-check" title="reCAPTCHA"
                description="Shown on the admin login form only while enabled and both keys below are set.">
                <x-slot:actions>
                    <label class="flex items-center gap-2 text-sm text-zinc-600 cursor-pointer">
                        <input type="checkbox" wire:model="settings.recaptcha_enabled" class="rounded border-zinc-300 text-primary" />
                        Enable
                    </label>
                    <button type="button" wire:click="openInfo('recaptcha')" title="Where to get these"
                        class="inline-flex size-7 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-primary cursor-pointer">
                        <flux:icon.information-circle class="size-5" />
                    </button>
                </x-slot:actions>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-6 gap-y-5">
                    @foreach ($this->envFields()['reCAPTCHA'] as $key => $meta)
                        @include('livewire.admin.env.partials.env-field', ['key' => $key, 'meta' => $meta])
                    @endforeach
                </div>
            </x-admin-section-card>
        </div>

        {{-- â”€â”€ Integrations tab â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
        <div x-show="activeTab === 'integrations'" x-cloak class="space-y-5">

            {{-- Google Pixel --}}
            <x-admin-section-card id="env-section-pixel" class="scroll-mt-24" header-border="border-zinc-100" icon="chart-bar" title="Google Pixel"
                description="The Measurement/Pixel ID (e.g. G-XXXXXXXXXX or AW-XXXXXXXXX) exposed via the public settings API for the frontend to use.">
                <x-slot:actions>
                    <button type="button" wire:click="openInfo('pixel')" title="Integration guide"
                        class="inline-flex size-7 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-primary cursor-pointer">
                        <flux:icon.information-circle class="size-5" />
                    </button>
                </x-slot:actions>
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
                <x-slot:actions>
                    <button type="button" wire:click="openInfo('google-maps')" title="Where to get this"
                        class="inline-flex size-7 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-primary cursor-pointer">
                        <flux:icon.information-circle class="size-5" />
                    </button>
                </x-slot:actions>
                <div class="max-w-xl">
                    @foreach ($this->envFields()['Google Maps'] as $key => $meta)
                        @include('livewire.admin.env.partials.env-field', ['key' => $key, 'meta' => $meta])
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

            {{-- AWS S3 --}}
            <x-admin-section-card id="env-section-aws-s3" class="scroll-mt-24" header-border="border-zinc-100" icon="cloud" title="AWS S3"
                description="Only needed if FILESYSTEM_DISK is set to s3 â€” otherwise uploads stay on local disk and these are unused.">
                <x-slot:actions>
                    <button type="button" wire:click="openInfo('aws-s3')" title="Where to get these"
                        class="inline-flex size-7 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-primary cursor-pointer">
                        <flux:icon.information-circle class="size-5" />
                    </button>
                </x-slot:actions>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-6 gap-y-5">
                    @foreach ($this->envFields()['AWS S3'] as $key => $meta)
                        @include('livewire.admin.env.partials.env-field', ['key' => $key, 'meta' => $meta])
                    @endforeach
                </div>
            </x-admin-section-card>

            {{-- Firebase --}}
            <x-admin-section-card id="env-section-firebase" class="scroll-mt-24" header-border="border-zinc-100" icon="fire" title="Firebase"
                description="Service-account credentials for the Firebase Admin SDK (e.g. push notifications).">
                <x-slot:actions>
                    <button type="button" wire:click="openInfo('firebase')" title="Where to get this"
                        class="inline-flex size-7 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-primary cursor-pointer">
                        <flux:icon.information-circle class="size-5" />
                    </button>
                </x-slot:actions>
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
                <x-slot:actions>
                    <button type="button" wire:click="openInfo('cms-editor')" title="What this controls"
                        class="inline-flex size-7 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-primary cursor-pointer">
                        <flux:icon.information-circle class="size-5" />
                    </button>
                </x-slot:actions>
                <div class="max-w-xl">
                    @foreach ($this->envFields()['CMS Editor'] as $key => $meta)
                        @include('livewire.admin.env.partials.env-field', ['key' => $key, 'meta' => $meta])
                    @endforeach
                </div>
            </x-admin-section-card>

            {{-- Fallback: any future env group added without a hand-built card above --}}
            @php $manuallyRenderedGroups = ['App', 'Google Login', 'Facebook Login', 'reCAPTCHA', 'Google Maps', 'AWS S3', 'Firebase', 'CMS Editor']; @endphp
            @foreach ($this->envFields() as $groupLabel => $fields)
                @continue(in_array($groupLabel, $manuallyRenderedGroups, true))
                <x-admin-section-card header-border="border-zinc-100" icon="rocket-launch" title="{{ __($groupLabel) }}">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach ($fields as $key => $meta)
                            @include('livewire.admin.env.partials.env-field', ['key' => $key, 'meta' => $meta])
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

    {{-- Section info modal --}}
    <flux:modal name="env-info" class="md:w-xl"
        x-on:open-modal.window="if ($event.detail.name === 'env-info') $flux.modal('env-info').show()"
        x-on:close-modal.window="if ($event.detail.name === 'env-info') $flux.modal('env-info').close()">
        @php
            $infoTitles = [
                'app' => 'About this section',
                'google-login' => 'Where to get these',
                'facebook-login' => 'Where to get these',
                'pixel' => 'Integration guide',
                'recaptcha' => 'Where to get these',
                'google-maps' => 'Where to get this',
                'aws-s3' => 'Where to get these',
                'firebase' => 'Where to get this',
                'cms-editor' => 'What this controls',
            ];
        @endphp
        <div class="space-y-4">
            <flux:heading>{{ $infoTitles[$infoKey] ?? '' }}</flux:heading>

            <div class="max-h-[65vh] overflow-y-auto pr-1 space-y-4 text-sm text-zinc-600 dark:text-zinc-400">
                @if ($infoKey === 'app')
                    <ul class="list-disc list-inside space-y-2">
                        <li><strong>App Name</strong> â€” shown in emails, error pages and some admin screens.</li>
                        <li><strong>App URL</strong> / <strong>Frontend URL</strong> / <strong>Vendor Portal URL</strong> â€” must match the real domains this install is served on, or links, redirects and CORS will break.</li>
                        <li><strong>Cache Store</strong> â€” pick <span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">database</span> or <span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">file</span> unless this server has Redis installed and reachable.</li>
                    </ul>
                    <flux:text class="text-xs text-zinc-500">
                        Changing the URLs or cache store may require a full page reload to take effect everywhere. The Environment setting moved to Settings > General.
                    </flux:text>
                @elseif ($infoKey === 'google-login')
                    <ol class="list-decimal list-inside space-y-2">
                        <li><span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">console.cloud.google.com</span> â†’ create (or pick) a project.</li>
                        <li>APIs &amp; Services â†’ Credentials â†’ Create Credentials â†’ <strong>OAuth client ID</strong>.</li>
                        <li>Application type: <strong>Web application</strong>.</li>
                        <li>Under Authorized redirect URIs, paste the exact value from the <strong>Google Redirect URI</strong> field (e.g. <span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded break-all">{{ str_replace('${APP_URL}', config('app.url'), $env['GOOGLE_REDIRECT_URI'] ?? '') }}</span>).</li>
                        <li>Create â€” copy the <strong>Client ID</strong> and <strong>Client secret</strong> it gives you into the fields, then save.</li>
                    </ol>
                    <flux:text class="text-xs text-zinc-500">
                        First time setting this up, Google may also ask you to configure the OAuth consent screen (app name, support email) before it lets you create the client ID.
                    </flux:text>
                @elseif ($infoKey === 'facebook-login')
                    <ol class="list-decimal list-inside space-y-2">
                        <li><span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">developers.facebook.com</span> â†’ My Apps â†’ Create App â†’ type <strong>Consumer</strong> (or "Other").</li>
                        <li>Add the <strong>Facebook Login</strong> product to the app.</li>
                        <li>App Settings â†’ Basic â€” copy the <strong>App ID</strong> and <strong>App Secret</strong> into the fields.</li>
                        <li>Facebook Login â†’ Settings â†’ Valid OAuth Redirect URIs, paste the exact value from the <strong>Facebook Redirect URI</strong> field (e.g. <span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded break-all">{{ str_replace('${APP_URL}', config('app.url'), $env['FACEBOOK_REDIRECT_URI'] ?? '') }}</span>).</li>
                        <li>Save changes on Facebook's side, then Save Environment Settings here.</li>
                    </ol>
                    <flux:text class="text-xs text-zinc-500">
                        The app stays in "Development" mode by default â€” only you (and any testers you add under Roles) can log in with it until you submit it for App Review.
                    </flux:text>
                @elseif ($infoKey === 'pixel')
                    <div>
                        <flux:heading size="sm" class="mb-2">1. Get the ID from Google</flux:heading>
                        <ol class="list-decimal list-inside space-y-1">
                            <li>Google Analytics (GA4): <span class="text-zinc-500">analytics.google.com</span> â†’ Admin â†’ Data Streams â†’ your web stream â†’ copy the <span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">Measurement ID</span> (<span class="font-mono text-xs">G-XXXXXXXXXX</span>).</li>
                            <li>Google Ads conversion tracking: Ads â†’ Tools â†’ Conversions â†’ copy the <span class="font-mono text-xs">AW-XXXXXXXXX</span> ID instead.</li>
                            <li>Paste it into the field and save.</li>
                        </ol>
                    </div>
                    <div>
                        <flux:heading size="sm" class="mb-2">2. Read it from Next.js</flux:heading>
                        <flux:text class="text-xs text-zinc-500 mb-2">
                            The value is already public â€” it comes back from
                            <span class="font-mono bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">GET /api/v1/settings/public</span>
                            as <span class="font-mono bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">data.tracking.google_pixel_id</span>. Fetch it once in the root layout and inject the gtag script:
                        </flux:text>
                        <pre class="rounded-lg bg-zinc-900 text-zinc-100 text-[11px] leading-relaxed p-3.5 overflow-x-auto" style="color-scheme: dark; background-color: #18181b !important; color: #f4f4f5 !important;"><code style="color: #f4f4f5 !important;">{{ '// app/layout.tsx
import Script from "next/script";

async function getPublicSettings() {
  const res = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/api/v1/settings/public`, {
    next: { revalidate: 3600 },
  });
  return (await res.json()).data;
}

export default async function RootLayout({ children }: { children: React.ReactNode }) {
  const settings = await getPublicSettings();
  const pixelId = settings.tracking?.google_pixel_id;

  return (
    <html lang="en">
      <body>
        {pixelId && (
          <>
            <Script
              src={`https://www.googletagmanager.com/gtag/js?id=${pixelId}`}
              strategy="afterInteractive"
            />
            <Script id="gtag-init" strategy="afterInteractive">
              {`
                window.dataLayer = window.dataLayer || [];
                function gtag(){ dataLayer.push(arguments); }
                gtag("js", new Date());
                gtag("config", "${pixelId}");
              `}
            </Script>
          </>
        )}
        {children}
      </body>
    </html>
  );
}' }}</code></pre>
                        <flux:text class="text-xs text-zinc-500 mt-2">
                            Leave the field blank to skip loading gtag entirely â€” the snippet above already guards for that.
                        </flux:text>
                    </div>
                @elseif ($infoKey === 'recaptcha')
                    <ol class="list-decimal list-inside space-y-2">
                        <li><span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">google.com/recaptcha/admin</span> â†’ create a new site.</li>
                        <li>reCAPTCHA type: <strong>reCAPTCHA v3</strong>.</li>
                        <li>Add this domain (and <span class="font-mono text-xs">localhost</span> for local testing).</li>
                        <li>Copy the <strong>Site Key</strong> and <strong>Secret Key</strong> into the fields, then save.</li>
                    </ol>
                    <flux:text class="text-xs text-zinc-500">
                        v3 is invisible â€” no checkbox. It runs in the background and scores each login attempt once a Site Key is saved; clearing both fields turns it off again.
                    </flux:text>
                @elseif ($infoKey === 'google-maps')
                    <ol class="list-decimal list-inside space-y-2">
                        <li><span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">console.cloud.google.com</span> â†’ create (or pick) a project.</li>
                        <li>APIs &amp; Services â†’ Library â†’ enable <strong>Maps JavaScript API</strong> (and <strong>Places API</strong> if needed).</li>
                        <li>APIs &amp; Services â†’ Credentials â†’ Create Credentials â†’ <strong>API Key</strong>.</li>
                        <li>Restrict the key to <strong>HTTP referrers</strong> (your domain, and <span class="font-mono text-xs">localhost</span> for local testing) so it can't be reused elsewhere if it leaks.</li>
                        <li>Copy the key into the field, then save.</li>
                    </ol>
                    <flux:text class="text-xs text-zinc-500">
                        This key is meant to run client-side, so referrer restriction (not secrecy) is what keeps it safe to expose.
                    </flux:text>
                @elseif ($infoKey === 'aws-s3')
                    <ol class="list-decimal list-inside space-y-2">
                        <li><span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">console.aws.amazon.com/s3</span> â†’ create (or pick) a bucket, note its name and region.</li>
                        <li><span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">console.aws.amazon.com/iam</span> â†’ Users â†’ Create user with programmatic access, grant it S3 access to that bucket.</li>
                        <li>Copy the generated <strong>Access Key ID</strong> and <strong>Secret Access Key</strong> into the fields â€” AWS only shows the secret once.</li>
                        <li>Fill in the <strong>Region</strong> and <strong>Bucket</strong> to match the bucket you created, then save.</li>
                    </ol>
                    <flux:text class="text-xs text-zinc-500">
                        Leave <strong>Use Path-Style Endpoint</strong> off for real AWS S3 â€” it's only for S3-compatible services (MinIO, DigitalOcean Spaces, etc.) that require it.
                    </flux:text>
                @elseif ($infoKey === 'firebase')
                    <ol class="list-decimal list-inside space-y-2">
                        <li><span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">console.firebase.google.com</span> â†’ your project â†’ gear icon â†’ <strong>Project settings</strong>.</li>
                        <li><strong>Service accounts</strong> tab â†’ <strong>Generate new private key</strong> â€” downloads a JSON file.</li>
                        <li>In <strong>File Manager</strong>, navigate to <span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">storage/app/private</span> and upload the JSON file there.</li>
                        <li>Paste its path <em>relative to that folder</em> into the field (e.g. just <span class="font-mono text-xs">firebase-service-account.json</span>, or <span class="font-mono text-xs">firebase/service-account.json</span> if you put it in a subfolder), then save.</li>
                        <li>The icon next to the field turns green once the file is found there.</li>
                    </ol>
                    <flux:text class="text-xs text-zinc-500">
                        This file grants full admin access to the Firebase project â€” keep it out of the public disk and out of version control.
                    </flux:text>
                @elseif ($infoKey === 'cms-editor')
                    <ul class="list-disc list-inside space-y-2">
                        <li>The <strong>Edit</strong> buttons on the Products, Pages and Posts screens build their Puck editor URL from this value.</li>
                        <li>It must point at the host where the Next.js CMS editor runs â€” including its port, if any (e.g. <span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">http://194.233.65.83:3002</span>).</li>
                        <li>Leave it blank to disable the visual editor buttons, or to keep them pointed at <span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">localhost</span> on a local install.</li>
                    </ul>
                    <flux:text class="text-xs text-zinc-500">
                        Read at runtime as <span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">config('cms.editor_base_url')</span> â€” saving here clears the config cache so the new value is picked up immediately.
                    </flux:text>
                @endif
            </div>
        </div>
    </flux:modal>

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