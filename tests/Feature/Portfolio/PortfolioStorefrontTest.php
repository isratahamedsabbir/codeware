<?php

use App\Livewire\Admin\ThemeSettings\Index;
use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use App\Support\PortfolioProfile;
use Database\Seeders\PortfolioContentSeeder;
use Database\Seeders\PortfolioMenuSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

/**
 * The portfolio one-pager's list sections, all of which are settings-driven now.
 *
 * Projects, Experience and Skills each used to be a table with its own admin CRUD
 * screens; Testimonials did not exist at all. All four are JSON settings edited
 * on the Theme Settings screen, so these tests cover the same three sections the
 * deleted table-backed ones did â€” plus testimonials â€” from the settings side.
 *
 * What matters on this page is what a visitor is not shown: a section with no
 * content must disappear rather than render a heading over nothing, and a
 * half-filled row must not reach the public page as a broken card.
 */
beforeEach(function () {
    Page::factory()->published()->create(['title' => ['en' => 'Home', 'bn' => ''], 'slug' => 'home', 'sort_order' => 0]);

    Setting::set('site_theme', 'portfolio');
    $this->seed(PortfolioMenuSeeder::class);
});

/**
 * @param  array<int, array<string, string>>  $rows
 */
function setRows(string $key, array $rows): void
{
    Setting::set($key, json_encode($rows));
}

