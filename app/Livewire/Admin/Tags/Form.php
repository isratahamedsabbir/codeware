<?php

namespace App\Livewire\Admin\Tags;

use App\Models\Tag;
use App\Support\AdminActivity;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    public ?int $tagId = null;

    #[Validate('required|string|max:255')]
    public string $name_en = '';

    #[Validate('nullable|string|max:255')]
    public string $name_bn = '';

    #[Validate('nullable|string|max:255')]
    public string $slug = '';

    public function mount(?int $id = null): void
    {
        if ($id) {
            $tag = Tag::findOrFail($id);
            $this->tagId = $id;
            $this->name_en = $tag->getTranslation('name', 'en', false) ?? '';
            $this->name_bn = $tag->getTranslation('name', 'bn', false) ?? '';
            $this->slug = $tag->slug;
        }
    }

    public function save(): void
    {
        if (empty($this->slug) && $this->name_en) {
            $this->slug = Str::slug($this->name_en);
        }

        $rules = $this->getRules();
        $rules['slug'] = $this->tagId
            ? 'required|string|max:255|unique:tags,slug,'.$this->tagId
            : 'required|string|max:255|unique:tags,slug';

        $this->validate($rules);

        $creating = $this->tagId === null;

        $data = [
            'name' => array_filter(['en' => $this->name_en, 'bn' => $this->name_bn]),
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
            "Tag: {$this->name_en}",
        );

        $this->redirect(route('admin.tags'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.tags.form')
            ->layout('layouts.admin', ['title' => $this->tagId ? 'Edit Tag' : 'New Tag']);
    }
}
