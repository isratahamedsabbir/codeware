<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\VoucherController;
use App\Models\Setting;
use Illuminate\Support\Facades\Route;

Route::get('/', [FrontendController::class, 'home'])->name('home');

// /home is just an alias for / — same controller method and view, so it
// keeps the homepage's own hero styling instead of the generic page layout.
Route::get('/home', [FrontendController::class, 'home']);

// E-commerce storefront — only reachable through the ecommerce theme's
// templates (or any theme that provides its own shop/product templates via
// Themes::view()). Category slugs live on each category's paired Page;
// brand/tag slugs are derived from the primary-locale name.
Route::get('/shop', [FrontendController::class, 'shop'])->name('shop');
Route::get('/products/{slug}', [FrontendController::class, 'product'])->name('products.show');
Route::get('/category/{slug}', [FrontendController::class, 'category'])->name('shop.category');
Route::get('/brand/{slug}', [FrontendController::class, 'brand'])->name('shop.brand');
Route::get('/tag/{slug}', [FrontendController::class, 'tag'])->name('shop.tag');

// Advertisement click tracking — counts the click, then sends the visitor to
// the banner's destination URL. Gated by the same feature flag that renders
// the banner on the product page.
Route::middleware('feature:advertisements')->group(function () {
    Route::get('/ad/{code}', [FrontendController::class, 'adClick'])->name('advertisements.click');
});

// Saved favorites — guests keep a session bag that merges into their account
// the moment they sign in (see App\Support\Favorites). No auth required.
Route::get('/favorites', [FrontendController::class, 'favorites'])->name('favorites');

// Blog — the public posts feed (listing + single post), gated by the same
// "blog" feature flag as the admin's Posts module. Post slugs, exactly like
// product slugs, live on each post's paired Page, so the detail route is
// resolved through that page's slug.
Route::middleware('feature:blog')->group(function () {
    Route::get('/blog', [FrontendController::class, 'blog'])->name('blog');
    Route::get('/blog/{slug}', [FrontendController::class, 'post'])->name('blog.post');
});

// Cart + checkout — session-backed guest carts (see App\Support\Cart) that turn
// into orders via the same server-side pipeline as the order API. Only exists
// while the orders feature is enabled (consistent with the /api/v1/orders
// routes). The confirmation page only shows the order placed in this session.
Route::middleware('feature:orders')->group(function () {
    Route::get('/cart', [FrontendController::class, 'cart'])->name('cart');
    Route::get('/checkout', [FrontendController::class, 'checkout'])->name('checkout');
    Route::get('/order-confirmation/{orderNumber}', [FrontendController::class, 'orderConfirmation'])->name('checkout.confirmation');
});

// Customer account — an ecommerce-store feature, so it only exists while the
// ecommerce theme is active (CustomerController aborts 404 otherwise). Orders
// are matched to the user by user_id first, then by their email, so history
// placed before signing up (guest checkout) still shows up here.
Route::middleware(['auth'])->group(function () {
    Route::get('/account', [CustomerController::class, 'dashboard'])->name('account.dashboard');
    Route::get('/account/orders', [CustomerController::class, 'orders'])->name('account.orders');
    // Plain {orderNumber} param rather than implicit {order:order_number}
    // binding: SubstituteBindings would resolve the Order before the 'auth'
    // middleware runs, so a guest requesting an order URL would get a 404
    // instead of the login redirect every other account page gives them.
    Route::get('/account/orders/{orderNumber}', [CustomerController::class, 'orderShow'])->name('account.orders.show');
    Route::get('/account/profile', [CustomerController::class, 'profile'])->name('account.profile');
});

// Standalone pages (About, Contact, FAQ, ...) — explicitly whitelisted rather
// than a bare `/{slug}` wildcard so this can never shadow auth/system routes
// (login, dashboard, token, ...) regardless of route registration order.
Route::get('/{slug}', [FrontendController::class, 'page'])
    ->where('slug', 'about|contact|faq')
    ->name('page');

// Public, signed invoice links — what the invoice QR code and "Download PDF"
// button point to, so a customer can view/print/download without logging in.
Route::middleware('signed')->group(function () {
    Route::get('/invoices/{order:order_number}', [InvoiceController::class, 'publicShow'])->name('invoices.public.show');
    Route::get('/invoices/{order:order_number}/download', [InvoiceController::class, 'publicDownload'])->name('invoices.public.download');

    // Issued gift vouchers — the link emailed to the buyer and encoded in the
    // QR code printed on the voucher itself, so redeeming never needs a login.
    Route::get('/vouchers/{voucher:code}', [VoucherController::class, 'publicShow'])->name('vouchers.public.show');
    Route::get('/vouchers/{voucher:code}/download', [VoucherController::class, 'publicDownload'])->name('vouchers.public.download');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // The admin panel moved onto its own host with its own login (see
    // bootstrap/app.php), so the post-login redirect points at that host. The
    // vendor portal likewise has its own separate login/session on its own
    // host (App\Livewire\Vendor\Auth\Login), and a session here never carries
    // over to either panel (host-only cookies, see .env's SESSION_DOMAIN).
    Route::get('dashboard', fn () => redirect(config('app.admin_url')))->name('dashboard');
});

// The admin panel is now `admin.codeware.test` (see bootstrap/app.php) — this
// catches old bookmarks and the frontend's "Admin" link, bouncing any
// /admin... path straight over there with its path intact. Deliberately
// outside auth: the admin host's own login decides access.
Route::get('/admin/{path?}', fn (?string $path = null) => $path
    ? redirect(rtrim(config('app.admin_url'), '/').'/'.ltrim($path, '/'))
    : redirect(config('app.admin_url')))
    ->where('path', '.*')
    ->name('admin.legacy');

Route::get('/token', function () {
    $token = auth()->user()->createToken('test-token', ['*'], now()->addMinutes(Setting::puckSessionMinutes()))->plainTextToken;

    return response()->json(['token' => $token]);
})->middleware(['auth']);

Route::get('/test-private-channel', [TestController::class, 'testPrivateChannel']);
Route::get('/test-public-channel', [TestController::class, 'testPublicChannel']);

require __DIR__.'/settings.php';
