<?php

use App\Livewire\Admin\DeveloperGuide;
use App\Livewire\Admin\ThemeSettings\Index as ThemeSettingsScreen;
use App\Models\MenuItem;
use App\Models\Setting;
use App\Models\User;
use App\Support\Themes;
use App\Support\ThemeSettings;
use Database\Seeders\AdminMenuSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

afterEach(function () {
    // Never leave a folder the install tests created on the real filesystem.
    File::deleteDirectory(Themes::path().'/retro');
    File::deleteDirectory(Themes::path().'/foo');
    File::deleteDirectory(Themes::path().'/bar');
    File::deleteDirectory(Themes::path().'/my-cool-store');

    // Undo onlyTheseThemesInstalled() — the real folder list is what later tests
    // and the rest of the suite expect to find.
    Themes::forget();
});

/**
 * Builds a real zip whose entries live under a single top-level folder ($slug),
 * mirroring what the installer expects. Returns the zip's file path.
 *
 * @param  array<string, string>  $files
 */
function makeThemeZip(string $slug, array $files): string
{
    $path = tempnam(sys_get_temp_dir(), 'theme-zip-').'.zip';

    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    foreach ($files as $file => $content) {
        $zip->addFromString($slug.'/'.$file, $content);
    }

    $zip->close();

    return $path;
}

/**
 * Makes Themes::all() report exactly $themes, by priming the day-long scan cache
 * it reads and clearing the per-request memo above it.
 *
 * The point is to reach the "only one theme is installed" branch without moving
 * the repository's own theme folders aside for the length of a test. afterEach
 * calls Themes::forget(), which puts the real list back.
 *
 * @param  array<string, string>  $themes
 */
function onlyTheseThemesInstalled(array $themes): void
{
    Themes::forget();
    Cache::put('themes:all', $themes, 86400);
}

/**
 * Installs a throwaway theme folder named retro through the real installer, so
 * the tests below act on a theme that is on disk with a theme.json and the whole
 * file layout behind it. afterEach deletes the folder.
 */
function installRetroTheme(): void
{
    Livewire::test(ThemeSettingsScreen::class)
        ->call('openInstallModal')
        ->set('themeZip', UploadedFile::fake()->createWithContent('retro.zip', file_get_contents(makeThemeZip('retro', [
            'home.blade.php' => 'retro home',
            'routes/web.php' => '<?php // retro routes',
            'public/css/theme.css' => '@import "../../../../resources/css/base.css";',
        ]))))
        ->set('installPassword', 'password')
        ->call('installTheme');

    expect(is_dir(Themes::path().'/retro'))->toBeTrue();
}

/**
 * Drops keys from a theme's real theme.json, so a test can assert on a field the
 * shipped file happens to carry a value for.
 *
 * Written by hand rather than through ThemeSettings::merge() because merging can
 * only add or overwrite — nothing else makes a key absent, and absent-from-the-file
 * is the state the two tests below are actually about. Pest's own snapshot
 * (tests/Pest.php) puts the file back afterwards, so the repository's theme
 * folders are left as they were found.
 *
 * @param  array<int, string>  $keys
 */
