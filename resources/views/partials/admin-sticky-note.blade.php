{{-- Floating draggable sticky note, opened from the header's note icon.
     Wrapped in @persist('admin-sticky-note') by the caller so its position
     and open/closed state survive wire:navigate page transitions. The text
     and the size the admin drags it to are additionally mirrored to
     localStorage, so they survive a real page reload or even logging out
     and back in on the same browser. --}}
<div x-data="{
        open: false,
        x: Math.max(16, window.innerWidth - 300),
        y: 72,
        width: parseInt(localStorage.getItem('admin-sticky-note-width')) || 256,
        height: parseInt(localStorage.getItem('admin-sticky-note-height')) || 288,
        dragging: false,
        dragOffsetX: 0,
        dragOffsetY: 0,
        note: localStorage.getItem('admin-sticky-note-text') || '',

        init() {
            this.$watch('note', (value) => localStorage.setItem('admin-sticky-note-text', value));

            new ResizeObserver((entries) => {
                const entry = entries[0];
                if (! entry) return;
                // entry.contentRect excludes border/padding, but the width/height
                // set via x-bind:style are full border-box sizes — using it here
                // would read back a smaller number each tick and shrink the panel
                // a little further on every reactive re-render, forever. offsetWidth/
                // offsetHeight report the same border-box size that was set, so this
                // is a no-op read-back instead of a shrinking feedback loop.
                this.width = Math.round(entry.target.offsetWidth);
                this.height = Math.round(entry.target.offsetHeight);
                localStorage.setItem('admin-sticky-note-width', this.width);
                localStorage.setItem('admin-sticky-note-height', this.height);
            }).observe(this.$refs.panel);
        },

        startDrag(e) {
            this.dragging = true;
            const rect = this.$refs.panel.getBoundingClientRect();
            this.dragOffsetX = e.clientX - rect.left;
            this.dragOffsetY = e.clientY - rect.top;
        },
        onDrag(e) {
            if (! this.dragging) return;
            const rect = this.$refs.panel.getBoundingClientRect();
            this.x = Math.min(Math.max(0, e.clientX - this.dragOffsetX), window.innerWidth - rect.width);
            this.y = Math.min(Math.max(0, e.clientY - this.dragOffsetY), window.innerHeight - rect.height);
        },
        stopDrag() { this.dragging = false; },
    }"
    x-on:toggle-sticky-note.window="open = ! open"
    x-on:pointermove.window="onDrag($event)"
    x-on:pointerup.window="stopDrag()">

    <div x-show="open" x-cloak x-ref="panel"
        x-bind:style="`left:${x}px; top:${y}px; width:${width}px; height:${height}px;`"
        class="fixed z-100 flex flex-col resize overflow-auto min-w-48 min-h-40 rounded-xl border border-amber-200 bg-amber-50 shadow-xl select-none dark:border-amber-900 dark:bg-amber-950/60">

        {{-- Drag handle + off button --}}
        <div x-on:pointerdown="startDrag($event)"
            class="flex shrink-0 items-center justify-between gap-2 rounded-t-xl border-b border-amber-200 bg-amber-100/70 px-3 py-2 cursor-grab active:cursor-grabbing dark:border-amber-900 dark:bg-amber-900/40">
            <span class="flex items-center gap-1.5 text-xs font-semibold text-amber-700 dark:text-amber-300">
                <flux:icon.document-text class="size-3.5" />
                {{ __('Sticky Note') }}
            </span>
            <button type="button" x-on:pointerdown.stop x-on:click="open = false"
                title="{{ __('Off') }}" aria-label="{{ __('Off') }}"
                class="inline-flex size-6 items-center justify-center rounded-md text-amber-500 transition-colors hover:bg-rose-100 hover:text-rose-500 cursor-pointer">
                <flux:icon.power class="size-3.5" />
            </button>
        </div>

        {{-- Note body --}}
        <textarea x-model="note" placeholder="{{ __('Write a note...') }}"
            class="w-full flex-1 resize-none rounded-b-xl border-none bg-transparent px-3 py-3 text-sm text-amber-900 placeholder-amber-400 outline-none focus:ring-0 dark:text-amber-100 dark:placeholder-amber-600"></textarea>
    </div>
</div>
