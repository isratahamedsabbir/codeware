<?php

namespace App\Livewire\Admin\MediaLibrary;

use App\Models\MediaLibrary;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;
    use WithPagination;

    public string $search = '';

    public string $filterType = 'all'; // all, image, document, video, audio

    public ?int $selectedMediaId = null;

    public bool $bulkMode = false;

    /** @var array<int, int> */
    public array $selectedMediaIds = [];

    public bool $showUploadModal = false;

    public bool $showDetailsModal = false;

    public ?int $editingMediaId = null;

    // Upload form
    public array $uploadFiles = [];

    public int $uploadIteration = 0;

    // Edit form
    public string $editTitle = '';

    public string $editAltText = '';

    public string $editCaption = '';

    public string $editDescription = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterType(): void
    {
        $this->resetPage();
    }

    public function openUploadModal(): void
    {
        $this->authorize('create', MediaLibrary::class);
        $this->uploadFiles = [];
        $this->uploadIteration++;
        $this->showUploadModal = true;
    }

    public function closeUploadModal(): void
    {
        $this->uploadFiles = [];
        $this->uploadIteration++;
        $this->showUploadModal = false;
    }

    public function saveUploads(): void
    {
        $this->authorize('create', MediaLibrary::class);

        $this->validate([
            'uploadFiles.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,webp,pdf,mp4,mp3,doc,docx,xls,xlsx',
        ]);

        foreach ($this->uploadFiles as $file) {
            $this->processUploadedFile($file);
        }

        $this->closeUploadModal();
        $this->dispatch('notify', message: 'Files uploaded successfully');
    }

    private function processUploadedFile($file): void
    {
        $path = $file->store('media', 'public');
        $mimeType = $file->getMimeType();
        $fileType = $this->getFileType($mimeType);

        $metadata = [];
        if ($fileType === 'image') {
            $imageSize = getimagesize($file->getRealPath());
            if ($imageSize) {
                $metadata['width'] = $imageSize[0];
                $metadata['height'] = $imageSize[1];
            }
        }

        MediaLibrary::create([
            'filename' => basename($path),
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $mimeType,
            'file_type' => $fileType,
            'file_size' => $file->getSize(),
            'disk' => 'public',
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'uploaded_by' => auth()->id(),
            'metadata' => $metadata,
        ]);
    }

    private function getFileType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        } elseif (str_starts_with($mimeType, 'video/')) {
            return 'video';
        } elseif (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        }

        return 'document';
    }

    public function selectMedia(int $mediaId): void
    {
        if ($this->bulkMode) {
            if (in_array($mediaId, $this->selectedMediaIds, true)) {
                $this->selectedMediaIds = array_values(array_diff($this->selectedMediaIds, [$mediaId]));
            } else {
                $this->selectedMediaIds[] = $mediaId;
            }

            return;
        }

        $this->selectedMediaId = $this->selectedMediaId === $mediaId ? null : $mediaId;
    }

    public function toggleBulkMode(): void
    {
        $this->bulkMode = ! $this->bulkMode;
        $this->selectedMediaId = null;
        $this->selectedMediaIds = [];
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
