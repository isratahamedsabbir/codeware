<?php

use App\Livewire\Admin\Contacts\Index;
use App\Livewire\Admin\Contacts\Index as ContactsIndex;
use App\Mail\TemplateDrivenMail;
use App\Models\Contact;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Mail;
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

it('shows full contact data in the view modal when the view action is clicked', function () {
    $contact = Contact::factory()->create([
        'full_name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'subject' => 'Partnership inquiry',
        'message' => 'This is the full message body that is normally truncated in the table.',
    ]);

    Livewire::test(ContactsIndex::class)
        ->assertSet('viewingContactId', null)
        ->call('showContact', $contact->id)
        ->assertSet('viewingContactId', $contact->id)
        ->assertDispatched('open-modal', name: 'contact-view')
        ->assertSee('Jane Doe')
        ->assertSee('jane@example.com')
        ->assertSee('Partnership inquiry')
        ->assertSee('This is the full message body that is normally truncated in the table.');
});

it('pre-fills the reply email modal with the contact email and subject', function () {
    $contact = Contact::factory()->create([
        'email' => 'jane@example.com',
        'subject' => 'Partnership inquiry',
    ]);

    Livewire::test(ContactsIndex::class)
        ->call('openCustomEmailFor', $contact->id)
        ->assertSet('customEmailTo', 'jane@example.com')
        ->assertSet('customEmailSubject', 'Re: Partnership inquiry')
        ->assertDispatched('close-modal', name: 'contact-view')
        ->assertDispatched('open-modal', name: 'send-custom-email');
});

it('sends a reply email to the contact', function () {
    Mail::fake();

    $contact = Contact::factory()->create(['email' => 'jane@example.com']);

    Livewire::test(ContactsIndex::class)
        ->call('openCustomEmailFor', $contact->id)
        ->set('customEmailSubject', 'Re: your question')
        ->set('customEmailDescription', 'Thanks for reaching out.')
        ->call('sendCustomEmail')
        ->assertHasNoErrors();

    Mail::assertSent(TemplateDrivenMail::class);
});

it('marks a contact as read but never lets it be flipped back to unread', function () {
    $contact = Contact::factory()->create(['status' => 'unread']);

    $component = Livewire::test(ContactsIndex::class)
        ->call('updateStatus', $contact->id, 'read');

    expect($contact->fresh()->status)->toBe('read');

    $component->assertDontSeeHtml('type="checkbox"')
        ->call('updateStatus', $contact->id, 'unread');

    expect($contact->fresh()->status)->toBe('read');
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
