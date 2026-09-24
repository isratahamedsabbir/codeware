{{--
    Ecommerce theme settings — bound to the Theme Settings screen (Admin →
    Theme Settings) via wire:model="settings.theme_{slug}_*". Values persist to
    the settings table and are read by ecommerce/home.blade.php, e.g.
        \App\Models\Setting::get('theme_ecommerce_hero_badge')
--}}
<div class="grid grid-cols-1 gap-5">
    <flux:field>
        <flux:label>Hero Badge<x-field-hint text="Small pill shown above the homepage headline (leave blank to hide)." /></flux:label>
        <flux:input wire:model="settings.theme_ecommerce_hero_badge" placeholder="e.g. New season collection" />
    </flux:field>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <flux:field>
            <flux:label>Primary Color<x-field-hint text="Brand color — header, footer, prices, buttons and section accents." /></flux:label>
            <div class="flex items-center gap-3">
                <input type="color" wire:model="settings.theme_ecommerce_primary_color"
                    class="h-10 w-14 shrink-0 cursor-pointer rounded-lg border border-zinc-300 bg-white p-1 dark:border-zinc-600 dark:bg-zinc-800">
                <flux:input wire:model="settings.theme_ecommerce_primary_color" placeholder="#045b30" class="font-mono" />
            </div>
        </flux:field>

        <flux:field>
            <flux:label>Secondary Color<x-field-hint text="Accent color — used with the primary in hero and promo gradients." /></flux:label>
            <div class="flex items-center gap-3">
                <input type="color" wire:model="settings.theme_ecommerce_secondary_color"
                    class="h-10 w-14 shrink-0 cursor-pointer rounded-lg border border-zinc-300 bg-white p-1 dark:border-zinc-600 dark:bg-zinc-800">
                <flux:input wire:model="settings.theme_ecommerce_secondary_color" placeholder="#7cc242" class="font-mono" />
            </div>
        </flux:field>
    </div>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <flux:field>
            <flux:label>Promo Banner 1 Title<x-field-hint text="Label over the first promo strip." /></flux:label>
            <flux:input wire:model="settings.theme_ecommerce_promo_1_title" placeholder="New arrivals" />
        </flux:field>

        <flux:field>
            <flux:label>Promo Banner 2 Title<x-field-hint text="Label over the second promo strip." /></flux:label>
            <flux:input wire:model="settings.theme_ecommerce_promo_2_title" placeholder="Best deals" />
        </flux:field>
    </div>
</div>