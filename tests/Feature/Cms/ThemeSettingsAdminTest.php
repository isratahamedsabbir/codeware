<?php

use App\Livewire\Admin\ThemeSettings\Index as ThemeSettings;
use App\Models\MenuItem;
use App\Models\Setting;
use App\Models\User;
use App\Support\Themes;
use Database\Seeders\AdminMenuSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
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

it('renders the theme settings page', function () {
    Livewire::test(ThemeSettings::class)
        ->assertStatus(200)
        ->assertSee('Enable Live Chat')
        ->assertSee('Show Announcement Popup');
});

it('links the theme builder guide PDF in the install modal', function () {
    expect(is_file(public_path('docs/theme-builder-guide.pdf')))->toBeTrue();

    Livewire::test(ThemeSettings::class)
        ->call('openInstallModal')
        ->assertSet('showInstallModal', true)
        ->assertSeeHtml('docs/theme-builder-guide.pdf');
});

it('supplies each theme manifest (name, version, author, tags) to the picker', function () {
    Livewire::test(ThemeSettings::class)
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

    Livewire::test(ThemeSettings::class)
        ->call('openInstallModal')
        ->set('themeZip', UploadedFile::fake()->createWithContent('retro.zip', file_get_contents($zip)))
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

    Livewire::test(ThemeSettings::class)
        ->call('openInstallModal')
        ->set('themeZip', UploadedFile::fake()->createWithContent('retro.zip', file_get_contents($zip)))
        ->call('installTheme')
        ->assertHasNoErrors();

    expect(Themes::manifest('retro'))
        ->name->toBe('Retro')
        ->version->toBe('1.0.0');
});

it('renders the selected theme settings panel below the picker', function () {
    Livewire::test(ThemeSettings::class)
        ->set('settings.site_theme', 'ecommerce')
        ->assertSeeHtml('Ecommerce')
        ->assertSeeHtml('v1.0.0');
});

it('loads theme-scoped settings stored under the theme_ prefix', function () {
    Setting::factory()->create(['key' => 'theme_ecommerce_hero_badge', 'value' => 'New season', 'group' => 'theme', 'type' => 'string']);

    $component = Livewire::test(ThemeSettings::class);

    expect($component->get('settings.theme_ecommerce_hero_badge'))->toBe('New season');
});

it('saves theme-scoped settings through Setting::set', function () {
    Livewire::test(ThemeSettings::class)
        ->set('settings.theme_ecommerce_hero_badge', 'Summer sale')
        ->set('settings.theme_portfolio_hero_title', 'Designer')
        ->call('save');

    expect(Setting::where('key', 'theme_ecommerce_hero_badge')->value('value'))->toBe('Summer sale')
        ->and(Setting::where('key', 'theme_portfolio_hero_title')->value('value'))->toBe('Designer');
});

it('renders a color picker for every storefront area', function () {
    $component = Livewire::test(ThemeSettings::class)
        ->set('settings.site_theme', 'ecommerce')
        ->assertSeeHtml('type="color"')
        ->assertDontSee('Primary Color')
        ->assertDontSee('Secondary Color');

    foreach (['header_bg', 'header_text', 'nav_bg', 'nav_text', 'footer_bg', 'footer_text', 'footer_bottom',
        'button_bg', 'button_text', 'heading', 'text', 'price', 'accent', 'sale', 'page_bg'] as $area) {
        $component->assertSeeHtml("theme_ecommerce_{$area}_color");
    }
});

it('saves brand-new per-area colors even before their settings rows exist', function () {
    Livewire::test(ThemeSettings::class)
        ->assertSet('settings.theme_ecommerce_header_bg_color', '')
        ->set('settings.theme_ecommerce_header_bg_color', '#112233')
        ->set('settings.theme_ecommerce_button_bg_color', '#c01616')
        ->call('save');

    expect(Setting::where('key', 'theme_ecommerce_header_bg_color')->value('value'))->toBe('#112233')
        ->and(Setting::where('key', 'theme_ecommerce_button_bg_color')->value('value'))->toBe('#c01616');
});

