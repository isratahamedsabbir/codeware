<?php

use App\Livewire\Admin\Cms\Form as CmsForm;
use App\Livewire\Admin\Cms\Index as CmsIndex;
use App\Models\CmsSection;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);
});

it('renders the cms index for a page', function () {
    $page = Page::factory()->create();

    Livewire::test(CmsIndex::class, ['pageId' => $page->id])->assertStatus(200);
});

it('displays existing cms sections in the table', function () {
    $page = Page::factory()->create();
    CmsSection::factory()->create(['page_id' => $page->id, 'name' => 'hero']);

    Livewire::test(CmsIndex::class, ['pageId' => $page->id])
        ->assertSee('hero');
});

it('only shows sections that belong to the page it was opened for', function () {
    $home = Page::factory()->create();
    $about = Page::factory()->create();
    CmsSection::factory()->create(['page_id' => $home->id, 'name' => 'hero']);
    CmsSection::factory()->create(['page_id' => $about->id, 'name' => 'team']);

    Livewire::test(CmsIndex::class, ['pageId' => $about->id])
        ->assertDontSee('hero')
        ->assertSee('team');
});

it('creates a cms section with a name and cards', function () {
    $page = Page::factory()->create();

    Livewire::test(CmsForm::class, ['pageId' => $page->id])
        ->set('name', 'hero')
        ->call('addCard')
        ->set('cards.0.image', '/storage/media/card.jpg')
        ->set('cards.0.title', 'Fast')
        ->set('cards.0.description', 'Blazing fast delivery')
        ->call('addContent')
        ->set('content.0.key', 'og type')
        ->set('content.0.value', 'website')
        ->call('save');

    $cms = CmsSection::where('page_id', $page->id)->where('name', 'hero')->sole();

    expect($cms->cards[0]['title'])->toBe('Fast')
        ->and($cms->cards[0]['description'])->toBe('Blazing fast delivery')
        ->and($cms->cards[0]['image'])->toBe('/storage/media/card.jpg')
        ->and($cms->content[0]['key'])->toBe('og_type')
        ->and($cms->content[0]['value'])->toBe('website');
});

it('can add and remove multiple content fields before saving', function () {
    $page = Page::factory()->create();

    Livewire::test(CmsForm::class, ['pageId' => $page->id])
        ->call('addContent')
        ->call('addContent')
        ->set('content.0.key', 'first')
        ->set('content.0.value', '1')
        ->set('content.1.key', 'second')
        ->set('content.1.value', '2')
        ->call('removeContent', 0)
        ->assertSet('content.0.key', 'second');
});

it('drops blank content rows when saving, but keeps filled ones', function () {
    $page = Page::factory()->create();

    Livewire::test(CmsForm::class, ['pageId' => $page->id])
        ->set('name', 'hero')
        ->call('addContent')
        ->call('addContent')
        ->set('content.0.key', 'og type')
        ->set('content.0.value', 'website')
        ->set('content.1.key', '')
        ->set('content.1.value', '')
        ->call('save');

    $cms = CmsSection::where('page_id', $page->id)->where('name', 'hero')->sole();

    expect($cms->content)->toHaveCount(1)
        ->and($cms->content[0]['key'])->toBe('og_type');
});

it('rejects duplicate content keys', function () {
    $page = Page::factory()->create();

    Livewire::test(CmsForm::class, ['pageId' => $page->id])
        ->set('name', 'hero')
        ->call('addContent')
        ->call('addContent')
        ->set('content.0.key', 'og:type')
        ->set('content.0.value', 'website')
        ->set('content.1.key', 'og:type')
        ->set('content.1.value', 'article')
        ->call('save')
        ->assertHasErrors(['content']);
});

it('loads existing content for editing', function () {
    $page = Page::factory()->create();
    $cms = CmsSection::factory()->create([
        'page_id' => $page->id,
        'name' => 'hero',
        'content' => [['key' => 'og:type', 'value' => 'website']],
    ]);

    Livewire::test(CmsForm::class, ['pageId' => $page->id, 'id' => $cms->id])
        ->assertSet('content.0.key', 'og:type')
        ->assertSet('content.0.value', 'website');
});

it('defaults a new content field to the textarea type', function () {
    $page = Page::factory()->create();

    Livewire::test(CmsForm::class, ['pageId' => $page->id])
        ->call('addContent')
        ->assertSet('content.0.type', 'textarea');
});

it('folds an older content row saved without a type (or the removed "text" type) into textarea', function () {
    $page = Page::factory()->create();
    $cms = CmsSection::factory()->create([
        'page_id' => $page->id,
        'content' => [['key' => 'og:type', 'value' => 'website']],
    ]);

    Livewire::test(CmsForm::class, ['pageId' => $page->id, 'id' => $cms->id])
        ->assertSet('content.0.type', 'textarea');
});