it('renders the projects the owner entered, with the stack split into chips', function () {
    setRows('theme_portfolio_projects', [
        [
            'title' => 'Hotel Booking System',
            'description' => 'Multi-property booking with real-time availability.',
            'icon' => 'ðŸ¨',
            'tech' => 'Laravel, MySQL, Stripe',
            'stats' => 'In Production',
            'link' => 'https://example.com/hotel',
        ],
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Hotel Booking System')
        ->assertSee('Multi-property booking with real-time availability.')
        ->assertSee('In Production')
        // The comma-separated field arrives as three separate chips, not as one
        // pill reading "Laravel, MySQL, Stripe".
        ->assertSee('Laravel')
        ->assertSee('MySQL')
        ->assertSee('Stripe')
        ->assertSee('href="https://example.com/hotel"', false);
});

it('drops a blank segment from a tech stack, so a trailing comma is not an empty pill', function () {
    setRows('theme_portfolio_projects', [
        ['title' => 'Trailing comma', 'description' => '', 'icon' => '', 'tech' => 'Laravel, MySQL,', 'stats' => '', 'link' => ''],
    ]);

    expect(PortfolioProfile::projects()->first()['tech'])->toBe(['Laravel', 'MySQL']);
});

it('renders the work timeline, and no company line for a role that had no company', function () {
    setRows('theme_portfolio_experiences', [
        ['role' => 'Backend Developer', 'company' => 'Acme Ltd', 'period' => '2022 - 2024', 'description' => 'Owned the billing service.'],
        ['role' => 'Freelance Developer', 'company' => '', 'period' => '2020 - 2022', 'description' => 'Client work of every shape.'],
    ]);

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('Backend Developer')
        ->and($html)->toContain('Acme Ltd')
        ->and($html)->toContain('Owned the billing service.')
        ->and($html)->toContain('Freelance Developer')
        // Only the role that has a company gets the "@ Company" line, so exactly
        // one of the two timeline cards carries the span.
        ->and(substr_count($html, 'text-(--pf-text-muted)">@ '))->toBe(1);
});

it('groups skills under their heading and prints the group size', function () {
    setRows('theme_portfolio_skills', [
        ['name' => 'Laravel', 'group' => 'Backend', 'icon' => 'ðŸ”´', 'description' => 'Framework of choice'],
        ['name' => 'MySQL', 'group' => 'Backend', 'icon' => '', 'description' => ''],
        ['name' => 'React', 'group' => 'Frontend', 'icon' => 'âš›ï¸', 'description' => ''],
    ]);

    expect(PortfolioProfile::skillGroups()->keys()->all())->toBe(['Backend', 'Frontend'])
        ->and(PortfolioProfile::skillGroups()['Backend']->pluck('name')->all())->toBe(['Laravel', 'MySQL']);

    $this->get('/')
        ->assertOk()
        ->assertSee('Backend')
        ->assertSee('Frontend')
        ->assertSee('Framework of choice');
});

it('drops a skill with no group, rather than filing it under a blank heading', function () {
    setRows('theme_portfolio_skills', [
        ['name' => 'Laravel', 'group' => 'Backend', 'icon' => '', 'description' => ''],
        ['name' => 'Orphan', 'group' => '', 'icon' => '', 'description' => ''],
        ['name' => 'Also Orphan', 'group' => '   ', 'icon' => '', 'description' => ''],
    ]);

    $groups = PortfolioProfile::skillGroups();

    expect($groups->keys()->all())->toBe(['Backend'])
        ->and($groups['Backend']->pluck('name')->all())->toBe(['Laravel'])
        // Nothing anywhere in the JSON may leak a groupless skill onto the page.
        ->and($this->get('/')->getContent())->not->toContain('Orphan');
});

it('renders testimonials with their attribution, and stars only where rated', function () {
    setRows('theme_portfolio_testimonials', [
        ['quote' => 'Took a half-finished app and made it sellable.', 'name' => 'A Client', 'role' => 'Founder, Acme', 'rating' => '5'],
        ['quote' => 'No surprises in the estimates.', 'name' => 'B Client', 'role' => 'PM, Example', 'rating' => ''],
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Took a half-finished app and made it sellable.')
        ->assertSee('A Client')
        ->assertSee('Founder, Acme')
        ->assertSee('No surprises in the estimates.')
        // One rated quote, so exactly one star row is drawn.
        ->assertSee('aria-label="5 out of 5"', false);
});

it('reads a rating as a number, clamped to five, and treats a blank one as no rating', function () {
    setRows('theme_portfolio_testimonials', [
        ['quote' => 'Five', 'name' => 'A', 'role' => '', 'rating' => '5'],
        ['quote' => 'Five slash five', 'name' => 'B', 'role' => '', 'rating' => '5/5'],
        ['quote' => 'Way out of range', 'name' => 'C', 'role' => '', 'rating' => '9'],
        ['quote' => 'Zero', 'name' => 'D', 'role' => '', 'rating' => '0'],
        ['quote' => 'Unrated', 'name' => 'E', 'role' => '', 'rating' => ''],
        ['quote' => 'Nonsense', 'name' => 'F', 'role' => '', 'rating' => 'great'],
    ]);

    expect(PortfolioProfile::testimonials()->pluck('rating')->all())->toBe([5, 5, 5, 1, null, null]);
});

it('hides a section with no rows instead of printing a heading over nothing', function () {
    // Everything left blank: the sections that used to be table-backed are the
    // ones most likely to be empty on a real install, and an empty "Featured
    // projects" heading advertises a gap rather than hiding it.
    $this->get('/')
        ->assertOk()
        ->assertDontSee('Featured projects')
        ->assertDontSee('What clients say');

    $this->assertSame(0, PortfolioProfile::projects()->count());
    $this->assertSame(0, PortfolioProfile::experiences()->count());
    $this->assertSame(0, PortfolioProfile::skillGroups()->count());
    $this->assertSame(0, PortfolioProfile::testimonials()->count());
});

it('keeps the testimonials anchor on the page even with nothing to show, so a deep link still lands', function () {
    $this->get('/')->assertOk()->assertSee('id="testimonials"', false);
});

it('survives a hand-edited or corrupt list value without fataling the page', function () {
    foreach (['theme_portfolio_projects', 'theme_portfolio_experiences', 'theme_portfolio_skills', 'theme_portfolio_testimonials'] as $key) {
        Setting::set($key, '{"not":"a list"}');
    }

    $this->get('/')
        ->assertOk()
        ->assertDontSee('Featured projects')
        ->assertDontSee('What clients say');
});

it('ships a complete demo portfolio, and re-seeding never doubles a section', function () {
    $this->seed(PortfolioContentSeeder::class);

    $first = [
        'projects' => PortfolioProfile::projects()->pluck('title')->all(),
        'experiences' => PortfolioProfile::experiences()->pluck('role')->all(),
        'skillGroups' => PortfolioProfile::skillGroups()->keys()->all(),
        'testimonials' => PortfolioProfile::testimonials()->pluck('quote')->all(),
    ];

    expect($first['projects'])->not->toBeEmpty()
        ->and($first['experiences'])->not->toBeEmpty()
        ->and($first['skillGroups'])->not->toBeEmpty()
        ->and($first['testimonials'])->not->toBeEmpty();

    $this->seed(PortfolioContentSeeder::class);

    expect(PortfolioProfile::projects()->pluck('title')->all())->toBe($first['projects'])
        ->and(PortfolioProfile::experiences()->pluck('role')->all())->toBe($first['experiences'])
        ->and(PortfolioProfile::skillGroups()->keys()->all())->toBe($first['skillGroups'])
        ->and(PortfolioProfile::testimonials()->pluck('quote')->all())->toBe($first['testimonials']);
});

it('seeds no placeholder company names, because "Your Company" tells a visitor nothing', function () {
    $this->seed(PortfolioContentSeeder::class);

    foreach (PortfolioProfile::experiences() as $experience) {
        expect($experience['company'])->not->toContain('Your Company')
            ->and($experience['company'])->not->toContain('Previous Company');
    }

    expect($this->get('/')->getContent())->not->toContain('Your Company');
});

it('round-trips a project through the admin screen and out to the page', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(Index::class)
        ->call('addRepeaterRow', 'theme_portfolio_projects', ['title', 'description', 'icon', 'tech', 'stats', 'link'])
        ->set('repeaters.theme_portfolio_projects.0.title', 'Storefront & Admin Split')
        ->set('repeaters.theme_portfolio_projects.0.description', 'A React storefront on a Laravel API.')
        ->set('repeaters.theme_portfolio_projects.0.tech', 'React, Laravel, MySQL')
        ->set('repeaters.theme_portfolio_projects.0.link', 'https://example.com/split')
        ->call('save')
        ->assertHasNoErrors();

    $stored = json_decode(Setting::where('key', 'theme_portfolio_projects')->value('value'), true);

    expect($stored)->toBe([[
        'title' => 'Storefront & Admin Split',
        'description' => 'A React storefront on a Laravel API.',
        'icon' => '',
        'tech' => 'React, Laravel, MySQL',
        'stats' => '',
        'link' => 'https://example.com/split',
    ]]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Storefront & Admin Split')
        ->assertSee('A React storefront on a Laravel API.');
});

it('never lets a repeater grow past the cap its theme declared', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->admin()->create());

    // The trust strip declares :max="4" in settings.blade.php. The add button
    // hides at four, and the server refuses at four: the cap is a promise about
    // how many rows the storefront was designed to lay out, so a crafted request
    // that calls the method directly must not be able to break the layout.
    setRows('theme_portfolio_stats', [
        ['value' => '1', 'label' => 'One'],
        ['value' => '2', 'label' => 'Two'],
        ['value' => '3', 'label' => 'Three'],
    ]);

    Livewire::test(Index::class)
        ->call('addRepeaterRow', 'theme_portfolio_stats', ['value', 'label'])
        // The new row is filled in, so it is a real row and is kept.
        ->set('repeaters.theme_portfolio_stats.3.value', '4')
        ->set('repeaters.theme_portfolio_stats.3.label', 'Four')
        ->assertCount('repeaters.theme_portfolio_stats', 4)
        // Already at the cap, so these are refused rather than appended.
        ->call('addRepeaterRow', 'theme_portfolio_stats', ['value', 'label'])
        ->call('addRepeaterRow', 'theme_portfolio_stats', ['value', 'label'])
        ->assertCount('repeaters.theme_portfolio_stats', 4)
        ->call('save');

    expect(json_decode(Setting::where('key', 'theme_portfolio_stats')->value('value'), true))
        ->toHaveCount(4)
        ->and(array_column(json_decode(Setting::where('key', 'theme_portfolio_stats')->value('value'), true), 'label'))
        ->toBe(['One', 'Two', 'Three', 'Four']);
});

it('does not store a row the owner added but never filled in', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->admin()->create());

    // An "Add" button that leaves a blank row behind would put an empty card on
    // the storefront the next time the page is saved from anywhere else. The
    // row is real while the owner is typing in it and disappears if they leave.
    setRows('theme_portfolio_stats', [
        ['value' => '1', 'label' => 'One'],
    ]);

    Livewire::test(Index::class)
        ->call('addRepeaterRow', 'theme_portfolio_stats', ['value', 'label'])
        ->assertCount('repeaters.theme_portfolio_stats', 2)
        ->call('save');

    expect(json_decode(Setting::where('key', 'theme_portfolio_stats')->value('value'), true))
        ->toBe([['value' => '1', 'label' => 'One']]);
});

