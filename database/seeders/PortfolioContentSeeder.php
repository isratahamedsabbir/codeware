<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * The portfolio theme's demo content.
 *
 * All of it is a key/value setting now, edited from Admin → Theme Settings. The
 * one-pager used to hardcode most of these as arrays in home.blade.php, which
 * meant a fresh install told visitors "Your University" and "Training Institute";
 * projects, experience and skills then got their own tables and admin CRUD
 * screens, which split the portfolio across four places to edit it. This seeder
 * writes one JSON list per section so the whole page is one screen and one save.
 *
 * Idempotent: every write goes through seedSetting()/seedScalar(), which skip a
 * key that already holds something, so re-seeding tops up a half-configured
 * install without clobbering what the owner has since written.
 */
class PortfolioContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedProfile();
        $this->seedServices();
        $this->seedProjects();
        $this->seedExperiences();
        $this->seedSkills();
        $this->seedTestimonials();
    }

    /**
     * The hero, the trust strip, "what I do", education and certifications — read
     * by the theme as plain settings through App\Support\PortfolioProfile and
     * edited by the owner from Admin → Theme Settings.
     *
     * This is *demo* content, in the same spirit as the lists below: it exists so
     * a fresh install shows a complete, working page rather than a shell, and
     * every line of it is meant to be replaced. Two of these keys in particular
     * are claims about a person rather than copy, and shipping them unfilled
     * would be a false statement on a CV:
     *
     *   - theme_portfolio_education / _certifications — a degree and a certificate
     *     the owner does not hold. Seeded anyway so the Credentials tab of the
     *     settings screen is demonstrably wired up, which is the only way to see
     *     that the repeater UI works before trusting it with a real degree.
     *   - theme_portfolio_resume_url — points at a path that is not there, so the
     *     hero's download button is visible but 404s. Upload a real CV and replace
     *     it, or clear the field and the button disappears.
     *
     * Only written when the key is absent. A setting cannot be "keyed on its
     * title" the way a table row can, so the only safe test is whether the key
     * already holds something; getting that wrong would overwrite real copy the
     * owner has since written, and re-running the seeder must never do that.
     */
    private function seedProfile(): void
    {
        $this->seedScalar('theme_portfolio_name', 'Sabbir Hossain');
        $this->seedScalar('theme_portfolio_hero_title', 'Full Stack Developer');
        $this->seedScalar(
            'theme_portfolio_hero_tagline',
            'I build Laravel applications and the storefronts that talk to them — admin panels, REST APIs, and WordPress or WooCommerce work on top.'
        );
        $this->seedScalar('theme_portfolio_location', 'Dhaka, Bangladesh');
        $this->seedScalar('theme_portfolio_availability', 'Available for new projects');

        // Deliberately blank. The obvious thing to seed here is a stock portrait
        // borrowed from another theme, and that is exactly the problem: the hero
        // then shows a stranger's face at full size to whoever opens the page.
        // Blank renders the theme's initials monogram instead, which reads as a
        // deliberate choice rather than a missing file. Upload a real portrait in
        // Admin → Theme Settings → Profile and it takes over.
        $this->seedScalar('theme_portfolio_photo', '');

        // Also deliberately blank, for a sharper version of the same reason: a
        // CV link that 404s is worse than no CV link, because the one thing it
        // costs is the recruiter's first impression. This button is the single
        // highest-value thing on the page to fill in — set the URL once the PDF is
        // uploaded and the button appears by itself. See the class docblock.
        $this->seedScalar('theme_portfolio_resume_url', '');
        $this->seedScalar('theme_portfolio_resume_label', 'Download CV');

        $this->seedSetting('theme_portfolio_stats', [
            ['value' => '5+', 'label' => 'Years shipping software'],
            ['value' => '40+', 'label' => 'Projects delivered'],
            ['value' => '3', 'label' => 'CMS & store migrations'],
        ]);

        // Services are not seeded here any more. They used to be a
        // theme_portfolio_services repeater, but a service is something a visitor
        // can book, so it has to be a real Service row a booking can point a
        // foreign key at — and that is edited under Admin → Services. See
        // seedServices() below for the rows that make a fresh install look
        // finished without a second place to maintain them.

        $this->seedSetting('theme_portfolio_education', [
            [
                'title' => 'B.Sc. in Computer Science & Engineering',
                'period' => '2016 — 2020',
                'description' => 'Example University',
            ],
            [
                'title' => 'Higher Secondary, Science',
                'period' => '2014 — 2016',
                'description' => 'Example College',
            ],
        ]);

        $this->seedSetting('theme_portfolio_certifications', [
            [
                'title' => 'AWS Certified Developer – Associate',
                'period' => '2024',
                'description' => 'Amazon Web Services',
            ],
            [
                'title' => 'Meta Front-End Developer',
                'period' => '2023',
                'description' => 'Meta',
            ],
        ]);
    }

    /**
     * Seed a single text setting, leaving an existing value alone.
     */
    private function seedScalar(string $key, string $value): void
    {
        if ($this->hasContent($key)) {
            return;
        }

        Setting::set($key, $value);
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    private function seedSetting(string $key, array $rows): void
    {
        if ($this->hasContent($key)) {
            return;
        }

        Setting::set($key, json_encode($rows));
    }

    /**
     * Whether this key already holds something the owner put there.
     *
     * Blank counts as empty, and that is the whole point: the theme's own settings
     * screen creates a row for every bound field on first save, so "the row
     * exists" says nothing about whether anyone filled it in. Treating a blank
     * row as absent is what lets a re-seed top up a half-configured install
     * without touching the fields that were genuinely filled in — and it is the
     * rule App\Support\PortfolioProfile already applies when it decides a field
     * has nothing to show.
     */
    private function hasContent(string $key): bool
    {
        $raw = trim((string) Setting::get($key, ''));

        if ($raw === '') {
            return false;
        }

        // The repeaters store a JSON list; "[]" and a JSON object both mean the
        // owner has not entered any rows.
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded !== [] : true;
    }

    /**
     * The "What I do" services, as real Service rows.
     *
     * These were a theme_portfolio_services repeater until the storefront gained
     * booking. A bookable thing has to be a row a booking can point a foreign key
     * at, which a JSON list in the settings table cannot be, so the list moved to
     * the Service model and its own admin CRUD screen (Admin → Services).
     *
     * Only written when the table is empty. Unlike the settings repeaters, a
     * service is a real record with a slug, so "does one already exist" is a
     * question the database can answer directly — and the answer has to be
     * consulted, because re-running this seeder against a site whose owner has
     * written their own services must not overwrite them with demo copy. That is
     * the same promise the other seed methods here make, and it is why the guard
     * is on the table rather than on any one key.
     *
     * price is left at 0 on all three, which the storefront reads as "no price
     * shown" rather than printing a free service nobody offered. featured_image is
     * left null for the same reason the project screenshots are: an image that is
     * not a real picture of the real work is worse than no image.
     */
    private function seedServices(): void
    {
        if (Service::withTrashed()->exists()) {
            return;
        }

        $services = [
            [
                'name' => 'Laravel Application Development',
                'description' => 'Admin panels, REST APIs and queued jobs — built to survive being extended by the next developer.',
            ],
            [
                'name' => 'Storefronts & Interfaces',
                'description' => 'Livewire for server-driven screens, or a React storefront talking to the same Laravel API.',
            ],
            [
                'name' => 'WordPress & WooCommerce',
                'description' => 'Custom themes, plugin work, and store builds or migrations that keep the data they already have.',
            ],
        ];

        foreach ($services as $index => $service) {
            // Both locales, the way the translatable factories do it: the en value
            // is the real one and bn is deliberately empty rather than machine
            // translated, so the storefront falls back to en instead of showing
            // copy nobody wrote.
            Service::create([
                'name' => ['en' => $service['name'], 'bn' => ''],
                'description' => ['en' => $service['description'], 'bn' => ''],
                'status' => 'active',
                'sort_order' => $index,
            ]);
        }
    }

    private function seedProjects(): void
    {
        // `tech` is one comma-separated field rather than a nested list: a
        // repeater row is flat (the admin hydrates every value to a scalar), and
        // App\Support\PortfolioProfile::splitList() splits it back into chips.
        //
        // `image`, `link` and `repo` are deliberately blank. A screenshot has to
        // be a real screen of the real app and a link has to resolve, so seeding
        // placeholders here would put a broken image or a dead URL on a page
        // meant to be read by a hiring manager. Blank is honest: the card renders
        // without the image and the card's links disappear entirely. Upload the
        // shots and paste the URLs from Admin → Theme Settings.
        $this->seedSetting('theme_portfolio_projects', [
            [
                'title' => 'Order & Inventory Platform',
                'description' => 'Stock control for a three-branch retailer, replacing a shared spreadsheet that was the single point of failure. Every sale decrements stock inside a transaction, and a low-stock threshold is what drives reordering — not a weekly report someone had to remember to run.',
                'icon' => '📦',
                'tech' => 'Laravel 11, Livewire 3, MySQL, Redis',
                'stats' => 'In production',
                'link' => '',
                'repo' => '',
                'image' => '',
            ],
            [
                'title' => 'Clinic Booking & Records',
                'description' => 'Appointment scheduling, patient history and printable prescription PDFs for a two-doctor practice. The scheduling screen is built around the one question that actually matters — who is free in this half-hour — so double-booking is impossible rather than merely discouraged.',
                'icon' => '🩺',
                'tech' => 'Laravel, MySQL, dompdf, Alpine.js',
                'stats' => 'In production',
                'link' => '',
                'repo' => '',
                'image' => '',
            ],
            [
                'title' => 'Headless Storefront on a Laravel API',
                'description' => 'A Next.js catalog and cart in front of a Laravel REST API, with token auth and the order pipeline kept server-side. Moving the storefront off the theme was what let the same catalogue data serve the site, a POS and a marketplace feed without three copies of the truth.',
                'icon' => '⚛️',
                'tech' => 'Next.js 15, Laravel, Sanctum, Tailwind',
                'stats' => 'In production',
                'link' => '',
                'repo' => '',
                'image' => '',
            ],
            [
                'title' => 'WooCommerce Store Rescue',
                'description' => 'A slow, plugin-heavy store brought back to a usable load time by object caching, query cleanup and a rebuilt checkout — with the order history migrated across intact. The brief was a performance problem; the thing that actually unlocked the fix was tracing where the slow requests were coming from rather than guessing at plugins.',
                'icon' => '🛒',
                'tech' => 'WordPress, WooCommerce, Redis, Cloudflare',
                'stats' => 'In production',
                'link' => '',
                'repo' => '',
                'image' => '',
            ],
        ]);
    }

    private function seedExperiences(): void
    {
        // No placeholder company names. "Your Company" is the same failure as the
        // hardcoded "Your University" the hero used to ship: a stranger reads it
        // and learns nothing about the person, and the owner has to remember to
        // delete it. A blank company renders as no company line at all, which is
        // an honest gap and one the owner is going to fill anyway.
        $this->seedSetting('theme_portfolio_experiences', [
            [
                'role' => 'Full Stack Developer',
                'company' => '',
                'period' => '2023 — Present',
                'description' => 'Taking Laravel applications from schema to deployment and maintaining them afterwards. Most of the week is spent on the unglamorous half of the job: reading a slow query, untangling a requirement that changed halfway through, and writing the migration that makes the next change boring.',
            ],
            [
                'role' => 'Backend Developer',
                'company' => '',
                'period' => '2021 — 2023',
                'description' => 'Built the REST APIs and admin panels other teams depended on. Learned to treat a database index and a clear error message as features, because both are what a user experiences when something goes wrong at 2am.',
            ],
            [
                'role' => 'WordPress & WooCommerce Developer',
                'company' => '',
                'period' => '2020 — 2021',
                'description' => 'Store builds, plugin work and migrations for small retailers — usually taking a site that had grown one plugin at a time and giving it a shape it could keep growing in.',
            ],
        ]);
    }

    private function seedSkills(): void
    {
        $this->seedSetting('theme_portfolio_skills', [
            ['name' => 'Laravel', 'group' => 'Backend', 'icon' => '🔴', 'description' => 'Framework of choice'],
            ['name' => 'PHP', 'group' => 'Backend', 'icon' => '🐘', 'description' => 'Modern PHP 8+'],
            ['name' => 'MySQL', 'group' => 'Backend', 'icon' => '🗄️', 'description' => 'Schema & query tuning'],
            ['name' => 'REST API', 'group' => 'Backend', 'icon' => '🔌', 'description' => 'Versioned, documented'],
            ['name' => 'Redis', 'group' => 'Backend', 'icon' => '⚡', 'description' => 'Caching & queues'],
            ['name' => 'React', 'group' => 'Frontend', 'icon' => '⚛️', 'description' => 'Components & hooks'],
            ['name' => 'Livewire', 'group' => 'Frontend', 'icon' => '💚', 'description' => 'Server-driven UI'],
            ['name' => 'Alpine.js', 'group' => 'Frontend', 'icon' => '🏔️', 'description' => 'Lightweight JS'],
            ['name' => 'Tailwind CSS', 'group' => 'Frontend', 'icon' => '🎨', 'description' => 'Utility styling'],
            ['name' => 'JavaScript', 'group' => 'Frontend', 'icon' => '📜', 'description' => 'ES2023+'],
            ['name' => 'WordPress', 'group' => 'CMS', 'icon' => '📝', 'description' => 'Custom themes & plugins'],
            ['name' => 'WooCommerce', 'group' => 'CMS', 'icon' => '🛒', 'description' => 'Store builds & migrations'],
            ['name' => 'Docker', 'group' => 'DevOps & Cloud', 'icon' => '🐳', 'description' => 'Containerization'],
            ['name' => 'Git', 'group' => 'DevOps & Cloud', 'icon' => '📦', 'description' => 'Version control'],
            ['name' => 'CI/CD', 'group' => 'DevOps & Cloud', 'icon' => '🔄', 'description' => 'Automated deployment'],
            ['name' => 'Linux', 'group' => 'DevOps & Cloud', 'icon' => '🐧', 'description' => 'Server administration'],
            ['name' => 'AWS', 'group' => 'DevOps & Cloud', 'icon' => '☁️', 'description' => 'EC2, S3, deployment'],
        ]);
    }

    /**
     * Demo testimonials.
     *
     * Seeded for the same reason as the education rows: it is the only way to see
     * the section and its repeater working before trusting it with a real quote.
     *
     * Every attribution here is a fill-in-the-blank slot, not a citation: the
     * names and companies read as obviously unfinished on purpose. A seeded row
     * saying "Sample Client, Founder, Example Ltd" is one an owner might leave
     * in place by accident, and a page asserting praise from a person who never
     * said it is the one mistake on a portfolio that actually costs you a job.
     * Delete these rows in Admin → Theme Settings before this page goes anywhere.
     */
    private function seedTestimonials(): void
    {
        $this->seedSetting('theme_portfolio_testimonials', [
            [
                'quote' => 'Replace this with a real quote: what the client said about the work, in their own words. One or two sentences is plenty.',
                'name' => 'Client name',
                'role' => 'Their role, Company',
                'rating' => '5',
            ],
            [
                'quote' => 'Replace this with a second real quote. The most convincing ones name a specific problem and what changed after you fixed it.',
                'name' => 'Client name',
                'role' => 'Their role, Company',
                'rating' => '5',
            ],
            [
                'quote' => 'Replace this with a third real quote, or delete this row entirely — three is a number, one honest one is better than three thin ones.',
                'name' => 'Client name',
                'role' => 'Their role, Company',
                'rating' => '',
            ],
        ]);
    }
}
