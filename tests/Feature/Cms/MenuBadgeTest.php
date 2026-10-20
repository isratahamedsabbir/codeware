<?php

use App\Models\Contact;
use App\Models\MenuItem;

it('counts only unread contacts for the Contacts menu item', function () {
    Contact::factory()->count(3)->create(['status' => 'unread']);
    Contact::factory()->create(['status' => 'read']);

    $contacts = new MenuItem(['route_name' => 'admin.contacts']);
    $dashboard = new MenuItem(['route_name' => 'admin.dashboard']);

    expect($contacts->badgeCount())->toBe(3)
        ->and($dashboard->badgeCount())->toBe(0);
});
