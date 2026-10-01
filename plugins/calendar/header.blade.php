@php
    $calendar = \App\Support\Plugins::settings('calendar');
@endphp
@if ($calendar['enabled'])
    <div class="relative" x-data="{
            open: false,
            today: new Date(),
            view: new Date(new Date().getFullYear(), new Date().getMonth(), 1),
            weekStart: {{ (int) $calendar['week_start'] }},
            get title() { return this.view.toLocaleDateString([], { month: 'long', year: 'numeric' }); },
            get weekdays() {
                const base = new Date(2023, 0, 1); // a Sunday
                return Array.from({ length: 7 }, (_, i) => new Date(2023, 0, 1 + ((i + this.weekStart) % 7))
                    .toLocaleDateString([], { weekday: 'short' }).slice(0, 2));
            },
            get days() {
                const y = this.view.getFullYear(), m = this.view.getMonth();
                const lead = (new Date(y, m, 1).getDay() - this.weekStart + 7) % 7;
                const count = new Date(y, m + 1, 0).getDate();
                return [...Array(lead).fill(null), ...Array.from({ length: count }, (_, i) => i + 1)];
            },
            isToday(d) {
                return d && this.today.getFullYear() === this.view.getFullYear()
                    && this.today.getMonth() === this.view.getMonth() && this.today.getDate() === d;
            },
            shift(n) { this.view = new Date(this.view.getFullYear(), this.view.getMonth() + n, 1); },
            reset() { this.today = new Date(); this.view = new Date(this.today.getFullYear(), this.today.getMonth(), 1); },
        }" @click.outside="open = false" @keydown.escape.window="open = false">
        <button type="button" @click="open = !open; if (open) reset()"
            title="{{ __('Calendar') }}" aria-label="{{ __('Calendar') }}" :aria-expanded="open"
            class="inline-flex size-8 items-center justify-center rounded-lg text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-zinc-800 focus:outline-none focus:ring-2 focus:ring-primary/30 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100">
            <flux:icon.calendar-days class="size-5" />
        </button>

        <div x-show="open" x-cloak x-transition.origin.top.right
            class="absolute right-0 top-full z-50 mt-2 w-72 rounded-xl border border-zinc-200 bg-white p-4 shadow-lg dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-3 flex items-center justify-between">
                <button type="button" @click="shift(-1)" aria-label="{{ __('Previous month') }}"
                    class="rounded p-1 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-700"><flux:icon.chevron-left class="size-4" /></button>
                <button type="button" @click="reset()" title="{{ __('Go to today') }}"
                    class="text-sm font-semibold text-zinc-900 dark:text-zinc-100" x-text="title"></button>
                <button type="button" @click="shift(1)" aria-label="{{ __('Next month') }}"
                    class="rounded p-1 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-700"><flux:icon.chevron-right class="size-4" /></button>
            </div>

            <div class="grid grid-cols-7 gap-1 text-center text-xs">
                <template x-for="w in weekdays" :key="w">
                    <span class="py-1 font-medium text-zinc-400" x-text="w"></span>
                </template>
                <template x-for="(d, i) in days" :key="i">
                    <span class="flex h-8 items-center justify-center rounded-md tabular-nums"
                        :class="isToday(d) ? 'bg-primary font-semibold text-white' : 'text-zinc-700 dark:text-zinc-200'"
                        x-text="d ?? ''"></span>
                </template>
            </div>
        </div>
    </div>
@endif
