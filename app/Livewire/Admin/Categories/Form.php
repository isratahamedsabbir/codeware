<?php

namespace App\Livewire\Admin\Categories;

use App\Concerns\HasSeoFields;
use App\Concerns\HasTranslatableFields;
use App\Models\Category;
use App\Models\Page;
use App\Models\ProductCategory;
use App\Models\Type;
use App\Support\AdminActivity;
use App\Support\Locale;
use App\Support\Slug;
use App\Support\Taxonomy;
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

    /**
     * Which content pool this category belongs to — a row in the shared taxonomy
     * table that every category, brand and tag picks from (see App\Models\Type).
     * Required: a category in no pool has no storefront or blog page to appear on.
     */
    #[Validate('required|integer|exists:types,id')]
    public ?int $typeId = null;

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
        // "New category" button (which links here with ?type=<id> for whichever
        // pool was showing) — a plain query string, not a route parameter, so
        // it's read directly rather than via a mount() arg.
        $queryType = request()->query('type');
        if (is_string($queryType) && ctype_digit($queryType) && Type::whereKey($queryType)->exists()) {
            $this->typeId = (int) $queryType;
        }

        if (! $id) {
            $this->typeId ??= $this->defaultTypeId();

            return;
        }

        $category = Category::findOrFail($id);
        $this->categoryId = $id;
        $this->typeId = $category->type_id;
        $this->parentId = $category->parent_id;
        $this->hydrateTranslatable($category, ['name', 'description']);
        $this->slug = $category->slug ?? '';
        $this->icon = $category->icon ?? null;

        $this->pageId = $category->page?->id;
        $this->hydrateSeoFieldsFromPage($category->page);

        $this->checkSlugAvailability();
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
    public function updatedTypeId(): void
    {
        $this->parentId = null;
    }

    private function checkSlugAvailability(): void
    {
        $this->slugAvailable = Slug::isAvailable($this->slug, $this->pageId);
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
     * The pool the admin picked, as a model — the form branches on it (parents
     * and an icon for product categories, a description for post categories) and
     * save() reads the paired Page's own `type` off it.
     */
    #[Computed]
    public function selectedType(): ?Type
    {
        return $this->typeId ? Type::find($this->typeId) : null;
    }

    /**
     * Product categories carry a parent tree and an icon; post categories are a
     * flat list with a description instead. Same split as before, keyed off the
     * selected pool rather than a hardcoded string on the form.
     */
    #[Computed]
    public function isProductPool(): bool
    {
        return $this->selectedType()?->slug === Type::PRODUCT;
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
        if (! $this->isProductPool) {
            return [];
        }

        $all = $this->productCategories();

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
     * @return Collection<int, Category>
     */
    private function productCategories(): Collection
    {
        return Category::where('type_id', $this->typeId)->orderBy('sort_order')->get();
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
        ]), $this->translatableRules(self::seoTranslatableFields()));
        $rules['slug'] = [
            'required', 'string', 'max:255',
            ...Slug::uniqueRules($this->pageId),
        ];

        if ($this->isProductPool) {
            $rules['parentId'] = Taxonomy::nullableRule(ProductCategory::class);
        }

        $this->validate($rules);

        if ($this->isProductPool && $this->parentId && $this->categoryId) {
            $invalidParents = [
                $this->categoryId,
                ...$this->descendantIds($this->categoryId, $this->productCategories()),
            ];

            if (in_array($this->parentId, $invalidParents, true)) {
                $this->addError('parentId', 'A category cannot be parented under itself or one of its own subcategories.');

                return;
            }
        }

        $creating = $this->categoryId === null;

        $data = [
            'type_id' => $this->typeId,
            'name' => $this->translatablePayload('name'),
            'description' => $this->isProductPool ? null : ($this->translatablePayload('description') ?: null),
            'icon' => $this->isProductPool ? ($this->icon ?: null) : null,
            'parent_id' => $this->isProductPool ? $this->parentId : null,
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

        // Keyed on category_id alone, not on the page's type as well: the admin
        // can move a category between pools, and the one page paired with a
        // category is identified by that category, so the pool is something to
        // write rather than something to match on. Otherwise changing pools
        // would orphan the existing page and quietly start a second one.
        $page = Page::updateOrCreate(
            ['category_id' => $category->id],
            [
                'type' => $this->selectedType?->pageType(),
                'user_id' => auth()->id(),
                'title' => $this->translatablePayload('name'),
                'slug' => $this->slug,
                'status' => $category->status,
                'description' => $this->isProductPool ? null : ($this->translatablePayload('description') ?: null),
                ...$this->seoPagePayload(),
            ]
        );
        $this->pageId = $page->id;

        AdminActivity::log(
            $creating ? 'created' : 'updated',
            "Category: {$this->primaryValue('name')}",
        );

        $this->redirect(route('admin.categories', ['type' => $this->typeId]), navigate: true);
    }

    private function defaultTypeId(): ?int
    {
        return Type::idFor(Type::PRODUCT) ?? Type::idFor(Type::POST);
    }

    public function render()
    {
        return view('livewire.admin.categories.form')
            ->layout('layouts.admin', ['title' => $this->categoryId ? 'Edit Category' : 'New Category']);
    }
}
