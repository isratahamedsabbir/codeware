{{-- Floating draggable calculator, opened from the header's calculator icon.
     Wrapped in @persist('admin-calculator') by the caller so its position,
     display and open/closed state survive wire:navigate page transitions. --}}
<div x-data="{
        open: false,
        x: Math.max(16, window.innerWidth - 300),
        y: 72,
        dragging: false,
        dragOffsetX: 0,
        dragOffsetY: 0,
        display: '0',
        stored: null,
        operator: null,
        resetNext: false,

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

        input(digit) {
            if (this.resetNext) { this.display = '0'; this.resetNext = false; }
            if (digit === '.' && this.display.includes('.')) return;
            this.display = (this.display === '0' && digit !== '.') ? String(digit) : this.display + digit;
        },
        chooseOperator(op) {
            if (this.operator !== null && ! this.resetNext) this.calculate();
            this.stored = parseFloat(this.display);
            this.operator = op;
            this.resetNext = true;
        },
        calculate() {
            if (this.operator === null || this.stored === null) return;
            const current = parseFloat(this.display);
            let result = current;
            if (this.operator === '+') result = this.stored + current;
            if (this.operator === '-') result = this.stored - current;
            if (this.operator === '×') result = this.stored * current;
            if (this.operator === '÷') result = current === 0 ? 0 : this.stored / current;
            this.display = String(Math.round(result * 1e10) / 1e10);
            this.stored = null;
            this.operator = null;
            this.resetNext = true;
        },
        percent() { this.display = String(parseFloat(this.display) / 100); },
        sqrt() {
            const value = parseFloat(this.display);
            this.display = value < 0 ? 'Error' : String(Math.sqrt(value));
        },
        clear() {
            this.display = '0';
            this.stored = null;
            this.operator = null;
            this.resetNext = false;
        },
    }"
    x-on:toggle-calculator.window="open = ! open"
    x-on:pointermove.window="onDrag($event)"
    x-on:pointerup.window="stopDrag()">

    <div x-show="open" x-cloak x-ref="panel"
        x-bind:style="`left:${x}px; top:${y}px;`"
        class="fixed z-100 w-64 rounded-xl border border-zinc-200 bg-white shadow-xl select-none dark:border-zinc-700 dark:bg-zinc-800">

        {{-- Drag handle + off button --}}
        <div x-on:pointerdown="startDrag($event)"
            class="flex items-center justify-between gap-2 rounded-t-xl border-b border-zinc-100 bg-zinc-50 px-3 py-2 cursor-grab active:cursor-grabbing dark:border-zinc-700 dark:bg-zinc-800/60">
            <span class="flex items-center gap-1.5 text-xs font-semibold text-zinc-500 dark:text-zinc-400">
                <flux:icon.calculator class="size-3.5" />
                {{ __('Calculator') }}
            </span>
            <button type="button" x-on:pointerdown.stop x-on:click="open = false"
                title="{{ __('Off') }}" aria-label="{{ __('Off') }}"
                class="inline-flex size-6 items-center justify-center rounded-md text-zinc-400 transition-colors hover:bg-rose-50 hover:text-rose-500 cursor-pointer">
                <flux:icon.power class="size-3.5" />
            </button>
        </div>

        {{-- Display --}}
        <div class="px-3 py-3 text-right">
            <p x-text="display" class="truncate text-2xl font-semibold text-zinc-800 dark:text-zinc-100"></p>
        </div>

        {{-- Keys --}}
        <div class="p-3 pt-0 space-y-1.5">
            <div class="grid grid-cols-4 gap-1.5">
                <button type="button" x-on:click="clear()" class="admin-calc-key text-rose-500">C</button>
                <button type="button" x-on:click="sqrt()" class="admin-calc-key">√</button>
                <button type="button" x-on:click="percent()" class="admin-calc-key">%</button>
                <button type="button" x-on:click="chooseOperator('÷')" class="admin-calc-key bg-primary! text-white!">÷</button>
            </div>

            <div class="grid grid-cols-4 auto-rows-[2.5rem] gap-1.5">
                <button type="button" x-on:click="input(7)" class="admin-calc-key">7</button>
                <button type="button" x-on:click="input(8)" class="admin-calc-key">8</button>
                <button type="button" x-on:click="input(9)" class="admin-calc-key">9</button>
                <button type="button" x-on:click="chooseOperator('×')" class="admin-calc-key bg-primary! text-white!">×</button>

                <button type="button" x-on:click="input(4)" class="admin-calc-key">4</button>
                <button type="button" x-on:click="input(5)" class="admin-calc-key">5</button>
                <button type="button" x-on:click="input(6)" class="admin-calc-key">6</button>
                <button type="button" x-on:click="chooseOperator('-')" class="admin-calc-key bg-primary! text-white!">−</button>

                <button type="button" x-on:click="input(1)" class="admin-calc-key">1</button>
                <button type="button" x-on:click="input(2)" class="admin-calc-key">2</button>
                <button type="button" x-on:click="input(3)" class="admin-calc-key">3</button>
                <button type="button" x-on:click="chooseOperator('+')" class="admin-calc-key row-span-2 bg-primary! text-white!">+</button>

                <button type="button" x-on:click="input(0)" class="admin-calc-key">0</button>
                <button type="button" x-on:click="input('.')" class="admin-calc-key">.</button>
                <button type="button" x-on:click="calculate()" class="admin-calc-key">=</button>
            </div>
        </div>
    </div>
</div>
