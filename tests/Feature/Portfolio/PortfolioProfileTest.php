<?php

use App\Livewire\Admin\ThemeSettings\Index as ThemeSettings;
use App\Models\Page;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Support\PortfolioProfile;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

/**
 * The settings-driven half of the portfolio one-pager: the hero, the trust
 * strip, "what I do", education and certifications. None of it has a table of
 * its own, so these tests are the only thing standing between a fresh install
 * and a page that says "Your University".
 */
beforeEach(function () {
    Page::factory()->published()->create(['title' => ['en' => 'Home', 'bn' => ''], 'slug' => 'home', 'sort_order' => 0]);

    Setting::set('site_theme', 'portfolio');
});

/**
 * Store a repeater's rows the way the admin screen does: one JSON setting.
 *
 * @param  array<int, array<string, string>>  $rows
 */
function setRepeater(string $key, array $rows): void
{
    Setting::set($key, json_encode($rows));
}

it('falls back to a monogram instead of a placeholder photo, so no stock silhouette ships', function () {
    $hero = PortfolioProfile::hero();

    expect($hero['photo'])->toBeNull()
        ->and($hero['monogram'])->toBeString()->not->toBe('');

    $this->get('/')
        ->assertOk()
        ->assertSee($hero['monogram'])
        // The literal path a placeholder would have left behind.
        ->assertDontSee('placeholder.com', false)
        ->assertDontSee('via.placeholder', false);
});

it('derives the monogram from the first two words of the name', function () {
    Setting::set('theme_portfolio_name', 'Sabbir Hossain');

    expect(PortfolioProfile::hero()['monogram'])->toBe('SH');

    Setting::set('theme_portfolio_name', '  Ada  Lovelace  ');

    expect(PortfolioProfile::hero()['monogram'])->toBe('AL');
});

it('uses the uploaded photo when one is set, and drops the monogram', function () {
    Setting::set('theme_portfolio_name', 'Ada Lovelace');
    Setting::set('theme_portfolio_photo', '/storage/portraits/ada.jpg');

    $hero = PortfolioProfile::hero();

    expect($hero['photo'])->toBe('/storage/portraits/ada.jpg');

    $this->get('/')
        ->assertOk()
        ->assertSee('/storage/portraits/ada.jpg')
        ->assertSee('alt="Ada Lovelace"', false)
        ->assertDontSee('pf-monogram', false);
});

it('keeps the shipped hero title and tagline keys working', function () {
    // These are the keys this theme already saved copy under; renaming them
    // would silently drop an existing install's text.
    Setting::set('theme_portfolio_hero_title', 'Backend & Platform Engineer');
    Setting::set('theme_portfolio_hero_tagline', 'APIs, queues and the databases behind them.');

    $hero = PortfolioProfile::hero();

    expect($hero['role'])->toBe('Backend & Platform Engineer')
        ->and($hero['tagline'])->toBe('APIs, queues and the databases behind them.');

    $this->get('/')
        ->assertOk()
        ->assertSee('Backend &amp; Platform Engineer', false)
        ->assertSee('APIs, queues and the databases behind them.');
});

