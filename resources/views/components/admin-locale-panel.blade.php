@props(['code', 'class' => 'space-y-4'])
<div x-show="locale === '{{ $code }}'" class="{{ $class }}">
    {{ $slot }}
</div>
