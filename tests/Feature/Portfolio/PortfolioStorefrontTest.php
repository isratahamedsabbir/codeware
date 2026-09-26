<?php

use App\Models\Page;
use App\Models\PortfolioExperience;
use App\Models\PortfolioProject;
use App\Models\PortfolioSkill;
use App\Models\Setting;
use App\Support\Portfolio;
use Database\Seeders\PortfolioContentSeeder;

beforeEach(function () {
    Page::factory()->published()->create(['title' => ['en' => 'Home', 'bn' => ''], 'slug' => 'home', 'sort_order' => 0]);

    Setting::set('site_theme', 'portfolio');
});

it('renders the admin-managed projects in the #projects section', function () {
    PortfolioProject::factory()->published()->create([
        'title' => ['en' => 'Hotel Booking System', 'bn' => 'হোটেল বুকিং সিস্টেম'],
        'description' => ['en' => 'Multi-property booking platform.', 'bn' => ''],
        'tech' => ['Laravel', 'Pusher'],
        'stats' => 'In Production',
        'sort_order' => 1,
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Hotel Booking System')
        ->assertSee('Multi-property booking platform.')
        ->assertSee('Laravel')
        ->assertSee('In Production');
});

it('keeps inactive projects out of the theme', function () {
    PortfolioProject::factory()->published()->create(['title' => ['en' => 'Live Project', 'bn' => '']]);
    PortfolioProject::factory()->draft()->create(['title' => ['en' => 'Hidden Project', 'bn' => '']]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Live Project')
        ->assertDontSee('Hidden Project');
});

it('renders the admin-managed timeline in the #experience section', function () {
    PortfolioExperience::factory()->published()->create([
        'role' => ['en' => 'Full Stack Developer', 'bn' => ''],
        'company' => ['en' => 'Your Company', 'bn' => ''],
        'period' => '2024 - Present',
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Full Stack Developer')
        ->assertSee('Your Company')
        ->assertSee('2024 - Present');
});

it('prints the active skills under one heading per group, in the order the groups first appear', function () {
    PortfolioSkill::factory()->published()->create(['name' => ['en' => 'Laravel', 'bn' => ''], 'group' => 'Backend', 'sort_order' => 1]);
    PortfolioSkill::factory()->published()->create(['name' => ['en' => 'AWS', 'bn' => ''], 'group' => 'DevOps & Cloud', 'sort_order' => 2]);
    PortfolioSkill::factory()->published()->create(['name' => ['en' => 'Redis', 'bn' => ''], 'group' => 'Backend', 'sort_order' => 3]);

    expect(Portfolio::skillGroups()->keys()->all())->toBe(['Backend', 'DevOps & Cloud'])
        ->and(Portfolio::skillGroups()->get('Backend')->pluck('name')->all())->toBe(['Laravel', 'Redis']);

    $this->get('/')
        ->assertOk()
        ->assertSee('Backend')
        ->assertSee('DevOps &amp; Cloud', false)
        ->assertSee('Laravel')
        ->assertSee('Redis');
});

it('leaves out inactive skills and empty groups entirely', function () {
    PortfolioSkill::factory()->published()->create(['name' => ['en' => 'Laravel', 'bn' => ''], 'group' => 'Backend']);
    PortfolioSkill::factory()->draft()->create(['name' => ['en' => 'Forgotten Skill', 'bn' => ''], 'group' => 'Retired Group']);

    $this->get('/')
        ->assertOk()
        ->assertSee('Laravel')
        ->assertDontSee('Forgotten Skill')
        ->assertDontSee('Retired Group');
});

it('falls back to an empty section, not a crash, when nothing is published yet', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('id="projects"', false)
        ->assertSee('id="experience"', false)
        ->assertSee('id="technology"', false);
});

it('shows the bn translation of a row when the site is rendering in bn', function () {
    PortfolioProject::factory()->published()->create([
        'title' => ['en' => 'Hotel Booking System', 'bn' => 'হোটেল বুকিং সিস্টেম'],
        'description' => ['en' => 'Multi-property booking platform.', 'bn' => 'বহু-প্রতিষ্ঠানের বুকিং প্ল্যাটফর্ম।'],
    ]);

    $this->withSession(['frontend_locale' => 'bn'])
        ->get('/')
        ->assertOk()
        ->assertSee('হোটেল বুকিং সিস্টেম')
        ->assertSee('বহু-প্রতিষ্ঠানের বুকিং প্ল্যাটফর্ম।');
});

it('seeds the showcase content active, so a fresh install renders a complete portfolio', function () {
    $this->seed(PortfolioContentSeeder::class);

    expect(PortfolioProject::where('status', 'active')->count())->toBe(4)
        ->and(PortfolioExperience::where('status', 'active')->count())->toBe(2)
        ->and(PortfolioSkill::where('status', 'active')->count())->toBe(14)
        ->and(Portfolio::skillGroups()->keys()->all())
        ->toBe(['Backend', 'Frontend & Tools', 'DevOps & Cloud']);

    $this->get('/')
        ->assertOk()
        ->assertSee('SaaS Starter Platform')
        ->assertSee('Full Stack Developer')
        ->assertSee('Frontend &amp; Tools', false);
});

it('re-seeding the showcase content does not duplicate rows', function () {
    $this->seed(PortfolioContentSeeder::class);
    $this->seed(PortfolioContentSeeder::class);

    expect(PortfolioProject::count())->toBe(4)
        ->and(PortfolioExperience::count())->toBe(2)
        ->and(PortfolioSkill::count())->toBe(14);
});

it('picks up an admin write immediately, without waiting out a cache lifetime', function () {
    PortfolioProject::factory()->published()->create(['title' => ['en' => 'Before', 'bn' => '']]);

    $this->get('/')->assertOk()->assertSee('Before');

    PortfolioProject::sole()->update(['title' => ['en' => 'After', 'bn' => ''], 'status' => 'active']);

    $this->get('/')->assertOk()->assertSee('After')->assertDontSee('Before');
});
