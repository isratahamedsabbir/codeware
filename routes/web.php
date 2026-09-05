<?php

use App\Http\Controllers\FrontendController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\TestController;
use App\Models\Setting;
use Illuminate\Support\Facades\Route;

Route::get('/', [FrontendController::class, 'home'])->name('home');

// /home is just an alias for / — same controller method and view, so it
// keeps the homepage's own hero styling instead of the generic page layout.
Route::get('/home', [FrontendController::class, 'home']);

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
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('dashboard', '/admin')->name('dashboard');
});

Route::get('/token', function () {
    $token = auth()->user()->createToken('test-token', ['*'], now()->addMinutes(Setting::puckSessionMinutes()))->plainTextToken;

    return response()->json(['token' => $token]);
})->middleware(['auth']);

Route::get('/test-private-channel', [TestController::class, 'testPrivateChannel']);
Route::get('/test-public-channel', [TestController::class, 'testPublicChannel']);

require __DIR__.'/settings.php';
