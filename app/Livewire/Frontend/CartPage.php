<?php

namespace App\Livewire\Frontend;

use App\Support\Cart;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * The /cart page body — the session cart's lines with quantity steppers,
 * per-line totals, an unbounded "clear cart" action, and a subtotal summary
 * linking on to checkout. Lines are keyed (plain product id or a variant
 * signature) so every quantity/remove action targets the exact line, including
 * the chosen option combinations.
 */
class CartPage extends Component
{
    public array $items = [];

    public int $count = 0;

    public float $subtotal = 0;

    public function mount(): void
    {
        $this->refresh();
    }

    #[On('cart-updated')]
    public function refresh(): void
    {
        $this->items = Cart::lines()->map(fn (array $line) => [
            'key' => $line['key'],
            'attributes' => $line['attributes'],
            'options_label' => Cart::optionsLabel($line['attributes']),
            'product_id' => $line['product_id'],
            'name' => $line['product']->name,
            'url' => route('products.show', $line['product']->slug),
            'image' => $line['product']->featured_image,
            'unit_price_label' => format_money($line['unit_price']),
            'discount_label' => $line['discount_price'] !== null
                ? format_money($line['discount_price'])
                : null,
            'line_total_label' => format_money($line['line_total']),
            'quantity' => $line['quantity'],
        ])->all();

        $this->count = (int) collect($this->items)->sum('quantity');
        $this->subtotal = Cart::subtotal();
    }

    public function increase(int|string $key): void
    {
        Cart::add(Cart::parseLineKey($key)[0], 1, Cart::parseLineKey($key)[1]);
        $this->refresh();
        $this->dispatch('cart-updated');
    }

    public function decrease(int|string $key): void
    {
        Cart::setQuantity($key, Cart::quantity($key) - 1);
        $this->refresh();
        $this->dispatch('cart-updated');
    }

    public function remove(int|string $key): void
    {
        Cart::remove($key);
        $this->refresh();
        $this->dispatch('cart-updated');
    }

    public function clear(): void
    {
        Cart::clear();
        $this->refresh();
        $this->dispatch('cart-updated');
    }

    public function render()
    {
        return view('livewire.frontend.cart-page');
    }
}
