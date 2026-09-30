<?php

namespace App\Livewire\Admin\MediaLibrary;

use App\Models\MediaLibrary;
use App\Models\Setting;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    /**
     * Identifies this page's own upload button to the shared PickerModal
     * (see resources/views/livewire/admin/media-library/picker-modal.blade.php)
     * so onMediaPickerSelected() below ignores selections from any other
     * <x-media-picker> that might also be open elsewhere on the page.
     */
    private const string PICKER_ID = 'media-library-manage-picker';

    public string $search = '';

    public string $filterType = 'all'; // all, image, document, video, audio

    public ?int $selectedMediaId = null;

    public bool $bulkMode = false;

    /** @var array<int, int> */
    public array $selectedMediaIds = [];

    public bool $showDetailsModal = false;

    public ?int $editingMediaId = null;

    // Edit form
    public string $editTitle = '';

    public string $editAltText = '';

    public string $editCaption = '';

    public string $editDescription = '';

    // Watermark (configured from the Media Library header button — used to live
    // on Settings → Other, moved here with the same four Setting keys)
    public bool $showWatermarkModal = false;

    public bool $watermarkEnabled = false;

    public ?string $watermarkImage = null;

    public string $watermarkPosition = 'bottom-right';

    public int $watermarkOpacity = 50;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterType(): void
    {
        $this->resetPage();
    }

    /**
     * The "Upload Files" header button (see @push('page-header-actions') in
     * index.blade.php) opens the same shared picker/upload modal every other
     * admin screen uses — see <x-media-picker>'s openPicker() for the
     * matching JS side of this event. Selecting/uploading a file there just
     * selects it here too, same as a grid click would.
     */
    #[On('mediaPickerSelected')]
    public function onMediaPickerSelected(string $pickerId, int $id): void
    {
        if ($pickerId !== self::PICKER_ID) {
            return;
        }

        $this->selectedMediaId = $id;
    }

    /**
     * The picker modal dispatches this after every upload/edit — even when
     * the user never clicks "Select this file" — so this grid stays in sync
     * without needing a page reload. render() re-queries the database on
     * every request, so this listener's body just needs to exist.
     */
    #[On('media-library-updated')]
    public function refreshAfterPickerUpdate(): void
    {
        //
    }

    /**
     * Plain click — always collapses to a single selection. If a multi-select
     * (ctrl+click) was in progress, this exits it and selects just this item.
     */
    public function selectMedia(int $mediaId): void
    {
        if ($this->bulkMode) {
            $this->bulkMode = false;
            $this->selectedMediaIds = [];
        }

        $this->selectedMediaId = $this->selectedMediaId === $mediaId ? null : $mediaId;
    }

    /**
     * Ctrl/Cmd+click — adds/removes this item from a multi-selection instead
     * of replacing it, entering multi-select mode on the first such click.
     */
    public function ctrlSelectMedia(int $mediaId): void
    {
        if (! $this->bulkMode) {
            $this->bulkMode = true;
            $this->selectedMediaIds = $this->selectedMediaId ? [$this->selectedMediaId] : [];
            $this->selectedMediaId = null;
        }

        if (in_array($mediaId, $this->selectedMediaIds, true)) {
            $this->selectedMediaIds = array_values(array_diff($this->selectedMediaIds, [$mediaId]));
        } else {
            $this->selectedMediaIds[] = $mediaId;
        }

        if (empty($this->selectedMediaIds)) {
            $this->bulkMode = false;
        }
    }

    public function selectAllOnPage(): void
    {
        $this->selectedMediaIds = MediaLibrary::query()
            ->when($this->search !== '', function ($q): void {
                $q->where(function ($q): void {
                    $q->where('title', 'like', '%'.$this->search.'%')
                        ->orWhere('original_filename', 'like', '%'.$this->search.'%')
                        ->orWhere('alt_text', 'like', '%'.$this->search.'%')
                        ->orWhere('description', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->filterType !== 'all', fn ($q) => $q->where('file_type', $this->filterType))
            ->latest()
            ->forPage($this->getPage(), 24)
            ->pluck('id')
            ->all();
    }

    public function clearSelection(): void
    {
        $this->selectedMediaId = null;
        $this->selectedMediaIds = [];
        $this->bulkMode = false;
    }

    public function deleteSelectedMedia(): void
    {
        $mediaItems = MediaLibrary::query()->whereIn('id', $this->selectedMediaIds)->get();

        foreach ($mediaItems as $media) {
            $this->authorize('delete', $media);
        }

        foreach ($mediaItems as $media) {
            Storage::disk($media->disk)->delete($media->path);
            $media->delete();
        }

        $this->selectedMediaIds = [];
        $this->dispatch('notify', message: 'Selected media deleted successfully');
    }

    public function confirmSelection(): void
    {
        if ($this->selectedMediaId) {
            $media = MediaLibrary::find($this->selectedMediaId);
            if ($media) {
                $mediaData = [
                    'id' => $media->id,
                    'url' => $media->url,
                    'title' => $media->title ?? $media->original_filename,
                    'alt' => $media->alt_text,
                ];

                $this->dispatch('media-selected', media: $mediaData);

                if (! request('picker')) {
                    $this->dispatch('notify', message: 'Media selected: '.($media->title ?? $media->original_filename));
                }
            }
        }
    }

    public function viewDetails(int $mediaId): void
    {
        $media = MediaLibrary::findOrFail($mediaId);
        $this->authorize('view', $media);

        $this->editingMediaId = $media->id;
        $this->editTitle = $media->title ?? '';
        $this->editAltText = $media->alt_text ?? '';
        $this->editCaption = $media->caption ?? '';
        $this->editDescription = $media->description ?? '';
        $this->showDetailsModal = true;
    }

    public function closeDetailsModal(): void
    {
        $this->showDetailsModal = false;
        $this->editingMediaId = null;
    }

    public function saveMediaDetails(): void
    {
        $media = MediaLibrary::findOrFail($this->editingMediaId);
        $this->authorize('update', $media);

        $this->validate([
            'editTitle' => 'nullable|string|max:255',
            'editAltText' => 'nullable|string|max:255',
            'editCaption' => 'nullable|string|max:500',
            'editDescription' => 'nullable|string|max:1000',
        ]);

        $media = MediaLibrary::findOrFail($this->editingMediaId);
        $media->update([
            'title' => $this->editTitle ?: null,
            'alt_text' => $this->editAltText ?: null,
            'caption' => $this->editCaption ?: null,
            'description' => $this->editDescription ?: null,
        ]);

        $this->closeDetailsModal();
        $this->dispatch('notify', message: 'Media details updated successfully');
    }

    public function deleteMedia(int $mediaId): void
    {
        $media = MediaLibrary::findOrFail($mediaId);
        $this->authorize('delete', $media);

        Storage::disk($media->disk)->delete($media->path);
        $media->delete();

        if ($this->selectedMediaId === $mediaId) {
            $this->clearSelection();
        }

        $this->selectedMediaIds = array_values(array_diff($this->selectedMediaIds, [$mediaId]));

        $this->dispatch('notify', message: 'Media deleted successfully');
    }

    /**
     * The Watermark header button (see @push('page-header-actions') in
     * index.blade.php, which is rendered outside this component's DOM root) has
     * no wire:id ancestor to call $wire on — it dispatches a plain window event
     * that the root <div x-on:open-watermark-modal.window=...> picks up.
     */
    public function openWatermarkModal(): void
    {
        $this->watermarkEnabled = Setting::get('watermark_enabled', '0') === '1';
        $this->watermarkImage = Setting::get('watermark_image') ?: null;
        $this->watermarkPosition = (string) Setting::get('watermark_position', 'bottom-right');
        $this->watermarkOpacity = (int) Setting::get('watermark_opacity', 50);
        $this->showWatermarkModal = true;
    }

    public function closeWatermarkModal(): void
    {
        $this->showWatermarkModal = false;
    }

    public function saveWatermark(): void
    {
        // watermarkEnabled intentionally not validated: a checkbox sends nothing
        // when cleared, and $this->watermarkEnabled ? '1' : '0' below is safe for
        // every value Livewire may deliver ('', '1', 'on', true, false).
        $this->validate([
            'watermarkImage' => ['nullable', 'string', 'max:2048'],
            'watermarkPosition' => ['required', 'in:top-left,top-right,bottom-left,bottom-right,center'],
            'watermarkOpacity' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        Setting::set('watermark_enabled', $this->watermarkEnabled ? '1' : '0');
        Setting::set('watermark_image', $this->watermarkImage);
        Setting::set('watermark_position', $this->watermarkPosition);
        Setting::set('watermark_opacity', (string) $this->watermarkOpacity);

        $this->closeWatermarkModal();
        $this->dispatch('notify', message: 'Watermark settings saved successfully');
    }

    // ── Storage symlink ──────────────────────────────────────────────────────
    // Everything this page shows is served from the `public` disk, which
    // Laravel only exposes over HTTP at /storage/... when public/storage is a
    // symlink into storage/app/public. On a fresh clone (or after someone
    // committed a copy of that folder instead of linking it) public/storage is
    // a plain directory or is missing entirely, uploads then 404, and
    // `php artisan storage:link` refuses to run because the path already
    // exists. The header button calls createStorageLink() so the fix does not
    // require shell access to the server.
    //
    // Staff can reach this page (route comment: "content — Staff included") so
    // the action is behind access-admin-system, the same gate that keeps other
    // system-level actions (delete post, bulk product ops) off the staff tier —
    // writing to the server filesystem is not a content edit. It is also
    // developer-environment only, so a deployed site neither shows the button
    // nor can be made to run it.

    public function hasStorageLink(): bool
    {
        return is_link($this->storageLinkPath());
    }

    /**
     * The link and its target come from filesystems.links, the same declaration
     * `php artisan storage:link` reads, so this button can never end up
     * pointing somewhere else than Artisan would.
     */
    public function storageLinkPath(): string
    {
        return (string) (array_key_first($this->configuredStorageLinks()) ?: public_path('storage'));
    }

    public function storageTargetPath(): string
    {
        $links = $this->configuredStorageLinks();
        $from = (string) array_key_first($links);

        return $from === '' ? storage_path('app/public') : (string) $links[$from];
    }

    /** @return array<string, string> */
    private function configuredStorageLinks(): array
    {
        return array_map('strval', (array) config('filesystems.links', []));
    }

    public function createStorageLink(): void
    {
        Gate::authorize('access-admin-system');

        // The header button that calls this is developer-only, and so is the
        // action: without this a deployed site could still have the filesystem
        // rewritten through a hand-rolled $wire call from devtools. Same
        // environment gate as the Admin → Features screen
        // (see Livewire\Admin\Features\Index).
        abort_unless(app()->environment('developer'), 404, 'Linking storage is only available in the developer environment.');

        if ($this->hasStorageLink()) {
            $this->dispatch('notify', type: 'info', message: 'Storage is already linked — nothing to do.');

            return;
        }

        $link = $this->storageLinkPath();
        $target = $this->storageTargetPath();

        if (! is_dir($target)) {
            $this->dispatch('notify', type: 'error', message: "Cannot link storage: {$target} does not exist.");

            return;
        }

        // A copied directory is sitting where the link has to go, which is
        // exactly what makes storage:link fail. Move it aside rather than
        // deleting it: nothing is lost, and the admin can drop the copy once
        // uploads are confirmed working through the link.
        $backup = null;

        if (file_exists($link)) {
            $backup = $this->unusedPath($link.'.bak-'.now()->format('Ymd-His'));

            if (! @rename($link, $backup)) {
                $this->dispatch('notify', type: 'error', message: "Could not move the existing {$link} directory aside. Check its permissions and try again.");

                return;
            }
        }

        if (! @symlink($target, $link)) {
            if ($backup !== null) {
                @rename($backup, $link);
            }

            $this->dispatch('notify', type: 'error', message: $this->symlinkFailureMessage());

            return;
        }

        // symlink() returning true is not proof of a working link on every
        // platform, and telling an admin "done" for a broken link is the worst
        // possible outcome here — so confirm before reporting success.
        if (! is_link($link)) {
            if ($backup !== null) {
                @rename($backup, $link);
            } else {
                @unlink($link);
            }

            $this->dispatch('notify', type: 'error', message: "Created {$link}, but it is not a usable symlink. Check the web server's file permissions.");

            return;
        }

        $this->dispatch('notify', type: 'success', message: $backup === null
            ? "Storage linked: {$link} → {$target}"
            : "Storage linked: {$link} → {$target}. The directory it replaced was kept at {$backup} — delete it once uploads are loading.");

        // The button is rendered from a @push('page-header-actions') block,
        // which the layout only flushes on a full page load, so re-rendering in
        // place would leave the button on screen with nothing left to do. Send
        // the admin back to a freshly rendered page instead.
        $this->redirect(route('admin.media-library'));
    }

    /** First free path in the $path, $path-1, $path-2, ... series. */
    private function unusedPath(string $path): string
    {
        $suffix = 1;

        while (file_exists($path.'-'.$suffix)) {
            $suffix++;
        }

        return file_exists($path) ? $path.'-'.$suffix : $path;
    }

    private function symlinkFailureMessage(): string
    {
        $reason = error_get_last()['message'] ?? 'unknown error';

        if (PHP_OS_FAMILY === 'Windows') {
            return 'Could not create the symlink ('.$reason.'). Windows only allows this when Developer Mode is on or the process is elevated — run "php artisan storage:link" from an Administrator terminal instead.';
        }

        return 'Could not create the symlink: '.$reason.'. Check that PHP is permitted to create symlinks, or run "php artisan storage:link" manually.';
    }

    public function render()
    {
        $this->authorize('viewAny', MediaLibrary::class);

        $query = MediaLibrary::query()
            ->with('uploader')
            ->latest();

        if ($this->search !== '') {
            $query->where(function ($q): void {
                $q->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('original_filename', 'like', '%'.$this->search.'%')
                    ->orWhere('alt_text', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->filterType !== 'all') {
            $query->where('file_type', $this->filterType);
        }

        $media = $query->paginate(24);

        // In picker mode, use separate picker view
        if (request('picker')) {
            return view('livewire.admin.media-library.picker', [
                'media' => $media,
            ])->layout('components.layouts.media-picker', [
                'title' => 'Media Picker',
            ]);
        }

        return view('livewire.admin.media-library.index', [
            'media' => $media,
        ])->layout('layouts.admin', [
            'title' => 'Media Library',
        ]);
    }
}
