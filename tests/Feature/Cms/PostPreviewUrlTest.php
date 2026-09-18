<?php

use App\Livewire\Admin\Posts\Index as PostsIndex;
use App\Models\Post;
use App\Models\User;
use App\Support\EnvFile;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

beforeEach(function () {
    // EnvFile must never touch the real project .env during tests — point it at a
    // throwaway file instead, and always restore the override afterwards.
    $this->envPath = sys_get_temp_dir().'/post-preview-url-test-'.uniqid().'.env';

    file_put_contents($this->envPath, <<<'ENV'
        APP_URL=https://example.test
        FRONTEND_URL=https://frontend.example.test
        APP_KEY=base64:untouchedsecretkeyvalue==
        ENV);

    EnvFile::$pathOverride = $this->envPath;

    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

afterEach(function () {
    EnvFile::$pathOverride = null;
    @unlink($this->envPath);
    Artisan::call('config:clear');
});

it('links a post straight to the frontend url and slug when no preview path is set', function () {
    config(['app.frontend_url' => 'https://frontend.example.test', 'app.frontend_post_path' => '']);
    pairPageFor(Post::factory()->create(), 'post', 'hello-world', $this->admin->id);

    $html = Livewire::test(PostsIndex::class)->html();

    expect($html)->toContain('https://frontend.example.test/hello-world');
});

it('inserts the configured preview path between the frontend url and the slug', function () {
    config(['app.frontend_url' => 'https://frontend.example.test', 'app.frontend_post_path' => 'blog']);
    pairPageFor(Post::factory()->create(), 'post', 'hello-world', $this->admin->id);

    $html = Livewire::test(PostsIndex::class)->html();

    expect($html)->toContain('https://frontend.example.test/blog/hello-world');
});

it('saves the post preview path from the settings modal', function () {
    Livewire::test(PostsIndex::class)
        ->set('postPreviewPath', 'blog')
        ->call('saveFrontendUrl');

    expect(EnvFile::get('FRONTEND_POST_PATH'))->toBe('blog');
});

it('strips leading and trailing slashes from the saved post preview path', function () {
    Livewire::test(PostsIndex::class)
        ->set('postPreviewPath', '/blog/')
        ->call('saveFrontendUrl');

    expect(EnvFile::get('FRONTEND_POST_PATH'))->toBe('blog');
});