it('applies the per-area colors to the storefront and ignores anything that is not a hex color', function () {
    Setting::set('site_theme', 'ecommerce');
    Setting::set('theme_ecommerce_primary_color', '#045b30');
    Setting::set('theme_ecommerce_header_bg_color', '#112233');
    Setting::set('theme_ecommerce_footer_text_color', '#eeeeee');
    Setting::set('theme_ecommerce_button_bg_color', 'red; } body { display:none');

    $this->get('/')
        ->assertOk()
        ->assertSee('--color-sf-header: #112233;', false)
        ->assertSee('--color-sf-footer-text: #eeeeee;', false)
        // The accent falls back to the legacy primary color.
        ->assertSee('--color-brand: #045b30;', false)
        ->assertDontSee('red; } body', false)
        ->assertDontSee('--color-sf-button:', false);
});

it('loads seeded ecommerce theme colors into the form', function () {
    Setting::factory()->create(['key' => 'theme_ecommerce_primary_color', 'value' => '#045b30', 'group' => 'frontend', 'type' => 'color']);
    Setting::factory()->create(['key' => 'theme_ecommerce_secondary_color', 'value' => '#7cc242', 'group' => 'frontend', 'type' => 'color']);

    $component = Livewire::test(ThemeSettings::class);

    expect($component->get('settings.theme_ecommerce_primary_color'))->toBe('#045b30')
        ->and($component->get('settings.theme_ecommerce_secondary_color'))->toBe('#7cc242');
});

