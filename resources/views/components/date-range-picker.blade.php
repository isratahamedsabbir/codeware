@props([
    'fromModel' => 'fromDate',
    'toModel' => 'toDate',
    'from' => '',
    'to' => '',
])

<div
    wire:key="drp-{{ $from }}-{{ $to }}"
    x-data="dateRangePicker({
        fromModel: @js($fromModel),
        toModel: @js($toModel),
        appliedFrom: @js($from),
        appliedTo: @js($to),
    })"
    @keydown.escape.window="cancel()"
    {{ $attributes->class(['relative']) }}
>
    <button type="button" x-ref="trigger" @click="toggle()"
        class="inline-flex h-8 w-full items-center justify-between gap-2 rounded border border-zinc-200 bg-white px-3 text-sm text-zinc-700 hover:border-zinc-300 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100 sm:w-[220px]">
        <span class="inline-flex min-w-0 items-center gap-2">
            <flux:icon.calendar class="size-4 shrink-0 text-zinc-400" />
            <span class="truncate" x-text="triggerLabel()"></span>
        </span>
        <flux:icon.chevron-down class="size-3.5 shrink-0 text-zinc-400" />
    </button>

    <template x-teleport="body">
    <div x-ref="panel" x-show="open" x-cloak :style="panelStyle"
        class="fixed z-50 flex w-[640px] max-w-[95vw] flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-lg">

        <div class="flex flex-col sm:flex-row">
            {{-- Presets --}}
            <div class="flex shrink-0 flex-row overflow-x-auto border-b border-zinc-100 py-2 sm:w-40 sm:flex-col sm:overflow-visible sm:border-b-0 sm:border-r">
                <template x-for="p in presets" :key="p.key">
                    <button type="button" @click="selectPreset(p.key)"
                        class="whitespace-nowrap px-4 py-2 text-left text-sm transition-colors"
                        :class="activePreset === p.key ? 'bg-primary text-white font-medium' : 'text-zinc-700 hover:bg-zinc-50'"
                        x-text="p.label"></button>
                </template>
            </div>

            {{-- Calendars --}}
            <div class="flex-1 p-4">
                <div class="mb-3 flex items-center justify-between">
                    <button type="button" @click="shiftMonths(-1)"
                        class="rounded p-1 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600">
                        <flux:icon.chevron-left class="size-4" />
                    </button>
                    <div class="grid flex-1 grid-cols-2 text-center text-sm font-semibold text-zinc-800">
                        <span x-text="monthLabel(leftMonth)"></span>
                        <span x-text="monthLabel(rightMonth)"></span>
                    </div>
                    <button type="button" @click="shiftMonths(1)"
                        class="rounded p-1 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600">
                        <flux:icon.chevron-right class="size-4" />
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <template x-for="(month, mi) in [leftMonth, rightMonth]" :key="mi">
                        <div>
                            <div class="mb-1 grid grid-cols-7 text-center text-[10.5px] font-semibold uppercase tracking-wider text-zinc-400">
                                <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                            </div>
                            <div class="grid grid-cols-7 gap-y-1 text-center text-sm">
                                <template x-for="cell in weeks(month)" :key="cell.iso">
                                    <button type="button" :disabled="!cell.inMonth"
                                        @click="selectDay(cell.iso)"
                                        class="mx-auto flex h-7 w-7 items-center justify-center rounded-full text-xs"
                                        :class="dayClasses(cell)"
                                        x-text="cell.day"></button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="flex items-center justify-between border-t border-zinc-100 px-4 py-3">
            <span class="text-sm font-medium text-zinc-800" x-text="workingLabel()"></span>
            <div class="flex items-center gap-2">
                <flux:button variant="ghost" size="sm" @click="cancel()">Cancel</flux:button>
                <flux:button variant="primary" size="sm" @click="apply()">Apply</flux:button>
            </div>
        </div>
    </div>
    </template>
</div>
