<?php

namespace App\Livewire\Admin\Cms;

use App\Models\CmsSection;
use App\Models\Page;
use App\Support\AdminActivity;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    public ?int $cmsId = null;

    public int $pageId;

    public string $name = '';

    /** @var array<int, array{image: ?string, title: string, description: string}> */
    public array $cards = [];

    /** @var array<int, array{key: string, type: string, value: string}> */
    public array $metadata = [];

    public function mount(int $pageId, ?int $id = null): void
    {
        $this->pageId = Page::findOrFail($pageId)->id;

        if ($id) {
            $cms = CmsSection::where('page_id', $this->pageId)->findOrFail($id);
            $this->cmsId = $cms->id;
            $this->name = $cms->name;
            $this->cards = $cms->cards ?? [];
            // Older rows were saved before the value-type selector existed —
            // default them to a plain text value so they still render/edit correctly.
            $this->metadata = collect($cms->metadata ?? [])
                ->map(fn (array $pair) => [...$pair, 'type' => $pair['type'] ?? 'text'])
                ->all();
        }
    }

    public function addCard(): void
    {
        $this->cards[] = ['image' => null, 'title' => '', 'description' => ''];
    }

    public function removeCard(int $index): void
    {
        unset($this->cards[$index]);
        $this->cards = array_values($this->cards);
    }

    public function addMetadata(): void
    {
        $this->metadata[] = ['key' => '', 'type' => 'text', 'value' => ''];
    }

    public function removeMetadata(int $index): void
    {
        unset($this->metadata[$index]);
        $this->metadata = array_values($this->metadata);
    }

    protected function rules(): array
    {
        return [
            'pageId' => 'required|integer|exists:pages,id',
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('cms', 'name')
                    ->where('page_id', $this->pageId)
                    ->ignore($this->cmsId),
            ],
            'cards' => 'array',
            'cards.*.image' => 'nullable|string',
            'cards.*.title' => 'nullable|string|max:255',
            'cards.*.description' => 'nullable|string',
            'metadata' => ['array', function (string $attribute, mixed $value, \Closure $fail) {
                $keys = collect($value)->pluck('key')->filter()->map(fn ($key) => strtolower(trim($key)));

                if ($keys->count() !== $keys->unique()->count()) {
                    $fail('Metadata keys must be unique.');
                }
            }],
            'metadata.*.key' => 'nullable|string|max:255',
            'metadata.*.type' => 'nullable|in:text,textarea,file',
            'metadata.*.value' => 'nullable|string|max:1000',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'page_id' => $this->pageId,
            'name' => $this->name,
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
            $data['sort_order'] = (int) CmsSection::where('page_id', $this->pageId)->max('sort_order') + 1;
            $cms = CmsSection::create($data);
            $this->cmsId = $cms->id;
        }

        AdminActivity::log(
            $creating ? 'created' : 'updated',
            "CMS section: {$this->name}",
        );

        $this->dispatch('notify', message: $creating ? 'CMS section created successfully' : 'CMS section updated successfully');

        $this->redirect(route('admin.cms', ['pageId' => $this->pageId]), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.cms.form', [
            'page' => Page::findOrFail($this->pageId),
        ])->layout('layouts.admin', ['title' => $this->cmsId ? 'Edit CMS Section' : 'New CMS Section']);
    }
}