it('truncates an over-long list on save, so a stored value can never exceed the declared cap', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->admin()->create());

    // The admin screen can only get here through its own add button, which stops
    // at the cap. This is the belt to that braces: a value already in the
    // database from a hand-edited settings row, an older cap, or a crafted
    // request is cut to the declared number of rows on the way back in.
    $rows = [];
    foreach (range(1, 9) as $n) {
        $rows[] = ['value' => (string) $n, 'label' => "Stat {$n}"];
    }
    setRows('theme_portfolio_stats', $rows);

    Livewire::test(Index::class)
        ->call('save');

    $stored = json_decode(Setting::where('key', 'theme_portfolio_stats')->value('value'), true);

    expect($stored)->toHaveCount(4)
        // The first rows win, which are the ones the reordering UI kept on top.
        ->and(array_column($stored, 'label'))->toBe(['Stat 1', 'Stat 2', 'Stat 3', 'Stat 4']);
});

it('reads a declared cap per repeater, so one list cannot borrow another list cap', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->admin()->create());

    $class = new ReflectionClass(Index::class);
    $maxes = $class->getMethod('declaredRepeaterMax');
    $maxes->setAccessible(true);
    $parsed = $maxes->invoke($class->newInstanceWithoutConstructor());

    // Each declaration states its own :max, and the parse reads the attribute
    // out of that one tag rather than scanning the file for numbers.
    expect($parsed['theme_portfolio_stats'])->toBe(4)
        ->and($parsed['theme_portfolio_skills'])->toBe(40)
        ->and($parsed['theme_portfolio_testimonials'])->toBe(12)
        // A repeater whose theme omits :max falls back to the component default.
        ->and($parsed)->not->toHaveKey('theme_not_a_real_repeater');
});

