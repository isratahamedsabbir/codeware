<?php

use App\Livewire\Admin\Pages\Form as PagesForm;
use App\Livewire\Admin\Pages\Index as PagesIndex;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('renders pages index', function () {
    Livewire::test(PagesIndex::class)->assertStatus(200);
});

it('displays pages in the table', function () {
    Page::factory()->create(['title' => ['en' => 'About Us', 'bn' => 'আমাদের সম্পর্কে']]);
    Livewire::test(PagesIndex::class)->assertSee('About Us');
});

it('can create a page', function () {
    Livewire::test(PagesForm::class)
        ->set('title.en', 'Contact')
        ->call('save');

    expect(Page::whereJsonContains('title->en', 'Contact')->exists())->toBeTrue();
});

it('validates english title is required', function () {
    Livewire::test(PagesForm::class)
        ->set('title.en', '')
        ->call('save')
        ->assertHasErrors(['title.en']);
});

it('can reorder pages', function () {
    $page1 = Page::factory()->create(['sort_order' => 0]);
    $page2 = Page::factory()->create(['sort_order' => 1]);

    Livewire::test(PagesIndex::class)
        ->call('reorder', [$page2->id, $page1->id]);

    expect(Page::find($page2->id)->sort_order)->toBe(0);
    expect(Page::find($page1->id)->sort_order)->toBe(1);
});

it('can soft-delete a page', function () {
    $page = Page::factory()->create();
    Livewire::test(PagesIndex::class)
        ->call('confirmDelete', $page->id)
        ->call('delete');

    expect(Page::find($page->id))->toBeNull();
    expect(Page::withTrashed()->find($page->id))->not->toBeNull();
});

it('opens the puck editor for an existing page', function () {
    $page = Page::factory()->create();

    $component = Livewire::test(PagesIndex::class)->call('openPuckEditor', $page->id);

    $xjs = $component->effects['xjs'] ?? [];
    expect($xjs[0]['expression'] ?? null)->toContain('\/puck\/edit\/page\/'.$page->id);
});

it('keeps one page\'s puck editor token valid after opening the editor for a different page', function () {
    $pageA = Page::factory()->create();
    $pageB = Page::factory()->create();

    Livewire::test(PagesIndex::class)->call('openPuckEditor', $pageA->id);
    expect($this->admin->tokens()->where('name', "puck-builder-{$pageA->id}")->exists())->toBeTrue();

    Livewire::test(PagesIndex::class)->call('openPuckEditor', $pageB->id);

    expect($this->admin->tokens()->where('name', "puck-builder-{$pageA->id}")->exists())->toBeTrue()
        ->and($this->admin->tokens()->where('name', "puck-builder-{$pageB->id}")->exists())->toBeTrue();
});

it('saves and opens the puck editor for a new page', function () {
    $component = Livewire::test(PagesForm::class)
        ->set('title.en', 'Brand New Page')
        ->call('saveAndOpenPageBuilder');

    expect(Page::whereJsonContains('title->en', 'Brand New Page')->exists())->toBeTrue();

    $xjs = $component->effects['xjs'] ?? [];
    expect($xjs[0]['expression'] ?? null)->toContain('\/puck\/edit\/page\/');
});
