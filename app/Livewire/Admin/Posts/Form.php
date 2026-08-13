<?php

namespace App\Livewire\Admin\Posts;

use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Tag;
use App\Support\AdminActivity;
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

    #[Validate('nullable|string')]
    public string $description_en = '';

    #[Validate('nullable|string')]
    public string $description_bn = '';

    #[Validate('nullable|string|max:255')]
    public string $seo_title = '';

    #[Validate('nullable|string')]
    public string $seo_description = '';

    #[Validate('nullable|integer|exists:categories,id,type,post')]
    public ?int $category_id = null;

    #[Validate('in:active,inactive')]
    public string $status = 'inactive';

    #[Validate('nullable|array')]
    public array $tag_ids = [];

    public ?string $featured_image = null;

    public string $featuredImagePickerId = '';

    #[Validate('nullable|string')]
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
        $this->featuredImagePickerId = 'featured-image-picker-'.Str::uuid()->toString();
        $this->ogImagePickerId = 'og-image-picker-'.Str::uuid()->toString();

        if ($id) {
            $post = Post::with('page', 'tags')->findOrFail($id);
            $this->postId = $id;
            $this->title_en = $post->getTranslation('title', 'en', false) ?? '';
            $this->title_bn = $post->getTranslation('title', 'bn', false) ?? '';
            $this->slug = $post->slug;
            $this->description_en = $post->getTranslation('description', 'en', false) ?? '';
            $this->description_bn = $post->getTranslation('description', 'bn', false) ?? '';
            $this->category_id = $post->category_id;
            $this->status = $post->status;
            $this->featured_image = $post->featured_image ?? '';
            $this->tag_ids = $post->tags->pluck('id')->all();

            // SEO fields and OG image live on the paired Page record, not on the post
            // itself — the Page is the single source of truth for a post's SEO data.
            $page = $post->page;
            $this->pageId = $page?->id;
            $this->og_image = $page?->og_image ?? null;
            $this->seo_title = $page?->seo_title ?? '';
            $this->seo_description = $page?->seo_description ?? '';
            $this->og_title = $page?->og_title ?? '';
            $this->og_description = $page?->og_description ?? '';
            $this->no_index = (bool) $page?->no_index;
            $this->no_follow = (bool) $page?->no_follow;
        }
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
            $this->slug = Str::slug($this->title_en);
        }

        $rules = $this->getRules();
        $rules['slug'] = $this->postId
            ? 'required|string|max:255|unique:posts,slug,'.$this->postId
            : 'required|string|max:255|unique:posts,slug';
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
            $this->slug = Str::slug($this->title_en);
        }

        $rules = $this->getRules();
        $rules['slug'] = $this->postId
            ? 'required|string|max:255|unique:posts,slug,'.$this->postId
            : 'required|string|max:255|unique:posts,slug';
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
            'status' => $this->status,
            'featured_image' => $this->featured_image ?: null,
            'published_at' => $this->status === 'active' ? now() : null,
        ];

        if ($this->postId) {
            $post = Post::findOrFail($this->postId);
            $post->update($data);
        } else {
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
                'status' => $this->status,
                'description' => array_filter(['en' => $this->description_en, 'bn' => $this->description_bn]) ?: null,
                'og_image' => $this->og_image ?: null,
                'seo_title' => $this->seo_title ?: null,
                'seo_description' => $this->seo_description ?: null,
                'og_title' => $this->og_title ?: null,
                'og_description' => $this->og_description ?: null,
                'no_index' => $this->no_index,
                'no_follow' => $this->no_follow,
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
