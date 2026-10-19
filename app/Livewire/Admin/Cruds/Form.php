<?php

namespace App\Livewire\Admin\Cruds;

use App\Models\Crud;
use App\Models\MenuItem;
use App\Support\AdminActivity;
use App\Support\Cruds;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    public ?int $crudId = null;

    public string $name = '';

    public string $icon = 'rectangle-stack';

    /**
     * A field with a `name` is an existing database column (its type is fixed);
     * one without is new. Names are re-checked against the stored definition on
     * save, so a tampered payload cannot claim a column.
     *
     * @var array<int, array{name?: string, label: string, type: string, required: bool, in_list: bool, options: string}>
     */
    public array $fields = [];

    public function mount(?int $id = null): void
    {
        if ($id === null) {
            $this->addField();

            return;
        }

        $crud = Crud::findOrFail($id);
        $this->crudId = $crud->id;
        $this->name = $crud->name;
        $this->icon = $crud->icon;
        $this->fields = $crud->fields;
    }

    public function pickIcon(string $icon): void
    {
        if (MenuItem::iconExists($icon)) {
            $this->icon = $icon;
        }

        $this->js("\$flux.modal('crud-icon-picker').close()");
    }

    public function addField(): void
    {
        $this->fields[] = ['label' => '', 'type' => 'text', 'required' => false, 'in_list' => true, 'options' => ''];
    }

    public function removeField(int $index): void
    {
        unset($this->fields[$index]);
        $this->fields = array_values($this->fields);
    }

    public function moveField(int $index, int $direction): void
    {
        $target = $index + $direction;

        if (! isset($this->fields[$index], $this->fields[$target])) {
            return;
        }

        [$this->fields[$index], $this->fields[$target]] = [$this->fields[$target], $this->fields[$index]];
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('cruds', 'name')->ignore($this->crudId)],
            'icon' => ['required', function ($attr, $value, $fail) {
                if (! MenuItem::iconExists($value)) {
                    $fail('That icon does not exist. Use a Heroicon name such as "users" or "cube".');
                }
            }],
            'fields' => ['required', 'array', 'min:1', 'max:40'],
            'fields.*.label' => ['required', 'string', 'max:60'],
            'fields.*.type' => ['required', Rule::in(array_keys(Cruds::TYPES))],
            'fields.*.options' => ['nullable', 'string', 'max:500'],
        ]);

        foreach ($this->fields as $i => $field) {
            if ($field['type'] === 'select' && Cruds::options($field) === []) {
                $this->addError("fields.$i.options", 'Add at least one option, separated by commas.');

                return;
            }
        }

        $this->crudId ? $this->update() : $this->create();

        $this->redirectRoute('admin.cruds', navigate: true);
    }

    private function create(): void
    {
        $slug = Str::slug($this->name);
        $table = Cruds::tableFor($this->name);

        if ($slug === '' || ! ctype_alpha($slug[0])) {
            $this->addError('name', 'The name must start with a letter.');

            return;
        }

        if (Schema::hasTable($table) || Crud::where('slug', $slug)->exists()) {
            $this->addError('name', 'A table for this name already exists.');

            return;
        }

        $fields = array_map(function (array $field) {
            unset($field['name']);

            return $field;
        }, $this->fields);

        $crud = new Crud([
            'name' => $this->name,
            'slug' => $slug,
            'table_name' => $table,
            'icon' => $this->icon,
            'fields' => Cruds::nameFields($fields),
        ]);

        Cruds::createTable($crud);

        try {
            $crud->save();
        } catch (\Throwable $e) {
            Cruds::dropTable($crud);
            throw $e;
        }

        AdminActivity::log('created', "CRUD: {$crud->name}");
        $this->dispatch('notify', message: 'CRUD created — it is in the sidebar now');
    }

    private function update(): void
    {
        $crud = Crud::findOrFail($this->crudId);
        $existing = collect($crud->fields)->keyBy('name');

        $slots = [];
        $newFields = [];
        $kept = [];

        foreach ($this->fields as $field) {
            $name = $field['name'] ?? null;

            if ($name && $existing->has($name) && ! in_array($name, $kept, true)) {
                $old = $existing[$name];
                $kept[] = $name;
                // The column keeps its name and type; only what the screens show can change.
                $slots[] = array_merge($old, [
                    'label' => $field['label'],
                    'required' => (bool) $field['required'],
                    'in_list' => (bool) $field['in_list'],
                    'options' => $old['type'] === 'select' ? $field['options'] : ($old['options'] ?? ''),
                ]);

                continue;
            }

            unset($field['name']);
            $slots[] = null;
            $newFields[] = $field;
        }

        $newFields = Cruds::nameFields($newFields, $existing->keys()->all());

        $final = [];
        foreach ($slots as $slot) {
            $final[] = $slot ?? array_shift($newFields);
        }
        $added = array_values(array_filter($final, fn ($f) => ! $existing->has($f['name'])));

        Cruds::dropColumns($crud, $existing->keys()->diff($kept)->values()->all());

        if ($added !== []) {
            Cruds::addColumns($crud, $added);
        }

        $crud->update(['name' => $this->name, 'icon' => $this->icon, 'fields' => $final]);

        AdminActivity::log('updated', "CRUD: {$crud->name}");
        $this->dispatch('notify', message: 'CRUD updated');
    }

    public function render()
    {
        $existingNames = $this->crudId
            ? array_column(Crud::findOrFail($this->crudId)->fields, 'name')
            : [];

        return view('livewire.admin.cruds.form', [
            'types' => Cruds::TYPES,
            'iconChoices' => array_values(array_filter(Cruds::ICONS, [MenuItem::class, 'iconExists'])),
            'existingNames' => $existingNames,
        ])->layout('layouts.admin', ['title' => $this->crudId ? 'Edit CRUD' : 'New CRUD']);
    }
}
