<?php

use App\Http\Controllers\InvoiceController;
use App\Livewire\Vendor\Auth\Login;
use App\Livewire\Vendor\Chat;
use App\Livewire\Vendor\Dashboard;
use App\Livewire\Vendor\Orders\Index as OrdersIndex;
use App\Livewire\Vendor\Orders\Show as OrdersShow;
use App\Livewire\Vendor\Products\Form as ProductsForm;
use App\Livewire\Vendor\Products\Index as ProductsIndex;
use App\Livewire\Vendor\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Its own login — not Fortify's shared one — so the vendor portal is a fully
// separate panel, not just a gated area behind the admin's login page.
Route::get('/login', Login::class)->name('login');

Route::post('/logout', function (Request $request) {
    Auth::guard('web')->logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('vendor.login');
})->middleware('auth')->name('logout');

Route::middleware(['auth', 'can:access-vendor-portal'])->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');

    Route::get('/profile', Profile::class)->name('profile');

    Route::get('/chat', Chat::class)->name('chat');

    Route::get('/products', ProductsIndex::class)->name('products');
    Route::get('/products/create', ProductsForm::class)->name('products.create');
    Route::get('/products/{id}/edit', ProductsForm::class)->name('products.edit');

    Route::get('/orders', OrdersIndex::class)->name('orders');
    Route::get('/orders/{orderId}', OrdersShow::class)->name('orders.show');
    Route::get('/orders/{order}/invoice', [InvoiceController::class, 'vendorShow'])->name('orders.invoice');
    Route::get('/orders/{order}/invoice/download', [InvoiceController::class, 'vendorDownload'])->name('orders.invoice.download');
});
