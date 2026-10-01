<?php

namespace App\Livewire\Admin\Plugins;

use App\Support\AdminActivity;
use App\Support\Plugins;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Plugin Settings — the one screen that installs, switches and removes plugins.
 * What a plugin does once it is on lives in its own index.blade.php, opened from
 * the sidebar's Plugins dropdown (see Show).
 */
class Index extends Component
{
    use WithFileUploads;

    public bool $showInstallModal = false;

    /** @var TemporaryUploadedFile|null */
    public $pluginZip = null;

    public function openInstallModal(): void
    {
        $this->resetErrorBag('pluginZip');
        $this->showInstallModal = true;
    }

    public function closeInstallModal(): void
    {
        $this->reset('pluginZip', 'showInstallModal');
        $this->resetErrorBag('pluginZip');
    }

    public function installPlugin(): void
    {
        $this->validate(['pluginZip' => ['required', 'file', 'mimes:zip']]);

        try {
            $slug = Plugins::installFromZip($this->pluginZip->getRealPath());
        } catch (\RuntimeException $e) {
            $this->addError('pluginZip', $e->getMessage());

            return;
        }

        AdminActivity::log('created', "Plugin \"{$slug}\" installed");

        $this->closeInstallModal();
        session()->flash('success', "Plugin \"{$slug}\" installed. Activate it below to add it to the Plugins menu.");
    }

    public function toggle(string $slug): void
    {
        $plugin = Plugins::find($slug);

        if ($plugin === null || $plugin['default']) {
            return;
        }

        Plugins::setActive($slug, ! $plugin['active']);

        AdminActivity::log('updated', "Plugin \"{$slug}\" ".($plugin['active'] ? 'deactivated' : 'activated'));
    }

    public function remove(string $slug): void
    {
        if (Plugins::delete($slug)) {
            AdminActivity::log('deleted', "Plugin \"{$slug}\" removed");
            session()->flash('success', "Plugin \"{$slug}\" removed.");
        }
    }

    public function render()
    {
        return view('livewire.admin.plugins.index', [
            'plugins' => Plugins::all(),
        ])->layout('layouts.admin', ['title' => 'Plugin Settings']);
    }
}
