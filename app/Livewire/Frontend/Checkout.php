<?php

namespace App\Livewire\Frontend;

use App\Models\Setting;
use App\Services\OrderPlacement;
use App\Support\Cart;
use App\Support\PaymentMethods;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * The /checkout page — the session cart's orderable lines plus a customer
 * form. Submitting turns the cart into an Order through the same server-side
 * pipeline as the order API (App\Services\OrderPlacement), then clears the
 * cart and bounces to the order confirmation page. The shop being disabled
 * stops checkout entirely (503), matching the order API's behaviour.
 */
class Checkout extends Component
{
    public array $items = [];

    public int $count = 0;

    public float $subtotal = 0;

    public string $customer_name = '';

    public string $customer_email = '';

    public string $customer_phone = '';

    public string $shipping_address = '';

    public string $payment_method = 'cod';

    public string $notes = '';

    public string $coupon_code = '';

    public array $paymentMethods = [];

    public function mount(): void
    {
        $this->paymentMethods = PaymentMethods::available();

        if ($user = auth()->user()) {
            $this->customer_name = $user->name;
            $this->customer_email = $user->email;
        }

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

    public function placeOrder()
    {
        abort_unless((bool) Setting::get('shop_enabled', true), 503, 'The shop is currently closed for new orders.');

        $lines = Cart::lines();

        if ($lines->isEmpty()) {
            $this->addError('cart', __('Your cart is empty.'));

            return;
        }

        // Any cart line that can't be resolved into an orderable line — a
        // product that went inactive, an upcoming product, or a variant
        // combination that vanished — blocks checkout so the shopper revisits
        // their cart.
        if (array_diff_key(Cart::items(), $lines->pluck('key')->flip()->all()) !== []) {
            $this->addError('cart', __('Some items in your cart are no longer available. Remove them and try again.'));

            return;
        }

        $validated = $this->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:30',
            'shipping_address' => 'required|string|max:2000',
            'payment_method' => ['required', 'string', Rule::in(array_keys(PaymentMethods::available()))],
            'notes' => 'nullable|string|max:1000',
            'coupon_code' => 'nullable|string|max:50',
        ]);

        try {
            $order = app(OrderPlacement::class)->placeProducts(
                $lines->map(fn (array $line) => [
                    'product_id' => $line['product_id'],
                    'quantity' => $line['quantity'],
                    'attributes' => $line['attributes'],
                ])->all(),
                [
                    'customer_name' => $validated['customer_name'],
                    'customer_email' => $validated['customer_email'],
                    'customer_phone' => $validated['customer_phone'],
                    'shipping_address' => $validated['shipping_address'],
                    'payment_method' => $validated['payment_method'],
                    'notes' => $validated['notes'] !== '' ? $validated['notes'] : null,
                ],
                $validated['coupon_code'] !== '' ? $validated['coupon_code'] : null,
            );
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0] ?? '');
            }

            return;
        }

        Cart::clear();
        $this->dispatch('cart-updated');
        session()->flash('placed_order', $order->order_number);

        return redirect()->route('checkout.confirmation', $order->order_number);
    }

    public function render()
    {
        return view('livewire.frontend.checkout');
    }
}
