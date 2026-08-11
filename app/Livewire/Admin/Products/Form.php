<?php

namespace App\Livewire\Admin\Products;

use App\Models\Page;
use App\Models\Product;
use App\Models\ProductCategory;
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
    public bool $is_featured = false;
    public int $sort_order = 0;

    #[Validate('nullable|string')]
    public string $description_en = '';
    #[Validate('nullable|string')]
    public string $description_bn = '';
    public string $featured_image = '';
    public string $og_image = '';

    public string $featuredImagePickerId = '';
    public string $ogImagePickerId = '';

    #[Validate('nullable|string|max:255')]
    public string $seo_title_en = '';
    #[Validate('nullable|string|max:255')]
    public string $seo_title_bn = '';
    #[Validate('nullable|string')]
    public string $seo_description_en = '';
    #[Validate('nullable|string')]
    public string $seo_description_bn = '';

    public function mount(?int $id = null): void
    {
        $this->featuredImagePickerId = 'featured-image-' . Str::uuid()->toString();
        $this->ogImagePickerId = 'og-image-' . Str::uuid()->toString();

        if ($id) {
            $product = Product::findOrFail($id);
            $this->productId           = $id;
            $this->name_en             = $product->getTranslation('name', 'en', false) ?? '';
            $this->name_bn             = $product->getTranslation('name', 'bn', false) ?? '';
            $this->slug                = $product->slug;
            $this->product_category_id = $product->product_category_id;
            $this->status              = $product->status;
            $this->is_featured         = (bool) $product->is_featured;
            $this->sort_order          = $product->sort_order;
            $this->description_en      = $product->getTranslation('description', 'en', false) ?? '';
            $this->description_bn      = $product->getTranslation('description', 'bn', false) ?? '';

            $this->featured_image = $product->featured_image ?? '';
            $this->og_image = $product->og_image ?? '';

            $this->seo_title_en        = $product->getTranslation('seo_title', 'en', false) ?? '';
            $this->seo_title_bn        = $product->getTranslation('seo_title', 'bn', false) ?? '';
            $this->seo_description_en  = $product->getTranslation('seo_description', 'en', false) ?? '';
            $this->seo_description_bn  = $product->getTranslation('seo_description', 'bn', false) ?? '';

            $page = $product->page;
            $this->pageId = $page?->id;
        }
    }

    #[Computed]
    public function productCategories()
    {
        return ProductCategory::orderBy('sort_order')->get();
    }

    public function openPuckEditor(): void
    {
        if (!$this->pageId) return;

        auth()->user()->tokens()->where('name', 'puck-builder')->delete();

        $token = auth()->user()->createToken(
            'puck-builder',
            ['*'],
            now()->addMinutes(config('app.puck_session', 5))
        )->plainTextToken;

        $url = config('cms.editor_base_url') . "/puck/edit/product/{$this->pageId}#token={$token}";
        $this->js('window.open(' . json_encode($url) . ', \'_blank\')');
    }

    public function saveAndOpenPageBuilder(): void
    {
        if (empty($this->slug) && $this->name_en) {
            $this->slug = Str::slug($this->name_en);
        }

        $rules = $this->getRules();
        $rules['slug'] = $this->productId
            ? 'required|string|max:255|unique:products,slug,' . $this->productId
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

        $url = config('cms.editor_base_url') . "/puck/edit/product/{$this->pageId}#token={$token}";
        $this->js('window.open(' . json_encode($url) . ', \'_blank\')');

        $this->redirect(route('admin.products.edit', $this->productId), navigate: true);
    }

    public function save(): void
    {
        if (empty($this->slug) && $this->name_en) {
            $this->slug = Str::slug($this->name_en);
        }

        $rules = $this->getRules();
        $rules['slug'] = $this->productId
            ? 'required|string|max:255|unique:products,slug,' . $this->productId
            : 'required|string|max:255|unique:products,slug';

        $this->validate($rules);

        $this->persistProduct();

        $this->dispatch('notify', message: $this->productId ? 'Product updated successfully' : 'Product created successfully');

        $this->redirect(route('admin.products'), navigate: true);
    }

    private function persistProduct(): void
    {
        $data = [
            'product_category_id' => $this->product_category_id,
            'name'   => array_filter(['en' => $this->name_en, 'bn' => $this->name_bn]),
            'slug'   => $this->slug,
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'sort_order'  => $this->sort_order,
            'description' => array_filter(['en' => $this->description_en, 'bn' => $this->description_bn]) ?: null,
            'featured_image' => $this->featured_image ?: null,
            'og_image'  => $this->og_image ?: null,
            'seo_title'      => array_filter(['en' => $this->seo_title_en, 'bn' => $this->seo_title_bn]) ?: null,
            'seo_description'=> array_filter(['en' => $this->seo_description_en, 'bn' => $this->seo_description_bn]) ?: null,
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
                'user_id'         => auth()->id(),
                'title'           => array_filter(['en' => $this->name_en, 'bn' => $this->name_bn]),
                'slug'            => $this->slug,
                'status'          => $this->status,
                'sort_order'      => $this->sort_order,
                'description'     => array_filter(['en' => $this->description_en, 'bn' => $this->description_bn]) ?: null,
                'og_image'        => $this->og_image ?: null,
                'seo_title'       => array_filter(['en' => $this->seo_title_en, 'bn' => $this->seo_title_bn]) ?: null,
                'seo_description' => array_filter(['en' => $this->seo_description_en, 'bn' => $this->seo_description_bn]) ?: null,
            ]
        );
        $this->pageId = $page->id;
    }

    public function render()
    {
        return view('livewire.admin.products.form')
            ->layout('layouts.admin', ['title' => $this->productId ? 'Edit Product' : 'New Product']);
    }
}
