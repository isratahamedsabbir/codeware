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

// Dealers
Route::get('/dealers', \App\Livewire\Admin\Dealers\Index::class)->name('dealers');
Route::get('/dealers/create', \App\Livewire\Admin\Dealers\Form::class)->name('dealers.create');
Route::get('/dealers/{id}/edit', \App\Livewire\Admin\Dealers\Form::class)->name('dealers.edit');

// Districts
Route::get('/districts', \App\Livewire\Admin\Districts\Index::class)->name('districts');
Route::get('/districts/create', \App\Livewire\Admin\Districts\Form::class)->name('districts.create');
Route::get('/districts/{id}/edit', \App\Livewire\Admin\Districts\Form::class)->name('districts.edit');

// Upazilas
Route::get('/upazilas', \App\Livewire\Admin\Upazilas\Index::class)->name('upazilas');
Route::get('/upazilas/create', \App\Livewire\Admin\Upazilas\Form::class)->name('upazilas.create');
Route::get('/upazilas/{id}/edit', \App\Livewire\Admin\Upazilas\Form::class)->name('upazilas.edit');

// Jobs
Route::get('/jobs', \App\Livewire\Admin\Jobs\Index::class)->name('jobs');
Route::get('/jobs/create', \App\Livewire\Admin\Jobs\Form::class)->name('jobs.create');
Route::get('/jobs/{id}/edit', \App\Livewire\Admin\Jobs\Form::class)->name('jobs.edit');

// Departments
Route::get('/departments', \App\Livewire\Admin\Departments\Index::class)->name('departments');
Route::get('/departments/create', \App\Livewire\Admin\Departments\Form::class)->name('departments.create');
Route::get('/departments/{id}/edit', \App\Livewire\Admin\Departments\Form::class)->name('departments.edit');

// Testimonials
Route::get('/testimonials', \App\Livewire\Admin\Testimonials\Index::class)->name('testimonials');
Route::get('/testimonials/create', \App\Livewire\Admin\Testimonials\Form::class)->name('testimonials.create');
Route::get('/testimonials/{id}/edit', \App\Livewire\Admin\Testimonials\Form::class)->name('testimonials.edit');

// Videos
Route::get('/videos', \App\Livewire\Admin\Videos\Index::class)->name('videos');
Route::get('/videos/create', \App\Livewire\Admin\Videos\Form::class)->name('videos.create');
Route::get('/videos/{id}/edit', \App\Livewire\Admin\Videos\Form::class)->name('videos.edit');

// Applications & Contacts (read-only, no form pages)
Route::get('/applications', \App\Livewire\Admin\Applications\Index::class)->name('applications');
Route::get('/contacts', \App\Livewire\Admin\Contacts\Index::class)->name('contacts');
Route::get('/applications/{id}/resume', \App\Http\Controllers\Admin\ResumeDownloadController::class)->name('applications.resume');