it('shows every declared portfolio list on one admin screen, and no separate CRUD screen exists', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->admin()->create());
    $this->seed(PortfolioContentSeeder::class);

    // All eight sections, one page, one save button. The bindings prove the
    // declaration reached the screen: a repeater is only editable if its rows
    // are bound, so a missing setting-key here means an owner who cannot edit
    // the section at all.
    // Selecting the theme is what renders its form, and it reloads the repeaters
    // from the stored values — so it has to happen before the assertions below,
    // not between them.
    $component = Livewire::test(Index::class)
        ->set('settings.site_theme', 'portfolio');

    foreach ([
        'theme_portfolio_projects' => 'title',
        'theme_portfolio_experiences' => 'role',
        'theme_portfolio_skills' => 'name',
        'theme_portfolio_testimonials' => 'quote',
        'theme_portfolio_stats' => 'value',
        'theme_portfolio_services' => 'title',
        'theme_portfolio_education' => 'title',
        'theme_portfolio_certifications' => 'title',
    ] as $key => $field) {
        $component->assertSeeHtml("wire:model=\"repeaters.{$key}.0.{$field}\"", false);
    }

    // And the three old CRUD screens are gone, not merely unlinked: the routes,
    // the components and the tables all went with them.
    expect(Schema::hasTable('portfolio_projects'))->toBeFalse()
        ->and(Schema::hasTable('portfolio_experiences'))->toBeFalse()
        ->and(Schema::hasTable('portfolio_skills'))->toBeFalse();

    foreach (['/portfolio/projects', '/portfolio/experiences', '/portfolio/skills'] as $path) {
        $this->get($path)->assertNotFound();
    }

    // Nothing in the sidebar points at them either.
    expect(DB::table('menu_items')->where('route_name', 'like', 'admin.portfolio-%')->exists())->toBeFalse();
});
