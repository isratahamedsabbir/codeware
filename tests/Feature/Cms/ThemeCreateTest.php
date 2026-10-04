<?php

use App\Livewire\Admin\ThemeSettings\Index as ThemeSettingsScreen;
use App\Models\Setting;
use App\Models\User;
use App\Support\Themes;
use App\Support\ThemeSettings;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;

/*
|--------------------------------------------------------------------------
| Creating a theme from the admin
|--------------------------------------------------------------------------
|
| The New Theme button writes a starter theme folder rather than a single file,
| and the property that matters is that the folder is whole on its own: every
| page template, the partials they share, the theme's own 404, its stylesheet,
| its settings screen and its routes file all inside it, so it can be zipped and
| handed to someone else without anything being left behind in a second
| directory. The bundled themes still keep their route files and stylesheets
| outside their folder, which is what the fallback halves of routeFile() and
| stylesheet() are for.
|
*/

// Cleaned on both sides of every test, not just after: a folder left behind by an
// interrupted run would make every "this slug is free" assertion below fail for a
// reason that has nothing to do with the code under test.
function cleanCreatedThemes(): void
{
    File::deleteDirectory(Themes::path().'/aurora');
    File::deleteDirectory(Themes::path().'/aurora-copy');
    File::deleteDirectory(Themes::path().'/aurora-two');
    File::deleteDirectory(Themes::path().'/bare-theme');

    Themes::forget();
}

beforeEach(function () {
    cleanCreatedThemes();

    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->admin()->create());
});

afterEach(function () {
    cleanCreatedThemes();
});

function createAurora(array $overrides = []): string
{
    return Themes::create(array_merge([
        'name' => 'Aurora',
        'slug' => 'aurora',
        'version' => '1.0.0',
        'description' => 'A single-column storefront.',
        'author' => 'Codeware',
    ], $overrides));
}

it('writes a complete theme folder from the basics given on the form', function () {
    createAurora();

    $folder = Themes::path().'/aurora';

    // Every route name in ROUTE_TEMPLATES has a template, because a theme is
    // strictly self-contained: a page it ships no template for does not exist on
    // that site, so a starter theme with holes in it would be a site with holes
    // in it.
    foreach (array_values(Themes::routeTemplates()) as $template) {
        expect(is_file($folder.'/'.str_replace('.', '/', $template).'.blade.php'))
            ->toBeTrue("the \"{$template}\" template is missing");
    }

    expect(is_file($folder.'/errors/404.blade.php'))->toBeTrue()
        ->and(is_file($folder.'/partials/_header.blade.php'))->toBeTrue()
        ->and(is_file($folder.'/partials/_footer.blade.php'))->toBeTrue()
        ->and(is_file($folder.'/settings.blade.php'))->toBeTrue()
        ->and(is_file($folder.'/public/css/theme.css'))->toBeTrue()
        ->and(is_file($folder.'/routes/web.php'))->toBeTrue()
        ->and(is_file($folder.'/'.ThemeSettings::FILE))->toBeTrue();
});

it('keeps everything the new theme needs inside its own folder', function () {
    createAurora();

    $folder = Themes::path().'/aurora';

    // Its stylesheet and its route file are read from in here — the whole point
    // of the button, and what lets the folder be zipped and handed over intact.
    expect(Themes::hasStylesheet('aurora'))->toBeTrue()
        ->and(Themes::stylesheet('aurora'))->toBe($folder.'/public/css/theme.css')
        ->and(Themes::storefrontEntry('aurora'))->toBe('themes/aurora/public/css/theme.css')
        ->and(Themes::routeFileExists('aurora'))->toBeTrue()
        ->and(Themes::routeFile('aurora'))->toBe($folder.'/routes/web.php')
        ->and(Themes::allRouteFiles())->toHaveKey('aurora', $folder.'/routes/web.php');
});

it('keeps the bundled themes in the same module layout', function () {
    // The shipped themes are modules like any other: stylesheet and routes in
    // their own folder, nothing left in routes/web or resources/css/themes.
    expect(Themes::routeFile('default'))->toBe(base_path('themes/default/routes/web.php'))
        ->and(Themes::stylesheet('portfolio'))->toBe(base_path('themes/portfolio/public/css/theme.css'))
        ->and(Themes::storefrontEntry('portfolio'))->toBe('themes/portfolio/public/css/theme.css');
});

it('ships a routes file with nothing registered in it', function () {
    createAurora();

    // routes/web.php registers every theme's route file on every request, so a
    // starter file with a live route pointing at a controller nobody has written
    // yet would take the whole site down rather than just the new theme.
    $live = array_filter(
        preg_split('/\R/', (string) File::get(Themes::path().'/aurora/routes/web.php')),
        fn (string $line) => str_contains($line, 'Route::')
            && ! str_starts_with(trim($line), '//')
            && ! str_starts_with(trim($line), '*'),
    );

    expect($live)->toBe([]);
});

it('writes a theme.json carrying the values from the form and a fresh serial number', function () {
    $before = Themes::nextSn();

    createAurora();

    $manifest = Themes::manifest('aurora');

    expect($manifest['name'])->toBe('Aurora')
        ->and($manifest['description'])->toBe('A single-column storefront.')
        ->and($manifest['version'])->toBe('1.0.0')
        ->and($manifest['author'])->toBe('Codeware')
        ->and($manifest['sn'])->toBe($before)
        ->and(Themes::isSnUnique($manifest['sn']))->toBeFalse() // it is this theme's own
        ->and(ThemeSettings::exists('aurora'))->toBeTrue();
});

