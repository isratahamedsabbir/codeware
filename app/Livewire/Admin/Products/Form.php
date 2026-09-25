<?php

namespace App\Livewire\Admin\Products;

use App\Concerns\HasSeoFields;
use App\Concerns\HasTranslatableFields;
use App\Models\Discount;
use App\Models\MediaLibrary;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductVendor;
use App\Models\Tag;
use App\Support\AdminActivity;
use App\Support\Locale;
use App\Support\PuckEditor;
use App\Support\Slug;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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

    #[Validate('nullable|string|max:100')]
    public string $sku = '';

    /**
     * null = not yet checked, true = available (green), false = taken (red).
     */
    public ?bool $slugAvailable = null;

    /**
     * @var array<int, int>
     */
    public array $category_ids = [];

    /**
     * Shares the same tag pool as Blog Posts (App\Models\Tag) rather than a
     * product-specific list — see Tag::products().
     *
     * @var array<int, int>
     */
    #[Validate('nullable|array')]
    public array $tag_ids = [];

    /**
     * Products explicitly linked as "related" — a self-referential set chosen
     * from the product picker; the public API shows these (falling back to
     * same-category products when empty).
     *
     * @var array<int, int>
     */
    #[Validate('nullable|array')]
    public array $related_product_ids = [];

    #[Validate('nullable|string|max:255')]
    public string $newTagName = '';

    #[Validate('nullable|integer|exists:categories,id,type,product_brand')]
    public string $brand_id = '';

    #[Validate('nullable|integer|exists:product_vendors,id')]
    public string $vendor_id = '';

    #[Validate('required|in:physical,digital')]
    public string $product_type = 'physical';

    #[Validate('required|numeric|min:0')]
    public string $price = '0';

    #[Validate('nullable|numeric|min:0|lt:price')]
    public string $discount_price = '';

    /**
     * The discount from the Discounts list applied to this product (pivot:
     * discount_product). Selecting one auto-computes discount_price from the
     * product price and pushes it into every variant — see updatedDiscountId().
     */
    #[Validate('nullable|integer|exists:discounts,id')]
    public string $discount_id = '';

    #[Validate('nullable|integer|min:0')]
    public string $quantity = '';

    /** Whole months of warranty coverage from purchase; blank = no warranty. */
    #[Validate('nullable|integer|min:0|max:600')]
    public string $warranty_months = '';

    public bool $is_featured = false;

    public array $description = [];

    /** Short summary for the product — rich text via the Details section. */
    public array $excerpt = [];

    /** Free-form specification list/table — rich text via the Details section. */
    public array $specifications = [];

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
     * 'Small'], 'sku' => 'TSHIRT-RED-S', 'price' => '500.00',
     * 'discount_price' => null, 'quantity' => '10', 'visible' => true,
     * 'image' => null, 'note' => null], ...]. Every combination carries its
     * own sku, auto-derived from the base product sku + the attribute values
     * (see autoVariantSku()) and shown read-only in the card's footer.
     *
     * @var array<int, array{attributes: array<string, string>, sku: string, price: string, discount_price: string, quantity: string, visible: bool, image: ?string, note: ?string}>
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
     * list via Product::syncFaqs() on every save. Not translatable — `is_active`
     * controls whether a question shows on the storefront.
     *
     * @var array<int, array{question: string, answer: string, is_active: bool}>
     */
    public array $faqs = [];

    public function mount(?int $id = null): void
    {
        $this->featuredImagePickerId = 'featured-image-'.Str::uuid()->toString();
        $this->galleryPickerId = 'product-gallery-'.Str::uuid()->toString();

        if ($id) {
            $product = Product::findOrFail($id);
            $this->productId = $id;
            $this->hydrateTranslatable($product, ['name', 'description', 'excerpt', 'specifications']);
            $this->slug = $product->slug ?? '';
            $this->category_ids = $product->categories->pluck('id')->all();
            $this->tag_ids = $product->tags->pluck('id')->all();
            $this->related_product_ids = $product->relatedProducts()
                ->pluck('product_related_product.related_product_id')
                ->all();
            $this->brand_id = $product->brand_id !== null ? (string) $product->brand_id : '';
            $this->vendor_id = $product->vendor_id !== null ? (string) $product->vendor_id : '';
            $this->sku = $product->sku ?? '';
            $this->product_type = $product->product_type;
            $this->price = (string) $product->price;
            $this->discount_price = $product->discount_price !== null ? (string) $product->discount_price : '';
            $this->discount_id = $product->discounts->first()?->id !== null ? (string) $product->discounts->first()->id : '';

            if ($this->discount_id === '') {
                $this->discount_price = '';
            }
            $this->quantity = $product->quantity !== null ? (string) $product->quantity : '';
            $this->warranty_months = $product->warranty_months !== null ? (string) $product->warranty_months : '';
            $this->is_featured = (bool) $product->is_featured;

            $this->featured_image = $product->featured_image ?? '';
            $this->gallery_ids = $product->gallery->pluck('id')->all();
            $this->variations = collect($product->variations ?? [])->map(fn ($row) => [
                // Older rows saved before combinations were supported only had
                // a single 'attribute'/'value' pair — fold them into the same
                // shape so existing data keeps working under the new picker.
                'attributes' => $row['attributes'] ?? (filled($row['attribute'] ?? null) ? [$row['attribute'] => $row['value']] : []),
                'sku' => $row['sku'] ?? '',
                'price' => $row['price'] ?? '',
                'discount_price' => $row['discount_price'] ?? '',
                'quantity' => $row['quantity'] ?? '',
                'visible' => $row['visible'] ?? true,
                'image' => $row['image'] ?? '',
                'note' => $row['note'] ?? '',
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
                'question' => $faq->question,
                'answer' => $faq->answer ?? '',
                'is_active' => (bool) $faq->is_active,
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
                'sku' => $this->autoVariantSku($attributes),
                'price' => $this->price,
                'discount_price' => $this->discount_price,
                'quantity' => '',
                'visible' => true,
                'image' => '',
                'note' => '',
            ])
            ->values()
            ->all();
    }

    /**
     * Derives a combination's sku from the base product sku + its attribute
     * values, e.g. base "TSHIRT" with Color: Red, Size: Small →
     * "TSHIRT-RED-SMALL". Values are uppercased, stripped to safe characters,
     * and joined in attribute order. Returns '' when the product has no base
     * sku yet, so a combination added before the sku is set stays blank (and
     * gets filled the same way at save time).
     *
     * @param  array<string, string>  $attributes
     */
    public function autoVariantSku(array $attributes): string
    {
        if ($this->sku === '') {
            return '';
        }

        $suffix = collect($attributes)
            ->map(fn (string $value) => Str::upper(preg_replace('/[^A-Za-z0-9]+/', '', Str::ascii($value)) ?: 'X'))
            ->implode('-');

        return $suffix === '' ? $this->sku : "{$this->sku}-{$suffix}";
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

    /**
     * When the product uses variants, the overall Quantity is the sum of every
     * variant's quantity (a variant stock IS the product stock). Runs live on
     * any variation change and again at the top of save()/saveAndOpenPageBuilder()
     * so validation always sees the merged total. A blank variant Qty counts
     * as 0 (out of stock), so with variants present the field always reflects
     * the variant total.
     */
    private function syncQuantityFromVariations(): void
    {
        if ($this->variations === []) {
            return;
        }

        $total = collect($this->variations)->reduce(
            fn ($carry, $row) => $carry + (int) ($row['quantity'] ?? 0),
            0
        );

        $this->quantity = (string) $total;
    }

    public function removeVariation(int $index): void
    {
        unset($this->variations[$index]);
        $this->variations = array_values($this->variations);
    }

    public function addFaq(): void
    {
        $this->faqs[] = ['question' => '', 'answer' => '', 'is_active' => true];
    }

    public function removeFaq(int $index): void
    {
        unset($this->faqs[$index]);
        $this->faqs = array_values($this->faqs);
    }

    /**
     * Drops any row left with no question — e.g. a blank card added via
     * addFaq() and never filled in.
     *
     * @return array<int, array{question: string, answer: string, is_active: bool}>
     */
    private function cleanedFaqs(): array
    {
        return collect($this->faqs)
            ->filter(fn ($row) => filled($row['question'] ?? null))
            ->map(fn ($row) => [
                'question' => $row['question'],
                'answer' => $row['answer'] ?? '',
                'is_active' => (bool) ($row['is_active'] ?? true),
            ])
            ->values()
            ->all();
    }

    /**
     * Drops any row left with no attributes — shouldn't normally happen since
     * generateVariations() already guards against it, but keeps save() safe
     * regardless. A blank row sku is auto-filled from the base sku, same as
     * generateVariations() does for brand-new combinations.
     *
     * @return array<int, array{attributes: array<string, string>, sku: ?string, price: ?string, discount_price: ?string, quantity: ?string, visible: bool, image: ?string, note: ?string}>
     */
    private function cleanedVariations(): array
    {
        return collect($this->variations)
            ->filter(fn ($row) => filled($row['attributes'] ?? null))
            ->map(fn ($row) => [
                'attributes' => $row['attributes'],
                'sku' => filled($row['sku'] ?? null) ? $row['sku'] : $this->autoVariantSku($row['attributes']),
                'price' => filled($row['price'] ?? null) ? $row['price'] : null,
                'discount_price' => filled($row['discount_price'] ?? null) ? $row['discount_price'] : null,
                'quantity' => filled($row['quantity'] ?? null) ? $row['quantity'] : 0,
                'visible' => (bool) ($row['visible'] ?? true),
                'image' => filled($row['image'] ?? null) ? $row['image'] : null,
                'note' => filled($row['note'] ?? null) ? $row['note'] : null,
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

        if (str_starts_with($name, 'variations.')) {
            $this->syncQuantityFromVariations();

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
     * When the product-level discount price is set (or cleared), push it into
     * every variant's discount_price so the sale price applies across the board
     * automatically. Each variant can still be overridden individually
     * afterwards — only a change to the base discount re-applies it.
     */
    public function updatedDiscountPrice(): void
    {
        $this->pushDiscountPriceToVariants();
    }

    /**
     * A discount picked from the Discounts list: apply its sale price to the
     * product (discount_price) and onward into every variant. Clearing the
     * selection removes the applied discount prices too, putting the product
     * back at its regular price.
     */
    public function updatedDiscountId(): void
    {
        if ($this->discount_id === '') {
            $this->discount_price = '';
        } else {
            $this->applySelectedDiscountToPrice();
        }

        $this->pushDiscountPriceToVariants();
    }

    /**
     * While a discount is applied, a change to the regular price re-derives
     * the sale price (e.g. 10% off a new price) instead of leaving the old
     * computed value behind.
     */
    public function updatedPrice(): void
    {
        if ($this->discount_id === '') {
            return;
        }

        $this->applySelectedDiscountToPrice();
        $this->pushDiscountPriceToVariants();
    }

    /**
     * Recomputes discount_price from the selected discount and the current
     * price. Skipped for a non-numeric/zero price so a still-empty price never
     * locks in a bogus 0.00 sale price that fails validation.
     */
    private function applySelectedDiscountToPrice(): void
    {
        if (! is_numeric($this->price) || (float) $this->price <= 0) {
            return;
        }

        $discount = Discount::find((int) $this->discount_id);

        if ($discount) {
            $this->discount_price = number_format($discount->priceFor((float) $this->price), 2, '.', '');
        }
    }

    /**
     * Pushes the current product-level discount_price into every variant so a
     * discount applied at product level automatically covers the whole
     * combination set ("discount added to the product lands on the variants").
     */
    private function pushDiscountPriceToVariants(): void
    {
        foreach ($this->variations as $i => $row) {
            $this->variations[$i]['discount_price'] = $this->discount_price;
        }
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

    /**
     * Same flattened tree, but as plain arrays for the Alpine expandable
     * picker — each row carries its id, name, parent, depth and child stats so
     * the client can render +/− toggles and show/hide children without a
     * second round trip.
     */
    #[Computed]
    public function categoryPickerTree()
    {
        return $this->categoryTree
            ->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->getTranslation('name', Locale::primary(), false),
                'parent_id' => $cat->parent_id,
                'depth' => $cat->depth,
                'has_children' => $this->categoryTree->contains(fn ($c) => $c->parent_id === $cat->id),
                'child_count' => $this->categoryTree->where('parent_id', $cat->id)->count(),
            ])
            ->values()
            ->all();
    }

    #[Computed]
    public function tags()
    {
        return Tag::where(fn ($q) => $q->whereIn('type', [Tag::TYPE_PRODUCT, Tag::TYPE_POST])->orWhereNull('type'))->orderBy('id')->get();
    }

    /**
     * Candidate products for the Related Products picker — every product
     * except the one being edited (a product can't be related to itself).
     * Bound client-side; the search filtering happens in the Alpine widget.
     */
    #[Computed]
    public function relatedProductOptions()
    {
        return Product::query()
            ->when($this->productId, fn ($q) => $q->where('id', '!=', $this->productId))
            ->orderBy('id')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->getTranslation('name', Locale::primary(), false),
            ])
            ->all();
    }

    /**
     * Creates a tag right from the form (type = product) and selects it — the
     * admin doesn't need to leave the product to build up its tag list.
     */
    public function createTag(): void
    {
        $this->validate(['newTagName' => 'required|string|max:255']);

        $name = trim($this->newTagName);

        if ($name === '') {
            return;
        }

        $this->newTagName = '';

        // Reuse by name across the whole tag pool: name is unique per locale
        // (see Tags\Form), so a product tag can't share a name with a post tag.
        $tag = Tag::where('name->'.Locale::primary(), $name)->first();

        if (! $tag) {
            $tag = Tag::create([
                'name' => [Locale::primary() => $name],
                'type' => Tag::TYPE_PRODUCT,
                'status' => 'active',
            ]);
            $this->dispatch('notify', message: 'Tag created successfully');
        }

        if (! in_array($tag->id, $this->tag_ids, true)) {
            $this->tag_ids[] = $tag->id;
        }
    }

    #[Computed]
    public function productAttributes()
    {
        return ProductAttribute::orderBy('name')->get();
    }

    #[Computed]
    public function productBrands()
    {
        return ProductBrand::where(fn ($q) => $q->where('type', ProductBrand::TYPE_PRODUCT)->orWhereNull('type'))->orderBy('sort_order')->orderBy('name->en')->get();
    }

    #[Computed]
    public function productVendors()
    {
        return ProductVendor::orderBy('sort_order')->orderBy('name')->get();
    }

    /**
     * Active discounts only, for the product-level dropdown — inactive
     * ones are hidden so they can't be newly attached from here. The
     * product's current discount is kept even if it went inactive, so an
     * existing attachment never silently disappears from the select.
     */
    #[Computed]
    public function discountOptions()
    {
        $active = Discount::active()->orderBy('name')->get();

        if ($this->discount_id !== '' && ! $active->contains('id', (int) $this->discount_id)) {
            $current = Discount::find((int) $this->discount_id);
            if ($current) {
                $active->push($current);
                $active = $active->sortBy('name')->values();
            }
        }

        return $active;
    }

    public function openPuckEditor(): void
    {
        if (! $this->pageId) {
            return;
        }

        $token = PuckEditor::token(auth()->user(), "puck-builder-{$this->pageId}");

        $url = config('cms.editor_base_url')."/puck/edit/product/{$this->pageId}#token={$token}";
        $this->js('window.open('.json_encode($url).', \'_blank\')');
    }

    /**
     * Per-combination sku validation: each auto-generated sku must be unique
     * across the product's own combinations and never equal to the base
     * product sku. Global uniqueness can't be DB-enforced (the skus live
     * inside a JSON array), so uniqueness is scoped to the product — the same
     * convention cart/order resolution uses to tell combinations apart.
     *
     * @return array<int, string|\Closure>
     */
    private function variationSkuRules(): array
    {
        return [
            'nullable', 'string',
            function (string $attribute, mixed $value, \Closure $fail): void {
                if ($value === null || $value === '') {
                    return;
                }

                if ($value === $this->sku) {
                    $fail('The variant SKU must be different from the product SKU.');

                    return;
                }

                $index = (int) Str::after(Str::before($attribute, '.sku'), 'variations.');

                foreach ($this->variations as $i => $row) {
                    if ($i === $index) {
                        continue;
                    }

                    if (($row['sku'] ?? '') === $value) {
                        $fail("The variant SKU {$value} is already used on another variant.");

                        return;
                    }
                }
            },
        ];
    }

    public function saveAndOpenPageBuilder(): void
    {
        $this->syncQuantityFromVariations();

        if (empty($this->slug) && $this->primaryValue('name')) {
            $this->slug = Slug::make($this->primaryValue('name'));
        }

        $rules = array_merge($this->getRules(), $this->translatableRules([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'excerpt' => 'nullable|string',
            'specifications' => 'nullable|string',
        ]));
        $rules['slug'] = [
            'required', 'string', 'max:255',
            ...Slug::uniqueRules($this->pageId),
        ];
        $rules['sku'] = [
            'nullable', 'string', 'max:100',
            $this->productId ? 'unique:products,sku,'.$this->productId : 'unique:products,sku',
        ];
        $rules['variations.*.price'] = 'nullable|numeric|min:0';
        $rules['variations.*.discount_price'] = 'nullable|numeric|min:0|lt:variations.*.price';
        $rules['variations.*.quantity'] = 'nullable|integer|min:0|lte:quantity';
        $rules['variations.*.image'] = 'nullable|string|max:500';
        $rules['variations.*.note'] = 'nullable|string|max:2000';
        $rules['variations.*.sku'] = $this->variationSkuRules();
        $rules['faqs.*.question'] = 'nullable|string|max:255';
        $rules['category_ids'] = 'array';
        $rules['category_ids.*'] = 'integer|exists:categories,id,type,product_category';
        $rules['tag_ids.*'] = [Rule::exists('categories', 'id')->where(fn ($q) => $q->whereIn('type', Tag::TYPES)->orWhereNull('type'))];
        $rules['related_product_ids.*'] = ['integer', Rule::exists('products', 'id')];

        $this->validate($rules);

        $this->persistProduct();

        $this->dispatch('notify', message: $this->productId ? 'Product updated successfully' : 'Product created successfully');

        $token = PuckEditor::token(auth()->user(), "puck-builder-{$this->pageId}");

        $url = config('cms.editor_base_url')."/puck/edit/product/{$this->pageId}#token={$token}";
        $this->js('window.open('.json_encode($url).', \'_blank\')');

        $this->redirect(route('admin.products.edit', $this->productId), navigate: true);
    }

    public function save(): void
    {
        $this->syncQuantityFromVariations();

        if (empty($this->slug) && $this->primaryValue('name')) {
            $this->slug = Slug::make($this->primaryValue('name'));
        }

        $rules = array_merge($this->getRules(), $this->translatableRules([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'excerpt' => 'nullable|string',
            'specifications' => 'nullable|string',
        ]));
        $rules['slug'] = [
            'required', 'string', 'max:255',
            ...Slug::uniqueRules($this->pageId),
        ];
        $rules['sku'] = [
            'nullable', 'string', 'max:100',
            $this->productId ? 'unique:products,sku,'.$this->productId : 'unique:products,sku',
        ];
        $rules['variations.*.price'] = 'nullable|numeric|min:0';
        $rules['variations.*.discount_price'] = 'nullable|numeric|min:0|lt:variations.*.price';
        $rules['variations.*.quantity'] = 'nullable|integer|min:0|lte:quantity';
        $rules['variations.*.image'] = 'nullable|string|max:500';
        $rules['variations.*.note'] = 'nullable|string|max:2000';
        $rules['variations.*.sku'] = $this->variationSkuRules();
        $rules['faqs.*.question'] = 'nullable|string|max:255';
        $rules['category_ids'] = 'array';
        $rules['category_ids.*'] = 'integer|exists:categories,id,type,product_category';
        $rules['tag_ids.*'] = [Rule::exists('categories', 'id')->where(fn ($q) => $q->whereIn('type', Tag::TYPES)->orWhereNull('type'))];
        $rules['related_product_ids.*'] = ['integer', Rule::exists('products', 'id')];

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
            'brand_id' => $this->brand_id !== '' ? (int) $this->brand_id : null,
            'vendor_id' => $this->vendor_id !== '' ? (int) $this->vendor_id : null,
            'sku' => $this->sku !== '' ? $this->sku : null,
            'product_type' => $this->product_type,
            'price' => $this->price,
            'discount_price' => $this->discount_price !== '' && $this->discount_id !== '' ? $this->discount_price : null,
            'quantity' => $this->quantity !== '' ? $this->quantity : 0,
            'warranty_months' => $this->warranty_months !== '' ? $this->warranty_months : null,
            'is_featured' => $this->is_featured,
            'description' => $this->translatablePayload('description') ?: null,
            'excerpt' => $this->translatablePayload('excerpt') ?: null,
            'specifications' => $this->translatablePayload('specifications') ?: null,
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

        $product->tags()->sync($this->tag_ids);

        $product->relatedProducts()->sync(
            collect($this->related_product_ids)
                ->filter(fn ($id) => (int) $id !== $product->id)
                ->mapWithKeys(fn ($id, $index) => [(int) $id => ['sort_order' => $index]])->all()
        );

        $product->discounts()->sync($this->discount_id !== '' ? [(int) $this->discount_id] : []);

        $product->syncFaqs($this->cleanedFaqs());

        $page = Page::updateOrCreate(
            ['type' => 'product', 'product_id' => $product->id],
            [
                'user_id' => auth()->id(),
                'title' => $this->translatablePayload('name'),
                'slug' => $this->slug,
                'status' => $product->status,
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
