<?php

namespace App\Livewire\Admin\Advance;

use App\Support\AdminActivity;
use Illuminate\Support\Facades\File;
use Livewire\Component;

class Robots extends Component
{
    public string $content = '';

    public ?string $updatedAt = null;

    public function mount(): void
    {
        $this->loadContent();
    }

    public function save(): void
    {
        $this->validate([
            'content' => 'required|string|max:10000',
        ]);

        File::put(public_path('robots.txt'), $this->content);

        AdminActivity::log('advance.robots.update', 'robots.txt updated');

        $this->loadContent();

        session()->flash('success', 'robots.txt saved.');
    }

    public function resetToDefault(): void
    {
        $this->content = $this->defaultContent();
    }

    protected function loadContent(): void
    {
        $path = public_path('robots.txt');

        if (File::exists($path)) {
            $this->content = File::get($path);
            $this->updatedAt = date('Y-m-d H:i:s', File::lastModified($path));

            return;
        }

        $this->content = $this->defaultContent();
        $this->updatedAt = null;
    }

    protected function defaultContent(): string
    {
        return "User-agent: *\nDisallow:\n\nSitemap: ".url('/sitemap.xml')."\n";
    }

    public function render()
    {
        return view('livewire.admin.advance.robots')->layout('layouts.admin', ['title' => 'Robots.txt']);
    }
}
