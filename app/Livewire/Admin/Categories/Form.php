<?php

namespace App\Livewire\Admin\Categories;

use App\Concerns\HasSeoFields;
use App\Concerns\HasTranslatableFields;
use App\Models\Category;
use App\Models\Page;
use App\Support\AdminActivity;
use App\Support\Locale;
use App\Support\Slug;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    use HasSeoFields, HasTranslatableFields;

    public ?int $categoryId = null;

    public ?int $pageId = null;

    #[Validate('required|in:product_category,post_category')]
    public string $type = Category::TYPE_PRODUCT;

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

    // ── Product category only ──
    public ?int $parentId = null;

    #[Validate('nullable|string')]
    public ?string $icon = null;

    public string $iconPickerId = '';

    // ── Post category only ──
    public array $description = [];

    public function mount(?int $id = null): void
    {
        $this->iconPickerId = 'icon-picker-'.Str::uuid()->toString();

        // Preselects the type when arriving from the Categories index's
        // "New category" button (which links here with ?type=... for
        // whichever pool was showing) — a plain query string, not a route
        // parameter, so it's read directly rather than via a mount() arg.
        $queryType = request()->query('type');
        if (is_string($queryType) && in_array($queryType, Category::TYPES, true)) {
            $this->type = $queryType;
        }

        if ($id) {
            $category = Category::findOrFail($id);
            $this->categoryId = $id;
            $this->type = $category->type;
            $this->parentId = $category->parent_id;
            $this->hydrateTranslatable($category, ['name', 'description']);
            $this->slug = $category->slug ?? '';
            $this->icon = $category->icon ?? null;

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

    /**
     * Switching type while creating clears the other type's parent — a
     * product category's parent can't sensibly become a post category's.
     */
    public function updatedType(): void
    {
        $this->parentId = null;
    }

    private function checkSlugAvailability(): void
    {
        $this->slugAvailable = Slug::isAvailable($this->slug, $this->pageId);
    }

    /**
     * Flattened, indented list of every product category this one could
     * legally be parented under — excludes itself and all of its own
     * descendants (at any depth) so the dropdown can never be used to create
     * a cycle. Empty for post categories, which don't use a parent tree.
     *
     * @return array<int, array{id: int, label: string, depth: int}>
     */
    #[Computed]
    public function parentOptions(): array
    {
        if ($this->type !== Category::TYPE_PRODUCT) {
            return [];
        }

        $all = Category::where('type', Category::TYPE_PRODUCT)->orderBy('sort_order')->get();

        $excluded = [];
        if ($this->categoryId) {
            $excluded = $this->descendantIds($this->categoryId, $all);
            $excluded[] = $this->categoryId;
        }

        return Category::tree($all)
            ->reject(fn (Category $cat) => in_array($cat->id, $excluded, true))
            ->map(fn (Category $cat) => [
                'id' => $cat->id,
                'label' => str_repeat('— ', $cat->depth).$cat->getTranslation('name', Locale::primary(), false),
                'depth' => $cat->depth,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function descendantIds(int $id, Collection $all): array
    {
        $childIds = $all->where('parent_id', $id)->pluck('id')->all();
        $descendants = $childIds;

        foreach ($childIds as $childId) {
            $descendants = [...$descendants, ...$this->descendantIds($childId, $all)];
        }

        return $descendants;
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

        if ($this->type === Category::TYPE_PRODUCT) {
            $rules['parentId'] = ['nullable', 'integer', 'exists:categories,id,type,product_category'];
        }

        $this->validate($rules);

        if ($this->type === Category::TYPE_PRODUCT && $this->parentId && $this->categoryId) {
            $invalidParents = [
                $this->categoryId,
                ...$this->descendantIds($this->categoryId, Category::where('type', Category::TYPE_PRODUCT)->orderBy('sort_order')->get()),
            ];

            if (in_array($this->parentId, $invalidParents, true)) {
                $this->addError('parentId', 'A category cannot be parented under itself or one of its own subcategories.');

                return;
            }
        }

        $creating = $this->categoryId === null;

        $data = [
            'type' => $this->type,
            'name' => $this->translatablePayload('name'),
            'description' => $this->type === Category::TYPE_POST ? ($this->translatablePayload('description') ?: null) : null,
            'icon' => $this->type === Category::TYPE_PRODUCT ? ($this->icon ?: null) : null,
            'parent_id' => $this->type === Category::TYPE_PRODUCT ? $this->parentId : null,
        ];

        if ($this->categoryId) {
            $category = Category::findOrFail($this->categoryId);
            $category->update($data);
            $this->dispatch('notify', message: 'Category updated successfully');
        } else {
            // New categories stay inactive until switched on from the list —
            // status is no longer editable from this form, see Index::toggleStatus().
            $data['status'] = 'inactive';
            $category = Category::create($data);
            $this->categoryId = $category->id;
            $this->dispatch('notify', message: 'Category created successfully');
        }

        $page = Page::updateOrCreate(
            ['type' => $this->type, 'category_id' => $category->id],
            [
                'user_id' => auth()->id(),
                'title' => $this->translatablePayload('name'),
                'slug' => $this->slug,
                'status' => $category->status,
                'description' => $this->type === Category::TYPE_POST ? ($this->translatablePayload('description') ?: null) : null,
                ...$this->seoPagePayload(),
            ]
        );
        $this->pageId = $page->id;

        AdminActivity::log(
            $creating ? 'created' : 'updated',
            "Category: {$this->primaryValue('name')}",
        );

        $this->redirect(route('admin.categories', ['type' => $this->type]), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.categories.form')
            ->layout('layouts.admin', ['title' => $this->categoryId ? 'Edit Category' : 'New Category']);
    }
}
