<?php

namespace App\Livewire\Admin\MediaLibrary;

use App\Models\MediaLibrary;
use App\Support\MediaLibraryUploader;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithFileUploads;

class PickerModal extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public bool $show = false;

    public string $pickerId = '';

    public string $search = '';

    public string $filterType = 'all';

    public ?int $selectedMediaId = null;

    /**
     * Only used when $multiple is true (the product gallery picker) — every other
     * caller (featured image, OG image, ...) stays single-select via $selectedMediaId.
     *
     * @var array<int, int>
     */
    public array $selectedMediaIds = [];

    /**
     * Set by the caller that opened this picker (see openPicker()) — when true,
     * plain clicks still replace the selection (so the common case, picking one
     * image, needs no modifier key) but Ctrl/Cmd+click toggles a click into the
     * existing selection instead of replacing it, matching OS file-picker
     * conventions. Single-select callers never set this, so their behavior is
     * unchanged.
     */
    public bool $multiple = false;

    public string $activeTab = 'library'; // library | upload

    /** @var array<int, mixed> */
    public array $uploadFiles = [];

    public int $uploadIteration = 0;

    public int $page = 1;

    public int $perPage = 32;

    /**
     * Set when the field that opened this picker only accepts images (site icon, OG
     * image, product/category/post images, ...) — restricts both the library filter
     * and the upload validation to real image files, rather than the general-purpose
     * mixed-file rule used by the standalone Media Library manager.
     */
    public bool $onlyImages = false;

    /**
     * Comma-separated extensions this field's upload is restricted to when $onlyImages
     * is set — varies per field (a favicon accepts ico/png, a loader gif/png/jpg, a
     * photo field jpg/png/webp, ...), set by whichever <x-media-picker mimes="..."> opened us.
     */
    public string $restrictMimes = 'jpg,jpeg,png,gif,webp';

    public int $maxSizeKb = 2048;

    // Edit form — mirrors Index.php's attribute-edit fields, kept in sync with
    // whichever item is currently selected (see loadEditFieldsForSelected()).
    public string $editTitle = '';

    public string $editAltText = '';

    public string $editCaption = '';

    public string $editDescription = '';

    public function openPicker(string $pickerId, bool $onlyImages = false, string $mimes = 'jpg,jpeg,png,gif,webp', int $maxSizeKb = 2048, bool $multiple = false): void
    {
        $this->pickerId = $pickerId;
        $this->onlyImages = $onlyImages;
        $this->restrictMimes = $mimes;
        $this->maxSizeKb = $maxSizeKb;
        $this->multiple = $multiple;
        $this->show = true;
        $this->selectedMediaId = null;
        $this->selectedMediaIds = [];
        $this->search = '';
        $this->filterType = $onlyImages ? 'image' : 'all';
        $this->activeTab = 'library';
        $this->page = 1;
        $this->uploadFiles = [];
        $this->uploadIteration++;
        $this->loadEditFieldsForSelected();
    }

    public function closePicker(): void
    {
        $this->show = false;
        $this->selectedMediaId = null;
        $this->selectedMediaIds = [];
        $this->multiple = false;
        $this->pickerId = '';
        $this->dispatch('media-picker-closed');
    }

    /**
     * $additive is true only for a Ctrl/Cmd+click while $multiple is on — every
     * other case (single-select callers, or a plain click even in multi mode)
     * replaces the selection outright, so picking one image never needs a
     * modifier key.
     */
    public function selectMedia(int $id, bool $additive = false): void
    {
        if (! $this->multiple) {
            $this->selectedMediaId = $this->selectedMediaId === $id ? null : $id;
            $this->loadEditFieldsForSelected();

            return;
        }

        $this->selectedMediaId = $id;

        if ($additive) {
            $this->selectedMediaIds = in_array($id, $this->selectedMediaIds, true)
                ? array_values(array_diff($this->selectedMediaIds, [$id]))
                : [...$this->selectedMediaIds, $id];
        } else {
            $this->selectedMediaIds = [$id];
        }

        $this->loadEditFieldsForSelected();
    }

    public function deselectMedia(int $id): void
    {
        $this->selectedMediaIds = array_values(array_diff($this->selectedMediaIds, [$id]));

        if ($this->selectedMediaId === $id) {
            $this->selectedMediaId = null;
        }

        $this->loadEditFieldsForSelected();
    }

    /**
     * Keeps the details panel's edit inputs matched to whichever item is
     * currently the "active" one ($selectedMediaId — the last one clicked,
     * even in multi-select mode), the same way Index.php's viewDetails() does.
     */
    private function loadEditFieldsForSelected(): void
    {
        $media = $this->selectedMediaId ? MediaLibrary::find($this->selectedMediaId) : null;

        $this->editTitle = $media?->title ?? '';
        $this->editAltText = $media?->alt_text ?? '';
        $this->editCaption = $media?->caption ?? '';
        $this->editDescription = $media?->description ?? '';
    }

    public function saveMediaDetails(): void
    {
        $media = MediaLibrary::findOrFail($this->selectedMediaId);
        $this->authorize('update', $media);

        $this->validate([
            'editTitle' => 'nullable|string|max:255',
            'editAltText' => 'nullable|string|max:255',
            'editCaption' => 'nullable|string|max:500',
            'editDescription' => 'nullable|string|max:1000',
        ]);

        $media->update([
            'title' => $this->editTitle ?: null,
            'alt_text' => $this->editAltText ?: null,
            'caption' => $this->editCaption ?: null,
            'description' => $this->editDescription ?: null,
        ]);

        $this->dispatch('notify', message: 'Media details updated successfully');
        $this->dispatch('media-library-updated');
    }

    public function confirmSelection(): void
    {
        if ($this->multiple) {
            $items = MediaLibrary::whereIn('id', $this->selectedMediaIds)->get()
                ->filter(fn (MediaLibrary $media) => ! $this->onlyImages || $media->isImage());

            if ($items->isEmpty()) {
                return;
            }

            $this->dispatch('mediaPickerSelectedMultiple',
                pickerId: $this->pickerId,
                items: $items->map(fn (MediaLibrary $media) => [
                    'id' => $media->id,
                    'url' => $media->url,
                    'title' => $media->title ?? $media->original_filename,
                    'alt' => $media->alt_text ?? '',
                ])->values()->all(),
            );

            $this->closePicker();

            return;
        }

        if ($this->selectedMediaId === null) {
            return;
        }

        $media = MediaLibrary::find($this->selectedMediaId);

        if ($media === null || ($this->onlyImages && ! $media->isImage())) {
            return;
        }

        $this->dispatch('mediaPickerSelected',
            pickerId: $this->pickerId,
            id: $media->id,
            url: $media->url,
            title: $media->title ?? $media->original_filename,
            alt: $media->alt_text ?? '',
        );

        $this->closePicker();
    }

    public function updatingSearch(): void
    {
        $this->page = 1;
    }

    public function updatingFilterType(): void
    {
        $this->page = 1;
    }

    public function previousPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
        }
    }

    public function nextPage(int $lastPage): void
    {
        if ($this->page < $lastPage) {
            $this->page++;
        }
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function saveUploads(): void
    {
        $this->authorize('create', MediaLibrary::class);

        $this->validate([
            'uploadFiles.*' => $this->onlyImages
                ? 'file|max:'.$this->maxSizeKb.'|mimes:'.$this->restrictMimes
                : 'file|max:10240|mimes:jpg,jpeg,png,gif,webp,pdf,mp4,mp3,doc,docx,xls,xlsx',
        ]);

        $lastId = null;

        foreach ($this->uploadFiles as $file) {
            $lastId = $this->processUploadedFile($file);
        }

        $this->uploadFiles = [];
        $this->uploadIteration++;
        $this->activeTab = 'library';
        $this->page = 1;
        $this->filterType = 'all';
        $this->search = '';

        if ($lastId !== null) {
            $this->selectedMediaId = $lastId;
            if ($this->multiple) {
                $this->selectedMediaIds = [$lastId];
            }
        }

        $this->loadEditFieldsForSelected();

        $this->dispatch('notify', message: 'Files uploaded successfully');
        $this->dispatch('media-library-updated');
    }

    private function processUploadedFile(mixed $file): int
    {
        $path = $file->store('media', 'public');

        $media = MediaLibraryUploader::store($path, $file->getClientOriginalName(), $file->getMimeType(), $file->getSize(), auth()->id());

        return $media->id;
    }

    /**
     * Called from the browser once a file over the 10MB threshold finishes
     * uploading via the chunked endpoint (ChunkedUploadController) — its
     * MediaLibrary row already exists by this point, so this just selects it
     * and refreshes the grid, matching what saveUploads() does for the
     * normal (<=10MB) path.
     */
    public function finishChunkedUpload(int $mediaId): void
    {
        $this->selectedMediaId = $mediaId;

        if ($this->multiple) {
            $this->selectedMediaIds = in_array($mediaId, $this->selectedMediaIds, true)
                ? $this->selectedMediaIds
                : [...$this->selectedMediaIds, $mediaId];
        }

        $this->activeTab = 'library';
        $this->page = 1;
        $this->filterType = $this->onlyImages ? 'image' : 'all';
        $this->search = '';
        $this->loadEditFieldsForSelected();

        $this->dispatch('notify', message: 'File uploaded successfully');
        $this->dispatch('media-library-updated');
    }

    public function render()
    {
        $media = collect();
        $total = 0;
        $totalPages = 1;
        $selectedMedia = null;

        if ($this->show) {
            $query = MediaLibrary::query()->latest();

            if ($this->search !== '') {
                $query->where(function ($q): void {
                    $q->where('title', 'like', '%'.$this->search.'%')
                        ->orWhere('original_filename', 'like', '%'.$this->search.'%')
                        ->orWhere('alt_text', 'like', '%'.$this->search.'%');
                });
            }

            if ($this->filterType !== 'all') {
                $query->where('file_type', $this->filterType);
            }

            $total = $query->count();
            $totalPages = max(1, (int) ceil($total / $this->perPage));
            $this->page = min($this->page, $totalPages);

            $media = $query
                ->skip(($this->page - 1) * $this->perPage)
                ->take($this->perPage)
                ->get();

            if ($this->selectedMediaId !== null) {
                $selectedMedia = MediaLibrary::find($this->selectedMediaId);
            }
        }

        return view('livewire.admin.media-library.picker-modal', compact(
            'media', 'total', 'totalPages', 'selectedMedia'
        ));
    }
}
