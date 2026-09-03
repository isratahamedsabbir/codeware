<?php

namespace App\Livewire\Admin\FileManager;

use App\Support\AdminActivity;
use App\Support\FileManagerPath;
use FilesystemIterator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;
use ZipArchive;

class Index extends Component
{
    use WithFileUploads;

    // Kept comfortably under config('livewire.payload.max_size') (16MB) — the
    // file's content round-trips as part of the Livewire request payload on
    // every interaction while the editor is open, alongside the rest of the
    // component's state, so this needs real headroom rather than sitting
    // right at the payload ceiling.
    private const MAX_EDIT_BYTES = 10 * 1024 * 1024; // 10 MB

    private const IMAGE_EXTS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico', 'avif'];

    private const VIDEO_EXTS = ['mp4', 'webm', 'ogg', 'ogv', 'mov', 'm4v'];

    #[Url]
    public string $path = '';

    public ?string $selected = null;

    public string $kind = 'none';

    public string $editingContent = '';

    public bool $editable = false;

    public string $createType = 'folder';

    public string $newName = '';

    public ?string $composeFolder = null;

    public string $composeName = '';

    public string $composeContent = '';

    public array $uploads = [];

    public array $checked = [];

    public ?string $deleteTarget = null;

    public bool $deletingSelected = false;

    public ?string $renameTarget = null;

    public string $renameNewName = '';

    public string $transferMode = 'move';

    public ?string $transferTarget = null;

    public bool $transferringSelected = false;

    public string $transferDestination = '';

    public function mount(): void
    {
        $this->normalizePath();
    }

    #[Computed]
    public function canManage(): bool
    {
        return Gate::allows('manage-file-manager');
    }

    /**
     * Every mutating action (create/compose/delete/rename/move/copy/zip/
     * extract/upload/save) re-checks this — the route-level `view-file-manager`
     * gate only covers browsing/downloading, so a user with read-only access
     * must be blocked here too, not just have the UI hidden from them.
     */
    private function authorizeManage(): void
    {
        abort_unless(Gate::allows('manage-file-manager'), 403);
    }

    public function updatedPath(): void
    {
        $this->normalizePath();
        $this->closePreview();
    }

    private function normalizePath(): void
    {
        try {
            $absolute = FileManagerPath::resolve($this->path);
        } catch (Throwable) {
            $this->path = '';

            return;
        }

        if (! is_dir($absolute)) {
            $relative = FileManagerPath::relative($absolute);
            $parent = dirname(str_replace('\\', '/', $relative));
            $this->path = $parent === '.' ? '' : $parent;
        }
    }

    #[Computed]
    public function breadcrumbs(): array
    {
        if ($this->path === '') {
            return [];
        }

        $accum = [];
        $crumbs = [];

        foreach (explode('/', $this->path) as $segment) {
            $accum[] = $segment;
            $crumbs[] = ['label' => $segment, 'path' => implode('/', $accum)];
        }

        return $crumbs;
    }

    #[Computed]
    public function entries()
    {
        try {
            $absolute = FileManagerPath::resolve($this->path);
        } catch (Throwable) {
            // The current folder became unreachable (deleted/moved elsewhere,
            // or an unresolvable path) — recover to the root instead of
            // crashing the render into a raw error page.
            $this->path = '';
            $this->dispatch('notify', message: "That folder couldn't be opened — showing the project root instead.");
            $absolute = FileManagerPath::resolve($this->path);
        }

        return collect(scandir($absolute) ?: [])
            ->reject(fn ($name) => in_array($name, ['.', '..'], true))
            ->map(function ($name) use ($absolute) {
                $full = $absolute.DIRECTORY_SEPARATOR.$name;
                $isDir = is_dir($full);

                return [
                    'name' => $name,
                    'is_dir' => $isDir,
                    'size' => $isDir ? null : @filesize($full),
                    'ext' => $isDir ? null : strtolower(pathinfo($name, PATHINFO_EXTENSION)),
                ];
            })
            ->sort(function ($a, $b) {
                if ($a['is_dir'] !== $b['is_dir']) {
                    return $a['is_dir'] ? -1 : 1;
                }

                return strcasecmp($a['name'], $b['name']);
            })
            ->values();
    }

