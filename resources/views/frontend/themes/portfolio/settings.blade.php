{{--
    Portfolio theme settings — bound to the Theme Settings screen (Admin →
    Theme Settings) via wire:model="settings.theme_{slug}_*". Values persist to
    the settings table and are read by portfolio/home.blade.php, e.g.
        \App\Models\Setting::get('theme_portfolio_hero_title')
--}}
<div class="grid grid-cols-1 gap-5">
    <flux:field>
        <flux:label>Hero Title<x-field-hint text="Big heading over the portfolio hero section." /></flux:label>
        <flux:input wire:model="settings.theme_portfolio_hero_title" placeholder="Full Stack Developer" />
    </flux:field>

    <flux:field>
        <flux:label>Hero Tagline<x-field-hint text="The intro line under the hero title." /></flux:label>
        <flux:textarea wire:model="settings.theme_portfolio_hero_tagline" class="h-24"
            placeholder="Building fast, reliable, and scalable web applications." />
    </flux:field>

    <flux:field>
        <flux:label>Availability Text<x-field-hint text="The green status pill in the contact section." /></flux:label>
        <flux:input wire:model="settings.theme_portfolio_availability" placeholder="Available for new projects" />
    </flux:field>
</div>