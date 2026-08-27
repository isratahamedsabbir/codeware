<?php

use App\Livewire\Admin\Cms\Form as CmsForm;
use App\Livewire\Admin\Cms\Index as CmsIndex;
use App\Models\CmsSection;
use App\Models\MenuItem;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);
});

it('renders the cms index', function () {
    Livewire::test(CmsIndex::class)->assertStatus(200);
});

it('displays existing cms sections in the table', function () {
    CmsSection::factory()->create(['page' => 'home', 'section' => 'hero']);

    Livewire::test(CmsIndex::class)
        ->assertSee('home')
        ->assertSee('hero');
});

it('filters cms sections by page', function () {
    CmsSection::factory()->create(['page' => 'home', 'section' => 'hero']);
    CmsSection::factory()->create(['page' => 'about', 'section' => 'team']);

    Livewire::test(CmsIndex::class)
        ->set('pageFilter', 'about')
        ->assertDontSee('hero')
        ->assertSee('team');
});

it('creates a cms section with a title, description, image, buttons and cards', function () {
    Livewire::test(CmsForm::class)
        ->set('page', 'home')
        ->set('section', 'hero')
        ->set('bg_image', '/storage/media/bg.jpg')
        ->set('image', '/storage/media/hero.jpg')
        ->set('title.en', 'Welcome')
        ->set('title.bn', 'স্বাগতম')
        ->set('description.en', 'We build great software.')
        ->call('addButton')
        ->set('buttons.0.label.en', 'Get Started')
        ->set('buttons.0.color', '#16a34a')
        ->set('buttons.0.link', '/contact')
        ->call('addCard')
        ->set('cards.0.image', '/storage/media/card.jpg')
        ->set('cards.0.title.en', 'Fast')
        ->set('cards.0.description.en', 'Blazing fast delivery')
        ->call('addMetadata')
        ->set('metadata.0.key', 'og:type')
        ->set('metadata.0.value', 'website')
        ->call('save');

    $cms = CmsSection::where('page', 'home')->where('section', 'hero')->sole();

    expect($cms->bg_image)->toBe('/storage/media/bg.jpg')
        ->and($cms->image)->toBe('/storage/media/hero.jpg')
        ->and($cms->title['en'])->toBe('Welcome')
        ->and($cms->title['bn'])->toBe('স্বাগতম')
        ->and($cms->description['en'])->toBe('We build great software.')
        ->and($cms->buttons[0]['label']['en'])->toBe('Get Started')
        ->and($cms->buttons[0]['color'])->toBe('#16a34a')
        ->and($cms->buttons[0]['link'])->toBe('/contact')
        ->and($cms->cards[0]['title']['en'])->toBe('Fast')
        ->and($cms->cards[0]['image'])->toBe('/storage/media/card.jpg')
        ->and($cms->metadata[0]['key'])->toBe('og:type')
        ->and($cms->metadata[0]['value'])->toBe('website');
});

it('can add and remove multiple metadata fields before saving', function () {
    Livewire::test(CmsForm::class)
        ->call('addMetadata')
        ->call('addMetadata')
        ->set('metadata.0.key', 'first')
        ->set('metadata.0.value', '1')
        ->set('metadata.1.key', 'second')
        ->set('metadata.1.value', '2')
        ->call('removeMetadata', 0)
        ->assertSet('metadata.0.key', 'second');
});

it('drops blank metadata rows when saving, but keeps filled ones', function () {
    Livewire::test(CmsForm::class)
        ->set('page', 'home')
        ->set('section', 'hero')
        ->call('addMetadata')
        ->call('addMetadata')
        ->set('metadata.0.key', 'og:type')
        ->set('metadata.0.value', 'website')
        ->set('metadata.1.key', '')
        ->set('metadata.1.value', '')
        ->call('save');

    $cms = CmsSection::where('page', 'home')->where('section', 'hero')->sole();

    expect($cms->metadata)->toHaveCount(1)
        ->and($cms->metadata[0]['key'])->toBe('og:type');
});

it('rejects duplicate metadata keys', function () {
    Livewire::test(CmsForm::class)
        ->set('page', 'home')
        ->set('section', 'hero')
        ->call('addMetadata')
        ->call('addMetadata')
        ->set('metadata.0.key', 'og:type')
        ->set('metadata.0.value', 'website')
        ->set('metadata.1.key', 'og:type')
        ->set('metadata.1.value', 'article')
        ->call('save')
        ->assertHasErrors(['metadata']);
});

it('loads existing metadata for editing', function () {
    $cms = CmsSection::factory()->create([
        'page' => 'home',
        'section' => 'hero',
        'metadata' => [['key' => 'og:type', 'value' => 'website']],
    ]);

    Livewire::test(CmsForm::class, ['id' => $cms->id])
        ->assertSet('metadata.0.key', 'og:type')
        ->assertSet('metadata.0.value', 'website');
});

it('validates page and section are required', function () {
    Livewire::test(CmsForm::class)
        ->set('page', '')
        ->set('section', '')
        ->call('save')
        ->assertHasErrors(['page', 'section']);
});

it('rejects a duplicate section name within the same page', function () {
    CmsSection::factory()->create(['page' => 'home', 'section' => 'hero']);

    Livewire::test(CmsForm::class)
        ->set('page', 'home')
        ->set('section', 'hero')
        ->call('save')
        ->assertHasErrors(['section']);
});

it('allows the same section name on a different page', function () {
    CmsSection::factory()->create(['page' => 'home', 'section' => 'hero']);

    Livewire::test(CmsForm::class)
        ->set('page', 'about')
        ->set('section', 'hero')
        ->call('save')
        ->assertHasNoErrors(['section']);

    expect(CmsSection::where('page', 'about')->where('section', 'hero')->exists())->toBeTrue();
});

it('allows keeping the same section name when editing that same record', function () {
    $cms = CmsSection::factory()->create(['page' => 'home', 'section' => 'hero']);

    Livewire::test(CmsForm::class, ['id' => $cms->id])
        ->set('page', 'home')
        ->set('section', 'hero')
        ->call('save')
        ->assertHasNoErrors(['section']);
});

it('can remove a button before saving', function () {
    Livewire::test(CmsForm::class)
        ->call('addButton')
        ->call('addButton')
        ->set('buttons.0.label.en', 'First')
        ->set('buttons.1.label.en', 'Second')
        ->call('removeButton', 0)
        ->assertSet('buttons.0.label.en', 'Second');
});

it('loads an existing section for editing', function () {
    $cms = CmsSection::factory()->create([
        'page' => 'home',
        'section' => 'hero',
        'title' => ['en' => 'Hi', 'bn' => ''],
    ]);

    Livewire::test(CmsForm::class, ['id' => $cms->id])
        ->assertSet('page', 'home')
        ->assertSet('section', 'hero')
        ->assertSet('title.en', 'Hi');
});

it('can delete a cms section', function () {
    $cms = CmsSection::factory()->create();

    Livewire::test(CmsIndex::class)
        ->call('confirmDelete', $cms->id)
        ->call('delete');

    expect(CmsSection::find($cms->id))->toBeNull();
});

it('seeds a CMS menu item under Content, below Pages', function () {
    $this->artisan('db:seed', ['--class' => 'AdminMenuSeeder']);

    $content = MenuItem::where('label', 'Content')->where('is_group', true)->sole();
    $children = $content->children()->orderBy('sort_order')->pluck('label')->all();

    expect($children)->toBe(['Pages', 'CMS']);
});