it('reads the trust strip, which is stored as value/label rather than a title', function () {
    setRepeater('theme_portfolio_stats', [
        ['value' => '5+', 'label' => 'Years shipping'],
        ['value' => '40+', 'label' => 'Projects delivered'],
    ]);

    expect(PortfolioProfile::stats()->all())->toBe([
        ['value' => '5+', 'label' => 'Years shipping'],
        ['value' => '40+', 'label' => 'Projects delivered'],
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('5+')
        ->assertSee('Years shipping')
        ->assertSee('40+')
        ->assertSee('Projects delivered');
});

it('drops a stat that is missing either half, because half a stat is noise', function () {
    setRepeater('theme_portfolio_stats', [
        ['value' => '5+', 'label' => 'Years shipping'],
        ['value' => '7', 'label' => ''],       // figure with nothing to weigh it
        ['value' => '', 'label' => 'Clients'], // label with no figure behind it
    ]);

    expect(PortfolioProfile::stats()->pluck('label')->all())->toBe(['Years shipping']);
});

it('renders the services, education and certifications the admin entered', function () {
    // Services come from the Service model, not from a settings repeater: a
    // bookable thing has to be a row a booking can point a foreign key at.
    Service::factory()->published()->create([
        'name' => ['en' => 'Laravel Application Development', 'bn' => ''],
        'description' => ['en' => 'Admin panels and REST APIs.', 'bn' => ''],
    ]);

    setRepeater('theme_portfolio_education', [
        ['title' => 'B.Sc. in Computer Science', 'period' => '2018 - 2022', 'description' => 'Example University'],
    ]);
    setRepeater('theme_portfolio_certifications', [
        ['title' => 'AWS Certified Developer', 'period' => '2024', 'description' => 'Amazon Web Services'],
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Laravel Application Development')
        ->assertSee('Admin panels and REST APIs.')
        ->assertSee('B.Sc. in Computer Science')
        ->assertSee('Example University')
        ->assertSee('2018 - 2022')
        ->assertSee('AWS Certified Developer')
        ->assertSee('Amazon Web Services');
});

it('shows only active services, so the page never offers something the admin retired', function () {
    Service::factory()->published()->create([
        'name' => ['en' => 'Published Work', 'bn' => ''],
    ]);
    Service::factory()->draft()->create([
        'name' => ['en' => 'Retired Work', 'bn' => ''],
    ]);

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('Published Work')
        ->and($html)->not->toContain('Retired Work');
});

it('offers a booking form on every service card', function () {
    Service::factory()->published()->create([
        'name' => ['en' => 'Bookable Thing', 'bn' => ''],
    ]);

    $html = $this->get('/')->assertOk()->getContent();

    // The form is a Livewire child carrying the service id, so a booking can
    // never be recorded against a different service than the one on the card.
    expect($html)->toContain('frontend.book-service')
        ->and($html)->toContain('Request This');
});

it('prints no price line for a service priced at zero rather than a free-sounding 0.00', function () {
    // services.price is NOT NULL DEFAULT 0. A card reading "0.00" says the work
    // is free, or that the page is broken - neither is what a 0 means.
    Service::factory()->published()->create([
        'name' => ['en' => 'Ask Me Pricing', 'bn' => ''],
        'price' => 0,
    ]);

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('Ask Me Pricing')
        ->and($html)->not->toContain('0.00');
});

it('hides a section entirely rather than printing a heading over nothing', function () {
    // The default state of every fresh install: no education, no certifications.
    expect(PortfolioProfile::education())->toBeEmpty()
        ->and(PortfolioProfile::certifications())->toBeEmpty();

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->not->toContain('Your University')
        ->and($html)->not->toContain('Training Institute');

    // The top-level anchors the seeded menu links to must survive regardless.
    expect($html)->toContain('id="services"', false)
        ->and($html)->toContain('id="education"', false);
});

it('survives a hand-edited or corrupt repeater value without fataling the page', function () {
    // Each of these is a value an operator could leave in the settings table.
    Setting::set('theme_portfolio_stats', 'not json at all');
    Setting::set('theme_portfolio_education', '["Just a string", 42, null]');
    Setting::set('theme_portfolio_certifications', '');

    expect(PortfolioProfile::stats())->toBeEmpty()
        // A bare string still becomes a titled row rather than being discarded.
        ->and(PortfolioProfile::education()->pluck('degree')->all())->toBe(['Just a string'])
        ->and(PortfolioProfile::certifications())->toBeEmpty();

    $this->get('/')->assertOk();
});

it('trims stored values, so a stray space never reaches the page', function () {
    setRepeater('theme_portfolio_education', [
        ['title' => '  B.Sc. Computer Science  ', 'period' => '', 'description' => ''],
    ]);

    $row = PortfolioProfile::education()->first();

    expect($row['degree'])->toBe('B.Sc. Computer Science');
});

it('round-trips a repeater through the admin screen and out to the page', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(ThemeSettings::class)
        ->set('settings.site_theme', 'portfolio')
        ->call('addRepeaterRow', 'theme_portfolio_education', ['title', 'period', 'description'])
        ->set('repeaters.theme_portfolio_education.0.title', 'B.Sc. in Computer Science')
        ->set('repeaters.theme_portfolio_education.0.description', 'Example University')
        ->call('save')
        ->assertHasNoErrors();

    expect(json_decode(Setting::where('key', 'theme_portfolio_education')->value('value'), true))
        ->toBe([['title' => 'B.Sc. in Computer Science', 'period' => '', 'description' => 'Example University']]);

    $this->get('/')
        ->assertOk()
        ->assertSee('B.Sc. in Computer Science')
        ->assertSee('Example University');
});

it('reorders, and never persists, a blank repeater row', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->admin()->create());

    setRepeater('theme_portfolio_education', [
        ['title' => 'Second', 'period' => '', 'description' => ''],
        ['title' => 'First', 'period' => '', 'description' => ''],
    ]);

    Livewire::test(ThemeSettings::class)
        ->set('settings.site_theme', 'portfolio')
        ->call('addRepeaterRow', 'theme_portfolio_education', ['title', 'period', 'description'])
        // The empty row the UI leaves on screen.
        ->call('moveRepeaterRow', 'theme_portfolio_education', 2, 'up')
        ->set('repeaters.theme_portfolio_education.1.title', '  Moved to the top  ')
        ->call('save');

    expect(json_decode(Setting::where('key', 'theme_portfolio_education')->value('value'), true))
        ->toBe([
            ['title' => 'Second', 'period' => '', 'description' => ''],
            ['title' => 'Moved to the top', 'period' => '', 'description' => ''],
            ['title' => 'First', 'period' => '', 'description' => ''],
        ]);
});

it('removes a repeater row without disturbing its neighbours', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->admin()->create());

    Setting::set('theme_portfolio_certifications', json_encode([
        ['title' => 'Keep me', 'period' => '2023', 'description' => 'Issuer A'],
        ['title' => 'Remove me', 'period' => '2024', 'description' => 'Issuer B'],
    ]));

    Livewire::test(ThemeSettings::class)
        ->set('settings.site_theme', 'portfolio')
        ->call('removeRepeaterRow', 'theme_portfolio_certifications', 1)
        ->call('save');

    expect(json_decode(Setting::where('key', 'theme_portfolio_certifications')->value('value'), true))
        ->toBe([['title' => 'Keep me', 'period' => '2023', 'description' => 'Issuer A']]);
});