function forgetThemeJsonKeys(string $slug, array $keys): void
{
    $file = ThemeSettings::file($slug);

    $values = json_decode((string) File::get($file), true);

    foreach ($keys as $key) {
        unset($values[$key]);
    }

    File::put(
        $file,
        json_encode((object) $values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n"
    );

    ThemeSettings::forget();
}

it('renders the theme settings page', function () {
    Livewire::test(ThemeSettingsScreen::class)
        ->assertStatus(200)
        ->assertSee('Enable Live Chat')
        ->assertSee('Show Announcement Popup');
});

it('links the theme builder guide PDF in the install modal', function () {
    expect(is_file(public_path('docs/theme-builder-guide.pdf')))->toBeTrue();

    Livewire::test(ThemeSettingsScreen::class)
        ->call('openInstallModal')
        ->assertSet('showInstallModal', true)
        ->assertSeeHtml('docs/theme-builder-guide.pdf');
});

it('supplies each theme manifest (name, version, author, tags) to the picker', function () {
    Livewire::test(ThemeSettingsScreen::class)
        ->assertViewHas('themeCards', function (array $cards): bool {
            $ecommerce = $cards['ecommerce'];

            return $ecommerce['manifest']['name'] === 'Ecommerce'
                && $ecommerce['manifest']['version'] === '1.0.0'
                && $ecommerce['manifest']['author'] === 'Codeware'
                && is_array($ecommerce['manifest']['tags']);
        });
});

it('falls back to slug-derived manifest fields when a theme has no theme.json', function () {
    // 'default' ships a theme.json; but Themes::manifest() with a slug pointing
    // at a folder without one (e.g. the freshly installed test theme in the
    // other tests) must still yield a well-formed manifest.
    $zip = makeThemeZip('retro', ['home.blade.php' => 'retro home']);

    Livewire::test(ThemeSettingsScreen::class)
        ->call('openInstallModal')
        ->set('themeZip', UploadedFile::fake()->createWithContent('retro.zip', file_get_contents($zip)))
        ->set('installPassword', 'password')
        ->call('installTheme')
        ->assertHasNoErrors();

    expect(Themes::manifest('retro'))
        ->name->toBe('Retro')
        ->version->toBe('1.0.0')
        ->author->toBe('')
        ->tags->toBe([]);
});

it('treats a malformed theme.json as if it were missing', function () {
    $zip = makeThemeZip('retro', [
        'home.blade.php' => 'retro home',
        'theme.json' => '{ not valid json ;;',
    ]);

    Livewire::test(ThemeSettingsScreen::class)
        ->call('openInstallModal')
        ->set('themeZip', UploadedFile::fake()->createWithContent('retro.zip', file_get_contents($zip)))
        ->set('installPassword', 'password')
        ->call('installTheme')
        ->assertHasNoErrors();

    expect(Themes::manifest('retro'))
        ->name->toBe('Retro')
        ->version->toBe('1.0.0');
});

it('renders the selected theme settings panel below the picker', function () {
    Livewire::test(ThemeSettingsScreen::class)
        ->set('settings.site_theme', 'ecommerce')
        ->assertSeeHtml('Ecommerce')
        ->assertSeeHtml('About Ecommerce');
});

it('loads theme-scoped values out of the theme\'s own theme.json', function () {
    // A theme's settings live in its own theme.json, not the settings table,
    // so this is written straight to the file. A DB row under the same key would
    // prove nothing — nothing on the save path reads one.
    ThemeSettings::merge('ecommerce', ['theme_ecommerce_accent_color' => '#c01616']);

    $component = Livewire::test(ThemeSettingsScreen::class);

    expect($component->get('settings.theme_ecommerce_accent_color'))->toBe('#c01616');
});

it('loads a blank value for a declared field the file has never had a key for', function () {
    ThemeSettings::merge('ecommerce', ['theme_ecommerce_accent_color' => '#c01616']);

    // header_bg_color is declared by ecommerce/settings.blade.php but absent from
    // the file, so it has to come up blank rather than missing from the bag.
    // Taken out of the file first: the shipped file does carry a colour for it,
    // and the state worth pinning down is the absent one, not whatever the
    // repository happens to be holding today.
    forgetThemeJsonKeys('ecommerce', ['theme_ecommerce_header_bg_color']);

    $component = Livewire::test(ThemeSettingsScreen::class);

    expect($component->get('settings.theme_ecommerce_header_bg_color'))->toBe('');
});

it('saves each theme\'s values into that theme\'s own theme.json', function () {
    Livewire::test(ThemeSettingsScreen::class)
        ->set('settings.theme_ecommerce_accent_color', '#c01616')
        ->set('settings.theme_portfolio_hero_title', 'Designer')
        ->call('save');

    expect(ThemeSettings::text('ecommerce', 'theme_ecommerce_accent_color'))->toBe('#c01616')
        ->and(ThemeSettings::text('portfolio', 'theme_portfolio_hero_title'))->toBe('Designer')
        // And nowhere near the other theme's file, nor the settings table.
        ->and(ThemeSettings::text('portfolio', 'theme_ecommerce_accent_color'))->toBe('')
        ->and(ThemeSettings::text('ecommerce', 'theme_portfolio_hero_title'))->toBe('')
        ->and(Setting::where('key', 'like', 'theme\_%')->count())->toBe(0);
});

it('stores repeater rows in the theme\'s theme.json as a JSON list', function () {
    // An empty list to start from, rather than whatever the file happens to
    // hold: this file belongs to whoever installed the theme, and a test that
    // only passed while it was empty would be testing the repository's copy.
    ThemeSettings::merge('portfolio', ['theme_portfolio_projects' => []]);

    Livewire::test(ThemeSettingsScreen::class)
        ->set('settings.site_theme', 'portfolio')
        ->call('addRepeaterRow', 'theme_portfolio_projects', ['title', 'description'])
        ->set('repeaters.theme_portfolio_projects.0.title', 'Laravel 12 migration tool')
        ->set('repeaters.theme_portfolio_projects.0.description', 'Rewrites schema files.')
        // A blank row is what the repeater UI always leaves on screen; it must
        // not become an empty project card on the public page.
        ->call('addRepeaterRow', 'theme_portfolio_projects', ['title', 'description'])
        ->call('save');

    expect(ThemeSettings::rows('portfolio', 'theme_portfolio_projects'))
        ->toBe([['title' => 'Laravel 12 migration tool', 'description' => 'Rewrites schema files.']]);

    // A real array in the file, not a JSON string inside one.
    expect(json_decode((string) file_get_contents(ThemeSettings::file('portfolio')), true)['theme_portfolio_projects'])
        ->toBeArray()
        ->not->toBeString();
});

it('warns instead of writing when a theme\'s theme.json is missing', function () {
    ThemeSettings::delete('ecommerce');

    $component = Livewire::test(ThemeSettingsScreen::class)
        ->set('settings.site_theme', 'ecommerce')
        // The card says which file is missing and puts the button next to it, so
        // a save that cannot write is explained where the fields are, not only
        // in a flash that goes away on reload.
        ->assertSee('This theme has no theme.json file.')
        ->assertSee(ThemeSettings::file('ecommerce'), escape: false)
        ->assertSee('Create theme.json')
        ->set('settings.theme_ecommerce_accent_color', '#c01616')
        ->call('save');

    // Nothing was invented, and nothing fell back to the settings table either.
    expect(ThemeSettings::exists('ecommerce'))->toBeFalse()
        ->and(Setting::where('key', 'theme_ecommerce_accent_color')->exists())->toBeFalse()
        ->and($component->html())->toContain('Create theme.json');
});

it('leaves keys the settings screen does not declare alone when saving', function () {
    // A theme.json is a theme's own file, and a theme author may well put
    // something in it the admin form knows nothing about. Merging the form's
    // values in has to leave that untouched — a save is not a rewrite.
    ThemeSettings::merge('ecommerce', ['theme_ecommerce_author_note' => 'Hand-edited.']);

    Livewire::test(ThemeSettingsScreen::class)
        ->set('settings.theme_ecommerce_accent_color', '#c01616')
        ->call('save');

    expect(ThemeSettings::text('ecommerce', 'theme_ecommerce_author_note'))->toBe('Hand-edited.')
        ->and(ThemeSettings::text('ecommerce', 'theme_ecommerce_accent_color'))->toBe('#c01616');
});

it('creates the selected theme\'s theme.json seeded with every field it declares', function () {
    ThemeSettings::delete('ecommerce');

    Livewire::test(ThemeSettingsScreen::class)
        ->set('settings.site_theme', 'ecommerce')
        ->assertSee('Create theme.json')
        ->call('createSettingsFile')
        ->assertHasNoErrors();

    $written = json_decode((string) file_get_contents(ThemeSettings::file('ecommerce')), true);

    // The point of the button: the theme's fields have somewhere to go again.
    expect(ThemeSettings::exists('ecommerce'))->toBeTrue()
        // A readable statement of what this theme can be configured with, not a
        // bare {} — the file is also what a theme author reads to find the keys.
        ->and($written)->toHaveKey('theme_ecommerce_accent_color', '')
        ->and($written)->toHaveKey('theme_ecommerce_promo_1_link', '')
        ->and(array_values($written))->not->toContain(null);

    // And the form now saves into it.
    Livewire::test(ThemeSettingsScreen::class)
        ->set('settings.site_theme', 'ecommerce')
        ->set('settings.theme_ecommerce_accent_color', '#c01616')
        ->call('save');

    expect(ThemeSettings::text('ecommerce', 'theme_ecommerce_accent_color'))->toBe('#c01616');
});

it('seeds an empty list for each repeater a theme declares', function () {
    ThemeSettings::delete('portfolio');

    Livewire::test(ThemeSettingsScreen::class)
        ->set('settings.site_theme', 'portfolio')
        ->call('createSettingsFile');

    $written = json_decode((string) file_get_contents(ThemeSettings::file('portfolio')), true);

    expect($written)->toHaveKey('theme_portfolio_projects', [])
        ->and($written)->toHaveKey('theme_portfolio_stats', [])
        ->and($written)->toHaveKey('theme_portfolio_name', '');
});

it('refuses to create a theme.json that is already there', function () {
    ThemeSettings::merge('ecommerce', ['theme_ecommerce_accent_color' => '#c01616']);

    // The button is not even offered for a theme that has its file, and calling
    // the action anyway is a no-op: this is the recovery path for a missing file,
    // not a reset, and a create that overwrote would be one click from wiping a
    // finished theme's content.
    Livewire::test(ThemeSettingsScreen::class)
        ->set('settings.site_theme', 'ecommerce')
        ->assertDontSee('Create theme.json')
        ->call('createSettingsFile')
        ->assertSet('settings.theme_ecommerce_accent_color', '#c01616');

    expect(ThemeSettings::text('ecommerce', 'theme_ecommerce_accent_color'))->toBe('#c01616');
});

it('offers no theme.json button for a theme that declares no settings form', function () {
    // A theme with no settings.blade.php has no fields to write, and its settings
    // card is not rendered at all — so there is nothing to create a file for. The
    // file the installer gave it holds its identity and nothing else.
    $zip = makeThemeZip('retro', ['home.blade.php' => 'retro home']);

    Livewire::test(ThemeSettingsScreen::class)
        ->call('openInstallModal')
        ->set('themeZip', UploadedFile::fake()->createWithContent('retro.zip', file_get_contents($zip)))
        ->set('installPassword', 'password')
        ->call('installTheme')
        ->set('settings.site_theme', 'retro');

    Livewire::test(ThemeSettingsScreen::class)
        ->set('settings.site_theme', 'retro')
        ->assertDontSee('Create theme.json')
        ->assertDontSee('Theme Settings</h');

    // And calling the action anyway does not overwrite it: it is reachable
    // directly by anything that can talk to the Livewire endpoint, and create()
    // refuses a file that is already there.
    Livewire::test(ThemeSettingsScreen::class)
        ->set('settings.site_theme', 'retro')
        ->call('createSettingsFile')
        ->assertHasNoErrors();

    expect(ThemeSettings::exists('retro'))->toBeTrue()
        ->and(ThemeSettings::settings('retro'))->toBe([]);
});

it('does not create a theme.json for a theme that is not installed', function () {
    Livewire::test(ThemeSettingsScreen::class)
        ->set('settings.site_theme', 'not-installed')
        ->call('createSettingsFile')
        ->assertHasNoErrors();

    // The pick falls back to the live theme, so the action acted on that — and
    // found its file already there, rather than inventing one for a slug that
    // has no folder on disk.
    expect(ThemeSettings::exists('default'))->toBeTrue()
        ->and(ThemeSettings::file('not-installed'))->not->toBeFalse()
        ->and(ThemeSettings::exists('not-installed'))->toBeFalse();
});

it('renders a color picker for every storefront area', function () {
    $component = Livewire::test(ThemeSettingsScreen::class)
        ->set('settings.site_theme', 'ecommerce')
        ->assertSeeHtml('type="color"')
        ->assertDontSee('Primary Color')
        ->assertDontSee('Secondary Color');

    foreach (['header_bg', 'header_text', 'nav_bg', 'nav_text', 'footer_bg', 'footer_text', 'footer_bottom',
        'button_bg', 'button_text', 'heading', 'text', 'price', 'accent', 'sale', 'page_bg'] as $area) {
        $component->assertSeeHtml("theme_ecommerce_{$area}_color");
    }
});

it('saves brand-new per-area colors even before they are in the file', function () {
    // Both keys start out absent, which is the whole subject of the test: the
    // screen has to offer a field the file has never heard of. Removed here
    // rather than assumed, since the shipped file now carries values for them.
    forgetThemeJsonKeys('ecommerce', ['theme_ecommerce_header_bg_color', 'theme_ecommerce_button_bg_color']);

    Livewire::test(ThemeSettingsScreen::class)
        ->assertSet('settings.theme_ecommerce_header_bg_color', '')
        ->set('settings.theme_ecommerce_header_bg_color', '#112233')
        ->set('settings.theme_ecommerce_button_bg_color', '#c01616')
        ->call('save');

    expect(ThemeSettings::text('ecommerce', 'theme_ecommerce_header_bg_color'))->toBe('#112233')
        ->and(ThemeSettings::text('ecommerce', 'theme_ecommerce_button_bg_color'))->toBe('#c01616');
});

it('applies the per-area colors to the storefront and ignores anything that is not a hex color', function () {
    Setting::set('site_theme', 'ecommerce');
    ThemeSettings::merge('ecommerce', [
        'theme_ecommerce_primary_color' => '#045b30',
        'theme_ecommerce_header_bg_color' => '#112233',
        'theme_ecommerce_footer_text_color' => '#eeeeee',
        'theme_ecommerce_button_bg_color' => 'red; } body { display:none',
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('--color-sf-header: #112233;', false)
        ->assertSee('--color-sf-footer-text: #eeeeee;', false)
        // The accent falls back to the legacy primary color.
        ->assertSee('--color-brand: #045b30;', false)
        ->assertDontSee('red; } body', false)
        ->assertDontSee('--color-sf-button:', false);
});

it('loads the ecommerce theme colors the file holds into the form', function () {
    ThemeSettings::merge('ecommerce', [
        'theme_ecommerce_primary_color' => '#045b30',
        'theme_ecommerce_secondary_color' => '#7cc242',
    ]);

    $component = Livewire::test(ThemeSettingsScreen::class);

    // The form only shows what ecommerce/settings.blade.php declares, and the
    // legacy primary is in there (the colour preview falls back to it) while the
    // secondary is not — it is read on the storefront, not edited here. Both
    // values still survive a save, because a save merges rather than rewrites.
    expect($component->get('settings.theme_ecommerce_primary_color'))->toBe('#045b30')
        ->and(ThemeSettings::text('ecommerce', 'theme_ecommerce_secondary_color'))->toBe('#7cc242');
});

it('renders the selected theme settings blade when the theme ships one', function () {
    expect(Themes::hasSettings('ecommerce'))->toBeTrue()
        ->and(Themes::hasSettings('default'))->toBeTrue()
        ->and(Themes::hasSettings('portfolio'))->toBeTrue();

    Livewire::test(ThemeSettingsScreen::class)
        ->set('settings.site_theme', 'ecommerce')
        // The ecommerce card holds the homepage banner uploads + its colors only.
        ->assertSee('Banners')
        ->assertSee("tab === 'colors'", false)
        ->assertSee('heroSlides.0')
        ->assertSee('Add slide')
        ->assertSee('settings.home_promo_banner_2')
        ->assertSee('theme_ecommerce_header_bg_color')
        ->assertSee('theme_ecommerce_button_bg_color')
        ->assertSee('theme_ecommerce_footer_bg_color')
        ->assertDontSee('Hero Badge')
        ->assertDontSee('Promo Banner 1 Title');
});

it('navigates a theme\'s settings sections by menu beside the form, not a tab strip above it', function () {
    // A strip at the top of a form this long scrolls out of reach before you
    // have read half of it; the menu holds its place in the left column. Both
    // directions are asserted because the two are interchangeable in the markup
    // — only one of them is the layout this screen is supposed to have.
    $expectMenu = function (string $slug, int $sections) {
        $html = Livewire::test(ThemeSettingsScreen::class)
            ->set('settings.site_theme', $slug)
            ->html();

        // Exactly one *vertical* tablist, one entry per section, each wired to
        // the same state the panels read, and it sits in the sticky sidebar
        // column. Scoped to the vertical orientation on purpose: the only other
        // tablist on the page is gone, but counting role="tab" unscoped would
        // quietly pass again if one were ever reintroduced for a different job.
        expect(substr_count($html, 'aria-orientation="vertical"'))->toBe(1)
            ->and(substr_count($html, "open('"))->toBe($sections)
            ->and($html)->toContain('lg:grid-cols-[15rem_minmax(0,1fr)]')
            ->and($html)->toContain('lg:sticky lg:top-14 lg:self-start');

        expect(substr_count($html, 'role="tablist" aria-orientation="vertical"'))->toBe(1);

        // The underline strip is gone: it styled itself with a bottom border
        // hung off the tablist.
        expect($html)->not->toContain('border-b-2 px-4 py-2.5');
    };

    // The portfolio theme's nine sections; the count is asserted because a
    // section added to the theme without a nav entry is a section the owner
    // cannot reach. Education and Certifications used to share one "Credentials"
    // entry and are two now, which is what took this from eight to nine.
    $expectMenu('portfolio', 9);
    $expectMenu('ecommerce', 3);
});

it('picks the theme from the Site Design grid, open on arrival and on the live theme', function () {
    // The switcher tab strip is gone — the Site Design cards are the one
    // selector — so that card has to be open on arrival, and every installed
    // theme needs a radio bound to the setting the storefront reads. `default`
    // counts: a theme shipping no settings form of its own still has to be
    // reachable.
    Setting::set('site_theme', 'portfolio');

    $html = Livewire::test(ThemeSettingsScreen::class)->html();

    expect($html)->toContain('x-data="{ open: true }"');

    foreach (['default', 'ecommerce', 'portfolio'] as $slug) {
        expect($html)->toContain('value="'.$slug.'"');
    }

    expect(substr_count($html, 'wire:model.live="settings.site_theme"'))->toBe(3)
        ->and($html)->not->toContain('aria-label="Theme"');
});

it('moves the selection when a theme card is picked', function () {
    // The card is the selector, so a pick has to reach the same setting the
    // storefront reads — otherwise the marked card, the live badge and the form
    // underneath would drift apart.
    Setting::set('site_theme', 'portfolio');

    Livewire::test(ThemeSettingsScreen::class)
        ->call('$set', 'settings.site_theme', 'ecommerce')
        ->assertSet('settings.site_theme', 'ecommerce');

    $html = Livewire::test(ThemeSettingsScreen::class)
        ->set('settings.site_theme', 'ecommerce')
        ->html();

    // The ecommerce settings form is what came up.
    expect($html)->toContain('theme_ecommerce_accent_color')
        ->and($html)->not->toContain('theme_portfolio_projects');

    // One card action menu per theme, so every card carries its own download.
    expect(substr_count($html, 'Theme actions'))->toBe(3)
        // And the picked card is told apart by its border treatment rather than a
        // tick overlay — nothing on a card re-shows the site_theme Alpine value.
        ->and(substr_count($html, 'x-show="$wire.settings.site_theme'))->toBe(0);
});

it('keys each theme settings panel by slug so switching themes cannot leave a stale Alpine scope', function () {
    // Regression. All three theme settings partials are swapped into one slot by
    // a plain @include, and portfolio and ecommerce both rooted themselves in an
    // unkeyed <div x-data="{ tab, open }"> — same tag, same shape. Livewire
    // therefore morphed one into the other in place instead of replacing it, and
    // Alpine kept whichever scope it parsed first. Arriving from portfolio left
    // `tab` at 'profile' against ecommerce's 'banners' / 'colors' panels, so
    // Theme Settings came up looking empty, and clicking Colors then appeared to
    // work only because the stale open() happened to set the same property —
    // which is why Banners stayed hidden and the bug read as two faults.
    //
    // A per-slug wire:key makes the node unique, so Livewire is forced to
    // replace it and Alpine re-initialises with the incoming theme's scope.
    foreach (['default', 'portfolio', 'ecommerce'] as $slug) {
        $html = Livewire::test(ThemeSettingsScreen::class)
            ->set('settings.site_theme', $slug)
            ->html();

        expect($html)->toContain('wire:key="theme-settings-'.$slug.'"');
    }

    // The key has to be the only thing telling the two nodes apart, and it has to
    // vary by slug: an unkeyed root, or a constant key, collapses the two scopes
    // back into one and the bug returns just as quietly.
    $portfolio = Livewire::test(ThemeSettingsScreen::class)
        ->set('settings.site_theme', 'portfolio')
        ->html();

    $ecommerce = Livewire::test(ThemeSettingsScreen::class)
        ->set('settings.site_theme', 'ecommerce')
        ->html();

    expect($portfolio)->toContain("localStorage.getItem('theme-portfolio-tab')")
        ->and($ecommerce)->toContain("localStorage.getItem('theme-ecommerce-tab')")
        ->and($portfolio)->not->toContain('wire:key="theme-settings-ecommerce"')
        ->and($ecommerce)->not->toContain('wire:key="theme-settings-portfolio"');
});

it('sends the theme settings info icon into the guide theme settings block', function () {
    $html = Livewire::test(ThemeSettingsScreen::class)
        ->set('settings.site_theme', 'ecommerce')
        ->html();

    expect($html)
        ->toContain(route('admin.developer-guide').'#theme-settings')
        ->toContain('How theme settings work and are declared — open the Developer Guide')
        // The modal that restated the guide is gone, and with it the duplicated
        // copy of how theme settings work. If either string comes back, someone
        // has rebuilt the second source of truth this link exists to avoid.
        ->not->toContain('showThemeGuide')
        ->not->toContain('Theme Settings Guide');
});

it('sends the site design info icon to the guide theme creation checklist', function () {
    // Site Design asks "how do I add a theme" and Theme Settings asks "how do
    // theme settings work" - two different questions, so they must not both
    // land on the same spot.
    $html = Livewire::test(ThemeSettingsScreen::class)
        ->set('settings.site_theme', 'ecommerce')
        ->html();

    expect($html)
        ->toContain(route('admin.developer-guide').'#create-a-theme')
        ->toContain('How to create a theme — open the Developer Guide');
});

it('points every guide deep link at an anchor the guide actually renders', function () {
    $admin = User::factory()->admin()->create();

    $guide = Livewire::actingAs($admin)->test(DeveloperGuide::class)->html();
    $settings = Livewire::actingAs($admin)->test(ThemeSettingsScreen::class)
        ->set('settings.site_theme', 'ecommerce')
        ->html();

    // Collect every fragment the theme settings screen links into the guide with,
    // then check each one resolves to a real id on the guide page. A renamed
    // section would otherwise leave a link that lands at the top of the page.
    preg_match_all(
        '/'.preg_quote(route('admin.developer-guide'), '/').'#([\w-]+)/',
        $settings,
        $links
    );

    expect($links[1])->toContain('create-a-theme', 'theme-settings');

    foreach ($links[1] as $fragment) {
        expect($guide)->toContain('id="'.$fragment.'"');
    }
});

it('orders the two theme settings destinations so neither lands inside the other', function () {
    $guide = Livewire::actingAs(User::factory()->admin()->create())
        ->test(DeveloperGuide::class)
        ->html();

    // Both anchors sit inside the same Themes section, so "the settings icon
    // opens the settings part" is only true while the settings block starts
    // before the creation checklist ends.
    $settings = strpos($guide, 'id="theme-settings"');
    $create = strpos($guide, 'id="create-a-theme"');

    expect($settings)->not->toBeFalse()
        ->and($create)->not->toBeFalse()
        ->and($settings)->toBeLessThan($create);
});

it('omits the theme settings card when the selected theme has none', function () {
    $zip = makeThemeZip('retro', ['home.blade.php' => 'retro home']);

    Livewire::test(ThemeSettingsScreen::class)
        ->call('openInstallModal')
        ->set('themeZip', UploadedFile::fake()->createWithContent('retro.zip', file_get_contents($zip)))
        ->set('installPassword', 'password')
        ->call('installTheme')
        ->assertHasNoErrors();

    expect(Themes::hasSettings('retro'))->toBeFalse();

    Livewire::test(ThemeSettingsScreen::class)
        ->set('settings.site_theme', 'retro')
        ->assertDontSee('Theme Settings</h')
        ->assertDontSee('theme_retro_');
});

it('is reachable by admins via its own route', function () {
    $this->get(route('admin.theme-settings'))->assertOk();
});

it('loads existing theme settings into the form', function () {
    Setting::factory()->create(['key' => 'site_theme', 'value' => 'ecommerce', 'group' => 'frontend', 'type' => 'select']);
    Setting::factory()->create(['key' => 'site_tagline', 'value' => 'Shop smart', 'group' => 'frontend', 'type' => 'textarea']);
    Setting::factory()->create(['key' => 'chat_widget_enabled', 'value' => '0', 'group' => 'frontend', 'type' => 'boolean']);
    Setting::factory()->create(['key' => 'popup_enabled', 'value' => '1', 'group' => 'frontend', 'type' => 'boolean']);
    Setting::factory()->create(['key' => 'popup_title', 'value' => 'Welcome', 'group' => 'frontend', 'type' => 'string']);

    $component = Livewire::test(ThemeSettingsScreen::class);

    expect($component->get('settings.site_theme'))->toBe('ecommerce')
        ->and($component->get('settings.site_tagline'))->toBe('Shop smart')
        ->and($component->get('settings.chat_widget_enabled'))->toBe(false)
        ->and($component->get('settings.popup_enabled'))->toBe(true)
        ->and($component->get('settings.popup_title'))->toBe('Welcome');
});

it('manages multiple hero slides and mirrors the first image into home_hero_image', function () {
    // An install from before the slider: its single hero image becomes slide 1.
    Setting::factory()->create(['key' => 'home_hero_image', 'value' => 'media/old.jpg', 'group' => 'frontend', 'type' => 'string']);

    $slide = fn (string $image, string $title = '', string $description = '', string $link = '') => compact('image', 'title', 'description', 'link');

    $component = Livewire::test(ThemeSettingsScreen::class)
        ->assertSet('heroSlides', [$slide('media/old.jpg')])
        ->call('addHeroSlide')
        ->set('heroSlides.1.image', 'media/two.jpg')
        ->set('heroSlides.1.title', 'Fresh tea')
        ->set('heroSlides.1.description', 'Hand-picked leaves.')
        ->set('heroSlides.1.link', '/shop?category=tea')
        ->call('addHeroSlide')
        ->set('heroSlides.2.image', 'media/three.jpg')
        ->call('removeHeroSlide', 0)
        ->assertSet('heroSlides', [
            $slide('media/two.jpg', 'Fresh tea', 'Hand-picked leaves.', '/shop?category=tea'),
            $slide('media/three.jpg'),
        ]);

    foreach (range(1, 10) as $_) {
        $component->call('addHeroSlide');
    }
    expect($component->get('heroSlides'))->toHaveCount(ThemeSettingsScreen::MAX_HERO_SLIDES);

    // The blank slides (no image) are dropped on save.
    $component->call('save');

    $saved = [
        $slide('media/two.jpg', 'Fresh tea', 'Hand-picked leaves.', '/shop?category=tea'),
        $slide('media/three.jpg'),
    ];
    expect(json_decode(Setting::where('key', 'home_hero_slides')->value('value'), true))->toBe($saved)
        ->and(Setting::where('key', 'home_hero_image')->value('value'))->toBe('media/two.jpg');

    Livewire::test(ThemeSettingsScreen::class)->assertSet('heroSlides', $saved);
});

it('still reads hero slides saved as plain image URLs', function () {
    Setting::set('home_hero_slides', json_encode(['media/a.jpg', 'media/b.jpg']));

    Livewire::test(ThemeSettingsScreen::class)
        ->assertSet('heroSlides.0.image', 'media/a.jpg')
        ->assertSet('heroSlides.1.image', 'media/b.jpg')
        ->assertSet('heroSlides.1.title', '');
});

it('renders the homepage hero as a slider with each slide\'s title, description and link', function () {
    Setting::set('site_theme', 'ecommerce');
    Setting::set('home_hero_slides', json_encode([
        ['image' => '/storage/a.jpg', 'title' => 'Fresh organic tea', 'description' => 'Hand-picked leaves.', 'link' => '/shop?category=tea'],
        ['image' => '/storage/b.jpg', 'title' => '', 'description' => '', 'link' => 'javascript:alert(1)'],
    ]));

    $this->get('/')
        ->assertOk()
        ->assertSee('/storage/a.jpg')
        ->assertSee('/storage/b.jpg')
        ->assertSee('Fresh organic tea')
        ->assertSee('Hand-picked leaves.')
        ->assertSee('Next slide')
        // The banner links to the active slide; the first one is in the markup.
        ->assertSee('href="/shop?category=tea"', false)
        // An unsafe link never reaches the page — that slide opens the shop.
        ->assertDontSee('javascript:alert(1)', false);

    Setting::set('home_hero_slides', json_encode([['image' => '/storage/a.jpg']]));

    $this->get('/')->assertOk()->assertSee('/storage/a.jpg')->assertDontSee('Next slide');
});

it('links each promo banner to its own URL, falling back to the shop', function () {
    Livewire::test(ThemeSettingsScreen::class)
        ->set('settings.site_theme', 'ecommerce')
        // The tiles' own labels, not the heading that used to sit above them:
        // a panel heading is decoration, and asserting on it made a cosmetic
        // change look like a broken screen.
        ->assertSee('New arrivals')
        ->assertSee('Best deals')
        ->assertSee('settings.theme_ecommerce_promo_2_link', false)
        ->set('settings.theme_ecommerce_promo_1_link', '/shop?sort=newest')
        ->set('settings.theme_ecommerce_promo_2_link', 'javascript:alert(1)')
        ->call('save');

    expect(ThemeSettings::text('ecommerce', 'theme_ecommerce_promo_1_link'))->toBe('/shop?sort=newest');

    $this->get('/')
        ->assertOk()
        ->assertSee('href="/shop?sort=newest"', false)
        // An unsafe link never reaches the page — that tile opens the shop.
        ->assertDontSee('javascript:alert(1)', false)
        ->assertSee('href="'.route('shop').'" class="group/promo', false);
});

it('lists every installed theme folder as a selectable design', function () {
    Livewire::test(ThemeSettingsScreen::class)
        ->assertViewHas('themes', fn ($themes) => collect(['default', 'ecommerce', 'portfolio'])->diff(array_keys($themes))->isEmpty());
});

it('saves the site-wide settings through Setting::set', function () {
    // The other half of the split: these are the site's own settings, not a
    // theme's, so they stay in the settings table whichever theme is live.
    Setting::factory()->create(['key' => 'site_theme', 'value' => 'default', 'group' => 'frontend', 'type' => 'select']);
    Setting::factory()->create(['key' => 'home_hero_image', 'value' => '', 'group' => 'frontend', 'type' => 'string']);
    Setting::factory()->create(['key' => 'chat_widget_enabled', 'value' => '1', 'group' => 'frontend', 'type' => 'boolean']);
    Setting::factory()->create(['key' => 'popup_enabled', 'value' => '0', 'group' => 'frontend', 'type' => 'boolean']);

    Livewire::test(ThemeSettingsScreen::class)
        ->set('settings.site_theme', 'ecommerce')
        ->set('heroSlides.0.image', 'media/hero.jpg')
        ->set('settings.chat_widget_enabled', false)
        ->set('settings.popup_enabled', true)
        ->set('settings.popup_title', 'Welcome to our store')
        ->set('settings.popup_description', 'Get 10% off.')
        ->set('settings.popup_button_label', 'Shop Now')
        ->set('settings.popup_button_url', '/shop')
        ->call('save');

    expect(Setting::where('key', 'site_theme')->value('value'))->toBe('ecommerce')
        ->and(Setting::where('key', 'home_hero_image')->value('value'))->toBe('media/hero.jpg')
        ->and(Setting::where('key', 'chat_widget_enabled')->value('value'))->toBe('0')
        ->and(Setting::where('key', 'popup_enabled')->value('value'))->toBe('1')
        ->and(Setting::where('key', 'popup_title')->value('value'))->toBe('Welcome to our store')
        ->and(Setting::where('key', 'popup_description')->value('value'))->toBe('Get 10% off.')
        ->and(Setting::where('key', 'popup_button_label')->value('value'))->toBe('Shop Now')
        ->and(Setting::where('key', 'popup_button_url')->value('value'))->toBe('/shop');
});

it('registers a Theme Settings item under Library & System in the admin menu', function () {
    $this->seed(AdminMenuSeeder::class);

    $item = MenuItem::where('group', MenuItem::GROUP_ADMIN_SIDEBAR)
        ->where('route_name', 'admin.theme-settings')
        ->first();

    expect($item)->not->toBeNull()
        ->and($item->label)->toBe('Theme Settings')
        ->and($item->icon)->toBe('swatch');
});

it('opens the install theme modal', function () {
    Livewire::test(ThemeSettingsScreen::class)
        ->call('openInstallModal')
        ->assertSet('showInstallModal', true)
        ->call('closeInstallModal')
        ->assertSet('showInstallModal', false);
});

it('installs a theme from a zip into the themes directory', function () {
    $zip = makeThemeZip('retro', [
        'home.blade.php' => 'retro home',
        'page.blade.php' => 'retro page',
        'partials/_head.blade.php' => 'retro head',
    ]);

    Livewire::test(ThemeSettingsScreen::class)
        ->call('openInstallModal')
        ->set('themeZip', UploadedFile::fake()->createWithContent('retro.zip', file_get_contents($zip)))
        ->set('installPassword', 'password')
        ->call('installTheme')
        ->assertSet('showInstallModal', false)
        ->assertHasNoErrors();

    expect(is_dir(Themes::path().'/retro'))->toBeTrue()
        ->and(file_exists(Themes::path().'/retro/home.blade.php'))->toBeTrue()
        ->and(file_exists(Themes::path().'/retro/partials/_head.blade.php'))->toBeTrue()
        ->and(Themes::all())->toHaveKey('retro');
});

it('turns a spaced theme folder name into a slugged theme folder', function () {
    $zip = makeThemeZip('My Cool Store', [
        'home.blade.php' => 'cool home',
    ]);

    Livewire::test(ThemeSettingsScreen::class)
        ->call('openInstallModal')
        ->set('themeZip', UploadedFile::fake()->createWithContent('my-cool-store.zip', file_get_contents($zip)))
        ->set('installPassword', 'password')
        ->call('installTheme')
        ->assertHasNoErrors();

    expect(is_dir(Themes::path().'/my-cool-store'))->toBeTrue();
});

it('rejects a file that is not a valid zip theme package', function () {
    Livewire::test(ThemeSettingsScreen::class)
        ->call('openInstallModal')
        ->set('themeZip', UploadedFile::fake()->create('theme.zip', 256))
        ->set('installPassword', 'password')
        ->call('installTheme')
        ->assertHasErrors(['themeZip']);
});

it('rejects a zip that does not contain exactly one root theme folder', function () {
    $zip = makeThemeZip('foo', ['home.blade.php' => 'foo home']);
    $zipBar = makeThemeZip('bar', ['home.blade.php' => 'bar home']);

    $merged = tempnam(sys_get_temp_dir(), 'theme-zip-').'.zip';
    copy($zip, $merged);

    $wrap = new ZipArchive;
    $wrap->open($merged, ZipArchive::CREATE);
    foreach (['bar/home.blade.php' => 'bar home'] as $file => $content) {
        $wrap->addFromString($file, $content);
    }
    $wrap->close();

    Livewire::test(ThemeSettingsScreen::class)
        ->call('openInstallModal')
        ->set('themeZip', UploadedFile::fake()->createWithContent('two-themes.zip', file_get_contents($merged)))
        ->set('installPassword', 'password')
        ->call('installTheme')
        ->assertHasErrors(['themeZip']);
});

it('does not overwrite an already-installed theme', function () {
    $zip = makeThemeZip('default', ['home.blade.php' => 'evil home']);

    Livewire::test(ThemeSettingsScreen::class)
        ->call('openInstallModal')
        ->set('themeZip', UploadedFile::fake()->createWithContent('default.zip', file_get_contents($zip)))
        ->set('installPassword', 'password')
        ->call('installTheme')
        ->assertHasErrors(['themeZip']);
});

it('rejects a zip containing path-traversal entries', function () {
    $path = tempnam(sys_get_temp_dir(), 'theme-zip-').'.zip';

    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('retro/home.blade.php', 'fine');
    $zip->addFromString('retro/../../evil.txt', 'pwn');
    $zip->close();

    Livewire::test(ThemeSettingsScreen::class)
        ->call('openInstallModal')
        ->set('themeZip', UploadedFile::fake()->createWithContent('bad.zip', file_get_contents($path)))
        ->set('installPassword', 'password')
        ->call('installTheme')
        ->assertHasErrors(['themeZip']);

    expect(is_dir(Themes::path().'/retro'))->toBeFalse();
});

it('saves the live chat widget color', function () {
    Livewire::test(ThemeSettingsScreen::class)
        ->set('settings.chat_widget_color', ' #FF5500 ')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::where('key', 'chat_widget_color')->value('value'))->toBe('#ff5500');
});

it('allows a blank live chat widget color to fall back to the site primary color', function () {
    Setting::set('chat_widget_color', '#ff5500');

    Livewire::test(ThemeSettingsScreen::class)
        ->set('settings.chat_widget_color', '')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::where('key', 'chat_widget_color')->value('value'))->toBe('');
});

it('rejects an invalid live chat widget color', function () {
    Livewire::test(ThemeSettingsScreen::class)
        ->set('settings.chat_widget_color', 'red; background:url(x)')
        ->call('save')
        ->assertHasErrors(['settings.chat_widget_color']);

    expect(Setting::where('key', 'chat_widget_color')->value('value'))->toBeNull();
});

it('gives an installed theme a theme.json so it is never half-configured', function () {
    $zip = makeThemeZip('retro', [
        'home.blade.php' => 'retro home',
        // No theme.json in the zip: the case this exists for. A zipped theme
        // built from templates alone installs perfectly and then looks broken —
        // the form renders, every field is blank, and Save has nowhere to put
        // them, which reads as "the panel lost my settings".
        'settings.blade.php' => <<<'BLADE'
            <x-admin.text key="theme_retro_tagline" label="Tagline" />
            <x-admin-repeatable-fields setting-key="theme_retro_projects" :max="3" label="Projects">
                <x-admin.text key="title" label="Title" />
            </x-admin-repeatable-fields>
        BLADE,
    ]);

    Livewire::test(ThemeSettingsScreen::class)
        ->call('openInstallModal')
        ->set('themeZip', UploadedFile::fake()->createWithContent('retro.zip', file_get_contents($zip)))
        ->set('installPassword', 'password')
        ->call('installTheme')
        ->assertHasNoErrors();

    expect(ThemeSettings::exists('retro'))->toBeTrue()
        ->and(ThemeSettings::text('retro', 'theme_retro_tagline'))->toBe('')
        ->and(ThemeSettings::rows('retro', 'theme_retro_projects'))->toBe([])
        // Every declared field is present-and-blank, not absent: the file says
        // what this theme can be configured with, which is the same thing a
        // theme author opens it to find out. A repeater is a list, not a string
        // — seeding it as '' is what save() would then write its rows back over.
        ->and(ThemeSettings::all('retro'))->toHaveKey('theme_retro_tagline', '')
        ->and(ThemeSettings::all('retro'))->toHaveKey('theme_retro_projects', [])
        // A well-formed manifest, not a settings map wearing a manifest's name.
        ->and(Themes::manifest('retro'))->toHaveKeys(['name', 'version', 'author', 'tags']);
});

it('leaves a theme that ships its own theme.json exactly as it was', function () {
    $shipped = json_encode([
        'name' => 'Retro Studio',
        'version' => '2.1.0',
        'theme_retro_tagline' => 'Ships its own.',
    ], JSON_PRETTY_PRINT);

    $zip = makeThemeZip('retro', [
        'home.blade.php' => 'retro home',
        'settings.blade.php' => '<x-admin.text key="theme_retro_tagline" label="Tagline" />',
        'theme.json' => $shipped,
    ]);

    Livewire::test(ThemeSettingsScreen::class)
        ->call('openInstallModal')
        ->set('themeZip', UploadedFile::fake()->createWithContent('retro.zip', file_get_contents($zip)))
        ->set('installPassword', 'password')
        ->call('installTheme')
        ->assertHasNoErrors();

    // Seeding must not be a silent reset: a theme re-uploaded to be updated
    // carries the content the owner filled in, and installing it is not allowed
    // to blank that back out.
    expect(ThemeSettings::text('retro', 'theme_retro_tagline'))->toBe('Ships its own.')
        ->and(Themes::manifest('retro')['name'])->toBe('Retro Studio')
        ->and(Themes::manifest('retro')['version'])->toBe('2.1.0');
});

it('gives a theme that declares no settings form a manifest holding only its identity', function () {
    // A theme with no settings.blade.php has no fields, so the file holds no
    // settings — but theme.json is also where a theme's name and serial number
    // live, and a theme installed with neither is one the picker cannot tell from
    // another. So it gets a manifest and nothing else.
    $zip = makeThemeZip('retro', ['home.blade.php' => 'retro home']);

    Livewire::test(ThemeSettingsScreen::class)
        ->call('openInstallModal')
        ->set('themeZip', UploadedFile::fake()->createWithContent('retro.zip', file_get_contents($zip)))
        ->set('installPassword', 'password')
        ->call('installTheme')
        ->assertHasNoErrors();

    expect(ThemeSettings::exists('retro'))->toBeTrue()
        ->and(ThemeSettings::settings('retro'))->toBe([])
        ->and(Themes::manifest('retro')['name'])->toBe('Retro')
        ->and(Themes::manifest('retro')['sn'])->toBeGreaterThan(0);
});

it('flags a theme that ships no routes file, because selecting it 404s the site', function () {
    Livewire::test(ThemeSettingsScreen::class)
        ->call('openInstallModal')
        ->set('themeZip', UploadedFile::fake()->createWithContent('retro.zip', file_get_contents(makeThemeZip('retro', [
            'home.blade.php' => 'retro home',
        ]))))
        ->set('installPassword', 'password')
        ->call('installTheme');

    $card = null;

    Livewire::test(ThemeSettingsScreen::class)
        ->assertViewHas('themeCards', function (array $cards) use (&$card): bool {
            $card = $cards['retro'] ?? null;

            return is_array($card);
        });

    expect($card['hasRoutes'])->toBeFalse()
        // Reported as the path to create, not as nothing: a theme with no routes
        // file is the one thing standing between it and a live site, so the card
        // names where the file goes — beside its own templates.
        ->and(str_replace('\\', '/', $card['routeFile']))
        ->toEndWith('themes/retro/routes/web.php');

    // And the warning is actually on the screen, next to the radio that picks
    // the theme — this is the last screen before the site stops resolving, and
    // it says where the missing file goes.
    Livewire::test(ThemeSettingsScreen::class)
        ->set('settings.site_theme', 'retro')
        ->assertSee('themes/retro/routes/web.php');
});

it('does not flag a theme that ships a routes file', function () {
    expect(Themes::routeFileExists('portfolio'))->toBeTrue();

    Livewire::test(ThemeSettingsScreen::class)
        ->assertViewHas('themeCards', fn (array $cards): bool => $cards['portfolio']['hasRoutes'] === true);
});

it('downloads an installed theme as a zip holding its own folder', function () {
    installRetroTheme();

    $zipPath = Themes::toZip('retro');

    try {
        $zip = new ZipArchive;

        expect($zip->open($zipPath))->toBeTrue();

        // The same shape installTheme() accepts, so a downloaded theme goes back
        // in whole: one top-level folder named for the slug, everything under it.
        foreach ([
            'retro/home.blade.php',
            'retro/routes/web.php',
            'retro/public/css/theme.css',
            'retro/theme.json',
        ] as $entry) {
            expect($zip->locateName($entry))->not->toBeFalse();
        }

        // And nothing escapes that folder - an entry outside it would land loose
        // in themes/ on re-install.
        for ($i = 0; $i < $zip->numFiles; $i++) {
            expect(str_starts_with($zip->getNameIndex($i), 'retro/'))->toBeTrue();
        }

        $zip->close();
    } finally {
        File::delete($zipPath);
    }
});

it('serves a downloaded theme to the browser as a named zip', function () {
    installRetroTheme();

    Livewire::test(ThemeSettingsScreen::class)
        ->call('downloadTheme', 'retro')
        ->assertFileDownloaded('retro.zip')
        ->assertHasNoErrors();
});

it('refuses to download a theme that is not installed', function () {
    Livewire::test(ThemeSettingsScreen::class)
        ->call('downloadTheme', 'nope-not-installed')
        ->assertHasErrors('themes');
});

it('will not download or delete a directory outside themes/ via a crafted slug', function () {
    // `themes/../storage` is a real directory, so anything that resolves the slug
    // against themes/ without checking it names an installed theme would zip the
    // whole storage tree — or, worse, delete it. Both actions take a slug off the
    // wire, and themeToDelete is a public property that can be set without going
    // through the modal, so the guard has to be in Themes, not just the form.
    // The slug as the wire would deliver it: a fragment that walks out of
    // themes/ and back down into the project root.
    $outside = '..'.DIRECTORY_SEPARATOR.basename(Themes::path());
    $resolved = Themes::path().DIRECTORY_SEPARATOR.$outside;

    expect(is_dir($resolved))->toBeTrue();

    expect(Themes::isInstalled($outside))->toBeFalse()
        ->and(Themes::delete($outside))->toBeFalse()
        ->and(is_dir($resolved))->toBeTrue();

    try {
        Themes::toZip($outside);

        $this->fail('toZip() packed a directory outside themes/.');
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toBe('That theme is not installed.');
    }

    Livewire::test(ThemeSettingsScreen::class)
        ->call('downloadTheme', $outside)
        ->assertHasErrors('themes');

    Livewire::test(ThemeSettingsScreen::class)
        ->set('themeToDelete', $outside)
        ->call('deleteTheme')
        ->assertHasErrors('themeToDelete');

    expect(is_dir($resolved))->toBeTrue();
});

it('offers a download for every theme, including the bundled ones', function () {
    // From the three-dot menu on each card, not from a separate section: one card
    // is one theme, so the actions for it belong on it.
    Livewire::test(ThemeSettingsScreen::class)
        ->assertSee("wire:click=\"downloadTheme('default')\"", escape: false)
        ->assertSee("wire:click=\"downloadTheme('portfolio')\"", escape: false)
        ->assertSee('Theme actions', escape: false);
});

it('does not put a tick on a card when a theme is picked', function () {
    // The live theme announces itself with a glowing border instead, so a tick
    // would only have repeated a mark that is already there.
    Setting::set('site_theme', 'default');

    $html = Livewire::test(ThemeSettingsScreen::class)
        ->set('settings.site_theme', 'ecommerce')
        ->html();

    // No Alpine-driven tick overlay is left anywhere: nothing on a card reacts to
    // the site_theme value any more, it is decided server-side when rendered.
    expect($html)->not->toContain('x-show="$wire.settings.site_theme')
        // And no filled tick-circle sitting in the corner of a card.
        ->and($html)->not->toContain('rounded-full bg-primary text-white shadow')
        // The picked card still says so, in words.
        ->and($html)->toContain('Selected');
});

it('deletes a theme folder and everything in it', function () {
    Livewire::test(ThemeSettingsScreen::class)
        ->call('openInstallModal')
        ->set('themeZip', UploadedFile::fake()->createWithContent('retro.zip', file_get_contents(makeThemeZip('retro', [
            'home.blade.php' => 'retro home',
            'routes/web.php' => '<?php // retro routes',
            'public/css/theme.css' => '@import "../../../../resources/css/base.css";',
        ]))))
        ->set('installPassword', 'password')
        ->call('installTheme');

    expect(is_dir(Themes::path().'/retro'))->toBeTrue();

    Livewire::test(ThemeSettingsScreen::class)
        ->call('confirmDelete', 'retro')
        ->call('deleteTheme')
        ->assertHasNoErrors();

    // The whole folder, not just the templates: its theme.json went with it, which
    // is where its settings were stored.
    expect(is_dir(Themes::path().'/retro'))->toBeFalse();

    // And the picker stops offering it on the very next render.
    Livewire::test(ThemeSettingsScreen::class)
        ->assertViewHas('themeCards', fn (array $cards): bool => ! array_key_exists('retro', $cards));
});

it('refuses to delete the live theme', function () {
    // Three themes are installed, so this is the live-theme guard and not the
    // last-theme one — and it has to hold for a theme that is *not* the default
    // one, or the default flag would be doing the work instead.
    expect(Themes::isDefault('ecommerce'))->toBeFalse()
        ->and(count(Themes::all()))->toBeGreaterThan(1);

    Setting::set('site_theme', 'ecommerce');

    expect(Themes::undeletableBecause('ecommerce'))->toContain('live');

    Livewire::test(ThemeSettingsScreen::class)
        ->call('confirmDelete', 'ecommerce')
        ->call('deleteTheme')
        ->assertHasErrors('themeToDelete');

    expect(is_dir(Themes::path().'/ecommerce'))->toBeTrue();
});

it('protects only the default theme, whichever one is live', function () {
    // The default theme is refused while another one is live, so the refusal is
    // the manifest flag and not the live-theme guard standing in for it.
    Setting::set('site_theme', 'ecommerce');

    expect(Themes::undeletableBecause('default'))->toContain('ships with Codeware');

    // Switching away from it does not unlock it, and picking it does not change
    // what the other two are allowed to do.
    Setting::set('site_theme', 'portfolio');

    expect(Themes::undeletableBecause('default'))->toContain('ships with Codeware')
        ->and(Themes::undeletableBecause('portfolio'))->toContain('live')
        ->and(Themes::undeletableBecause('ecommerce'))->toBeNull();
});

it('lets any theme but the default one be deleted', function () {
    // Only themes/default/theme.json carries "default": true. The other two
    // bundled themes are ordinary folders as far as deletion goes — being part of
    // the release is not the same as being the one the site falls back to.
    expect(Themes::isDefault('default'))->toBeTrue()
        ->and(Themes::isDefault('ecommerce'))->toBeFalse()
        ->and(Themes::isDefault('portfolio'))->toBeFalse();

    // Something else has to be live, or the live-theme guard would be the thing
    // under test rather than the manifest flag.
    Setting::set('site_theme', 'ecommerce');

    expect(Themes::undeletableBecause('ecommerce'))->toContain('live')
        ->and(Themes::undeletableBecause('portfolio'))->toBeNull();

    // The fallback theme stays protected even once it is not the live one.
    expect(Themes::undeletableBecause('default'))->toContain('ships with Codeware');

    // And a non-default, non-live theme really does go — including its whole
    // folder tree, so the delete is checked against a snapshot rather than the
    // top-level files alone.
    $stash = tempnam(sys_get_temp_dir(), 'portfolio-');

    File::delete($stash);
    File::copyDirectory(Themes::path().'/portfolio', $stash);

    try {
        Livewire::test(ThemeSettingsScreen::class)
            ->call('confirmDelete', 'portfolio')
            ->assertSet('deleteBlockedBy', null)
            ->call('deleteTheme')
            ->assertHasNoErrors();

        expect(is_dir(Themes::path().'/portfolio'))->toBeFalse();
    } finally {
        // Put it back whole — the rest of the suite, and the repository, expect
        // this theme to be there.
        File::copyDirectory($stash, Themes::path().'/portfolio');
        File::deleteDirectory($stash);
    }

    expect(is_dir(Themes::path().'/portfolio'))->toBeTrue()
        ->and(File::exists(Themes::path().'/portfolio/routes/web.php'))->toBeTrue();
});

it('refuses to delete the last installed theme even when it is not the live one', function () {
    // A stale themes:all entry, which is a real state rather than a contrived one:
    // the folder list is cached for a day, so a theme whose folder has since
    // gone can still be listed — and active() will not resolve to it, because it
    // checks the folder is really there. Nothing but the count stops that row
    // being deleted. 'gone' is not the default theme either, so the manifest flag
    // cannot be what is refusing it.
    onlyTheseThemesInstalled(['gone' => 'Gone']);

    expect(Themes::isDefault('gone'))->toBeFalse()
        ->and(Themes::active())->not->toBe('gone')
        ->and(Themes::undeletableBecause('gone'))->toContain('only');

    Livewire::test(ThemeSettingsScreen::class)
        ->call('confirmDelete', 'gone')
        ->call('deleteTheme')
        ->assertHasErrors('themeToDelete');
});

it('explains why a theme cannot be deleted instead of only greying the button out', function () {
    Livewire::test(ThemeSettingsScreen::class)
        ->call('confirmDelete', 'default')
        ->assertSet('showDeleteModal', true)
        ->assertSet('themeToDelete', 'default')
        ->assertSee('This theme cannot be deleted')
        // The reason is on screen rather than behind a hover: the card menu quotes
        // it too, but the modal is where it is read before anything is attempted.
        ->assertSee('This theme ships with Codeware')
        // And no Delete action is reachable in that modal — the screen does not
        // offer a button the server would only refuse.
        ->assertDontSee('wire:click="deleteTheme"', escape: false);
});

it('lists a real warning of what a delete takes with it', function () {
    Livewire::test(ThemeSettingsScreen::class)
        ->call('openInstallModal')
        ->set('themeZip', UploadedFile::fake()->createWithContent('retro.zip', file_get_contents(makeThemeZip('retro', [
            'home.blade.php' => 'retro home',
        ]))))
        ->set('installPassword', 'password')
        ->call('installTheme');

    Livewire::test(ThemeSettingsScreen::class)
        ->call('confirmDelete', 'retro')
        ->assertSet('showDeleteModal', true)
        ->assertSet('deleteBlockedBy', null)
        // Not a one-line confirm: it names the folder, the settings that live in
        // the theme's own theme.json, and the migrations that stay applied.
        ->assertSee('Delete this theme?')
        ->assertSee('themes/retro/')
        ->assertSee('theme.json')
        ->assertSee('migrations it already ran stay applied');
});

it('cancels a delete without touching the folder', function () {
    Livewire::test(ThemeSettingsScreen::class)
        ->call('openInstallModal')
        ->set('themeZip', UploadedFile::fake()->createWithContent('retro.zip', file_get_contents(makeThemeZip('retro', [
            'home.blade.php' => 'retro home',
        ]))))
        ->set('installPassword', 'password')
        ->call('installTheme');

    Livewire::test(ThemeSettingsScreen::class)
        ->call('confirmDelete', 'retro')
        ->call('closeDeleteModal')
        ->assertSet('showDeleteModal', false)
        ->assertSet('themeToDelete', null)
        ->assertDontSee('Delete this theme?');

    expect(is_dir(Themes::path().'/retro'))->toBeTrue();
});
