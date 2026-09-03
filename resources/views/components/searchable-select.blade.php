@props([
    'model',
    'search',
    'options',
    'selectedValue' => '',
    'selectedLabel' => null,
    'allLabel' => 'All',
    'placeholder' => 'Search…',
])

<div x-data="{
        open: false,
        panelStyle: '',
        updatePosition() {
            this.$nextTick(() => {
                const trigger = this.$refs.trigger;
                const panel = this.$refs.panel;
                if (!trigger || !panel) return;

                const rect = trigger.getBoundingClientRect();
                const panelWidth = panel.offsetWidth;
                let left = rect.left;
                const maxLeft = window.innerWidth - panelWidth - 12;
                if (left > maxLeft) left = Math.max(12, maxLeft);

                this.panelStyle = `top:${rect.bottom + 8}px; left:${left}px;`;
            });
        },
        toggle() {
            if (this.open) {
                this.open = false;
                return;
            }
            this.open = true;
            this.updatePosition();
        },
        init() {
            const handler = (e) => {
                if (!this.$refs.trigger || !document.body.contains(this.$refs.trigger)) {
                    document.removeEventListener('click', handler);
                    return;
                }
                if (!this.open) return;
                if (this.$refs.trigger.contains(e.target)) return;
                if (this.$refs.panel && this.$refs.panel.contains(e.target)) return;
                this.open = false;
            };
            document.addEventListener('click', handler);

            window.addEventListener('resize', () => this.open && this.updatePosition());
            window.addEventListener('scroll', () => this.open && this.updatePosition(), true);
        },
    }"
    @keydown.escape.window="open = false"
    {{ $attributes->class(['relative']) }}>
    <button type="button" x-ref="trigger" @click="toggle()"
        class="inline-flex w-full items-center justify-between gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 hover:border-zinc-300 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100 sm:w-[200px]">
        <span class="truncate">{{ $selectedLabel ?: $allLabel }}</span>
        <flux:icon.chevron-down class="size-3.5 shrink-0 text-zinc-400 transition-transform" ::class="{ 'rotate-180': open }" />
    </button>

    <template x-teleport="body">
        <div x-ref="panel" x-show="open" x-cloak :style="panelStyle"
            class="fixed z-50 w-[260px] overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-lg">
            <div class="border-b border-zinc-100 p-2">
                <flux:input wire:model.live.debounce.300ms="{{ $search }}" :placeholder="$placeholder" size="sm" icon="magnifying-glass" />
            </div>

            <div class="max-h-64 overflow-y-auto py-1">
                <button type="button" wire:click="$set('{{ $model }}', '')" @click="open = false"
                    class="block w-full px-3 py-2 text-left text-sm transition-colors {{ $selectedValue === '' ? 'bg-primary font-medium text-white' : 'text-zinc-700 hover:bg-zinc-50' }}">
                    {{ $allLabel }}
                </button>

                @forelse ($options as $option)
                    <button type="button" wire:click="$set('{{ $model }}', '{{ $option->id }}')" @click="open = false"
                        class="block w-full truncate px-3 py-2 text-left text-sm transition-colors {{ (string) $selectedValue === (string) $option->id ? 'bg-primary font-medium text-white' : 'text-zinc-700 hover:bg-zinc-50' }}">
                        {{ $option->label }}
                    </button>
                @empty
                    <div class="px-3 py-6 text-center text-xs text-zinc-400">No matches found.</div>
                @endforelse
            </div>
        </div>
    </template>
</div>
