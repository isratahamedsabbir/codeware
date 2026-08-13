<?php

use Illuminate\Support\Facades\Route;

Route::get('/', \App\Livewire\Admin\Dashboard::class)->name('dashboard');

// Profile
Route::get('/profile', \App\Livewire\Admin\Profile::class)->name('profile');

// Posts
Route::get('/posts', \App\Livewire\Admin\Posts\Index::class)->name('posts');
Route::get('/posts/create', \App\Livewire\Admin\Posts\Form::class)->name('posts.create');
Route::get('/posts/{id}/edit', \App\Livewire\Admin\Posts\Form::class)->name('posts.edit');

// Pages
Route::get('/pages', \App\Livewire\Admin\Pages\Index::class)->name('pages');
Route::get('/pages/create', \App\Livewire\Admin\Pages\Form::class)->name('pages.create');
Route::get('/pages/{id}/edit', \App\Livewire\Admin\Pages\Form::class)->name('pages.edit');

// Post Categories
Route::get('/post-categories', \App\Livewire\Admin\PostCategories\Index::class)->name('post-categories');
Route::get('/post-categories/create', \App\Livewire\Admin\PostCategories\Form::class)->name('post-categories.create');
Route::get('/post-categories/{id}/edit', \App\Livewire\Admin\PostCategories\Form::class)->name('post-categories.edit');

// Post Tags
Route::get('/tags', \App\Livewire\Admin\Tags\Index::class)->name('tags');
Route::get('/tags/create', \App\Livewire\Admin\Tags\Form::class)->name('tags.create');
Route::get('/tags/{id}/edit', \App\Livewire\Admin\Tags\Form::class)->name('tags.edit');

// Media Library & Settings (no CRUD pages)
Route::get('/media-library', \App\Livewire\Admin\MediaLibrary\Index::class)->name('media-library');
Route::get('/settings', \App\Livewire\Admin\Settings\Index::class)->name('settings');

// Email Templates
Route::get('/email-templates', \App\Livewire\Admin\EmailTemplates\Index::class)->name('email-templates');

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

// Roles & Permissions
Route::get('/roles', \App\Livewire\Admin\Roles\Index::class)->name('roles');
Route::get('/roles/create', \App\Livewire\Admin\Roles\Form::class)->name('roles.create');
Route::get('/roles/{id}/edit', \App\Livewire\Admin\Roles\Form::class)->name('roles.edit');
Route::get('/permissions', \App\Livewire\Admin\Permissions\Index::class)->name('permissions');

// Users
Route::get('/users', \App\Livewire\Admin\Users\Index::class)->name('users');
Route::get('/users/create', \App\Livewire\Admin\Users\Form::class)->name('users.create');
Route::get('/users/{id}/edit', \App\Livewire\Admin\Users\Form::class)->name('users.edit');

// Admin Activity History
Route::get('/history', \App\Livewire\Admin\ActivityLogs\Index::class)->name('history');

// Localization
Route::get('/languages', \App\Livewire\Admin\Languages\Index::class)->name('languages');
Route::get('/languages/create', \App\Livewire\Admin\Languages\Form::class)->name('languages.create');
Route::get('/languages/{id}/edit', \App\Livewire\Admin\Languages\Form::class)->name('languages.edit');
Route::get('/translations', \App\Livewire\Admin\Translations\Index::class)->name('translations');

// Menu
Route::get('/menu', \App\Livewire\Admin\Menu\Index::class)->name('menu');
