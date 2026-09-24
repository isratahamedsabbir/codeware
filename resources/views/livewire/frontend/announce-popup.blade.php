<div
    x-cloak
    x-show="open"
    x-data="{
        open: ! localStorage.getItem('codeware_popup_dismissed'),
        dismiss() {
            localStorage.setItem('codeware_popup_dismissed', '1');
            this.open = false;
        },
    }"
    @keydown.escape.window="dismiss"
    role="dialog"
    aria-modal="true"
    class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto bg-black/60 px-4 py-8 backdrop-blur-sm"
>
    <div class="absolute inset-0" @click="dismiss"></div>

    <div @click.stop class="relative w-full max-w-lg overflow-hidden rounded-card bg-brand shadow-2xl">
        @if ($image)
            <img src="{{ $image }}" alt="{{ $title }}" class="absolute inset-0 h-full w-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/55 to-black/25"></div>
        @else
            <div class="absolute inset-0 bg-gradient-to-b from-brand to-emerald-950"></div>
        @endif

        <button type="button" @click="dismiss" aria-label="{{ __('Close') }}"
            class="absolute right-3 top-3 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-white/15 text-white backdrop-blur transition hover:bg-white/30">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>

        <div class="relative flex flex-col items-center px-6 py-12 text-center sm:px-10">
            <h2 class="text-2xl font-extrabold tracking-tight text-white sm:text-3xl">{{ $title }}</h2>

            @if ($description)
                <p class="mt-3 text-sm leading-relaxed text-white/85 sm:text-base">{{ $description }}</p>
            @endif

            @if ($buttonLabel && $buttonUrl)
                <a href="{{ $buttonUrl }}"
                    class="mt-7 inline-flex items-center gap-2 rounded-full bg-white px-7 py-3 text-sm font-bold text-brand shadow-lg transition hover:opacity-90">
                    {{ $buttonLabel }}
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </a>
            @endif

            <button type="button" @click="dismiss"
                class="mt-4 text-xs font-semibold text-white/60 underline-offset-4 transition hover:text-white hover:underline">
                {{ __('Maybe later') }}
            </button>
        </div>
    </div>
</div>