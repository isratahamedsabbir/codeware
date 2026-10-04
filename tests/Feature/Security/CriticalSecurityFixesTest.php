<?php

/**
 * Regression tests for the Phase 1 critical security fixes.
 *
 * Every test here exists because a specific fix could be undone by an edit
 * that looks harmless — a route re-added "just for local testing", an
 * allow-list replaced by a request parameter, a stored extension switched back
 * to the client's filename. Each test names the vulnerability it pins down in
 * the `it(...)` description.
 */

use App\Livewire\Admin\FileManager\Index as FileManager;
use App\Livewire\Vendor\Orders\Show as VendorOrderShow;
use App\Livewire\Vendor\Profile;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVendor;
use App\Models\User;
use App\Models\UserDocument;
use App\Support\FileManagerPath;
use App\Support\PuckEditor;
use App\Support\SafeUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

// ---------------------------------------------------------------------------
// #1 — GET /token privilege escalation
// ---------------------------------------------------------------------------

it('does not expose a route that mints a Sanctum token on plain auth', function () {
    $routes = collect(app('router')->getRoutes())->map(fn ($route) => $route->uri());

    expect($routes)->not->toContain('token');
});

it('404s a customer who walks to the old token debug route', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer)->get('/token')->assertNotFound();
});

it('404s an admin at the old token debug route too, since it served everyone', function () {
    $admin = User::factory()->create();
    Role::findOrCreate('admin', 'web');
    $admin->assignRole('admin');

    $this->actingAs($admin)->get('/token')->assertNotFound();
});

it('never hands out a wildcard ability on the Puck editor token', function () {
    expect(PuckEditor::ABILITY)->not->toBe('*');

    $admin = User::factory()->create();

    $token = PuckEditor::token($admin, 'puck-test');

    expect($admin->tokens()->first()->abilities)
        ->toContain(PuckEditor::ABILITY)
        ->not->toContain('*');

    expect($token)->toBeString()->not->toBeEmpty();
});

// ---------------------------------------------------------------------------
// #2 — Chunked upload: the extension allow-list was a request parameter
// ---------------------------------------------------------------------------

beforeEach(function () {
    Role::findOrCreate('admin', 'web');
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->actingAs($this->admin);
});

