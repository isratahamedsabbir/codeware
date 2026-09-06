<?php

use App\Livewire\Admin\Pages\Form as PagesForm;
use App\Models\Page;
use App\Models\ProductCategory;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);
});

it('opens the puck editor with the page own type, not always "page"', function () {
    $category = ProductCategory::factory()->create();
    $page = pairPageFor($category, 'product_category', 'category-page', $this->admin->id);

    $component = Livewire::test(PagesForm::class, ['id' => $page->id])
        ->call('openPuckEditor');

    $xjs = $component->effects['xjs'] ?? [];

    expect($xjs)->not->toBeEmpty();
    expect($xjs[0]['expression'])->toContain('\/puck\/edit\/product_category\/'.$page->id)
        ->not->toContain('\/puck\/edit\/page\/'.$page->id);
});

it('still opens the puck editor as "page" for a genuine standalone page', function () {
    $page = Page::factory()->create(['type' => 'page']);

    $component = Livewire::test(PagesForm::class, ['id' => $page->id])
        ->call('openPuckEditor');

    $xjs = $component->effects['xjs'] ?? [];

    expect($xjs[0]['expression'])->toContain('\/puck\/edit\/page\/'.$page->id);
});
