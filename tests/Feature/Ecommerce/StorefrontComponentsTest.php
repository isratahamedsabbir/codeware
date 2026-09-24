<?php

use App\Support\PaymentMethods;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

it('marks earlier checkout steps done and links the cart step back to the cart', function () {
    $html = Blade::render('<x-storefront.checkout-steps :current="2" />');

    expect($html)->toContain('href="'.route('cart').'"')   // step 1 done → link back
        ->and($html)->toContain('bg-emerald-500')           // done style
        ->and($html)->toContain('bg-brand text-white')      // current style
        ->and($html)->toContain('border border-zinc-300');  // upcoming style

    // On the cart itself nothing is done yet, so there's no link back.
    expect(Blade::render('<x-storefront.checkout-steps :current="1" />'))->not->toContain('href=');
});

it('renders the primary button as a link or a button with the same look', function () {
    $link = Blade::render('<x-storefront.button href="/checkout" icon="bag">Go</x-storefront.button>');
    $button = Blade::render('<x-storefront.button form="checkout-form" icon="bag" loading="placeOrder" loading-text="Saving">Place</x-storefront.button>');

    expect($link)->toContain('<a')->toContain('href="/checkout"')->toContain('rounded-[5px] bg-sf-button')
        ->and($button)->toContain('<button')->toContain('type="submit"')->toContain('form="checkout-form"')
        ->toContain('rounded-[5px] bg-sf-button')
        ->toContain('wire:target="placeOrder"')->toContain('Saving');
});

it('styles a storefront input as invalid when its field has an error', function () {
    $errors = (new ViewErrorBag)->put('default', new MessageBag(['customer_name' => ['Required.']]));

    // Pages get $errors shared by Laravel / Livewire; do the same here.
    view()->share('errors', $errors);

    $html = Blade::render('<x-storefront.input name="customer_name" label="Full name" icon="user" />');

    expect($html)->toContain('wire:model="customer_name"')
        ->and($html)->toContain('border-red-300')
        ->and($html)->toContain('Required.');
});

it('maps every payment method to a storefront icon', function () {
    expect(PaymentMethods::icon('cod'))->toBe('banknotes')
        ->and(PaymentMethods::icon('bkash'))->toBe('smartphone')
        ->and(PaymentMethods::icon('paypal'))->toBe('wallet')
        ->and(PaymentMethods::icon('stripe'))->toBe('credit-card');

    // Every icon name it can return exists in the icon component.
    foreach (['banknotes', 'smartphone', 'wallet', 'credit-card'] as $name) {
        expect(Blade::render('<x-storefront.icon name="'.$name.'" />'))->toContain('<path');
    }
});
