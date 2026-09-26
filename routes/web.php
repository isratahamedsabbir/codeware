<?php

/*
 * The system's own web routes — the plumbing every theme shares.
 *
 * The storefront's public pages are NOT here: each theme owns its routes in
 * routes/web/{slug}.php, and only the active theme's pages answer (see the
 * bottom of this file). So `/shop` is a route on an ecommerce site and a 404 on
 * a portfolio one, decided by whether the active theme can serve the page rather
 * than by a controller discovering the theme has no template for it. Everything
 * left here is either not a themed page (a signed invoice link, the /admin
 * bounce, /token) or belongs to a host with its own route file (see
 * bootstrap/app.php).
 */

use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\VoucherController;
use App\Models\Setting;
use App\Support\Themes;
use Illuminate\Support\Facades\Route;

// Public, signed invoice links — what the invoice QR code and "Download PDF"
// button point to, so a customer can view/print/download without logging in.
// Deliberately not theme-scoped: these render invoices.show / vouchers.show,
// which are the same on every theme, and a voucher bought on the ecommerce site
// has to stay redeemable through its link after the admin switches the site to
// a different theme.
Route::middleware('signed')->group(function () {
    Route::get('/invoices/{order:order_number}', [InvoiceController::class, 'publicShow'])->name('invoices.public.show');
    Route::get('/invoices/{order:order_number}/download', [InvoiceController::class, 'publicDownload'])->name('invoices.public.download');

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

// Crawler-facing files, served from the application's own idea of its address
// (Seo\Url) rather than from a file dropped in public/ by hand. The static files
// that used to sit there are gone on purpose: the web server answers
// /robots.txt and /sitemap.xml off disk before Laravel is ever reached, so a
// stale copy in public/ would quietly win over these routes.
//
// Registered here rather than in a theme's route file, because the sitemap
// describes the site rather than one theme's presentation of it and has to
// keep answering when the active theme is a portfolio with no product or blog
// templates at all.
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', RobotsController::class)->name('robots');

// Every theme's storefront routes, one file per theme — but only the pages the
// active theme can actually serve answer. The 'theme' guard each file is wrapped
// in 404s a route whose page the active theme has no template for, so /shop is a
// portfolio site's not-found page even though the route exists. See
// App\Http\Middleware\EnsureActiveTheme for why the routes are all registered
// rather than only the active theme's file being loaded, and
// routes/web/default.php for the one-file-per-theme rule.
//
// `Route::middleware(...)->group()` takes the file as its only argument (it's a
// RouteRegistrar, not the facade's own two-argument group()) and deliberately
// inherits this file's own `web` middleware group (applied by withRouting in
// bootstrap/app.php), so a theme's routes are session-cookie scoped,
// locale-resolved and block-checked like any other web route.
foreach (Themes::allRouteFiles() as $slug => $file) {
    Route::middleware('theme')->group($file);
}
