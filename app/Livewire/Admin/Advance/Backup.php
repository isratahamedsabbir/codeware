<?php

namespace App\Livewire\Admin\Advance;

use App\Support\AdminActivity;
use FilesystemIterator;
use Livewire\Component;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class Backup extends Component
{
    public int $fileCount = 0;

    public string $sizeMb = '0';

    public function mount(): void
    {
        $this->refreshStatus();
    }

    public function download()
    {
        $filename = 'storage-backup-'.now()->format('Y-m-d-His').'.zip';
        $zipPath = storage_path('app/'.$filename);

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $source = storage_path('app');

        foreach ($this->iterate($source) as $item) {
            $relative = ltrim(str_replace('\\', '/', substr($item->getPathname(), strlen($source))), '/');

            // Never bundle the backup archive itself, or a previous run's leftovers.
            if (str_starts_with($relative, 'storage-backup-') || str_starts_with($relative, 'database-')) {
                continue;
            }

            $item->isDir() ? $zip->addEmptyDir($relative) : $zip->addFile($item->getPathname(), $relative);
        }

        $zip->close();

        AdminActivity::log('advance.backup.download', 'Storage backup downloaded');

        return response()->download($zipPath, $filename)->deleteFileAfterSend(true);
    }

    protected function refreshStatus(): void
    {
        $bytes = 0;
        $count = 0;

        foreach ($this->iterate(storage_path('app')) as $item) {
            if ($item->isFile()) {
                $bytes += $item->getSize();
                $count++;
            }
        }

        $this->fileCount = $count;
        $this->sizeMb = number_format($bytes / 1024 / 1024, 2);
    }

    protected function iterate(string $directory): RecursiveIteratorIterator
    {
        return new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
    }

    public function render()
    {
        return view('livewire.admin.advance.backup')->layout('layouts.admin', ['title' => 'Backup']);
    }
}
