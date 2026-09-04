<?php

use App\Livewire\Admin\Cms\Form;
use App\Models\Page;
use App\Models\User;
use Livewire\Livewire;

it('switches constant value field to file picker when File is clicked', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $page = Page::factory()->create();

    $this->actingAs($admin);

    Livewire::test(Form::class, ['pageId' => $page->id])
        ->call('addConstant')
        ->assertSet('constant.0.type', 'textarea')
        ->assertDontSee('mp-constant-0-value', false)
        ->call('setConstantType', 0, 'file')
        ->assertSet('constant.0.type', 'file')
        ->assertSee('mp-constant-0-value', false);
});
