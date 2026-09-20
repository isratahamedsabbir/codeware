<?php

namespace App\Livewire\Admin\Tags;

use App\Concerns\HasTranslatableFields;
use App\Models\Tag;
use App\Support\AdminActivity;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    use HasTranslatableFields;

    public ?int $tagId = null;

    public array $name = [];

    /**
     * Which pool the tag belongs to — post tags show on the Post form, product
     * tags on the Product form. Legacy tags (pre-split rows) stay selectable so
     * they can be migrated into one of the two pools.
     */
    #[Validate('required|in:post,product,tag')]
    public string $type = Tag::TYPE_POST;

    public function mount(?int $id = null): void
    {
        if ($id) {
            $tag = Tag::findOrFail($id);
            $this->tagId = $id;
            $this->hydrateTranslatable($tag, ['name']);
            $this->type = $tag->type;
        }
    }

    public function save(): void
    {
        $rules = array_merge($this->getRules(), $this->translatableRules([
            'name' => 'required|string|max:255',
        ]));

        // Uniqueness is enforced against tag rows only (post/product/legacy);
        // the column is the JSON path, so the primary locale's value is what's
        // compared — a category or brand sharing the string is fine.
        $rules['name.'.$this->primaryLocale][] = Rule::unique('categories', 'name->'.$this->primaryLocale)
            ->where(fn ($q) => $q->whereIn('type', Tag::TYPES)->orWhereNull('type'))
            ->ignore($this->tagId);

        $this->validate($rules);

        $creating = $this->tagId === null;

        $data = [
            'name' => $this->translatablePayload('name'),
            'type' => $this->type,
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

    public function render()
    {
        return view('livewire.admin.tags.form')
            ->layout('layouts.admin', ['title' => $this->tagId ? 'Edit Tag' : 'New Tag']);
    }
}
