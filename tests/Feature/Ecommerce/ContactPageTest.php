<?php

use App\Livewire\Frontend\ContactForm;
use App\Models\Contact;
use App\Models\Page;
use App\Models\Setting;
use Livewire\Livewire;

beforeEach(function () {
    Setting::set('site_theme', 'ecommerce');
    Page::factory()->published()->create(['title' => ['en' => 'Contact Us', 'bn' => ''], 'slug' => 'contact', 'type' => 'page']);
});

it('lays the contact page out as contact details beside the message form', function () {
    Setting::set('contact_phone', '+880 1234 567890');
    Setting::set('contact_email', 'support@example.com');
    Setting::set('contact_address', '');

    $this->get('/contact')
        ->assertOk()
        ->assertSeeInOrder(['Contact Us</h1>', 'Contact information', 'Call us', 'Email us', 'Send us a message'], false)
        ->assertSee('href="tel:+8801234567890"', false)
        ->assertSee('href="mailto:support@example.com"', false)
        // Details that aren't set are left out.
        ->assertDontSee('Visit us')
        ->assertSee('placeholder="How can we help you?"', false);
});

it('widens the form when no contact details are set', function () {
    Setting::set('contact_phone', '');
    Setting::set('contact_email', '');
    Setting::set('contact_address', '');

    $this->get('/contact')
        ->assertOk()
        ->assertDontSee('Contact information')
        ->assertSee('Send us a message');
});

it('still saves messages sent from the contact form', function () {
    Livewire::test(ContactForm::class, ['messagePlaceholder' => 'How can we help you?'])
        ->set('full_name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->set('message', 'Where is my order?')
        ->call('send')
        ->assertSet('sent', true);

    expect(Contact::where('email', 'jane@example.com')->value('message'))->toBe('Where is my order?');
});
