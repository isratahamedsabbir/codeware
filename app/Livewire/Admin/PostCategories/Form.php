<?php

namespace App\Livewire\Admin\PostCategories;

use App\Models\PostCategory;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    public ?int $categoryId = null;

    #[Validate('required|string|max:255')]
    public string $name_en = '';

    #[Validate('nullable|string|max:255')]
    public string $name_bn = '';

    #[Validate('nullable|string|max:255')]
    public string $slug = '';

    #[Validate('nullable|string')]
    public string $description_en = '';

    #[Validate('nullable|string')]
    public string $description_bn = '';

    #[Validate('nullable|string|max:255')]
    public string $seo_title_en = '';

    #[Validate('nullable|string|max:255')]
    public string $seo_title_bn = '';

    #[Validate('nullable|string')]
    public string $seo_description_en = '';

    #[Validate('nullable|string')]
    public string $seo_description_bn = '';

    #[Validate('nullable|integer|min:0')]
    public int $sort_order = 0;

    #[Validate('in:active,inactive')]
    public string $status = 'active';

    public function mount(?int $id = null): void
    {
        if ($id) {
            $category = PostCategory::findOrFail($id);
            $this->categoryId              = $id;
            $this->name_en                 = $category->getTranslation('name', 'en', false) ?? '';
            $this->name_bn                 = $category->getTranslation('name', 'bn', false) ?? '';
            $this->slug                    = $category->slug;
            $this->description_en          = $category->getTranslation('description', 'en', false) ?? '';
            $this->description_bn          = $category->getTranslation('description', 'bn', false) ?? '';
            $this->seo_title_en            = $category->getTranslation('seo_title', 'en', false) ?? '';
            $this->seo_title_bn            = $category->getTranslation('seo_title', 'bn', false) ?? '';
            $this->seo_description_en      = $category->getTranslation('seo_description', 'en', false) ?? '';
            $this->seo_description_bn      = $category->getTranslation('seo_description', 'bn', false) ?? '';
            $this->sort_order              = $category->sort_order;
            $this->status                  = $category->status;
        }
    }

    public function save(): void
    {
        if (empty($this->slug) && $this->name_en) {
            $this->slug = Str::slug($this->name_en);
        }

        $rules = $this->getRules();
        $rules['slug'] = $this->categoryId
            ? 'required|string|max:255|unique:categories,slug,' . $this->categoryId . ',id,type,post'
            : 'required|string|max:255|unique:categories,slug,NULL,id,type,post';

        $this->validate($rules);

        $data = [
            'name'           => array_filter(['en' => $this->name_en, 'bn' => $this->name_bn]),
            'slug'           => $this->slug,
            'description'    => array_filter(['en' => $this->description_en, 'bn' => $this->description_bn]) ?: null,
            'seo_title'      => array_filter(['en' => $this->seo_title_en, 'bn' => $this->seo_title_bn]) ?: null,
            'seo_description' => array_filter(['en' => $this->seo_description_en, 'bn' => $this->seo_description_bn]) ?: null,
            'sort_order'     => $this->sort_order,
            'status'         => $this->status,
        ];

        if ($this->categoryId) {
            PostCategory::findOrFail($this->categoryId)->update($data);
            $this->dispatch('notify', message: 'Category updated successfully');
        } else {
            PostCategory::create($data);
            $this->dispatch('notify', message: 'Category created successfully');
        }

        $this->redirect(route('admin.post-categories'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.post-categories.form')
            ->layout('layouts.admin', ['title' => $this->categoryId ? 'Edit Post Category' : 'New Post Category']);
    }
}
