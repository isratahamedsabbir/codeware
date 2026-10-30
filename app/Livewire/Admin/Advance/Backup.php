<?php

namespace App\Livewire\Admin\Advance;

use App\Support\AdminActivity;
use App\Support\DatabaseDumper;
use FilesystemIterator;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

/**
 * Single "Backup" tool for the whole site — the separate Storage and Database
 * tools were merged into one, since a backup only makes sense as a complete
 * snapshot. Downloads a single zip containing both the SQL dump (under
 * database/) and everything under storage/app (uploaded media, generated files
 * and .env backups).
 */
class Backup extends Component
{
    public string $connectionName = '';

    public int $tableCount = 0;

    public string $dbSizeMb = '0';

    public int $fileCount = 0;

    public string $fileSizeMb = '0';

    public function mount(): void
    {
        $this->refreshStatus();
    }

    public function download(string $type = 'full')
    {
        abort_unless(in_array($type, ['full', 'database', 'files'], true), 404);

        $stamp = now()->format('Y-m-d-His');

        if ($type === 'database') {
            $filename = 'database-backup-'.$stamp.'.sql';
            $dumpPath = storage_path('app/'.$filename);
            DatabaseDumper::dumpTo($dumpPath);

            AdminActivity::log('advance.backup.download', 'Database backup downloaded');

            return response()->download($dumpPath, $filename)->deleteFileAfterSend(true);
        }

        $filename = ($type === 'files' ? 'files-backup-' : 'full-backup-').$stamp.'.zip';
        $zipPath = storage_path('app/'.$filename);

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $dumpPath = null;

        if ($type === 'full') {
            $dumpPath = sys_get_temp_dir().'/codeware-database-'.$stamp.'.sql';
            DatabaseDumper::dumpTo($dumpPath);

            $zip->addEmptyDir('database');
            $zip->addFile($dumpPath, 'database/database-'.$stamp.'.sql');
        }

        $source = storage_path('app');

        foreach ($this->iterate($source) as $item) {
            $relative = ltrim(str_replace('\\', '/', substr($item->getPathname(), strlen($source))), '/');

            // Never bundle backup archives/dumps, including leftovers from older versions.
            if (str_starts_with($relative, 'full-backup-')
                || str_starts_with($relative, 'files-backup-')
                || str_starts_with($relative, 'database-backup-')
                || str_starts_with($relative, 'storage-backup-')
                || str_starts_with($relative, 'database-')) {
                continue;
            }

            $item->isDir() ? $zip->addEmptyDir($relative) : $zip->addFile($item->getPathname(), $relative);
        }

        $zip->close();

        if ($dumpPath) {
            @unlink($dumpPath);
        }

        AdminActivity::log('advance.backup.download', ($type === 'files' ? 'Files' : 'Full').' backup downloaded');

        return response()->download($zipPath, $filename)->deleteFileAfterSend(true);
    }

    protected function refreshStatus(): void
    {
        $this->refreshStorageStatus();
        $this->refreshDatabaseStatus();
    }

    protected function refreshStorageStatus(): void
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
        $this->fileSizeMb = number_format($bytes / 1024 / 1024, 2);
    }

    protected function refreshDatabaseStatus(): void
    {
        $this->connectionName = config('database.connections.mysql.database');

        $this->tableCount = count(DB::select('SHOW TABLES'));

        $size = DB::selectOne(
            'SELECT SUM(data_length + index_length) AS bytes FROM information_schema.TABLES WHERE table_schema = ?',
            [$this->connectionName]
        );

        $this->dbSizeMb = number_format(($size->bytes ?? 0) / 1024 / 1024, 2);
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
