{{-- Clock plugin management screen. Rendered inside the Plugins\Show component, so
     `values.*` is bound to this plugin's saved settings (see Plugins::settings()). --}}
<div class="max-w-2xl space-y-6">
    <x-admin-section-card icon="clock" title="{{ $plugin['name'] }}" description="{{ $plugin['description'] }}">
        <div class="flex items-center justify-between gap-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <div>
                <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">Show clock in header</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">A clock icon appears on the right of the top bar; click it to open the time tile.</p>
            </div>
            <flux:switch wire:model.live="values.enabled" />
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <flux:field class="sm:col-span-2">
                <flux:label>Clock style</flux:label>
                <flux:select wire:model.live="values.style">
                    <flux:select.option value="analog">Analog (dial with hands, plus digital time)</flux:select.option>
                    <flux:select.option value="digital">Digital only</flux:select.option>
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Time format</flux:label>
                <flux:select wire:model.live="values.format">
                    <flux:select.option value="12">12-hour (3:45 PM)</flux:select.option>
                    <flux:select.option value="24">24-hour (15:45)</flux:select.option>
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Show seconds</flux:label>
                <flux:select wire:model.live="values.seconds">
                    <flux:select.option value="1">Yes</flux:select.option>
                    <flux:select.option value="0">No</flux:select.option>
                </flux:select>
            </flux:field>
        </div>
    </x-admin-section-card>
</div>