it('can save a content field as a textarea value', function () {
    $page = Page::factory()->create();

    Livewire::test(CmsForm::class, ['pageId' => $page->id])
        ->set('name', 'hero')
        ->call('addContent')
        ->set('content.0.key', 'long_note')
        ->set('content.0.type', 'textarea')
        ->set('content.0.value', "Line one\nLine two")
        ->call('save');

    $cms = CmsSection::where('page_id', $page->id)->where('name', 'hero')->sole();

    expect($cms->content[0]['type'])->toBe('textarea')
        ->and($cms->content[0]['value'])->toBe("Line one\nLine two");
});

it('can save a content field as a file value', function () {
    $page = Page::factory()->create();

    Livewire::test(CmsForm::class, ['pageId' => $page->id])
        ->set('name', 'hero')
        ->call('addContent')
        ->set('content.0.key', 'brochure')
        ->set('content.0.type', 'file')
        ->set('content.0.value', '/storage/media/brochure.pdf')
        ->call('save');

    $cms = CmsSection::where('page_id', $page->id)->where('name', 'hero')->sole();

    expect($cms->content[0]['type'])->toBe('file')
        ->and($cms->content[0]['value'])->toBe('/storage/media/brochure.pdf');
});

it('rejects an unknown content value type', function () {
    $page = Page::factory()->create();

    Livewire::test(CmsForm::class, ['pageId' => $page->id])
        ->call('addContent')
        ->set('content.0.key', 'k')
        ->set('content.0.type', 'not-a-real-type')
        ->set('content.0.value', 'v')
        ->call('save')
        ->assertHasErrors(['content.0.type']);
});

it('validates name is required', function () {
    $page = Page::factory()->create();

    Livewire::test(CmsForm::class, ['pageId' => $page->id])
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name']);
});

it('rejects a duplicate section name within the same page', function () {
    $page = Page::factory()->create();
    CmsSection::factory()->create(['page_id' => $page->id, 'name' => 'hero']);

    Livewire::test(CmsForm::class, ['pageId' => $page->id])
        ->set('name', 'hero')
        ->call('save')
        ->assertHasErrors(['name']);
});

it('allows the same section name on a different page', function () {
    $home = Page::factory()->create();
    $about = Page::factory()->create();
    CmsSection::factory()->create(['page_id' => $home->id, 'name' => 'hero']);

    Livewire::test(CmsForm::class, ['pageId' => $about->id])
        ->set('name', 'hero')
        ->call('save')
        ->assertHasNoErrors(['name']);

    expect(CmsSection::where('page_id', $about->id)->where('name', 'hero')->exists())->toBeTrue();
});

it('allows keeping the same section name when editing that same record', function () {
    $page = Page::factory()->create();
    $cms = CmsSection::factory()->create(['page_id' => $page->id, 'name' => 'hero']);

    Livewire::test(CmsForm::class, ['pageId' => $page->id, 'id' => $cms->id])
        ->set('name', 'hero')
        ->call('save')
        ->assertHasNoErrors(['name']);
});

it('loads an existing section for editing', function () {
    $page = Page::factory()->create();
    $cms = CmsSection::factory()->create([
        'page_id' => $page->id,
        'name' => 'hero',
        'cards' => [['image' => null, 'title' => 'Hi', 'description' => '']],
    ]);

    Livewire::test(CmsForm::class, ['pageId' => $page->id, 'id' => $cms->id])
        ->assertSet('pageId', $page->id)
        ->assertSet('name', 'hero')
        ->assertSet('cards.0.title', 'Hi');
});

it('can delete a cms section', function () {
    $page = Page::factory()->create();
    $cms = CmsSection::factory()->create(['page_id' => $page->id]);

    Livewire::test(CmsIndex::class, ['pageId' => $page->id])
        ->call('confirmDelete', $cms->id)
        ->call('delete');

    expect(CmsSection::find($cms->id))->toBeNull();
});

it('can reorder cms sections within a page', function () {
    $page = Page::factory()->create();
    $first = CmsSection::factory()->create(['page_id' => $page->id, 'name' => 'hero', 'sort_order' => 0]);
    $second = CmsSection::factory()->create(['page_id' => $page->id, 'name' => 'features', 'sort_order' => 1]);

    Livewire::test(CmsIndex::class, ['pageId' => $page->id])
        ->call('reorder', [$second->id, $first->id]);

    expect($second->fresh()->sort_order)->toBe(0)
        ->and($first->fresh()->sort_order)->toBe(1);
});

it('seeds Pages as a standalone menu item, with no separate CMS item', function () {
    $this->artisan('db:seed', ['--class' => 'AdminMenuSeeder']);

    $pages = MenuItem::where('route_name', 'admin.pages')->sole();

    expect($pages->is_group)->toBeFalse()
        ->and($pages->parent_id)->toBeNull()
        ->and(MenuItem::where('route_name', 'admin.cms')->exists())->toBeFalse();
});
