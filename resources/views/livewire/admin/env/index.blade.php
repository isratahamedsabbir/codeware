<div x-data="{
    init() {
        // A link into this page can point straight at one field, e.g.
        // .../env#VENDOR_URL — scroll it into view and flash it once rendered.
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
        <div class="rounded-lg bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 text-sm dark:bg-amber-950 dark:border-amber-800 dark:text-amber-300">
            <strong>{{ __('Careful') }}:</strong>
            {{ __('These edit the live .env file this server runs on. A wrong value can take the site down until it is fixed. A backup of the current file is saved automatically before every change. Mail credentials live on the Email Templates page instead.') }}
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-start">
            {{-- Maintenance mode --}}
            <x-admin-section-card header-border="border-zinc-100" icon="wrench" title="Maintenance Mode"
                icon-color="{{ $maintenanceMode ? 'bg-red-500/10 text-red-600' : 'bg-primary/10 text-primary' }}"
                description="Takes the public site offline for every visitor. The admin panel and login stay reachable either way."
                class="w-full {{ $maintenanceMode ? 'border-red-300! dark:border-red-800!' : '' }}">
                <x-slot:actions>
                    @if ($maintenanceMode)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 dark:bg-red-950 px-3 py-1 text-xs font-semibold text-red-700 dark:text-red-300 ring-1 ring-red-600/20">
                            <span class="size-1.5 rounded-full bg-red-500 animate-pulse"></span>
                            Site is offline
                        </span>
                    @endif
                </x-slot:actions>

                @if ($maintenanceMode)
                    <flux:button size="sm" variant="danger" wire:click="disableMaintenanceMode" wire:loading.attr="disabled">
                        Bring Site Back Online
                    </flux:button>
                @else
                    <flux:button size="sm" variant="outline" wire:click="confirmEnableMaintenanceMode" wire:loading.attr="disabled">
                        Enable Maintenance Mode
                    </flux:button>
                @endif
            </x-admin-section-card>

            {{-- Debug mode --}}
            <x-admin-section-card header-border="border-zinc-100" icon="bug-ant" title="Debug Mode"
                icon-color="{{ $debugMode ? 'bg-amber-500/10 text-amber-600' : 'bg-primary/10 text-primary' }}"
                description="Shows full error details and stack traces to visitors. Leave this off in production."
                class="w-full {{ $debugMode ? 'border-amber-300! dark:border-amber-800!' : '' }}">
                <x-slot:actions>
                    @if ($debugMode)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 dark:bg-amber-950 px-3 py-1 text-xs font-semibold text-amber-700 dark:text-amber-300 ring-1 ring-amber-600/20">
                            <span class="size-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                            Debug on
                        </span>
                    @endif
                </x-slot:actions>

                @if ($debugMode)
                    <flux:button size="sm" variant="danger" wire:click="disableDebugMode" wire:loading.attr="disabled">
                        Turn Debug Mode Off
                    </flux:button>
                @else
                    <flux:button size="sm" variant="outline" wire:click="confirmEnableDebugMode" wire:loading.attr="disabled">
                        Enable Debug Mode
                    </flux:button>
                @endif
            </x-admin-section-card>
        </div>

        {{-- App, Google Login, Facebook Login, reCAPTCHA, Google Maps and AWS S3
             each get their own hand-built section further down (own layout and — for
             reCAPTCHA — an Enable toggle), so skip them here to avoid rendering the
             same group twice. Each section's guide note opens from the info icon on
             its card (see the env-info modal at the bottom). Any future env group
             added to envFields() without a custom card still falls back to the
             generic card below. --}}
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

        {{-- App --}}
        <x-admin-section-card header-border="border-zinc-100" icon="rocket-launch" title="App"
            description="Core application identity, URLs and cache store.">
            <x-slot:actions>
                <button type="button" wire:click="openInfo('app')" title="About this section"
                    class="inline-flex size-7 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-primary cursor-pointer">
                    <flux:icon.information-circle class="size-5" />
                </button>
            </x-slot:actions>
            @foreach ($this->envFields()['App'] as $key => $meta)
                @include('livewire.admin.env.partials.env-field', ['key' => $key, 'meta' => $meta])
            @endforeach
        </x-admin-section-card>

        {{-- Google Login --}}
        <x-admin-section-card header-border="border-zinc-100" icon="globe-alt" title="Google Login">
            <x-slot:actions>
                <button type="button" wire:click="openInfo('google-login')" title="Where to get these"
                    class="inline-flex size-7 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-primary cursor-pointer">
                    <flux:icon.information-circle class="size-5" />
                </button>
            </x-slot:actions>
            @foreach ($this->envFields()['Google Login'] as $key => $meta)
                @include('livewire.admin.env.partials.env-field', ['key' => $key, 'meta' => $meta])
            @endforeach
        </x-admin-section-card>

        {{-- Facebook Login --}}
        <x-admin-section-card header-border="border-zinc-100" icon="chat-bubble-left-right" title="Facebook Login">
            <x-slot:actions>
                <button type="button" wire:click="openInfo('facebook-login')" title="Where to get these"
                    class="inline-flex size-7 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-primary cursor-pointer">
                    <flux:icon.information-circle class="size-5" />
                </button>
            </x-slot:actions>
            @foreach ($this->envFields()['Facebook Login'] as $key => $meta)
                @include('livewire.admin.env.partials.env-field', ['key' => $key, 'meta' => $meta])
            @endforeach
        </x-admin-section-card>

        {{-- Tracking --}}
        <x-admin-section-card header-border="border-zinc-100" icon="chart-bar" title="Google Pixel"
            description="The Measurement/Pixel ID (e.g. G-XXXXXXXXXX or AW-XXXXXXXXX) exposed via the public settings API for the frontend to use.">
            <x-slot:actions>
                <button type="button" wire:click="openInfo('pixel')" title="Integration guide"
                    class="inline-flex size-7 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-primary cursor-pointer">
                    <flux:icon.information-circle class="size-5" />
                </button>
            </x-slot:actions>
            <flux:field>
                <flux:label>Google Pixel ID</flux:label>
                <flux:input wire:model="settings.google_pixel_id" placeholder="G-XXXXXXXXXX" class="font-mono" />
            </flux:field>
        </x-admin-section-card>

        {{-- reCAPTCHA --}}
        <x-admin-section-card header-border="border-zinc-100" icon="shield-check" title="reCAPTCHA"
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

            @foreach ($this->envFields()['reCAPTCHA'] as $key => $meta)
                @include('livewire.admin.env.partials.env-field', ['key' => $key, 'meta' => $meta])
            @endforeach
        </x-admin-section-card>

        {{-- Google Maps --}}
        <x-admin-section-card header-border="border-zinc-100" icon="map" title="Google Maps"
            description="Used wherever the app needs to render a Google Map (e.g. store/branch locations).">
            <x-slot:actions>
                <button type="button" wire:click="openInfo('google-maps')" title="Where to get this"
                    class="inline-flex size-7 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-primary cursor-pointer">
                    <flux:icon.information-circle class="size-5" />
                </button>
            </x-slot:actions>
            @foreach ($this->envFields()['Google Maps'] as $key => $meta)
                @include('livewire.admin.env.partials.env-field', ['key' => $key, 'meta' => $meta])
            @endforeach

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
                            // key is rejected outright — wrong key, billing disabled, or (most
                            // commonly, since this often differs per environment) this domain
                            // isn't in the key's allowed HTTP referrers — so surface that here
                            // instead of leaving the box permanently blank with only a console
                            // warning to explain why.
                            window.gm_authFailure = () => {
                                $el.innerHTML = '<div class=\'flex items-center justify-center h-full text-center text-xs text-rose-500 px-4\'>{{ __('Google rejected this key — check that this domain is in the key\'s allowed HTTP referrers (Google Cloud Console → Credentials), and that billing / the Maps JavaScript API are enabled.') }}</div>';
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
                        {{ __('Live preview using the saved key, just to confirm it works — centered on a placeholder location. The maps used elsewhere in the app can point anywhere.') }}
                    </flux:text>
                @else
                    <div class="mt-4 flex flex-col items-center justify-center gap-2 h-56 rounded-lg border border-dashed border-zinc-300 dark:border-zinc-700 text-zinc-400 text-sm">
                        <flux:icon.map class="size-6" />
                        <span>{{ __('Save an API key above to preview the map here.') }}</span>
                    </div>
                @endif
        </x-admin-section-card>

        {{-- AWS S3 --}}
        <x-admin-section-card header-border="border-zinc-100" icon="cloud" title="AWS S3"
            description="Only needed if FILESYSTEM_DISK is set to s3 — otherwise uploads stay on local disk and these are unused.">
            <x-slot:actions>
                <button type="button" wire:click="openInfo('aws-s3')" title="Where to get these"
                    class="inline-flex size-7 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-primary cursor-pointer">
                    <flux:icon.information-circle class="size-5" />
                </button>
            </x-slot:actions>
            @foreach ($this->envFields()['AWS S3'] as $key => $meta)
                @include('livewire.admin.env.partials.env-field', ['key' => $key, 'meta' => $meta])
            @endforeach
        </x-admin-section-card>

        {{-- Firebase --}}
        <x-admin-section-card header-border="border-zinc-100" icon="fire" title="Firebase"
            description="Service-account credentials for the Firebase Admin SDK (e.g. push notifications).">
            <x-slot:actions>
                <button type="button" wire:click="openInfo('firebase')" title="Where to get this"
                    class="inline-flex size-7 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-primary cursor-pointer">
                    <flux:icon.information-circle class="size-5" />
                </button>
            </x-slot:actions>
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
        </x-admin-section-card>

        {{-- CMS Editor --}}
        <x-admin-section-card header-border="border-zinc-100" icon="pencil-square" title="CMS Editor"
            description="Base URL of the Next.js Puck editor this admin panel opens for visual editing.">
            <x-slot:actions>
                <button type="button" wire:click="openInfo('cms-editor')" title="What this controls"
                    class="inline-flex size-7 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-primary cursor-pointer">
                    <flux:icon.information-circle class="size-5" />
                </button>
            </x-slot:actions>
            @foreach ($this->envFields()['CMS Editor'] as $key => $meta)
                @include('livewire.admin.env.partials.env-field', ['key' => $key, 'meta' => $meta])
            @endforeach
        </x-admin-section-card>

        <flux:button size="sm" variant="primary" wire:click="confirmSaveEnv" wire:loading.attr="disabled">
            {{ __('Save Environment Settings') }}
        </flux:button>
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
                        <li><strong>App Name</strong> — shown in emails, error pages and some admin screens.</li>
                        <li><strong>App URL</strong> / <strong>Frontend URL</strong> / <strong>Vendor Portal URL</strong> — must match the real domains this install is served on, or links, redirects and CORS will break.</li>
                        <li><strong>Cache Store</strong> — pick <span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">database</span> or <span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">file</span> unless this server has Redis installed and reachable.</li>
                    </ul>
                    <flux:text class="text-xs text-zinc-500">
                        Changing the URLs or cache store may require a full page reload to take effect everywhere. The Environment setting moved to Settings > General.
                    </flux:text>
                @elseif ($infoKey === 'google-login')
                    <ol class="list-decimal list-inside space-y-2">
                        <li><span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">console.cloud.google.com</span> → create (or pick) a project.</li>
                        <li>APIs &amp; Services → Credentials → Create Credentials → <strong>OAuth client ID</strong>.</li>
                        <li>Application type: <strong>Web application</strong>.</li>
                        <li>Under Authorized redirect URIs, paste the exact value from the <strong>Google Redirect URI</strong> field (e.g. <span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded break-all">{{ str_replace('${APP_URL}', config('app.url'), $env['GOOGLE_REDIRECT_URI'] ?? '') }}</span>).</li>
                        <li>Create — copy the <strong>Client ID</strong> and <strong>Client secret</strong> it gives you into the fields, then save.</li>
                    </ol>
                    <flux:text class="text-xs text-zinc-500">
                        First time setting this up, Google may also ask you to configure the OAuth consent screen (app name, support email) before it lets you create the client ID.
                    </flux:text>
                @elseif ($infoKey === 'facebook-login')
                    <ol class="list-decimal list-inside space-y-2">
                        <li><span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">developers.facebook.com</span> → My Apps → Create App → type <strong>Consumer</strong> (or "Other").</li>
                        <li>Add the <strong>Facebook Login</strong> product to the app.</li>
                        <li>App Settings → Basic — copy the <strong>App ID</strong> and <strong>App Secret</strong> into the fields.</li>
                        <li>Facebook Login → Settings → Valid OAuth Redirect URIs, paste the exact value from the <strong>Facebook Redirect URI</strong> field (e.g. <span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded break-all">{{ str_replace('${APP_URL}', config('app.url'), $env['FACEBOOK_REDIRECT_URI'] ?? '') }}</span>).</li>
                        <li>Save changes on Facebook's side, then Save Environment Settings here.</li>
                    </ol>
                    <flux:text class="text-xs text-zinc-500">
                        The app stays in "Development" mode by default — only you (and any testers you add under Roles) can log in with it until you submit it for App Review.
                    </flux:text>
                @elseif ($infoKey === 'pixel')
                    <div>
                        <flux:heading size="sm" class="mb-2">1. Get the ID from Google</flux:heading>
                        <ol class="list-decimal list-inside space-y-1">
                            <li>Google Analytics (GA4): <span class="text-zinc-500">analytics.google.com</span> → Admin → Data Streams → your web stream → copy the <span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">Measurement ID</span> (<span class="font-mono text-xs">G-XXXXXXXXXX</span>).</li>
                            <li>Google Ads conversion tracking: Ads → Tools → Conversions → copy the <span class="font-mono text-xs">AW-XXXXXXXXX</span> ID instead.</li>
                            <li>Paste it into the field and save.</li>
                        </ol>
                    </div>
                    <div>
                        <flux:heading size="sm" class="mb-2">2. Read it from Next.js</flux:heading>
                        <flux:text class="text-xs text-zinc-500 mb-2">
                            The value is already public — it comes back from
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
                            Leave the field blank to skip loading gtag entirely — the snippet above already guards for that.
                        </flux:text>
                    </div>
                @elseif ($infoKey === 'recaptcha')
                    <ol class="list-decimal list-inside space-y-2">
                        <li><span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">google.com/recaptcha/admin</span> → create a new site.</li>
                        <li>reCAPTCHA type: <strong>reCAPTCHA v3</strong>.</li>
                        <li>Add this domain (and <span class="font-mono text-xs">localhost</span> for local testing).</li>
                        <li>Copy the <strong>Site Key</strong> and <strong>Secret Key</strong> into the fields, then save.</li>
                    </ol>
                    <flux:text class="text-xs text-zinc-500">
                        v3 is invisible — no checkbox. It runs in the background and scores each login attempt once a Site Key is saved; clearing both fields turns it off again.
                    </flux:text>
                @elseif ($infoKey === 'google-maps')
                    <ol class="list-decimal list-inside space-y-2">
                        <li><span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">console.cloud.google.com</span> → create (or pick) a project.</li>
                        <li>APIs &amp; Services → Library → enable <strong>Maps JavaScript API</strong> (and <strong>Places API</strong> if needed).</li>
                        <li>APIs &amp; Services → Credentials → Create Credentials → <strong>API Key</strong>.</li>
                        <li>Restrict the key to <strong>HTTP referrers</strong> (your domain, and <span class="font-mono text-xs">localhost</span> for local testing) so it can't be reused elsewhere if it leaks.</li>
                        <li>Copy the key into the field, then save.</li>
                    </ol>
                    <flux:text class="text-xs text-zinc-500">
                        This key is meant to run client-side, so referrer restriction (not secrecy) is what keeps it safe to expose.
                    </flux:text>
                @elseif ($infoKey === 'aws-s3')
                    <ol class="list-decimal list-inside space-y-2">
                        <li><span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">console.aws.amazon.com/s3</span> → create (or pick) a bucket, note its name and region.</li>
                        <li><span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">console.aws.amazon.com/iam</span> → Users → Create user with programmatic access, grant it S3 access to that bucket.</li>
                        <li>Copy the generated <strong>Access Key ID</strong> and <strong>Secret Access Key</strong> into the fields — AWS only shows the secret once.</li>
                        <li>Fill in the <strong>Region</strong> and <strong>Bucket</strong> to match the bucket you created, then save.</li>
                    </ol>
                    <flux:text class="text-xs text-zinc-500">
                        Leave <strong>Use Path-Style Endpoint</strong> off for real AWS S3 — it's only for S3-compatible services (MinIO, DigitalOcean Spaces, etc.) that require it.
                    </flux:text>
                @elseif ($infoKey === 'firebase')
                    <ol class="list-decimal list-inside space-y-2">
                        <li><span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">console.firebase.google.com</span> → your project → gear icon → <strong>Project settings</strong>.</li>
                        <li><strong>Service accounts</strong> tab → <strong>Generate new private key</strong> — downloads a JSON file.</li>
                        <li>In <strong>File Manager</strong>, navigate to <span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">storage/app/private</span> and upload the JSON file there.</li>
                        <li>Paste its path <em>relative to that folder</em> into the field (e.g. just <span class="font-mono text-xs">firebase-service-account.json</span>, or <span class="font-mono text-xs">firebase/service-account.json</span> if you put it in a subfolder), then save.</li>
                        <li>The icon next to the field turns green once the file is found there.</li>
                    </ol>
                    <flux:text class="text-xs text-zinc-500">
                        This file grants full admin access to the Firebase project — keep it out of the public disk and out of version control.
                    </flux:text>
                @elseif ($infoKey === 'cms-editor')
                    <ul class="list-disc list-inside space-y-2">
                        <li>The <strong>Edit</strong> buttons on the Products, Pages and Posts screens build their Puck editor URL from this value.</li>
                        <li>It must point at the host where the Next.js CMS editor runs — including its port, if any (e.g. <span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">http://194.233.65.83:3002</span>).</li>
                        <li>Leave it blank to disable the visual editor buttons, or to keep them pointed at <span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">localhost</span> on a local install.</li>
                    </ul>
                    <flux:text class="text-xs text-zinc-500">
                        Read at runtime as <span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">config('cms.editor_base_url')</span> — saving here clears the config cache so the new value is picked up immediately.
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
                {{ __('This overwrites the live .env file and clears the configuration cache. If a value is wrong — especially the database credentials — the site may stop working until it is corrected.') }}
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

    {{-- Maintenance mode confirmation --}}
    <flux:modal name="maintenance-mode-confirm" class="md:w-96"
        x-on:open-modal.window="if ($event.detail.name === 'maintenance-mode-confirm') $flux.modal('maintenance-mode-confirm').show()"
        x-on:close-modal.window="if ($event.detail.name === 'maintenance-mode-confirm') $flux.modal('maintenance-mode-confirm').close()">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                    <flux:icon.exclamation-triangle class="w-5 h-5 text-red-500" />
                </div>
                <flux:heading>{{ __('Enable maintenance mode?') }}</flux:heading>
            </div>
            <flux:text class="text-sm text-zinc-500">
                {{ __('Every visitor to the public site will see a "down for maintenance" page until you turn this back off. The admin panel and login stay reachable, so you can always come back here to re-enable the site.') }}
            </flux:text>
            <div class="flex gap-2 pt-1">
                <button wire:click="enableMaintenanceMode" wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 px-4 h-8 text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 transition-colors border-none cursor-pointer">
                    {{ __('Take site offline') }}
                </button>
                <flux:modal.close>
                    <flux:button size="sm" variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    {{-- Debug mode confirmation --}}
    <flux:modal name="debug-mode-confirm" class="md:w-96"
        x-on:open-modal.window="if ($event.detail.name === 'debug-mode-confirm') $flux.modal('debug-mode-confirm').show()"
        x-on:close-modal.window="if ($event.detail.name === 'debug-mode-confirm') $flux.modal('debug-mode-confirm').close()">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-amber-50 flex items-center justify-center shrink-0">
                    <flux:icon.exclamation-triangle class="w-5 h-5 text-amber-500" />
                </div>
                <flux:heading>{{ __('Enable debug mode?') }}</flux:heading>
            </div>
            <flux:text class="text-sm text-zinc-500">
                {{ __('Errors will show full stack traces, file paths, and environment values to every visitor until you turn this back off. Only enable this briefly while actively debugging.') }}
            </flux:text>
            <div class="flex gap-2 pt-1">
                <button wire:click="enableDebugMode" wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 px-4 h-8 text-sm font-medium rounded-lg text-white bg-amber-600 hover:bg-amber-700 transition-colors border-none cursor-pointer">
                    {{ __('Enable debug mode') }}
                </button>
                <flux:modal.close>
                    <flux:button size="sm" variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</div>
