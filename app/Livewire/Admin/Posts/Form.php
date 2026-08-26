<?php

namespace App\Livewire\Admin\Posts;

use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Tag;
use App\Support\AdminActivity;
use App\Support\Slug;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    public ?int $postId = null;

    public ?int $pageId = null;

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

    #[Validate('nullable|string')]
    public string $description_en = '';

    #[Validate('nullable|string')]
    public string $description_bn = '';

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
            $this->title_en = $post->getTranslation('title', 'en', false) ?? '';
            $this->title_bn = $post->getTranslation('title', 'bn', false) ?? '';
            $this->slug = $post->slug;
            $this->description_en = $post->getTranslation('description', 'en', false) ?? '';
            $this->description_bn = $post->getTranslation('description', 'bn', false) ?? '';
            $this->category_id = $post->category_id;
            $this->featured_image = $post->featured_image ?? '';
            $this->tag_ids = $post->tags->pluck('id')->all();

            // SEO now lives entirely on the paired Page record, edited via the Page
            // screen — this form only keeps the Page in sync on title/slug/status.
            $this->pageId = $post->page?->id;

            $this->checkSlugAvailability();
        }
    }

    /**
     * Live slug-as-you-type — regenerates from the English title only while
     * the slug still matches what we last auto-generated (i.e. the admin
     * hasn't typed a custom one), or is empty. Editing an existing post's
     * title never touches its already-set slug this way, since autoSlug
     * starts empty and never matches a loaded slug.
     */
    public function updatedTitleEn(string $value): void
    {
        if ($this->slug === '' || $this->slug === $this->autoSlug) {
            $this->autoSlug = Slug::make($value);
            $this->slug = $this->autoSlug;
        }

        $this->checkSlugAvailability();
    }

    /**
     * Fires on direct manual edits to the slug field too, so the red/green
     * indicator stays accurate whether the slug came from auto-typing or a
     * deliberate override.
     */
    public function updatedSlug(): void
    {
        $this->checkSlugAvailability();
    }

    private function checkSlugAvailability(): void
    {
        $this->slugAvailable = Slug::isAvailable($this->slug, $this->pageId, 'posts', $this->postId);
    }

    #[Computed]
    public function categories()
    {
        return PostCategory::orderBy('slug')->get();
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
            now()->addMinutes(config('app.puck_session', 5))
        )->plainTextToken;

        $url = config('cms.editor_base_url')."/puck/edit/post/{$this->pageId}#token={$token}";
        $this->js('window.open('.json_encode($url).', \'_blank\')');
    }

    public function saveAndOpenPageBuilder(): void
    {
        if (empty($this->slug) && $this->title_en) {
            $this->slug = Slug::make($this->title_en);
        }

        $rules = $this->getRules();
        $rules['slug'] = [
            'required', 'string', 'max:255',
            ...Slug::uniqueRules($this->pageId, 'posts', $this->postId),
        ];
        $rules['tag_ids.*'] = 'exists:tags,id';

        $this->validate($rules);

        $this->persistPost();

        $this->dispatch('notify', message: $this->postId ? 'Post updated successfully' : 'Post created successfully');

        auth()->user()->tokens()->where('name', 'puck-builder')->delete();

        $token = auth()->user()->createToken(
            'puck-builder',
            ['*'],
            now()->addMinutes(config('app.puck_session', 5))
        )->plainTextToken;

        $url = config('cms.editor_base_url')."/puck/edit/post/{$this->pageId}#token={$token}";
        $this->js('window.open('.json_encode($url).', \'_blank\')');

        $this->redirect(route('admin.posts.edit', $this->postId), navigate: true);
    }

    public function save(): void
    {
        if (empty($this->slug) && $this->title_en) {
            $this->slug = Slug::make($this->title_en);
        }

        $rules = $this->getRules();
        $rules['slug'] = [
            'required', 'string', 'max:255',
            ...Slug::uniqueRules($this->pageId, 'posts', $this->postId),
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
            'title' => array_filter(['en' => $this->title_en, 'bn' => $this->title_bn]),
            'slug' => $this->slug,
            'description' => array_filter(['en' => $this->description_en, 'bn' => $this->description_bn]) ?: null,
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
                'title' => array_filter(['en' => $this->title_en, 'bn' => $this->title_bn]),
                'slug' => $this->slug,
                'status' => $post->status,
                'description' => array_filter(['en' => $this->description_en, 'bn' => $this->description_bn]) ?: null,
            ]
        );
        $this->pageId = $page->id;

        AdminActivity::log(
            $creating ? 'created' : 'updated',
            "Post #{$post->id}: {$this->title_en}",
        );
    }

    // This method is called by the FilePicker component when an image is selected
    public function render()
    {
        return view('livewire.admin.posts.form')
            ->layout('layouts.admin', ['title' => $this->postId ? 'Edit Post Metadata' : 'New Post']);
    }
}