    public function open(string $name): void
    {
        $relative = $this->path === '' ? $name : $this->path.'/'.$name;

        try {
            $absolute = FileManagerPath::resolve($relative);
        } catch (Throwable) {
            $this->dispatch('notify', message: "\"{$name}\" could not be opened. It may have been moved, deleted, or renamed.");

            return;
        }

        if (is_dir($absolute)) {
            $this->path = $relative;
            $this->closePreview();
            $this->checked = [];

            return;
        }

        $this->loadFile($relative, $absolute);
    }

    public function up(): void
    {
        if ($this->path === '') {
            return;
        }

        $parent = dirname(str_replace('\\', '/', $this->path));
        $this->path = $parent === '.' ? '' : $parent;
        $this->closePreview();
        $this->checked = [];
    }

    public function goTo(string $path): void
    {
        $this->path = $path;
        $this->closePreview();
        $this->checked = [];
    }

    public function clearChecked(): void
    {
        $this->checked = [];
    }

    public function toggleChecked(string $name): void
    {
        if (in_array($name, $this->checked, true)) {
            $this->checked = array_values(array_diff($this->checked, [$name]));
        } else {
            $this->checked[] = $name;
        }
    }

    public function closePreview(): void
    {
        $this->selected = null;
        $this->editingContent = '';
        $this->editable = false;
        $this->kind = 'none';
    }

