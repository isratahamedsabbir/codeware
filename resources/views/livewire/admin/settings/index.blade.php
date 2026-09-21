<div>
    {{-- Alpine tab switcher: General | Currency | ... --}}
    <div x-data="{
        tab: new URLSearchParams(location.search).get('tab') || localStorage.getItem('admin-settings-tab') || 'general',
        init() {
            this.$watch('tab', (value) => localStorage.setItem('admin-settings-tab', value));
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
                        @if ($group === 'images')
                            {{-- Each image gets its own section card rather than sharing one
                                 "Images" card, so every upload slot reads as its own settings
                                 block (title, hint and file picker together). --}}
                            <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 items-start">
                                @foreach ($items as $setting)
                                    @continue (! in_array($setting->key, ['site_icon', 'site_icon_white', 'favicon', 'loader'], true))
                                    @php
                                        $imageMeta = match ($setting->key) {
                                            'favicon' => ['title' => 'Favicon', 'hint' => '32×32px, square', 'placeholder' => 'Choose a favicon from the library', 'mimes' => 'ico,png', 'maxSize' => 1],
                                            'loader' => ['title' => 'Loader', 'hint' => '200×200px, square', 'placeholder' => 'Choose a loading animation from the library', 'mimes' => 'gif,png,jpg', 'maxSize' => 2],
                                            'site_icon_white' => ['title' => 'White Icon', 'hint' => '512×512px, transparent', 'placeholder' => 'Choose a white icon from the library', 'mimes' => 'png,webp', 'maxSize' => 2],
                                            default => ['title' => 'Site Icon', 'hint' => '512×512px, transparent', 'placeholder' => 'Choose a site icon from the library', 'mimes' => 'png,webp', 'maxSize' => 2],
                                        };
                                    @endphp
                                    <x-admin-section-card header-border="border-zinc-100" icon="photo" :title="$imageMeta['title']"
                                        :description="$imageMeta['hint']">
                                        <x-media-picker model="settings.{{ $setting->key }}" :placeholder="$imageMeta['placeholder']" :mimes="$imageMeta['mimes']"
                                            :max-size-mb="$imageMeta['maxSize']" only-images dropzone />
                                    </x-admin-section-card>
                                @endforeach
                            </div>
                        @else
                            <div class="lg:col-span-2">
                                @include('partials.admin-settings-group-card', ['group' => $group, 'items' => $items])
                            </div>
                        @endif
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
                            <flux:label>Site Design<x-field-hint text="{{ __('The design shown at your site\'s homepage (:url).', ['url' => url('/')]) }}" /></flux:label>
                            <select wire:model="settings.site_theme"
                                class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700">
                                @foreach (\App\Support\Themes::all() as $slug => $label)
                                    <option value="{{ $slug }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </flux:field>

                        <div class="mt-5 pt-5 border-t border-zinc-100 dark:border-zinc-700">
                            <label class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300 cursor-pointer">
                                <input type="checkbox" wire:model="settings.chat_widget_enabled" class="rounded border-zinc-300 text-primary" />
                                Chat Box
                            </label>
                            <p class="text-xs text-zinc-400 mt-1">
                                Shows the live support chat bubble in the corner of every public page.
                            </p>
                        </div>
                    </x-admin-section-card>
                </div>

            </div>
        </div>

        {{-- Other tab --}}
        <div x-show="tab === 'other'">
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

                {{-- Watermark --}}
                <x-admin-section-card header-border="border-zinc-100" icon="photo" title="Watermark"
                    description="Stamps this image onto every file uploaded to the Media Library.">
                    <x-slot:actions>
                        <label class="flex items-center gap-2 text-sm text-zinc-600 cursor-pointer">
                            <input type="checkbox" wire:model="settings.watermark_enabled" class="rounded border-zinc-300 text-primary" />
                            Enable
                        </label>
                    </x-slot:actions>

                    <x-media-picker model="settings.watermark_image" label="Watermark Image" hint="PNG with transparency works best"
                        placeholder="Select watermark image from library" mimes="png,jpg,jpeg,webp" only-images dropzone />

                    <div class="grid grid-cols-1 gap-4">
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
                    </div>
                </x-admin-section-card>

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

                {{-- Shop toggle --}}
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

                {{-- Language switcher --}}
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

    <livewire:admin.media-library.picker-modal key="settings-picker-modal" />
</div>
