@props(['code', 'class' => 'space-y-3'])
<div x-show="locale === '{{ $code }}'" class="{{ $class }}">
    {{ $slot }}
</div>
