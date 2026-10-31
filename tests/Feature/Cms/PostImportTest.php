<?php

use App\Livewire\Admin\Posts\Index as PostsIndex;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

/**
 * Builds a CSV the way the Posts screen's export writes one. Rows are keyed
 * by header name so a test only spells out the columns it cares about.
 *
 * @param  list<array<string, string|int>>  $rows
 * @param  list<string>|null  $header
 */
function postImportCsv(array $rows, ?array $header = null): string
{
    $header ??= [
        'ID', 'Title (EN)', 'Title (BN)', 'Slug', 'Category', 'Status',
        'Published At', 'Description (EN)', 'Description (BN)',
    ];

    $handle = fopen('php://temp', 'r+');
    fputcsv($handle, $header);

    foreach ($rows as $row) {
        fputcsv($handle, array_map(fn (string $column) => (string) ($row[$column] ?? ''), $header));
    }

    rewind($handle);
    $csv = stream_get_contents($handle);
    fclose($handle);

    return $csv;
}

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);

    $this->import = fn (string $csv, bool $updateExisting = false) => Livewire::test(PostsIndex::class)
        ->set('importFile', UploadedFile::fake()->createWithContent('posts.csv', $csv))
        ->set('importUpdateExisting', $updateExisting)
        ->call('importCsv');
});

it('imports new posts with their translations, category, status and paired page', function () {
    $category = PostCategory::factory()->create(['name' => ['en' => 'News', 'bn' => 'সংবাদ']]);

    $component = ($this->import)(postImportCsv([[
        'Title (EN)' => 'Hello World',
        'Title (BN)' => 'হ্যালো বিশ্ব',
        'Slug' => 'hello-world',
        'Category' => 'News',
        'Status' => 'Active',
        'Published At' => '2026-01-01 09:00:00',
        'Description (EN)' => 'English description',
        'Description (BN)' => 'বাংলা বর্ণনা',
    ]]));

    $post = Post::sole();

    expect($component->get('importResult'))->toMatchArray([
        'created' => 1,
        'updated' => 0,
        'skipped' => 0,
        'errors' => [],
        'warnings' => [],
    ])
        ->and($post->getTranslation('title', 'en'))->toBe('Hello World')
        ->and($post->getTranslation('title', 'bn'))->toBe('হ্যালো বিশ্ব')
        ->and($post->category_id)->toBe($category->id)
        ->and($post->status)->toBe('active')
        ->and($post->user_id)->toBe($this->admin->id)
        ->and($post->published_at->toDateTimeString())->toBe('2026-01-01 09:00:00')
        ->and($post->getTranslation('description', 'en'))->toBe('English description')
        ->and($post->getTranslation('description', 'bn'))->toBe('বাংলা বর্ণনা');

    $page = Page::where(['type' => 'post', 'post_id' => $post->id])->sole();

    expect($page->slug)->toBe('hello-world')
        ->and($page->status)->toBe('active')
        ->and($page->getTranslation('title', 'en'))->toBe('Hello World');
});

it('creates an inactive post with a generated slug and no publish date by default', function () {
    $component = ($this->import)(postImportCsv([[
        'Title (EN)' => 'A New Post',
        'Slug' => '',
        'Status' => '',
    ]]));

    $post = Post::sole();

    expect($component->get('importResult')['created'])->toBe(1)
        ->and($post->status)->toBe('inactive')
        ->and($post->published_at)->toBeNull()
        ->and($post->slug)->toBe('a-new-post');
});

it('updates an existing post matched by id when the update toggle is on', function () {
    $post = Post::factory()->published()->create([
        'title' => ['en' => 'Old Title', 'bn' => 'পুরনো শিরোনাম'],
    ]);
    $page = pairPageFor($post, 'post', 'old-slug', $this->admin->id);
    $page->update(['title' => ['en' => 'Old Title', 'bn' => 'পুরনো শিরোনাম']]);

    // Only the columns a spreadsheet used for a rename: ID and Title (EN).
    $component = ($this->import)(
        postImportCsv([[
            'ID' => $post->id,
            'Title (EN)' => 'Renamed Post',
        ]], ['ID', 'Title (EN)']),
        true,
    );

    $post->refresh();
    $page->refresh();

    expect($component->get('importResult'))->toMatchArray(['created' => 0, 'updated' => 1, 'skipped' => 0])
        ->and($post->getTranslation('title', 'en'))->toBe('Renamed Post')
        ->and($page->getTranslation('title', 'en'))->toBe('Renamed Post')
        ->and($page->slug)->toBe('old-slug')
        ->and($post->status)->toBe('active');
});

