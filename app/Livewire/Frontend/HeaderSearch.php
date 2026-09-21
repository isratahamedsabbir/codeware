<?php

namespace App\Livewire\Frontend;

use App\Models\Product;
use Livewire\Component;

/**
 * Storefront header search with a live product-suggestion dropdown. Submitting
 * the form goes to the shop search page (same GET route as the old inline
 * form), while typing shows matching products under the input — both en and bn
 * names are matched, mirroring the shop's free-text search.
 */
class HeaderSearch extends Component
{
    public string $query = '';

    /**
     * Styling variant — true when embedded in the dark header bar (desktop),
     * false for the light mobile menu panel.
     */
    public bool $onDark = true;

    /** @var array<int, array{slug: string, name: string, image: ?string, price: string, old_price: ?string}> */
    public array $suggestions = [];

    public function mount(): void
    {
        $this->query = (string) request()->query('search', '');
    }

    public function updatedQuery(): void
    {
        $term = trim($this->query);

        if (mb_strlen($term) < 1) {
            $this->suggestions = [];

            return;
        }

        $needle = '%'.mb_strtolower($term).'%';

        $this->suggestions = Product::active()
            ->where(function ($q) use ($needle) {
                // json_unquote(json_extract(...)) returns a binary-collation string
                // in MySQL, making a plain LIKE case-sensitive even on a
                // case-insensitive column — so compare both sides lowercased.
                $q->whereRaw('LOWER(json_unquote(json_extract(`name`, \'$."en"\'))) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(json_unquote(json_extract(`name`, \'$."bn"\'))) LIKE ?', [$needle]);
            })
            ->orderBy('sort_order')
            ->limit(6)
            ->get()
            ->map(fn (Product $product) => [
                'slug' => $product->slug,
                'name' => $product->name,
                'image' => $product->featured_image,
                'price' => format_money($product->hasDiscount() ? $product->discount_price : $product->price),
                'old_price' => $product->hasDiscount() ? format_money($product->price) : null,
            ])
            ->all();
    }

    public function render()
    {
        return view('livewire.frontend.header-search');
    }
}
