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

it('creates a cms section with titles, descriptions, buttons, cards and images', function () {
    $component = Livewire::test(CmsForm::class)
        ->set('page', 'home')
        ->set('section', 'hero')
        ->set('bg_image', '/storage/media/bg.jpg')
        ->call('addTitle')
        ->set('titles.0.en', 'Welcome')
        ->set('titles.0.bn', 'স্বাগতম')
        ->call('addDescription')
        ->set('descriptions.0.en', 'We build great software.')
        ->call('addButton')
        ->set('buttons.0.label.en', 'Get Started')
        ->set('buttons.0.color', '#16a34a')
        ->set('buttons.0.link', '/contact')
        ->call('addCard')
        ->set('cards.0.image', '/storage/media/card.jpg')
        ->set('cards.0.title.en', 'Fast')
        ->set('cards.0.description.en', 'Blazing fast delivery')
        ->call('addImage')
        ->set('images.0', '/storage/media/gallery-1.jpg')
        ->call('save');

    $cms = CmsSection::where('page', 'home')->where('section', 'hero')->sole();

    expect($cms->bg_image)->toBe('/storage/media/bg.jpg')
        ->and($cms->titles[0]['en'])->toBe('Welcome')
        ->and($cms->titles[0]['bn'])->toBe('স্বাগতম')
        ->and($cms->descriptions[0]['en'])->toBe('We build great software.')
        ->and($cms->buttons[0]['label']['en'])->toBe('Get Started')
        ->and($cms->buttons[0]['color'])->toBe('#16a34a')
        ->and($cms->buttons[0]['link'])->toBe('/contact')
        ->and($cms->cards[0]['title']['en'])->toBe('Fast')
        ->and($cms->cards[0]['image'])->toBe('/storage/media/card.jpg')
        ->and($cms->images[0])->toBe('/storage/media/gallery-1.jpg');
});

it('validates page and section are required', function () {
    Livewire::test(CmsForm::class)
        ->set('page', '')
        ->set('section', '')
        ->call('save')
        ->assertHasErrors(['page', 'section']);
});

it('can remove a repeater row before saving', function () {
    Livewire::test(CmsForm::class)
        ->call('addTitle')
        ->call('addTitle')
        ->set('titles.0.en', 'First')
        ->set('titles.1.en', 'Second')
        ->call('removeTitle', 0)
        ->assertSet('titles.0.en', 'Second');
});

it('loads an existing section for editing', function () {
    $cms = CmsSection::factory()->create([
        'page' => 'home',
        'section' => 'hero',
        'titles' => [['en' => 'Hi', 'bn' => '']],
    ]);

    Livewire::test(CmsForm::class, ['id' => $cms->id])
        ->assertSet('page', 'home')
        ->assertSet('section', 'hero')
        ->assertSet('titles.0.en', 'Hi');
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
