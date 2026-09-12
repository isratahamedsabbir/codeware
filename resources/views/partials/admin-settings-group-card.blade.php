{{-- One "group" card in the Settings General tab (General, Localization,
     Pagination, Newsletter, Images, ...). Pulled into its own partial so the
     General tab's layout can place cards explicitly (left column, right
     stack, full-width row) without duplicating this per-field rendering.
     Included with ['group' => ..., 'items' => ...]; inherits $settings from
     the parent view for the color-swatch preview. --}}
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
