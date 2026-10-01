{{-- Calendar plugin management screen. `values.*` is bound to the plugin's saved settings. --}}
<div class="max-w-2xl space-y-6">
    <x-admin-section-card icon="calendar-days" title="{{ $plugin['name'] }}" description="{{ $plugin['description'] }}">
        <div class="flex items-center justify-between gap-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <div>
                <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">Show calendar in header</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">A calendar icon appears in the top bar; click it to open a month view.</p>
            </div>
            <flux:switch wire:model.live="values.enabled" />
        </div>

        <div class="mt-4">
            <flux:field>
                <flux:label>Week starts on</flux:label>
                <flux:select wire:model.live="values.week_start">
                    <flux:select.option value="0">Sunday</flux:select.option>
                    <flux:select.option value="1">Monday</flux:select.option>
                    <flux:select.option value="6">Saturday</flux:select.option>
                </flux:select>
            </flux:field>
        </div>
    </x-admin-section-card>
</div>
