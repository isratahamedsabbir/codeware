<?php

namespace App\Livewire\Admin\ProductCategories;

use App\Concerns\HasSeoFields;
use App\Concerns\HasTranslatableFields;
use App\Models\Page;
use App\Models\ProductCategory;
use App\Support\AdminActivity;
use App\Support\Slug;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    use HasSeoFields, HasTranslatableFields;

    public ?int $categoryId = null;

    public ?int $pageId = null;

    public array $name = [];

    #[Validate('nullable|string|max:255')]
    public string $slug = '';

    /**
     * The last auto-generated slug value, so we know whether the admin has
     * manually diverged from it — see updated().
     */
    public string $autoSlug = '';

    /**
     * null = not yet checked, true = available (green), false = taken (red).
     */
    public ?bool $slugAvailable = null;

    #[Validate('nullable|string')]
    public ?string $icon = null;

    public string $iconPickerId = '';

    public function mount(?int $id = null): void
    {
        $this->iconPickerId = 'icon-picker-'.Str::uuid()->toString();

        if ($id) {
            $cat = ProductCategory::findOrFail($id);
            $this->categoryId = $id;
            $this->hydrateTranslatable($cat, ['name']);
            $this->slug = $cat->slug ?? '';
            $this->icon = $cat->icon ?? null;

            $this->pageId = $cat->page?->id;
            $this->hydrateSeoFieldsFromPage($cat->page);

            $this->checkSlugAvailability();
        }
    }

    /**
     * Live slug-as-you-type — regenerates from the primary locale's name only
     * while the slug still matches what we last auto-generated (i.e. the
     * admin hasn't typed a custom one), or is empty. Editing an existing
     * category's name never touches its already-set slug this way, since
     * autoSlug starts empty and never matches a loaded slug. Livewire's magic
     * updated{Field}() hooks don't fire for array sub-key mutations like
     * "name.en", so this lives in the generic updated() catch-all instead.
     */
    public function updated(string $name, mixed $value): void
    {
        if (! $this->isPrimaryLocaleUpdate($name, 'name')) {
            return;
        }

        if ($this->slug === '' || $this->slug === $this->autoSlug) {
            $this->autoSlug = Slug::make($value);
            $this->slug = $this->autoSlug;
        }

        $this->syncCanonicalSlug();
        $this->checkSlugAvailability();
    }

    /**
     * Fires on direct manual edits to the slug field too, so the red/green
     * indicator stays accurate whether the slug came from auto-typing or a
     * deliberate override.
     */
    public function updatedSlug(): void
    {
        $this->slug = Slug::lower($this->slug);
        $this->syncCanonicalSlug();
        $this->checkSlugAvailability();
    }

    private function checkSlugAvailability(): void
    {
        $this->slugAvailable = Slug::isAvailable($this->slug, $this->pageId);
    }

    public function save(): void
    {
        if (empty($this->slug) && $this->primaryValue('name')) {
            $this->slug = Slug::make($this->primaryValue('name'));
        }

        $rules = array_merge($this->getRules(), $this->translatableRules([
            'name' => 'required|string|max:255',
        ]));
        $rules['slug'] = [
            'required', 'string', 'max:255',
            ...Slug::uniqueRules($this->pageId),
        ];

        $this->validate($rules);

        $creating = $this->categoryId === null;

        $data = [
            'name' => $this->translatablePayload('name'),
            'icon' => $this->icon ?: null,
        ];

        if ($this->categoryId) {
            $category = ProductCategory::findOrFail($this->categoryId);
            $category->update($data);
            $this->dispatch('notify', message: 'Category updated successfully');
        } else {
            // New categories stay inactive until switched on from the list —
            // status is no longer editable from this form, see Index::toggleStatus().
            $data['status'] = 'inactive';
            $category = ProductCategory::create($data);
            $this->categoryId = $category->id;
            $this->dispatch('notify', message: 'Category created successfully');
        }

        $page = Page::updateOrCreate(
            ['type' => 'product_category', 'category_id' => $category->id],
            [
                'user_id' => auth()->id(),
                'title' => $this->translatablePayload('name'),
                'slug' => $this->slug,
                'status' => $category->status,
                ...$this->seoPagePayload(),
            ]
        );
        $this->pageId = $page->id;

        AdminActivity::log(
            $creating ? 'created' : 'updated',
            "Product Category: {$this->primaryValue('name')}",
        );

        $this->redirect(route('admin.product-categories'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.product-categories.form')
            ->layout('layouts.admin', ['title' => $this->categoryId ? 'Edit Product Category' : 'New Product Category']);
    }
}
