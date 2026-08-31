<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
    <link rel="stylesheet" href="{{ asset('themes/portfolio/style.css') }}">
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="theme-portfolio antialiased">

    @php
        $siteName = \App\Models\Setting::get('site_name', config('app.name'));
        $siteIcon = \App\Models\Setting::get('site_icon_white') ?: \App\Models\Setting::get('site_icon');
        $contactEmail = \App\Models\Setting::get('contact_email');
        $contactAddress = \App\Models\Setting::get('contact_address');
        $socialIcons = [
            'facebook_url' => ['abbr' => 'FB', 'label' => 'Facebook'],
            'twitter_url' => ['abbr' => 'X', 'label' => 'Twitter'],
            'instagram_url' => ['abbr' => 'IG', 'label' => 'Instagram'],
            'youtube_url' => ['abbr' => 'YT', 'label' => 'YouTube'],
            'linkedin_url' => ['abbr' => 'IN', 'label' => 'LinkedIn'],
            'tiktok_url' => ['abbr' => 'TT', 'label' => 'TikTok'],
        ];
        $socials = collect($socialIcons)
            ->map(fn ($meta, $key) => ['url' => \App\Models\Setting::get($key), 'abbr' => $meta['abbr'], 'label' => $meta['label']])
            ->filter(fn ($social) => filled($social['url']))
            ->values();

        // Placeholder showcase content — swap these arrays out (or wire them to
        // CMS sections) once real project/experience/skills data is ready.
        $projects = [
            ['icon' => '🚀', 'title' => 'SaaS Starter Platform', 'description' => 'Multi-tenant SaaS boilerplate with subscription billing, team management, and role-based access.', 'tech' => ['Laravel', 'Livewire', 'Stripe', 'Redis'], 'stats' => 'Open Source', 'link' => '#'],
            ['icon' => '🏨', 'title' => 'Hotel Booking System', 'description' => 'Multi-property booking platform with real-time availability, payments, and guest messaging.', 'tech' => ['Laravel', 'MySQL', 'Stripe', 'Pusher'], 'stats' => 'In Production', 'link' => '#'],
            ['icon' => '💼', 'title' => 'Freelance Marketplace', 'description' => 'Service marketplace with subscription plans, escrow payments, and a dispute resolution center.', 'tech' => ['Laravel', 'Livewire', 'MySQL'], 'stats' => 'Beta', 'link' => '#'],
            ['icon' => '⚙️', 'title' => 'Inventory Automation', 'description' => 'Warehouse tracking system with barcode scanning, live dashboards, and predictive restocking alerts.', 'tech' => ['PHP', 'MySQL', 'JavaScript'], 'stats' => 'Internal Tool', 'link' => '#'],
        ];

        $experience = [
            ['role' => 'Full Stack Developer', 'company' => 'Your Company', 'period' => '2024 - Present', 'description' => 'Building and maintaining scalable web applications, owning features end-to-end from database design to deployment.'],
            ['role' => 'Backend Developer', 'company' => 'Previous Company', 'period' => '2022 - 2024', 'description' => 'Designed RESTful APIs and optimized database performance for high-traffic applications.'],
        ];

        $education = [
            ['degree' => 'B.Sc. in Computer Science', 'school' => 'Your University', 'period' => '2018 - 2022', 'description' => ''],
            ['degree' => 'Web Development Bootcamp', 'school' => 'Training Institute', 'period' => '2022', 'description' => ''],
        ];

        $certifications = [
            ['title' => 'AWS Certified Developer', 'issuer' => 'Amazon Web Services', 'date' => '2023'],
        ];

        $skillGroups = [
            ['title' => 'Backend', 'skills' => [
                ['name' => 'Laravel', 'icon' => '🔴', 'description' => 'Advanced Framework'],
                ['name' => 'PHP', 'icon' => '🐘', 'description' => 'Modern PHP 8+'],
                ['name' => 'MySQL', 'icon' => '🗄️', 'description' => 'Database Optimization'],
                ['name' => 'REST API', 'icon' => '🔌', 'description' => 'Scalable Architecture'],
                ['name' => 'Redis', 'icon' => '⚡', 'description' => 'Caching & Queues'],
            ]],
            ['title' => 'Frontend & Tools', 'skills' => [
                ['name' => 'Livewire', 'icon' => '💚', 'description' => 'Reactive UI'],
                ['name' => 'Alpine.js', 'icon' => '🏔️', 'description' => 'Lightweight JS'],
                ['name' => 'Tailwind CSS', 'icon' => '🎨', 'description' => 'Modern Styling'],
                ['name' => 'Docker', 'icon' => '🐳', 'description' => 'Containerization'],
                ['name' => 'Git', 'icon' => '📦', 'description' => 'Version Control'],
            ]],
            ['title' => 'DevOps & Cloud', 'skills' => [
                ['name' => 'AWS', 'icon' => '☁️', 'description' => 'EC2, S3, Deployment'],
                ['name' => 'VPS Hosting', 'icon' => '🖥️', 'description' => 'Server Management'],
                ['name' => 'CI/CD', 'icon' => '🔄', 'description' => 'Automated Deployment'],
                ['name' => 'Linux', 'icon' => '🐧', 'description' => 'Server Administration'],
            ]],
        ];
    @endphp

    <header class="fixed inset-x-0 top-0 z-20 border-b border-(--pf-border) bg-(--pf-bg)/80 backdrop-blur-md">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            <a href="{{ url('/') }}" class="pf-heading flex items-center gap-2">
                @if ($siteIcon)
                    <img src="{{ $siteIcon }}" alt="{{ $siteName }}" class="h-7 w-auto">
                @endif
                <span class="text-base font-bold tracking-tight">{{ $siteName }}</span>
            </a>

            <nav class="hidden items-center gap-8 md:flex">
                @foreach ($menuItems ?? [] as $menuItem)
                    <a href="{{ url($menuItem->url) }}"
                        class="pf-nav-link pf-mono text-xs uppercase tracking-widest {{ url($menuItem->url) === url()->current() ? 'is-active' : '' }}">
                        {{ $menuItem->label }}
                    </a>
                @endforeach
            </nav>

            <div class="flex items-center gap-3">
                <div class="pf-lang-pill pf-mono text-xs font-semibold uppercase">
                    <a href="{{ request()->fullUrlWithQuery(['lang' => 'en']) }}" class="{{ app()->getLocale() === 'en' ? 'is-active' : '' }}">EN</a>
                    <a href="{{ request()->fullUrlWithQuery(['lang' => 'bn']) }}" class="{{ app()->getLocale() === 'bn' ? 'is-active' : '' }}">BN</a>
                </div>

                <button type="button" data-pf-theme-toggle aria-label="Toggle light / dark theme" class="pf-theme-toggle">
                    <svg class="pf-icon-sun h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="4" />
                        <path stroke-linecap="round" d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41m11.32-11.32 1.41-1.41" />
                    </svg>
                    <svg class="pf-icon-moon h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z" />
                    </svg>
                </button>
            </div>
        </div>
    </header>

    <main>
        {{-- Hero --}}
        <section id="home" class="pf-grid-bg relative flex min-h-screen items-center overflow-hidden px-6 pt-24">
            <div class="mx-auto max-w-4xl text-center">
                <span class="pf-badge pf-animate pf-mono mb-6 inline-flex rounded-full px-4 py-2 text-xs font-medium" style="animation-delay:100ms">
                    <svg class="mr-2 h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="16 18 22 12 16 6" />
                        <polyline points="8 6 2 12 8 18" />
                    </svg>
                    Full Stack Developer
                </span>

                <h1 class="pf-heading pf-animate text-4xl font-extrabold leading-tight tracking-tight sm:text-6xl" style="animation-delay:200ms">
                    Hi, I'm
                    <span data-typewriter class="pf-gradient-text whitespace-nowrap" style="visibility:hidden">{{ $siteName }}</span>
                </h1>

                <p class="pf-animate mx-auto mt-6 max-w-xl text-lg text-(--pf-text-muted) sm:text-xl" style="animation-delay:300ms">
                    Building fast, reliable, and scalable web applications with modern tools. Passionate about clean code and thoughtful design.
                </p>

                <div class="pf-animate mt-10 flex flex-wrap justify-center gap-4" style="animation-delay:400ms">
                    <a href="#projects" class="pf-btn-solid pf-mono rounded-full px-8 py-3 text-sm font-semibold uppercase tracking-wide transition hover:-translate-y-0.5">
                        View Projects
                    </a>
                    <a href="#contact" class="pf-btn-outline pf-mono rounded-full px-8 py-3 text-sm font-semibold uppercase tracking-wide transition hover:-translate-y-0.5">
                        Contact Me
                    </a>
                </div>

                @if ($socials->isNotEmpty() || $contactEmail)
                    <div class="pf-animate mt-10 flex justify-center gap-3" style="animation-delay:500ms">
                        @foreach ($socials as $social)
                            <a href="{{ $social['url'] }}" target="_blank" rel="noopener" aria-label="{{ $social['abbr'] }}"
                                class="pf-social-icon pf-mono text-[11px] font-bold">
                                {{ $social['abbr'] }}
                            </a>
                        @endforeach
                        @if ($contactEmail)
                            <a href="mailto:{{ $contactEmail }}" aria-label="Email" class="pf-social-icon">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="5" width="18" height="14" rx="2" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m3 7 9 6 9-6" />
                                </svg>
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </section>

        {{-- Projects --}}
        <section id="projects" class="border-t border-(--pf-border) bg-(--pf-bg-elevated)/40 px-6 py-24">
            <div class="mx-auto max-w-6xl">
                <div class="mx-auto mb-14 max-w-xl text-center">
                    <p class="pf-mono mb-3 text-xs text-(--pf-text-muted)">// 01</p>
                    <h2 class="pf-heading text-3xl font-bold tracking-tight sm:text-4xl">Featured Projects</h2>
                    <p class="mt-4 text-(--pf-text-muted)">Selected projects showcasing scalable architecture, clean code, and thoughtful user experience.</p>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    @foreach ($projects as $project)
                        <div class="pf-card group flex flex-col rounded-xl p-6">
                            <div class="flex items-start justify-between gap-3">
                                <span class="text-3xl">{{ $project['icon'] }}</span>
                                <span class="pf-chip pf-mono rounded-full px-3 py-1 text-[11px]">{{ $project['stats'] }}</span>
                            </div>
                            <h3 class="pf-heading mt-4 text-lg font-semibold">{{ $project['title'] }}</h3>
                            <p class="mt-2 flex-1 text-sm text-(--pf-text-muted)">{{ $project['description'] }}</p>
                            <div class="mt-4 flex flex-wrap gap-2">
                                @foreach ($project['tech'] as $tech)
                                    <span class="pf-chip pf-mono rounded-full px-2.5 py-1 text-[10px]">{{ $tech }}</span>
                                @endforeach
                            </div>
                            <a href="{{ $project['link'] }}" class="pf-mono mt-5 inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-(--pf-primary) transition group-hover:gap-2.5">
                                View Project
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="5" y1="12" x2="19" y2="12" />
                                    <polyline points="12 5 19 12 12 19" />
                                </svg>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Experience & Education --}}
        <section id="experience" class="border-t border-(--pf-border) px-6 py-24" x-data="{ tab: 'experience' }">
            <div class="mx-auto max-w-4xl">
                <div class="mb-12 text-center">
                    <p class="pf-mono mb-3 text-xs text-(--pf-text-muted)">// 02</p>
                    <h2 class="pf-heading text-3xl font-bold tracking-tight sm:text-4xl">Experience & Education</h2>
                    <p class="mt-4 text-(--pf-text-muted)">My professional journey and qualifications.</p>
                </div>

                <div class="mb-10 flex justify-center">
                    <div class="pf-tabs pf-mono grid grid-cols-3 text-xs font-semibold uppercase tracking-wide">
                        <button type="button" @click="tab = 'experience'" class="pf-tab px-4 py-2" :class="{ 'is-active': tab === 'experience' }">Experience</button>
                        <button type="button" @click="tab = 'education'" class="pf-tab px-4 py-2" :class="{ 'is-active': tab === 'education' }">Education</button>
                        <button type="button" @click="tab = 'certifications'" class="pf-tab px-4 py-2" :class="{ 'is-active': tab === 'certifications' }">Certified</button>
                    </div>
                </div>

                <div x-show="tab === 'experience'" x-cloak class="space-y-4">
                    @foreach ($experience as $item)
                        <div class="pf-card rounded-xl p-6">
                            <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between">
                                <h3 class="pf-heading font-semibold">{{ $item['role'] }} · {{ $item['company'] }}</h3>
                                <span class="pf-mono text-xs text-(--pf-text-muted)">{{ $item['period'] }}</span>
                            </div>
                            <p class="mt-2 text-sm text-(--pf-text-muted)">{{ $item['description'] }}</p>
                        </div>
                    @endforeach
                </div>

                <div x-show="tab === 'education'" x-cloak class="space-y-4">
                    @foreach ($education as $item)
                        <div class="pf-card rounded-xl p-6">
                            <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between">
                                <h3 class="pf-heading font-semibold">{{ $item['degree'] }}</h3>
                                <span class="pf-mono text-xs text-(--pf-text-muted)">{{ $item['period'] }}</span>
                            </div>
                            <p class="mt-1 text-sm text-(--pf-text-muted)">{{ $item['school'] }}</p>
                        </div>
                    @endforeach
                </div>

                <div x-show="tab === 'certifications'" x-cloak class="space-y-4">
                    @foreach ($certifications as $item)
                        <div class="pf-card rounded-xl p-6">
                            <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between">
                                <h3 class="pf-heading font-semibold">{{ $item['title'] }}</h3>
                                <span class="pf-mono text-xs text-(--pf-text-muted)">{{ $item['date'] }}</span>
                            </div>
                            <p class="mt-1 text-sm text-(--pf-text-muted)">{{ $item['issuer'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Skills --}}
        <section id="skills" class="border-t border-(--pf-border) bg-(--pf-bg-elevated)/40 px-6 py-24">
            <div class="mx-auto max-w-6xl">
                <div class="mx-auto mb-14 max-w-xl text-center">
                    <p class="pf-mono mb-3 text-xs text-(--pf-text-muted)">// 03</p>
                    <h2 class="pf-heading text-3xl font-bold tracking-tight sm:text-4xl">Technical Proficiency</h2>
                    <p class="mt-4 text-(--pf-text-muted)">A comprehensive toolset for building robust, scalable, and user-centric digital solutions.</p>
                </div>

                <div class="space-y-12">
                    @foreach ($skillGroups as $group)
                        <div>
                            <div class="mb-6 flex items-center gap-4">
                                <h3 class="pf-heading text-xl font-semibold">{{ $group['title'] }}</h3>
                                <div class="h-px flex-grow bg-(--pf-border)"></div>
                            </div>
                            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                                @foreach ($group['skills'] as $skill)
                                    <div class="pf-skill-card rounded-xl p-4 text-center">
                                        <div class="text-3xl">{{ $skill['icon'] }}</div>
                                        <div class="pf-heading mt-2 text-sm font-semibold">{{ $skill['name'] }}</div>
                                        <div class="mt-1 text-xs text-(--pf-text-muted)">{{ $skill['description'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Any additional CMS-authored sections get appended here --}}
        @foreach ($sections as $section)
            @continue(blank($section->localizedCards()))

            <section id="{{ $section->name }}" class="border-t border-(--pf-border) px-6 py-24">
                <div class="mx-auto max-w-6xl">
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($section->localizedCards() as $card)
                            <div class="pf-card group overflow-hidden rounded-xl">
                                @if ($card['image'])
                                    <div class="aspect-4/3 overflow-hidden">
                                        <img src="{{ $card['image'] }}" alt="{{ $card['title'] }}"
                                            class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                    </div>
                                @endif
                                <div class="p-5">
                                    @if ($card['title'])
                                        <h3 class="pf-heading pf-mono font-semibold">{{ $card['title'] }}</h3>
                                    @endif
                                    @if ($card['description'])
                                        <p class="mt-2 text-sm text-(--pf-text-muted) line-clamp-2">{{ $card['description'] }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endforeach

        {{-- Contact --}}
        <section id="contact" class="border-t border-(--pf-border) px-6 py-24">
            <div class="mx-auto max-w-6xl">
                <div class="mx-auto mb-14 max-w-xl text-center">
                    <p class="pf-mono mb-3 text-xs text-(--pf-text-muted)">// 04</p>
                    <h2 class="pf-heading text-3xl font-bold tracking-tight sm:text-4xl">Get In Touch</h2>
                    <p class="mt-4 text-(--pf-text-muted)">Have a project in mind? Let's work together to create something amazing.</p>
                </div>

                <div class="grid gap-8 lg:grid-cols-2">
                    <div class="pf-card pf-contact-form rounded-2xl p-6 sm:p-8">
                        @if ($block = \App\Support\PageBlocks::for('contact'))
                            @livewire($block)
                        @endif
                    </div>

                    <div class="space-y-6">
                        @if ($contactEmail || $contactAddress)
                            <div class="pf-card rounded-xl p-6">
                                <h3 class="pf-heading mb-4 font-semibold">Contact Information</h3>
                                <div class="space-y-4">
                                    @if ($contactEmail)
                                        <div class="flex items-center gap-3">
                                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-(--pf-bg) text-(--pf-primary)">
                                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <rect x="3" y="5" width="18" height="14" rx="2" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m3 7 9 6 9-6" />
                                                </svg>
                                            </span>
                                            <div>
                                                <p class="text-xs text-(--pf-text-muted)">Email</p>
                                                <a href="mailto:{{ $contactEmail }}" class="pf-heading text-sm font-semibold hover:text-(--pf-primary)">{{ $contactEmail }}</a>
                                            </div>
                                        </div>
                                    @endif
                                    @if ($contactAddress)
                                        <div class="flex items-center gap-3">
                                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-(--pf-bg) text-(--pf-primary)">
                                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-6.1-7-11a7 7 0 0 1 14 0c0 4.9-7 11-7 11z" />
                                                    <circle cx="12" cy="10" r="2.5" />
                                                </svg>
                                            </span>
                                            <div>
                                                <p class="text-xs text-(--pf-text-muted)">Location</p>
                                                <p class="pf-heading text-sm font-semibold">{{ $contactAddress }}</p>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <div class="mt-5 flex items-center gap-2 rounded-lg px-4 py-3" style="background-color: color-mix(in srgb, var(--pf-primary) 12%, transparent);">
                                    <span class="pf-pulse-dot h-2 w-2 rounded-full"></span>
                                    <span class="pf-mono text-xs font-medium text-(--pf-primary)">Available for new projects</span>
                                </div>
                            </div>
                        @endif

                        <div class="pf-card rounded-xl p-6">
                            <h3 class="pf-heading mb-4 font-semibold">Connect With Me</h3>
                            <div class="grid grid-cols-2 gap-4">
                                @foreach ($socials as $social)
                                    <a href="{{ $social['url'] }}" target="_blank" rel="noopener"
                                        class="pf-connect-tile flex flex-col items-center gap-2 rounded-lg p-4">
                                        <span class="pf-mono text-lg font-bold">{{ $social['abbr'] }}</span>
                                        <span class="text-xs font-medium">{{ $social['label'] }}</span>
                                    </a>
                                @endforeach
                                @if ($contactEmail)
                                    <a href="mailto:{{ $contactEmail }}" class="pf-connect-tile flex flex-col items-center gap-2 rounded-lg p-4">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="5" width="18" height="14" rx="2" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m3 7 9 6 9-6" />
                                        </svg>
                                        <span class="text-xs font-medium">Email</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-(--pf-border) px-6 py-10">
        <div class="mx-auto flex max-w-6xl flex-col items-center gap-4 text-center sm:flex-row sm:justify-between sm:text-left">
            <p class="pf-mono text-xs text-(--pf-text-muted)">&copy; {{ now()->setTimezone(display_timezone())->year }} {{ $siteName }}. {{ __('All rights reserved.') }}</p>
            @if ($socials->isNotEmpty())
                <div class="flex gap-3">
                    @foreach ($socials as $social)
                        <a href="{{ $social['url'] }}" target="_blank" rel="noopener" aria-label="{{ $social['abbr'] }}" class="pf-social-icon pf-mono text-[11px] font-bold">
                            {{ $social['abbr'] }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </footer>

    <livewire:frontend.chat-widget />

    @fluxScripts
    <script src="{{ asset('themes/portfolio/script.js') }}" defer></script>
</body>
</html>
