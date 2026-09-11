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
            <button type="button" @click="tab = 'custom-code'"
                :class="tab==='custom-code'?'border-b-2 border-primary text-primary font-medium':'text-zinc-500 hover:text-zinc-700'"
                class="mx-4 rounded-none! py-3 text-sm -mb-px">Custom Code</button>
            <button type="button" @click="tab = 'tracking'"
                :class="tab==='tracking'?'border-b-2 border-primary text-primary font-medium':'text-zinc-500 hover:text-zinc-700'"
                class="mx-4 rounded-none! py-3 text-sm -mb-px">Tracking</button>
            <button type="button" @click="tab = 'constant'"
                :class="tab==='constant'?'border-b-2 border-primary text-primary font-medium':'text-zinc-500 hover:text-zinc-700'"
                class="mx-4 rounded-none! py-3 text-sm -mb-px">Constant</button>
        </div>

        {{-- General tab --}}
        <div x-show="tab === 'general'">
            <div class="max-w-[1600px]">
                {{-- General sits on the left spanning both rows; Localization and
                     Pagination stack to its right; Images and anything else fall
                     to a full-width row underneath. --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-start">
                    @foreach ($groupedSettings as $group => $items)
                        <div class="{{ match ($group) {
                            'general' => 'order-1 lg:row-span-2',
                            'localization' => 'order-2',
                            'pagination' => 'order-3',
                            'newsletter' => 'order-4',
                            'images' => 'order-5 lg:col-span-2',
                            default => 'order-6 lg:col-span-2',
                        } }}">
                            @php
                                $groupIcon = match ($group) {
                                    'general' => 'information-circle',
                                    'images' => 'photo',
                                    'pagination' => 'document-duplicate',
                                    'localization' => 'language',
                                    'newsletter' => 'megaphone',
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
                                            <flux:label>{{ $setting->key === 'app_locale' ? 'Language' : ucwords(str_replace('_', ' ', $setting->key)) }}</flux:label>
                                        @endunless
                                        @if ($setting->type === 'boolean')
                                            <div class="flex items-center gap-2">
                                                <input type="checkbox"
                                                    wire:model="settings.{{ $setting->key }}"
                                                    class="rounded border-zinc-300 text-primary" />
                                                <span class="text-sm text-zinc-600">Enable</span>
                                            </div>
                                            @if ($setting->key === 'notify_subscribers_on_new_product')
                                                <flux:text class="text-xs text-zinc-500">
                                                    {{ __('When enabled, everyone on the Subscribers list gets an email as soon as a new product is created.') }}
                                                </flux:text>
                                            @endif
                                        @elseif ($setting->type === 'color')
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-lg border border-zinc-300 shrink-0"
                                                     style="background-color: {{ $settings[$setting->key] ?? '#ffffff' }}"
                                                     x-data
                                                     :style="'background-color: ' + ($wire.settings['{{ $setting->key }}'] || '#ffffff')"></div>
                                                <flux:input wire:model="settings.{{ $setting->key }}" placeholder="#000000" class="flex-1 font-mono" />
                                            </div>
                                        @elseif ($setting->key === 'pagination_per_page')
                                            <flux:input type="number" min="1" max="100" wire:model="settings.{{ $setting->key }}" />
                                            <flux:text class="text-xs text-zinc-500">
                                                {{ __('Default number of items per page on the public site (products, posts, etc.). A request can still override this with its own ?per_page= value.') }}
                                            </flux:text>
                                        @elseif ($setting->key === 'app_locale')
                                            <select wire:model="settings.{{ $setting->key }}"
                                                class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700">
                                                @foreach (\App\Support\Locale::active() as $language)
                                                    <option value="{{ $language->code }}">
                                                        {{ $language->flag ? $language->flag.' ' : '' }}{{ $language->native_name ?: $language->name }} ({{ strtoupper($language->code) }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <flux:text class="text-xs text-zinc-500">
                                                {{ __('The admin panel language — same as the header language switcher, applies to every admin user.') }}
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
                                        @elseif ($setting->key === 'date_format')
                                            @php
                                                $dateFormatOptions = [
                                                    'd M Y, h:i A' => '08 Sep 2026, 08:59 AM',
                                                    'M d, Y g:i A' => 'Sep 08, 2026 8:59 AM',
                                                    'd/m/Y h:i A' => '08/09/2026 08:59 AM',
                                                    'm/d/Y h:i A' => '09/08/2026 08:59 AM',
                                                    'Y-m-d H:i' => '2026-09-08 08:59',
                                                ];
                                            @endphp
                                            <select wire:model="settings.{{ $setting->key }}"
                                                class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700">
                                                @foreach ($dateFormatOptions as $format => $example)
                                                    <option value="{{ $format }}">{{ $example }}</option>
                                                @endforeach
                                            </select>
                                            <flux:text class="text-xs text-zinc-500">
                                                {{ __('How dates are shown across the admin panel and in API responses (the "_display" fields alongside each date).') }}
                                            </flux:text>
                                        @elseif ($setting->key === 'site_icon' || $setting->key === 'site_icon_white' || $setting->key === 'favicon' || $setting->key === 'loader')
                                            <x-media-picker model="settings.{{ $setting->key }}"
                                                label="{{ match ($setting->key) { 'favicon' => 'Favicon', 'loader' => 'Loader', 'site_icon_white' => 'White Icon', default => 'Site Icon' } }}"
                                                hint="{{ match ($setting->key) { 'favicon' => '32×32px, square', 'loader' => '200×200px, square', 'site_icon_white' => '512×512px, transparent', default => '512×512px, transparent' } }}"
                                                placeholder="{{ match ($setting->key) { 'loader' => 'Choose a loading animation from the library', 'favicon' => 'Choose a favicon from the library', 'site_icon_white' => 'Choose a white icon from the library', default => 'Choose a site icon from the library' } }}"
                                                mimes="{{ match ($setting->key) { 'favicon' => 'ico,png', 'loader' => 'gif,png,jpg', default => 'png,webp' } }}"
                                                :max-size-mb="$setting->key === 'favicon' ? 1 : 2"
                                                only-images dropzone />
                                        @elseif ($setting->type === 'textarea')
                                            <flux:textarea wire:model="settings.{{ $setting->key }}" class="h-24" />
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
            <div class="max-w-[1600px] space-y-5">
                <x-admin-section-card header-border="border-zinc-100" icon="banknotes" title="Currency" class="max-w-2xl"
                    description="Set the currency used across the site for product pricing and payments.">
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
            <div class="max-w-[1600px] space-y-5">
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
                        <flux:button size="sm" variant="danger" wire:click="disableDebugMode" wire:loading.attr="disabled">
                            Turn Debug Mode Off
                        </flux:button>
                    @else
                        <flux:button size="sm" variant="outline" wire:click="confirmEnableDebugMode" wire:loading.attr="disabled">
                            Enable Debug Mode
                        </flux:button>
                    @endif
                </x-admin-section-card>

                @php $socialGroups = ['Google Login', 'Facebook Login']; @endphp

                @foreach ($this->envFields() as $groupLabel => $fields)
                    @continue(in_array($groupLabel, $socialGroups, true))
                    <x-admin-section-card header-border="border-zinc-100" icon="server" :title="__($groupLabel)" class="max-w-2xl">
                        @foreach ($fields as $key => $meta)
                            @include('livewire.admin.settings.partials.env-field', ['key' => $key, 'meta' => $meta])
                        @endforeach
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

                <flux:button size="sm" variant="primary" wire:click="confirmSaveEnv" wire:loading.attr="disabled">
                    {{ __('Save Environment Settings') }}
                </flux:button>
            </div>
        </div>

        {{-- Other tab --}}
        <div x-show="tab === 'other'">
            <div class="max-w-[1600px] space-y-5">
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
            </div>
        </div>

        {{-- Custom Code tab --}}
        <div x-show="tab === 'custom-code'">
            <div class="max-w-[1600px] space-y-5">
                <div class="max-w-2xl rounded-lg bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 text-sm dark:bg-amber-950 dark:border-amber-800 dark:text-amber-300">
                    <strong>{{ __('Careful') }}:</strong>
                    {{ __('This code runs as-is on every visitor\'s browser (e.g. analytics or pixel scripts). Only paste code from sources you trust.') }}
                </div>

                <x-admin-section-card header-border="border-zinc-100" icon="code-bracket" title="Head Code" class="max-w-2xl"
                    description="Injected into <head>, before it closes — meta tags, verification tags, analytics.">
                    <flux:field>
                        <flux:textarea wire:model="settings.custom_head_code" class="h-40 font-mono text-xs" placeholder="<script>...</script>" />
                    </flux:field>
                </x-admin-section-card>

                <x-admin-section-card header-border="border-zinc-100" icon="code-bracket" title="Body Code" class="max-w-2xl"
                    description="Injected just before </body> closes — chat widgets, tracking pixels, deferred scripts.">
                    <flux:field>
                        <flux:textarea wire:model="settings.custom_body_code" class="h-40 font-mono text-xs" placeholder="<script>...</script>" />
                    </flux:field>
                </x-admin-section-card>
            </div>
        </div>

        {{-- Tracking tab --}}
        <div x-show="tab === 'tracking'">
            <div class="max-w-[1600px]">
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
            </div>
        </div>

        {{-- Constant tab --}}
        <div x-show="tab === 'constant'">
            <div class="max-w-[1600px] space-y-5">
                <x-admin-section-card header-border="border-zinc-100" icon="variable" title="Constant"
                    description="Freeform key/value pairs, available site-wide — not tied to any page or CMS section.">
                    <x-slot:actions>
                        <flux:button size="sm" variant="outline" icon="plus" wire:click="addConstant">Add field</flux:button>
                    </x-slot:actions>

                    <flux:error name="constants" />

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @forelse ($constants as $i => $pair)
                        <div wire:key="settings-constant-row-{{ $i }}" class="group relative rounded-[5px] border border-zinc-200 bg-zinc-50/60 overflow-hidden transition-colors hover:border-zinc-300">
                            <div class="flex items-center justify-between gap-3 px-4 py-2.5 border-b border-zinc-200 bg-white">
                                <div class="flex items-center gap-2">
                                    <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-zinc-300 text-[11px] font-semibold text-zinc-500">
                                        {{ $i + 1 }}
                                    </div>
                                    <flux:heading size="sm">Field</flux:heading>
                                </div>

                                <button type="button" wire:click="removeConstant({{ $i }})"
                                    class="shrink-0 rounded-lg p-1.5 text-zinc-400 transition-colors hover:bg-rose-50 hover:text-rose-500 cursor-pointer" aria-label="Remove field">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <div class="p-4 space-y-3">
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
                        <div class="col-span-full rounded-[5px] border border-dashed border-zinc-200 py-10 text-center">
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