it('refuses a chunked upload of a php file even when the request allow-lists php', function () {
    Storage::fake('local');
    Storage::fake('public');

    $response = $this->postJson(route('admin.media-library.chunk-upload'), [
        'chunk' => UploadedFile::fake()->createWithContent('chunk', '<?php system($_GET["c"]);'),
        'chunkIndex' => 0,
        'totalChunks' => 1,
        'uploadId' => 'rce-attempt',
        'filename' => 'shell.php',
        'allowedExt' => 'php',
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors('filename');

    expect(Storage::disk('public')->allFiles())->toBeEmpty();
});

it('refuses a chunked upload of a php file disguised with a double extension', function () {
    Storage::fake('local');
    Storage::fake('public');

    $this->postJson(route('admin.media-library.chunk-upload'), [
        'chunk' => UploadedFile::fake()->createWithContent('chunk', '<?php'),
        'chunkIndex' => 0,
        'totalChunks' => 1,
        'uploadId' => 'double-ext',
        'filename' => 'invoice.pdf.php',
        'allowedExt' => 'php,pdf',
    ])->assertStatus(422)->assertJsonValidationErrors('filename');

    expect(Storage::disk('public')->allFiles())->toBeEmpty();
});

it('refuses a chunked upload of an env file', function () {
    Storage::fake('local');
    Storage::fake('public');

    $this->postJson(route('admin.media-library.chunk-upload'), [
        'chunk' => UploadedFile::fake()->createWithContent('chunk', 'APP_KEY=base64:leaked'),
        'chunkIndex' => 0,
        'totalChunks' => 1,
        'uploadId' => 'env-attempt',
        'filename' => '.env',
        'allowedExt' => 'env',
    ])->assertStatus(422)->assertJsonValidationErrors('filename');
});

it('still accepts a normal file when the request omits allowedExt entirely', function () {
    Storage::fake('local');
    Storage::fake('public');

    $response = $this->postJson(route('admin.media-library.chunk-upload'), [
        'chunk' => UploadedFile::fake()->createWithContent('chunk', 'Report body'),
        'chunkIndex' => 0,
        'totalChunks' => 1,
        'uploadId' => 'no-allowed-ext',
        'filename' => 'report.pdf',
    ]);

    $response->assertOk()->assertJson(['done' => true]);
});

it('honours allowedExt as a narrowing hint, not a widening one', function () {
    Storage::fake('local');
    Storage::fake('public');

    // The picker modal offers images-only, so a pdf is refused when the caller
    // says images-only — while never becoming a way to ask for something new.
    $this->postJson(route('admin.media-library.chunk-upload'), [
        'chunk' => UploadedFile::fake()->createWithContent('chunk', 'body'),
        'chunkIndex' => 0,
        'totalChunks' => 1,
        'uploadId' => 'narrowed',
        'filename' => 'report.pdf',
        'allowedExt' => 'jpg,png',
    ])->assertStatus(422)->assertJsonValidationErrors('filename');
});

it('caps the size of a single chunk', function () {
    Storage::fake('local');
    Storage::fake('public');

    $this->postJson(route('admin.media-library.chunk-upload'), [
        'chunk' => UploadedFile::fake()->create('chunk', 11 * 1024), // 11 MB
        'chunkIndex' => 0,
        'totalChunks' => 1,
        'uploadId' => 'fat-chunk',
        'filename' => 'big.mp4',
    ])->assertStatus(422)->assertJsonValidationErrors('chunk');

    expect(Storage::disk('public')->allFiles())->toBeEmpty();
});

// ---------------------------------------------------------------------------
// #3 — Stored extension came from the client's filename
// ---------------------------------------------------------------------------

it('derives a stored extension from the content, not the client filename', function () {
    // Named .php, but the bytes are a PDF: the stored name must follow the bytes.
    $disguised = UploadedFile::fake()->create('shell.php', 1, 'application/pdf');

    expect(SafeUpload::extension($disguised))->toBe('pdf');

    $real = UploadedFile::fake()->create('notes.txt', 1, 'text/plain');
    expect(SafeUpload::extension($real))->toBe('txt');
});

it('falls back to a non-attacker-chosen extension for content it cannot type', function () {
    // No extension at all: must not become a trailing-dot or client-named file.
    $nameless = UploadedFile::fake()->create('Makefile', 1, 'application/x-empty');

    expect(SafeUpload::extension($nameless))->not->toBe('')->not->toBe('php');
});

it('stores a vendor NID upload under a content-derived extension', function () {
    Role::findOrCreate('vendor', 'web');
    $vendor = User::factory()->create();
    $vendor->assignRole('vendor');
    $productVendor = ProductVendor::factory()->create();
    $productVendor->users()->attach($vendor);

    Livewire::actingAs($vendor)
        ->test(Profile::class)
        ->set('newDocuments', [UploadedFile::fake()->create('id.pdf', 20, 'application/pdf')])
        ->call('uploadDocuments')
        ->assertHasNoErrors();

    $stored = UserDocument::first();

    expect($stored)->not->toBeNull();
    expect($stored->file)->toEndWith('.pdf');
    expect($stored->file)->not->toContain('.php');
});

// ---------------------------------------------------------------------------
// #4 — File Manager was rooted at the whole project
// ---------------------------------------------------------------------------

function securityFileManagerAdmin(): User
{
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    Permission::findOrCreate('view file manager', 'web');
    Permission::findOrCreate('manage file manager', 'web');
    $role = Role::findOrCreate('admin', 'web');
    $role->givePermissionTo(['view file manager', 'manage file manager']);

    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

it('refuses to resolve the .env file, which sits at the project root', function () {
    expect(fn () => FileManagerPath::resolve('.env'))
        ->toThrow(NotFoundHttpException::class);

    // Any variant, not just the one this deployment happens to use.
    expect(FileManagerPath::isDenied('.env.backup'))->toBeTrue()
        ->and(FileManagerPath::isDenied('.ENV'))->toBeTrue();
});

it('refuses to resolve the vendor, node_modules and bootstrap trees', function () {
    foreach (['vendor/laravel/framework', 'node_modules/vite', 'bootstrap/cache', 'storage/framework/views', 'storage/logs', 'public/build/assets'] as $path) {
        expect(FileManagerPath::isDenied($path))->toBeTrue("expected {$path} to be denied");
    }

    expect(fn () => FileManagerPath::resolve('vendor/laravel'))
        ->toThrow(NotFoundHttpException::class);
});

it('still resolves ordinary asset paths, so the tool keeps working', function () {
    expect(FileManagerPath::isDenied('public/img'))->toBeFalse();
    expect(FileManagerPath::isDenied('storage/app/public'))->toBeFalse();
    expect(FileManagerPath::isDenied('themes/ecommerce/public/css'))->toBeFalse();
    expect(FileManagerPath::isDenied('resources/views'))->toBeFalse();
    expect(FileManagerPath::isDenied('composer.json'))->toBeFalse();
    expect(FileManagerPath::isDenied(''))->toBeFalse();
});

it('refuses to stream .env through the raw file route', function () {
    $admin = securityFileManagerAdmin();

    $this->actingAs($admin)
        ->get(route('admin.file-manager.raw', ['path' => '.env']))
        ->assertNotFound();

    $this->actingAs($admin)
        ->get(route('admin.file-manager.raw', ['path' => '.env', 'download' => 1]))
        ->assertNotFound();
});

it('rejects executable and secret file names for create, compose, rename and upload', function () {
    foreach (['shell.php', 'shell.PHTML', 'shell.phar', '.env', 'web.config', 'id_rsa', 'private.pem', 'app.ini', 'notes.txt', 'image.jpg', 'archive.zip', 'Makefile'] as $name) {
        $expected = in_array($name, ['notes.txt', 'image.jpg', 'archive.zip', 'Makefile'], true);

        expect(FileManagerPath::nameIsAllowed($name))->toBe($expected, "unexpected verdict for {$name}");
    }
});

it('rejects a name containing a path separator, which would escape the target folder', function () {
    expect(FileManagerPath::nameIsAllowed('../shell.php'))->toBeFalse();
    expect(FileManagerPath::nameIsAllowed('notes/shell.php'))->toBeFalse();
    expect(FileManagerPath::nameIsAllowed('..\\..\\shell.php'))->toBeFalse();
});

it('does not let the File Manager create a php file in the project', function () {
    $admin = securityFileManagerAdmin();
    $this->actingAs($admin);

    $fixture = storage_path('app/security-fm');
    File::ensureDirectoryExists($fixture);

    Livewire::test(FileManager::class, ['path' => 'storage/app/security-fm'])
        ->set('createType', 'file')
        ->set('newName', 'shell.php')
        ->call('createEntry')
        ->assertHasErrors('newName');

    expect(File::exists($fixture.'/shell.php'))->toBeFalse();

    File::deleteDirectory($fixture);
});

it('does not let the File Manager compose a php file', function () {
    $admin = securityFileManagerAdmin();
    $this->actingAs($admin);

    $fixture = storage_path('app/security-fm-compose');
    File::ensureDirectoryExists($fixture);

    Livewire::test(FileManager::class, ['path' => 'storage/app/security-fm-compose'])
        ->set('composeName', 'shell.php')
        ->set('composeContent', '<?php system($_GET["c"]);')
        ->call('composeFile')
        ->assertHasErrors('composeName');

    expect(File::exists($fixture.'/shell.php'))->toBeFalse();

    File::deleteDirectory($fixture);
});

it('does not let the File Manager upload a php file', function () {
    $admin = securityFileManagerAdmin();
    $this->actingAs($admin);

    $fixture = storage_path('app/security-fm-upload');
    File::ensureDirectoryExists($fixture);

    // updatedUploads() is a lifecycle hook — setting the property is what
    // invokes it, Livewire 4 refuses an explicit ->call().
    Livewire::test(FileManager::class, ['path' => 'storage/app/security-fm-upload'])
        ->set('uploads', [UploadedFile::fake()->createWithContent('shell.php', '<?php system($_GET["c"]);')]);

    expect(File::exists($fixture.'/shell.php'))->toBeFalse();

    File::deleteDirectory($fixture);
});

it('does not let the File Manager rename a text file into a php file', function () {
    $admin = securityFileManagerAdmin();
    $this->actingAs($admin);

    $fixture = storage_path('app/security-fm-rename');
    File::ensureDirectoryExists($fixture);
    File::put($fixture.'/notes.txt', 'hello');

    Livewire::test(FileManager::class, ['path' => 'storage/app/security-fm-rename'])
        ->call('openRenameModal', 'notes.txt')
        ->set('renameNewName', 'shell.php')
        ->call('renameEntry')
        ->assertHasErrors('renameNewName');

    expect(File::exists($fixture.'/shell.php'))->toBeFalse();
    expect(File::exists($fixture.'/notes.txt'))->toBeTrue();

    File::deleteDirectory($fixture);
});

it('does not extract a zip that carries a php file', function () {
    $admin = securityFileManagerAdmin();
    $this->actingAs($admin);

    $fixture = storage_path('app/security-fm-zip');
    File::ensureDirectoryExists($fixture);

    $zipPath = $fixture.'/bundle.zip';
    $zip = new ZipArchive;
    $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('notes.txt', 'harmless');
    $zip->addFromString('shell.php', '<?php system($_GET["c"]);');
    $zip->close();

    Livewire::test(FileManager::class, ['path' => 'storage/app/security-fm-zip'])
        ->call('extractZip', 'bundle.zip');

    expect(File::exists($fixture.'/bundle'))->toBeFalse();

    File::deleteDirectory($fixture);
});

// ---------------------------------------------------------------------------
// #5 — .env.example shipped the deployment's own secrets
// ---------------------------------------------------------------------------

it('ships no live APP_KEY or Reverb secret in .env.example', function () {
    $contents = File::get(base_path('.env.example'));

    expect($contents)->toMatch('/^APP_KEY=\s*$/m')
        ->and($contents)->toMatch('/^REVERB_APP_KEY=\s*$/m')
        ->and($contents)->toMatch('/^REVERB_APP_SECRET=\s*$/m')
        ->and($contents)->not->toMatch('/^APP_KEY=base64:\S+/m')
        ->and($contents)->not->toMatch('/^REVERB_APP_SECRET=\S+/m');
});

// ---------------------------------------------------------------------------
// #6 — Vendor order IDOR via a public Eloquent model property
// ---------------------------------------------------------------------------

it('holds no Eloquent model as a public Livewire property on the vendor order screen', function () {
    $properties = (new ReflectionClass(VendorOrderShow::class))->getProperties(ReflectionProperty::IS_PUBLIC);

    foreach ($properties as $property) {
        $type = $property->getType();

        expect($type === null || ! str_contains((string) $type->getName(), 'App\\Models'))
            ->toBeTrue("public property \${$property->getName()} holds a model, so Livewire rehydrates it without re-running the vendor scope");
    }

    $names = array_map(fn ($property) => $property->getName(), $properties);

    expect($names)->toContain('orderId')
        ->and($names)->not->toContain('order');
});

it('404s a vendor who swaps the order id in the wire payload for another vendor\'s order', function () {
    $vendorA = User::factory()->create();
    $vendorB = User::factory()->create();

    $pvA = ProductVendor::factory()->create();
    $pvA->users()->attach($vendorA);

    $pvB = ProductVendor::factory()->create();
    $pvB->users()->attach($vendorB);

    $mineProduct = Product::factory()->create(['vendor_id' => $pvA->id]);
    $theirProduct = Product::factory()->create(['vendor_id' => $pvB->id]);

    $theirOrder = Order::factory()->create([
        'customer_name' => 'Private Person',
        'customer_email' => 'private@example.com',
    ]);
    OrderItem::factory()->create([
        'order_id' => $theirOrder->id,
        'product_id' => $theirProduct->id,
    ]);

    $myOrder = Order::factory()->create();
    OrderItem::factory()->create([
        'order_id' => $myOrder->id,
        'product_id' => $mineProduct->id,
    ]);

    Livewire::actingAs($vendorA)
        ->test(VendorOrderShow::class, ['orderId' => $myOrder->id])
        ->assertOk()
        // The tampering that used to work: the id is a plain int on the payload
        // now, and every render re-scopes it through findOrder().
        ->set('orderId', $theirOrder->id)
        ->assertNotFound();
});

// ---------------------------------------------------------------------------
// #7 — Stored XSS through the invoice line-item SKU
// ---------------------------------------------------------------------------

it('escapes a script payload in an order item sku on the invoice', function () {
    $admin = User::factory()->admin()->create();

    $order = Order::factory()->create();
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'item_name' => 'Widget',
        'sku' => '<script>alert("xss")</script>',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.orders.invoice', $order));

    $response->assertOk()
        ->assertDontSee('<script>alert("xss")</script>', false)
        ->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', false);
});

it('still prints the sku as a styled small line when it is a normal value', function () {
    $admin = User::factory()->admin()->create();

    $order = Order::factory()->create();
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'item_name' => 'Widget',
        'sku' => 'SKU-123',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.orders.invoice', $order))
        ->assertOk()
        ->assertSee('<small style="color:#6b7280;">SKU-123</small>', false);
});
