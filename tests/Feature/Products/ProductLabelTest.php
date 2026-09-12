<?php

use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Support\PrintAssets;
use Barryvdh\DomPDF\Facade\Pdf;
use Database\Seeders\RolePermissionSeeder;
use Picqer\Barcode\BarcodeGeneratorPNG;

function buildLabelViewData(Product $product, string $code): array
{
    $barcode = (new BarcodeGeneratorPNG)->getBarcode($code, BarcodeGeneratorPNG::TYPE_CODE_128, 2, 60);

    return [
        'product' => $product,
        'code' => $code,
        'barcode' => 'data:image/png;base64,'.base64_encode($barcode),
        'logo' => PrintAssets::logoDataUri(),
        'siteName' => Setting::get('site_name', 'Codeware'),
        'contactPhone' => Setting::get('contact_phone'),
        'contactEmail' => Setting::get('contact_email'),
        'currencySymbol' => Setting::get('currency_symbol', '৳'),
    ];
}

it('renders the label as exactly one page, not two', function () {
    Setting::set('site_name', 'Codeware Ltd');
    Setting::set('contact_phone', '+880 1234-567890');
    Setting::set('contact_email', 'hello@codeware.test');
    $product = Product::factory()->create(['sku' => 'ONE-PAGE-TEST']);

    $pdf = Pdf::loadView('products.label', buildLabelViewData($product, 'ONE-PAGE-TEST'))
        ->setPaper([0, 0, 216, 324]);

    expect($pdf->getDomPDF()->getCanvas()->get_page_count())->toBe(1);
});

it('still fits on one page with long, realistic worst-case content', function () {
    // Short strings alone previously hid a bug (.card { height: 100% }
    // inside an auto-sized body computes unpredictably in dompdf) — a
    // wrapping product name plus long site name/phone/email is what
    // actually pushed it onto a second page.
    Setting::set('site_name', 'Codeware Agro Solutions Limited');
    Setting::set('contact_phone', '+880 1234-567890 / +880 9876-543210');
    Setting::set('contact_email', 'customer.support@codewareagrosolutions.com');
    $product = Product::factory()->create([
        'sku' => 'VERY-LONG-SKU-CODE-001',
        'name' => ['en' => 'Premium Organic Fertilizer Mix for All Season Crops 50kg Bag', 'bn' => ''],
    ]);

    $pdf = Pdf::loadView('products.label', buildLabelViewData($product, $product->sku))
        ->setPaper([0, 0, 216, 324]);

    expect($pdf->getDomPDF()->getCanvas()->get_page_count())->toBe(1);
});

it('includes the site name, phone, and email on the label', function () {
    Setting::set('site_name', 'Codeware Ltd');
    Setting::set('contact_phone', '+880 1234-567890');
    Setting::set('contact_email', 'hello@codeware.test');
    $product = Product::factory()->create(['sku' => 'CONTACT-TEST']);

    $html = view('products.label', buildLabelViewData($product, 'CONTACT-TEST'))->render();

    expect($html)
        ->toContain('Codeware Ltd')
        ->toContain('+880 1234-567890')
        ->toContain('hello@codeware.test');
});

it('gives the barcode explicit width/height attributes, not just CSS', function () {
    // dompdf doesn't reliably shrink an image to fit a percentage-width
    // table cell from CSS (max-width/height:auto) alone — the barcode was
    // overflowing past the card and off the page's right edge until this
    // was set via HTML attributes instead.
    $product = Product::factory()->create(['sku' => 'ATTR-TEST']);

    $html = view('products.label', buildLabelViewData($product, 'ATTR-TEST'))->render();

    expect($html)->toContain('width="150" height="44"');
});

it('lets an admin download a product label as a pdf', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $product = Product::factory()->create(['sku' => 'MOUSE-1']);

    $response = $this->actingAs($admin)->get(route('admin.products.label', $product->id));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
});

it('falls back to a generated code when the product has no sku', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $product = Product::factory()->create(['sku' => null]);

    $response = $this->actingAs($admin)->get(route('admin.products.label', $product->id));

    $response->assertOk();
    expect($response->headers->get('Content-Disposition'))->toContain('label-PROD-'.$product->id);
});

it('can print a label for a soft-deleted product', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $product = Product::factory()->create();
    $product->delete();

    $this->actingAs($admin)
        ->get(route('admin.products.label', $product->id))
        ->assertOk();
});

it('lets staff (who already manage products) print a label too', function () {
    $this->seed(RolePermissionSeeder::class);
    $staff = User::factory()->create(['is_admin' => false]);
    $staff->assignRole('staff');
    $product = Product::factory()->create();

    $this->actingAs($staff)
        ->get(route('admin.products.label', $product->id))
        ->assertOk();
});

it('rejects a user with no admin access at all', function () {
    $customer = User::factory()->create(['is_admin' => false]);
    $product = Product::factory()->create();

    $this->actingAs($customer)
        ->get(route('admin.products.label', $product->id))
        ->assertForbidden();
});