    private function loadFile(string $relative, string $absolute): void
    {
        $this->selected = $relative;

        $ext = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));

        if (in_array($ext, self::IMAGE_EXTS, true)) {
            $this->kind = 'image';
            $this->editable = false;

            return;
        }

        if (in_array($ext, self::VIDEO_EXTS, true)) {
            $this->kind = 'video';
            $this->editable = false;

            return;
        }

        $size = @filesize($absolute) ?: 0;

        if ($size > self::MAX_EDIT_BYTES) {
            $this->kind = 'too-large';
            $this->editable = false;

            return;
        }

        $sample = @file_get_contents($absolute, false, null, 0, 8192);

        if ($sample === false || str_contains($sample, "\0")) {
            $this->kind = 'binary';
            $this->editable = false;

            return;
        }

        $this->kind = 'text';
        $this->editable = true;
        $this->editingContent = @file_get_contents($absolute) ?: '';
    }

    public function saveFile(): void
    {
        if (! $this->selected || ! $this->editable) {
            return;
        }

        $this->authorizeManage();

        try {
            $absolute = FileManagerPath::resolve($this->selected);
        } catch (Throwable) {
            $this->dispatch('notify', message: 'File no longer exists.');

            return;
        }

        if (! is_file($absolute)) {
            $this->dispatch('notify', message: 'File no longer exists.');

            return;
        }

        $this->backup($absolute);

        if (! $this->writeFileWithRetry($absolute, $this->editingContent)) {
            $this->dispatch('notify', message: 'Could not save the file — it may be locked by another process (e.g. a running queue worker) or you may not have write permission. Your changes are still in the editor; try again.');

            return;
        }

        AdminActivity::log('file_manager.edit', "Edited file: {$this->selected}");

        $this->dispatch('notify', message: 'File saved successfully');
        $this->dispatch('file-manager-saved');
    }

    /**
     * Writes with a couple of short retries rather than a single attempt —
     * actively-written files (e.g. storage/logs/laravel.log, which the
     * concurrently-running queue worker and app itself keep appending to)
     * can hold a transient lock that clears within milliseconds.
     */
    private function writeFileWithRetry(string $absolute, string $content, int $attempts = 3): bool
    {
        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            if (@file_put_contents($absolute, $content, LOCK_EX) !== false) {
                return true;
            }

            if ($attempt < $attempts) {
                usleep(150_000);
            }
        }

        return false;
    }

    public function openCreateModal(string $type): void
    {
        $this->authorizeManage();

        $this->createType = $type === 'file' ? 'file' : 'folder';
        $this->newName = '';
        $this->resetErrorBag('newName');
        $this->dispatch('open-modal', name: 'file-manager-create');
    }

    /**
     * A name is safe to place directly inside a known directory: no path
     * separators (which would escape it) and no reserved Windows filename
     * characters.
     */
    private function isValidName(string $name): bool
    {
        return $name !== ''
            && preg_match('/^[^\\\\\/:*?"<>|]+$/', $name) === 1
            && ! in_array($name, ['.', '..'], true);
    }

    public function createEntry(): void
    {
        $this->authorizeManage();

        $name = trim($this->newName);

        if (! $this->isValidName($name)) {
            $this->addError('newName', 'Enter a valid name (no slashes or special characters).');

            return;
        }

        try {
            $parent = FileManagerPath::resolve($this->path);
        } catch (Throwable) {
            $this->addError('newName', 'This folder no longer exists.');

            return;
        }

        $target = $parent.DIRECTORY_SEPARATOR.$name;

        if (file_exists($target)) {
            $this->addError('newName', 'A file or folder with that name already exists here.');

            return;
        }

        $relative = $this->path === '' ? $name : $this->path.'/'.$name;

        if ($this->createType === 'folder') {
            if (! @mkdir($target, 0755)) {
                $this->addError('newName', 'Could not create the folder.');

                return;
            }

            AdminActivity::log('file_manager.create_folder', "Created folder: {$relative}");
        } else {
            if (@file_put_contents($target, '') === false) {
                $this->addError('newName', 'Could not create the file.');

                return;
            }

            AdminActivity::log('file_manager.create_file', "Created file: {$relative}");
        }

        $this->dispatch('notify', message: ucfirst($this->createType).' created successfully');
        $this->dispatch('close-modal', name: 'file-manager-create');
        $this->newName = '';
    }

    /**
     * Opens the "Compose" modal — write a brand-new text file's content and
     * save it in one step. $folderName, when set (invoked from a folder's
     * 3-dot menu), targets that folder directly without navigating into it
     * first; otherwise it targets the currently browsed folder.
     */
    public function openComposeModal(?string $folderName = null): void
    {
        $this->authorizeManage();

        $this->composeFolder = $folderName;
        $this->composeName = '';
        $this->composeContent = '';
        $this->resetErrorBag('composeName');
        $this->dispatch('open-modal', name: 'file-manager-compose');
    }

    public function composeFile(): void
    {
        $this->authorizeManage();

        $name = trim($this->composeName);

        if (! $this->isValidName($name)) {
            $this->addError('composeName', 'Enter a valid file name (no slashes or special characters).');

            return;
        }

        $targetPath = $this->composeFolder
            ? ($this->path === '' ? $this->composeFolder : $this->path.'/'.$this->composeFolder)
            : $this->path;

        try {
            $dir = FileManagerPath::resolve($targetPath);
        } catch (Throwable) {
            $this->addError('composeName', 'That folder no longer exists.');

            return;
        }

        if (! is_dir($dir)) {
            $this->addError('composeName', 'That folder no longer exists.');

            return;
        }

        $target = $dir.DIRECTORY_SEPARATOR.$name;

        if (file_exists($target)) {
            $this->addError('composeName', 'A file with that name already exists here.');

            return;
        }

        if (@file_put_contents($target, $this->composeContent) === false) {
            $this->addError('composeName', 'Could not create the file.');

            return;
        }

        $relative = $targetPath === '' ? $name : $targetPath.'/'.$name;
        AdminActivity::log('file_manager.compose', "Composed file: {$relative}");

        $this->dispatch('notify', message: 'File created successfully');
        $this->dispatch('close-modal', name: 'file-manager-compose');
        $this->composeName = '';
        $this->composeContent = '';
        $this->composeFolder = null;
    }

    public function confirmDelete(string $name): void
    {
        $this->authorizeManage();

        $this->deleteTarget = $name;
        $this->deletingSelected = false;
        $this->dispatch('open-modal', name: 'file-manager-delete');
    }

    public function confirmDeleteSelected(): void
    {
        $this->authorizeManage();

        if (empty($this->checked)) {
            return;
        }

        $this->deleteTarget = null;
        $this->deletingSelected = true;
        $this->dispatch('open-modal', name: 'file-manager-delete');
    }

    public function deleteEntry(): void
    {
        $this->authorizeManage();

        if (! $this->deleteTarget) {
            return;
        }

        try {
            $dir = FileManagerPath::resolve($this->path);
        } catch (Throwable) {
            $this->deleteTarget = null;
            $this->dispatch('close-modal', name: 'file-manager-delete');
            $this->dispatch('notify', message: 'This folder no longer exists.');

            return;
        }

        $absolute = $dir.DIRECTORY_SEPARATOR.$this->deleteTarget;
        $relative = $this->path === '' ? $this->deleteTarget : $this->path.'/'.$this->deleteTarget;

        if (is_dir($absolute)) {
            File::deleteDirectory($absolute);
        } elseif (is_file($absolute)) {
            @unlink($absolute);
        }

        $this->checked = array_values(array_diff($this->checked, [$this->deleteTarget]));

        if ($this->selected === $relative) {
            $this->closePreview();
        }

        AdminActivity::log('file_manager.delete', "Deleted: {$relative}");

        $this->deleteTarget = null;
        $this->dispatch('close-modal', name: 'file-manager-delete');
        $this->dispatch('notify', message: 'Deleted successfully');
    }

    public function deleteSelected(): void
    {
        $this->authorizeManage();

        if (empty($this->checked)) {
            $this->deletingSelected = false;
            $this->dispatch('close-modal', name: 'file-manager-delete');

            return;
        }

        try {
            $dir = FileManagerPath::resolve($this->path);
        } catch (Throwable) {
            $this->checked = [];
            $this->deletingSelected = false;
            $this->dispatch('close-modal', name: 'file-manager-delete');
            $this->dispatch('notify', message: 'This folder no longer exists.');

            return;
        }

        $relativeList = [];
        $count = 0;

        foreach ($this->checked as $name) {
            $absolute = $dir.DIRECTORY_SEPARATOR.$name;

            if (is_dir($absolute)) {
                File::deleteDirectory($absolute);
                $count++;
            } elseif (is_file($absolute)) {
                @unlink($absolute);
                $count++;
            } else {
                continue;
            }

            $relativeList[] = $this->path === '' ? $name : $this->path.'/'.$name;
        }

        if ($this->selected !== null && in_array($this->selected, $relativeList, true)) {
            $this->closePreview();
        }

        AdminActivity::log('file_manager.delete_selected', "Deleted {$count} item(s): ".implode(', ', $relativeList));

        $this->checked = [];
        $this->deletingSelected = false;
        $this->dispatch('close-modal', name: 'file-manager-delete');
        $this->dispatch('notify', message: "Deleted {$count} item(s) successfully");
    }

    public function openRenameModal(string $name): void
    {
        $this->authorizeManage();

        $this->renameTarget = $name;
        $this->renameNewName = $name;
        $this->resetErrorBag('renameNewName');
        $this->dispatch('open-modal', name: 'file-manager-rename');
    }

    public function renameEntry(): void
    {
        $this->authorizeManage();

        if (! $this->renameTarget) {
            return;
        }

        $newName = trim($this->renameNewName);

        if (! $this->isValidName($newName)) {
            $this->addError('renameNewName', 'Enter a valid name (no slashes or special characters).');

            return;
        }

        try {
            $dir = FileManagerPath::resolve($this->path);
        } catch (Throwable) {
            $this->addError('renameNewName', 'This folder no longer exists.');

            return;
        }

        $oldAbsolute = $dir.DIRECTORY_SEPARATOR.$this->renameTarget;
        $newAbsolute = $dir.DIRECTORY_SEPARATOR.$newName;

        if (! file_exists($oldAbsolute)) {
            $this->addError('renameNewName', 'That item no longer exists.');

            return;
        }

        if ($newName !== $this->renameTarget && file_exists($newAbsolute)) {
            $this->addError('renameNewName', 'A file or folder with that name already exists here.');

            return;
        }

        if (! @rename($oldAbsolute, $newAbsolute)) {
            $this->addError('renameNewName', 'Could not rename the item.');

            return;
        }

        $oldRelative = $this->path === '' ? $this->renameTarget : $this->path.'/'.$this->renameTarget;
        $newRelative = $this->path === '' ? $newName : $this->path.'/'.$newName;

        if ($this->selected === $oldRelative) {
            $this->selected = $newRelative;
        }

        $this->checked = array_values(array_diff($this->checked, [$this->renameTarget]));

        AdminActivity::log('file_manager.rename', "Renamed {$oldRelative} to {$newRelative}");

        $this->renameTarget = null;
        $this->dispatch('close-modal', name: 'file-manager-rename');
        $this->dispatch('notify', message: 'Renamed successfully');
    }

    public function openTransferModal(string $mode, string $name): void
    {
        $this->authorizeManage();

        $this->transferMode = $mode === 'copy' ? 'copy' : 'move';
        $this->transferTarget = $name;
        $this->transferringSelected = false;
        $this->transferDestination = $this->path;
        $this->resetErrorBag('transferDestination');
        $this->dispatch('open-modal', name: 'file-manager-transfer');
    }

    public function openTransferModalSelected(string $mode): void
    {
        $this->authorizeManage();

        if (empty($this->checked)) {
            return;
        }

        $this->transferMode = $mode === 'copy' ? 'copy' : 'move';
        $this->transferTarget = null;
        $this->transferringSelected = true;
        $this->transferDestination = $this->path;
        $this->resetErrorBag('transferDestination');
        $this->dispatch('open-modal', name: 'file-manager-transfer');
    }

    /**
     * Subfolders of the currently browsed transfer destination, for the
     * folder-tree picker in the Move/Copy modal. The item(s) being
     * transferred are hidden when browsing their own source folder, since
     * moving/copying into them is meaningless (and blocked server-side
     * anyway).
     */
    #[Computed]
    public function transferBrowseEntries()
    {
        try {
            $absolute = FileManagerPath::resolve($this->transferDestination);
        } catch (Throwable) {
            return collect();
        }

        if (! is_dir($absolute)) {
            return collect();
        }

        $exclude = $this->transferDestination === $this->path
            ? ($this->transferringSelected ? $this->checked : array_filter([$this->transferTarget]))
            : [];

        return collect(scandir($absolute) ?: [])
            ->reject(fn ($name) => in_array($name, ['.', '..'], true))
            ->reject(fn ($name) => in_array($name, $exclude, true))
            ->filter(fn ($name) => is_dir($absolute.DIRECTORY_SEPARATOR.$name))
            ->sort(fn ($a, $b) => strcasecmp($a, $b))
            ->values();
    }

    #[Computed]
    public function transferBreadcrumbs(): array
    {
        $normalized = trim(str_replace('\\', '/', $this->transferDestination), '/');

        if ($normalized === '') {
            return [];
        }

        $accum = [];
        $crumbs = [];

        foreach (explode('/', $normalized) as $segment) {
            $accum[] = $segment;
            $crumbs[] = ['label' => $segment, 'path' => implode('/', $accum)];
        }

        return $crumbs;
    }

    public function transferGoTo(string $path): void
    {
        $this->transferDestination = $path;
        $this->resetErrorBag('transferDestination');
    }

    public function transferBrowseInto(string $name): void
    {
        $this->transferDestination = $this->transferDestination === ''
            ? $name
            : $this->transferDestination.'/'.$name;
        $this->resetErrorBag('transferDestination');
    }

    public function transferUp(): void
    {
        if ($this->transferDestination === '') {
            return;
        }

        $parent = dirname(str_replace('\\', '/', $this->transferDestination));
        $this->transferDestination = $parent === '.' ? '' : $parent;
        $this->resetErrorBag('transferDestination');
    }

    public function transferEntry(): void
    {
        $this->authorizeManage();

        if ($this->transferringSelected) {
            $this->transferSelected();

            return;
        }

        if (! $this->transferTarget) {
            return;
        }

        try {
            $sourceDir = FileManagerPath::resolve($this->path);
        } catch (Throwable) {
            $this->addError('transferDestination', 'This folder no longer exists.');

            return;
        }

        $sourceAbsolute = $sourceDir.DIRECTORY_SEPARATOR.$this->transferTarget;

        if (! file_exists($sourceAbsolute)) {
            $this->addError('transferDestination', 'That item no longer exists.');

            return;
        }

        try {
            $destAbsolute = FileManagerPath::resolve($this->transferDestination);
        } catch (Throwable) {
            $this->addError('transferDestination', 'Enter a valid destination folder.');

            return;
        }

        if (! is_dir($destAbsolute)) {
            $this->addError('transferDestination', 'Destination must be a folder.');

            return;
        }

        // Guard against moving/copying a folder into itself or one of its own descendants.
        if (
            is_dir($sourceAbsolute)
            && ($destAbsolute === $sourceAbsolute || str_starts_with($destAbsolute, $sourceAbsolute.DIRECTORY_SEPARATOR))
        ) {
            $this->addError('transferDestination', "Can't move a folder into itself.");

            return;
        }

        $targetAbsolute = $destAbsolute.DIRECTORY_SEPARATOR.$this->transferTarget;

        if ($targetAbsolute === $sourceAbsolute) {
            $this->addError('transferDestination', 'Source and destination are the same.');

            return;
        }

        if (file_exists($targetAbsolute)) {
            $this->addError('transferDestination', 'A file or folder with that name already exists there.');

            return;
        }

        if ($this->transferMode === 'copy') {
            if (is_dir($sourceAbsolute)) {
                File::copyDirectory($sourceAbsolute, $targetAbsolute);
            } else {
                File::copy($sourceAbsolute, $targetAbsolute);
            }
        } elseif (! @rename($sourceAbsolute, $targetAbsolute)) {
            $this->addError('transferDestination', 'Could not move the item.');

            return;
        }

        $sourceRelative = $this->path === '' ? $this->transferTarget : $this->path.'/'.$this->transferTarget;
        $targetRelative = FileManagerPath::relative($targetAbsolute);

        if ($this->transferMode === 'move') {
            $this->checked = array_values(array_diff($this->checked, [$this->transferTarget]));

            if ($this->selected === $sourceRelative) {
                $this->closePreview();
            }
        }

        AdminActivity::log(
            $this->transferMode === 'copy' ? 'file_manager.copy' : 'file_manager.move',
            ucfirst($this->transferMode)."d {$sourceRelative} to {$targetRelative}"
        );

        $this->transferTarget = null;
        $this->dispatch('close-modal', name: 'file-manager-transfer');
        $this->dispatch('notify', message: ucfirst($this->transferMode).'d successfully');
    }

    private function transferSelected(): void
    {
        if (empty($this->checked)) {
            $this->transferringSelected = false;
            $this->dispatch('close-modal', name: 'file-manager-transfer');

            return;
        }

        try {
            $sourceDir = FileManagerPath::resolve($this->path);
        } catch (Throwable) {
            $this->addError('transferDestination', 'This folder no longer exists.');

            return;
        }

        try {
            $destAbsolute = FileManagerPath::resolve($this->transferDestination);
        } catch (Throwable) {
            $this->addError('transferDestination', 'Enter a valid destination folder.');

            return;
        }

        if (! is_dir($destAbsolute)) {
            $this->addError('transferDestination', 'Destination must be a folder.');

            return;
        }

        if ($destAbsolute === $sourceDir) {
            $this->addError('transferDestination', 'Source and destination are the same.');

            return;
        }

        $movedNames = [];
        $count = 0;
        $skipped = 0;

        foreach ($this->checked as $name) {
            $sourceAbsolute = $sourceDir.DIRECTORY_SEPARATOR.$name;

            if (! file_exists($sourceAbsolute)) {
                $skipped++;

                continue;
            }

            // Guard against moving/copying a folder into itself or one of its own descendants.
            if (
                is_dir($sourceAbsolute)
                && ($destAbsolute === $sourceAbsolute || str_starts_with($destAbsolute, $sourceAbsolute.DIRECTORY_SEPARATOR))
            ) {
                $skipped++;

                continue;
            }

            $targetAbsolute = $destAbsolute.DIRECTORY_SEPARATOR.$name;

            if (file_exists($targetAbsolute)) {
                $skipped++;

                continue;
            }

            if ($this->transferMode === 'copy') {
                if (is_dir($sourceAbsolute)) {
                    File::copyDirectory($sourceAbsolute, $targetAbsolute);
                } else {
                    File::copy($sourceAbsolute, $targetAbsolute);
                }
            } elseif (! @rename($sourceAbsolute, $targetAbsolute)) {
                $skipped++;

                continue;
            }

            $count++;
            $movedNames[] = $name;
        }

        if ($this->transferMode === 'move' && $count > 0) {
            $this->checked = array_values(array_diff($this->checked, $movedNames));

            foreach ($movedNames as $name) {
                $relative = $this->path === '' ? $name : $this->path.'/'.$name;

                if ($this->selected === $relative) {
                    $this->closePreview();

                    break;
                }
            }
        }

        if ($count > 0) {
            AdminActivity::log(
                $this->transferMode === 'copy' ? 'file_manager.copy_selected' : 'file_manager.move_selected',
                ucfirst($this->transferMode)."d {$count} item(s) to: ".FileManagerPath::relative($destAbsolute)
            );
        }

        $this->transferringSelected = false;
        $this->dispatch('close-modal', name: 'file-manager-transfer');

        $message = $count > 0
            ? ucfirst($this->transferMode)."d {$count} item(s) successfully".($skipped ? ", {$skipped} skipped" : '.')
            : 'Nothing was '.($this->transferMode === 'copy' ? 'copied' : 'moved').' — items may already exist at the destination.';

        $this->dispatch('notify', message: $message);
    }

    public function updatedUploads(): void
    {
        if (empty($this->uploads)) {
            return;
        }

        $this->authorizeManage();

        $this->validate(['uploads.*' => 'file|max:102400']);

        try {
            $dir = FileManagerPath::resolve($this->path);
        } catch (Throwable) {
            $this->uploads = [];
            $this->dispatch('notify', message: 'This folder no longer exists.');

            return;
        }

        $uploaded = 0;
        $skipped = 0;

        foreach ($this->uploads as $file) {
            $name = $file->getClientOriginalName();

            if (! $this->isValidName($name) || file_exists($dir.DIRECTORY_SEPARATOR.$name)) {
                $skipped++;

                continue;
            }

            // copy() rather than move()/rename() — more reliable than a cross-volume
            // rename when the temp upload lives on a different disk/stream than the
            // destination; Livewire prunes its own temp upload afterwards regardless.
            File::copy($file->getRealPath(), $dir.DIRECTORY_SEPARATOR.$name);
            $uploaded++;
        }

        $this->uploads = [];

        if ($uploaded > 0) {
            AdminActivity::log('file_manager.upload', "Uploaded {$uploaded} file(s) to: ".($this->path ?: '/'));
        }

        $message = $uploaded > 0
            ? "{$uploaded} file(s) uploaded successfully".($skipped ? ", {$skipped} skipped (already exists or invalid name)" : '.')
            : 'Upload skipped — file(s) already exist or have an invalid name.';

        $this->dispatch('notify', message: $message);
    }

    public function zipSelected(): void
    {
        $this->authorizeManage();

        if (empty($this->checked)) {
            return;
        }

        try {
            $dir = FileManagerPath::resolve($this->path);
        } catch (Throwable) {
            $this->checked = [];
            $this->dispatch('notify', message: 'This folder no longer exists.');

            return;
        }

        $zipName = 'archive-'.now()->format('YmdHis').'.zip';
        $zipPath = $dir.DIRECTORY_SEPARATOR.$zipName;

        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
            $this->dispatch('notify', message: 'Could not create the zip file.');

            return;
        }

        $count = 0;

        foreach ($this->checked as $name) {
            $full = $dir.DIRECTORY_SEPARATOR.$name;

            if (! file_exists($full)) {
                continue;
            }

            if (is_dir($full)) {
                $this->addDirectoryToZip($zip, $full, $name);
            } else {
                $zip->addFile($full, $name);
            }

            $count++;
        }

        $zip->close();

        $relative = $this->path === '' ? $zipName : $this->path.'/'.$zipName;
        AdminActivity::log('file_manager.zip', "Zipped {$count} item(s) into: {$relative}");

        $this->checked = [];
        $this->dispatch('notify', message: "Created {$zipName}");
    }

    private function addDirectoryToZip(ZipArchive $zip, string $absoluteDir, string $localPrefix): void
    {
        $zip->addEmptyDir($localPrefix);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($absoluteDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $localPath = $localPrefix.'/'.ltrim(
                str_replace('\\', '/', substr($item->getPathname(), strlen($absoluteDir))),
                '/'
            );

            if ($item->isDir()) {
                $zip->addEmptyDir($localPath);
            } else {
                $zip->addFile($item->getPathname(), $localPath);
            }
        }
    }

    public function extractZip(string $name): void
    {
        $this->authorizeManage();

        try {
            $dir = FileManagerPath::resolve($this->path);
        } catch (Throwable) {
            $this->dispatch('notify', message: 'This folder no longer exists.');

            return;
        }

        $zipPath = $dir.DIRECTORY_SEPARATOR.$name;

        if (! is_file($zipPath) || strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'zip') {
            return;
        }

        $targetName = pathinfo($name, PATHINFO_FILENAME);
        $targetDir = $dir.DIRECTORY_SEPARATOR.$targetName;

        if (file_exists($targetDir)) {
            $this->dispatch('notify', message: "\"{$targetName}\" already exists here — remove it first.");

            return;
        }

        $zip = new ZipArchive;

        if ($zip->open($zipPath) !== true) {
            $this->dispatch('notify', message: 'Could not open the zip file.');

            return;
        }

        // Zip-slip guard: reject the whole archive if any entry would escape
        // the target folder, before extracting anything.
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);

            if ($entry === false || ! $this->isZipEntrySafe($entry)) {
                $zip->close();
                $this->dispatch('notify', message: 'Zip contains an unsafe path and was not extracted.');

                return;
            }
        }

        @mkdir($targetDir, 0755, true);

        $zip->extractTo($targetDir);
        $zip->close();

        $relative = $this->path === '' ? $targetName : $this->path.'/'.$targetName;
        AdminActivity::log('file_manager.extract', "Extracted {$name} into: {$relative}");

        $this->dispatch('notify', message: "Extracted into \"{$targetName}\"");
    }

    private function isZipEntrySafe(string $entry): bool
    {
        $entry = str_replace('\\', '/', $entry);

        if ($entry === '' || str_starts_with($entry, '/') || preg_match('#^[a-zA-Z]:#', $entry) === 1) {
            return false;
        }

        foreach (explode('/', $entry) as $segment) {
            if ($segment === '..') {
                return false;
            }
        }

        return true;
    }

    private function backup(string $absolute): void
    {
        $dir = storage_path('app/file-manager-backups');

        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $name = str_replace(['/', '\\'], '_', $this->selected).'.'.now()->format('YmdHis').'.bak';
        @copy($absolute, $dir.DIRECTORY_SEPARATOR.$name);

        // Keep only the most recent 200 backups across all files.
        collect(File::files($dir))
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->skip(200)
            ->each(fn ($file) => @unlink($file->getPathname()));
    }

    public function render()
    {
        return view('livewire.admin.file-manager.index')
            ->layout('layouts.admin', ['title' => 'File Manager']);
    }
}