it('leaves the live theme alone', function () {
    Setting::set('site_theme', 'portfolio');

    createAurora();

    // Creating a theme and choosing it are two decisions: a theme that took the
    // site live the moment it was written would replace a finished design with
    // seventeen placeholders.
    expect(Setting::get('site_theme'))->toBe('portfolio')
        ->and(Themes::active())->toBe('portfolio');
});

it('lists the new theme on the picker without activating it', function () {
    createAurora();

    expect(Themes::all())->toHaveKey('aurora');

    Livewire::test(ThemeSettingsScreen::class)
        ->assertSee('aurora');
});

it('refuses a slug or a name that is already taken', function () {
    createAurora();

    // The slug: a folder of that name is already on disk.
    expect(fn () => Themes::create([
        'name' => 'Something Else',
        'slug' => 'aurora',
        'version' => '1.0.0',
        'description' => '',
        'author' => '',
    ]))->toThrow(RuntimeException::class);

    // The name: a different folder, the same display name. Theme names have to be
    // unique, and a blank name is not free either (see Themes::isNameUnique()).
    expect(fn () => Themes::create([
        'name' => 'Aurora',
        'slug' => 'aurora-two',
        'version' => '1.0.0',
        'description' => '',
        'author' => '',
    ]))->toThrow(RuntimeException::class);
});

it('refuses a slug that is not a plain folder name', function () {
    expect(fn () => Themes::create([
        'name' => 'Escape',
        'slug' => '../../config',
        'version' => '1.0.0',
        'description' => '',
        'author' => '',
    ]))->toThrow(RuntimeException::class);
});

it('renders the starter home page without a controller behind it', function () {
    createAurora();

    // Every template is a working placeholder that reads no variables, because
    // what a given controller passes is the one thing a scaffold cannot know.
    $html = View::make('theme-aurora::home')->render();

    expect($html)->toContain('<!DOCTYPE html>')
        ->and($html)->toContain('This is the "aurora" theme\'s home page');
});

it('creates the theme from the New Theme modal', function () {
    // The header button itself lives in the layout's pushed stack, which a Livewire
    // render does not emit — the modal it opens is what this screen renders.
    Livewire::test(ThemeSettingsScreen::class)
        ->call('openCreateModal')
        ->assertSee('Nothing goes live')
        ->set('newName', 'Aurora')
        ->set('newSlug', 'aurora')
        ->set('newVersion', '1.0.0')
        ->set('newDescription', 'A single-column storefront.')
        ->set('newAuthor', 'Codeware')
        ->call('createTheme')
        ->assertHasNoErrors()
        // The form is emptied and the modal closed, so a second create starts
        // from a blank name rather than from the last theme typed in.
        ->assertSet('showCreateModal', false)
        ->assertSet('newName', '');

    expect(is_file(Themes::path().'/aurora/theme.json'))->toBeTrue()
        ->and(Themes::manifest('aurora')['name'])->toBe('Aurora');
});

it('derives the slug from the name until it is edited by hand', function () {
    Livewire::test(ThemeSettingsScreen::class)
        ->call('openCreateModal')
        ->set('newName', 'My Cool Store')
        ->assertSet('newSlug', 'my-cool-store');

    // Once the owner types their own, the name stops rewriting it. The flag is set
    // by the slug field's own x-on:input in the modal, since a Livewire update
    // hook cannot tell a typed slug from a derived one.
    Livewire::test(ThemeSettingsScreen::class)
        ->call('openCreateModal')
        ->set('newSlug', 'hand-picked')
        ->set('slugEdited', true)
        ->set('newName', 'Another Name')
        ->assertSet('newSlug', 'hand-picked');
});

it('keeps a bad slug out of the filesystem and says why', function () {
    Livewire::test(ThemeSettingsScreen::class)
        ->call('openCreateModal')
        ->set('newName', 'Bad Name')
        ->set('newSlug', 'bad slug!')
        ->call('createTheme')
        ->assertHasErrors(['newSlug']);

    expect(is_dir(Themes::path().'/bad slug!'))->toBeFalse();
});

it('refuses a theme whose name an installed theme already uses, without writing a folder', function () {
    createAurora();

    Livewire::test(ThemeSettingsScreen::class)
        ->call('openCreateModal')
        ->set('newName', 'aurora') // same name, different folder — case and spacing aside
        ->set('newSlug', 'aurora-copy')
        ->call('createTheme')
        ->assertHasErrors(['newSlug']);

    expect(is_dir(Themes::path().'/aurora-copy'))->toBeFalse();
});

it('leaves nothing behind in the themes directory for a theme that ships no stylesheet', function () {
    // The catch-all: a theme with no theme.css of its own is still styled, by
    // the bundle that scans every theme. Creating one here proves the new folder
    // is not the only way a theme can be styled.
    File::ensureDirectoryExists(Themes::path().'/bare-theme');
    File::put(Themes::path().'/bare-theme/home.blade.php', 'bare');
    Themes::forget();

    expect(Themes::hasStylesheet('bare-theme'))->toBeFalse()
        ->and(Themes::storefrontEntry('bare-theme'))->toBe('resources/css/storefront.css');
});
