<?php

namespace App\Livewire\Admin\Testimonials;

use App\Models\Testimonial;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public ?int $deletingId = null;

    public function updatedSearch(): void { $this->resetPage(); }

    public function updatedStatusFilter(): void { $this->resetPage(); }

    public function reorder(array $order): void
    {
        foreach ($order as $sortOrder => $testimonialId) {
            Testimonial::where('id', $testimonialId)->update(['sort_order' => $sortOrder]);
        }
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'testimonial-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            Testimonial::findOrFail($this->deletingId)->delete();
            $this->dispatch('notify', message: 'Testimonial deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'testimonial-delete');
    }

    public function render()
    {
        return view('livewire.admin.testimonials.index', [
            'testimonials' => Testimonial::query()
                ->when($this->search, fn ($q) => $q
                    ->where('name->en', 'like', "%{$this->search}%")
                    ->orWhere('name->bn', 'like', "%{$this->search}%")
                    ->orWhere('comment->en', 'like', "%{$this->search}%"))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->orderBy('sort_order')
                ->orderBy('id')
                ->paginate(20),
        ])->layout('layouts.admin', ['title' => 'Testimonials']);
    }
}
