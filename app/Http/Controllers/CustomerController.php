<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Page;
use App\Models\ProductVendor;
use App\Models\Setting;
use App\Support\Themes;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

/**
 * The customer account area behind a storefront login — dashboard, order
 * history, order detail, and profile editing. An ecommerce-only feature: the
 * routes abort 404 on any other active theme, so a portfolio or default-theme
 * site is never changed by this.
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

        return view('frontend.themes.'.Themes::view('account.dashboard'), $this->viewData([
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

        return view('frontend.themes.'.Themes::view('account.orders'), $this->viewData([
            'orders' => $orders,
            'currentSlug' => 'account',
        ]));
    }

    public function orderShow(string $orderNumber)
    {
        $this->ensureEcommerceTheme();

        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        abort_unless($order->belongsToCustomer(auth()->user()), 404, 'Unknown order.');

        $order->load(['items', 'transactions']);

        return view('frontend.themes.'.Themes::view('account.order'), $this->viewData([
            'order' => $order,
            'currentSlug' => 'account',
        ]));
    }

    public function profile()
    {
        $this->ensureEcommerceTheme();

        return view('frontend.themes.'.Themes::view('account.profile'), $this->viewData([
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
            'title' => Setting::get('seo_meta_title') ?: Setting::get('site_name'),
            'page' => null,
            'sections' => collect(),
            'navPages' => Page::ofType('page')->published()->orderBy('sort_order')->get(),
            'menuItems' => MenuItem::where('group', 'frontend')->where('is_active', true)->orderBy('sort_order')->get(),
            'showVendorLogin' => $this->showVendorLogin(),
        ];
    }

    /**
     * Whether the Vendor Login link should appear in the header/footer —
     * same rule as FrontendController::showVendorLogin().
     */
    private function showVendorLogin(): bool
    {
        $vendorRoleActive = Role::where('name', 'vendor')->where('status', 'active')->exists();

        return $vendorRoleActive && ProductVendor::active()->exists();
    }
}
