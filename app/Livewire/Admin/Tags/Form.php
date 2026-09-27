<?php

namespace App\Livewire\Admin\Tags;

use App\Concerns\HasTranslatableFields;
use App\Models\Tag;
use App\Models\Type;
use App\Support\AdminActivity;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    use HasTranslatableFields;

    public ?int $tagId = null;

    public array $name = [];

    /**
     * Which pool the tag belongs to — post tags show on the Post form, product
     * tags on the Product form. Required, with no "shared (both)" option: a tag
     * that belonged to neither pool was never really a tag, so every row now
     * picks exactly one (see the add_type_id_to_categories migration).
     */
    #[Validate('required|integer|exists:types,id')]
    public ?int $typeId = null;

    public function mount(?int $id = null): void
    {
        if (! $id) {
            $this->typeId = $this->defaultTypeId();

            return;
        }

        $tag = Tag::findOrFail($id);
        $this->tagId = $id;
        $this->hydrateTranslatable($tag, ['name']);
        $this->typeId = $tag->type_id;
    }

    public function save(): void
    {
        $rules = array_merge($this->getRules(), $this->translatableRules([
            'name' => 'required|string|max:255',
        ]));

        // Uniqueness is enforced against tag rows only (kind = tag); the column
        // is the JSON path, so the primary locale's value is what's compared —
        // a category or brand sharing the string is fine.
        $rules['name.'.$this->primaryLocale][] = Rule::unique('categories', 'name->'.$this->primaryLocale)
            ->where(fn ($q) => $q->where('kind', Tag::KIND))
            ->ignore($this->tagId);

        $this->validate($rules);

        $creating = $this->tagId === null;

        $data = [
            'name' => $this->translatablePayload('name'),
            'type_id' => $this->typeId,
        ];

        if ($this->tagId) {
            Tag::findOrFail($this->tagId)->update($data);
            $this->dispatch('notify', message: 'Tag updated successfully');
        } else {
            // New tags stay inactive until switched on from the list — status is
            // no longer editable from this form, see Index::toggleStatus().
            $data['status'] = 'inactive';
            Tag::create($data);
            $this->dispatch('notify', message: 'Tag created successfully');
        }

        AdminActivity::log(
            $creating ? 'created' : 'updated',
            "Tag: {$this->primaryValue('name')}",
        );

        $this->redirect(route('admin.tags'), navigate: true);
    }

    /**
     * @return Collection<int, Type>
     */
    #[Computed]
    public function typeOptions()
    {
        return Type::selectOptions();
    }

    /**
     * New tags arrive here from the Tags list's "New tag" button and from the
     * inline "create tag" input on the Post and Product forms, so preselect the
     * Post pool — the same default the form used before types were a table.
     */
    private function defaultTypeId(): ?int
    {
        return Type::idFor(Type::POST) ?? Type::idFor(Type::PRODUCT);
    }

    public function render()
    {
        return view('livewire.admin.tags.form')
            ->layout('layouts.admin', ['title' => $this->tagId ? 'Edit Tag' : 'New Tag']);
    }
}
