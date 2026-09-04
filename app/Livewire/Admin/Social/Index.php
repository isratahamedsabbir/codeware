<?php

namespace App\Livewire\Admin\Social;

use App\Models\SocialLink;
use App\Support\AdminActivity;
use Livewire\Component;

class Index extends Component
{
    /** @var array<int, array{id: int, platform: string, label: string, url: string}> */
    public array $links = [];

    public function mount(): void
    {
        $this->links = SocialLink::orderBy('sort_order')->get()
            ->map(fn (SocialLink $link) => [
                'id' => $link->id,
                'platform' => $link->platform,
                'label' => $link->label,
                'url' => $link->url ?? '',
            ])
            ->all();
    }

    public function save(): void
    {
        foreach ($this->links as $link) {
            SocialLink::whereKey($link['id'])->update(['url' => trim($link['url'])]);
        }

        AdminActivity::log('updated', 'Social links updated');

        $this->dispatch('notify', message: 'Social links saved.');
    }

    public function render()
    {
        return view('livewire.admin.social.index')->layout('layouts.admin', ['title' => 'Social Links']);
    }
}
