<?php

namespace App\Livewire\Admin;

use App\Models\MediaLibrary;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductCategory;
use Livewire\Component;

class Dashboard extends Component
{
    public int $totalProducts    = 0;
    public int $totalCategories  = 0;
    public int $totalPosts       = 0;
    public int $publishedPosts   = 0;
    public int $draftPosts       = 0;
    public int $totalPages       = 0;
    public int $totalMedia       = 0;
    public string $totalMediaSize = '0 B';

    public function mount(): void
    {
        $this->totalProducts     = Product::count();
        $this->totalCategories   = ProductCategory::count();
        $this->totalPosts        = Post::count();
        $this->publishedPosts    = Post::published()->count();
        $this->draftPosts        = Post::draft()->count();
        $this->totalPages        = Page::count();
        $this->totalMedia        = MediaLibrary::count();

        $this->totalMediaSize = $this->formatBytes(MediaLibrary::sum('file_size'));
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow   = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow   = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    public function render()
    {
        return view('livewire.admin.dashboard', [
            'recentProducts' => Product::with('category')->latest()->take(5)->get(),
            'recentPosts'    => Post::with('category')->latest()->take(5)->get(),
        ])->layout('layouts.admin', ['title' => 'Dashboard', 'hidePageHeading' => true]);
    }
}
