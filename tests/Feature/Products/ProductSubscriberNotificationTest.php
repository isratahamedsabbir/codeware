<?php

use App\Mail\TemplateDrivenMail;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Setting;
use App\Models\Subscriber;
use Illuminate\Support\Facades\Mail;

it('emails subscribed subscribers when a new product is created', function () {
    Mail::fake();
    Setting::set('notify_subscribers_on_new_product', '1');

    $subscribed = Subscriber::factory()->create(['email' => 'jane@example.com']);
    Subscriber::factory()->unsubscribed()->create(['email' => 'john@example.com']);

    Product::factory()->create(['product_category_id' => ProductCategory::factory(), 'name' => ['en' => 'Wireless Mouse', 'bn' => '']]);

    Mail::assertQueued(TemplateDrivenMail::class, fn (TemplateDrivenMail $mail) => $mail->hasTo('jane@example.com')
        && $mail->subjectLine === 'New Product: Wireless Mouse'
    );
    Mail::assertNotQueued(TemplateDrivenMail::class, fn (TemplateDrivenMail $mail) => $mail->hasTo('john@example.com'));
});

it('does not email subscribers when the setting is disabled', function () {
    Mail::fake();
    Setting::set('notify_subscribers_on_new_product', '0');

    Subscriber::factory()->create();

    Product::factory()->create(['product_category_id' => ProductCategory::factory()]);

    Mail::assertNothingQueued();
});
