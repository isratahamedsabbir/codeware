<?php

namespace App\Livewire\Frontend;

use App\Models\Product;
use App\Models\Setting;
use App\Models\ShippingMethod;
use App\Services\OrderPlacement;
use App\Support\Cart;
use App\Support\PaymentMethods;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * The one-page order form on a product's campaign landing page: pick the
 * option(s) and quantity, type a name / phone / address, choose the delivery
 * area and confirm — no cart and no checkout page in between.
 *
 * It goes through the same server-side pipeline as /checkout
 * (App\Services\OrderPlacement), so prices, the discount and the shipping cost
 * are always worked out on the server; the browser only ever says "option 2,
 * quantity 3". The session cart is never touched.
 *
 * A landing visitor is not asked for an email. The order needs one to hang an
 * account on, so a guest's is derived from their phone number — the same
 * number later maps to the same account.
 */
class LandingOrder extends Component
{
    public int $productId;

    /** 'compact' (two columns, basic page) or 'wide' (stacked sections, standard page). */
    public string $layout = 'compact';

    /** Quantity per option, keyed by the option's index in options(). */
    public array $quantities = [];

    public string $customer_name = '';

    public string $customer_phone = '';

    public string $shipping_address = '';

    public string $notes = '';

    public ?int $shipping_method_id = null;

    public function mount(int $productId, string $layout = 'compact'): void
    {
        $this->productId = $productId;
        $this->layout = $layout === 'wide' ? 'wide' : 'compact';

        $first = $this->options->first(fn (array $option) => $option['in_stock']);

        foreach ($this->options as $index => $option) {
            $this->quantities[$index] = $first && $first['index'] === $index ? 1 : 0;
        }

        $this->shipping_method_id = $this->shippingMethods->first()?->id;

        if ($user = auth()->user()) {
            $this->customer_name = $user->name;
        }
    }

    #[Computed]
    public function product(): Product
    {
        return Product::active()->findOrFail($this->productId);
    }

    /**
     * What can be ordered: one entry per visible option combination, or a single
     * entry for a product with none. Rebuilt from the product on every request —
     * nothing here is trusted from the browser.
     *
     * @return Collection<int, array{index: int, label: string, attributes: array<string, string>, price: float, regular: float, discounted: bool, image: ?string, in_stock: bool}>
     */
    #[Computed]
    public function options(): Collection
    {
        $product = $this->product;
        $rows = $product->visibleVariations();

        if ($rows === []) {
            $discount = $product->effectiveDiscount();

            return collect([[
                'index' => 0,
                'label' => (string) $product->name,
                'attributes' => [],
                'price' => $discount ?? (float) $product->price,
                'regular' => (float) $product->price,
                'discounted' => $discount !== null,
                'image' => $product->featured_image,
                'in_stock' => ! $product->is_upcoming && $product->inStock(),
            ]]);
        }

        return collect($rows)->values()->map(function (array $row, int $index) use ($product) {
            $attributes = array_map('strval', $row['attributes'] ?? []);
            $regular = $product->variationPrice($attributes);
            $discount = $product->effectiveDiscount($attributes);

            return [
                'index' => $index,
                'label' => implode(' · ', array_values($attributes)),
                'attributes' => $attributes,
                'price' => $discount ?? $regular,
                'regular' => $regular,
                'discounted' => $discount !== null,
                'image' => filled($row['image'] ?? null) ? $row['image'] : $product->featured_image,
                'in_stock' => ! $product->is_upcoming && $product->variationInStock($attributes),
            ];
        });
    }

    #[Computed]
    public function shippingMethods(): Collection
    {
        return ShippingMethod::active()->orderBy('cost')->get();
    }

    #[Computed]
    public function subtotal(): float
    {
        return round($this->options->sum(fn (array $option) => $option['price'] * $this->quantityOf($option['index'])), 2);
    }

    #[Computed]
    public function vat(): float
    {
        return Setting::vatFor($this->subtotal);
    }

    #[Computed]
    public function shippingCost(): float
    {
        return (float) ($this->shippingMethods->firstWhere('id', $this->shipping_method_id)?->cost ?? 0);
    }

    #[Computed]
    public function total(): float
    {
        return round($this->subtotal + $this->vat + $this->shippingCost, 2);
    }

    public function quantityOf(int $index): int
    {
        return max(0, min(Cart::MAX_QUANTITY, (int) ($this->quantities[$index] ?? 0)));
    }

    /** Tapping a card selects it (quantity 1) when nothing of it is chosen yet. */
    public function select(int $index): void
    {
        if ($this->quantityOf($index) === 0 && ($this->options[$index]['in_stock'] ?? false)) {
            $this->quantities[$index] = 1;
        }
    }

    public function increase(int $index): void
    {
        if ($this->options[$index]['in_stock'] ?? false) {
            $this->quantities[$index] = min(Cart::MAX_QUANTITY, $this->quantityOf($index) + 1);
        }
    }

    public function decrease(int $index): void
    {
        $this->quantities[$index] = max(0, $this->quantityOf($index) - 1);
    }

    public function placeOrder()
    {
        abort_unless((bool) Setting::get('shop_enabled', true), 503, 'The shop is currently closed for new orders.');

        $validated = $this->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => ['required', 'string', 'regex:/^\+?[0-9\s-]{10,15}$/'],
            'shipping_address' => 'required|string|max:2000',
            'shipping_method_id' => ['nullable', 'integer', Rule::exists('shipping_methods', 'id')->where('status', 'active')],
            'notes' => 'nullable|string|max:1000',
        ], [
            'customer_phone.regex' => __('Enter a valid phone number.'),
        ]);

        $lines = $this->options
            ->filter(fn (array $option) => $this->quantityOf($option['index']) > 0)
            ->map(fn (array $option) => [
                'product_id' => $this->productId,
                'quantity' => $this->quantityOf($option['index']),
                'attributes' => $option['attributes'],
            ])
            ->values();

        if ($lines->isEmpty()) {
            $this->addError('quantities', __('Pick a product first.'));

            return;
        }

        if ($this->options->contains(fn (array $option) => $this->quantityOf($option['index']) > 0 && ! $option['in_stock'])) {
            $this->addError('quantities', __('Sorry, one of the selected items is out of stock.'));

            return;
        }

        $digits = preg_replace('/\D/', '', $validated['customer_phone']);

        try {
            $order = app(OrderPlacement::class)->placeProducts(
                $lines->all(),
                [
                    'customer_name' => $validated['customer_name'],
                    'customer_email' => auth()->user()?->email ?? "{$digits}@guest.invalid",
                    'customer_phone' => $validated['customer_phone'],
                    'shipping_address' => $validated['shipping_address'],
                    'payment_method' => PaymentMethods::COD,
                    'notes' => filled($validated['notes']) ? $validated['notes'] : null,
                ],
                null,
                $validated['shipping_method_id'],
            );
        } catch (ValidationException $e) {
            foreach ($e->errors() as $messages) {
                $this->addError('quantities', $messages[0] ?? '');
            }

            return;
        }

        session()->flash('placed_order', $order->order_number);

        return redirect()->route('checkout.confirmation', $order->order_number);
    }

    public function render()
    {
        return view($this->layout === 'wide' ? 'livewire.frontend.landing-order-wide' : 'livewire.frontend.landing-order');
    }
}
