<?php

namespace App\Livewire\Admin\Posts;

use App\Concerns\HasSeoFields;
use App\Concerns\HasTranslatableFields;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Setting;
use App\Models\Tag;
use App\Support\AdminActivity;
use App\Support\Locale;
use App\Support\Slug;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    use HasSeoFields, HasTranslatableFields;

    public ?int $postId = null;

    public ?int $pageId = null;

    public array $title = [];

    public array $description = [];

    #[Validate('nullable|string|max:255')]
    public string $slug = '';

    /**
     * The last auto-generated slug value, so we know whether the admin has
     * manually diverged from it — see updated().
     */
    public string $autoSlug = '';

    /**
     * null = not yet checked, true = available (green), false = taken (red).
     */
    public ?bool $slugAvailable = null;

    #[Validate('nullable|integer|exists:categories,id,type,post')]
    public ?int $category_id = null;

    #[Validate('nullable|array')]
    public array $tag_ids = [];

    public ?string $featured_image = null;

    public string $featuredImagePickerId = '';

    public function mount(?int $id = null): void
    {
        $this->featuredImagePickerId = 'featured-image-picker-'.Str::uuid()->toString();

        if ($id) {
            $post = Post::with('page', 'tags')->findOrFail($id);
            $this->postId = $id;
            $this->hydrateTranslatable($post, ['title', 'description']);
            $this->slug = $post->slug ?? '';
            $this->category_id = $post->category_id;
            $this->featured_image = $post->featured_image ?? '';
            $this->tag_ids = $post->tags->pluck('id')->all();

            $this->pageId = $post->page?->id;
            $this->hydrateSeoFieldsFromPage($post->page);

            $this->checkSlugAvailability();
        }
    }

    /**
     * Live slug-as-you-type — regenerates from the primary locale's title
     * only while the slug still matches what we last auto-generated (i.e.
     * the admin hasn't typed a custom one), or is empty. Editing an existing
     * post's title never touches its already-set slug this way, since
     * autoSlug starts empty and never matches a loaded slug. Livewire's
     * magic updated{Field}() hooks don't fire for array sub-key mutations
     * like "title.en", so this lives in the generic updated() catch-all
     * instead.
     */
    public function updated(string $name, mixed $value): void
    {
        if (! $this->isPrimaryLocaleUpdate($name, 'title')) {
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
     * deliberate override.
     */
    public function updatedSlug(): void
    {
        $this->slug = Slug::lower($this->slug);
        $this->syncCanonicalSlug();
        $this->checkSlugAvailability();
    }

    private function checkSlugAvailability(): void
    {
        $this->slugAvailable = Slug::isAvailable($this->slug, $this->pageId);
    }

    #[Computed]
    public function categories()
    {
        return PostCategory::orderBy('name->'.Locale::primary())->get();
    }

    #[Computed]
    public function tags()
    {
        return Tag::orderBy('id')->get();
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

        $url = config('cms.editor_base_url')."/puck/edit/post/{$this->pageId}#token={$token}";
        $this->js('window.open('.json_encode($url).', \'_blank\')');
    }

    public function saveAndOpenPageBuilder(): void
    {
        if (empty($this->slug) && $this->primaryValue('title')) {
            $this->slug = Slug::make($this->primaryValue('title'));
        }

        $rules = array_merge($this->getRules(), $this->translatableRules([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]));
        $rules['slug'] = [
            'required', 'string', 'max:255',
            ...Slug::uniqueRules($this->pageId),
        ];
        $rules['tag_ids.*'] = 'exists:tags,id';

        $this->validate($rules);

        $this->persistPost();

        $this->dispatch('notify', message: $this->postId ? 'Post updated successfully' : 'Post created successfully');

        auth()->user()->tokens()->where('name', 'puck-builder')->delete();

        $token = auth()->user()->createToken(
            'puck-builder',
            ['*'],
            now()->addMinutes(Setting::puckSessionMinutes())
        )->plainTextToken;

        $url = config('cms.editor_base_url')."/puck/edit/post/{$this->pageId}#token={$token}";
        $this->js('window.open('.json_encode($url).', \'_blank\')');

        $this->redirect(route('admin.posts.edit', $this->postId), navigate: true);
    }

    public function save(): void
    {
        if (empty($this->slug) && $this->primaryValue('title')) {
            $this->slug = Slug::make($this->primaryValue('title'));
        }

        $rules = array_merge($this->getRules(), $this->translatableRules([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]));
        $rules['slug'] = [
            'required', 'string', 'max:255',
            ...Slug::uniqueRules($this->pageId),
        ];
        $rules['tag_ids.*'] = 'exists:tags,id';

        $this->validate($rules);

        $this->persistPost();

        $this->dispatch('notify', message: $this->postId ? 'Post updated successfully' : 'Post created successfully');

        $this->redirect(route('admin.posts'), navigate: true);
    }

    private function persistPost(): void
    {
        $creating = $this->postId === null;

        $data = [
            'user_id' => auth()->id(),
            'title' => $this->translatablePayload('title'),
            'description' => $this->translatablePayload('description') ?: null,
            'category_id' => $this->category_id,
            'featured_image' => $this->featured_image ?: null,
        ];

        if ($this->postId) {
            $post = Post::findOrFail($this->postId);
            $post->update($data);
        } else {
            // New posts stay inactive until switched on from the list — status is
            // no longer editable from this form, see Index::toggleStatus().
            $data['status'] = 'inactive';
            $post = Post::create($data);
            $this->postId = $post->id;
        }

        $post->tags()->sync($this->tag_ids);

        $page = Page::updateOrCreate(
            ['type' => 'post', 'post_id' => $post->id],
            [
                'user_id' => auth()->id(),
                'title' => $this->translatablePayload('title'),
                'description' => $this->translatablePayload('description') ?: null,
                'slug' => $this->slug,
                'status' => $post->status,
                ...$this->seoPagePayload(),
            ]
        );
        $this->pageId = $page->id;

        AdminActivity::log(
            $creating ? 'created' : 'updated',
            "Post #{$post->id}: {$this->primaryValue('title')}",
        );
    }

    // This method is called by the FilePicker component when an image is selected
    public function render()
    {
        return view('livewire.admin.posts.form')
            ->layout('layouts.admin', ['title' => $this->postId ? 'Edit Post Metadata' : 'New Post']);
    }
}
