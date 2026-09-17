<?php

namespace App\Livewire\Admin\Reviews;

use App\Concerns\HasPerPage;
use App\Models\Post;
use App\Models\Product;
use App\Models\Review;
use App\Models\Service;
use App\Support\AdminActivity;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use HasPerPage, WithPagination;

    /**
     * Friendly type name (shown in the filter/table) → model class — kept in
     * sync with ReviewController::REVIEWABLE_TYPES.
     */
    private const TYPES = [
        'post' => Post::class,
        'product' => Product::class,
        'service' => Service::class,
    ];

    public string $search = '';

    public string $statusFilter = '';

    public string $typeFilter = '';

    public string $ratingFilter = '';

    public ?int $viewingId = null;

    public ?int $deletingId = null;

    /** @var array<int, int> */
    public array $selectedIds = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedRatingFilter(): void
    {
        $this->resetPage();
    }

    public function updateStatus(int $id, string $status): void
    {
        $review = Review::findOrFail($id);
        $review->update(['status' => $status]);

        AdminActivity::log('updated', "Review #{$review->id} marked {$status}");
        $this->dispatch('notify', message: 'Review status updated');
    }

    public function viewDetails(int $id): void
    {
        $this->viewingId = $id;
    }

    public function closeDetails(): void
    {
        $this->viewingId = null;
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'review-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $review = Review::findOrFail($this->deletingId);
            AdminActivity::log('deleted', "Review #{$review->id}");
            $review->delete();
            $this->dispatch('notify', message: 'Review deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'review-delete');
    }

    /**
     * Ctrl/Cmd+click row selection or the row's own checkbox (see the view) —
     * toggles one review id in/out of the bulk-selection.
     */
    public function toggleSelect(int $id): void
    {
        if (in_array($id, $this->selectedIds, true)) {
            $this->selectedIds = array_values(array_diff($this->selectedIds, [$id]));

            return;
        }

        $this->selectedIds[] = $id;
    }

    public function confirmBulkDelete(): void
    {
        if ($this->selectedIds === []) {
            return;
        }

        $this->dispatch('open-modal', name: 'review-bulk-delete');
    }

    public function bulkDelete(): void
    {
        $reviews = Review::whereIn('id', $this->selectedIds)->get();

        foreach ($reviews as $review) {
            AdminActivity::log('deleted', "Review #{$review->id}");
            $review->delete();
        }

        $count = $reviews->count();
        $this->selectedIds = [];

        $this->dispatch('notify', message: "{$count} ".Str::plural('review', $count).' deleted successfully');
        $this->dispatch('close-modal', name: 'review-bulk-delete');
    }

    /**
     * The friendly type label ('Post'/'Product'/'Service') for a review row —
     * the FQCN is what's actually stored in reviewable_type.
     */
    public function typeLabel(string $reviewableType): string
    {
        $friendly = array_search($reviewableType, self::TYPES, true);

        return $friendly ? ucfirst($friendly) : 'Unknown';
    }

    public function render()
    {
        return view('livewire.admin.reviews.index', [
            'reviews' => Review::query()
                ->with(['user', 'reviewable'])
                ->when($this->search, fn ($q) => $q->where(fn ($inner) => $inner
                    ->where('title', 'like', "%{$this->search}%")
                    ->orWhere('body', 'like', "%{$this->search}%")
                    ->orWhereHas('user', fn ($q2) => $q2->where('name', 'like', "%{$this->search}%"))))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->when($this->typeFilter && isset(self::TYPES[$this->typeFilter]), fn ($q) => $q->where('reviewable_type', self::TYPES[$this->typeFilter]))
                ->when($this->ratingFilter !== '', fn ($q) => $q->where('rating', $this->ratingFilter))
                ->latest()
                ->paginate($this->perPage),
        ])->layout('layouts.admin', ['title' => 'Reviews', 'hidePageHeading' => true]);
    }
}
