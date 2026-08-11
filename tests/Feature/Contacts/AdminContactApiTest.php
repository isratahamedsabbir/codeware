<?php

use App\Models\Contact;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

it('lists contacts with pagination', function () {
    Contact::factory()->count(5)->create();

    $this->actingAs($this->admin)
        ->getJson('/api/v1/admin/contacts?per_page=2')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']])
        ->assertJsonPath('meta.total', 5)
        ->assertJsonPath('meta.per_page', 2);
});

it('filters contacts by status', function () {
    Contact::factory()->create(['status' => 'unread']);
    Contact::factory()->read()->create(['status' => 'read']);

    $this->actingAs($this->admin)
        ->getJson('/api/v1/admin/contacts?status=unread')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'unread');
});

it('shows a single contact', function () {
    $contact = Contact::factory()->create();

    $this->actingAs($this->admin)
        ->getJson("/api/v1/admin/contacts/{$contact->id}")
        ->assertOk()
        ->assertJsonStructure(['data' => [
            'id', 'full_name', 'phone_number', 'email', 'subject', 'message', 'status', 'created_at',
        ]])
        ->assertJsonPath('data.id', $contact->id);
});

it('updates contact status', function () {
    $contact = Contact::factory()->create(['status' => 'unread']);

    $this->actingAs($this->admin)
        ->putJson("/api/v1/admin/contacts/{$contact->id}", ['status' => 'read'])
        ->assertOk();

    expect($contact->fresh()->status)->toBe('read');
});

it('deletes a contact', function () {
    $contact = Contact::factory()->create();

    $this->actingAs($this->admin)
        ->deleteJson("/api/v1/admin/contacts/{$contact->id}")
        ->assertNoContent();

    expect(Contact::find($contact->id))->toBeNull();
});

it('requires admin authentication', function () {
    $this->getJson('/api/v1/admin/contacts')->assertUnauthorized();
});
