<?php

namespace App\Livewire\Admin\Cruds;

use App\Concerns\HasBulkSelection;
use App\Concerns\HasPerPage;
use App\Models\Crud;
use App\Support\AdminActivity;
use App\Support\Cruds;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/** The generic list / create / edit / delete screen for any generated CRUD. */
class Records extends Component
{
    use HasBulkSelection, HasPerPage, WithFileUploads, WithPagination;

    public string $slug;

    public string $search = '';

    /** Exact-match filters on the select / yes-no fields, keyed by field name. */
    public array $filters = [];

    public string $sortBy = 'id';

    public string $sortDir = 'desc';

    public ?int $editingId = null;

    public array $form = [];

    /** Freshly chosen image files, keyed by field name. */
    public array $uploads = [];

    public ?int $deletingId = null;

    public function mount(string $slug): void
    {
        $this->slug = $slug;
        $this->crud();
    }

    private function crud(): Crud
    {
        return Crud::where('slug', $this->slug)->firstOrFail();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->selectedIds = [];
    }

    public function updatedFilters(): void
    {
        $this->resetPage();
        $this->selectedIds = [];
    }

    public function sort(string $column): void
    {
        if (! in_array($column, array_merge(['id'], array_column($this->crud()->fields, 'name')), true)) {
            return;
        }

        $this->sortDir = ($this->sortBy === $column && $this->sortDir === 'asc') ? 'desc' : 'asc';
        $this->sortBy = $column;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'filters');
        $this->resetPage();
    }

    private function blankForm(Crud $crud): array
    {
        return collect($crud->fields)
            ->mapWithKeys(fn ($f) => [$f['name'] => $f['type'] === 'boolean' ? false : ''])
            ->all();
    }

    public function create(): void
    {
        $this->editingId = null;
        $this->form = $this->blankForm($this->crud());
        $this->uploads = [];
        $this->resetValidation();
        $this->dispatch('open-modal', name: 'crud-record-form');
    }

    public function edit(int $id): void
    {
        $crud = $this->crud();
        $record = $crud->recordModel()->newQuery()->findOrFail($id);

        $this->editingId = $id;
        $this->form = collect($crud->fields)->mapWithKeys(function ($f) use ($record) {
            $raw = $record->getRawOriginal($f['name']);
            $value = match ($f['type']) {
                'date' => $raw ? substr((string) $raw, 0, 10) : '',
                'datetime' => $raw ? str_replace(' ', 'T', substr((string) $raw, 0, 16)) : '',
                'boolean' => (bool) $raw,
                default => $raw ?? '',
            };

            return [$f['name'] => $value];
        })->all();
        $this->uploads = [];
        $this->resetValidation();
        $this->dispatch('open-modal', name: 'crud-record-form');
    }

    public function removeImage(string $name): void
    {
        if (array_key_exists($name, $this->form)) {
            $this->form[$name] = '';
        }
        unset($this->uploads[$name]);
    }

    public function save(): void
    {
        $crud = $this->crud();
        $rules = Cruds::rules($crud);

        foreach ($crud->fields as $f) {
            if ($f['type'] === 'image') {
                // The path is set from the upload below, never trusted from the client.
                $rules['form.'.$f['name']] = ['nullable', 'string', 'max:255'];
                $rules['uploads.'.$f['name']] = ['nullable', 'image', 'max:4096'];
            }
        }

        $this->validate($rules);

        $query = $crud->recordModel()->newQuery();
        $record = $this->editingId ? $query->findOrFail($this->editingId) : null;

        $data = [];
        $trash = [];

        foreach ($crud->fields as $f) {
            $name = $f['name'];
            $value = $this->form[$name] ?? null;

            if ($f['type'] === 'image') {
                $old = $record?->getRawOriginal($name);
                $upload = $this->uploads[$name] ?? null;

                if ($upload) {
                    $value = $upload->store('crud/'.$crud->slug, 'public');
                } else {
                    // Only the stored path of this very record may stay; anything else clears the field.
                    $value = ($value !== '' && $value === $old) ? $old : null;
                }

                if ($old && $old !== $value) {
                    $trash[] = $old;
                }

                if (($f['required'] ?? false) && ! $value) {
                    $this->addError('form.'.$name, 'This field is required.');

                    return;
                }
            }

            $data[$name] = $f['type'] === 'boolean' ? (bool) $value : ($value === '' ? null : $value);
        }

        $record ? $record->update($data) : $query->create($data);
        Storage::disk('public')->delete($trash);

        AdminActivity::log($record ? 'updated' : 'created', "{$crud->name} record");
        $this->uploads = [];
        $this->dispatch('close-modal', name: 'crud-record-form');
        $this->dispatch('notify', message: 'Saved');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'crud-record-delete');
    }

    public function delete(): void
    {
        $this->destroyRecords([$this->deletingId]);
        $this->deletingId = null;
        $this->dispatch('close-modal', name: 'crud-record-delete');
        $this->dispatch('notify', message: 'Deleted');
    }

    public function confirmBulkDelete(): void
    {
        if ($this->selectedIds !== []) {
            $this->dispatch('open-modal', name: 'crud-record-bulk-delete');
        }
    }

    public function bulkDelete(): void
    {
        $this->destroyRecords($this->selectedIds);
        $this->selectedIds = [];
        $this->dispatch('close-modal', name: 'crud-record-bulk-delete');
        $this->dispatch('notify', message: 'Records deleted');
    }

    /** @param array<int, int|null> $ids */
    private function destroyRecords(array $ids): void
    {
        $crud = $this->crud();
        $query = $crud->recordModel()->newQuery()->whereIn('id', array_filter($ids));
        $imageFields = collect($crud->fields)->where('type', 'image')->pluck('name');

        foreach ($query->get() as $record) {
            foreach ($imageFields as $name) {
                if ($path = $record->getRawOriginal($name)) {
                    Storage::disk('public')->delete($path);
                }
            }
        }

        $count = $query->delete();
        AdminActivity::log('deleted', "{$crud->name}: {$count} record(s)");
    }

    public function render()
    {
        $crud = $this->crud();
        $fields = collect($crud->fields);
        $query = $crud->recordModel()->newQuery();

        if ($this->search !== '') {
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $this->search).'%';
            $searchable = $fields->filter(fn ($f) => in_array($f['type'], ['text', 'textarea', 'email', 'url', 'select']));

            $query->where(function ($q) use ($searchable, $like) {
                foreach ($searchable as $f) {
                    $q->orWhere($f['name'], 'like', $like);
                }
            });
        }

        $filterable = $fields->filter(fn ($f) => in_array($f['type'], ['select', 'boolean']))->keyBy('name');

        foreach ($this->filters as $name => $value) {
            if ($value !== '' && $value !== null && $filterable->has($name)) {
                $query->where($name, $filterable[$name]['type'] === 'boolean' ? (bool) $value : $value);
            }
        }

        $sortable = $this->sortBy === 'id' || $fields->contains('name', $this->sortBy);
        $query->orderBy($sortable ? $this->sortBy : 'id', $this->sortDir === 'asc' ? 'asc' : 'desc');

        return view('livewire.admin.cruds.records', [
            'crud' => $crud,
            'listFields' => $fields->filter(fn ($f) => $f['in_list'] ?? true)->values(),
            'filterable' => $filterable,
            'records' => $query->paginate($this->perPage),
            'total' => $crud->recordModel()->newQuery()->count(),
        ])->layout('layouts.admin', ['title' => $crud->name, 'hidePageHeading' => true]);
    }
}
