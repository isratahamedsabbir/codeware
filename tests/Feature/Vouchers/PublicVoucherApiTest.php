<?php

use App\Mail\TemplateDrivenMail;
use App\Models\EmailTemplate;
use App\Models\Feature;
use App\Models\Voucher;
use App\Models\VoucherPurchase;
use App\Support\Features;
use Illuminate\Support\Facades\Mail;

it('lists only active vouchers', function () {
    Voucher::factory()->active()->create(['name' => ['en' => 'Active Gift', 'bn' => '']]);
    Voucher::factory()->inactive()->create(['name' => ['en' => 'Hidden Gift', 'bn' => '']]);

    $this->getJson('/api/v1/vouchers')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Active Gift');
});

it('shows a voucher by slug', function () {
    Voucher::factory()->active()->create([
        'name' => ['en' => 'Birthday Gift', 'bn' => ''],
        'description' => ['en' => 'For birthdays', 'bn' => ''],
        'slug' => 'birthday-gift',
    ]);

    $this->getJson('/api/v1/vouchers/birthday-gift')
        ->assertOk()
        ->assertJsonPath('data.slug', 'birthday-gift')
        ->assertJsonPath('data.name', 'Birthday Gift')
        ->assertJsonPath('data.description', 'For birthdays');
});

it('does not expose an inactive voucher by slug', function () {
    Voucher::factory()->inactive()->create(['slug' => 'hidden-gift']);

    $this->getJson('/api/v1/vouchers/hidden-gift')->assertNotFound();
});

it('buys a voucher, issues a code and emails the pdf', function () {
    Mail::fake();
    EmailTemplate::factory()->create(['key' => 'voucher_purchase', 'active' => true]);

    $voucher = Voucher::factory()->active()->create([
        'price' => 900,
        'value' => 1000,
        'currency' => 'BDT',
        'valid_days' => 30,
    ]);

    $response = $this->postJson('/api/v1/vouchers', [
        'voucher_id' => $voucher->id,
        'customer_name' => 'Jane Doe',
        'customer_email' => 'jane@example.com',
        'recipient_name' => 'John Doe',
        'message' => 'Happy birthday!',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.value', 1000)
        ->assertJsonPath('data.price_paid', 900)
        ->assertJsonPath('data.recipient_name', 'John Doe')
        ->assertJsonPath('data.status', 'issued');

    $purchase = VoucherPurchase::firstOrFail();

    expect($purchase->code)->toStartWith('VCH-')
        ->and($purchase->expires_at)->not->toBeNull()
        ->and($purchase->expires_at->isFuture())->toBeTrue();

    Mail::assertSent(TemplateDrivenMail::class, function (TemplateDrivenMail $mail) use ($purchase) {
        return $mail->hasTo('jane@example.com')
            && count($mail->attachments) === 1
            && $mail->attachments[0]->as === "voucher-{$purchase->code}.pdf";
    });
});

it('rejects a voucher that is not active', function () {
    $voucher = Voucher::factory()->inactive()->create();

    $this->postJson('/api/v1/vouchers', [
        'voucher_id' => $voucher->id,
        'customer_name' => 'Jane Doe',
        'customer_email' => 'jane@example.com',
    ])->assertJsonValidationErrors('voucher_id');
});

it('requires the buyer details', function () {
    $this->postJson('/api/v1/vouchers', [])
        ->assertJsonValidationErrors(['voucher_id', 'customer_name', 'customer_email']);
});

it('still issues the voucher when the confirmation email throws', function () {
    EmailTemplate::factory()->create(['key' => 'voucher_purchase', 'active' => true]);
    Mail::shouldReceive('to')->andThrow(new Exception('SMTP connection refused'));

    $voucher = Voucher::factory()->active()->create();

    $this->postJson('/api/v1/vouchers', [
        'voucher_id' => $voucher->id,
        'customer_name' => 'Jane Doe',
        'customer_email' => 'jane@example.com',
    ])->assertCreated();

    expect(VoucherPurchase::query()->count())->toBe(1);
});

it('returns a 404 for every voucher endpoint when the vouchers feature is disabled', function () {
    Feature::updateOrCreate(
        ['key' => 'vouchers'],
        ['label' => Features::ALL['vouchers'], 'is_enabled' => false],
    );

    $this->getJson('/api/v1/vouchers')->assertNotFound();
    $this->getJson('/api/v1/vouchers/anything')->assertNotFound();
    $this->postJson('/api/v1/vouchers', [])->assertNotFound();
});
