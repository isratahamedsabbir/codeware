@php
    $quickMenuItems = \App\Models\AdminMenuItem::shortMenuCached();
@endphp

@if ($quickMenuItems->isNotEmpty())
    <flux:dropdown position="bottom" align="end">
        <flux:button variant="subtle" square class="!p-1.5" aria-label="{{ __('Short menu') }}">
            <flux:icon.bolt class="size-5 text-zinc-500" />
        </flux:button>

        <flux:menu>
            <flux:menu.heading>{{ __('Short Menu') }}</flux:menu.heading>
            @foreach ($quickMenuItems as $item)
                @php
                    $href = $item->route_name && \Illuminate\Support\Facades\Route::has($item->route_name)
                        ? route($item->route_name)
                        : ($item->url ?? '#');
                    $iconName = \App\Models\AdminMenuItem::iconExists($item->icon) ? $item->icon : 'link';
                @endphp
                <flux:menu.item :href="$href" :icon="$iconName" wire:navigate>
                    {{ __($item->label) }}
                </flux:menu.item>
            @endforeach
        </flux:menu>
    </flux:dropdown>
@endif
