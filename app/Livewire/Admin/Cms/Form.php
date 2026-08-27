<?php

namespace App\Livewire\Admin\Cms;

use App\Models\CmsSection;
use App\Support\AdminActivity;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    public ?int $cmsId = null;

    public string $page = '';

    public string $section = '';

    public ?string $bg_image = null;

    public ?string $image = null;

    /** @var array{en: string, bn: string} */
    public array $title = ['en' => '', 'bn' => ''];

    /** @var array{en: string, bn: string} */
    public array $description = ['en' => '', 'bn' => ''];

    /** @var array<int, array{label: array{en: string, bn: string}, color: string, link: string}> */
    public array $buttons = [];

    /** @var array<int, array{image: ?string, title: array{en: string, bn: string}, description: array{en: string, bn: string}}> */
    public array $cards = [];

    /** @var array<int, array{key: string, value: string}> */
    public array $metadata = [];

    public function mount(?int $id = null): void
    {
        if ($id) {
            $cms = CmsSection::findOrFail($id);
            $this->cmsId = $cms->id;
            $this->page = $cms->page;
            $this->section = $cms->section;
            $this->bg_image = $cms->bg_image;
            $this->image = $cms->image;
            $this->title = $cms->title ?? ['en' => '', 'bn' => ''];
            $this->description = $cms->description ?? ['en' => '', 'bn' => ''];
            $this->buttons = $cms->buttons ?? [];
            $this->cards = $cms->cards ?? [];
            $this->metadata = $cms->metadata ?? [];
        }
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

    public function addMetadata(): void
    {
        $this->metadata[] = ['key' => '', 'value' => ''];
    }

    public function removeMetadata(int $index): void
    {
        unset($this->metadata[$index]);
        $this->metadata = array_values($this->metadata);
    }

    protected function rules(): array
    {
        return [
            'page' => 'required|string|max:255',
            'section' => [
                'required', 'string', 'max:255',
                Rule::unique('cms', 'section')->where('page', $this->page)->ignore($this->cmsId),
            ],
            'bg_image' => 'nullable|string',
            'image' => 'nullable|string',
            'title.en' => 'nullable|string|max:255',
            'title.bn' => 'nullable|string|max:255',
            'description.en' => 'nullable|string',
            'description.bn' => 'nullable|string',
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
            'metadata' => ['array', function (string $attribute, mixed $value, \Closure $fail) {
                $keys = collect($value)->pluck('key')->filter()->map(fn ($key) => strtolower(trim($key)));

                if ($keys->count() !== $keys->unique()->count()) {
                    $fail('Metadata keys must be unique.');
                }
            }],
            'metadata.*.key' => 'nullable|string|max:255',
            'metadata.*.value' => 'nullable|string|max:1000',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'page' => $this->page,
            'section' => $this->section,
            'bg_image' => $this->bg_image ?: null,
            'image' => $this->image ?: null,
            'title' => filled($this->title['en'] ?? null) || filled($this->title['bn'] ?? null) ? $this->title : null,
            'description' => filled($this->description['en'] ?? null) || filled($this->description['bn'] ?? null) ? $this->description : null,
            'buttons' => array_values($this->buttons),
            'cards' => array_values($this->cards),
            'metadata' => collect($this->metadata)->filter(fn ($pair) => filled($pair['key'] ?? null))->values()->all(),
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
