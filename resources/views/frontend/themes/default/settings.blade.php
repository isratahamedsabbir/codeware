{{--
    Default theme settings — bound to the Theme Settings screen (Admin →
    Theme Settings) via wire:model="settings.theme_{slug}_*". Values persist to
    the settings table and can be read anywhere with
        \App\Models\Setting::get('theme_default_intro_heading')
    See default/home.blade.php for live usage.
--}}
<div class="grid grid-cols-1 gap-5">
    <flux:field>
        <flux:label>Intro Heading<x-field-hint text="The headline rendered on the public homepage." /></flux:label>
        <flux:input wire:model="settings.theme_default_intro_heading" placeholder="Welcome to {{ config('app.name') }}" />
    </flux:field>

    <flux:field>
        <flux:label>Intro Text<x-field-hint text="The supporting line under the heading." /></flux:label>
        <flux:textarea wire:model="settings.theme_default_intro_text" class="h-24" placeholder="A short description of the site." />
    </flux:field>
</div>