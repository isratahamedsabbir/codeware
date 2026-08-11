<?php

namespace App\Livewire\Admin\Pages;

use App\Models\Page;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    public ?int $pageId = null;

    #[Validate('required|string|max:255')]
    public string $title_en = '';

    #[Validate('nullable|string|max:255')]
    public string $title_bn = '';

    #[Validate('nullable|string|max:255')]
    public string $slug = '';

    #[Validate('nullable|string|max:255')]
    public string $seo_title_en = '';

    #[Validate('nullable|string|max:255')]
    public string $seo_title_bn = '';

    #[Validate('nullable|string')]
    public string $seo_description_en = '';

    #[Validate('nullable|string')]
    public string $seo_description_bn = '';

    #[Validate('in:active,inactive')]
    public string $status = 'active';

    #[Validate('nullable|string|max:100')]
    public string $template = 'puck';

    public int $sort_order = 0;

    public ?string $og_image = null;

    public string $ogImagePickerId = '';

    public function mount(?int $id = null): void
    {
        $this->ogImagePickerId = 'page-og-image-picker-' . Str::uuid()->toString();

        if ($id) {
            $page = Page::findOrFail($id);
            $this->pageId              = $id;
            $this->title_en            = $page->getTranslation('title', 'en', false) ?? '';
            $this->title_bn            = $page->getTranslation('title', 'bn', false) ?? '';
            $this->slug                = $page->slug;
            $this->seo_title_en        = $page->getTranslation('seo_title', 'en', false) ?? '';
            $this->seo_title_bn        = $page->getTranslation('seo_title', 'bn', false) ?? '';
            $this->seo_description_en  = $page->getTranslation('seo_description', 'en', false) ?? '';
            $this->seo_description_bn  = $page->getTranslation('seo_description', 'bn', false) ?? '';
            $this->status              = $page->status;
            $this->template            = $page->template ?? 'puck';
            $this->sort_order          = $page->sort_order;
            $this->og_image            = $page->og_image ?? null;
        }
    }

    public function openPuckEditor(): void
    {
        if (!$this->pageId) return;

        auth()->user()->tokens()->where('name', 'puck-builder')->delete();

        $token = auth()->user()->createToken(
            'puck-builder',
            ['*'],
            now()->addMinutes(config('app.puck_session', 5))
        )->plainTextToken;

        $url = config('cms.editor_base_url') . "/puck/edit/page/{$this->pageId}#token={$token}";
        $this->js('window.open(' . json_encode($url) . ', \'_blank\')');
    }

    public function saveAndOpenPageBuilder(): void
    {
        if (empty($this->slug) && $this->title_en) {
            $this->slug = Str::slug($this->title_en);
        }

        $rules = $this->getRules();
        $rules['slug'] = $this->pageId
            ? 'required|string|max:255|unique:pages,slug,' . $this->pageId
            : 'required|string|max:255|unique:pages,slug';

        $this->validate($rules);

        $data = [
            'user_id'         => auth()->id(),
            'title'           => array_filter(['en' => $this->title_en, 'bn' => $this->title_bn]),
            'slug'            => $this->slug,
            'status'          => $this->status,
            'template'        => $this->template ?: 'puck',
            'sort_order'      => $this->sort_order,
            'og_image'        => $this->og_image ?: null,
            'seo_title'       => array_filter(['en' => $this->seo_title_en, 'bn' => $this->seo_title_bn]) ?: null,
            'seo_description' => array_filter(['en' => $this->seo_description_en, 'bn' => $this->seo_description_bn]) ?: null,
        ];

        if ($this->pageId) {
            Page::findOrFail($this->pageId)->update($data);
            $this->dispatch('notify', message: 'Page updated successfully');
        } else {
            $page = Page::create($data);
            $this->pageId = $page->id;
            $this->dispatch('notify', message: 'Page created successfully');
        }

        auth()->user()->tokens()->where('name', 'puck-builder')->delete();

        $token = auth()->user()->createToken(
            'puck-builder',
            ['*'],
            now()->addMinutes(config('app.puck_session', 5))
        )->plainTextToken;

        $url = config('cms.editor_base_url') . "/puck/edit/page/{$this->pageId}#token={$token}";
        $this->js('window.open(' . json_encode($url) . ', \'_blank\')');
    }

    public function save(): void
    {
        if (empty($this->slug) && $this->title_en) {
            $this->slug = Str::slug($this->title_en);
        }

        $rules = $this->getRules();
        $rules['slug'] = $this->pageId
            ? 'required|string|max:255|unique:pages,slug,' . $this->pageId
            : 'required|string|max:255|unique:pages,slug';

        $this->validate($rules);

        $data = [
            'user_id'         => auth()->id(),
            'title'           => array_filter(['en' => $this->title_en, 'bn' => $this->title_bn]),
            'slug'            => $this->slug,
            'status'          => $this->status,
            'template'        => $this->template ?: 'puck',
            'sort_order'      => $this->sort_order,
            'og_image'        => $this->og_image ?: null,
            'seo_title'       => array_filter(['en' => $this->seo_title_en, 'bn' => $this->seo_title_bn]) ?: null,
            'seo_description' => array_filter(['en' => $this->seo_description_en, 'bn' => $this->seo_description_bn]) ?: null,
        ];

        if ($this->pageId) {
            Page::findOrFail($this->pageId)->update($data);
            $this->dispatch('notify', message: 'Page updated successfully');
        } else {
            $page = Page::create($data);
            $this->pageId = $page->id;
            $this->dispatch('notify', message: 'Page created successfully');
        }

        $this->redirect(route('admin.pages'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.pages.form')
            ->layout('layouts.admin', ['title' => $this->pageId ? 'Edit Page Metadata' : 'New Page']);
    }
}
