{{-- $path is the full wire:model path when the field is nested per locale (settings.seo_meta_title.en); $field is the flat key it is built from.
     $locale, when given, badges the label with the locale code so an admin can tell at a
     glance which language's copy this input writes — same convention as every other
     per-locale field in the admin. --}}
@php
    $inputId = $path ?? 'settings.'.$field;
@endphp
<div class="flex items-center justify-between">
    <flux:label :badge="$locale ?? null">{{ $label }}</flux:label>
    <span class="text-xs tabular-nums" x-data
        x-text="(($wire.{{ $inputId }} || '').length) + ' / {{ $max }}'"
        :class="(($wire.{{ $inputId }} || '').length) > {{ $max }} ? 'text-red-500' : 'text-zinc-400'"></span>
</div>