it('finds an existing post by slug when the id column is blank', function () {
    $post = Post::factory()->create(['title' => ['en' => 'Legacy Post', 'bn' => '']]);
    pairPageFor($post, 'post', 'legacy-post', $this->admin->id);

    $component = ($this->import)(postImportCsv([[
        'Slug' => 'legacy-post',
        'Title (EN)' => 'Legacy Post Renamed',
        'Status' => 'Inactive',
    ]]), true);

    $post->refresh();

    expect($component->get('importResult')['updated'])->toBe(1)
        ->and($post->getTranslation('title', 'en'))->toBe('Legacy Post Renamed')
        ->and($post->status)->toBe('inactive')
        ->and($post->page->status)->toBe('inactive');
});

it('skips rows for existing posts when the update toggle is off', function () {
    $post = Post::factory()->create(['title' => ['en' => 'Keep Me', 'bn' => '']]);
    pairPageFor($post, 'post', 'keep-me', $this->admin->id);

    $component = ($this->import)(postImportCsv([[
        'ID' => $post->id,
        'Title (EN)' => 'Should Not Replace',
    ]]));

    expect($component->get('importResult'))->toMatchArray(['created' => 0, 'updated' => 0, 'skipped' => 1])
        ->and($post->refresh()->getTranslation('title', 'en'))->toBe('Keep Me');
});

it('reports a row-level error but still imports the other rows', function () {
    $component = ($this->import)(postImportCsv([
        ['Title (EN)' => '', 'Slug' => 'orphan-row'],
        ['Title (EN)' => 'Valid Post', 'Slug' => 'valid-post'],
    ]));

    $result = $component->get('importResult');

    expect($result['created'])->toBe(1)
        ->and($result['skipped'])->toBe(1)
        ->and($result['errors'])->toHaveCount(1)
        ->and($result['errors'][0])->toContain('Line 2: Title (EN) is required.')
        ->and(Post::whereJsonContains('title->en', 'Valid Post')->exists())->toBeTrue();
});

it('warns about an unknown category instead of failing the row', function () {
    $component = ($this->import)(postImportCsv([[
        'Title (EN)' => 'Uncategorised Post',
        'Category' => 'Missing Category',
    ]]));

    $result = $component->get('importResult');

    expect($result['created'])->toBe(1)
        ->and($result['warnings'])->toHaveCount(1)
        ->and($result['warnings'][0])->toContain('Category "Missing Category" was not found')
        ->and(Post::sole()->category_id)->toBeNull();
});

it('rejects a file whose header lacks Title (EN)', function () {
    $component = ($this->import)(postImportCsv(
        [['Title (BN)' => 'শিরোনাম']],
        ['ID', 'Title (BN)'],
    ));

    expect($component->get('importResult'))->toBeNull()
        ->and(Post::count())->toBe(0);

    $component->assertDispatched('notify', fn ($event, $params) => ($params['type'] ?? null) === 'error'
        && str_contains($params['message'] ?? '', 'Title (EN)'));
});

it('appends a suffix when an imported slug is already taken', function () {
    Page::factory()->create(['type' => 'post', 'slug' => 'hello-world', 'status' => 'active']);

    ($this->import)(postImportCsv([[
        'Title (EN)' => 'Another Hello World',
        'Slug' => 'hello-world',
    ]]));

    expect(Post::sole()->slug)->toBe('hello-world-2');
});

it('accepts a header with a UTF-8 BOM and different casing', function () {
    $csv = "\xEF\xBB\xBFtitle (en),title (bn)\nBom Post,বম পোস্ট\n";

    $component = ($this->import)($csv);

    expect($component->get('importResult')['created'])->toBe(1)
        ->and(Post::sole()->getTranslation('title', 'en'))->toBe('Bom Post')
        ->and(Post::sole()->getTranslation('title', 'bn'))->toBe('বম পোস্ট');
});

it('requires a CSV file before importing', function () {
    Livewire::test(PostsIndex::class)
        ->call('importCsv')
        ->assertHasErrors(['importFile']);
});

it('renders the import form only while its modal is open', function () {
    $component = Livewire::test(PostsIndex::class)
        ->assertSet('importModalOpen', false)
        ->assertDontSee('Overwrite existing posts')
        ->call('openImport')
        ->assertSet('importModalOpen', true)
        ->assertSee('Overwrite existing posts')
        ->assertDispatched('open-modal', name: 'post-import')
        ->call('closeImport')
        ->assertSet('importModalOpen', false)
        ->assertDispatched('close-modal', name: 'post-import');
});
