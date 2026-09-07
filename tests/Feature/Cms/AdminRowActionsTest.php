<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Blade;

function renderRowActions(array $actions): string
{
    return Blade::render('<x-admin-row-actions :actions="$actions" />', ['actions' => $actions]);
}

it('renders inline icon buttons by default', function () {
    $html = renderRowActions([
        ['href' => '/edit/1', 'icon' => 'pencil', 'label' => 'Edit', 'color' => 'primary'],
        ['wireClick' => 'confirmDelete(1)', 'icon' => 'trash', 'label' => 'Delete', 'color' => 'rose-500'],
    ]);

    expect($html)->toContain('href="/edit/1"')
        ->toContain('wire:click="confirmDelete(1)"')
        ->not->toContain('aria-label="Actions"');
});

it('renders a three-dot dropdown trigger instead when the setting is dropdown', function () {
    Setting::set('admin_actions_display', 'dropdown');

    $html = renderRowActions([
        ['href' => '/edit/1', 'icon' => 'pencil', 'label' => 'Edit', 'color' => 'primary'],
        ['wireClick' => 'confirmDelete(1)', 'icon' => 'trash', 'label' => 'Delete', 'color' => 'rose-500'],
    ]);

    expect($html)->toContain('aria-label="Actions"')
        ->toContain('href="/edit/1"')
        ->toContain('wire:click="confirmDelete(1)"');
});

it('hides an action marked visible => false', function () {
    $html = renderRowActions([
        ['href' => '/edit/1', 'icon' => 'pencil', 'label' => 'Edit', 'color' => 'primary'],
        ['wireClick' => 'makeDefault(1)', 'icon' => 'star', 'label' => 'Set as default', 'color' => 'amber-500', 'visible' => false],
    ]);

    expect($html)->toContain('href="/edit/1"')
        ->not->toContain('makeDefault(1)');
});

it('renders a disabled action as a non-interactive element without a wire:click or href', function () {
    $html = renderRowActions([
        ['icon' => 'document', 'label' => 'Page', 'color' => 'secondary', 'disabled' => true],
    ]);

    expect($html)->toContain('cursor-not-allowed')
        ->toContain('aria-label="Page"')
        ->not->toContain('wire:click')
        ->not->toContain('href=');
});

it('defaults admin_actions_display to inline when the setting is missing', function () {
    expect(Setting::get('admin_actions_display', 'inline'))->toBe('inline');
});
