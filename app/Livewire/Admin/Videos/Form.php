<?php

namespace App\Livewire\Admin\Videos;

use App\Models\Video;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    public ?int $videoId = null;

    #[Validate('required|string|max:255')]
    public string $title_en = '';

    #[Validate('nullable|string|max:255')]
    public string $title_bn = '';

    #[Validate('required|string|max:500')]
    public string $youtube_link = '';

    #[Validate('nullable|string|max:500')]
    public string $thumbnail = '';

    public string $thumbnailPickerId = '';

    #[Validate('in:active,inactive')]
    public string $status = 'active';

    public int $sort_order = 0;

    public function mount(?int $id = null): void
    {
        $this->thumbnailPickerId = 'thumbnail-picker-' . Str::uuid()->toString();

        if ($id) {
            $video = Video::findOrFail($id);
            $this->videoId      = $id;
            $this->title_en     = $video->getTranslation('title', 'en', false) ?? '';
            $this->title_bn     = $video->getTranslation('title', 'bn', false) ?? '';
            $this->youtube_link = $video->youtube_link;
            $this->thumbnail    = $video->thumbnail ?? '';
            $this->status       = $video->status;
            $this->sort_order   = $video->sort_order;
        }
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'title'        => array_filter(['en' => $this->title_en, 'bn' => $this->title_bn]),
            'youtube_link' => $this->youtube_link,
            'thumbnail'    => $this->thumbnail ?: null,
            'status'       => $this->status,
            'sort_order'   => $this->sort_order,
        ];

        if ($this->videoId) {
            Video::findOrFail($this->videoId)->update($data);
            $this->dispatch('notify', message: 'Video updated successfully');
        } else {
            Video::create($data);
            $this->dispatch('notify', message: 'Video created successfully');
        }

        $this->redirect(route('admin.videos'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.videos.form')
            ->layout('layouts.admin', ['title' => $this->videoId ? 'Edit Video' : 'New Video']);
    }
}
