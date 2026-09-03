{{-- Shows Settings → General → Loader (a GIF) over the admin UI while something
     slow to render is in flight — a wire:navigate page transition (e.g. right
     after login while the dashboard is being fetched) or any Livewire AJAX
     request (table filters/pagination/saves). Falls back to the bundled
     default GIF if none has been uploaded yet. --}}
@php($loaderUrl = \App\Models\Setting::get('loader') ?: asset('default/loader.gif'))
<div wire:loading.flex
    class="fixed inset-0 z-[9999] hidden items-center justify-center bg-white/70 backdrop-blur-sm dark:bg-zinc-900/70">
    <img src="{{ $loaderUrl }}" alt="Loading" class="max-w-[150px] object-contain">     
</div> 

<div x-data="{ show: false }" x-init="
        window.addEventListener('livewire:navigating', () => show = true);
        window.addEventListener('livewire:navigated', () => show = false);
    " x-show="show" x-cloak
    class="fixed inset-0 z-[9999] flex items-center justify-center bg-white/70 backdrop-blur-sm dark:bg-zinc-900/70"> 
    <img src="{{ $loaderUrl }}" alt="Loading" class="max-w-[150px] object-contain"> 
</div>  
