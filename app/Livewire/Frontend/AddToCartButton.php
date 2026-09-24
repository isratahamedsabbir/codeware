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

    /** Quantity of this product's plain (no-options) line already in the cart. */
    public int $inCart = 0;

    /**
     * Quantity already in the cart per option combination of this product,
     * keyed by Cart::signature() — lets the picker swap its add button for a
     * stepper once the selected combination is in the cart.
     *
     * @var array<string, int>
     */
    public array $inCartByCombo = [];

    public function mount(int $productId, string $slug = '', int $quantity = 1, bool $adjustable = false, bool $requiresOptions = false, bool $showPicker = false, bool $hasVariations = false, ?bool $inStock = null, ?bool $isUpcoming = null): void
    {
        $this->productId = $productId;
        $this->slug = $slug;
        $this->quantity = max(1, min(Cart::MAX_QUANTITY, $quantity));
        $this->adjustable = $adjustable;
        $this->requiresOptions = $requiresOptions;
        $this->showPicker = $showPicker;
        $this->hasVariations = $hasVariations;
        $this->syncCart();

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
        $this->syncCart();
        $this->dispatch('cart-updated');
    }

    /**
     * The in-cart stepper's "+": one more of the line (plain product or the
     * given option combination), through the same stock/option checks as add().
     *
     * @param  array<string, string>|null  $attributes
     */
    public function increment(?array $attributes = []): void
    {
        $quantity = $this->quantity;
        $this->quantity = 1;
        $this->add($attributes);
        $this->quantity = $quantity;
    }

    /**
     * The in-cart stepper's "−": one fewer of the line; at zero the line leaves
     * the cart and the control falls back to its "Add to cart" button.
     *
     * @param  array<string, string>|null  $attributes
     */
    public function decrement(?array $attributes = []): void
    {
        $attributes = $attributes === null ? [] : array_map('strval', $attributes);
        $key = Cart::lineKey($this->productId, $attributes);

        Cart::setQuantity($key, Cart::quantity($key) - 1);

        $this->syncCart();
        $this->added = $this->inCart > 0 || $this->inCartByCombo !== [];
        $this->dispatch('cart-updated');
    }

    private function syncCart(): void
    {
        $this->inCart = Cart::quantity($this->productId);

        $prefix = $this->productId.'::';
        $this->inCartByCombo = [];

        foreach (Cart::items() as $key => $quantity) {
            if (str_starts_with((string) $key, $prefix)) {
                $this->inCartByCombo[substr((string) $key, strlen($prefix))] = (int) $quantity;
            }
        }
    }

    public function render()
    {
        // Whenever a product can carry options we need its full variation data:
        // on the detail page the picker is rendered inline, and on product cards
        // an options product opens a picker modal instead of the old link.
        $product = $this->requiresOptions || $this->showPicker
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
