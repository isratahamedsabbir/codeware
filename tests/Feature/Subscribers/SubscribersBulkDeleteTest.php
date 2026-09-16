<?php

use App\Livewire\Admin\Subscribers\Index as SubscribersIndex;
use App\Models\Subscriber;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('keeps the correct breadcrumb after a wire:click round trip', function () {
    $subscriber = Subscriber::factory()->create();

    Livewire::test(SubscribersIndex::class)
        ->call('toggleSelect', $subscriber->id)
        ->assertSee('Subscribers')
        ->assertDontSee('Update');
});

it('hides the row checkboxes until a bulk selection is active', function () {
    $subscribers = Subscriber::factory()->count(2)->create();

    $component = Livewire::test(SubscribersIndex::class);

    $component->assertDontSeeHtml('type="checkbox"');

    $component->call('toggleSelect', $subscribers[0]->id)
        ->assertSeeHtml('type="checkbox"');
});

it('shows the bulk action toolbar only once something is selected', function () {
    $subscriber = Subscriber::factory()->create();

    Livewire::test(SubscribersIndex::class)
        ->assertDontSee('Delete (')
        ->assertDontSee('Export (')
        ->call('toggleSelect', $subscriber->id)
        ->assertSee('Delete (1)')
        ->assertSee('Export (1)')
        ->call('toggleSelect', $subscriber->id)
        ->assertDontSee('Delete (')
        ->assertDontSee('Export (');
});

it('toggles a subscriber id in and out of the selection', function () {
    $subscriber = Subscriber::factory()->create();

    Livewire::test(SubscribersIndex::class)
        ->call('toggleSelect', $subscriber->id)
        ->assertSet('selectedIds', [$subscriber->id])
        ->call('toggleSelect', $subscriber->id)
        ->assertSet('selectedIds', []);
});

it('does not open the bulk delete modal with nothing selected', function () {
    Livewire::test(SubscribersIndex::class)
        ->call('confirmBulkDelete')
        ->assertNotDispatched('open-modal');
});

it('opens the bulk delete confirmation modal once something is selected', function () {
    $subscriber = Subscriber::factory()->create();

    Livewire::test(SubscribersIndex::class)
        ->call('toggleSelect', $subscriber->id)
        ->call('confirmBulkDelete')
        ->assertDispatched('open-modal', name: 'subscriber-bulk-delete');
});

it('deletes every selected subscriber and clears the selection', function () {
    $subscribers = Subscriber::factory()->count(3)->create();
    $keep = Subscriber::factory()->create();

    Livewire::test(SubscribersIndex::class)
        ->call('toggleSelect', $subscribers[0]->id)
        ->call('toggleSelect', $subscribers[1]->id)
        ->call('bulkDelete')
        ->assertSet('selectedIds', [])
        ->assertDispatched('notify', message: '2 subscribers deleted successfully')
        ->assertDispatched('close-modal', name: 'subscriber-bulk-delete');

    expect(Subscriber::find($subscribers[0]->id))->toBeNull()
        ->and(Subscriber::find($subscribers[1]->id))->toBeNull()
        ->and(Subscriber::find($subscribers[2]->id))->not->toBeNull()
        ->and(Subscriber::find($keep->id))->not->toBeNull();
});

it('uses singular wording when only one subscriber is deleted', function () {
    $subscriber = Subscriber::factory()->create();

    Livewire::test(SubscribersIndex::class)
        ->call('toggleSelect', $subscriber->id)
        ->call('bulkDelete')
        ->assertDispatched('notify', message: '1 subscriber deleted successfully');
});

it('disables the per-row delete action for every row once a bulk selection is active', function () {
    $subscribers = Subscriber::factory()->count(2)->create();

    Livewire::test(SubscribersIndex::class)
        ->call('toggleSelect', $subscribers[0]->id)
        ->assertDontSeeHtml('confirmDelete('.$subscribers[0]->id.')');
});

it('exports only the requested subscriber ids as a downloadable csv', function () {
    $included = Subscriber::factory()->create(['email' => 'included@example.test']);
    $excluded = Subscriber::factory()->create(['email' => 'excluded@example.test']);

    $response = $this->get(route('admin.subscribers.export', ['ids' => [$included->id]]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

    expect($csv)->toContain('included@example.test')
        ->and($csv)->not->toContain('excluded@example.test');
});
