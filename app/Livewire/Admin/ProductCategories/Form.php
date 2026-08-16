<?php

namespace App\Livewire\Admin\ProductCategories;

use App\Models\Page;
use App\Models\ProductCategory;
use App\Support\AdminActivity;
use App\Support\Slug;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    public ?int $categoryId = null;

    public ?int $pageId = null;

    #[Validate('required|string|max:255')]
    public string $name_en = '';

    #[Validate('nullable|string|max:255')]
    public string $name_bn = '';

    #[Validate('nullable|string|max:255')]
    public string $slug = '';

    /**
     * The last auto-generated slug value, so we know whether the admin has
     * manually diverged from it — see updatedNameEn().
     */
    public string $autoSlug = '';

    /**
     * null = not yet checked, true = available (green), false = taken (red).
     */
    public ?bool $slugAvailable = null;

    #[Validate('nullable|string')]
    public string $description_en = '';

    #[Validate('nullable|string')]
    public string $description_bn = '';

    #[Validate('nullable|string')]
    public ?string $icon = null;

    public string $iconPickerId = '';

    #[Validate('nullable|integer|min:0')]
    public int $sort_order = 0;

    #[Validate('in:active,inactive')]
    public string $status = 'active';

    public function mount(?int $id = null): void
    {
        $this->iconPickerId = 'icon-picker-'.Str::uuid()->toString();

        if ($id) {
            $cat = ProductCategory::findOrFail($id);
            $this->categoryId = $id;
            $this->name_en = $cat->getTranslation('name', 'en', false) ?? '';
            $this->name_bn = $cat->getTranslation('name', 'bn', false) ?? '';
            $this->slug = $cat->slug;
            $this->description_en = $cat->getTranslation('description', 'en', false) ?? '';
            $this->description_bn = $cat->getTranslation('description', 'bn', false) ?? '';
            $this->icon = $cat->icon ?? null;
            $this->sort_order = $cat->sort_order;
            $this->status = $cat->status ?? 'active';

            // SEO now lives entirely on the paired Page record, edited via the Page
            // screen — this form only keeps the Page in sync on title/slug/status.
            $this->pageId = $cat->page?->id;

            $this->checkSlugAvailability();
        }
    }

    /**
     * Live slug-as-you-type — regenerates from the English name only while the
     * slug still matches what we last auto-generated (i.e. the admin hasn't
     * typed a custom one), or is empty. Editing an existing category's name
     * never touches its already-set slug this way, since autoSlug starts
     * empty and never matches a loaded slug.
     */
    public function updatedNameEn(string $value): void
    {
        if ($this->slug === '' || $this->slug === $this->autoSlug) {
            $this->autoSlug = Slug::make($value);
            $this->slug = $this->autoSlug;
        }

        $this->checkSlugAvailability();
    }

    /**
     * Fires on direct manual edits to the slug field too, so the red/green
     * indicator stays accurate whether the slug came from auto-typing or a
     * deliberate override.
     */
    public function updatedSlug(): void
    {
        $this->checkSlugAvailability();
    }

    private function checkSlugAvailability(): void
    {
        $this->slugAvailable = Slug::isAvailable($this->slug, $this->pageId, 'categories', $this->categoryId);
    }

    public function save(): void
    {
        if (empty($this->slug) && $this->name_en) {
            $this->slug = Slug::make($this->name_en);
        }

        $rules = $this->getRules();
        $rules['slug'] = [
            'required', 'string', 'max:255',
            ...Slug::uniqueRules($this->pageId, 'categories', $this->categoryId),
        ];

        $this->validate($rules);

        $creating = $this->categoryId === null;

        $data = [
            'name' => array_filter(['en' => $this->name_en, 'bn' => $this->name_bn]),
            'slug' => $this->slug,
            'description' => array_filter(['en' => $this->description_en, 'bn' => $this->description_bn]) ?: null,
            'icon' => $this->icon ?: null,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
        ];

        if ($this->categoryId) {
            $category = ProductCategory::findOrFail($this->categoryId);
            $category->update($data);
            $this->dispatch('notify', message: 'Category updated successfully');
        } else {
            $category = ProductCategory::create($data);
            $this->categoryId = $category->id;
            $this->dispatch('notify', message: 'Category created successfully');
        }

        $page = Page::updateOrCreate(
            ['type' => 'product_category', 'category_id' => $category->id],
            [
                'user_id' => auth()->id(),
                'title' => array_filter(['en' => $this->name_en, 'bn' => $this->name_bn]),
                'slug' => $this->slug,
                'status' => $this->status,
                'sort_order' => $this->sort_order,
                'description' => array_filter(['en' => $this->description_en, 'bn' => $this->description_bn]) ?: null,
            ]
        );
        $this->pageId = $page->id;

        AdminActivity::log(
            $creating ? 'created' : 'updated',
            "Product Category: {$this->name_en}",
        );

        $this->redirect(route('admin.product-categories'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.product-categories.form')
            ->layout('layouts.admin', ['title' => $this->categoryId ? 'Edit Product Category' : 'New Product Category']);
    }
}
