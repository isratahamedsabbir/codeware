<?php

namespace App\Livewire\Admin\Types;

use App\Concerns\HasBulkSelection;
use App\Concerns\HasPerPage;
use App\Concerns\WithSearch;
use App\Models\Type;
use App\Support\AdminActivity;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use HasBulkSelection, HasPerPage, WithPagination, WithSearch;

    public string $statusFilter = '';

    public ?int $deletingId = null;

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(int $id): void
    {
        $type = Type::findOrFail($id);
        $newStatus = $type->status === 'active' ? 'inactive' : 'active';

        $type->update(['status' => $newStatus]);

        AdminActivity::log('updated', "Type: {$type->name} ".($newStatus === 'active' ? 'activated' : 'deactivated'));
        $this->dispatch('notify', message: 'Type status updated');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'type-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $type = Type::findOrFail($this->deletingId);

            if ($this->isInUse($type)) {
                $this->deletingId = null;
                $this->dispatch('notify', message: "{$type->name} is still assigned to categories, brands or tags. Deactivate it instead.");
                $this->dispatch('close-modal', name: 'type-delete');

                return;
            }

            AdminActivity::log('deleted', "Type: {$type->name}");
            $type->delete();
            $this->dispatch('notify', message: 'Type deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'type-delete');
    }

    public function confirmBulkDelete(): void
    {
        if ($this->selectedIds === []) {
            return;
        }

        $this->dispatch('open-modal', name: 'type-bulk-delete');
    }

    public function bulkDelete(): void
    {
        $types = Type::whereIn('id', $this->selectedIds)->get();
        $count = 0;
        $skipped = 0;

        foreach ($types as $type) {
            if ($this->isInUse($type)) {
                $skipped++;

                continue;
            }

            AdminActivity::log('deleted', "Type: {$type->name}");
            $type->delete();
            $count++;
        }

        $this->selectedIds = [];

        // A bulk run is a mixed bag far more often than a single delete, so say
        // what was left behind instead of letting it look like a full success.
        $message = "{$count} ".Str::plural('type', $count).' deleted successfully';

        if ($skipped > 0) {
            $message .= " — {$skipped} still in use, deactivate ".($skipped === 1 ? 'it' : 'them').' instead';
        }

        $this->dispatch('notify', message: $message);
        $this->dispatch('close-modal', name: 'type-bulk-delete');
    }

    public function render()
    {
        return view('livewire.admin.types.index', [
            'types' => Type::query()
                ->withCount(['categories', 'brands', 'tags'])
                ->when($this->search, fn ($q) => $q->where('name->en', 'like', "%{$this->search}%")
                    ->orWhere('name->bn', 'like', "%{$this->search}%")
                    ->orWhere('slug', 'like', "%{$this->search}%"))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->orderBy('sort_order')
                ->orderBy('id')
                ->paginate($this->perPage),
        ])->layout('layouts.admin', ['title' => 'Types', 'hidePageHeading' => true]);
    }

    /**
     * Every category, brand and tag in that type would be left pointing at a row
     * nothing renders any more, so a Type in use can only be deactivated from
     * this screen — that takes its rows out of circulation without orphaning
     * them.
     *
     * A pure predicate on purpose: reporting is left to the caller so a bulk
     * delete sends one summary notification rather than one per skipped row.
     * SoftDeletes would otherwise hide a type that other rows still point at.
     */
    private function isInUse(Type $type): bool
    {
        return $type->categories()->exists()
            || $type->brands()->exists()
            || $type->tags()->exists();
    }
}
