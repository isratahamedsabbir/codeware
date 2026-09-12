<?php

use App\Livewire\Vendor\Dashboard;
use App\Livewire\Vendor\Orders\Index as OrdersIndex;
use App\Livewire\Vendor\Orders\Show as OrdersShow;
use App\Livewire\Vendor\Products\Index as ProductsIndex;
use Illuminate\Support\Facades\Route;

Route::get('/', Dashboard::class)->name('dashboard');

Route::get('/products', ProductsIndex::class)->name('products');

Route::get('/orders', OrdersIndex::class)->name('orders');
Route::get('/orders/{orderId}', OrdersShow::class)->name('orders.show');
