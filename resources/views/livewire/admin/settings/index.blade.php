<div>
    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm dark:bg-green-950 dark:border-green-800 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    {{-- Alpine tab switcher: General | Layout | Payments --}}
    <div x-data="{ tab: 'general' }">

        {{-- Tab nav --}}
        <div class="flex gap-0 mb-6 border-b border-zinc-200 dark:border-zinc-700">
            <button type="button" @click="tab = 'general'"
                :class="tab==='general'?'border-b-2 border-blue-600 text-blue-600 font-medium':'text-zinc-500 hover:text-zinc-700'"
                class="px-5 py-3 text-sm -mb-px">General</button>
            <button type="button" @click="tab = 'layout'"
                :class="tab==='layout'?'border-b-2 border-blue-600 text-blue-600 font-medium':'text-zinc-500 hover:text-zinc-700'"
                class="px-5 py-3 text-sm -mb-px">Layout</button>
            <button type="button" @click="tab = 'payments'"
                :class="tab==='payments'?'border-b-2 border-blue-600 text-blue-600 font-medium':'text-zinc-500 hover:text-zinc-700'"
                class="px-5 py-3 text-sm -mb-px">Payments</button>
            <button type="button" @click="tab = 'currency'"
                :class="tab==='currency'?'border-b-2 border-blue-600 text-blue-600 font-medium':'text-zinc-500 hover:text-zinc-700'"
                class="px-5 py-3 text-sm -mb-px">Currency</button>
            <button type="button" @click="tab = 'seo'"
                :class="tab==='seo'?'border-b-2 border-blue-600 text-blue-600 font-medium':'text-zinc-500 hover:text-zinc-700'"
                class="px-5 py-3 text-sm -mb-px">SEO</button>
            <button type="button" @click="tab = 'theme'"
                :class="tab==='theme'?'border-b-2 border-blue-600 text-blue-600 font-medium':'text-zinc-500 hover:text-zinc-700'"
                class="px-5 py-3 text-sm -mb-px">Theme</button>
            <button type="button" @click="tab = 'colors'"
                :class="tab==='colors'?'border-b-2 border-blue-600 text-blue-600 font-medium':'text-zinc-500 hover:text-zinc-700'"
                class="px-5 py-3 text-sm -mb-px">Other</button>
        </div>

        {{-- General tab --}}
        <div x-show="tab === 'general'">
            <div class="max-w-2xl space-y-6">
                @foreach ($groupedSettings as $group => $items)
                    <div>
                        <flux:heading size="sm" class="mb-3 capitalize">{{ $group ?? 'General' }}</flux:heading>
                        <div class="space-y-4 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
                            @foreach ($items as $setting)
                                <flux:field>
                                    <flux:label>{{ ucwords(str_replace('_', ' ', $setting->key)) }}</flux:label>
                                    @if ($setting->type === 'boolean')
                                        <div class="flex items-center gap-2">
                                            <input type="checkbox"
                                                wire:model="settings.{{ $setting->key }}"
                                                class="rounded border-zinc-300 text-blue-600" />
                                            <span class="text-sm text-zinc-600">Enable</span>
                                        </div>
                                    @elseif ($setting->type === 'color')
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-lg border border-zinc-300 shrink-0"
                                                 style="background-color: {{ $settings[$setting->key] ?? '#ffffff' }}"
                                                 x-data
                                                 :style="'background-color: ' + ($wire.settings['{{ $setting->key }}'] || '#ffffff')"></div>
                                            <flux:input wire:model="settings.{{ $setting->key }}" placeholder="#000000" class="flex-1 font-mono" />
                                        </div>
                                    @elseif ($setting->key === 'site_icon' || $setting->key === 'favicon')
                                        <x-media-picker model="settings.{{ $setting->key }}"
                                            label="{{ $setting->key === 'favicon' ? 'Favicon' : 'Site Icon' }}"
                                            placeholder="Choose a {{ $setting->key === 'favicon' ? 'favicon' : 'site icon' }} from the library" />
                                    @elseif ($setting->type === 'textarea')
                                        <flux:textarea wire:model="settings.{{ $setting->key }}" rows="3" />
                                    @else
                                        <flux:input wire:model="settings.{{ $setting->key }}" />
                                    @endif
                                </flux:field>
                            @endforeach
                        </div>
                    </div>
                @endforeach

            </div>
        </div>

        {{-- Layout tab --}}
        <div x-show="tab === 'layout'">
            <div class="max-w-2xl space-y-4">
                <flux:text class="text-zinc-500">
                    Edit your site header and footer using the Puck visual editor. Changes open in a new tab.
                </flux:text>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
                        <flux:heading size="sm" class="mb-2">Header</flux:heading>
                        <flux:text class="text-sm text-zinc-500 mb-4">Edit the site-wide header layout and navigation.</flux:text>
                        <a href="{{ $this->getEditorUrl('header') }}" target="_blank"
                            class="inline-flex items-center justify-center gap-2 w-full rounded-lg bg-blue-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-blue-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Edit Header
                        </a>
                    </div>

                    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
                        <flux:heading size="sm" class="mb-2">Footer</flux:heading>
                        <flux:text class="text-sm text-zinc-500 mb-4">Edit the site-wide footer layout and links.</flux:text>
                        <a href="{{ $this->getEditorUrl('footer') }}" target="_blank"
                            class="inline-flex items-center justify-center gap-2 w-full rounded-lg bg-blue-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-blue-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Edit Footer
                        </a>
                    </div>
                </div>

                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 bg-zinc-50 dark:bg-zinc-800/50">
                    <h5 class="text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider mb-2">How it works</h5>
                    <ul class="text-xs text-zinc-500 space-y-1">
                        <li>1. Click "Edit Header" or "Edit Footer" to open the Puck visual editor</li>
                        <li>2. Make your changes using the drag-and-drop interface</li>
                        <li>3. Save your changes in Puck - they will be stored automatically</li>
                        <li>4. The changes will appear on your live site immediately</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Payments tab --}}
        <div x-show="tab === 'payments'" class="max-w-2xl space-y-6">
            <flux:text class="text-zinc-500">
                Enter your payment gateway credentials. Credentials are stored privately and never exposed via the public API.
            </flux:text>

            {{-- PayPal --}}
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
                <div class="flex items-center justify-between mb-4">
                    <flux:heading size="sm">PayPal</flux:heading>
                    <label class="flex items-center gap-2 text-sm text-zinc-600 cursor-pointer">
                        <input type="checkbox" wire:model="settings.paypal_enabled" class="rounded border-zinc-300 text-blue-600" />
                        Enable
                    </label>
                </div>
                <div class="space-y-4">
                    <flux:field>
                        <flux:label>Mode</flux:label>
                        <select wire:model="settings.paypal_mode"
                            class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700">
                            <option value="sandbox">Sandbox</option>
                            <option value="live">Live</option>
                        </select>
                    </flux:field>
                    <flux:field>
                        <flux:label>Client ID</flux:label>
                        <flux:input wire:model="settings.paypal_client_id" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Client Secret</flux:label>
                        <flux:input type="password" wire:model="settings.paypal_client_secret" />
                    </flux:field>
                </div>
            </div>

            {{-- Stripe --}}
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
                <div class="flex items-center justify-between mb-4">
                    <flux:heading size="sm">Stripe</flux:heading>
                    <label class="flex items-center gap-2 text-sm text-zinc-600 cursor-pointer">
                        <input type="checkbox" wire:model="settings.stripe_enabled" class="rounded border-zinc-300 text-blue-600" />
                        Enable
                    </label>
                </div>
                <div class="space-y-4">
                    <flux:field>
                        <flux:label>Mode</flux:label>
                        <select wire:model="settings.stripe_mode"
                            class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700">
                            <option value="test">Test</option>
                            <option value="live">Live</option>
                        </select>
                    </flux:field>
                    <flux:field>
                        <flux:label>Publishable Key</flux:label>
                        <flux:input wire:model="settings.stripe_publishable_key" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Secret Key</flux:label>
                        <flux:input type="password" wire:model="settings.stripe_secret_key" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Webhook Secret</flux:label>
                        <flux:input type="password" wire:model="settings.stripe_webhook_secret" />
                    </flux:field>
                </div>
            </div>

            {{-- bKash --}}
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
                <div class="flex items-center justify-between mb-4">
                    <flux:heading size="sm">bKash</flux:heading>
                    <label class="flex items-center gap-2 text-sm text-zinc-600 cursor-pointer">
                        <input type="checkbox" wire:model="settings.bkash_enabled" class="rounded border-zinc-300 text-blue-600" />
                        Enable
                    </label>
                </div>
                <div class="space-y-4">
                    <flux:field>
                        <flux:label>Mode</flux:label>
                        <select wire:model="settings.bkash_mode"
                            class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700">
                            <option value="sandbox">Sandbox</option>
                            <option value="live">Live</option>
                        </select>
                    </flux:field>
                    <flux:field>
                        <flux:label>Username</flux:label>
                        <flux:input wire:model="settings.bkash_username" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Password</flux:label>
                        <flux:input type="password" wire:model="settings.bkash_password" />
                    </flux:field>
                    <flux:field>
                        <flux:label>App Key</flux:label>
                        <flux:input wire:model="settings.bkash_app_key" />
                    </flux:field>
                    <flux:field>
                        <flux:label>App Secret</flux:label>
                        <flux:input type="password" wire:model="settings.bkash_app_secret" />
                    </flux:field>
                </div>
            </div>

            {{-- SSLCommerz --}}
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
                <div class="flex items-center justify-between mb-4">
                    <flux:heading size="sm">SSLCommerz</flux:heading>
                    <label class="flex items-center gap-2 text-sm text-zinc-600 cursor-pointer">
                        <input type="checkbox" wire:model="settings.sslcommerz_enabled" class="rounded border-zinc-300 text-blue-600" />
                        Enable
                    </label>
                </div>
                <div class="space-y-4">
                    <flux:field>
                        <flux:label>Mode</flux:label>
                        <select wire:model="settings.sslcommerz_mode"
                            class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700">
                            <option value="sandbox">Sandbox</option>
                            <option value="live">Live</option>
                        </select>
                    </flux:field>
                    <flux:field>
                        <flux:label>Store ID</flux:label>
                        <flux:input wire:model="settings.sslcommerz_store_id" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Store Password</flux:label>
                        <flux:input type="password" wire:model="settings.sslcommerz_store_password" />
                    </flux:field>
                </div>
            </div>

            {{-- Apple Pay --}}
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
                <div class="flex items-center justify-between mb-4">
                    <flux:heading size="sm">Apple Pay</flux:heading>
                    <label class="flex items-center gap-2 text-sm text-zinc-600 cursor-pointer">
                        <input type="checkbox" wire:model="settings.applepay_enabled" class="rounded border-zinc-300 text-blue-600" />
                        Enable
                    </label>
                </div>
                <div class="space-y-4">
                    <flux:field>
                        <flux:label>Merchant ID</flux:label>
                        <flux:input wire:model="settings.applepay_merchant_id" placeholder="merchant.com.example" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Merchant Name</flux:label>
                        <flux:input wire:model="settings.applepay_merchant_name" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Domain</flux:label>
                        <flux:input wire:model="settings.applepay_domain" placeholder="example.com" />
                    </flux:field>
                </div>
            </div>

        </div>

        {{-- Currency tab --}}
        <div x-show="tab === 'currency'">
            <div class="max-w-2xl space-y-6">
                <flux:text class="text-zinc-500">
                    Set the currency used across the site for product pricing and payments.
                </flux:text>

                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-5 space-y-4">
                    <flux:heading size="sm">Currency</flux:heading>
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

                {{-- Preview --}}
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
                    <flux:heading size="sm" class="mb-2">Preview</flux:heading>
                    <div class="rounded-lg bg-zinc-50 dark:bg-zinc-800/50 px-4 py-6 text-center">
                        <span class="text-2xl font-bold text-zinc-800 dark:text-zinc-100" x-data
                            x-text="($wire.settings.currency_position || 'left') === 'right' ? '1,250.00 ' + ($wire.settings.currency_symbol || '৳') : ($wire.settings.currency_symbol || '৳') + '1,250.00'"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- SEO tab --}}
        <div x-show="tab === 'seo'">
            <div class="max-w-2xl space-y-6">
                <flux:text class="text-zinc-500">
                    Control search engine visibility and social sharing metadata for your site.
                </flux:text>

                {{-- Meta tags --}}
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-5 space-y-4">
                    <flux:heading size="sm">Meta Tags</flux:heading>
                    <flux:field>
                        <flux:label>Meta Title</flux:label>
                        <flux:input wire:model="settings.seo_meta_title"
                            placeholder="Title shown in search engine results" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Meta Description</flux:label>
                        <flux:textarea wire:model="settings.seo_meta_description" rows="3"
                            placeholder="Short summary shown in search engine results" />
                    </flux:field>
                </div>

                {{-- Open Graph --}}
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-5 space-y-4">
                    <flux:heading size="sm">Open Graph</flux:heading>
                    <flux:text class="text-xs text-zinc-500 -mt-2">
                        Used when your site is shared on social media (Facebook, WhatsApp, etc.).
                    </flux:text>
                    <flux:field>
                        <flux:label>OG Title</flux:label>
                        <flux:input wire:model="settings.seo_og_title"
                            placeholder="Title shown when shared on social media" />
                    </flux:field>
                    <flux:field>
                        <flux:label>OG Description</flux:label>
                        <flux:textarea wire:model="settings.seo_og_description" rows="3"
                            placeholder="Description shown when shared on social media" />
                    </flux:field>
                    <x-media-picker model="settings.seo_og_image" label="OG Image"
                        placeholder="Select OG image from library" />
                </div>
            </div>
        </div>

        {{-- Theme tab --}}
        <div x-show="tab === 'theme'">
            <div class="max-w-2xl space-y-6">
                <flux:text class="text-zinc-500">
                    Choose a light or dark theme for the admin panel and pick an accent color for buttons, links and menu highlights.
                </flux:text>

                {{-- Theme mode --}}
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-5 space-y-4">
                    <flux:heading size="sm">Theme Mode</flux:heading>
                    <div class="grid grid-cols-2 gap-3 max-w-sm">
                        <label class="cursor-pointer">
                            <input type="radio" name="theme_mode" value="light" wire:model="settings.theme_mode" class="sr-only peer">
                            <div class="flex flex-col items-center gap-2 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 peer-checked:border-blue-600 peer-checked:ring-2 peer-checked:ring-blue-600/20 transition-all">
                                <flux:icon.sun class="size-6 text-amber-500" />
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Light</span>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="theme_mode" value="dark" wire:model="settings.theme_mode" class="sr-only peer">
                            <div class="flex flex-col items-center gap-2 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 peer-checked:border-blue-600 peer-checked:ring-2 peer-checked:ring-blue-600/20 transition-all">
                                <flux:icon.moon class="size-6 text-indigo-400" />
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Dark</span>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Accent color --}}
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-5 space-y-4">
                    <flux:heading size="sm">Accent Color</flux:heading>
                    <flux:text class="text-xs text-zinc-500 -mt-2">
                        This becomes your custom theme's primary color.
                    </flux:text>
                    <div class="flex flex-wrap gap-2">
                        @foreach (['#1e7bc4', '#2563eb', '#7cc242', '#7c3aed', '#db2777', '#ea580c', '#dc2626', '#0d9488'] as $color)
                            <button type="button" wire:click="$set('settings.theme_accent', '{{ $color }}')"
                                class="size-8 rounded-full border-2 border-white dark:border-zinc-800 shadow-sm transition-transform hover:scale-110"
                                style="background-color: {{ $color }}"
                                :class="($wire.settings.theme_accent ?? '') === '{{ $color }}' ? 'ring-2 ring-offset-2 ring-zinc-400 dark:ring-offset-zinc-900' : ''"></button>
                        @endforeach
                    </div>
                    <flux:field>
                        <flux:label>Custom hex</flux:label>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg border border-zinc-300 dark:border-zinc-700 shrink-0"
                                 x-data
                                 :style="'background-color: ' + ($wire.settings.theme_accent || '#1e7bc4')"></div>
                            <flux:input wire:model="settings.theme_accent" placeholder="#1e7bc4" class="flex-1 font-mono" />
                        </div>
                    </flux:field>
                </div>

                {{-- Theme name --}}
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-5 space-y-4">
                    <flux:heading size="sm">Theme Name</flux:heading>
                    <flux:text class="text-xs text-zinc-500 -mt-2">
                        A friendly label for your custom theme.
                    </flux:text>
                    <flux:input wire:model="settings.theme_name" placeholder="e.g. Forest Green" />
                </div>
            </div>
        </div>

        {{-- Other tab --}}
        <div x-show="tab === 'colors'">
            <div class="max-w-2xl space-y-6">
                <flux:text class="text-zinc-500">
                    Brand colors and social profile links for your site.
                </flux:text>

                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-5 space-y-4">
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

                {{-- Social links --}}
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-5 space-y-4">
                    <flux:heading size="sm">Social Links</flux:heading>
                    <flux:text class="text-xs text-zinc-500 -mt-2">
                        Add links to your social media profiles. These appear across the site.
                    </flux:text>
                    @php
                        $socials = [
                            ['key' => 'facebook_url',    'label' => 'Facebook',     'color' => '#1877f2'],
                            ['key' => 'twitter_url',     'label' => 'Twitter / X',  'color' => '#000000'],
                            ['key' => 'instagram_url',   'label' => 'Instagram',    'color' => '#e4405f'],
                            ['key' => 'youtube_url',     'label' => 'YouTube',      'color' => '#ff0000'],
                            ['key' => 'linkedin_url',    'label' => 'LinkedIn',     'color' => '#0a66c2'],
                            ['key' => 'tiktok_url',      'label' => 'TikTok',       'color' => '#000000'],
                            ['key' => 'whatsapp_number', 'label' => 'WhatsApp',     'color' => '#25d366'],
                        ];
                    @endphp
                    @foreach ($socials as $social)
                        <flux:field>
                            <flux:label>
                                <span class="inline-flex items-center gap-2">
                                    <span class="inline-flex size-5 items-center justify-center rounded-full text-[10px] font-bold text-white"
                                        style="background-color: {{ $social['color'] }}">
                                        {{ strtoupper(substr($social['label'], 0, 1)) }}
                                    </span>
                                    {{ $social['label'] }}
                                </span>
                            </flux:label>
                            <flux:input wire:model="settings.{{ $social['key'] }}"
                                placeholder="{{ $social['key'] === 'whatsapp_number' ? '+8801XXXXXXXXX' : 'https://' }}" />
                        </flux:field>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Save --}}
        <div class="mt-6">
            <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled">
                Save Settings
            </flux:button>
        </div>

    </div>

    <livewire:admin.media-library.picker-modal />
</div>
