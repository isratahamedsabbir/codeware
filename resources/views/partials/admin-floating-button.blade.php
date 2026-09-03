{{-- Floating action button — configured under Settings → Other → Floating Button.
     Action is one of: link (opens a configured URL), back (browser history), top (scroll to top). --}}
@php
    $fabEnabled = (bool) \App\Models\Setting::get('floating_button_enabled', false);
    $fabAction = \App\Models\Setting::get('floating_button_action', 'top');
    $fabLink = \App\Models\Setting::get('floating_button_link', '');
@endphp

@if ($fabEnabled)
    <div class="fixed bottom-6 right-6 z-40">
        @if ($fabAction === 'link' && $fabLink)
            <a href="{{ $fabLink }}" target="_blank" rel="noopener"
                class="flex size-12 items-center justify-center rounded-full bg-primary text-white shadow-lg transition-transform hover:scale-105"
                aria-label="Open link">
                <flux:icon.arrow-top-right-on-square class="size-5" />
            </a>
        @elseif ($fabAction === 'back')
            <button type="button" onclick="history.back()"
                class="flex size-12 items-center justify-center rounded-full bg-primary text-white shadow-lg transition-transform hover:scale-105 border-none cursor-pointer"
                aria-label="Go back">
                <flux:icon.arrow-left class="size-5" />
            </button>
        @elseif ($fabAction === 'top')
            <button type="button" onclick="window.scrollTo({ top: 0, behavior: 'smooth' })"
                class="flex size-12 items-center justify-center rounded-full bg-primary text-white shadow-lg transition-transform hover:scale-105 border-none cursor-pointer"
                aria-label="Go to top">
                <flux:icon.arrow-up class="size-5" />
            </button>
        @endif
    </div>
@endif
