@php
    $clock = \App\Support\Plugins::settings('clock');
@endphp
@if ($clock['enabled'])
    <div class="relative" x-data="{
            open: false,
            now: new Date(),
            h24: {{ $clock['format'] === '24' ? 'true' : 'false' }},
            seconds: {{ $clock['seconds'] ? 'true' : 'false' }},
            init() { setInterval(() => this.now = new Date(), 1000); },
            get time() {
                return this.now.toLocaleTimeString([], {
                    hour: '2-digit', minute: '2-digit',
                    second: this.seconds ? '2-digit' : undefined, hour12: !this.h24,
                });
            },
            get date() {
                return this.now.toLocaleDateString([], { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
            },
        }" @click.outside="open = false" @keydown.escape.window="open = false">
        <button type="button" @click="open = !open"
            title="{{ __('Clock') }}" aria-label="{{ __('Clock') }}" :aria-expanded="open"
            class="inline-flex size-8 items-center justify-center rounded-lg text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-zinc-800 focus:outline-none focus:ring-2 focus:ring-primary/30 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100">
            <flux:icon.clock class="size-5" />
        </button>

        <div x-show="open" x-cloak x-transition.origin.top.right
            class="absolute right-0 top-full z-50 mt-2 w-64 rounded-xl border border-zinc-200 bg-white p-4 text-center shadow-lg dark:border-zinc-700 dark:bg-zinc-800">
            @if ($clock['style'] === 'analog')
                <svg viewBox="0 0 200 200" class="mx-auto mb-3 size-44 text-zinc-800 dark:text-zinc-100" role="img" aria-label="{{ __('Analog clock') }}">
                    <circle cx="100" cy="100" r="96" fill="none" stroke="currentColor" stroke-width="3" />
                    @foreach (range(0, 59) as $tick)
                        @php $major = $tick % 5 === 0; @endphp
                        <line x1="100" y1="{{ $major ? 10 : 8 }}" x2="100" y2="{{ $major ? 22 : 14 }}"
                            transform="rotate({{ $tick * 6 }} 100 100)" stroke="currentColor"
                            stroke-width="{{ $major ? 3 : 1 }}" stroke-linecap="round" opacity="{{ $major ? 1 : 0.4 }}" />
                    @endforeach
                    <line x1="100" y1="100" x2="100" y2="55" stroke="currentColor" stroke-width="6" stroke-linecap="round"
                        :transform="`rotate(${((now.getHours() % 12) + now.getMinutes() / 60) * 30} 100 100)`" />
                    <line x1="100" y1="100" x2="100" y2="32" stroke="currentColor" stroke-width="4" stroke-linecap="round"
                        :transform="`rotate(${(now.getMinutes() + now.getSeconds() / 60) * 6} 100 100)`" />
                    <template x-if="seconds">
                        <line x1="100" y1="114" x2="100" y2="26" stroke="#ef4444" stroke-width="2" stroke-linecap="round"
                            :transform="`rotate(${now.getSeconds() * 6} 100 100)`" />
                    </template>
                    <circle cx="100" cy="100" r="5" fill="#ef4444" />
                </svg>
            @endif
            <p class="{{ $clock['style'] === 'analog' ? 'text-lg' : 'text-3xl' }} font-semibold tabular-nums text-zinc-900 dark:text-zinc-100" x-text="time"></p>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400" x-text="date"></p>
        </div>
    </div>
@endif
