<?php

namespace Database\Seeders;

use App\Models\JobPost;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class JobPostSeeder extends Seeder
{
    /** Demo vacancies for the Careers page. Idempotent: rows are matched by slug. */
    public function run(): void
    {
        $jobs = [
            [
                'title' => 'Senior Laravel Developer',
                'department' => 'Engineering',
                'location' => 'Dhaka, Bangladesh',
                'employment_type' => 'full-time',
                'vacancy' => 2,
                'deadline' => now()->addDays(30),
                'description' => "We are looking for an experienced Laravel developer to build and maintain our e-commerce platform and REST APIs.\n\nYou will work closely with the frontend and product teams, review code, and help shape our engineering practices.",
                'requirements' => "- 4+ years of professional PHP / Laravel experience\n- Strong knowledge of MySQL and query optimisation\n- Experience with Livewire, REST APIs and queues\n- Comfortable writing automated tests (Pest / PHPUnit)\n- Good communication skills in English",
            ],
            [
                'title' => 'Frontend Developer (React / Next.js)',
                'department' => 'Engineering',
                'location' => 'Dhaka, Bangladesh',
                'employment_type' => 'full-time',
                'vacancy' => 1,
                'deadline' => now()->addDays(21),
                'description' => "Join us to build fast, accessible storefront experiences with Next.js and Tailwind CSS.\n\nYou will turn designs into polished UI and work with our API team on data fetching and state management.",
                'requirements' => "- 2+ years of React experience, Next.js preferred\n- Solid HTML, CSS (Tailwind) and TypeScript\n- Experience with TanStack Query or similar\n- An eye for detail and responsive design",
            ],
            [
                'title' => 'UI/UX Designer',
                'department' => 'Design',
                'location' => 'Remote',
                'employment_type' => 'contract',
                'vacancy' => 1,
                'deadline' => now()->addDays(14),
                'description' => "We need a designer to craft clean, conversion-focused interfaces for our storefront and admin panel.\n\nYou will own the process from wireframes to final handoff.",
                'requirements' => "- Portfolio showing web / e-commerce work\n- Proficiency in Figma\n- Understanding of design systems and accessibility",
            ],
            [
                'title' => 'Digital Marketing Executive',
                'department' => 'Marketing',
                'location' => 'Chattogram, Bangladesh',
                'employment_type' => 'full-time',
                'vacancy' => 3,
                'deadline' => now()->addDays(25),
                'description' => "Plan and run campaigns across social media, search and email to grow our online sales.\n\nYou will track performance and report on results every week.",
                'requirements' => "- 1+ year in digital marketing\n- Experience with Facebook Ads and Google Ads\n- Basic analytics skills\n- Strong written Bangla and English",
            ],
            [
                'title' => 'Customer Support Intern',
                'department' => 'Support',
                'location' => 'Dhaka, Bangladesh',
                'employment_type' => 'internship',
                'vacancy' => 2,
                'deadline' => null,
                'description' => "Help our customers with orders, deliveries and returns over chat, phone and email.\n\nA great first step into a career in e-commerce operations.",
                'requirements' => "- Currently studying or recently graduated\n- Clear communication and a friendly attitude\n- Willingness to learn quickly",
            ],
            [
                'title' => 'Warehouse Supervisor',
                'department' => 'Operations',
                'location' => 'Gazipur, Bangladesh',
                'employment_type' => 'full-time',
                'vacancy' => 1,
                'deadline' => now()->addDays(10),
                'description' => 'Oversee daily warehouse operations: receiving, inventory, packing and dispatch.',
                'requirements' => "- 3+ years in warehouse or logistics\n- Experience managing a small team\n- Basic inventory software knowledge",
            ],
        ];

        foreach ($jobs as $i => $job) {
            JobPost::updateOrCreate(
                ['slug' => Str::slug($job['title'])],
                $job + ['status' => 'open', 'sort_order' => $i],
            );
        }
    }
}
