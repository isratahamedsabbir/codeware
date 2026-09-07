<?php

namespace App\Livewire\Admin\Tags;

use App\Concerns\HasTranslatableFields;
use App\Models\Tag;
use App\Support\AdminActivity;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    use HasTranslatableFields;

    public ?int $tagId = null;

    public array $name = [];

    #[Validate('nullable|string|max:255')]
    public string $slug = '';

    public function mount(?int $id = null): void
    {
        if ($id) {
            $tag = Tag::findOrFail($id);
            $this->tagId = $id;
            $this->hydrateTranslatable($tag, ['name']);
            $this->slug = $tag->slug;
        }
    }

    public function save(): void
    {
        if (empty($this->slug) && $this->primaryValue('name')) {
            $this->slug = Str::slug($this->primaryValue('name'));
        }

        $rules = array_merge($this->getRules(), $this->translatableRules([
            'name' => 'required|string|max:255',
        ]));
        $rules['slug'] = $this->tagId
            ? 'required|string|max:255|unique:tags,slug,'.$this->tagId
            : 'required|string|max:255|unique:tags,slug';

        $this->validate($rules);

        $creating = $this->tagId === null;

        $data = [
            'name' => $this->translatablePayload('name'),
            'slug' => $this->slug,
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
