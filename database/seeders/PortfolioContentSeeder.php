<?php

namespace Database\Seeders;

use App\Models\PortfolioExperience;
use App\Models\PortfolioProject;
use App\Models\PortfolioSkill;
use Illuminate\Database\Seeder;

/**
 * The portfolio theme's showcase content, moved out of the hardcoded arrays that
 * used to sit in its home.blade.php and into the three admin-editable tables.
 * Seeded *active* (unlike the other content seeders) so a fresh install renders
 * a complete-looking portfolio; delete or deactivate a row from /admin/portfolio
 * to change what the theme shows.
 *
 * Idempotent: keyed on the primary-locale title/name/role, so re-seeding restores
 * the placeholder rows without clobbering edits an admin has since made.
 */
class PortfolioContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedProjects();
        $this->seedExperiences();
        $this->seedSkills();
    }

    private function seedProjects(): void
    {
        $projects = [
            ['icon' => '🚀', 'title' => 'SaaS Starter Platform', 'description' => 'Multi-tenant SaaS boilerplate with subscription billing, team management, and role-based access.', 'tech' => ['Laravel', 'Livewire', 'Stripe', 'Redis'], 'stats' => 'Open Source'],
            ['icon' => '🏨', 'title' => 'Hotel Booking System', 'description' => 'Multi-property booking platform with real-time availability, payments, and guest messaging.', 'tech' => ['Laravel', 'MySQL', 'Stripe', 'Pusher'], 'stats' => 'In Production'],
            ['icon' => '💼', 'title' => 'Freelance Marketplace', 'description' => 'Service marketplace with subscription plans, escrow payments, and a dispute resolution center.', 'tech' => ['Laravel', 'Livewire', 'MySQL'], 'stats' => 'Beta'],
            ['icon' => '⚙️', 'title' => 'Inventory Automation', 'description' => 'Warehouse tracking system with barcode scanning, live dashboards, and predictive restocking alerts.', 'tech' => ['PHP', 'MySQL', 'JavaScript'], 'stats' => 'Internal Tool'],
        ];

        foreach ($projects as $sortOrder => $project) {
            PortfolioProject::firstOrCreate(
                ['title->en' => $project['title']],
                [
                    'description' => ['en' => $project['description']],
                    'icon' => $project['icon'],
                    'tech' => $project['tech'],
                    'stats' => $project['stats'],
                    'status' => 'active',
                    'sort_order' => $sortOrder + 1,
                ],
            );
        }
    }

    private function seedExperiences(): void
    {
        $experiences = [
            ['role' => 'Full Stack Developer', 'company' => 'Your Company', 'period' => '2024 - Present', 'description' => 'Building and maintaining scalable web applications, owning features end-to-end from database design to deployment.'],
            ['role' => 'Backend Developer', 'company' => 'Previous Company', 'period' => '2022 - 2024', 'description' => 'Designed RESTful APIs and optimized database performance for high-traffic applications.'],
        ];

        foreach ($experiences as $sortOrder => $experience) {
            PortfolioExperience::firstOrCreate(
                ['role->en' => $experience['role']],
                [
                    'company' => ['en' => $experience['company']],
                    'period' => $experience['period'],
                    'description' => ['en' => $experience['description']],
                    'status' => 'active',
                    'sort_order' => $sortOrder + 1,
                ],
            );
        }
    }

    private function seedSkills(): void
    {
        $groups = [
            'Backend' => [
                ['name' => 'Laravel', 'icon' => '🔴', 'description' => 'Advanced Framework'],
                ['name' => 'PHP', 'icon' => '🐘', 'description' => 'Modern PHP 8+'],
                ['name' => 'MySQL', 'icon' => '🗄️', 'description' => 'Database Optimization'],
                ['name' => 'REST API', 'icon' => '🔌', 'description' => 'Scalable Architecture'],
                ['name' => 'Redis', 'icon' => '⚡', 'description' => 'Caching & Queues'],
            ],
            'Frontend & Tools' => [
                ['name' => 'Livewire', 'icon' => '💚', 'description' => 'Reactive UI'],
                ['name' => 'Alpine.js', 'icon' => '🏔️', 'description' => 'Lightweight JS'],
                ['name' => 'Tailwind CSS', 'icon' => '🎨', 'description' => 'Modern Styling'],
                ['name' => 'Docker', 'icon' => '🐳', 'description' => 'Containerization'],
                ['name' => 'Git', 'icon' => '📦', 'description' => 'Version Control'],
            ],
            'DevOps & Cloud' => [
                ['name' => 'AWS', 'icon' => '☁️', 'description' => 'EC2, S3, Deployment'],
                ['name' => 'VPS Hosting', 'icon' => '🖥️', 'description' => 'Server Management'],
                ['name' => 'CI/CD', 'icon' => '🔄', 'description' => 'Automated Deployment'],
                ['name' => 'Linux', 'icon' => '🐧', 'description' => 'Server Administration'],
            ],
        ];

        $sortOrder = 0;

        foreach ($groups as $group => $skills) {
            foreach ($skills as $skill) {
                $sortOrder++;

                PortfolioSkill::firstOrCreate(
                    ['name->en' => $skill['name'], 'group' => $group],
                    [
                        'icon' => $skill['icon'],
                        'description' => ['en' => $skill['description']],
                        'status' => 'active',
                        'sort_order' => $sortOrder,
                    ],
                );
            }
        }
    }
}
