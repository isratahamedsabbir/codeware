<?php

namespace App\Livewire\Admin\Types;

use App\Concerns\HasTranslatableFields;
use App\Models\Type;
use App\Support\AdminActivity;
use App\Support\Slug;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    use HasTranslatableFields;

    public ?int $typeId = null;

    public array $name = [];

    public string $slug = '';

    public int $sortOrder = 0;

    public function mount(?int $id = null): void
    {
        if (! $id) {
            return;
        }

        $type = Type::findOrFail($id);
        $this->typeId = $id;
        $this->hydrateTranslatable($type, ['name']);
        $this->slug = $type->slug;
        $this->sortOrder = $type->sort_order;
    }

    public function save(): void
    {
        // The model derives a blank slug from the primary name on save, so
        // resolve it here first: `nullable` makes a blank field skip the unique
        // check (Laravel compares against NULL, which matches nothing), and the
        // derived value would then trip the unique index at INSERT time as a raw
        // 500 instead of a validation message. Guarded on having a name so an
        // empty form still fails on `name`, not on a confusing empty slug.
        if (blank($this->slug) && filled($this->primaryValue('name'))) {
            $this->slug = Slug::make($this->primaryValue('name'));
        }

        $rules = array_merge($this->getRules(), $this->translatableRules([
            'name' => 'required|string|max:255',
        ]), [
            'slug' => ['nullable', 'string', 'max:30', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'sortOrder' => 'required|integer|min:0',
        ]);

        $rules['name.'.$this->primaryLocale][] = Rule::unique('types', 'name->'.$this->primaryLocale)
            ->ignore($this->typeId);

        // Slugs are the stable key the rest of the system keys off (Type::idFor,
        // pages.type, the pool subqueries), so a rename must not collide.
        $rules['slug'][] = Rule::unique('types', 'slug')->ignore($this->typeId);

        $this->validate($rules);

        $data = [
            'name' => $this->translatablePayload('name'),
            'slug' => $this->slug ?: null,
            'sort_order' => $this->sortOrder,
        ];

        $creating = $this->typeId === null;

        if ($this->typeId) {
            // Status is toggled from the index list (see Index::toggleStatus()),
            // not this form — omit it here so saving never silently reactivates a
            // type an admin had pulled out of circulation.
            Type::findOrFail($this->typeId)->update($data);
            $this->dispatch('notify', message: 'Type updated successfully');
        } else {
            $data['status'] = 'active';
            Type::create($data);
            $this->dispatch('notify', message: 'Type created successfully');
        }

        AdminActivity::log(
            $creating ? 'created' : 'updated',
            "Type: {$this->primaryValue('name')}",
        );

        $this->redirect(route('admin.types'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.types.form')
            ->layout('layouts.admin', ['title' => $this->typeId ? 'Edit Type' : 'New Type']);
    }
}
