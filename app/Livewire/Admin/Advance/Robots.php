<?php

namespace App\Livewire\Admin\Advance;

use App\Models\Setting;
use App\Support\AdminActivity;
use App\Support\Seo\Url;
use Livewire\Component;

/**
 * The robots.txt rules, held in the settings table and rendered by
 * App\Http\Controllers\RobotsController at the moment of the request.
 *
 * This used to write public/robots.txt. Two things made that the wrong home for
 * them: the file is served by the web server before Laravel is reached, so what
 * an admin saved and what a crawler read could disagree, and it could not know
 * the site's own address to point the Sitemap: line at. The rules are still the
 * admin's to write; the Sitemap: line is the controller's to append.
 */
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

        Setting::set('seo_robots_txt', trim($this->content));

        AdminActivity::log('advance.robots.update', 'robots.txt rules updated');

        $this->loadContent();

        session()->flash('success', 'robots.txt saved.');
    }

    public function resetToDefault(): void
    {
        $this->content = $this->defaultContent();
    }

    protected function loadContent(): void
    {
        $stored = (string) Setting::get('seo_robots_txt', '');

        $this->content = $stored !== '' ? $stored : $this->defaultContent();
        // Read straight off the row rather than off the cached value, which
        // carries no timestamp — the cache is there so a request never queries
        // for this, and this screen is exactly the place the real one is wanted.
        $this->updatedAt = $stored === ''
            ? null
            : Setting::query()->where('key', 'seo_robots_txt')->value('updated_at');
    }

    protected function defaultContent(): string
    {
        // No Sitemap: line here — the controller appends it on every response, so
        // it cannot be lost to a careless save and cannot be pointed elsewhere.
        return "User-agent: *\nDisallow:";
    }

    public function render()
    {
        return view('livewire.admin.advance.robots', [
            'sitemapUrl' => Url::url('sitemap.xml'),
        ])->layout('layouts.admin', ['title' => 'Robots.txt']);
    }
}
