<?php

namespace App\Livewire\Admin\PostCategories;

use App\Concerns\HasSeoFields;
use App\Concerns\HasTranslatableFields;
use App\Models\Page;
use App\Models\PostCategory;
use App\Support\AdminActivity;
use App\Support\Slug;
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

    public array $description = [];

    public function mount(?int $id = null): void
    {
        if ($id) {
            $category = PostCategory::findOrFail($id);
            $this->categoryId = $id;
            $this->hydrateTranslatable($category, ['name', 'description']);
            $this->slug = $category->slug ?? '';

            $this->pageId = $category->page?->id;
            $this->hydrateSeoFieldsFromPage($category->page);

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
            'description' => 'nullable|string',
        ]));
        $rules['slug'] = [
            'required', 'string', 'max:255',
            ...Slug::uniqueRules($this->pageId),
        ];

        $this->validate($rules);

        $creating = $this->categoryId === null;

        $data = [
            'name' => $this->translatablePayload('name'),
            'description' => $this->translatablePayload('description') ?: null,
        ];

        if ($this->categoryId) {
            $category = PostCategory::findOrFail($this->categoryId);
            $category->update($data);
            $this->dispatch('notify', message: 'Category updated successfully');
        } else {
            // New categories stay inactive until switched on from the list —
            // status is no longer editable from this form, see Index::toggleStatus().
            $data['status'] = 'inactive';
            $category = PostCategory::create($data);
            $this->categoryId = $category->id;
            $this->dispatch('notify', message: 'Category created successfully');
        }

        $page = Page::updateOrCreate(
            ['type' => 'post_category', 'category_id' => $category->id],
            [
                'user_id' => auth()->id(),
                'title' => $this->translatablePayload('name'),
                'slug' => $this->slug,
                'status' => $category->status,
                'description' => $this->translatablePayload('description') ?: null,
                ...$this->seoPagePayload(),
            ]
        );
        $this->pageId = $page->id;

        AdminActivity::log(
            $creating ? 'created' : 'updated',
            "Post Category: {$this->primaryValue('name')}",
        );

        $this->redirect(route('admin.post-categories'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.post-categories.form')
            ->layout('layouts.admin', ['title' => $this->categoryId ? 'Edit Post Category' : 'New Post Category']);
    }
}
