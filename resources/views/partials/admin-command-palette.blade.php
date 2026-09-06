@php
    $commandItems = $sidebarMenu->flatMap(function ($item) {
        if ($item->is_group) {
            return $item->children->map(fn ($child) => [
                'label' => __($child->label),
                'group' => __($item->label),
                'icon' => \App\Models\MenuItem::iconExists($child->icon) ? $child->icon : 'link',
                'href' => $child->route_name && \Illuminate\Support\Facades\Route::has($child->route_name)
                    ? route($child->route_name)
                    : ($child->url ?? '#'),
            ]);
        }

        return [[
            'label' => __($item->label),
            'group' => null,
            'icon' => \App\Models\MenuItem::iconExists($item->icon) ? $item->icon : 'link',
            'href' => $item->route_name && \Illuminate\Support\Facades\Route::has($item->route_name)
                ? route($item->route_name)
                : ($item->url ?? '#'),
        ]];
    })->values();

    $commandLabelsJson = json_encode($commandItems->pluck('label')->all());
    $commandGroupsJson = json_encode($commandItems->pluck('group')->all());
@endphp

<div x-data="{
        commandOpen: false,
        commandQuery: '',
        commandSelected: 0,
        commandLabels: {{ $commandLabelsJson }},
        commandGroups: {{ $commandGroupsJson }},
        commandOpenPalette() {
            this.commandOpen = true;
            this.commandQuery = '';
            this.commandSelected = 0;
            document.body.style.overflow = 'hidden';
            this.$nextTick(() => this.$refs.commandInput.focus());
        },
        commandClosePalette() {
            this.commandOpen = false;
            document.body.style.overflow = '';
        },
        commandMatches(i) {
            const q = this.commandQuery.trim().toLowerCase();
            if (!q) return true;
            const group = this.commandGroups[i] || '';
            return this.commandLabels[i].toLowerCase().includes(q) || group.toLowerCase().includes(q);
        },
        commandVisible() {
            return this.commandLabels.map((_, i) => i).filter(i => this.commandMatches(i));
        },
        commandMove(delta) {
            const visible = this.commandVisible();
            if (!visible.length) return;
            let pos = visible.indexOf(this.commandSelected);
            pos = pos === -1 ? 0 : (pos + delta + visible.length) % visible.length;
            this.commandSelected = visible[pos];
            this.$nextTick(() => this.$refs['commandItem' + this.commandSelected]?.scrollIntoView({ block: 'nearest' }));
        },
        commandActivate() {
            this.$refs['commandItem' + this.commandSelected]?.click();
        },
    }"
    x-on:keydown.window="
        if (($event.ctrlKey || $event.metaKey) && $event.shiftKey && $event.key.toLowerCase() === 'k') {
            $event.preventDefault();
            commandOpen ? commandClosePalette() : commandOpenPalette();
        } else if (commandOpen && $event.key === 'Escape') {
            $event.preventDefault();
            commandClosePalette();
        } else if (commandOpen && $event.key === 'ArrowDown') {
            $event.preventDefault();
            commandMove(1);
        } else if (commandOpen && $event.key === 'ArrowUp') {
            $event.preventDefault();
            commandMove(-1);
        } else if (commandOpen && $event.key === 'Enter') {
            $event.preventDefault();
            commandActivate();
        }
    "
>
    <button type="button" x-on:click="commandOpenPalette()"
        class="hidden sm:inline-flex items-center gap-2 h-8 px-2.5 rounded-lg border border-zinc-200 text-zinc-400 hover:text-zinc-600 hover:bg-zinc-50 transition-colors dark:border-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
        aria-label="{{ __('Open command palette') }}">
        <flux:icon.magnifying-glass class="size-4" />
        <kbd class="text-[10px] font-semibold tracking-wide">Ctrl Shift K</kbd>
    </button>

    <div x-show="commandOpen" x-cloak
        class="fixed inset-0 z-50 flex items-start justify-center px-4 pt-24"
        x-on:click.self="commandClosePalette()">

        <div class="fixed inset-0 bg-zinc-900/40 backdrop-blur-sm"></div>

        <div x-show="commandOpen" x-transition.opacity.duration.150ms
            class="relative w-full max-w-lg bg-white dark:bg-zinc-800 rounded-xl shadow-2xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">

            <div class="flex items-center gap-2.5 px-4 py-3 border-b border-zinc-100 dark:border-zinc-700">
                <flux:icon.magnifying-glass class="size-4 text-zinc-400 shrink-0" />
                <input x-ref="commandInput" type="text" x-model="commandQuery"
                    x-on:input="commandSelected = commandVisible()[0] ?? 0"
                    placeholder="{{ __('Jump to a page...') }}" autocomplete="off"
                    class="flex-1 bg-transparent outline-none text-sm text-zinc-800 dark:text-zinc-100 placeholder:text-zinc-400">
                <kbd class="text-[10px] font-semibold text-zinc-400 border border-zinc-200 dark:border-zinc-600 rounded px-1.5 py-0.5">Esc</kbd>
            </div>

            <div class="max-h-80 overflow-y-auto py-1.5">
                @foreach ($commandItems as $index => $item)
                    <a href="{{ $item['href'] }}" wire:navigate.hover x-ref="commandItem{{ $index }}"
                        x-show="commandMatches({{ $index }})"
                        x-on:mouseenter="commandSelected = {{ $index }}"
                        x-on:click="commandClosePalette()"
                        class="flex items-center gap-2.5 px-4 py-2 text-sm transition-colors"
                        :class="commandSelected === {{ $index }} ? 'bg-primary/10 text-primary' : 'text-zinc-700 dark:text-zinc-200'">
                        <x-dynamic-component :component="'flux::icon.'.$item['icon']" class="size-4 shrink-0" />
                        <span class="truncate">{{ $item['label'] }}</span>
                        @if ($item['group'])
                            <span class="ml-auto text-xs text-zinc-400 shrink-0">{{ $item['group'] }}</span>
                        @endif
                    </a>
                @endforeach

                <div x-show="commandVisible().length === 0" x-cloak class="px-4 py-8 text-center text-sm text-zinc-400">
                    {{ __('No pages found for') }} "<span x-text="commandQuery" class="text-zinc-500 font-medium"></span>"
                </div>
            </div>
        </div>
    </div>
</div>
