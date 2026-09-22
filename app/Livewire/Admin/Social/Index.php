<?php

namespace App\Livewire\Admin\Social;

use App\Models\SocialLink;
use App\Support\AdminActivity;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Component;

class Index extends Component
{
    /** @var array<int, array{id: int|null, platform: string, label: string, url: string}> */
    public array $links = [];

    /** @var array<int, int> */
    public array $removedIds = [];

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

        $this->removedIds = [];
    }

    public function addLink(): void
    {
        $this->links[] = ['id' => null, 'platform' => '', 'label' => '', 'url' => ''];
    }

    public function removeLink(int $index): void
    {
        if (! isset($this->links[$index])) {
            return;
        }

        if ($this->links[$index]['id'] !== null) {
            $this->removedIds[] = $this->links[$index]['id'];
        }

        unset($this->links[$index]);
        $this->links = array_values($this->links);
    }

    public function save(): void
    {
        if (! empty($this->removedIds)) {
            SocialLink::whereIn('id', $this->removedIds)->delete();
        }

        $sort = 0;
        foreach ($this->links as $link) {
            $platform = Str::slug((string) ($link['platform'] ?? ''));
            $label = trim((string) ($link['label'] ?? ''));
            $url = trim((string) ($link['url'] ?? ''));

            // Blank new rows are skipped entirely; nothing is persisted for them.
            if ($platform === '' && $label === '') {
                continue;
            }

            if ($platform === '') {
                $platform = Str::slug($label);
            }

            if (($link['id'] ?? null) !== null) {
                // A mass update via the query builder (whereKey()->update()) never fires
                // Eloquent's `saved` event, so SocialLink::booted()'s cache-bust hook
                // wouldn't run — bust it explicitly below instead.
                SocialLink::whereKey($link['id'])->update([
                    'label' => $label !== '' ? $label : $link['label'],
                    'url' => $url,
                    'sort_order' => $sort,
                ]);
            } else {
                // updateOrCreate collapses a re-added platform (e.g. deleted above
                // this loop) into a single row instead of tripping the unique key.
                SocialLink::updateOrCreate(
                    ['platform' => $platform],
                    ['label' => $label !== '' ? $label : ucfirst($platform), 'url' => $url, 'sort_order' => $sort],
                );
            }

            $sort++;
        }

        $this->removedIds = [];
        Cache::forget('social-links:all');

        AdminActivity::log('updated', 'Social links updated');

        $this->mount();
        $this->dispatch('notify', message: 'Social links saved.');
    }

    public function render()
    {
        return view('livewire.admin.social.index')->layout('layouts.admin', ['title' => 'Social Links']);
    }
}
