<?php

namespace App\Livewire\Admin\Cruds;

use App\Models\Crud;
use App\Models\MenuItem;
use App\Support\AdminActivity;
use App\Support\Cruds;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class Index extends Component
{
    public ?int $deletingId = null;

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'crud-delete');
    }

    public function delete(): void
    {
        $crud = Crud::findOrFail($this->deletingId);

        Cruds::dropTable($crud);
        AdminActivity::log('deleted', "CRUD: {$crud->name}");
        $crud->delete();

        // The sidebar caches its menu rows; the CRUD links themselves are read live.
        MenuItem::flushCache();
        $this->deletingId = null;
        $this->dispatch('close-modal', name: 'crud-delete');
        $this->dispatch('notify', message: 'CRUD and its table deleted');
    }

    public function render()
    {
        return view('livewire.admin.cruds.index', [
            'cruds' => Crud::query()->orderBy('name')->get(),
            'counts' => Crud::query()->get()->mapWithKeys(fn (Crud $c) => [
                $c->id => Schema::hasTable($c->table_name) ? DB::table($c->table_name)->count() : 0,
            ]),
        ])->layout('layouts.admin', ['title' => 'CRUD Builder', 'hidePageHeading' => true]);
    }
}
