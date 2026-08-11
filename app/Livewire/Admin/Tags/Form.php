<?php

namespace App\Livewire\Admin\Tags;

use App\Models\Tag;
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

    #[Validate('in:active,inactive')]
    public string $status = 'active';

    public function mount(?int $id = null): void
    {
        if ($id) {
            $tag = Tag::findOrFail($id);
            $this->tagId  = $id;
            $this->name_en = $tag->getTranslation('name', 'en', false) ?? '';
            $this->name_bn = $tag->getTranslation('name', 'bn', false) ?? '';
            $this->slug    = $tag->slug;
            $this->status  = $tag->status;
        }
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => array_filter(['en' => $this->name_en, 'bn' => $this->name_bn]),
            'slug' => $this->slug,
            'status' => $this->status,
        ];

        if ($this->tagId) {
            Tag::findOrFail($this->tagId)->update($data);
            $this->dispatch('notify', message: 'Tag updated successfully');
        } else {
            Tag::create($data);
            $this->dispatch('notify', message: 'Tag created successfully');
        }

        $this->redirect(route('admin.tags'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.tags.form')
            ->layout('layouts.admin', ['title' => $this->tagId ? 'Edit Tag' : 'New Tag']);
    }
}
