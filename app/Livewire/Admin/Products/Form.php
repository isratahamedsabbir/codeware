<?php

namespace App\Livewire\Admin\Products;

use App\Concerns\HasSeoFields;
use App\Concerns\HasTranslatableFields;
use App\Models\MediaLibrary;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductCategory;
use App\Models\Setting;
use App\Support\AdminActivity;
use App\Support\Slug;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    use HasSeoFields, HasTranslatableFields;

    public ?int $productId = null;

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

    /**
     * @var array<int, int>
     */
    public array $category_ids = [];

    #[Validate('required|numeric|min:0')]
    public string $price = '0';

    #[Validate('nullable|numeric|min:0|lt:price')]
    public string $discount_price = '';

    #[Validate('nullable|integer|min:0')]
    public string $quantity = '';

    public bool $is_featured = false;

    public array $description = [];

    public string $featured_image = '';

    public string $featuredImagePickerId = '';

    /**
     * Ordered Media Library ids for the product's gallery — order in this array
     * is the display order, persisted to product_media.sort_order on save (see
     * persistProduct()). Kept as plain ids rather than binding the picker to
     * hydrated MediaLibrary models directly so re-ordering is a cheap array
     * operation instead of re-fetching.
     */
    public array $gallery_ids = [];

    public string $galleryPickerId = '';

    /**
     * Flat list of variant combinations (e.g. "Color: Red, Size: Small"), each
     * optionally overriding the product's price/stock. Built via
     * generateVariations(), which takes the checked values in
     * $variationSelectedValues and produces the cartesian product across every
     * attribute that has at least one value checked — combinations that
     * already existed keep their price/discount/quantity/visible, only new
     * ones start blank and visible. 'visible' controls whether the combination
     * is exposed on the public API/storefront — a generated combination that
     * doesn't actually exist (e.g. no Red XXL in stock) can be hidden instead
     * of deleted, and regenerated back into existence later without losing
     * its price. Shape: [['attributes' => ['Color' => 'Red', 'Size' =>
     * 'Small'], 'price' => '500.00', 'discount_price' => null, 'quantity' =>
     * '10', 'visible' => true], ...].
     *
     * @var array<int, array{attributes: array<string, string>, price: string, discount_price: string, quantity: string, visible: bool}>
     */
    public array $variations = [];

    /**
     * Which of the system's attributes (e.g. Color, Size, Material) are
     * relevant for this product — controls which value pickers show below,
     * so a product doesn't get cluttered with every attribute ever defined.
     *
     * @var array<int, string>
     */
    public array $variationActiveAttributes = [];

    /**
     * Backing state for the value checkboxes above the variation cards, keyed
     * by attribute name (e.g. ['Color' => ['Red', 'Blue'], 'Size' =>
     * ['Small']]). generateVariations() reads this to build the combinations.
     *
     * @var array<string, array<int, string>>
     */
    public array $variationSelectedValues = [];

    /**
     * Backed by the shared `faqs` table (App\Concerns\HasFaqs), not a column
     * on the product itself — see persistProduct(), which replaces the whole
     * list via Product::syncFaqs() on every save.
     *
     * @var array<int, array{question: array<string, string>, answer: array<string, string>}>
     */
    public array $faqs = [];

    public function mount(?int $id = null): void
    {
        $this->featuredImagePickerId = 'featured-image-'.Str::uuid()->toString();
        $this->galleryPickerId = 'product-gallery-'.Str::uuid()->toString();

        if ($id) {
            $product = Product::findOrFail($id);
            $this->productId = $id;
            $this->hydrateTranslatable($product, ['name', 'description']);
            $this->slug = $product->slug ?? '';
            $this->category_ids = $product->categories->pluck('id')->all();
            $this->price = (string) $product->price;
            $this->discount_price = $product->discount_price !== null ? (string) $product->discount_price : '';
            $this->quantity = $product->quantity !== null ? (string) $product->quantity : '';
            $this->is_featured = (bool) $product->is_featured;

            $this->featured_image = $product->featured_image ?? '';
            $this->gallery_ids = $product->gallery->pluck('id')->all();
            $this->variations = collect($product->variations ?? [])->map(fn ($row) => [
                // Older rows saved before combinations were supported only had
                // a single 'attribute'/'value' pair — fold them into the same
                // shape so existing data keeps working under the new picker.
                'attributes' => $row['attributes'] ?? (filled($row['attribute'] ?? null) ? [$row['attribute'] => $row['value']] : []),
                'price' => $row['price'] ?? '',
                'discount_price' => $row['discount_price'] ?? '',
                'quantity' => $row['quantity'] ?? '',
                'visible' => $row['visible'] ?? true,
            ])->all();

            foreach ($this->variations as $row) {
                foreach ($row['attributes'] as $attributeName => $value) {
                    if (! in_array($attributeName, $this->variationActiveAttributes, true)) {
                        $this->variationActiveAttributes[] = $attributeName;
                    }

                    if (! in_array($value, $this->variationSelectedValues[$attributeName] ?? [], true)) {
                        $this->variationSelectedValues[$attributeName][] = $value;
                    }
                }
            }

            $this->faqs = $product->faqs->map(fn ($faq) => [
                'question' => $faq->getTranslations('question'),
                'answer' => $faq->getTranslations('answer'),
            ])->all();

            $this->pageId = $product->page?->id;
            $this->hydrateSeoFieldsFromPage($product->page);

            $this->checkSlugAvailability();
        }
    }

    #[Computed]
    public function galleryMedia()
    {
        $media = MediaLibrary::whereIn('id', $this->gallery_ids)->get()->keyBy('id');

        return collect($this->gallery_ids)->map(fn ($id) => $media->get($id))->filter()->values();
    }

    public function addGalleryImage(int $id): void
    {
        if (! in_array($id, $this->gallery_ids, true) && MediaLibrary::whereKey($id)->exists()) {
            $this->gallery_ids[] = $id;
        }
    }

    /**
     * @param  array<int, int>  $ids
     */
    public function addGalleryImages(array $ids): void
    {
        $validIds = MediaLibrary::whereIn('id', $ids)->pluck('id')->all();

        // Preserve the order the picker returned them in (the order they were
        // clicked/selected), not whatever whereIn() happened to fetch them in.
        foreach ($ids as $id) {
            if (in_array($id, $validIds, true) && ! in_array($id, $this->gallery_ids, true)) {
                $this->gallery_ids[] = $id;
            }
        }
    }

    public function removeGalleryImage(int $id): void
    {
        $this->gallery_ids = array_values(array_diff($this->gallery_ids, [$id]));
    }

    /**
     * @param  array<int, int>  $order  Media ids in their new display order.
     */
    public function reorderGallery(array $order): void
    {
        $this->gallery_ids = array_values(array_intersect($order, $this->gallery_ids));
    }

    /**
     * Builds every combination across the attributes that currently have at
     * least one checked value (e.g. Color: Red, Blue + Size: Small produces
     * Red/Small, Red/Large is skipped since Large isn't checked, Blue/Small).
     * Attribute order follows productAttributes() so the combination order
     * stays stable across regenerations. A combination that already exists
     * keeps its price/discount/quantity; combinations no longer matching the
     * checked values are dropped.
     */
    public function generateVariations(): void
    {
        $axes = [];

        foreach ($this->productAttributes as $attribute) {
            if (! in_array($attribute->name, $this->variationActiveAttributes, true)) {
                continue;
            }

            $values = array_values(array_filter($this->variationSelectedValues[$attribute->name] ?? []));

            if ($values !== []) {
                $axes[$attribute->name] = $values;
            }
        }

        if ($axes === []) {
            return;
        }

        $combinations = [[]];

        foreach ($axes as $attributeName => $values) {
            $next = [];

            foreach ($combinations as $combo) {
                foreach ($values as $value) {
                    $next[] = $combo + [$attributeName => $value];
                }
            }

            $combinations = $next;
        }

        $existing = collect($this->variations)->keyBy(
            fn ($row) => $this->variationComboKey($row['attributes'] ?? [])
        );

        $this->variations = collect($combinations)
            ->map(fn ($attributes) => $existing->get($this->variationComboKey($attributes)) ?? [
                'attributes' => $attributes,
                'price' => '',
                'discount_price' => '',
                'quantity' => '',
                'visible' => true,
            ])
            ->values()
            ->all();
    }

    /**
     * A stable identity for a combination regardless of the order its
     * attributes happen to be in, used to match old rows against newly
     * generated ones so their price/discount/quantity survive a regenerate.
     */
    private function variationComboKey(array $attributes): string
    {
        ksort($attributes);

        return json_encode($attributes);
    }

    public function removeVariation(int $index): void
    {
        unset($this->variations[$index]);
        $this->variations = array_values($this->variations);
    }

    public function addFaq(): void
    {
        $this->faqs[] = ['question' => [], 'answer' => []];
    }

    public function removeFaq(int $index): void
    {
        unset($this->faqs[$index]);
        $this->faqs = array_values($this->faqs);
    }

    /**
     * Drops any row left with no primary-locale question — e.g. a blank card
     * added via addFaq() and never filled in.
     *
     * @return array<int, array{question: array<string, string>, answer: array<string, string>}>
     */
    private function cleanedFaqs(): array
    {
        return collect($this->faqs)
            ->filter(fn ($row) => filled($row['question'][$this->primaryLocale] ?? null))
            ->map(fn ($row) => [
                'question' => array_filter($row['question'] ?? []),
                'answer' => array_filter($row['answer'] ?? []),
            ])
            ->values()
            ->all();
    }

    /**
     * Drops any row left with no attributes — shouldn't normally happen since
     * generateVariations() already guards against it, but keeps save() safe
     * regardless.
     *
     * @return array<int, array{attributes: array<string, string>, price: ?string, discount_price: ?string, quantity: ?string, visible: bool}>
     */
    private function cleanedVariations(): array
    {
        return collect($this->variations)
            ->filter(fn ($row) => filled($row['attributes'] ?? null))
            ->map(fn ($row) => [
                'attributes' => $row['attributes'],
                'price' => filled($row['price'] ?? null) ? $row['price'] : null,
                'discount_price' => filled($row['discount_price'] ?? null) ? $row['discount_price'] : null,
                'quantity' => filled($row['quantity'] ?? null) ? $row['quantity'] : null,
                'visible' => (bool) ($row['visible'] ?? true),
            ])
            ->values()
            ->all();
    }

    /**
     * Live slug-as-you-type — regenerates from the primary locale's name only
     * while the slug still matches what we last auto-generated (i.e. the
     * admin hasn't typed a custom one), or is empty. Editing an existing
     * product's name never touches its already-set slug this way, since
     * autoSlug starts empty and never matches a loaded slug. Livewire's magic
     * updated{Field}() hooks don't fire for array sub-key mutations like
     * "name.en", so this lives in the generic updated() catch-all instead.
     */
    public function updated(string $name, mixed $value): void
    {
        if ($name === 'variationActiveAttributes' || str_starts_with($name, 'variationSelectedValues.')) {
            $this->generateVariations();

            return;
        }

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

    #[Computed]
    public function productCategories()
    {
        return ProductCategory::orderBy('sort_order')->get();
    }

    /**
     * Every category, depth-first flattened for the Categories checkbox
     * picker — subcategories render indented directly under their parent.
     */
    #[Computed]
    public function categoryTree()
    {
        return ProductCategory::tree($this->productCategories);
    }

    #[Computed]
    public function productAttributes()
    {
        return ProductAttribute::orderBy('name')->get();
    }

    public function openPuckEditor(): void
    {
        if (! $this->pageId) {
            return;
        }

        auth()->user()->tokens()->where('name', 'puck-builder')->delete();

        $token = auth()->user()->createToken(
            'puck-builder',
            ['*'],
            now()->addMinutes(Setting::puckSessionMinutes())
        )->plainTextToken;

        $url = config('cms.editor_base_url')."/puck/edit/product/{$this->pageId}#token={$token}";
        $this->js('window.open('.json_encode($url).', \'_blank\')');
    }

    public function saveAndOpenPageBuilder(): void
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
        $rules['variations.*.price'] = 'nullable|numeric|min:0';
        $rules['variations.*.discount_price'] = 'nullable|numeric|min:0|lt:variations.*.price';
        $rules['variations.*.quantity'] = 'nullable|integer|min:0|lte:quantity';
        $rules['category_ids'] = 'array';
        $rules['category_ids.*'] = 'integer|exists:categories,id,type,product';

        $this->validate($rules);

        $this->persistProduct();

        $this->dispatch('notify', message: $this->productId ? 'Product updated successfully' : 'Product created successfully');

        auth()->user()->tokens()->where('name', 'puck-builder')->delete();

        $token = auth()->user()->createToken(
            'puck-builder',
            ['*'],
            now()->addMinutes(Setting::puckSessionMinutes())
        )->plainTextToken;

        $url = config('cms.editor_base_url')."/puck/edit/product/{$this->pageId}#token={$token}";
        $this->js('window.open('.json_encode($url).', \'_blank\')');

        $this->redirect(route('admin.products.edit', $this->productId), navigate: true);
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
        $rules['variations.*.price'] = 'nullable|numeric|min:0';
        $rules['variations.*.discount_price'] = 'nullable|numeric|min:0|lt:variations.*.price';
        $rules['variations.*.quantity'] = 'nullable|integer|min:0|lte:quantity';
        $rules['category_ids'] = 'array';
        $rules['category_ids.*'] = 'integer|exists:categories,id,type,product';

        $this->validate($rules);

        $this->persistProduct();

        $this->dispatch('notify', message: $this->productId ? 'Product updated successfully' : 'Product created successfully');

        $this->redirect(route('admin.products'), navigate: true);
    }

    private function persistProduct(): void
    {
        $creating = $this->productId === null;

        $data = [
            'name' => $this->translatablePayload('name'),
            'price' => $this->price,
            'discount_price' => $this->discount_price !== '' ? $this->discount_price : null,
            'quantity' => $this->quantity !== '' ? $this->quantity : 0,
            'is_featured' => $this->is_featured,
            'description' => $this->translatablePayload('description') ?: null,
            'featured_image' => $this->featured_image ?: null,
            'variations' => $this->cleanedVariations(),
        ];

        if ($this->productId) {
            $product = Product::findOrFail($this->productId);
            $product->update($data);
        } else {
            // New products stay inactive until switched on from the list — status
            // is no longer editable from this form, see Index::toggleStatus().
            $data['status'] = 'inactive';
            $product = Product::create($data);
            $this->productId = $product->id;
        }

        $product->gallery()->sync(
            collect($this->gallery_ids)->mapWithKeys(fn ($id, $index) => [$id => ['sort_order' => $index]])->all()
        );

        $product->categories()->sync($this->category_ids);

        $product->syncFaqs($this->cleanedFaqs());

        $page = Page::updateOrCreate(
            ['type' => 'product', 'product_id' => $product->id],
            [
                'user_id' => auth()->id(),
                'title' => $this->translatablePayload('name'),
                'slug' => $this->slug,
                'status' => $product->status,
                'description' => $this->translatablePayload('description') ?: null,
                ...$this->seoPagePayload(),
            ]
        );
        $this->pageId = $page->id;

        AdminActivity::log(
            $creating ? 'created' : 'updated',
            "Product #{$product->id}: {$this->primaryValue('name')}",
        );
    }

    public function render()
    {
        return view('livewire.admin.products.form')
            ->layout('layouts.admin', ['title' => $this->productId ? 'Edit Product' : 'New Product']);
    }
}
