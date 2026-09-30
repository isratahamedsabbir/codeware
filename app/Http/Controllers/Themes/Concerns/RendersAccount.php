<?php

namespace App\Http\Controllers\Themes\Concerns;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Http\Request;

/**
 * The customer account area behind a storefront login — dashboard, order
 * history, order detail, and profile editing.
 *
 * Used by the ecommerce theme's controllers, and by no other theme's: the
 * account area is an ecommerce-store feature, and routes/web/ecommerce.php is
 * the only file that registers these routes at all.
 *
 * Note what is *not* here any more: the old CustomerController had an
 * ensureEcommerceTheme() guard aborting unless the active theme was literally
 * named "ecommerce", on the grounds that the controller was shared and could
 * be called directly. The guard was already belt-and-braces (the route group's
 * 'theme' middleware is what keeps /account off a portfolio site), and it is
 * now plainly wrong: the account pages live in Themes/Ecommerce/, so a theme
 * that wanted them would be *in* this trait's users rather than named after
 * someone else's theme. The routing guard is the single rule, and it is keyed
 * on the route's template rather than on a theme's name.
 */
trait RendersAccount
{
    public function dashboard()
    {
        $orders = Order::forCustomer(auth()->user())
            ->with(['items'])
            ->latest()
            ->limit(5)
            ->get();

        return $this->view('account.dashboard', [
            'orders' => $orders,
            'currentSlug' => 'account',
        ]);
    }

    public function orders(Request $request)
    {
        $orders = Order::forCustomer(auth()->user())
            ->with(['items'])
            ->latest()
            ->paginate(Setting::perPage())
            ->withQueryString();

        return $this->view('account.orders', [
            'orders' => $orders,
            'currentSlug' => 'account',
        ]);
    }

    public function orderShow(string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        abort_unless($order->belongsToCustomer(auth()->user()), 404, 'Unknown order.');

        return $this->view('account.order', [
            'order' => $order->load(['items.product', 'transactions']),
            'currentSlug' => 'account',
        ]);
    }

    public function profile()
    {
        return $this->view('account.profile', [
            'currentSlug' => 'account',
        ]);
    }
}
