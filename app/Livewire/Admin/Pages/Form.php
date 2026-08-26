<?php

namespace App\Livewire\Admin\Pages;

use App\Models\Page;
use App\Support\AdminActivity;
use App\Support\Slug;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
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

    #[Validate('nullable|string|max:255')]
    public string $seo_title = '';

    #[Validate('nullable|string')]
    public string $seo_description = '';

    #[Validate('nullable|string|max:100')]
    public string $template = 'puck';

    public ?string $og_image = null;

    public string $ogImagePickerId = '';

    #[Validate('nullable|string|max:255')]
    public string $og_title = '';

    #[Validate('nullable|string|max:255')]
    public string $og_description = '';

    public bool $no_index = false;

    public bool $no_follow = false;

    public function mount(?int $id = null): void
    {
        $this->ogImagePickerId = 'page-og-image-picker-'.Str::uuid()->toString();

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
            $this->seo_title = $page->seo_title ?? '';
            $this->seo_description = $page->seo_description ?? '';
            $this->template = $page->template ?? 'puck';
            $this->og_image = $page->og_image ?? null;
            $this->og_title = $page->og_title ?? '';
            $this->og_description = $page->og_description ?? '';
            $this->no_index = (bool) $page->no_index;
            $this->no_follow = (bool) $page->no_follow;

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

        $this->checkSlugAvailability();
    }

    private function checkSlugAvailability(): void
    {
        $this->slugAvailable = Slug::isAvailable($this->slug, $this->pageId);
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
            now()->addMinutes(config('app.puck_session', 5))
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
            now()->addMinutes(config('app.puck_session', 5))
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
            // The linked entity owns the slug — always take its current
            // value rather than trusting the (read-only, but client-supplied)
            // form field, so this Page's slug can never drift from it.
            $this->slug = DB::table($entityTable)->where('id', $entityId)->value('slug');
        } elseif (empty($this->slug) && $this->title_en) {
            $this->slug = Slug::make($this->title_en);
        }

        $rules = $this->getRules();
        $rules['slug'] = $entityTable
            ? ['required', 'string', 'max:255']
            : ['required', 'string', 'max:255', ...Slug::uniqueRules($this->pageId)];

        $this->validate($rules);

        $data = [
            'user_id' => auth()->id(),
            'title' => array_filter(['en' => $this->title_en, 'bn' => $this->title_bn]),
            'slug' => $this->slug,
            'template' => $this->template ?: 'puck',
            'og_image' => $this->og_image ?: null,
            'seo_title' => $this->seo_title ?: null,
            'seo_description' => $this->seo_description ?: null,
            'og_title' => $this->og_title ?: null,
            'og_description' => $this->og_description ?: null,
            'no_index' => $this->no_index,
            'no_follow' => $this->no_follow,
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
     * being a standalone page. The linked entity owns the slug in that case —
     * this Page's slug is just a read-only mirror of it, never an independent
     * value (see persistPage(), which re-reads it from the entity on save).
     */
    public function isLinked(): bool
    {
        return $this->type !== 'page';
    }

    public function render()
    {
        return view('livewire.admin.pages.form')
            ->layout('layouts.admin', ['title' => $this->pageId ? 'Edit Page Metadata' : 'New Page']);
    }
}
