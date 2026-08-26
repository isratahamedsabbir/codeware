<?php

namespace App\Livewire\Admin\Cms;

use App\Models\CmsSection;
use App\Support\AdminActivity;
use Livewire\Component;

class Form extends Component
{
    public ?int $cmsId = null;

    public string $page = '';

    public string $section = '';

    public int $sort_order = 0;

    public ?string $bg_image = null;

    /** @var array<int, array{en: string, bn: string}> */
    public array $titles = [];

    /** @var array<int, array{en: string, bn: string}> */
    public array $descriptions = [];

    /** @var array<int, array{label: array{en: string, bn: string}, color: string, link: string}> */
    public array $buttons = [];

    /** @var array<int, array{image: ?string, title: array{en: string, bn: string}, description: array{en: string, bn: string}}> */
    public array $cards = [];

    /** @var array<int, ?string> */
    public array $images = [];

    public function mount(?int $id = null): void
    {
        if ($id) {
            $cms = CmsSection::findOrFail($id);
            $this->cmsId = $cms->id;
            $this->page = $cms->page;
            $this->section = $cms->section;
            $this->sort_order = $cms->sort_order;
            $this->bg_image = $cms->bg_image;
            $this->titles = $cms->titles ?? [];
            $this->descriptions = $cms->descriptions ?? [];
            $this->buttons = $cms->buttons ?? [];
            $this->cards = $cms->cards ?? [];
            $this->images = $cms->images ?? [];
        }
    }

    public function addTitle(): void
    {
        $this->titles[] = ['en' => '', 'bn' => ''];
    }

    public function removeTitle(int $index): void
    {
        unset($this->titles[$index]);
        $this->titles = array_values($this->titles);
    }

    public function addDescription(): void
    {
        $this->descriptions[] = ['en' => '', 'bn' => ''];
    }

    public function removeDescription(int $index): void
    {
        unset($this->descriptions[$index]);
        $this->descriptions = array_values($this->descriptions);
    }

    public function addButton(): void
    {
        $this->buttons[] = ['label' => ['en' => '', 'bn' => ''], 'color' => '#2563eb', 'link' => ''];
    }

    public function removeButton(int $index): void
    {
        unset($this->buttons[$index]);
        $this->buttons = array_values($this->buttons);
    }

    public function addCard(): void
    {
        $this->cards[] = ['image' => null, 'title' => ['en' => '', 'bn' => ''], 'description' => ['en' => '', 'bn' => '']];
    }

    public function removeCard(int $index): void
    {
        unset($this->cards[$index]);
        $this->cards = array_values($this->cards);
    }

    public function addImage(): void
    {
        $this->images[] = null;
    }

    public function removeImage(int $index): void
    {
        unset($this->images[$index]);
        $this->images = array_values($this->images);
    }

    protected function rules(): array
    {
        return [
            'page' => 'required|string|max:255',
            'section' => 'required|string|max:255',
            'sort_order' => 'integer|min:0',
            'bg_image' => 'nullable|string',
            'titles' => 'array',
            'titles.*.en' => 'nullable|string|max:255',
            'titles.*.bn' => 'nullable|string|max:255',
            'descriptions' => 'array',
            'descriptions.*.en' => 'nullable|string',
            'descriptions.*.bn' => 'nullable|string',
            'buttons' => 'array',
            'buttons.*.label.en' => 'nullable|string|max:255',
            'buttons.*.label.bn' => 'nullable|string|max:255',
            'buttons.*.color' => 'nullable|string|max:20',
            'buttons.*.link' => 'nullable|string|max:255',
            'cards' => 'array',
            'cards.*.image' => 'nullable|string',
            'cards.*.title.en' => 'nullable|string|max:255',
            'cards.*.title.bn' => 'nullable|string|max:255',
            'cards.*.description.en' => 'nullable|string',
            'cards.*.description.bn' => 'nullable|string',
            'images' => 'array',
            'images.*' => 'nullable|string',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'page' => $this->page,
            'section' => $this->section,
            'sort_order' => $this->sort_order,
            'bg_image' => $this->bg_image ?: null,
            'titles' => array_values(array_filter($this->titles, fn ($t) => filled($t['en'] ?? null) || filled($t['bn'] ?? null))),
            'descriptions' => array_values(array_filter($this->descriptions, fn ($d) => filled($d['en'] ?? null) || filled($d['bn'] ?? null))),
            'buttons' => array_values($this->buttons),
            'cards' => array_values($this->cards),
            'images' => array_values(array_filter($this->images)),
        ];

        $creating = $this->cmsId === null;

        if ($this->cmsId) {
            CmsSection::findOrFail($this->cmsId)->update($data);
        } else {
            // New sections stay inactive until switched on from the list — status
            // is no longer editable from this form, see Index::toggleStatus().
            $data['status'] = 'inactive';
            $cms = CmsSection::create($data);
            $this->cmsId = $cms->id;
        }

        AdminActivity::log(
            $creating ? 'created' : 'updated',
            "CMS section: {$this->page} / {$this->section}",
        );

        $this->dispatch('notify', message: $creating ? 'CMS section created successfully' : 'CMS section updated successfully');

        $this->redirect(route('admin.cms'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.cms.form')
            ->layout('layouts.admin', ['title' => $this->cmsId ? 'Edit CMS Section' : 'New CMS Section']);
    }
}
