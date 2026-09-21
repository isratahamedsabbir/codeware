<?php

namespace App\Livewire\Frontend;

use App\Models\Product;
use App\Support\Cart;
use Livewire\Component;

/**
 * The storefront's "Add to cart" control — a one-tap button on product cards
 * (quantity fixed at 1), an adjustable stepper + add button on the product
 * detail page, and on variant products the full option picker (attribute
 * swatches, combination price/stock preview). Writing the cart never needs an
 * account: it lands in the guest session (App\Support\Cart). Every change
 * dispatches `cart-updated` so the header count and cart page stay in sync.
 *
 * Cards for products with options pass `requires-options`, which renders a
 * "Select options" link to the detail page instead of a button — the base
 * product must never be added while a combination exists.
 */
class AddToCartButton extends Component
{
    public int $productId;

    /** Detail-page slug for the card "Select options" link (cards pass it in). */
    public string $slug = '';

    public int $quantity = 1;

    public bool $adjustable = false;

    public bool $requiresOptions = false;

    /** When true the detail page asked us to own the option picker too. */
    public bool $showPicker = false;

    /** Cards pass this in to avoid a product lookup just to learn it has options. */
    public bool $hasVariations = false;

    public bool $added = false;

    public bool $inStock = false;

    public bool $isUpcoming = false;

    public function mount(int $productId, string $slug = '', int $quantity = 1, bool $adjustable = false, bool $requiresOptions = false, bool $showPicker = false, bool $hasVariations = false, ?bool $inStock = null, ?bool $isUpcoming = null): void
    {
        $this->productId = $productId;
        $this->slug = $slug;
        $this->quantity = max(1, min(Cart::MAX_QUANTITY, $quantity));
        $this->adjustable = $adjustable;
        $this->requiresOptions = $requiresOptions;
        $this->showPicker = $showPicker;
        $this->hasVariations = $hasVariations;

        // Product cards already know these, so they pass them in and save the
        // lookup; the detail page leaves them null and we read the product
        // ourselves.
        if ($inStock !== null) {
            $this->inStock = $inStock;
            $this->isUpcoming = (bool) $isUpcoming;

            return;
        }

        $product = Product::find($productId);

        $this->inStock = $product?->inStock() ?? false;
        $this->isUpcoming = (bool) ($product?->is_upcoming ?? false);
    }

    public function increase(): void
    {
        if ($this->quantity < Cart::MAX_QUANTITY) {
            $this->quantity++;
        }
    }

    public function decrease(): void
    {
        if ($this->quantity > 1) {
            $this->quantity--;
        }
    }

    /**
     * @param  array<string, string>|null  $attributes
     */
    public function add(?array $attributes = []): void
    {
        $attributes = $attributes === null ? [] : array_map('strval', $attributes);

        // Cards use requires-options: a base line would bypass the picker.
        if ($this->requiresOptions && $attributes === []) {
            return;
        }

        if (! $this->inStock || $this->isUpcoming) {
            return;
        }

        $product = Product::find($this->productId);

        if (! $product) {
            return;
        }

        if ($attributes !== []) {
            if ($product->variationRow($attributes) === null) {
                $this->addError('options', 'The selected combination is no longer available.');

                return;
            }

            if (! $product->variationInStock($attributes)) {
                $this->addError('options', 'This combination is currently out of stock.');

                return;
            }
        }

        Cart::add($this->productId, $this->quantity, $attributes);
        $this->added = true;
        $this->dispatch('cart-updated');
    }

    public function render()
    {
        $product = ($this->requiresOptions && $this->slug === '') || $this->showPicker
            ? Product::find($this->productId)
            : null;

        $variations = $product?->visibleVariations() ?? [];
        $hasVisibleVariations = $this->showPicker ? $variations !== [] : $this->hasVariations;

        return view('livewire.frontend.add-to-cart-button', [
            'product' => $product,
            'slug' => $this->slug !== '' ? $this->slug : $product?->slug,
            'variations' => $variations,
            'hasVisibleVariations' => $hasVisibleVariations,
        ]);
    }
}
