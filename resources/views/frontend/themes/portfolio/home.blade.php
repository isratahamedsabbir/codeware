<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
    @include('partials.seo-meta')
    <link rel="stylesheet" href="{{ asset('themes/portfolio/style.css') }}">
    <style>[x-cloak]{display:none!important}</style>
    @include('partials.custom-code-head')
</head>
<body class="theme-portfolio antialiased">

    @php
        $siteName = \App\Models\Setting::get('site_name', config('app.name'));
        $siteIcon = \App\Models\Setting::get('site_icon_white') ?: \App\Models\Setting::get('site_icon');
        $contactEmail = \App\Models\Setting::get('contact_email');
        $contactAddress = \App\Models\Setting::get('contact_address');
        $pfHeroTitle = \App\Models\Setting::get('theme_portfolio_hero_title', 'Full Stack Developer');
        $pfHeroTagline = \App\Models\Setting::get('theme_portfolio_hero_tagline', 'Building fast, reliable, and scalable web applications with modern tools. Passionate about clean code and thoughtful design.');
        $pfAvailability = \App\Models\Setting::get('theme_portfolio_availability', 'Available for new projects');

        $socialIcons = [
            'facebook' => ['abbr' => 'FB', 'label' => 'Facebook'],
            'twitter' => ['abbr' => 'X', 'label' => 'Twitter'],
            'instagram' => ['abbr' => 'IG', 'label' => 'Instagram'],
            'youtube' => ['abbr' => 'YT', 'label' => 'YouTube'],
            'linkedin' => ['abbr' => 'IN', 'label' => 'LinkedIn'],
            'tiktok' => ['abbr' => 'TT', 'label' => 'TikTok'],
        ];
        $socials = collect($socialIcons)
            ->map(fn ($meta, $platform) => ['url' => \App\Models\SocialLink::url($platform), 'abbr' => $meta['abbr'], 'label' => $meta['label']])
            ->filter(fn ($social) => filled($social['url']))
            ->values();

        // Projects, Experience and Technology are admin-managed (Portfolio ▸ Projects /
        // Experience / Skills at /admin/portfolio) — Education and Certifications are
        // still placeholder arrays, and the next step is to give them their own screens.
        $projects = \App\Support\Portfolio::projects();
        $experience = \App\Support\Portfolio::experiences();
        $skillGroups = \App\Support\Portfolio::skillGroups();

        $education = [
            ['degree' => 'B.Sc. in Computer Science', 'school' => 'Your University', 'period' => '2018 - 2022', 'description' => ''],
            ['degree' => 'Web Development Bootcamp', 'school' => 'Training Institute', 'period' => '2022', 'description' => ''],
        ];

        $certifications = [
            ['title' => 'AWS Certified Developer', 'issuer' => 'Amazon Web Services', 'date' => '2023'],
        ];
    @endphp

    @include('frontend.themes.portfolio.partials.header')

    <main>
        {{-- Hero --}}
        <section id="home" class="pf-grid-bg relative flex min-h-screen items-center overflow-hidden px-6 pt-24">
            <div class="mx-auto max-w-4xl text-center">
                <span class="pf-badge pf-animate pf-mono mb-6 inline-flex rounded-full px-4 py-2 text-xs font-medium" style="animation-delay:100ms">
                    <svg class="mr-2 h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="16 18 22 12 16 6" />
                        <polyline points="8 6 2 12 8 18" />
                    </svg>
                    {{ $pfHeroTitle }}
                </span>

                <h1 class="pf-heading pf-animate text-4xl font-extrabold leading-tight tracking-tight sm:text-6xl" style="animation-delay:200ms">
                    Hi, I'm
                    <span data-typewriter class="pf-gradient-text whitespace-nowrap" style="visibility:hidden">{{ $siteName }}</span>
                </h1>

                <p class="pf-animate mx-auto mt-6 max-w-xl text-lg text-(--pf-text-muted) sm:text-xl" style="animation-delay:300ms">
                    {{ $pfHeroTagline }}
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
                    <h2 class="pf-heading text-3xl font-bold tracking-tight sm:text-4xl">Featured Projects</h2>
                    <p class="mt-4 text-(--pf-text-muted)">Selected projects showcasing scalable architecture, clean code, and thoughtful user experience.</p>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    @forelse ($projects as $project)
                        <div class="pf-card group flex flex-col rounded-xl p-6">
                            <div class="flex items-start justify-between gap-3">
                                <span class="text-3xl">{{ $project->icon }}</span>
                                @if ($project->stats)
                                    <span class="pf-chip pf-mono rounded-full px-3 py-1 text-[11px]">{{ $project->stats }}</span>
                                @endif
                            </div>
                            <h3 class="pf-heading mt-4 text-lg font-semibold">{{ $project->title }}</h3>
                            <p class="mt-2 flex-1 text-sm text-(--pf-text-muted)">{{ $project->description }}</p>
                            <div class="mt-4 flex flex-wrap gap-2">
                                @foreach ($project->tech ?? [] as $tech)
                                    <span class="pf-chip pf-mono rounded-full px-2.5 py-1 text-[10px]">{{ $tech }}</span>
                                @endforeach
                            </div>
                            <a href="{{ $project->link ?: '#' }}" class="pf-mono mt-5 inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-(--pf-primary) transition group-hover:gap-2.5">
                                View Project
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="5" y1="12" x2="19" y2="12" />
                                    <polyline points="12 5 19 12 12 19" />
                                </svg>
                            </a>
                        </div>
                    @empty
                        <p class="col-span-full text-center text-sm text-(--pf-text-muted)">No projects yet — add them under Portfolio ▸ Projects.</p>
                    @endforelse
                </div>
            </div>
        </section>

        {{-- Experience & Education --}}
        <section id="experience" class="border-t border-(--pf-border) px-6 py-24" x-data="{ tab: 'experience' }">
            <div class="mx-auto max-w-4xl">
                <div class="mb-12 text-center">
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
                    @forelse ($experience as $item)
                        <div class="pf-card rounded-xl p-6">
                            <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between">
                                <h3 class="pf-heading font-semibold">
                                    {{ $item->role }}@if ($item->company)<span class="text-(--pf-text-muted)"> · {{ $item->company }}</span>@endif
                                </h3>
                                <span class="pf-mono text-xs text-(--pf-text-muted)">{{ $item->period }}</span>
                            </div>
                            <p class="mt-2 text-sm text-(--pf-text-muted)">{{ $item->description }}</p>
                        </div>
                    @empty
                        <p class="text-center text-sm text-(--pf-text-muted)">No experience yet — add it under Portfolio ▸ Experience.</p>
                    @endforelse
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

        {{-- Technology --}}
        <section id="technology" class="border-t border-(--pf-border) bg-(--pf-bg-elevated)/40 px-6 py-24">
            <div class="mx-auto max-w-6xl">
                <div class="mx-auto mb-14 max-w-xl text-center">
                    <h2 class="pf-heading text-3xl font-bold tracking-tight sm:text-4xl">Technology</h2>
                    <p class="mt-4 text-(--pf-text-muted)">A comprehensive toolset for building robust, scalable, and user-centric digital solutions.</p>
                </div>

                <div class="space-y-12">
                    @forelse ($skillGroups as $groupName => $skills)
                        <div>
                            <div class="mb-6 flex items-center gap-4">
                                <h3 class="pf-heading text-xl font-semibold">{{ $groupName }}</h3>
                                <div class="h-px flex-grow bg-(--pf-border)"></div>
                            </div>
                            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                                @foreach ($skills as $skill)
                                    <div class="pf-skill-card rounded-xl p-4 text-center">
                                        <div class="text-3xl">{{ $skill->icon }}</div>
                                        <div class="pf-heading mt-2 text-sm font-semibold">{{ $skill->name }}</div>
                                        <div class="mt-1 text-xs text-(--pf-text-muted)">{{ $skill->description }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-sm text-(--pf-text-muted)">No skills yet — add them under Portfolio ▸ Skills.</p>
                    @endforelse
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
                                    <span class="pf-mono text-xs font-medium text-(--pf-primary)">{{ $pfAvailability }}</span>
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

    @include('frontend.themes.portfolio.partials.footer')

    <livewire:frontend.chat-widget />

    @fluxScripts
    <script src="{{ asset('themes/portfolio/script.js') }}" defer></script>
@include('partials.custom-code-body')
</body>
</html>
