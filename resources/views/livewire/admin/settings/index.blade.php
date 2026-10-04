<div>
    {{-- Alpine tab switcher: General | Currency | ... --}}
    <div x-data="{
        tab: new URLSearchParams(location.search).get('tab') || localStorage.getItem('admin-settings-tab') || 'general',
        init() {
            const validTabs = ['general', 'custom-code', 'constant', 'widgets'];
            // 'other' was this tab's old name — keep old links/bookmarks working.
            if (this.tab === 'other') this.tab = 'widgets';
            if (!validTabs.includes(this.tab)) this.tab = 'general';
            this.$watch('tab', (value) => {
                try { localStorage.setItem('admin-settings-tab', value) } catch (e) {}
                // Keep the address shareable: ?tab=… reopens the same tab.
                const url = new URL(location.href);
                url.searchParams.set('tab', value);
                history.replaceState(history.state, '', url);
            });
        }
    }">

        {{-- Tab nav — a card of icon tabs, each with a one-line summary. --}}
        @php
            $settingsTabs = [
                'general' => ['General', 'cog-6-tooth', 'Currency, VAT, site identity, images'],
                'custom-code' => ['Custom Code', 'code-bracket', 'Head & body scripts'],
                'constant' => ['Constant', 'variable', 'Site-wide key / value pairs'],
                'widgets' => ['Widgets', 'squares-2x2', 'Header widgets, toggles, admin tools'],
            ];
        @endphp
        <div role="tablist" aria-label="Settings sections"
            class="mb-6 flex gap-1 overflow-x-auto rounded-xl border border-zinc-200 bg-white p-1.5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            @foreach ($settingsTabs as $key => [$label, $icon, $summary])
                <button type="button" role="tab" @click="tab = '{{ $key }}'"
                    :aria-selected="tab === '{{ $key }}'"
                    :class="tab === '{{ $key }}'
                        ? 'bg-primary/10 text-primary ring-1 ring-primary/20'
                        : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100'"
                    class="group flex min-w-44 flex-1 items-center gap-3 rounded-lg! px-3.5 py-2.5 text-left transition-colors">
                    <span :class="tab === '{{ $key }}'
                            ? 'bg-primary text-white shadow-sm'
                            : 'bg-zinc-100 text-zinc-500 group-hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-400'"
                        class="flex size-9 shrink-0 items-center justify-center rounded-lg transition-colors">
                        <flux:icon :name="$icon" variant="mini" class="size-4.5" />
                    </span>
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-semibold">{{ $label }}</span>
                        <span class="block truncate text-[11px] font-normal text-zinc-400">{{ $summary }}</span>
                    </span>
                </button>
            @endforeach
        </div>

        {{-- General tab --}}
        <div x-show="tab === 'general'">
            <div class="max-w-[1600px] space-y-5">
                {{-- Environment --}}
                <x-admin-section-card header-border="border-zinc-100" icon="rocket-launch" title="Environment"
                    description="Which environment this install runs as.">
                    <flux:field class="max-w-xs">
                        <flux:label>Runtime environment</flux:label>
                        <select wire:model="appEnv" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700">
                            <option value="local">local</option>
                            <option value="staging">staging</option>
                            <option value="production">production</option>
                            <option value="testing">testing</option>
                            <option value="developer">developer</option>
                        </select>
                        <flux:error name="appEnv" />
                    </flux:field>

                    <flux:text class="text-xs text-amber-600 dark:text-amber-400">
                        A wrong value can take the site down until it is fixed. The rest of the app's .env-backed settings live on Developer Tools.
                    </flux:text>

                    <flux:button size="sm" variant="outline" wire:click="confirmSaveEnvironment" wire:loading.attr="disabled">
                        Save Environment
                    </flux:button>
                </x-admin-section-card>

                {{-- Currency and VAT / Tax sit side by side at the top of their own
                     columns so one never stretches the other into a gap; every field
                     inside them runs the card's full width for easy editing. General
                     and Images flow below Currency, with Localization, Pagination,
                     Newsletter and Backend below VAT; each column flows independently
                     (space-y-5). Currency has an info button that opens the live
                     preview in a modal — see settings-currency-preview below. --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-start">
                    <div class="space-y-5">
                        <x-admin-section-card header-border="border-zinc-100" icon="banknotes" title="Currency"
                            description="Set the currency used across the site for product pricing and payments.">
                            <x-slot:titleActions>
                                <button type="button" @click="$dispatch('open-modal', { name: 'settings-currency-preview' })" title="Live preview"
                                    class="flex size-5 items-center justify-center text-zinc-400 transition-colors hover:text-primary cursor-pointer">
                                    <flux:icon.information-circle class="size-4" />
                                </button>
                            </x-slot:titleActions>

                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-400">Currency</p>
                            <flux:field>
                                <flux:label>Currency Code</flux:label>
                                <flux:input wire:model="settings.currency_code" placeholder="BDT, USD, EUR" class="uppercase" maxlength="3" />
                                <flux:error name="settings.currency_code" />
                            </flux:field>
                            <flux:field>
                                <flux:label>Currency Symbol</flux:label>
                                <flux:input wire:model="settings.currency_symbol" placeholder="৳, $, €" maxlength="4" />
                                <flux:error name="settings.currency_symbol" />
                            </flux:field>

                            <p class="mt-5 mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-400">Formatting</p>
                            <flux:field>
                                <flux:label>Symbol Position</flux:label>
                                <div class="relative">
                                    <select wire:model="settings.currency_position"
                                        class="w-full appearance-none rounded-lg border border-zinc-300 bg-white pl-3 pr-9 py-2 text-sm text-zinc-700 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200">
                                        <option value="left">Before amount — ৳1,250.00</option>
                                        <option value="right">After amount — 1,250.00 ৳</option>
                                    </select>
                                    <flux:icon.chevron-down class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-zinc-400" />
                                </div>
                            </flux:field>
                            <flux:field>
                                <flux:label>Decimal Places</flux:label>
                                <flux:input type="number" wire:model="settings.decimal_places" min="0" max="4" />
                                <flux:error name="settings.decimal_places" />
                            </flux:field>
                        </x-admin-section-card>

                        @if (isset($groupedSettings['general']))
                            @include('partials._admin-settings-group-card', ['group' => 'general', 'items' => $groupedSettings['general']])
                        @endif

                        {{-- Image uploads sit right under General so the space they
                             would otherwise leave empty to the left of the Localization
                             / Pagination / Newsletter stack is put to use. --}}
                        @foreach ($groupedSettings as $group => $items)
                            @continue ($group !== 'images')
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 items-start">
                                @foreach ($items as $setting)
                                    @continue (! in_array($setting->key, ['site_icon', 'site_icon_white', 'favicon', 'loader', 'sidebar_logo'], true))
                                    @php
$imageMeta = match ($setting->key) {
                                            'favicon' => ['title' => 'Favicon', 'hint' => '32×32px, square', 'size' => '32 × 32', 'placeholder' => 'Choose a favicon from the library', 'mimes' => 'ico,png', 'maxSize' => 1],
                                            'loader' => ['title' => 'Loader', 'hint' => '200×200px, square', 'size' => '200 × 200', 'placeholder' => 'Choose a loading animation from the library', 'mimes' => 'gif,png,jpg', 'maxSize' => 2],
                                            'site_icon_white' => ['title' => 'White Icon', 'hint' => '512×512px, transparent', 'size' => '512 × 512', 'placeholder' => 'Choose a white icon from the library', 'mimes' => 'png,webp,avif', 'maxSize' => 2],
                                            'sidebar_logo' => ['title' => 'Sidebar Logo', 'hint' => 'Admin panel sidebar brand image', 'size' => 'logo', 'placeholder' => 'Choose a logo from the library', 'mimes' => 'png,webp,avif,jpg,svg', 'maxSize' => 2],
                                            default => ['title' => 'Site Icon', 'hint' => '512×512px, transparent', 'size' => '512 × 512', 'placeholder' => 'Choose a site icon from the library', 'mimes' => 'png,webp,avif', 'maxSize' => 2],
                                        };
                                    @endphp
                                    <x-admin-section-card header-border="border-zinc-100" icon="photo" :title="$imageMeta['title']"
                                        :description="$imageMeta['hint']">
                                        <x-media-picker model="settings.{{ $setting->key }}" label="" :size-hint="$imageMeta['size']"
                                            :placeholder="$imageMeta['placeholder']" :mimes="$imageMeta['mimes']"
                                            :max-size-mb="$imageMeta['maxSize']" only-images dropzone />
                                    </x-admin-section-card>
                                @endforeach
                            </div>
                        @endforeach
                    </div>

                    <div class="space-y-5">
                        {{-- VAT / Tax --}}
                        <x-admin-section-card header-border="border-zinc-100" icon="receipt-percent" title="VAT / Tax"
                            description="Optional tax added on top of every order's discounted subtotal.">
                            <flux:field>
                                <flux:label>Apply VAT</flux:label>
                                <div class="relative">
                                    <select wire:model="settings.vat_enabled"
                                        class="w-full appearance-none rounded-lg border border-zinc-300 bg-white pl-3 pr-9 py-2 text-sm text-zinc-700 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200">
                                        <option value="1">Yes — add VAT to orders</option>
                                        <option value="0">No — exclude VAT</option>
                                    </select>
                                    <flux:icon.chevron-down class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-zinc-400" />
                                </div>
                                <flux:error name="settings.vat_enabled" />
                            </flux:field>
                            <flux:field>
                                <flux:label>VAT Rate (%)</flux:label>
                                <flux:input type="number" wire:model="settings.vat_rate" min="0" max="100" step="0.01" />
                                <flux:error name="settings.vat_rate" />
                            </flux:field>
                            <flux:field>
                                <flux:label>VAT Label</flux:label>
                                <flux:input wire:model="settings.vat_label" placeholder="VAT, Tax, GST..." maxlength="50" />
                                <flux:error name="settings.vat_label" />
                            </flux:field>
                            <p class="text-xs text-zinc-400 dark:text-zinc-500">
                                When enabled, the rate is applied to each order's discounted subtotal and added on top of the total.
                            </p>
                        </x-admin-section-card>

                        @foreach (['localization', 'pagination', 'newsletter'] as $rightGroup)
                            @continue (! isset($groupedSettings[$rightGroup]))
                            @include('partials._admin-settings-group-card', ['group' => $rightGroup, 'items' => $groupedSettings[$rightGroup]])
                        @endforeach

                        {{-- Backend (admin panel colors) — shared the old Theme tab with the
                             frontend Site Design picker, which moved to the dedicated Theme
                             Settings screen. The colors themselves belong here in General. --}}
                        <x-admin-section-card header-border="border-zinc-100" icon="swatch" title="Backend"
                            description="Colors and typeface used across the admin panel, including buttons.">
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
                                {{-- The panel's typeface. Admin-only, and nothing to do with the
                                     storefront themes. System font and Segoe UI are faces the
                                     machine already has, so they cost nothing; Roboto is
                                     self-hosted and the only one that downloads a file. System
                                     font is the default. See App\Support\AdminFont. --}}
                                <flux:field class="sm:col-span-2">
                                    <flux:label>Backend Font</flux:label>
                                    <flux:select wire:model="settings.admin_font" class="w-full">
                                        @foreach ($adminFontOptions as $value => $label)
                                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    <flux:description>
                                        Typeface for the admin panel only. System font follows the
                                        device. To add a font, put its files in a new folder inside
                                        public/fonts/ (e.g. public/fonts/inter/) and it will appear here.
                                    </flux:description>
                                </flux:field>
                            </div>
                        </x-admin-section-card>
                    </div>

                    @foreach ($groupedSettings as $group => $items)
                        @continue (in_array($group, ['general', 'localization', 'pagination', 'newsletter', 'images'], true))
                        <div class="lg:col-span-2">
                            @include('partials._admin-settings-group-card', ['group' => $group, 'items' => $items])
                        </div>
                    @endforeach
                </div>
            </div>
        </div>


        {{-- Custom Code tab --}}
        <div x-show="tab === 'custom-code'">
            <div class="max-w-[1600px] space-y-5">
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
                    <x-slot:titleActions>
                        {{-- Points into the Developer Guide rather than opening a copy of
                             it. Everything this button used to explain - what a constant
                             is, how to read one, where they surface - is answered once
                             in the guide, so the modal was a second copy to keep true,
                             and the first one to go stale.

                             It was also already wrong when written: it claimed a file
                             constant "resolves to" its public URL, when the picker
                             stores the URL as the value outright and nothing resolves
                             it. And it never mentioned the key rules that decide whether
                             a save succeeds at all.

                             A plain anchor rather than wire:navigate: the guide is
                             another page entirely, and a full load is what actually
                             honours the fragment and lands on the heading. --}}
                        <a href="{{ route('admin.developer-guide') }}#constants"
                            title="How constants work — open the Developer Guide"
                            aria-label="How constants work — open the Developer Guide"
                            class="flex size-5 items-center justify-center text-zinc-400 transition-colors hover:text-primary cursor-pointer">
                            <flux:icon.information-circle class="size-4" />
                        </a>
                    </x-slot:titleActions>
                    <x-slot:actions>
                        <flux:button size="sm" variant="outline" icon="plus" wire:click="addConstant" x-on:click="open = true">Add field</flux:button>
                    </x-slot:actions>

                    <flux:error name="constants" />

                    <x-admin-constant-fields :items="$constants" :open-constants="$openConstants" model="constants" empty-hint="Add reusable key/value pairs for phone numbers, links, footer text and more." />
                </x-admin-section-card>
            </div>
        </div>

        {{-- Widgets tab — each widget is its own card, straight in the grid.

             A toggle whose feature is off (Settings → Features) drops out here
             rather than configuring something the panel no longer has. The
             header widget each one enables asks the same question, so the two
             never disagree — see Features::settingAvailable(). --}}
        @php
            $shopToggleAvailable = \App\Support\Features::settingAvailable('shop_toggle_enabled');
            $languageSwitcherAvailable = \App\Support\Features::settingAvailable('language_switcher_enabled');
            $productAdditionalDataAvailable = \App\Support\Features::settingAvailable('additional_data_products_enabled');
            $postAdditionalDataAvailable = \App\Support\Features::settingAvailable('additional_data_posts_enabled');
        @endphp
        <div x-show="tab === 'widgets'">
            <div class="max-w-[1600px]">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-start">
                {{-- Floating action button --}}
                <x-admin-section-card header-border="border-zinc-100" x-data icon="cursor-arrow-rays" title="Floating Button"
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
                <x-admin-section-card header-border="border-zinc-100" icon="ellipsis-horizontal" title="Table Actions"
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

                {{-- Watermark was here — moved to the Media Library, where a
                     button in the header opens a modal for configuring it. --}}

                {{-- Calculator widget --}}
                <x-admin-section-card header-border="border-zinc-100" icon="calculator" title="Calculator"
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
                <x-admin-section-card header-border="border-zinc-100" icon="document-text" title="Sticky Note"
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

                {{-- Notification bell --}}
                <x-admin-section-card header-border="border-zinc-100" icon="bell" title="Notifications"
                    description="Shows the notification bell in the admin header.">
                    <x-slot:actions>
                        <label class="flex items-center gap-2 text-sm text-zinc-600 cursor-pointer">
                            <input type="checkbox" wire:model="settings.notifications_enabled" class="rounded border-zinc-300 text-primary" />
                            Enable
                        </label>
                    </x-slot:actions>

                    <p class="text-xs text-zinc-400">
                        Turning this off only hides the bell. Notifications are still recorded, so turning it back on finds them all still there.
                    </p>
                </x-admin-section-card>

                {{-- Shop toggle --}}
                @if ($shopToggleAvailable)
                <x-admin-section-card header-border="border-zinc-100" icon="building-storefront" title="Shop Toggle"
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
                @endif

                {{-- Language switcher --}}
                @if ($languageSwitcherAvailable)
                <x-admin-section-card header-border="border-zinc-100" icon="language" title="Language Switcher"
                    description="Shows the language dropdown in the admin header.">
                    <x-slot:actions>
                        <label class="flex items-center gap-2 text-sm text-zinc-600 cursor-pointer">
                            <input type="checkbox" wire:model="settings.language_switcher_enabled" class="rounded border-zinc-300 text-primary" />
                            Enable
                        </label>
                    </x-slot:actions>

                    <p class="text-xs text-zinc-400">
                        Turning this off does not change the admin panel's language, only hides the switcher itself.
                    </p>
                </x-admin-section-card>
                @endif

                {{-- Additional Data sections — show/hide the rich-editor cards on the
                     admin Product and Blog Post forms (see Product and Posts forms). --}}
                @if ($productAdditionalDataAvailable)
                <x-admin-section-card header-border="border-zinc-100" icon="document-text" title="Product Additional Data"
                    description="Shows the Additional Data card on the product form.">
                    <x-slot:actions>
                        <label class="flex items-center gap-2 text-sm text-zinc-600 cursor-pointer">
                            <input type="checkbox" wire:model="settings.additional_data_products_enabled" class="rounded border-zinc-300 text-primary" />
                            Enable
                        </label>
                    </x-slot:actions>

                    <p class="text-xs text-zinc-400">
                        Rich editors for the product's description, short description and specification. Off hides the card without touching saved content.
                    </p>
                </x-admin-section-card>
                @endif

                @if ($postAdditionalDataAvailable)
                <x-admin-section-card header-border="border-zinc-100" icon="newspaper" title="Blog Post Additional Data"
                    description="Shows the Additional Data card on the blog post form.">
                    <x-slot:actions>
                        <label class="flex items-center gap-2 text-sm text-zinc-600 cursor-pointer">
                            <input type="checkbox" wire:model="settings.additional_data_posts_enabled" class="rounded border-zinc-300 text-primary" />
                            Enable
                        </label>
                    </x-slot:actions>

                    <p class="text-xs text-zinc-400">
                        A rich editor for the post's description. Off hides the card without touching saved content.
                    </p>
                </x-admin-section-card>
                @endif
                </div>
            </div>
        </div>

        <div class="mt-6">
            <flux:button variant="primary" size="sm" wire:click="save" wire:loading.attr="disabled">
                Save Settings
            </flux:button>
        </div>
    </div>

        {{-- Environment save confirmation --}}
    <flux:modal name="settings-env-confirm" class="md:w-96"
        x-on:open-modal.window="if ($event.detail.name === 'settings-env-confirm') $flux.modal('settings-env-confirm').show()"
        x-on:close-modal.window="if ($event.detail.name === 'settings-env-confirm') $flux.modal('settings-env-confirm').close()">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-amber-50 flex items-center justify-center shrink-0">
                    <flux:icon.exclamation-triangle class="w-5 h-5 text-amber-500" />
                </div>
                <flux:heading>{{ __('Save environment?') }}</flux:heading>
            </div>
            <flux:text class="text-sm text-zinc-500">
                {{ __('This overwrites the live .env file and clears the configuration cache. If this is wrong, the site may stop working until it is corrected.') }}
            </flux:text>
            <div class="flex gap-2 pt-1">
                <button wire:click="saveEnvironment" wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 px-4 h-8 text-sm font-medium rounded-lg text-white bg-amber-600 hover:bg-amber-700 transition-colors border-none cursor-pointer">
                    {{ __('Save anyway') }}
                </button>
                <flux:modal.close>
                    <flux:button size="sm" variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    {{-- Currency live preview — opened from the info button on the Currency
         card in the General tab. Shows sample pricing with the current
         currency + VAT settings. --}}
    <flux:modal name="settings-currency-preview" class="md:w-[420px]"
        x-on:open-modal.window="if ($event.detail.name === 'settings-currency-preview') $flux.modal('settings-currency-preview').show()"
        x-on:close-modal.window="if ($event.detail.name === 'settings-currency-preview') $flux.modal('settings-currency-preview').close()">
        <div class="rounded-xl bg-zinc-50 px-5 py-7 text-center dark:bg-zinc-800/50"
            x-data="{
                fmt(value) {
                    const dec = parseInt($wire.settings.decimal_places || '2', 10);
                    const num = Number(value || 0).toLocaleString('en-US', { minimumFractionDigits: dec, maximumFractionDigits: dec });
                    const sym = $wire.settings.currency_symbol || '৳';
                    return ($wire.settings.currency_position || 'left') === 'right' ? num + ' ' + sym : sym + num;
                }
            }">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400">Live Preview</p>
            <p class="mt-2 text-3xl font-bold tabular-nums text-zinc-900 dark:text-white" x-text="fmt(1250)"></p>
            <span class="mt-1 inline-block text-[10px] font-medium uppercase tracking-wider text-zinc-400"
                x-text="$wire.settings.currency_code || 'BDT'"></span>

            <div class="mx-auto my-6 h-px w-full bg-zinc-200 dark:bg-zinc-700"></div>

            <div class="space-y-1.5 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-zinc-500 dark:text-zinc-400">Subtotal</span>
                    <span class="tabular-nums text-zinc-800 dark:text-zinc-200" x-text="fmt(899.5)"></span>
                </div>
                <div class="flex items-center justify-between" x-show="$wire.settings.vat_enabled === '1' || $wire.settings.vat_enabled === true">
                    <span class="text-zinc-500 dark:text-zinc-400" x-text="$wire.settings.vat_label || 'VAT'"></span>
                    <span class="tabular-nums text-zinc-800 dark:text-zinc-200" x-text="fmt(Math.round((899.5 * (Number($wire.settings.vat_rate) || 0)) / 100 * 100) / 100)"></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-zinc-500 dark:text-zinc-400">Shipping</span>
                    <span class="text-emerald-600">Free</span>
                </div>
                <div class="flex items-center justify-between border-t border-zinc-200 pt-2 font-semibold dark:border-zinc-700">
                    <span class="text-zinc-800 dark:text-zinc-100">Total</span>
                    <span class="tabular-nums text-zinc-900 dark:text-white" x-text="fmt($wire.settings.vat_enabled === '1' || $wire.settings.vat_enabled === true ? 899.5 + Math.round((899.5 * (Number($wire.settings.vat_rate) || 0)) / 100 * 100) / 100 : 899.5)"></span>
                </div>
            </div>
        </div>
    </flux:modal>

    <livewire:admin.media-library.picker-modal key="settings-picker-modal" />
</div>
