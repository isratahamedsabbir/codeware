<?php

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

// Saved favorites — guests keep a session bag that merges into their account
// the moment they sign in (see App\Support\Favorites). No auth required.
Route::get('/favorites', [FrontendController::class, 'favorites'])->name('favorites');

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
    // The vendor portal now has its own separate login/session on its own
    // host (App\Livewire\Vendor\Auth\Login) rather than sharing this site's —
    // a session here never carries over there (host-only cookies), so a
    // vendor-only account bouncing through here would just land logged-out
    // on the vendor login page with no explanation. AdminMiddleware's normal
    // 403 for a non-admin account is a clearer outcome than that dead end.
    Route::redirect('dashboard', '/admin')->name('dashboard');
});

Route::get('/token', function () {
    $token = auth()->user()->createToken('test-token', ['*'], now()->addMinutes(Setting::puckSessionMinutes()))->plainTextToken;

    return response()->json(['token' => $token]);
})->middleware(['auth']);

Route::get('/test-private-channel', [TestController::class, 'testPrivateChannel']);
Route::get('/test-public-channel', [TestController::class, 'testPublicChannel']);

require __DIR__.'/settings.php';
