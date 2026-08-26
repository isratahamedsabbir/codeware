@props([
    'sidebar' => false,
])

@php
    $siteIcon = \App\Models\Setting::get('site_icon');
@endphp

@if($sidebar)
    <flux:sidebar.brand {{ $attributes }}>
        <x-slot name="logo">
            <img src="{{ $siteIcon ?: '/default/logo.png' }}" alt="{{ config('app.name') }}" class="h-7 w-auto">
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand {{ $attributes }}>
        <x-slot name="logo">
            <img src="{{ $siteIcon ?: '/default/logo.png' }}" alt="{{ config('app.name') }}" class="h-7 w-auto">
        </x-slot>
    </flux:brand>
@endif
