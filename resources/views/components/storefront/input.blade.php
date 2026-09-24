{{--
    A labelled storefront form field with a leading icon and its validation
    error — bound with wire:model to `name` (also the id / error key).
    `textarea` switches to a multi-line field with `rows`. Any other
    attributes (placeholder, autocomplete, type…) go onto the control.
--}}
@props(['name', 'label', 'icon' => null, 'textarea' => false, 'rows' => 3, 'optional' => false])

@php
    // Pages always share $errors; this keeps the field safe when rendered alone.
    $errors ??= new \Illuminate\Support\ViewErrorBag;
    $invalid = $errors->has($name);
    $classes = implode(' ', [
        'w-full rounded-xl border bg-white py-2.5 pr-4 text-sm text-sf-text shadow-sm outline-none transition placeholder:text-zinc-400 focus:ring-2',
        $icon ? 'pl-10' : 'pl-4',
        $invalid ? 'border-red-300 focus:border-red-400 focus:ring-red-100' : 'border-zinc-300 focus:border-brand focus:ring-brand/20',
    ]);
@endphp

<div>
    <label for="{{ $name }}" class="mb-1.5 block text-sm font-semibold text-sf-text">
        {{ $label }}
        @if ($optional)
            <span class="font-normal text-zinc-400">({{ __('optional') }})</span>
        @endif
    </label>
    <div class="relative">
        @if ($icon)
            <span @class(['pointer-events-none absolute left-3.5 text-zinc-400', 'top-3' => $textarea, 'top-1/2 -translate-y-1/2' => ! $textarea])>
                <x-storefront.icon :name="$icon" stroke="1.8" class="h-4.5 w-4.5" />
            </span>
        @endif
        @if ($textarea)
            <textarea id="{{ $name }}" wire:model="{{ $name }}" rows="{{ $rows }}" {{ $attributes->merge(['class' => $classes]) }}></textarea>
        @else
            <input id="{{ $name }}" wire:model="{{ $name }}" {{ $attributes->merge(['type' => 'text', 'class' => $classes]) }}>
        @endif
    </div>
    @error($name) <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
</div>
