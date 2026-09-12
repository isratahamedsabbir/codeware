<div>
    {{-- Alpine tab switcher: General | Currency | ... --}}
    <div x-data="{
        tab: new URLSearchParams(location.search).get('tab') || localStorage.getItem('admin-settings-tab') || 'general',
        init() {
            this.$watch('tab', (value) => localStorage.setItem('admin-settings-tab', value));

            // A link into this page can point straight at one field, e.g.
            // .../settings?tab=env#VENDOR_URL (see Product Vendors' Settings
            // button) — scroll it into view and flash it once the tab's
            // x-show transition has actually rendered it.
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

        {{-- Tab nav --}}
        <div class="flex gap-0 mb-6 border-b border-zinc-200 dark:border-zinc-700">
            <button type="button" @click="tab = 'general'"
                :class="tab==='general'?'border-b-2 border-primary text-primary font-medium':'text-zinc-500 hover:text-zinc-700'"
                class="mx-4 ml-0 rounded-none! py-3 text-sm -mb-px">General</button>
            <button type="button" @click="tab = 'currency'"
                :class="tab==='currency'?'border-b-2 border-primary text-primary font-medium':'text-zinc-500 hover:text-zinc-700'"
                class="mx-4 rounded-none! py-3 text-sm -mb-px">Currency</button>
            <button type="button" @click="tab = 'theme'"
                :class="tab==='theme'?'border-b-2 border-primary text-primary font-medium':'text-zinc-500 hover:text-zinc-700'"
                class="mx-4 rounded-none! py-3 text-sm -mb-px">Theme</button>
            <button type="button" @click="tab = 'env'"
                :class="tab==='env'?'border-b-2 border-primary text-primary font-medium':'text-zinc-500 hover:text-zinc-700'"
                class="mx-4 rounded-none! py-3 text-sm -mb-px">Env</button>
            <button type="button" @click="tab = 'other'"
                :class="tab==='other'?'border-b-2 border-primary text-primary font-medium':'text-zinc-500 hover:text-zinc-700'"
                class="mx-4 rounded-none! py-3 text-sm -mb-px">Other</button>
            <button type="button" @click="tab = 'custom-code'"
                :class="tab==='custom-code'?'border-b-2 border-primary text-primary font-medium':'text-zinc-500 hover:text-zinc-700'"
                class="mx-4 rounded-none! py-3 text-sm -mb-px">Custom Code</button>
            <button type="button" @click="tab = 'constant'"
                :class="tab==='constant'?'border-b-2 border-primary text-primary font-medium':'text-zinc-500 hover:text-zinc-700'"
                class="mx-4 rounded-none! py-3 text-sm -mb-px">Constant</button>
        </div>

        {{-- General tab --}}
        <div x-show="tab === 'general'">
            <div class="max-w-[1600px]">
                {{-- General sits on the left; Localization, Pagination and Newsletter
                     stack tightly to its right in their own column (so their combined
                     height — not each card's own grid row — determines the gap between
                     them, however many small groups exist); Images and anything else
                     fall to a full-width row underneath. Explicit placement rather than
                     order+row-span, since the latter leaves a blank cell the moment the
                     right-hand cards don't add up to exactly two rows. --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-start">
                    @if (isset($groupedSettings['general']))
                        @include('partials.admin-settings-group-card', ['group' => 'general', 'items' => $groupedSettings['general']])
                    @endif

                    <div class="space-y-5">
                        @foreach (['localization', 'pagination', 'newsletter'] as $rightGroup)
                            @continue (! isset($groupedSettings[$rightGroup]))
                            @include('partials.admin-settings-group-card', ['group' => $rightGroup, 'items' => $groupedSettings[$rightGroup]])
                        @endforeach
                    </div>

                    @foreach ($groupedSettings as $group => $items)
                        @continue (in_array($group, ['general', 'localization', 'pagination', 'newsletter'], true))
                        <div class="lg:col-span-2">
                            @include('partials.admin-settings-group-card', ['group' => $group, 'items' => $items])
                        </div>
                    @endforeach
                </div>
            </div>
        </div>


        {{-- Currency tab --}}
        <div x-show="tab === 'currency'">
            <div class="max-w-[1600px] space-y-5">
                <x-admin-section-card header-border="border-zinc-100" icon="banknotes" title="Currency"
                    description="Set the currency used across the site for product pricing and payments.">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <flux:field>
                            <flux:label>Currency Code</flux:label>
                            <flux:input wire:model="settings.currency_code" placeholder="BDT, USD, EUR" class="uppercase" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Currency Symbol</flux:label>
                            <flux:input wire:model="settings.currency_symbol" placeholder="৳, $, €" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Symbol Position</flux:label>
                            <select wire:model="settings.currency_position"
                                class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700">
                                <option value="left">Left (৳1,250.00)</option>
                                <option value="right">Right (1,250.00 ৳)</option>
                            </select>
                        </flux:field>
                        <flux:field>
                            <flux:label>Decimal Places</flux:label>
                            <flux:input type="number" wire:model="settings.decimal_places" min="0" max="4" />
                        </flux:field>
                    </div>

                    <flux:field>
                        <flux:label>Preview</flux:label>
                        <div class="rounded-lg bg-zinc-50 dark:bg-zinc-800/50 px-4 py-6 text-center">
                            <span class="text-2xl font-bold text-zinc-800 dark:text-zinc-100" x-data
                                x-text="($wire.settings.currency_position || 'left') === 'right' ? '1,250.00 ' + ($wire.settings.currency_symbol || '৳') : ($wire.settings.currency_symbol || '৳') + '1,250.00'"></span>
                        </div>
                    </flux:field>
                </x-admin-section-card>
            </div>
        </div>

        {{-- Theme tab --}}
        <div x-show="tab === 'theme'">
            <div class="max-w-[1600px] space-y-5">

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-start">
                    <x-admin-section-card header-border="border-zinc-100" icon="swatch" title="Backend"
                        description="Colors used across the admin panel, including buttons.">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach ($colorSettings as $setting)
                                <flux:field>
                                    <flux:label>{{ ucwords(str_replace('_', ' ', $setting->key)) }}</flux:label>
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg border border-zinc-300 shrink-0"
                                             style="background-color: {{ $settings[$setting->key] ?? '#ffffff' }}"
                                             x-data
                                             :style="'background-color: ' + ($wire.settings['{{ $setting->key }}'] || '#ffffff')"></div>
                                        <flux:input wire:model="settings.{{ $setting->key }}" placeholder="#000000" class="flex-1 font-mono" />
                                    </div>
                                </flux:field>
                            @endforeach
                        </div>
                    </x-admin-section-card>

                    <x-admin-section-card header-border="border-zinc-100" icon="globe-alt" title="Frontend"
                        description="Choose the design shown to visitors on the public site.">
                        <flux:field class="max-w-sm">
                            <flux:label>Site Design</flux:label>
                            <select wire:model="settings.site_theme"
                                class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700">
                                @foreach (\App\Support\Themes::all() as $slug => $label)
                                    <option value="{{ $slug }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <flux:text class="text-xs text-zinc-500">
                                {{ __('The design shown at your site\'s homepage (:url).', ['url' => url('/')]) }}
                            </flux:text>
                        </flux:field>
                    </x-admin-section-card>
                </div>

            </div>
        </div>

        {{-- Env tab --}}
        <div x-show="tab === 'env'">
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

                {{-- Google Login, Facebook Login and reCAPTCHA each get their own
                     hand-built section further down (own layout, guide card, and —
                     for reCAPTCHA — an Enable toggle), so skip them here to avoid
                     rendering the same group twice. --}}
                @php $manuallyRenderedGroups = ['Google Login', 'Facebook Login', 'reCAPTCHA']; @endphp

                @foreach ($this->envFields() as $groupLabel => $fields)
                    @continue(in_array($groupLabel, $manuallyRenderedGroups, true))
                    <x-admin-section-card header-border="border-zinc-100" icon="rocket-launch" title="{{ __($groupLabel) }}"
                        description="Core application identity and URLs. Changing the environment or URLs may require a full page reload to take effect everywhere.">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach ($fields as $key => $meta)
                                @include('livewire.admin.settings.partials.env-field', ['key' => $key, 'meta' => $meta])
                            @endforeach
                        </div>
                    </x-admin-section-card>
                @endforeach

                {{-- Google Login --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-start">
                    <x-admin-section-card header-border="border-zinc-100" icon="globe-alt" title="Google Login">
                        @foreach ($this->envFields()['Google Login'] as $key => $meta)
                            @include('livewire.admin.settings.partials.env-field', ['key' => $key, 'meta' => $meta])
                        @endforeach
                    </x-admin-section-card>

                    <x-admin-section-card header-border="border-zinc-100" icon="book-open" title="Where to get these" body-class="px-6 py-5 space-y-3">
                        <ol class="list-decimal list-inside space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
                            <li><span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">console.cloud.google.com</span> → create (or pick) a project.</li>
                            <li>APIs &amp; Services → Credentials → Create Credentials → <strong>OAuth client ID</strong>.</li>
                            <li>Application type: <strong>Web application</strong>.</li>
                            <li>Under Authorized redirect URIs, paste the exact value from the <strong>Google Redirect URI</strong> field on the left (e.g. <span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded break-all">{{ str_replace('${APP_URL}', config('app.url'), $env['GOOGLE_REDIRECT_URI'] ?? '') }}</span>).</li>
                            <li>Create — copy the <strong>Client ID</strong> and <strong>Client secret</strong> it gives you into the fields on the left, then save.</li>
                        </ol>
                        <flux:text class="text-xs text-zinc-500">
                            First time setting this up, Google may also ask you to configure the OAuth consent screen (app name, support email) before it lets you create the client ID.
                        </flux:text>
                    </x-admin-section-card>
                </div>

                {{-- Facebook Login --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-start">
                    <x-admin-section-card header-border="border-zinc-100" icon="chat-bubble-left-right" title="Facebook Login">
                        @foreach ($this->envFields()['Facebook Login'] as $key => $meta)
                            @include('livewire.admin.settings.partials.env-field', ['key' => $key, 'meta' => $meta])
                        @endforeach
                    </x-admin-section-card>

                    <x-admin-section-card header-border="border-zinc-100" icon="book-open" title="Where to get these" body-class="px-6 py-5 space-y-3">
                        <ol class="list-decimal list-inside space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
                            <li><span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">developers.facebook.com</span> → My Apps → Create App → type <strong>Consumer</strong> (or "Other").</li>
                            <li>Add the <strong>Facebook Login</strong> product to the app.</li>
                            <li>App Settings → Basic — copy the <strong>App ID</strong> and <strong>App Secret</strong> into the fields on the left.</li>
                            <li>Facebook Login → Settings → Valid OAuth Redirect URIs, paste the exact value from the <strong>Facebook Redirect URI</strong> field on the left (e.g. <span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded break-all">{{ str_replace('${APP_URL}', config('app.url'), $env['FACEBOOK_REDIRECT_URI'] ?? '') }}</span>).</li>
                            <li>Save changes on Facebook's side, then Save Environment Settings here.</li>
                        </ol>
                        <flux:text class="text-xs text-zinc-500">
                            The app stays in "Development" mode by default — only you (and any testers you add under Roles) can log in with it until you submit it for App Review.
                        </flux:text>
                    </x-admin-section-card>
                </div>

                {{-- Tracking --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-start">
                    <x-admin-section-card header-border="border-zinc-100" icon="chart-bar" title="Google Pixel"
                        description="The Measurement/Pixel ID (e.g. G-XXXXXXXXXX or AW-XXXXXXXXX) exposed via the public settings API for the frontend to use.">
                        <flux:field>
                            <flux:label>Google Pixel ID</flux:label>
                            <flux:input wire:model="settings.google_pixel_id" placeholder="G-XXXXXXXXXX" class="font-mono" />
                        </flux:field>
                    </x-admin-section-card>

                    <x-admin-section-card header-border="border-zinc-100" icon="book-open" title="Integration guide" body-class="px-6 py-5 space-y-5"
                        description="Where the ID comes from, and how to wire it up in the Next.js frontend.">
                        <div>
                            <flux:heading size="sm" class="mb-2">1. Get the ID from Google</flux:heading>
                            <ol class="list-decimal list-inside space-y-1 text-sm text-zinc-600 dark:text-zinc-400">
                                <li>Google Analytics (GA4): <span class="text-zinc-500">analytics.google.com</span> → Admin → Data Streams → your web stream → copy the <span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">Measurement ID</span> (<span class="font-mono text-xs">G-XXXXXXXXXX</span>).</li>
                                <li>Google Ads conversion tracking: Ads → Tools → Conversions → copy the <span class="font-mono text-xs">AW-XXXXXXXXX</span> ID instead.</li>
                                <li>Paste it into the field on the left and save.</li>
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
                                Leave the field on the left blank to skip loading gtag entirely — the snippet above already guards for that.
                            </flux:text>
                        </div>
                    </x-admin-section-card>
                </div>

                {{-- reCAPTCHA --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-start">
                    <x-admin-section-card header-border="border-zinc-100" icon="shield-check" title="reCAPTCHA"
                        description="Shown on the admin login form only while enabled and both keys below are set.">
                        <x-slot:actions>
                            <label class="flex items-center gap-2 text-sm text-zinc-600 cursor-pointer">
                                <input type="checkbox" wire:model="settings.recaptcha_enabled" class="rounded border-zinc-300 text-primary" />
                                Enable
                            </label>
                        </x-slot:actions>

                        @foreach ($this->envFields()['reCAPTCHA'] as $key => $meta)
                            @include('livewire.admin.settings.partials.env-field', ['key' => $key, 'meta' => $meta])
                        @endforeach
                    </x-admin-section-card>

                    <x-admin-section-card header-border="border-zinc-100" icon="book-open" title="Where to get these" body-class="px-6 py-5 space-y-3">
                        <ol class="list-decimal list-inside space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
                            <li><span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">google.com/recaptcha/admin</span> → create a new site.</li>
                            <li>reCAPTCHA type: <strong>reCAPTCHA v2</strong> → "I'm not a robot" Checkbox.</li>
                            <li>Add this domain (and <span class="font-mono text-xs">localhost</span> for local testing).</li>
                            <li>Copy the <strong>Site Key</strong> and <strong>Secret Key</strong> into the fields on the left, then save.</li>
                        </ol>
                        <flux:text class="text-xs text-zinc-500">
                            The checkbox only appears on the login form once a Site Key is saved — clearing both fields removes it again.
                        </flux:text>
                    </x-admin-section-card>
                </div>

                <flux:button size="sm" variant="primary" wire:click="confirmSaveEnv" wire:loading.attr="disabled">
                    {{ __('Save Environment Settings') }}
                </flux:button>
            </div>
        </div>

        {{-- Other tab --}}
        <div x-show="tab === 'other'">
            <div class="max-w-[1600px]">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-start">
                {{-- Floating action button --}}
                <x-admin-section-card header-border="border-zinc-100" x-data icon="cursor-arrow-rays" title="Floating Button" class="max-w-md"
                    description="Shows a floating button in the corner of every admin page.">
                    <x-slot:actions>
                        <label class="flex items-center gap-2 text-sm text-zinc-600 cursor-pointer">
                            <input type="checkbox" wire:model="settings.floating_button_enabled" class="rounded border-zinc-300 text-primary" />
                            Enable
                        </label>
                    </x-slot:actions>

                    <flux:field>
                        <flux:label>Action</flux:label>
                        <select wire:model="settings.floating_button_action"
                            class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700">
                            <option value="link">Open link</option>
                            <option value="back">Go back</option>
                            <option value="top">Go to top</option>
                        </select>
                    </flux:field>
                    <flux:field x-show="$wire.settings.floating_button_action === 'link'">
                        <flux:label>Link URL</flux:label>
                        <flux:input wire:model="settings.floating_button_link" placeholder="https://example.com" />
                    </flux:field>
                </x-admin-section-card>

                {{-- Table actions display --}}
                <x-admin-section-card header-border="border-zinc-100" icon="ellipsis-horizontal" title="Table Actions" class="max-w-md"
                    description="How action buttons (Edit, Delete, ...) appear on admin list tables.">
                    <flux:field>
                        <flux:label>Display style</flux:label>
                        <select wire:model="settings.admin_actions_display"
                            class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700">
                            <option value="inline">Inline icon buttons</option>
                            <option value="dropdown">Three-dot dropdown menu</option>
                        </select>
                    </flux:field>
                </x-admin-section-card>

                {{-- Watermark --}}
                <x-admin-section-card header-border="border-zinc-100" icon="photo" title="Watermark" class="max-w-md"
                    description="Stamps this image onto every file uploaded to the Media Library.">
                    <x-slot:actions>
                        <label class="flex items-center gap-2 text-sm text-zinc-600 cursor-pointer">
                            <input type="checkbox" wire:model="settings.watermark_enabled" class="rounded border-zinc-300 text-primary" />
                            Enable
                        </label>
                    </x-slot:actions>

                    <x-media-picker model="settings.watermark_image" label="Watermark Image" hint="PNG with transparency works best"
                        placeholder="Select watermark image from library" mimes="png,jpg,jpeg,webp" only-images dropzone />

                    <flux:field>
                        <flux:label>Position</flux:label>
                        <select wire:model="settings.watermark_position"
                            class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700">
                            <option value="top-left">Top left</option>
                            <option value="top-right">Top right</option>
                            <option value="bottom-left">Bottom left</option>
                            <option value="bottom-right">Bottom right</option>
                            <option value="center">Center</option>
                        </select>
                    </flux:field>
                    <flux:field>
                        <flux:label>Opacity ({{ $settings['watermark_opacity'] ?? 50 }}%)</flux:label>
                        <input type="range" min="0" max="100" wire:model="settings.watermark_opacity" class="w-full" />
                    </flux:field>
                </x-admin-section-card>

                {{-- Calculator widget --}}
                <x-admin-section-card header-border="border-zinc-100" icon="calculator" title="Calculator" class="max-w-md"
                    description="Adds a calculator icon to the admin header for quick math.">
                    <x-slot:actions>
                        <label class="flex items-center gap-2 text-sm text-zinc-600 cursor-pointer">
                            <input type="checkbox" wire:model="settings.calculator_enabled" class="rounded border-zinc-300 text-primary" />
                            Enable
                        </label>
                    </x-slot:actions>

                    <p class="text-xs text-zinc-400">
                        Shortcut: <span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1.5 py-0.5 rounded">Ctrl</span> +
                        <span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1.5 py-0.5 rounded">Alt</span> +
                        <span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1.5 py-0.5 rounded">C</span>
                        opens or closes it from anywhere.
                    </p>
                </x-admin-section-card>

                {{-- Sticky note widget --}}
                <x-admin-section-card header-border="border-zinc-100" icon="document-text" title="Sticky Note" class="max-w-md"
                    description="Adds a note icon to the admin header for quick reminders.">
                    <x-slot:actions>
                        <label class="flex items-center gap-2 text-sm text-zinc-600 cursor-pointer">
                            <input type="checkbox" wire:model="settings.sticky_note_enabled" class="rounded border-zinc-300 text-primary" />
                            Enable
                        </label>
                    </x-slot:actions>

                    <p class="text-xs text-zinc-400">
                        Shortcut: <span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1.5 py-0.5 rounded">Ctrl</span> +
                        <span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1.5 py-0.5 rounded">Alt</span> +
                        <span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1.5 py-0.5 rounded">N</span>
                        opens or closes it from anywhere.
                    </p>
                </x-admin-section-card>

                {{-- Shop toggle --}}
                <x-admin-section-card header-border="border-zinc-100" icon="building-storefront" title="Shop Toggle" class="max-w-md"
                    description="Adds a Shop On/Off button to the admin header for quickly closing the storefront to new orders.">
                    <x-slot:actions>
                        <label class="flex items-center gap-2 text-sm text-zinc-600 cursor-pointer">
                            <input type="checkbox" wire:model="settings.shop_toggle_enabled" class="rounded border-zinc-300 text-primary" />
                            Enable
                        </label>
                    </x-slot:actions>

                    <p class="text-xs text-zinc-400">
                        While the shop is off, customers cannot place new orders.
                    </p>
                </x-admin-section-card>
                </div>
            </div>
        </div>

        {{-- Custom Code tab --}}
        <div x-show="tab === 'custom-code'">
            <div class="max-w-[1600px] space-y-5">
                <div class="rounded-lg bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 text-sm dark:bg-amber-950 dark:border-amber-800 dark:text-amber-300">
                    <strong>{{ __('Careful') }}:</strong>
                    {{ __('This code runs as-is on every visitor\'s browser (e.g. analytics or pixel scripts). Only paste code from sources you trust.') }}
                </div>

                <x-admin-section-card header-border="border-zinc-100" icon="code-bracket" title="Head Code"
                    description="Injected into <head>, before it closes — meta tags, verification tags, analytics.">
                    <flux:field>
                        <flux:textarea wire:model="settings.custom_head_code" class="h-40 w-full font-mono text-xs" placeholder="<script>...</script>" />
                    </flux:field>
                </x-admin-section-card>

                <x-admin-section-card header-border="border-zinc-100" icon="code-bracket" title="Body Code"
                    description="Injected just before </body> closes — chat widgets, tracking pixels, deferred scripts.">
                    <flux:field>
                        <flux:textarea wire:model="settings.custom_body_code" class="h-40 w-full font-mono text-xs" placeholder="<script>...</script>" />
                    </flux:field>
                </x-admin-section-card>
            </div>
        </div>

        {{-- Constant tab --}}
        <div x-show="tab === 'constant'">
            <div class="max-w-[1600px] space-y-5">
                <x-admin-section-card header-border="border-zinc-100" icon="variable" title="Constant"
                    description="Freeform key/value pairs, available site-wide — not tied to any page or CMS section."
                    collapsible :collapsed="true">
                    <x-slot:actions>
                        <flux:button size="sm" variant="outline" icon="plus" wire:click="addConstant" x-on:click="open = true">Add field</flux:button>
                    </x-slot:actions>

                    <flux:error name="constants" />

                    <div class="space-y-3">
                    @forelse ($constants as $i => $pair)
                        @php $constantOpen = in_array($i, $openConstants, true); @endphp
                        <div wire:key="settings-constant-row-{{ $i }}"
                            class="group relative rounded-[5px] border border-zinc-200 bg-zinc-50/60 overflow-hidden transition-colors hover:border-zinc-300">
                            <div wire:click="toggleConstant({{ $i }})" role="button" tabindex="0"
                                class="flex items-center justify-between gap-3 px-4 py-2.5 border-b border-zinc-200 bg-white cursor-pointer select-none">
                                <div class="flex items-center gap-2 min-w-0">
                                    <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-zinc-300 text-[11px] font-semibold text-zinc-500">
                                        {{ $i + 1 }}
                                    </div>
                                    <flux:heading size="sm" class="truncate font-mono">
                                        {{ ($pair['key'] ?? '') !== '' ? $pair['key'] : 'Field '.($i + 1) }}
                                    </flux:heading>
                                </div>

                                <div class="flex items-center gap-1 shrink-0">
                                    <button type="button" wire:click.stop="removeConstant({{ $i }})"
                                        class="rounded-lg p-1.5 text-zinc-400 transition-colors hover:bg-rose-50 hover:text-rose-500 cursor-pointer" aria-label="Remove field">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                    <button type="button" wire:click.stop="toggleConstant({{ $i }})"
                                        class="flex size-7 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-600 cursor-pointer"
                                        aria-expanded="{{ $constantOpen ? 'true' : 'false' }}" aria-label="Toggle field">
                                        <flux:icon.chevron-down class="size-4 transition-transform {{ $constantOpen ? 'rotate-180' : '' }}" />
                                    </button>
                                </div>
                            </div>

                            <div class="p-4 space-y-3 {{ $constantOpen ? '' : 'hidden' }}">
                                <flux:field>
                                    <div class="grid grid-cols-2 gap-1.5 rounded-lg bg-zinc-100 p-1">
                                        <button type="button" wire:click="setConstantType({{ $i }}, 'textarea')"
                                            class="flex items-center justify-center gap-1.5 rounded-md py-2 text-xs font-medium transition-colors cursor-pointer {{ ($pair['type'] ?? 'textarea') === 'textarea' ? 'bg-white text-zinc-900 shadow-sm' : 'text-zinc-500 hover:text-zinc-700' }}">
                                            <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h10" />
                                            </svg>
                                            Textarea
                                        </button>
                                        <button type="button" wire:click="setConstantType({{ $i }}, 'file')"
                                            class="flex items-center justify-center gap-1.5 rounded-md py-2 text-xs font-medium transition-colors cursor-pointer {{ ($pair['type'] ?? 'textarea') === 'file' ? 'bg-white text-zinc-900 shadow-sm' : 'text-zinc-500 hover:text-zinc-700' }}">
                                            <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                                <polyline points="14 2 14 8 20 8" />
                                            </svg>
                                            File
                                        </button>
                                    </div>
                                </flux:field>

                                <flux:field>
                                    <flux:label>Key</flux:label>
                                    <flux:input wire:model.live="constants.{{ $i }}.key" placeholder="e.g. support_email" class="font-mono" />
                                    <flux:error name="constants.{{ $i }}.key" />
                                </flux:field>

                                @if (($pair['type'] ?? 'textarea') === 'file')
                                    <x-media-picker model="constants.{{ $i }}.value" label="Value" dropzone />
                                    <flux:error name="constants.{{ $i }}.value" />
                                @else
                                    <flux:field>
                                        <flux:label>Value</flux:label>
                                        <flux:textarea wire:model="constants.{{ $i }}.value" class="h-24" placeholder="e.g. support@example.com" />
                                        <flux:error name="constants.{{ $i }}.value" />
                                    </flux:field>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="rounded-[5px] border border-dashed border-zinc-200 py-10 text-center">
                            <svg class="mx-auto mb-2 h-8 w-8 text-zinc-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7" />
                            </svg>
                            <p class="text-sm text-zinc-400">No constants yet.</p>
                        </div>
                    @endforelse
                    </div>
                </x-admin-section-card>
            </div>
        </div>

        {{-- Save — hidden on the Env tab, where "Save Environment Settings"
             above already persists both the .env fields and any plain
             Settings on that tab (Tracking's Google Pixel ID) in one click. --}}
        <div class="mt-6" x-show="tab !== 'env'">
            <flux:button variant="primary" size="sm" wire:click="save" wire:loading.attr="disabled">
                Save Settings
            </flux:button>
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

    <livewire:admin.media-library.picker-modal key="settings-picker-modal" />
</div>
