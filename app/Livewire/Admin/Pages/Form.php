<?php

namespace App\Livewire\Admin\Pages;

use App\Concerns\HasSeoFields;
use App\Models\Page;
use App\Models\Setting;
use App\Support\AdminActivity;
use App\Support\Slug;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    use HasSeoFields;

    public ?int $pageId = null;

    /**
     * Set from the loaded Page's type/foreign keys — 'page' for a plain page,
     * or 'product'/'post'/'product_category'/'post_category' for one paired
     * with an entity. Only ever set by mount(); never user-editable.
     */
    public string $type = 'page';

    public ?int $productId = null;

    public ?int $postId = null;

    public ?int $categoryId = null;

    #[Validate('required|string|max:255')]
    public string $title_en = '';

    #[Validate('nullable|string|max:255')]
    public string $title_bn = '';

    #[Validate('nullable|string|max:255')]
    public string $slug = '';

    /**
     * The last auto-generated slug value, so we know whether the admin has
     * manually diverged from it — see updatedTitleEn().
     */
    public string $autoSlug = '';

    /**
     * null = not yet checked, true = available (green), false = taken (red).
     */
    public ?bool $slugAvailable = null;

    #[Validate('nullable|string|max:100')]
    public string $template = 'puck';

    /** @var array<int, array{key: string, type: string, value: string}> */
    public array $constant = [];

    public function mount(?int $id = null): void
    {
        if ($id) {
            $page = Page::findOrFail($id);
            $this->pageId = $id;
            $this->type = $page->type;
            $this->productId = $page->product_id;
            $this->postId = $page->post_id;
            $this->categoryId = $page->category_id;
            $this->title_en = $page->getTranslation('title', 'en', false) ?? '';
            $this->title_bn = $page->getTranslation('title', 'bn', false) ?? '';
            $this->slug = $page->slug;
            $this->template = $page->template ?? 'puck';
            $this->hydrateSeoFieldsFromPage($page);

            // Older rows were saved with the since-removed single-line "text" type
            // (or no type at all) — fold both into textarea so they still render/edit correctly.
            $this->constant = collect($page->constant ?? [])
                ->map(fn (array $pair) => [...$pair, 'type' => in_array($pair['type'] ?? null, ['textarea', 'file'], true) ? $pair['type'] : 'textarea'])
                ->all();

            if (! $this->isLinked()) {
                $this->checkSlugAvailability();
            }
        }
    }

    /**
     * Live slug-as-you-type — regenerates from the English title only while
     * the slug still matches what we last auto-generated (i.e. the admin
     * hasn't typed a custom one), or is empty. Editing an existing page's
     * title never touches its already-set slug this way, since autoSlug
     * starts empty and never matches a loaded slug.
     */
    public function updatedTitleEn(string $value): void
    {
        if ($this->isLinked()) {
            return;
        }

        if ($this->slug === '' || $this->slug === $this->autoSlug) {
            $this->autoSlug = Slug::make($value);
            $this->slug = $this->autoSlug;
        }

        $this->syncCanonicalSlug();
        $this->checkSlugAvailability();
    }

    /**
     * Fires on direct manual edits to the slug field too, so the red/green
     * indicator stays accurate whether the slug came from auto-typing or a
     * deliberate override. No-ops for a linked page — its slug field is
     * read-only, the entity owns the value.
     */
    public function updatedSlug(): void
    {
        if ($this->isLinked()) {
            return;
        }

        $this->slug = Slug::lower($this->slug);
        $this->syncCanonicalSlug();
        $this->checkSlugAvailability();
    }

    private function checkSlugAvailability(): void
    {
        $this->slugAvailable = Slug::isAvailable($this->slug, $this->pageId);
    }

    public function addConstant(): void
    {
        $this->constant[] = ['key' => '', 'type' => 'textarea', 'value' => ''];
    }

    public function removeConstant(int $index): void
    {
        unset($this->constant[$index]);
        $this->constant = array_values($this->constant);
    }

    public function setConstantType(int $index, string $type): void
    {
        if (! array_key_exists($index, $this->constant) || ! in_array($type, ['textarea', 'file'], true)) {
            return;
        }

        $this->constant[$index]['type'] = $type;
    }

    public function updated(string $name, mixed $value): void
    {
        if (preg_match('/^constant\.\d+\.key$/', $name)) {
            $sanitized = preg_replace('/[^A-Za-z0-9_]/', '', preg_replace('/\s+/', '_', trim($value)));

            if ($sanitized !== $value) {
                data_set($this, $name, $sanitized);
            }
        }
    }

    public function openPuckEditor(): void
    {
        if (! $this->pageId) {
            return;
        }

        auth()->user()->tokens()->where('name', 'puck-builder')->delete();

        $token = auth()->user()->createToken(
            'puck-builder',
            ['*'],
            now()->addMinutes(Setting::puckSessionMinutes())
        )->plainTextToken;

        $url = config('cms.editor_base_url')."/puck/edit/{$this->type}/{$this->pageId}#token={$token}";
        $this->js('window.open('.json_encode($url).', \'_blank\')');
    }

    public function saveAndOpenPageBuilder(): void
    {
        $this->persistPage();

        auth()->user()->tokens()->where('name', 'puck-builder')->delete();

        $token = auth()->user()->createToken(
            'puck-builder',
            ['*'],
            now()->addMinutes(Setting::puckSessionMinutes())
        )->plainTextToken;

        $url = config('cms.editor_base_url')."/puck/edit/{$this->type}/{$this->pageId}#token={$token}";
        $this->js('window.open('.json_encode($url).', \'_blank\')');
    }

    public function save(): void
    {
        $this->persistPage();

        $this->redirect(route('admin.pages'), navigate: true);
    }

    private function persistPage(): void
    {
        [$entityTable, $entityId] = $this->linkedEntity();

        if ($entityTable && $entityId) {
            // This Page is the sole owner of the slug for a linked entity —
            // always re-read the current DB value rather than trusting the
            // (read-only, but client-supplied) form field, so it can never
            // drift via a tampered request.
            $this->slug = Page::find($this->pageId)?->slug ?? $this->slug;
        } elseif (empty($this->slug) && $this->title_en) {
            $this->slug = Slug::make($this->title_en);
        }

        $rules = $this->getRules();
        $rules['slug'] = $entityTable
            ? ['required', 'string', 'max:255']
            : ['required', 'string', 'max:255', ...Slug::uniqueRules($this->pageId)];
        $rules['constant'] = ['array', function (string $attribute, mixed $value, \Closure $fail) {
            $keys = collect($value)->pluck('key')->filter()->map(fn ($key) => strtolower(trim($key)));

            if ($keys->count() !== $keys->unique()->count()) {
                $fail('Constant keys must be unique.');
            }
        }];
        $rules['constant.*.key'] = ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9_]*$/'];
        $rules['constant.*.type'] = 'nullable|in:textarea,file';
        $rules['constant.*.value'] = 'nullable|string|max:1000';

        $this->validate($rules);

        $data = [
            'user_id' => auth()->id(),
            'title' => array_filter(['en' => $this->title_en, 'bn' => $this->title_bn]),
            'slug' => $this->slug,
            'template' => $this->template ?: 'puck',
            ...$this->seoPagePayload(),
            'constant' => collect($this->constant)->filter(fn ($pair) => filled($pair['key'] ?? null))->values()->all(),
        ];

        $creating = $this->pageId === null;

        if ($this->pageId) {
            Page::findOrFail($this->pageId)->update($data);
            $this->dispatch('notify', message: 'Page updated successfully');
        } else {
            // New pages stay inactive until switched on from the list — status is
            // no longer editable from this form, see Index::toggleStatus().
            $data['status'] = 'inactive';
            $page = Page::create($data);
            $this->pageId = $page->id;
            $this->dispatch('notify', message: 'Page created successfully');
        }

        AdminActivity::log(
            $creating ? 'created' : 'updated',
            "Page #{$this->pageId}: {$this->title_en}",
        );
    }

    /**
     * @return array{0: ?string, 1: ?int} the linked entity's table and id, or
     *                                    [null, null] for a plain page
     */
    private function linkedEntity(): array
    {
        return match ($this->type) {
            'product' => ['products', $this->productId],
            'post' => ['posts', $this->postId],
            'product_category', 'post_category' => ['categories', $this->categoryId],
            default => [null, null],
        };
    }

    /**
     * True when this Page is paired with a Product/Post/Category rather than
     * being a standalone page. The slug field is read-only in that case — it's
     * still edited from the entity's own admin form (Products, Posts, ...),
     * not here (see persistPage(), which re-reads the current value from the
     * database on save rather than trusting the client-supplied field).
     */
    public function isLinked(): bool
    {
        return $this->type !== 'page';
    }

    public function render()
    {
        return view('livewire.admin.pages.form')
            ->layout('layouts.admin', ['title' => $this->pageId ? 'Edit Page Constant' : 'New Page']);
    }
}
