<?php

namespace App\Livewire\Admin\Seo;

use App\Models\Setting;
use App\Support\AdminActivity;
use Livewire\Component;

class Index extends Component
{
    public array $settings = [];

    /** @var array<int, string> */
    public array $canonicalUrls = [];

    public function mount(): void
    {
        foreach (Setting::where('group', 'seo')->get() as $setting) {
            $this->settings[$setting->key] = $setting->value ?? '';
        }

        $this->canonicalUrls = json_decode(Setting::get('seo_canonical_urls', '[]') ?: '[]', true) ?: [];

        if (empty($this->canonicalUrls)) {
            $this->canonicalUrls = [''];
        }
    }

    public function addCanonicalUrl(): void
    {
        $this->canonicalUrls[] = '';
    }

    public function removeCanonicalUrl(int $index): void
    {
        unset($this->canonicalUrls[$index]);
        $this->canonicalUrls = array_values($this->canonicalUrls);

        if (empty($this->canonicalUrls)) {
            $this->canonicalUrls = [''];
        }
    }

    public function save(): void
    {
        foreach ($this->settings as $key => $value) {
            Setting::set($key, $value);
        }

        $urls = array_values(array_filter($this->canonicalUrls, fn ($url) => trim($url) !== ''));
        Setting::set('seo_canonical_urls', json_encode($urls));

        AdminActivity::log('updated', 'SEO settings updated');

        $this->dispatch('notify', message: 'SEO settings saved.');
    }

    public function render()
    {
        return view('livewire.admin.seo.index')->layout('layouts.admin', ['title' => 'Global SEO']);
    }
}
