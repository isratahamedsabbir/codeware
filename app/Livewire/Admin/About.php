<?php

namespace App\Livewire\Admin;

use App\Support\CodeInstall;
use App\Support\SystemUpdate;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class About extends Component
{
    use WithFileUploads;

    /** @var TemporaryUploadedFile|null */
    public $updateZip = null;

    public string $updatePassword = '';

    public function applyUpdate(): void
    {
        $this->validate(['updateZip' => ['required', 'file', 'mimes:zip']]);

        if ($refusal = CodeInstall::refusal($this->updatePassword, $this->updateZip->getRealPath())) {
            $this->addError('updateZip', $refusal);

            return;
        }

        try {
            $result = SystemUpdate::apply($this->updateZip->getRealPath());
        } catch (\RuntimeException $e) {
            $this->addError('updateZip', $e->getMessage());

            return;
        }

        CodeInstall::record('updated', "System updated from a zip ({$result['files']} files)");

        $this->reset('updateZip', 'updatePassword');
        session()->flash('success', "Update applied: {$result['files']} files replaced and migrations run. Previous files were saved to ".basename($result['backup']).' in storage/app/update-backups.');
    }

    /** Version a CLI tool reports, or null when it can't be run from the web server. */
    private function toolVersion(string $name, string $command, string $pattern): ?string
    {
        return Cache::remember("about:{$name}-version", now()->addDay(), function () use ($command, $pattern) {
            if (! function_exists('exec')) {
                return null;
            }

            @exec($command.' 2>&1', $output);

            return preg_match($pattern, implode("\n", $output), $m) ? $m[1] : null;
        });
    }

    public function render()
    {
        return view('livewire.admin.about', [
            'composerVersion' => $this->toolVersion('composer', 'composer --version --no-ansi', '/Composer version (\S+)/'),
            'nodeVersion' => $this->toolVersion('node', 'node --version', '/v?(\d+\.\d+\.\d+)/'),
        ])->layout('layouts.admin', ['title' => 'About']);
    }
}
