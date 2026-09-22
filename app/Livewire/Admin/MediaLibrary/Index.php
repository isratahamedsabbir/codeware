<?php

namespace App\Livewire\Admin\MediaLibrary;

use App\Models\MediaLibrary;
use App\Models\Setting;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
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
