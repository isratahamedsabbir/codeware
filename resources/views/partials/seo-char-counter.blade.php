{{-- $path is the full wire:model path when the field is nested per locale (settings.seo_meta_title.en); $field is the flat key it is built from. --}}
@php
    $inputId = $path ?? 'settings.'.$field;
@endphp
<div class="flex items-center justify-between">
    <flux:label>{{ $label }}</flux:label>
    <span class="text-xs tabular-nums" x-data
        x-text="(($wire.{{ $inputId }} || '').length) + ' / {{ $max }}'"
        :class="(($wire.{{ $inputId }} || '').length) > {{ $max }} ? 'text-red-500' : 'text-zinc-400'"></span>
</div>
