<?php

namespace App\Livewire\Admin\Products;

use App\Concerns\HasSeoFields;
use App\Concerns\HasTranslatableFields;
use App\Models\MediaLibrary;
use App\Models\Page;
use App\Models\Product;
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
            $this->is_featured = (bool) $product->is_featured;

            $this->featured_image = $product->featured_image ?? '';
            $this->gallery_ids = $product->gallery->pluck('id')->all();

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
            'is_featured' => $this->is_featured,
            'description' => $this->translatablePayload('description') ?: null,
            'featured_image' => $this->featured_image ?: null,
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
