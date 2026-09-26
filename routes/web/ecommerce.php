<?php

/*
 * The storefront routes of the "ecommerce" theme — the whole shop.
 *
 * routes/web.php registers this file behind the 'theme' guard, which 404s any
 * request the active theme has no template for, so these routes answer only
 * while the ecommerce theme is active, and this is the only theme with a cart,
 * a checkout and a customer account. CustomerController's own theme guard is
 * belt-and-braces now, not the thing that keeps /account off a portfolio site.
 *
 * Each of the shop routes renders through the active theme's own template, and
 * would 404 on a theme that shipped none (see Themes::view()) — so every
 * template referenced from Themes::ROUTE_TEMPLATES needs a route here too.
 */

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\FrontendController;
use Illuminate\Support\Facades\Route;

// The homepage and the standalone pages are repeated here on purpose: each theme
// file is a complete description of that theme's site, so they can be read side
// by side, and the 'theme' guard is per *route name* rather than per file. That
// is what keeps the copies from colliding — a route registered by two files is
// matched once, and a file-scoped guard would then reject the very theme that
// shares it.
Route::get('/', [FrontendController::class, 'home'])->name('home');

// /home is just an alias for / — same controller method and view, so it
// keeps the homepage's own hero styling instead of the generic page layout.
Route::get('/home', [FrontendController::class, 'home']);

// Standalone pages (About, Contact, FAQ, ...) — explicitly whitelisted rather
// than a bare `/{slug}` wildcard so this can never shadow auth/system routes
// (login, dashboard, token, ...) regardless of route registration order. A
// theme with no page.blade.php (the portfolio) registers no `page` route, so
// this one is simply never matched there.
Route::get('/{slug}', [FrontendController::class, 'page'])
    ->where('slug', 'about|contact|faq')
    ->name('page');

// Category slugs live on each category's paired Page; brand/tag slugs are
// derived from the primary-locale name.
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

// Cart + checkout — session-backed carts (see App\Support\Cart) that turn into
// orders via the same server-side pipeline as the order API. Only exists while
// the orders feature is enabled (consistent with the /api/v1/orders routes).
// The confirmation page only shows the order placed in this session.
//
// Checkout itself is behind 'auth' — a guest pressing "Proceed to checkout" from
// the (still public) cart is sent to log in and returned here afterwards, which
// is what ties the resulting order to a user_id instead of only an email. The
// cart page itself stays public so browsing and building a basket never forces
// an account.
Route::middleware('feature:orders')->group(function () {
    Route::get('/cart', [FrontendController::class, 'cart'])->name('cart');
    Route::get('/checkout', [FrontendController::class, 'checkout'])
        ->middleware('auth')
        ->name('checkout');
    Route::get('/order-confirmation/{orderNumber}', [FrontendController::class, 'orderConfirmation'])->name('checkout.confirmation');
});

// Customer account — orders are matched to the user by user_id first, then by
// their email, so an order placed without an account (through the order API)
// still shows up here for the customer who later registers with the same
// address.
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
