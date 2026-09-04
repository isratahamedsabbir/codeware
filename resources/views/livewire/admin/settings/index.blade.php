<div>
    {{-- Alpine tab switcher: General | Currency | ... --}}
    <div x-data="{
        tab: localStorage.getItem('admin-settings-tab') || 'general',
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
            <button type="button" @click="tab = 'env'"
                :class="tab==='env'?'border-b-2 border-primary text-primary font-medium':'text-zinc-500 hover:text-zinc-700'"
                class="mx-4 rounded-none! py-3 text-sm -mb-px">Env</button>
            <button type="button" @click="tab = 'other'"
                :class="tab==='other'?'border-b-2 border-primary text-primary font-medium':'text-zinc-500 hover:text-zinc-700'"
                class="mx-4 rounded-none! py-3 text-sm -mb-px">Other</button>
            <button type="button" @click="tab = 'constant'"
                :class="tab==='constant'?'border-b-2 border-primary text-primary font-medium':'text-zinc-500 hover:text-zinc-700'"
                class="mx-4 rounded-none! py-3 text-sm -mb-px">Constant</button>
        </div>

        {{-- General tab --}}
        <div x-show="tab === 'general'">
            <div class="max-w-[1600px]">
                {{-- General renders first, Images stacks directly beneath it full-width,
                     then every other group stacks beneath those, in $groupOrder's order. --}}
                <div class="flex gap-5 flex-wrap items-start">
                    @foreach ($groupedSettings as $group => $items)
                        <div class="{{ match ($group) {
                            'general' => 'w-full order-1',
                            'images' => 'w-full order-2',
                            default => 'w-full order-3',
                        } }}">
                            @php
                                $groupIcon = match ($group) {
                                    'general' => 'information-circle',
                                    'images' => 'photo',
                                    'pagination' => 'document-duplicate',
                                    'localization' => 'language',
                                    default => 'squares-2x2',
                                };
                            @endphp
                            <x-admin-section-card header-border="border-zinc-100" :icon="$groupIcon" :title="ucfirst($group ?? 'General')">
                                <div class="{{ $group === 'images' ? 'grid grid-cols-2 sm:grid-cols-4 gap-4' : 'space-y-4' }}">
                                @foreach ($items as $setting)
                                    <flux:field>
                                        @php
                                            $isMediaPicker = in_array($setting->key, ['site_icon', 'site_icon_white', 'favicon', 'loader'], true);
                                        @endphp
                                        @unless ($isMediaPicker)
                                            <flux:label>{{ ucwords(str_replace('_', ' ', $setting->key)) }}</flux:label>
                                        @endunless
                                        @if ($setting->type === 'boolean')
                                            <div class="flex items-center gap-2">
                                                <input type="checkbox"
                                                    wire:model="settings.{{ $setting->key }}"
                                                    class="rounded border-zinc-300 text-primary" />
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
                                        @elseif ($setting->key === 'app_locale')
                                            <select wire:model="settings.{{ $setting->key }}"
                                                class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700">
                                                @foreach (\App\Support\Locale::active() as $language)
                                                    <option value="{{ $language->code }}">{{ $language->native_name ?: $language->name }}</option>
                                                @endforeach
                                            </select>
                                            <flux:text class="text-xs text-zinc-500">
                                                {{ __('The default language the site renders in. Manage languages under the Localization menu.') }}
                                            </flux:text>
                                        @elseif ($setting->key === 'pagination_per_page')
                                            <flux:input type="number" min="1" max="100" wire:model="settings.{{ $setting->key }}" />
                                            <flux:text class="text-xs text-zinc-500">
                                                {{ __('Default number of items per page on the public site (products, posts, etc.). A request can still override this with its own ?per_page= value.') }}
                                            </flux:text>
                                        @elseif ($setting->key === 'timezone')
                                            <select wire:model="settings.{{ $setting->key }}"
                                                class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700">
                                                @foreach (\App\Support\Timezones::grouped() as $region => $zones)
                                                    <optgroup label="{{ $region }}">
                                                        @foreach ($zones as $zone)
                                                            <option value="{{ $zone }}">{{ $zone }}</option>
                                                        @endforeach
                                                    </optgroup>
                                                @endforeach
                                            </select>
                                            <flux:text class="text-xs text-zinc-500">
                                                {{ __('Dates are stored in UTC and shown to users in this timezone.') }}
                                            </flux:text>
                                        @elseif ($setting->key === 'site_icon' || $setting->key === 'site_icon_white' || $setting->key === 'favicon' || $setting->key === 'loader')
                                            <x-media-picker model="settings.{{ $setting->key }}"
                                                label="{{ match ($setting->key) { 'favicon' => 'Favicon', 'loader' => 'Loader (GIF)', 'site_icon_white' => 'White Icon', default => 'Site Icon' } }}"
                                                placeholder="{{ match ($setting->key) { 'loader' => 'Choose a loading animation (GIF) from the library', 'favicon' => 'Choose a favicon from the library', 'site_icon_white' => 'Choose a white icon from the library', default => 'Choose a site icon from the library' } }}"
                                                dropzone />
                                        @elseif ($setting->type === 'textarea')
                                            <flux:textarea wire:model="settings.{{ $setting->key }}" class="h-48" />
                                        @else
                                            <flux:input wire:model="settings.{{ $setting->key }}" />
                                        @endif
                                    </flux:field>
                                @endforeach
                                </div>
                            </x-admin-section-card>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>


        {{-- Currency tab --}}
        <div x-show="tab === 'currency'">
            <div class="max-w-[1600px] space-y-6">
                <flux:text class="text-zinc-500">
                    Set the currency used across the site for product pricing and payments.
                </flux:text>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

                <x-admin-section-card header-border="border-zinc-100" icon="banknotes" title="Currency">
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
                </x-admin-section-card>

                <x-admin-section-card header-border="border-zinc-100" icon="eye" title="Preview" icon-color="bg-blue-500/10 text-blue-600">
                    <div class="rounded-lg bg-zinc-50 dark:bg-zinc-800/50 px-4 py-6 text-center">
                        <span class="text-2xl font-bold text-zinc-800 dark:text-zinc-100" x-data
                            x-text="($wire.settings.currency_position || 'left') === 'right' ? '1,250.00 ' + ($wire.settings.currency_symbol || '৳') : ($wire.settings.currency_symbol || '৳') + '1,250.00'"></span>
                    </div>
                </x-admin-section-card>

                </div>
            </div>
        </div>

        {{-- Theme tab --}}
        <div x-show="tab === 'theme'">
            <div class="max-w-[1600px] space-y-6">

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

                <x-admin-section-card header-border="border-zinc-100" icon="swatch" title="Backend"
                    description="Colors used across the admin panel, including buttons.">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-2xl">
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

            </div>
        </div>

        {{-- Env tab --}}
        <div x-show="tab === 'env'">
            <div class="max-w-[1600px] space-y-6">
                <div class="max-w-2xl rounded-lg bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 text-sm dark:bg-amber-950 dark:border-amber-800 dark:text-amber-300">
                    <strong>{{ __('Careful') }}:</strong>
                    {{ __('These edit the live .env file this server runs on. A wrong value can take the site down until it is fixed. A backup of the current file is saved automatically before every change. Mail credentials live on the Email Templates page instead.') }}
                </div>

                {{-- Maintenance mode --}}
                <x-admin-section-card header-border="border-zinc-100" icon="wrench" title="Maintenance Mode"
                    icon-color="{{ $maintenanceMode ? 'bg-red-500/10 text-red-600' : 'bg-primary/10 text-primary' }}"
                    description="Takes the public site offline for every visitor. The admin panel and login stay reachable either way."
                    class="max-w-2xl {{ $maintenanceMode ? 'border-red-300! dark:border-red-800!' : '' }}">
                    <x-slot:actions>
                        @if ($maintenanceMode)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 dark:bg-red-950 px-3 py-1 text-xs font-semibold text-red-700 dark:text-red-300 ring-1 ring-red-600/20">
                                <span class="size-1.5 rounded-full bg-red-500 animate-pulse"></span>
                                Site is offline
                            </span>
                        @endif
                    </x-slot:actions>

                    @if ($maintenanceMode)
                        <flux:button variant="danger" wire:click="disableMaintenanceMode" wire:loading.attr="disabled">
                            Bring Site Back Online
                        </flux:button>
                    @else
                        <flux:button variant="outline" wire:click="confirmEnableMaintenanceMode" wire:loading.attr="disabled">
                            Enable Maintenance Mode
                        </flux:button>
                    @endif
                </x-admin-section-card>

                {{-- Debug mode --}}
                <x-admin-section-card header-border="border-zinc-100" icon="bug-ant" title="Debug Mode"
                    icon-color="{{ $debugMode ? 'bg-amber-500/10 text-amber-600' : 'bg-primary/10 text-primary' }}"
                    description="Shows full error details and stack traces to visitors. Leave this off in production."
                    class="max-w-2xl {{ $debugMode ? 'border-amber-300! dark:border-amber-800!' : '' }}">
                    <x-slot:actions>
                        @if ($debugMode)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 dark:bg-amber-950 px-3 py-1 text-xs font-semibold text-amber-700 dark:text-amber-300 ring-1 ring-amber-600/20">
                                <span class="size-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                Debug on
                            </span>
                        @endif
                    </x-slot:actions>

                    @if ($debugMode)
                        <flux:button variant="danger" wire:click="disableDebugMode" wire:loading.attr="disabled">
                            Turn Debug Mode Off
                        </flux:button>
                    @else
                        <flux:button variant="outline" wire:click="confirmEnableDebugMode" wire:loading.attr="disabled">
                            Enable Debug Mode
                        </flux:button>
                    @endif
                </x-admin-section-card>

                @foreach ($this->envFields() as $groupLabel => $fields)
                    <x-admin-section-card header-border="border-zinc-100" icon="server" :title="__($groupLabel)" class="max-w-2xl">
                        @foreach ($fields as $key => $meta)
                            <flux:field>
                                <flux:label>{{ __($meta['label']) }}</flux:label>
                                @if ($meta['type'] === 'boolean')
                                    <select wire:model="env.{{ $key }}"
                                        class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700">
                                        <option value="true">{{ __('True') }}</option>
                                        <option value="false">{{ __('False') }}</option>
                                    </select>
                                @elseif ($meta['type'] === 'select')
                                    <select wire:model="env.{{ $key }}"
                                        class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700">
                                        @foreach ($meta['options'] as $option)
                                            <option value="{{ $option }}">{{ $option }}</option>
                                        @endforeach
                                    </select>
                                @elseif ($meta['type'] === 'password')
                                    <flux:input type="password" wire:model="env.{{ $key }}" />
                                @else
                                    <flux:input wire:model="env.{{ $key }}" />
                                @endif
                                <flux:error name="env.{{ $key }}" />
                            </flux:field>
                        @endforeach
                    </x-admin-section-card>
                @endforeach

                <flux:button variant="primary" wire:click="confirmSaveEnv" wire:loading.attr="disabled">
                    {{ __('Save Environment Settings') }}
                </flux:button>
            </div>
        </div>

        {{-- Other tab --}}
        <div x-show="tab === 'other'">
            <div class="max-w-[1600px] space-y-6">
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
            </div>
        </div>

        {{-- Constant tab --}}
        <div x-show="tab === 'constant'">
            <div class="max-w-[1600px] space-y-6">
                <x-admin-section-card header-border="border-zinc-100" icon="variable" title="Constant"
                    description="Freeform key/value pairs, available site-wide — not tied to any page or CMS section.">
                    <x-slot:actions>
                        <flux:button size="xs" variant="outline" icon="plus" wire:click="addConstant">Add field</flux:button>
                    </x-slot:actions>

                    <flux:error name="constants" />

                    @forelse ($constants as $i => $pair)
                        <div wire:key="settings-constant-row-{{ $i }}" class="group relative rounded-xl border border-zinc-200 bg-zinc-50/60 p-4 transition-colors hover:border-zinc-300">
                            <div class="flex items-start gap-3">
                                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-zinc-900 text-[11px] font-semibold text-white">
                                    {{ $i + 1 }}
                                </div>

                                <div class="flex-1 min-w-0 space-y-3">
                                    <flux:field>
                                        <flux:label>Value type</flux:label>
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
                                            <flux:textarea wire:model="constants.{{ $i }}.value" class="h-48" placeholder="e.g. support@example.com" />
                                            <flux:error name="constants.{{ $i }}.value" />
                                        </flux:field>
                                    @endif
                                </div>

                                <button type="button" wire:click="removeConstant({{ $i }})"
                                    class="shrink-0 rounded-lg p-1.5 text-zinc-400 transition-colors hover:bg-rose-50 hover:text-rose-500 cursor-pointer" aria-label="Remove field">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-zinc-200 py-10 text-center">
                            <svg class="mx-auto mb-2 h-8 w-8 text-zinc-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7" />
                            </svg>
                            <p class="text-sm text-zinc-400">No constants yet.</p>
                        </div>
                    @endforelse
                </x-admin-section-card>
            </div>
        </div>

        {{-- Save --}}
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
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg text-white bg-amber-600 hover:bg-amber-700 transition-colors border-none cursor-pointer">
                    {{ __('Save anyway') }}
                </button>
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
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
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 transition-colors border-none cursor-pointer">
                    {{ __('Take site offline') }}
                </button>
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
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
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg text-white bg-amber-600 hover:bg-amber-700 transition-colors border-none cursor-pointer">
                    {{ __('Enable debug mode') }}
                </button>
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    <livewire:admin.media-library.picker-modal key="settings-picker-modal" />
</div>
