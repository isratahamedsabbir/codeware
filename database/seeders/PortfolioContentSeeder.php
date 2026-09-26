<?php

namespace Database\Seeders;

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

        // Ships with the repository, so the hero has a real portrait to load and
        // the monogram fallback does not mask a broken path. Point this at an
        // uploaded photo (Admin → Theme Settings → Profile) before going live.
        $this->seedScalar('theme_portfolio_photo', '/default/profile.jpg');

        // See the class docblock: a dead link until a real CV is uploaded.
        $this->seedScalar('theme_portfolio_resume_url', '/resume.pdf');
        $this->seedScalar('theme_portfolio_resume_label', 'Download CV');

        $this->seedSetting('theme_portfolio_stats', [
            ['value' => '5+', 'label' => 'Years shipping software'],
            ['value' => '40+', 'label' => 'Projects delivered'],
            ['value' => '3', 'label' => 'CMS & store migrations'],
        ]);

        // Three cards is what fills a desktop row exactly; the theme's settings
        // screen caps this at nine for anyone who wants more.
        $this->seedSetting('theme_portfolio_services', [
            [
                'title' => 'Laravel Application Development',
                'icon' => '⚙️',
                'description' => 'Admin panels, REST APIs and queued jobs — built to survive being extended by the next developer.',
            ],
            [
                'title' => 'Storefronts & Interfaces',
                'icon' => '🖥️',
                'description' => 'Livewire for server-driven screens, or a React storefront talking to the same Laravel API.',
            ],
            [
                'title' => 'WordPress & WooCommerce',
                'icon' => '📝',
                'description' => 'Custom themes, plugin work, and store builds or migrations that keep the data they already have.',
            ],
        ]);

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

    private function seedProjects(): void
    {
        // `tech` is one comma-separated field rather than a nested list: a
        // repeater row is flat (the admin hydrates every value to a scalar), and
        // App\Support\PortfolioProfile::splitList() splits it back into chips.
        $this->seedSetting('theme_portfolio_projects', [
            [
                'title' => 'SaaS Starter Platform',
                'description' => 'Multi-tenant SaaS boilerplate with subscription billing, team management, and role-based access.',
                'icon' => '🚀',
                'tech' => 'Laravel, Livewire, Stripe, Redis',
                'stats' => 'Open Source',
                'link' => '',
            ],
            [
                'title' => 'Hotel Booking System',
                'description' => 'Multi-property booking platform with real-time availability, payments, and guest messaging.',
                'icon' => '🏨',
                'tech' => 'Laravel, MySQL, Stripe, Pusher',
                'stats' => 'In Production',
                'link' => '',
            ],
            [
                'title' => 'Storefront & Admin Split',
                'description' => 'React storefront talking to a Laravel REST API, with a WordPress blog on the same domain.',
                'icon' => '⚛️',
                'tech' => 'React, Laravel, MySQL, WordPress',
                'stats' => 'In Production',
                'link' => '',
            ],
            [
                'title' => 'Freelance Marketplace',
                'description' => 'Service marketplace with subscription plans, escrow payments, and a dispute resolution center.',
                'icon' => '💼',
                'tech' => 'Laravel, Livewire, MySQL',
                'stats' => 'Beta',
                'link' => '',
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
                'period' => '2024 - Present',
                'description' => 'Building and maintaining web applications end to end, from database design through to deployment.',
            ],
            [
                'role' => 'Backend Developer',
                'company' => '',
                'period' => '2022 - 2024',
                'description' => 'Designed RESTful APIs and tuned database queries for traffic that outgrew the first design.',
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
     * The names here are not clients, so the owner has to replace every row
     * before this page is public — which the settings screen says out loud on
     * the field itself.
     */
    private function seedTestimonials(): void
    {
        $this->seedSetting('theme_portfolio_testimonials', [
            [
                'quote' => 'Took a half-finished Laravel app and turned it into something we could actually sell. The handover doc alone was worth it.',
                'name' => 'Sample Client',
                'role' => 'Founder, Example Ltd',
                'rating' => '5',
            ],
            [
                'quote' => 'Clear communication, no surprises in the estimates, and the handover included everything our next developer needed.',
                'name' => 'Sample Client',
                'role' => 'Product Lead, Example Co',
                'rating' => '5',
            ],
            [
                'quote' => 'Rebuilt our store without losing a single order. Went live on a Friday and rolled back twice by Monday, unprompted.',
                'name' => 'Sample Client',
                'role' => 'Owner, Example Store',
                'rating' => '4',
            ],
        ]);
    }
}
