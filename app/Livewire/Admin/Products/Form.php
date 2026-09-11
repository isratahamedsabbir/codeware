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

    #[Validate('nullable|integer|exists:categories,id,type,product')]
    public ?int $product_category_id = null;

    #[Validate('required|numeric|min:0')]
    public string $price = '0';

    #[Validate('nullable|numeric|min:0|lt:price')]
    public string $discount_price = '';

    #[Validate('nullable|integer|min:0')]
    public string $quantity = '';

    public bool $charge_shipping = true;

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
     * Flat list of attribute+value option cards shown on the product page (e.g.
     * "Size: Small"), each optionally overriding the product's price/stock. No
     * combinatorial matrix — picking "Size: Small" and "Color: Red" doesn't
     * imply a "Small Red" combination, they're independent option cards. Built
     * one at a time via the Attribute+Value picker (addVariation()) rather
     * than typed, since both attribute and value come from ProductAttribute's
     * managed lists. Shape: [['attribute' => 'Size', 'value' => 'Small',
     * 'price' => '500.00', 'discount_price' => null, 'quantity' => '10'], ...].
     *
     * @var array<int, array{attribute: string, value: string, price: string, discount_price: string, quantity: string}>
     */
    public array $variations = [];

    /**
     * The Attribute+Value picker above the variation cards — cleared back to
     * '' after each addVariation() so adding several values in a row just
     * means re-picking Value (Attribute usually stays put via wire:model.live,
     * see updatedVariationAttribute()).
     */
    public string $variationAttribute = '';

    public string $variationValue = '';

    public function mount(?int $id = null): void
    {
        $this->featuredImagePickerId = 'featured-image-'.Str::uuid()->toString();
        $this->galleryPickerId = 'product-gallery-'.Str::uuid()->toString();

        if ($id) {
            $product = Product::findOrFail($id);
            $this->productId = $id;
            $this->hydrateTranslatable($product, ['name', 'description']);
            $this->slug = $product->slug ?? '';
            $this->product_category_id = $product->product_category_id;
            $this->price = (string) $product->price;
            $this->discount_price = $product->discount_price !== null ? (string) $product->discount_price : '';
            $this->quantity = $product->quantity !== null ? (string) $product->quantity : '';
            $this->charge_shipping = (bool) $product->charge_shipping;
            $this->is_featured = (bool) $product->is_featured;

            $this->featured_image = $product->featured_image ?? '';
            $this->gallery_ids = $product->gallery->pluck('id')->all();
            $this->variations = collect($product->variations ?? [])->map(fn ($row) => [
                'attribute' => $row['attribute'] ?? '',
                'value' => $row['value'] ?? '',
                'price' => $row['price'] ?? '',
                'discount_price' => $row['discount_price'] ?? '',
                'quantity' => $row['quantity'] ?? '',
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
     * The Value picker's options — every value defined on the currently
     * selected attribute (see App\Livewire\Admin\ProductAttributes\Form),
     * empty while no attribute is picked yet or it has none defined.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function variationValueOptions(): array
    {
        return $this->productAttributes->firstWhere('name', $this->variationAttribute)?->values ?? [];
    }

    /**
     * Livewire's magic updated{Property}() hook — clears the stale Value
     * selection the moment the Attribute changes, since the old value almost
     * certainly doesn't belong to the newly picked attribute's list.
     */
    public function updatedVariationAttribute(): void
    {
        $this->variationValue = '';
    }

    public function addVariation(): void
    {
        if ($this->variationAttribute === '' || $this->variationValue === '') {
            return;
        }

        $alreadyAdded = collect($this->variations)->contains(
            fn ($row) => $row['attribute'] === $this->variationAttribute && $row['value'] === $this->variationValue
        );

        if (! $alreadyAdded) {
            $this->variations[] = [
                'attribute' => $this->variationAttribute,
                'value' => $this->variationValue,
                'price' => '',
                'discount_price' => '',
                'quantity' => '',
            ];
        }

        // Attribute stays picked — adding several values off the same
        // attribute in a row is the common case.
        $this->variationValue = '';
    }

    public function removeVariation(int $index): void
    {
        unset($this->variations[$index]);
        $this->variations = array_values($this->variations);
    }

    /**
     * Drops any row left with no attribute/value picked — shouldn't normally
     * happen since addVariation() already guards against it, but keeps save()
     * safe regardless.
     *
     * @return array<int, array{attribute: string, value: string, price: ?string, discount_price: ?string, quantity: ?string}>
     */
    private function cleanedVariations(): array
    {
        return collect($this->variations)
            ->filter(fn ($row) => filled($row['attribute'] ?? null) && filled($row['value'] ?? null))
            ->map(fn ($row) => [
                'attribute' => $row['attribute'],
                'value' => $row['value'],
                'price' => filled($row['price'] ?? null) ? $row['price'] : null,
                'discount_price' => filled($row['discount_price'] ?? null) ? $row['discount_price'] : null,
                'quantity' => filled($row['quantity'] ?? null) ? $row['quantity'] : null,
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
        $rules['variations.*.quantity'] = 'nullable|integer|min:0';

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
        $rules['variations.*.quantity'] = 'nullable|integer|min:0';

        $this->validate($rules);

        $this->persistProduct();

        $this->dispatch('notify', message: $this->productId ? 'Product updated successfully' : 'Product created successfully');

        $this->redirect(route('admin.products'), navigate: true);
    }

    private function persistProduct(): void
    {
        $creating = $this->productId === null;

        $data = [
            'product_category_id' => $this->product_category_id,
            'name' => $this->translatablePayload('name'),
            'price' => $this->price,
            'discount_price' => $this->discount_price !== '' ? $this->discount_price : null,
            'quantity' => $this->quantity !== '' ? $this->quantity : null,
            'charge_shipping' => $this->charge_shipping,
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
