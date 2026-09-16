<?php

use App\Livewire\Admin\Contacts\Index;
use App\Livewire\Admin\Contacts\Index as ContactsIndex;
use App\Models\Contact;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('keeps the correct breadcrumb after a wire:click round trip', function () {
    $contact = Contact::factory()->create();

    Livewire::test(ContactsIndex::class)
        ->call('toggleSelect', $contact->id)
        ->assertSee('Contacts')
        ->assertDontSee('Update');
});

it('hides the row checkboxes until a bulk selection is active', function () {
    $contacts = Contact::factory()->count(2)->create();

    $component = Livewire::test(ContactsIndex::class);

    $component->assertDontSeeHtml('type="checkbox"');

    $component->call('toggleSelect', $contacts[0]->id)
        ->assertSeeHtml('type="checkbox"');
});

it('shows only the export button in the bulk toolbar, never a delete button', function () {
    $contact = Contact::factory()->create();

    Livewire::test(ContactsIndex::class)
        ->assertDontSee('Export (')
        ->call('toggleSelect', $contact->id)
        ->assertSee('Export (1)')
        ->assertDontSee('Delete (');
});

it('toggles a contact id in and out of the selection', function () {
    $contact = Contact::factory()->create();

    Livewire::test(ContactsIndex::class)
        ->call('toggleSelect', $contact->id)
        ->assertSet('selectedIds', [$contact->id])
        ->call('toggleSelect', $contact->id)
        ->assertSet('selectedIds', []);
});

it('has no bulkDelete method — Contacts is deliberately read-only', function () {
    expect(method_exists(Index::class, 'bulkDelete'))->toBeFalse();
});

it('exports only the requested contact ids as a downloadable csv', function () {
    $included = Contact::factory()->create(['full_name' => 'Included Person', 'subject' => 'Hello']);
    $excluded = Contact::factory()->create(['full_name' => 'Excluded Person', 'subject' => 'Hi']);

    $response = $this->get(route('admin.contacts.export', ['ids' => [$included->id]]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

    expect($csv)->toContain('Included Person')
        ->and($csv)->not->toContain('Excluded Person');
});
