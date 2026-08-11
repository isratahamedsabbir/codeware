<?php

namespace App\Livewire\Admin\MediaLibrary;

use App\Models\MediaLibrary;
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

    public string $activeTab = 'library'; // library | upload

    /** @var array<int, mixed> */
    public array $uploadFiles = [];

    public int $uploadIteration = 0;

    public int $page = 1;

    public int $perPage = 32;

    public function openPicker(string $pickerId): void
    {
        $this->pickerId = $pickerId;
        $this->show = true;
        $this->selectedMediaId = null;
        $this->search = '';
        $this->filterType = 'all';
        $this->activeTab = 'library';
        $this->page = 1;
        $this->uploadFiles = [];
        $this->uploadIteration++;
    }

    public function closePicker(): void
    {
        $this->show = false;
        $this->selectedMediaId = null;
        $this->pickerId = '';
        $this->dispatch('media-picker-closed');
    }

    public function selectMedia(int $id): void
    {
        $this->selectedMediaId = $this->selectedMediaId === $id ? null : $id;
    }

    public function confirmSelection(): void
    {
        if ($this->selectedMediaId === null) {
            return;
        }

        $media = MediaLibrary::find($this->selectedMediaId);

        if ($media === null) {
            return;
        }

        $this->dispatch('mediaPickerSelected',
            pickerId: $this->pickerId,
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
            'uploadFiles.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,webp,pdf,mp4,mp3,doc,docx,xls,xlsx',
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
        }

        $this->dispatch('notify', message: 'Files uploaded successfully');
    }

    private function processUploadedFile(mixed $file): int
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

        $media = MediaLibrary::create([
            'filename'          => basename($path),
            'original_filename' => $file->getClientOriginalName(),
            'mime_type'         => $mimeType,
            'file_type'         => $fileType,
            'file_size'         => $file->getSize(),
            'disk'              => 'public',
            'path'              => $path,
            'url'               => \Illuminate\Support\Facades\Storage::disk('public')->url($path),
            'uploaded_by'       => auth()->id(),
            'metadata'          => $metadata,
        ]);

        return $media->id;
    }

    private function getFileType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        if (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        }

        return 'document';
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
