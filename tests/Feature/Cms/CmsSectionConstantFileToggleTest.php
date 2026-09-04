<?php

use App\Livewire\Admin\Cms\Form;
use App\Models\Page;
use App\Models\User;
use Livewire\Livewire;

it('switches content value field to file picker when File is clicked', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $page = Page::factory()->create();

    $this->actingAs($admin);

    Livewire::test(Form::class, ['pageId' => $page->id])
        ->call('addContent')
        ->assertSet('content.0.type', 'textarea')
        ->assertDontSee('mp-content-0-value', false)
        ->call('setContentType', 0, 'file')
        ->assertSet('content.0.type', 'file')
        ->assertSee('mp-content-0-value', false);
});
