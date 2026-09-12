<?php

use App\Mail\TemplateDrivenMail;
use App\Models\EmailTemplate;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\Mail;

it('sends a customer confirmation and an admin notification when an order is created', function () {
    Mail::fake();
    EmailTemplate::factory()->create(['key' => 'order_confirmation', 'active' => true]);
    EmailTemplate::factory()->create(['key' => 'order_admin_notification', 'active' => true]);
    Setting::set('order_email', 'shop-admin@example.com');

    $order = Order::factory()->create(['customer_email' => 'jane@example.com']);

    Mail::assertSent(TemplateDrivenMail::class, 2);
    Mail::assertSent(TemplateDrivenMail::class, fn ($mail) => $mail->hasTo('jane@example.com'));
    Mail::assertSent(TemplateDrivenMail::class, fn ($mail) => $mail->hasTo('shop-admin@example.com'));
});

it('falls back to the contact email when no dedicated order email is set', function () {
    Mail::fake();
    EmailTemplate::factory()->create(['key' => 'order_admin_notification', 'active' => true]);
    Setting::set('order_email', '');
    Setting::set('contact_email', 'hello@example.com');

    Order::factory()->create();

    Mail::assertSent(TemplateDrivenMail::class, fn ($mail) => $mail->hasTo('hello@example.com'));
});

it('sends nothing when no admin recipient is configured at all', function () {
    Mail::fake();
    EmailTemplate::factory()->create(['key' => 'order_admin_notification', 'active' => true]);
    Setting::set('order_email', '');
    Setting::set('contact_email', '');

    Order::factory()->create();

    Mail::assertNotSent(TemplateDrivenMail::class);
});

it('sends nothing when the templates do not exist or are inactive', function () {
    Mail::fake();

    Order::factory()->create();

    Mail::assertNothingSent();
});

it('still creates the order even if sending its emails throws', function () {
    EmailTemplate::factory()->create(['key' => 'order_confirmation', 'active' => true]);
    Mail::shouldReceive('to')->andThrow(new Exception('SMTP connection refused'));

    $order = Order::factory()->create();

    expect($order->exists)->toBeTrue();
    expect(Order::find($order->id))->not->toBeNull();
});
