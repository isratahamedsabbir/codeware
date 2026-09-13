<?php

namespace App\Livewire\Vendor\Products;

use App\Concerns\HasTranslatableFields;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductVendor;
use App\Support\Slug;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * A trimmed-down version of Admin\Products\Form scoped to a vendor's own
 * catalog — no variations/attributes, FAQs, SEO fields, or Puck page builder
 * access (admin-only concerns), and vendor_id is never a free-choice field:
 * it's locked to the current user's assigned vendor(s), never the full list.
 */
class Form extends Component
{
    use HasTranslatableFields, WithFileUploads;

    public ?int $productId = null;

    public ?int $pageId = null;

    public array $name = [];

    public array $description = [];

    #[Validate('nullable|string|max:255')]
    public string $slug = '';

    /**
     * The last auto-generated slug value, so we know whether the vendor has
     * manually diverged from it — see updated().
     */
    public string $autoSlug = '';

    #[Validate('nullable|string|max:100')]
    public string $sku = '';

    /**
     * null = not yet checked, true = available (green), false = taken (red).
     */
    public ?bool $slugAvailable = null;

    /** @var array<int, int> */
    public array $category_ids = [];

    #[Validate('nullable|integer|exists:product_brands,id')]
    public string $brand_id = '';

    #[Validate('required|integer')]
    public string $vendor_id = '';

    #[Validate('required|numeric|min:0')]
    public string $price = '0';

    #[Validate('nullable|numeric|min:0|lt:price')]
    public string $discount_price = '';

    #[Validate('nullable|integer|min:0')]
    public string $quantity = '';

    /** @var TemporaryUploadedFile|null */
    public $featuredImage = null;

    public string $existingFeaturedImage = '';

    public function mount(?int $id = null): void
    {
        $vendorIds = $this->vendorIds();

        abort_if($vendorIds === [], 403);

        if (count($vendorIds) === 1) {
            $this->vendor_id = (string) $vendorIds[0];
        }

        if ($id) {
            $product = Product::whereIn('vendor_id', $vendorIds)->findOrFail($id);

            $this->productId = $id;
            $this->hydrateTranslatable($product, ['name', 'description']);
            $this->slug = $product->slug ?? '';
            $this->category_ids = $product->categories->pluck('id')->all();
            $this->brand_id = $product->brand_id !== null ? (string) $product->brand_id : '';
            $this->vendor_id = (string) $product->vendor_id;
            $this->sku = $product->sku ?? '';
            $this->price = (string) $product->price;
            $this->discount_price = $product->discount_price !== null ? (string) $product->discount_price : '';
            $this->quantity = $product->quantity !== null ? (string) $product->quantity : '';
            $this->existingFeaturedImage = $product->featured_image ?? '';
            $this->pageId = $product->page?->id;

            $this->checkSlugAvailability();
        }
    }

    /**
     * @return array<int, int>
     */
    private function vendorIds(): array
    {
        return Auth::user()->vendors()->pluck('product_vendors.id')->all();
    }

    #[Computed]
    public function myVendors()
    {
        return ProductVendor::whereIn('id', $this->vendorIds())->orderBy('name')->get();
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
    public function productBrands()
    {
        return ProductBrand::orderBy('sort_order')->orderBy('name')->get();
    }

    public function updatedFeaturedImage(): void
    {
        $this->validate(['featuredImage' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048']);
    }

    /**
     * Live slug-as-you-type — regenerates from the primary locale's name only
     * while the slug still matches what we last auto-generated, or is empty.
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

        $this->checkSlugAvailability();
    }

    public function updatedSlug(): void
    {
        $this->slug = Slug::lower($this->slug);
        $this->checkSlugAvailability();
    }

    private function checkSlugAvailability(): void
    {
        $this->slugAvailable = Slug::isAvailable($this->slug, $this->pageId);
    }

    public function save(): void
    {
        $vendorIds = $this->vendorIds();

        abort_unless(in_array((int) $this->vendor_id, $vendorIds, true), 403);

        if (empty($this->slug) && $this->primaryValue('name')) {
            $this->slug = Slug::make($this->primaryValue('name'));
        }

        $rules = $this->translatableRules([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        $rules['slug'] = [
            'required', 'string', 'max:255',
            ...Slug::uniqueRules($this->pageId),
        ];
        $rules['sku'] = [
            'nullable', 'string', 'max:100',
            $this->productId ? 'unique:products,sku,'.$this->productId : 'unique:products,sku',
        ];
        $rules['brand_id'] = 'nullable|integer|exists:product_brands,id';
        $rules['vendor_id'] = ['required', 'integer', 'in:'.implode(',', $vendorIds)];
        $rules['price'] = 'required|numeric|min:0';
        $rules['discount_price'] = 'nullable|numeric|min:0|lt:price';
        $rules['quantity'] = 'nullable|integer|min:0';
        $rules['category_ids'] = 'array';
        $rules['category_ids.*'] = 'integer|exists:categories,id,type,product';

        $this->validate($rules);

        $this->persistProduct($vendorIds);

        $this->dispatch('notify', message: $this->productId ? 'Product updated successfully' : 'Product created — an admin will review and activate it');

        $this->redirect(route('vendor.products'), navigate: true);
    }

    /**
     * @param  array<int, int>  $vendorIds
     */
    private function persistProduct(array $vendorIds): void
    {
        $data = [
            'name' => $this->translatablePayload('name'),
            'brand_id' => $this->brand_id !== '' ? (int) $this->brand_id : null,
            'vendor_id' => (int) $this->vendor_id,
            'sku' => $this->sku !== '' ? $this->sku : null,
            'price' => $this->price,
            'discount_price' => $this->discount_price !== '' ? $this->discount_price : null,
            'quantity' => $this->quantity !== '' ? $this->quantity : 0,
            'description' => $this->translatablePayload('description') ?: null,
        ];

        if (! empty($this->featuredImage)) {
            // existingFeaturedImage (when set) is the absolute URL MediaLibrary-style
            // values use elsewhere, not a disk-relative path, so the previous file
            // isn't deleted here — only a new one is stored.
            $path = $this->featuredImage->storeAs(
                'products',
                Str::uuid()->toString().'.'.$this->featuredImage->getClientOriginalExtension(),
                'public',
            );

            $data['featured_image'] = url(Storage::disk('public')->url($path));
            $this->existingFeaturedImage = $data['featured_image'];
        }

        if ($this->productId) {
            $product = Product::whereIn('vendor_id', $vendorIds)->findOrFail($this->productId);
            $product->update($data);
        } else {
            // New products stay inactive until an admin reviews and activates
            // them — same as Admin\Products\Form, and Vendor\Products\Index
            // doesn't expose a status toggle to match.
            $data['status'] = 'inactive';
            $product = Product::create($data);
            $this->productId = $product->id;
        }

        $product->categories()->sync($this->category_ids);

        Page::updateOrCreate(
            ['type' => 'product', 'product_id' => $product->id],
            [
                'user_id' => Auth::id(),
                'title' => $this->translatablePayload('name'),
                'slug' => $this->slug,
                'status' => $product->status,
                'description' => $this->translatablePayload('description') ?: null,
            ]
        );
    }

    public function render()
    {
        return view('livewire.vendor.products.form')
            ->layout('layouts.vendor', ['title' => $this->productId ? 'Edit Product' : 'New Product']);
    }
}
