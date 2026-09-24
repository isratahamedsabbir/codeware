{{--
    A selectable radio card (shipping method, payment method): icon, label
    (default slot), optional `aside` slot on the right (price / badge) and a
    check mark once chosen. The radio itself is visually hidden; the whole card
    is the click target. `live` makes the choice sync to Livewire immediately.
--}}
@props(['name', 'value', 'icon', 'live' => false])

<label class="group relative flex cursor-pointer items-center gap-3 rounded-card border border-zinc-300 bg-white px-4 py-3.5 text-sm font-semibold text-zinc-700 shadow-sm transition hover:border-zinc-400 has-[:checked]:border-brand has-[:checked]:bg-brand/5 has-[:checked]:ring-1 has-[:checked]:ring-brand/30 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand/40">
    <input type="radio" name="{{ $name }}" value="{{ $value }}" class="sr-only"
        @if ($live) wire:model.live="{{ $name }}" @else wire:model="{{ $name }}" @endif>

    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-zinc-100 text-zinc-500 transition group-has-[:checked]:bg-brand/10 group-has-[:checked]:text-brand">
        <x-storefront.icon :name="$icon" stroke="1.8" class="h-5 w-5" />
    </span>

    <span class="min-w-0 group-has-[:checked]:text-sf-heading">{{ $slot }}</span>

    <span class="ml-auto flex shrink-0 items-center gap-2.5">
        {{ $aside ?? '' }}
        <x-storefront.icon name="check-circle" class="h-5 w-5 text-brand opacity-0 transition group-has-[:checked]:opacity-100" />
    </span>
</label>