it('renders the selected theme settings blade when the theme ships one', function () {
    expect(Themes::hasSettings('ecommerce'))->toBeTrue()
        ->and(Themes::hasSettings('default'))->toBeTrue()
        ->and(Themes::hasSettings('portfolio'))->toBeTrue();

    Livewire::test(ThemeSettings::class)
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

it('shows the theme settings guide via the info icon on the theme settings card', function () {
    Livewire::test(ThemeSettings::class)
        ->set('settings.site_theme', 'ecommerce')
        ->assertSee('How theme settings work')
        ->assertSee('Theme Settings Guide')
        ->assertSee('settings.blade.php')
        ->assertSee('Theme Builder Guide PDF')
        ->assertSee('Setting::get()');
});

it('omits the theme settings card when the selected theme has none', function () {
    $zip = makeThemeZip('retro', ['home.blade.php' => 'retro home']);

    Livewire::test(ThemeSettings::class)
        ->call('openInstallModal')
        ->set('themeZip', UploadedFile::fake()->createWithContent('retro.zip', file_get_contents($zip)))
        ->call('installTheme')
        ->assertHasNoErrors();

    expect(Themes::hasSettings('retro'))->toBeFalse();

    Livewire::test(ThemeSettings::class)
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

    $component = Livewire::test(ThemeSettings::class);

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

    $component = Livewire::test(ThemeSettings::class)
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
    expect($component->get('heroSlides'))->toHaveCount(ThemeSettings::MAX_HERO_SLIDES);

    // The blank slides (no image) are dropped on save.
    $component->call('save');

    $saved = [
        $slide('media/two.jpg', 'Fresh tea', 'Hand-picked leaves.', '/shop?category=tea'),
        $slide('media/three.jpg'),
    ];
    expect(json_decode(Setting::where('key', 'home_hero_slides')->value('value'), true))->toBe($saved)
        ->and(Setting::where('key', 'home_hero_image')->value('value'))->toBe('media/two.jpg');

    Livewire::test(ThemeSettings::class)->assertSet('heroSlides', $saved);
});

it('still reads hero slides saved as plain image URLs', function () {
    Setting::set('home_hero_slides', json_encode(['media/a.jpg', 'media/b.jpg']));

    Livewire::test(ThemeSettings::class)
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
    Livewire::test(ThemeSettings::class)
        ->set('settings.site_theme', 'ecommerce')
        ->assertSee('Promo tiles')
        ->assertSee('settings.theme_ecommerce_promo_2_link', false)
        ->set('settings.theme_ecommerce_promo_1_link', '/shop?sort=newest')
        ->set('settings.theme_ecommerce_promo_2_link', 'javascript:alert(1)')
        ->call('save');

    expect(Setting::where('key', 'theme_ecommerce_promo_1_link')->value('value'))->toBe('/shop?sort=newest');

    $this->get('/')
        ->assertOk()
        ->assertSee('href="/shop?sort=newest"', false)
        // An unsafe link never reaches the page — that tile opens the shop.
        ->assertDontSee('javascript:alert(1)', false)
        ->assertSee('href="'.route('shop').'" class="group/promo', false);
});

it('lists every installed theme folder as a selectable design', function () {
    Livewire::test(ThemeSettings::class)
        ->assertViewHas('themes', fn ($themes) => collect(['default', 'ecommerce', 'portfolio'])->diff(array_keys($themes))->isEmpty());
});

it('saves theme settings through Setting::set', function () {
    Setting::factory()->create(['key' => 'site_theme', 'value' => 'default', 'group' => 'frontend', 'type' => 'select']);
    Setting::factory()->create(['key' => 'home_hero_image', 'value' => '', 'group' => 'frontend', 'type' => 'string']);
    Setting::factory()->create(['key' => 'chat_widget_enabled', 'value' => '1', 'group' => 'frontend', 'type' => 'boolean']);
    Setting::factory()->create(['key' => 'popup_enabled', 'value' => '0', 'group' => 'frontend', 'type' => 'boolean']);

    Livewire::test(ThemeSettings::class)
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
    Livewire::test(ThemeSettings::class)
        ->call('openInstallModal')
        ->assertSet('showInstallModal', true)
        ->call('closeInstallModal')
        ->assertSet('showInstallModal', false);
});

it('installs a theme from a zip into the themes directory', function () {
    $zip = makeThemeZip('retro', [
        'home.blade.php' => 'retro home',
        'page.blade.php' => 'retro page',
        'partials/head.blade.php' => 'retro head',
    ]);

    Livewire::test(ThemeSettings::class)
        ->call('openInstallModal')
        ->set('themeZip', UploadedFile::fake()->createWithContent('retro.zip', file_get_contents($zip)))
        ->call('installTheme')
        ->assertSet('showInstallModal', false)
        ->assertHasNoErrors();

    expect(is_dir(Themes::path().'/retro'))->toBeTrue()
        ->and(file_exists(Themes::path().'/retro/home.blade.php'))->toBeTrue()
        ->and(file_exists(Themes::path().'/retro/partials/head.blade.php'))->toBeTrue()
        ->and(Themes::all())->toHaveKey('retro');
});

it('turns a spaced theme folder name into a slugged theme folder', function () {
    $zip = makeThemeZip('My Cool Store', [
        'home.blade.php' => 'cool home',
    ]);

    Livewire::test(ThemeSettings::class)
        ->call('openInstallModal')
        ->set('themeZip', UploadedFile::fake()->createWithContent('my-cool-store.zip', file_get_contents($zip)))
        ->call('installTheme')
        ->assertHasNoErrors();

    expect(is_dir(Themes::path().'/my-cool-store'))->toBeTrue();
});

it('rejects a file that is not a valid zip theme package', function () {
    Livewire::test(ThemeSettings::class)
        ->call('openInstallModal')
        ->set('themeZip', UploadedFile::fake()->create('theme.zip', 256))
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

    Livewire::test(ThemeSettings::class)
        ->call('openInstallModal')
        ->set('themeZip', UploadedFile::fake()->createWithContent('two-themes.zip', file_get_contents($merged)))
        ->call('installTheme')
        ->assertHasErrors(['themeZip']);
});

it('does not overwrite an already-installed theme', function () {
    $zip = makeThemeZip('default', ['home.blade.php' => 'evil home']);

    Livewire::test(ThemeSettings::class)
        ->call('openInstallModal')
        ->set('themeZip', UploadedFile::fake()->createWithContent('default.zip', file_get_contents($zip)))
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

    Livewire::test(ThemeSettings::class)
        ->call('openInstallModal')
        ->set('themeZip', UploadedFile::fake()->createWithContent('bad.zip', file_get_contents($path)))
        ->call('installTheme')
        ->assertHasErrors(['themeZip']);

    expect(is_dir(Themes::path().'/retro'))->toBeFalse();
});

it('saves the live chat widget color', function () {
    Livewire::test(ThemeSettings::class)
        ->set('settings.chat_widget_color', ' #FF5500 ')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::where('key', 'chat_widget_color')->value('value'))->toBe('#ff5500');
});

it('allows a blank live chat widget color to fall back to the site primary color', function () {
    Setting::set('chat_widget_color', '#ff5500');

    Livewire::test(ThemeSettings::class)
        ->set('settings.chat_widget_color', '')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::where('key', 'chat_widget_color')->value('value'))->toBe('');
});

it('rejects an invalid live chat widget color', function () {
    Livewire::test(ThemeSettings::class)
        ->set('settings.chat_widget_color', 'red; background:url(x)')
        ->call('save')
        ->assertHasErrors(['settings.chat_widget_color']);

    expect(Setting::where('key', 'chat_widget_color')->value('value'))->toBeNull();
});
