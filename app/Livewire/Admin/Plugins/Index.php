<?php

namespace App\Livewire\Admin\Plugins;

use App\Models\MenuItem;
use App\Support\AdminActivity;
use App\Support\CodeInstall;
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

    public string $installPassword = '';

    public bool $showCreateModal = false;

    public string $newName = '';

    public string $newSlug = '';

    /** Set once the admin types in the slug box, which stops the name from overwriting it. */
    public bool $slugEdited = false;

    public string $newVersion = '1.0.0';

    public string $newDescription = '';

    public string $newAuthor = '';

    public string $newIcon = 'puzzle-piece';

    public function openInstallModal(): void
    {
        $this->resetErrorBag('pluginZip');
        $this->showInstallModal = true;
    }

    public function closeInstallModal(): void
    {
        $this->reset('pluginZip', 'showInstallModal', 'installPassword');
        $this->resetErrorBag('pluginZip');
    }

    public function installPlugin(): void
    {
        $this->validate(['pluginZip' => ['required', 'file', 'mimes:zip']]);

        if ($refusal = CodeInstall::refusal($this->installPassword, $this->pluginZip->getRealPath())) {
            $this->addError('pluginZip', $refusal);

            return;
        }

        try {
            $slug = Plugins::installFromZip($this->pluginZip->getRealPath());
        } catch (\RuntimeException $e) {
            $this->addError('pluginZip', $e->getMessage());

            return;
        }

        CodeInstall::record('created', "Plugin \"{$slug}\" installed");

        $this->closeInstallModal();
        session()->flash('success', "Plugin \"{$slug}\" installed. Activate it below to add it to the Plugins menu.");
    }

    public function openCreateModal(): void
    {
        $this->resetErrorBag();
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->reset('newName', 'newSlug', 'newVersion', 'newDescription', 'newAuthor', 'newIcon', 'showCreateModal');
        $this->slugEdited = false;
        $this->resetErrorBag();
    }

    public function updatedNewName(): void
    {
        if (! $this->slugEdited) {
            $this->newSlug = Plugins::slugify($this->newName);
        }
    }

    /**
     * Creates the plugin folder from the basics given here. Nothing is activated
     * yet — the admin edits the generated files first, then presses Activate.
     */
    public function createPlugin(): void
    {
        if ($refusal = CodeInstall::refusal(null, needsPassword: false)) {
            $this->addError('newSlug', $refusal);

            return;
        }

        $this->validate([
            'newName' => 'required|string|max:191',
            'newSlug' => ['required', 'string', 'max:191', function ($attribute, $value, $fail) {
                if (! Plugins::isValidSlug($value)) {
                    $fail(__('Use lowercase letters, numbers, dashes and underscores only.'));

                    return;
                }

                if (file_exists(Plugins::path().'/'.$value)) {
                    $fail(__('A plugin with this slug already exists.'));
                }
            }],
            'newVersion' => 'required|string|max:32',
            'newDescription' => 'nullable|string|max:255',
            'newAuthor' => 'nullable|string|max:191',
            'newIcon' => ['nullable', 'string', 'max:64', function ($attribute, $value, $fail) {
                if ($value && ! MenuItem::iconExists($value)) {
                    $fail(__('Unknown icon name.'));
                }
            }],
        ]);

        $slug = $this->newSlug;

        try {
            Plugins::create([
                'name' => $this->newName,
                'slug' => $slug,
                'version' => $this->newVersion,
                'description' => $this->newDescription,
                'author' => $this->newAuthor,
                'icon' => MenuItem::iconExists($this->newIcon) ? $this->newIcon : 'puzzle-piece',
            ]);
        } catch (\RuntimeException $e) {
            $this->addError('newSlug', $e->getMessage());

            return;
        }

        AdminActivity::log('plugins.create', "Plugin \"{$slug}\" created");

        $this->closeCreateModal();

        session()->flash('success', "Plugin \"{$slug}\" created in plugins/{$slug}. Edit its files, then press Activate to add it to the Plugins menu.");
    }

    /**
     * Hands the plugin back as a .zip holding its own folder — a backup before a
     * round of hand-editing, or a file to share with someone else.
     */
    public function downloadPlugin(string $slug)
    {
        try {
            $zipPath = Plugins::toZip($slug);
        } catch (\RuntimeException $e) {
            $this->addError('plugins', $e->getMessage());

            return null;
        }

        AdminActivity::log('plugins.download', "Plugin \"{$slug}\" downloaded as a zip");

        return response()->download($zipPath, "{$slug}.zip")->deleteFileAfterSend(true);
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
