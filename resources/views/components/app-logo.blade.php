@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand {{ $attributes }}>
        <x-slot name="logo">
            <img src="/codeware_logo.png" alt="{{ config('app.name') }}" class="h-7 w-auto">
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand {{ $attributes }}>
        <x-slot name="logo">
            <img src="/codeware_logo.png" alt="{{ config('app.name') }}" class="h-7 w-auto">
        </x-slot>
    </flux:brand>
@endif
