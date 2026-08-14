<?php

namespace App\Livewire\Admin\Products;

use App\Models\Page;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\AdminActivity;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    public ?int $productId = null;

    public ?int $pageId = null;

    #[Validate('required|string|max:255')]
    public string $name_en = '';

    #[Validate('nullable|string|max:255')]
    public string $name_bn = '';

    #[Validate('nullable|string|max:255')]
    public string $slug = '';

    #[Validate('nullable|integer|exists:categories,id,type,product')]
    public ?int $product_category_id = null;

    #[Validate('in:active,inactive')]
    public string $status = 'active';

    #[Validate('required|numeric|min:0')]
    public string $price = '0';

    public bool $is_featured = false;

    public int $sort_order = 0;

    #[Validate('nullable|string')]
    public string $description_en = '';

    #[Validate('nullable|string')]
    public string $description_bn = '';

    public string $featured_image = '';

    public string $featuredImagePickerId = '';

    public function mount(?int $id = null): void
    {
        $this->featuredImagePickerId = 'featured-image-'.Str::uuid()->toString();

        if ($id) {
            $product = Product::findOrFail($id);
            $this->productId = $id;
            $this->name_en = $product->getTranslation('name', 'en', false) ?? '';
            $this->name_bn = $product->getTranslation('name', 'bn', false) ?? '';
            $this->slug = $product->slug;
            $this->product_category_id = $product->product_category_id;
            $this->status = $product->status;
            $this->price = (string) $product->price;
            $this->is_featured = (bool) $product->is_featured;
            $this->sort_order = $product->sort_order;
            $this->description_en = $product->getTranslation('description', 'en', false) ?? '';
            $this->description_bn = $product->getTranslation('description', 'bn', false) ?? '';

            $this->featured_image = $product->featured_image ?? '';

            // SEO now lives entirely on the paired Page record, edited via the Page
            // screen — this form only keeps the Page in sync on title/slug/status.
            $this->pageId = $product->page?->id;
        }
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
            now()->addMinutes(config('app.puck_session', 5))
        )->plainTextToken;

        $url = config('cms.editor_base_url')."/puck/edit/product/{$this->pageId}#token={$token}";
        $this->js('window.open('.json_encode($url).', \'_blank\')');
    }

    public function saveAndOpenPageBuilder(): void
    {
        if (empty($this->slug) && $this->name_en) {
            $this->slug = Str::slug($this->name_en);
        }

        $rules = $this->getRules();
        $rules['slug'] = $this->productId
            ? 'required|string|max:255|unique:products,slug,'.$this->productId
            : 'required|string|max:255|unique:products,slug';

        $this->validate($rules);

        $this->persistProduct();

        $this->dispatch('notify', message: $this->productId ? 'Product updated successfully' : 'Product created successfully');

        auth()->user()->tokens()->where('name', 'puck-builder')->delete();

        $token = auth()->user()->createToken(
            'puck-builder',
            ['*'],
            now()->addMinutes(config('app.puck_session', 5))
        )->plainTextToken;

        $url = config('cms.editor_base_url')."/puck/edit/product/{$this->pageId}#token={$token}";
        $this->js('window.open('.json_encode($url).', \'_blank\')');

        $this->redirect(route('admin.products.edit', $this->productId), navigate: true);
    }

    public function save(): void
    {
        if (empty($this->slug) && $this->name_en) {
            $this->slug = Str::slug($this->name_en);
        }

        $rules = $this->getRules();
        $rules['slug'] = $this->productId
            ? 'required|string|max:255|unique:products,slug,'.$this->productId
            : 'required|string|max:255|unique:products,slug';

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
            'name' => array_filter(['en' => $this->name_en, 'bn' => $this->name_bn]),
            'slug' => $this->slug,
            'status' => $this->status,
            'price' => $this->price,
            'is_featured' => $this->is_featured,
            'sort_order' => $this->sort_order,
            'description' => array_filter(['en' => $this->description_en, 'bn' => $this->description_bn]) ?: null,
            'featured_image' => $this->featured_image ?: null,
        ];

        if ($this->productId) {
            $product = Product::findOrFail($this->productId);
            $product->update($data);
        } else {
            $product = Product::create($data);
            $this->productId = $product->id;
        }

        $page = Page::updateOrCreate(
            ['type' => 'product', 'product_id' => $product->id],
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
            "Product #{$product->id}: {$this->name_en}",
        );
    }

    public function render()
    {
        return view('livewire.admin.products.form')
            ->layout('layouts.admin', ['title' => $this->productId ? 'Edit Product' : 'New Product']);
    }
}
