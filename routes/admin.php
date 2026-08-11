<?php

use Illuminate\Support\Facades\Route;

Route::get('/', \App\Livewire\Admin\Dashboard::class)->name('dashboard');

// Posts
Route::get('/posts', \App\Livewire\Admin\Posts\Index::class)->name('posts');
Route::get('/posts/create', \App\Livewire\Admin\Posts\Form::class)->name('posts.create');
Route::get('/posts/{id}/edit', \App\Livewire\Admin\Posts\Form::class)->name('posts.edit');

// Pages
Route::get('/pages', \App\Livewire\Admin\Pages\Index::class)->name('pages');
Route::get('/pages/create', \App\Livewire\Admin\Pages\Form::class)->name('pages.create');
Route::get('/pages/{id}/edit', \App\Livewire\Admin\Pages\Form::class)->name('pages.edit');

// Blog Categories
Route::get('/blog-categories', \App\Livewire\Admin\BlogCategories\Index::class)->name('blog-categories');
Route::get('/blog-categories/create', \App\Livewire\Admin\BlogCategories\Form::class)->name('blog-categories.create');
Route::get('/blog-categories/{id}/edit', \App\Livewire\Admin\BlogCategories\Form::class)->name('blog-categories.edit');

// Tags
Route::get('/tags', \App\Livewire\Admin\Tags\Index::class)->name('tags');
Route::get('/tags/create', \App\Livewire\Admin\Tags\Form::class)->name('tags.create');
Route::get('/tags/{id}/edit', \App\Livewire\Admin\Tags\Form::class)->name('tags.edit');

// Media Library & Settings (no CRUD pages)
Route::get('/media-library', \App\Livewire\Admin\MediaLibrary\Index::class)->name('media-library');
Route::get('/settings', \App\Livewire\Admin\Settings\Index::class)->name('settings');

// Products
Route::get('/products', \App\Livewire\Admin\Products\Index::class)->name('products');
Route::get('/products/create', \App\Livewire\Admin\Products\Form::class)->name('products.create');
Route::get('/products/{id}/edit', \App\Livewire\Admin\Products\Form::class)->name('products.edit');

// Product Categories
Route::get('/product-categories', \App\Livewire\Admin\ProductCategories\Index::class)->name('product-categories');
Route::get('/product-categories/create', \App\Livewire\Admin\ProductCategories\Form::class)->name('product-categories.create');
Route::get('/product-categories/{id}/edit', \App\Livewire\Admin\ProductCategories\Form::class)->name('product-categories.edit');

// Contacts (read-only)
Route::get('/contacts', \App\Livewire\Admin\Contacts\Index::class)->name('contacts');
