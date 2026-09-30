<?php

namespace App\Http\Controllers\Themes\Concerns;

use App\Models\Order;
use Illuminate\Support\Facades\URL;

/**
 * The cart, the checkout, and the post-order confirmation.
 *
 * Session-backed carts (see App\Support\Cart) that turn into orders via the
 * same server-side pipeline as the order API. Only exists while the orders
 * feature is on — the route group in routes/web/ecommerce.php gates that, and
 * only the ecommerce theme registers these routes at all.
 *
 * Both the cart and the checkout are open to guests: a visitor fills the form
 * in and pays on delivery without ever making an account. The order still
 * belongs to somebody — App\Services\OrderPlacement resolves the account from
 * the email they type, creating one when the address is new — so nothing is
 * lost by not signing in first, and the order turns up in that customer's
 * account history the moment they can get back in through a password reset.
 */
trait RendersCart
{
    /**
     * The shopping cart page — the session cart's lines, quantity controls and
     * summary live in the CartPage Livewire component (see App\Support\Cart).
     */
    public function cart()
    {
        return $this->view('cart', [
            'title' => __('My cart'),
            'currentSlug' => 'cart',
        ]);
    }

    /**
     * The checkout page — cart summary plus a customer form that turns the cart
     * into an Order (see the Checkout Livewire component).
     */
    public function checkout()
    {
        return $this->view('checkout', [
            'title' => __('Checkout'),
            'currentSlug' => 'checkout',
        ]);
    }

    /**
     * The post-checkout confirmation page. Only reachable for the order that
     * was just placed in this session — anything else bounces back to the shop,
     * so an arbitrary (or guessed) order number can't be browsed.
     */
    public function orderConfirmation(string $orderNumber)
    {
        if (session('placed_order') !== $orderNumber) {
            return redirect()->route('shop');
        }

        $order = Order::with('items.product')->where('order_number', $orderNumber)->firstOrFail();

        return $this->view('order-confirmation', [
            'order' => $order,
            // The same permanent signed links the invoice QR code points at, so
            // the shopper can view/print or download the invoice without an account.
            'invoiceUrl' => URL::signedRoute('invoices.public.show', ['order' => $order->order_number]),
            'invoiceDownloadUrl' => URL::signedRoute('invoices.public.download', ['order' => $order->order_number]),
            'title' => __('Order placed'),
            'currentSlug' => 'checkout',
        ]);
    }
}
