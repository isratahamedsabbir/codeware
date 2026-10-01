<?php

namespace App\Livewire\Admin\Plugins;

use App\Support\Plugins;
use Livewire\Component;

/**
 * Hosts a plugin's own index.blade.php inside the admin layout. This is the
 * screen a plugin is managed from; the plugin decides what is on it.
 */
class Show extends Component
{
    public string $slug;

    /**
     * The plugin's saved settings, bound from its index.blade.php as `values.*`.
     * Only keys declared under "settings" in plugin.json are stored.
     */
    public array $values = [];

    public function mount(string $slug): void
    {
        $plugin = Plugins::find($slug);

        abort_unless($plugin !== null && $plugin['active'], 404);

        $this->slug = $slug;
        $this->values = Plugins::settings($slug);
    }

    public function updatedValues(): void
    {
        Plugins::saveSettings($this->slug, $this->values);
        $this->values = Plugins::settings($this->slug);

        // Header widgets live outside this component; reload so they pick up the change.
        $this->redirectRoute('admin.plugins.show', $this->slug, navigate: true);
    }

    public function render()
    {
        $plugin = Plugins::find($this->slug);

        return view('livewire.admin.plugins.show', [
            'plugin' => $plugin,
            'indexView' => Plugins::indexView($this->slug),
        ])->layout('layouts.admin', ['title' => $plugin['name']]);
    }
}
