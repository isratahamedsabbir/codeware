<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Setting;
use App\Support\Frontend;
use App\Support\Themes;
use Illuminate\Http\Request;

/**
 * The customer account area behind a storefront login — dashboard, order
 * history, order detail, and profile editing. An ecommerce-only feature: these
 * routes are only registered by routes/web/ecommerce.php, and its 'theme' guard
 * 404s them unless the ecommerce theme is active, so on any other theme they
 * never reach this controller. ensureEcommerceTheme() below repeats that check
 * so the controller is still safe to call directly.
 */
class CustomerController extends Controller
{
    public function dashboard()
    {
        $this->ensureEcommerceTheme();

        $orders = Order::forCustomer(auth()->user())
            ->with(['items'])
            ->latest()
            ->limit(5)
            ->get();

        return view(Themes::viewOrFail('account.dashboard'), $this->viewData([
            'orders' => $orders,
            'currentSlug' => 'account',
        ]));
    }

    public function orders(Request $request)
    {
        $this->ensureEcommerceTheme();

        $orders = Order::forCustomer(auth()->user())
            ->with(['items'])
            ->latest()
            ->paginate(Setting::perPage())
            ->withQueryString();

        return view(Themes::viewOrFail('account.orders'), $this->viewData([
            'orders' => $orders,
            'currentSlug' => 'account',
        ]));
    }

    public function orderShow(string $orderNumber)
    {
        $this->ensureEcommerceTheme();

        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        abort_unless($order->belongsToCustomer(auth()->user()), 404, 'Unknown order.');

        $order->load(['items.product', 'transactions']);

        return view(Themes::viewOrFail('account.order'), $this->viewData([
            'order' => $order,
            'currentSlug' => 'account',
        ]));
    }

    public function profile()
    {
        $this->ensureEcommerceTheme();

        return view(Themes::viewOrFail('account.profile'), $this->viewData([
            'currentSlug' => 'account',
        ]));
    }

    /**
     * The customer account is an ecommerce-store feature — on any other theme
     * (portfolio, default, ...) the routes are simply not there.
     */
    private function ensureEcommerceTheme(): void
    {
        abort_unless(Themes::active() === 'ecommerce', 404, 'This page only exists on an ecommerce site.');
    }

    /**
     * The shared view payload every account page gets — the same shape the
     * storefront pages receive, so the theme's header/footer partials work.
     */
    private function viewData(array $data = []): array
    {
        return $data + [
            'title' => Setting::translated('seo_meta_title') ?: Setting::get('site_name'),
            'page' => null,
            'sections' => collect(),
            'navPages' => Frontend::navPages(),
            'menuItems' => Frontend::menuItems(),
            'showVendorLogin' => Frontend::showVendorLogin(),
            'showDeliveryLogin' => Frontend::showDeliveryLogin(),
        ];
    }
}
