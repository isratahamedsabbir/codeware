<?php

use App\Mail\TemplateDrivenMail;
use App\Models\EmailTemplate;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Subscriber;
use Illuminate\Support\Facades\Mail;

it('emails subscribed subscribers when a new product is created', function () {
    Mail::fake();
    Setting::set('notify_subscribers_on_new_product', '1');
    EmailTemplate::factory()->create([
        'key' => 'new_product_notification',
        'subject_template' => 'New Product: {{product_name}}',
        'body_template' => '<p>{{product_name}} is now available.</p>',
        'active' => true,
    ]);

    $subscribed = Subscriber::factory()->create(['email' => 'jane@example.com']);
    Subscriber::factory()->unsubscribed()->create(['email' => 'john@example.com']);

    Product::factory()->create(['name' => ['en' => 'Wireless Mouse', 'bn' => '']]);

    Mail::assertSent(TemplateDrivenMail::class, fn (TemplateDrivenMail $mail) => $mail->hasTo('jane@example.com')
        && $mail->subjectLine === 'New Product: Wireless Mouse'
    );
    Mail::assertNotSent(TemplateDrivenMail::class, fn (TemplateDrivenMail $mail) => $mail->hasTo('john@example.com'));
});

it('does not email subscribers when the setting is disabled', function () {
    Mail::fake();
    Setting::set('notify_subscribers_on_new_product', '0');

    Subscriber::factory()->create();

    Product::factory()->create();

    Mail::assertNothingSent();
});

it('does not email subscribers when the template is missing', function () {
    Mail::fake();
    Setting::set('notify_subscribers_on_new_product', '1');

    Subscriber::factory()->create();

    Product::factory()->create();

    Mail::assertNothingSent();
});
