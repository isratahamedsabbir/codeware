<?php

use App\Livewire\Admin\Tags\Index as TagsIndex;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('keeps the correct breadcrumb after a wire:click round trip', function () {
    $tag = Tag::factory()->create();

    Livewire::test(TagsIndex::class)
        ->call('toggleSelect', $tag->id)
        ->assertSee('Blog')
        ->assertDontSee('Update');
});

it('hides the row checkboxes until a bulk selection is active', function () {
    $tags = Tag::factory()->count(2)->create();

    $component = Livewire::test(TagsIndex::class);

    $component->assertDontSeeHtml('type="checkbox"');

    $component->call('toggleSelect', $tags[0]->id)
        ->assertSeeHtml('type="checkbox"');
});

it('shows the bulk action toolbar only once something is selected', function () {
    $tag = Tag::factory()->create();

    Livewire::test(TagsIndex::class)
        ->assertDontSee('Delete (')
        ->assertDontSee('Export (')
        ->call('toggleSelect', $tag->id)
        ->assertSee('Delete (1)')
        ->assertSee('Export (1)')
        ->call('toggleSelect', $tag->id)
        ->assertDontSee('Delete (')
        ->assertDontSee('Export (');
});

it('toggles a tag id in and out of the selection', function () {
    $tag = Tag::factory()->create();

    Livewire::test(TagsIndex::class)
        ->call('toggleSelect', $tag->id)
        ->assertSet('selectedIds', [$tag->id])
        ->call('toggleSelect', $tag->id)
        ->assertSet('selectedIds', []);
});

it('does not open the bulk delete modal with nothing selected', function () {
    Livewire::test(TagsIndex::class)
        ->call('confirmBulkDelete')
        ->assertNotDispatched('open-modal');
});

it('opens the bulk delete confirmation modal once something is selected', function () {
    $tag = Tag::factory()->create();

    Livewire::test(TagsIndex::class)
        ->call('toggleSelect', $tag->id)
        ->call('confirmBulkDelete')
        ->assertDispatched('open-modal', name: 'tag-bulk-delete');
});

it('deletes every selected tag and clears the selection', function () {
    $tags = Tag::factory()->count(3)->create();
    $keep = Tag::factory()->create();

    Livewire::test(TagsIndex::class)
        ->call('toggleSelect', $tags[0]->id)
        ->call('toggleSelect', $tags[1]->id)
        ->call('bulkDelete')
        ->assertSet('selectedIds', [])
        ->assertDispatched('notify', message: '2 tags deleted successfully')
        ->assertDispatched('close-modal', name: 'tag-bulk-delete');

    expect(Tag::find($tags[0]->id))->toBeNull()
        ->and(Tag::find($tags[1]->id))->toBeNull()
        ->and(Tag::find($tags[2]->id))->not->toBeNull()
        ->and(Tag::find($keep->id))->not->toBeNull();
});

it('uses singular wording when only one tag is deleted', function () {
    $tag = Tag::factory()->create();

    Livewire::test(TagsIndex::class)
        ->call('toggleSelect', $tag->id)
        ->call('bulkDelete')
        ->assertDispatched('notify', message: '1 tag deleted successfully');
});

it('disables the per-row actions for every row once a bulk selection is active', function () {
    $tags = Tag::factory()->count(2)->create();

    $component = Livewire::test(TagsIndex::class);

    $component->assertSeeHtml(route('admin.tags.edit', $tags[0]->id));

    $component->call('toggleSelect', $tags[0]->id)
        ->assertDontSeeHtml(route('admin.tags.edit', $tags[0]->id))
        ->assertDontSeeHtml(route('admin.tags.edit', $tags[1]->id));
});

it('exports only the requested tag ids as a downloadable csv', function () {
    $included = Tag::factory()->create(['name' => ['en' => 'Included Tag', 'bn' => '']]);
    $excluded = Tag::factory()->create(['name' => ['en' => 'Excluded Tag', 'bn' => '']]);

    $response = $this->get(route('admin.tags.export', ['ids' => [$included->id]]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

    expect($csv)->toContain('Included Tag')
        ->and($csv)->not->toContain('Excluded Tag');
});
