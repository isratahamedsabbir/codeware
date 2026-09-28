<?php

use App\Livewire\Frontend\NewsletterSubscribe;
use App\Models\Feature;
use App\Models\Language;
use App\Models\Page;
use App\Models\Setting;
use App\Models\SocialLink;
use App\Models\Subscriber;
use App\Models\User;
use App\Support\PortfolioSocials;
use Database\Seeders\PortfolioMenuSeeder;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

use function Pest\Laravel\get;

it('subscribes a new email from the footer box', function () {
    Livewire::test(NewsletterSubscribe::class)
        ->set('email', 'john@example.com')
        ->call('subscribe')
        ->assertSet('done', true)
        ->assertSet('already', false)
        ->assertSet('email', '');

    $this->assertDatabaseHas('subscribers', [
        'email' => 'john@example.com',
        'status' => 'subscribed',
    ]);
});

it('tells an already subscribed email it is already on the list', function () {
    Subscriber::factory()->create(['email' => 'john@example.com', 'status' => 'subscribed']);

    Livewire::test(NewsletterSubscribe::class)
        ->set('email', 'john@example.com')
        ->call('subscribe')
        ->assertSet('done', true)
        ->assertSet('already', true);

    expect(Subscriber::where('email', 'john@example.com')->count())->toBe(1);
});

it('resubscribes a previously unsubscribed email', function () {
    Subscriber::factory()->unsubscribed()->create(['email' => 'john@example.com']);

    Livewire::test(NewsletterSubscribe::class)
        ->set('email', 'john@example.com')
        ->call('subscribe')
        ->assertSet('done', true)
        ->assertSet('already', false);

    expect(Subscriber::where('email', 'john@example.com')->first()->status)->toBe('subscribed');
});

it('validates the email address', function () {
    Livewire::test(NewsletterSubscribe::class)
        ->set('email', 'not-an-email')
        ->call('subscribe')
        ->assertHasErrors(['email' => 'email'])
        ->assertSet('done', false);
});

it('renders the newsletter box in the ecommerce footer', function () {
    $this->seed(RolePermissionSeeder::class);
    User::factory()->admin()->create();

    Setting::set('site_theme', 'ecommerce');
    Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true]);
    Page::factory()->published()->create(['title' => ['en' => 'Home', 'bn' => ''], 'slug' => 'home', 'sort_order' => 0]);

    $html = get('/')
        ->assertOk()
        ->assertSee('Newsletter')
        ->assertSee('Your email address')
        ->assertSee('Subscribe', false)
        ->getContent();

    // And it lives inside the "Connect with us" column — it is one more way to
    // reach us, not a band of its own above the grid. Asserted by position: the
    // "Connect with us" heading comes first, then this column's own "Newsletter"
    // sub-heading, then the form.
    expect(strpos($html, 'Connect with us'))
        ->toBeLessThan(strpos($html, '>Newsletter<'))
        ->and(strpos($html, '>Newsletter<'))
        ->toBeLessThan(strpos($html, 'newsletter-form'));

    // Three columns instead of four (since the Information column was retired)
    // leave this one wide enough for the component's own default sm:flex-row,
    // so the field and button no longer need to be forced into a stack.
    expect($html)->not->toContain('[&_.newsletter-form]:flex-col');
});

it('renders the newsletter box in the portfolio footer', function () {
    // Same component, same Subscriber row behind it as the ecommerce copy —
    // only the theme it sits in differs, and it has to re-theme itself to this
    // page's light tokens or the field comes out white-on-white.
    $this->seed(RolePermissionSeeder::class);
    User::factory()->admin()->create();

    Setting::set('site_theme', 'portfolio');
    $this->seed(PortfolioMenuSeeder::class);
    Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true]);
    Page::factory()->published()->create(['title' => ['en' => 'Home', 'bn' => ''], 'slug' => 'home', 'sort_order' => 0]);
    SocialLink::factory()->create(['platform' => 'github', 'label' => 'GitHub', 'url' => 'https://github.com/sabbir']);

    $html = get('/')
        ->assertOk()
        ->assertSee('Newsletter')
        ->assertSee('Your email address')
        ->getContent();

    // The scope that re-points the component's --form-* tokens at this theme.
    // Without it the box renders in the ecommerce footer's dark colours.
    expect($html)->toContain('pf-newsletter');

    // And it shares the "Elsewhere" column with the social icons rather than
    // taking a full-width band of its own — the two are the same offer, and a
    // footer that says it twice at two sizes is one too many. The hairline
    // between them is what makes them read as one column.
    expect($html)->toContain('pf-newsletter mt-9 border-t border-(--pf-border) pt-8');

    // And it writes to the same table, from this theme's footer.
    Livewire::test(NewsletterSubscribe::class)
        ->set('email', 'portfolio@example.com')
        ->call('subscribe')
        ->assertSet('done', true);

    $this->assertDatabaseHas('subscribers', [
        'email' => 'portfolio@example.com',
        'status' => 'subscribed',
    ]);
});

it('drops the portfolio footer box when the newsletter feature is switched off', function () {
    // A theme still collecting addresses after the owner turned the feature off
    // is the exact thing the switch exists to prevent, so the block goes with it.
    $this->seed(RolePermissionSeeder::class);
    User::factory()->admin()->create();

    Setting::set('site_theme', 'portfolio');
    $this->seed(PortfolioMenuSeeder::class);
    Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true]);
    Page::factory()->published()->create(['title' => ['en' => 'Home', 'bn' => ''], 'slug' => 'home', 'sort_order' => 0]);

    Feature::create(['key' => 'newsletter', 'label' => 'Newsletter (Subscribers)', 'is_enabled' => false]);

    $this->get('/')
        ->assertOk()
        ->assertDontSee('Your email address')
        ->assertDontSee('pf-newsletter');
});

it('still collects subscribers on a portfolio with no social links saved', function () {
    // The newsletter column is guarded on the newsletter feature, not on the
    // social list: an owner who has never filled in a social URL should still
    // get a working signup box rather than an empty column.
    $this->seed(RolePermissionSeeder::class);
    User::factory()->admin()->create();

    Setting::set('site_theme', 'portfolio');
    $this->seed(PortfolioMenuSeeder::class);
    Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true]);
    Page::factory()->published()->create(['title' => ['en' => 'Home', 'bn' => ''], 'slug' => 'home', 'sort_order' => 0]);

    expect(PortfolioSocials::all())->toBeEmpty();

    get('/')
        ->assertOk()
        ->assertSee('Newsletter')
        ->assertSee('Your email address')
        // No divider to hang off nothing, since there is nothing above it.
        ->assertDontSee('pf-newsletter mt-9');
});
