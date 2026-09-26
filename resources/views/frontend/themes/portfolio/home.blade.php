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
        $contactAddress = \App\Models\Setting::get('contact_address');

        // Every section on this page is settings-driven and read through
        // App\Support\PortfolioProfile. Projects, Experience, Skills and
        // Testimonials each used to be backed by a table with its own admin CRUD
        // screen; they are lists in the settings table now, so there is one place
        // to edit the portfolio and one place for it to go wrong.
        $profile = \App\Support\PortfolioProfile::hero();
        $stats = \App\Support\PortfolioProfile::stats();
        $services = \App\Support\PortfolioProfile::services();
        $projects = \App\Support\PortfolioProfile::projects();
        $experience = \App\Support\PortfolioProfile::experiences();
        $skillGroups = \App\Support\PortfolioProfile::skillGroups();
        $testimonials = \App\Support\PortfolioProfile::testimonials();
        $education = \App\Support\PortfolioProfile::education();
        $certifications = \App\Support\PortfolioProfile::certifications();

        $contactEmail = $profile['email'];

        // The contact section is built out of up to three things: the form, the
        // contact details, and the social tiles. Which of them exist decides the
        // layout, so both are resolved once here rather than re-queried mid-markup.
        $contactBlock = \App\Support\PageBlocks::for('contact');

        $platformLabels = [
            'facebook' => 'Facebook',
            'twitter' => 'X',
            'instagram' => 'Instagram',
            'youtube' => 'YouTube',
            'linkedin' => 'LinkedIn',
            'tiktok' => 'TikTok',
            'github' => 'GitHub',
            'gitlab' => 'GitLab',
            'behance' => 'Behance',
            'dribbble' => 'Dribbble',
            'whatsapp' => 'WhatsApp',
            'telegram' => 'Telegram',
        ];
        $socials = collect(array_keys($platformLabels))
            ->map(fn (string $platform) => [
                'platform' => $platform,
                'label' => $platformLabels[$platform],
                'url' => \App\Models\SocialLink::url($platform),
            ])
            // An admin-added platform this theme has no mark for still gets a
            // tile: SocialLink::url() is the only gate, the icon partial falls
            // back to an initial. Keyed on platform so the admin's own sort_order
            // (fetched per platform) drives the order.
            ->filter(fn (array $social) => filled($social['url']))
            ->sortBy(fn (array $social) => array_search($social['platform'], array_keys($platformLabels)))
            ->values();

        // The right-hand contact column, split by what is actually in it: a
        // "Contact information" card with nothing but social tiles under it is a
        // card with a heading and no rows. Both empty means the column is not
        // drawn at all — an empty bordered card reads as a bug, not as a page.
        $hasContactInfo = filled($contactEmail)
            || filled($contactAddress)
            || filled($profile['location']);
        $hasContactDetails = $hasContactInfo || $socials->isNotEmpty();
    @endphp

    @include('frontend.themes.portfolio.partials.header')

    <main>
        {{-- Hero --}}
        <section id="home" class="pf-grid-bg relative overflow-hidden px-6 pt-32 pb-20 sm:pt-40 sm:pb-28">
            {{-- Two coloured blooms behind the copy and the portrait. Decorative
                 only, so they are hidden from assistive tech. --}}
            <div class="pf-glow pointer-events-none absolute -top-32 -left-24 h-105 w-105 rounded-full opacity-60" aria-hidden="true"></div>
            <div class="pf-glow pointer-events-none absolute -right-32 -bottom-40 h-130 w-130 rounded-full opacity-40" aria-hidden="true"></div>

            <div class="relative mx-auto grid max-w-6xl items-center gap-14 lg:grid-cols-[1.15fr_0.85fr] lg:gap-16">
                {{-- Copy --}}
                <div class="text-center lg:text-left">
                    @if ($profile['availability'])
                        <span class="pf-badge pf-mono pf-animate mb-7 px-4 py-2 text-[11px] font-medium tracking-wide" style="animation-delay:60ms">
                            <span class="pf-pulse-dot h-1.5 w-1.5 rounded-full"></span>
                            {{ $profile['availability'] }}
                        </span>
                    @endif

                    <h1 class="pf-heading pf-animate text-4xl leading-[1.08] font-bold sm:text-5xl lg:text-6xl" style="animation-delay:140ms">
                        <span class="pf-mono block text-base font-normal text-(--pf-text-muted) sm:text-lg">Hi, I'm</span>
                        <span data-typewriter class="pf-gradient-text mt-2 block whitespace-nowrap" style="visibility:hidden">{{ $profile['name'] }}</span>
                    </h1>

                    @if ($profile['role'])
                        <p class="pf-animate pf-mono mt-5 text-sm font-medium tracking-wide text-(--pf-primary) sm:text-base" style="animation-delay:220ms">
                            {{ $profile['role'] }}
                        </p>
                    @endif

                    @if ($profile['tagline'])
                        <p class="pf-animate mx-auto mt-6 max-w-xl text-base text-(--pf-text-muted) sm:text-lg lg:mx-0" style="animation-delay:300ms">
                            {{ $profile['tagline'] }}
                        </p>
                    @endif

                    @if ($profile['location'])
                        <p class="pf-animate pf-mono mt-5 inline-flex items-center gap-2 text-xs text-(--pf-text-muted)" style="animation-delay:340ms">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-6.1-7-11a7 7 0 0 1 14 0c0 4.9-7 11-7 11z" />
                                <circle cx="12" cy="10" r="2.5" />
                            </svg>
                            {{ $profile['location'] }}
                        </p>
                    @endif

                    <div class="pf-animate mt-9 flex flex-wrap items-center justify-center gap-3 lg:justify-start" style="animation-delay:400ms">
                        <a href="#projects" class="pf-btn-solid px-7 py-3 text-sm">
                            View Projects
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="5" y1="12" x2="19" y2="12" />
                                <polyline points="12 5 19 12 12 19" />
                            </svg>
                        </a>

                        @if ($profile['resume_url'])
                            <a href="{{ $profile['resume_url'] }}" target="_blank" rel="noopener" class="pf-btn-outline px-7 py-3 text-sm">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" />
                                </svg>
                                {{ $profile['resume_label'] }}
                            </a>
                        @endif

                        <a href="#contact" class="pf-btn-outline px-7 py-3 text-sm">Contact Me</a>
                    </div>

                    @if ($socials->isNotEmpty())
                        <div class="pf-animate mt-9 flex justify-center lg:justify-start" style="animation-delay:480ms">
                            @include('frontend.themes.portfolio.partials.social-links', ['variant' => 'row'])
                        </div>
                    @endif
                </div>

                {{-- Portrait. A photo when one is set, the owner's initials when
                     not — never a broken <img> and never a stock silhouette. --}}
                <div class="pf-animate relative mx-auto w-full max-w-sm lg:max-w-none" style="animation-delay:240ms">
                    <div class="pf-glow pointer-events-none absolute inset-4 rounded-full opacity-70" aria-hidden="true"></div>

                    <div class="pf-photo-frame relative aspect-4/5 overflow-hidden rounded-3xl">
                        @if ($profile['photo'])
                            <img src="{{ $profile['photo'] }}" alt="{{ $profile['name'] }}"
                                class="h-full w-full object-cover" loading="eager" fetchpriority="high">
                        @else
                            <div class="pf-monogram text-[5rem] sm:text-[7rem]">{{ $profile['monogram'] }}</div>
                        @endif
                    </div>

                    {{-- A stat card overlapping the frame's bottom-left corner.
                         Only worth drawing when there is a stat to draw. --}}
                    @if ($stats->isNotEmpty())
                        @php($leadStat = $stats->first())
                        <div class="pf-card absolute -bottom-5 -left-4 hidden rounded-xl px-5 py-3.5 sm:block">
                            <div class="pf-heading text-2xl leading-none font-bold">{{ $leadStat['value'] }}</div>
                            <div class="pf-mono mt-1.5 text-[10px] tracking-wide text-(--pf-text-muted)">{{ $leadStat['label'] }}</div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Trust strip. A number with no unit is noise, so the label is part
                 of the row and a row missing either half is dropped upstream. --}}
            @if ($stats->isNotEmpty())
                <div class="relative mx-auto mt-20 max-w-6xl">
                    <div class="pf-card grid grid-cols-2 gap-y-8 rounded-2xl px-6 py-8 sm:grid-cols-4 sm:px-8">
                        @foreach ($stats as $stat)
                            <div class="pf-stat px-2 text-center">
                                <div class="pf-heading text-2xl font-bold sm:text-3xl">{{ $stat['value'] }}</div>
                                <div class="pf-mono mt-2 text-[10px] tracking-wider text-(--pf-text-muted) uppercase">{{ $stat['label'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>

        {{-- What I do — the section that tells a visitor what they can hire you
             for before they read a single project. Rendered unconditionally
             because the nav links to #services: a nav item whose target is
             absent is a dead link. Projects, Experience and Technology take the
             same approach. --}}
        <section id="services" class="border-t border-(--pf-border) px-6 py-24">
            <div class="mx-auto max-w-6xl">
                {{-- The <section> above stays even when the list is empty, because
                     the nav links to #services and a nav item with no target is a
                     dead link. The heading and the cards do not: a public page
                     that says "Services I can help with" over nothing advertises
                     a gap, and a visitor cannot tell the difference between that
                     and a portfolio that is merely unfinished. Seeded content (see
                     PortfolioContentSeeder) means this branch is rare. --}}
                @if ($services->isNotEmpty())
                    <div data-pf-reveal class="max-w-2xl">
                        <span class="pf-eyebrow pf-mono">01 &mdash; What I do</span>
                        <h2 class="pf-heading mt-5 text-3xl font-bold sm:text-4xl">Services I can help with</h2>
                        <p class="mt-4 text-(--pf-text-muted)">The kinds of problems I take on, and what you get back.</p>
                    </div>

                    <div class="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($services as $index => $service)
                            <div data-pf-reveal class="pf-card pf-service pf-card-hover relative rounded-2xl p-6"
                                style="--pf-reveal-delay: {{ $index * 70 }}ms">
                                <span class="pf-mono pf-service-index" aria-hidden="true">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>

                                <span class="pf-service-icon" aria-hidden="true">{{ $service['icon'] ?: '◆' }}</span>

                                <h3 class="pf-heading mt-5 text-base font-semibold">{{ $service['title'] }}</h3>
                                @if ($service['description'])
                                    <p class="mt-2.5 text-sm leading-relaxed text-(--pf-text-muted)">{{ $service['description'] }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        {{-- Projects --}}
        <section id="projects" class="border-t border-(--pf-border) bg-(--pf-bg-elevated)/40 px-6 py-24">
            <div class="mx-auto max-w-6xl">
                @if ($projects->isNotEmpty())
                    <div data-pf-reveal class="max-w-2xl">
                        <span class="pf-eyebrow pf-mono">02 &mdash; Selected work</span>
                        <h2 class="pf-heading mt-5 text-3xl font-bold sm:text-4xl">Featured projects</h2>
                        <p class="mt-4 text-(--pf-text-muted)">Selected projects showcasing scalable architecture, clean code, and thoughtful user experience.</p>
                    </div>

                    <div class="mt-14 grid gap-5 sm:grid-cols-2">
                    @foreach ($projects as $index => $project)
                        <div data-pf-reveal class="pf-card pf-card-hover pf-card-rail group flex flex-col rounded-2xl p-6"
                            style="--pf-reveal-delay: {{ ($index % 2) * 90 }}ms">
                            <div class="flex items-start justify-between gap-3">
                                <span class="pf-skill-mark text-2xl" aria-hidden="true">{{ $project['icon'] ?: '◆' }}</span>
                                @if ($project['stats'])
                                    <span class="pf-chip pf-mono px-3 py-1 text-[10px]">{{ $project['stats'] }}</span>
                                @endif
                            </div>

                            <h3 class="pf-heading mt-5 text-lg font-semibold">{{ $project['title'] }}</h3>
                            @if ($project['description'])
                                <p class="mt-2.5 flex-1 text-sm leading-relaxed text-(--pf-text-muted)">{{ $project['description'] }}</p>
                            @endif

                            @if (filled($project['tech']))
                                <div class="mt-5 flex flex-wrap gap-1.5">
                                    @foreach ($project['tech'] as $tech)
                                        <span class="pf-chip pf-mono px-2.5 py-1 text-[10px]">{{ $tech }}</span>
                                    @endforeach
                                </div>
                            @endif

                            @if (filled($project['link']))
                                <a href="{{ $project['link'] }}" target="_blank" rel="noopener"
                                    class="pf-mono mt-6 inline-flex items-center gap-1.5 text-[11px] font-semibold tracking-wider text-(--pf-primary) uppercase">
                                    View project
                                    <svg class="h-3.5 w-3.5 transition-transform duration-300 group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="5" y1="12" x2="19" y2="12" />
                                        <polyline points="12 5 19 12 12 19" />
                                    </svg>
                                </a>
                            @endif
                        </div>
                    @endforeach
                    </div>
                @endif
            </div>
        </section>

        {{-- Experience & credentials --}}

        <section id="experience" class="border-t border-(--pf-border) px-6 py-24">
            {{-- The #education anchor sits outside the guard below, so the nav item
                 and any deep link still land on this section even when every list
                 in it is empty. It is zero-height, and carries the scroll-margin,
                 so it is invisible and costs nothing. --}}
            <span id="education" class="block scroll-mt-28" aria-hidden="true"></span>
            <div class="mx-auto max-w-6xl">
                {{-- Three independent lists share this section, so the section
                     shows if *any* of them has content, and each column is guarded
                     on its own. Credentials alone (a degree, a certificate) with no
                     job title is still worth printing. --}}
                @if ($experience->isNotEmpty() || $education->isNotEmpty() || $certifications->isNotEmpty())
                    <div data-pf-reveal class="max-w-2xl">
                        <span class="pf-eyebrow pf-mono">03 &mdash; Background</span>
                        <h2 class="pf-heading mt-5 text-3xl font-bold sm:text-4xl">Experience &amp; credentials</h2>
                        <p class="mt-4 text-(--pf-text-muted)">Where I have worked, what I studied, and what I am certified in.</p>
                    </div>

                    <div class="mt-14 grid gap-14 lg:grid-cols-[1.2fr_0.8fr] lg:gap-16">
                        {{-- Roles, as a timeline rather than a stack of identical
                             cards: the rail is what turns a list of dates into a
                             career. --}}
                        @if ($experience->isNotEmpty())
                            <div data-pf-reveal>
                        <h3 class="pf-mono mb-7 text-[11px] font-semibold tracking-wider text-(--pf-text-muted) uppercase">Work experience</h3>

                        @foreach ($experience as $index => $item)
                            <div class="pf-timeline-item {{ $index < $experience->count() - 1 ? 'pb-9' : '' }}">
                                <span class="pf-timeline-dot" aria-hidden="true"></span>

                                <div class="pf-card pf-card-hover rounded-xl p-5">
                                    <div class="flex flex-col gap-1.5 sm:flex-row sm:items-baseline sm:justify-between sm:gap-4">
                                        <h4 class="pf-heading font-semibold">
                                            {{ $item['role'] }}
                                            @if (filled($item['company']))
                                                <span class="font-normal text-(--pf-text-muted)">@ {{ $item['company'] }}</span>
                                            @endif
                                        </h4>
                                        @if (filled($item['period']))
                                            <span class="pf-mono shrink-0 text-[11px] text-(--pf-text-muted)">{{ $item['period'] }}</span>
                                        @endif
                                    </div>

                                    @if (filled($item['description']))
                                        <p class="mt-2.5 text-sm leading-relaxed text-(--pf-text-muted)">{{ $item['description'] }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                            </div>
                        @endif


                    {{-- Education and certifications, stacked. The anchor is
                         unconditional so a deep link to #education and the
                         scroll-margin below always land somewhere, but each
                         visible block is dropped whole when empty: a heading
                         over nothing advertises a gap in the portfolio. --}}
                    <div id="education" class="scroll-mt-28 space-y-12">
                        @if ($education->isNotEmpty())
                            <div data-pf-reveal>
                                <h3 class="pf-mono mb-6 text-[11px] font-semibold tracking-wider text-(--pf-text-muted) uppercase">Education</h3>
                                <div class="space-y-3">
                                    @foreach ($education as $item)
                                        <div class="pf-card rounded-xl p-5">
                                            <div class="flex items-start justify-between gap-4">
                                                <h4 class="pf-heading text-sm font-semibold">{{ $item['degree'] }}</h4>
                                                @if ($item['period'])
                                                    <span class="pf-mono shrink-0 text-[10px] text-(--pf-text-muted)">{{ $item['period'] }}</span>
                                                @endif
                                            </div>
                                            @if ($item['school'])
                                                <p class="mt-1.5 text-sm text-(--pf-text-muted)">{{ $item['school'] }}</p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if ($certifications->isNotEmpty())
                            <div data-pf-reveal>
                                <h3 class="pf-mono mb-6 text-[11px] font-semibold tracking-wider text-(--pf-text-muted) uppercase">Certifications</h3>
                                <div class="space-y-3">
                                    @foreach ($certifications as $item)
                                        <div class="pf-card rounded-xl p-5">
                                            <div class="flex items-start justify-between gap-4">
                                                <h4 class="pf-heading text-sm font-semibold">{{ $item['title'] }}</h4>
                                                @if ($item['date'])
                                                    <span class="pf-mono shrink-0 text-[10px] text-(--pf-text-muted)">{{ $item['date'] }}</span>
                                                @endif
                                            </div>
                                            @if ($item['issuer'])
                                                <p class="mt-1.5 text-sm text-(--pf-text-muted)">{{ $item['issuer'] }}</p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </section>

        {{-- Technology --}}
        <section id="technology" class="border-t border-(--pf-border) bg-(--pf-bg-elevated)/40 px-6 py-24">
            <div class="mx-auto max-w-6xl">
                @if ($skillGroups->isNotEmpty())
                    <div data-pf-reveal class="max-w-2xl">
                        <span class="pf-eyebrow pf-mono">04 &mdash; Toolkit</span>
                        <h2 class="pf-heading mt-5 text-3xl font-bold sm:text-4xl">Technology</h2>
                        <p class="mt-4 text-(--pf-text-muted)">A comprehensive toolset for building robust, scalable, and user-centric digital solutions.</p>
                    </div>

                    <div class="mt-14 space-y-12">
                    @foreach ($skillGroups as $groupName => $skills)
                        <div data-pf-reveal>
                            <div class="pf-mono mb-5 flex items-baseline gap-3">
                                <h3 class="pf-heading text-sm font-semibold tracking-wide">{{ $groupName }}</h3>
                                <span class="text-[11px] text-(--pf-text-muted)">{{ $skills->count() }}</span>
                            </div>

                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($skills as $skill)
                                    <div class="pf-skill">
                                        <span class="pf-skill-mark" aria-hidden="true">{{ $skill['icon'] ?: '◆' }}</span>
                                        <span class="min-w-0">
                                            <span class="pf-heading block text-sm font-semibold">{{ $skill['name'] }}</span>
                                            @if (filled($skill['description']))
                                                <span class="mt-0.5 block truncate text-[11px] text-(--pf-text-muted)">{{ $skill['description'] }}</span>
                                            @endif
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                    </div>
                @endif
            </div>
        </section>

        {{-- Testimonials --}}
        {{-- The anchor is unconditional so a deep link and the footer nav item
             both still land somewhere; the block inside is what hides when there
             is nothing to show. --}}
        <section id="testimonials" class="border-t border-(--pf-border) px-6 py-24">
            @if ($testimonials->isNotEmpty())
                <div class="mx-auto max-w-6xl">
                    <div data-pf-reveal class="max-w-2xl">
                        <span class="pf-eyebrow pf-mono">05 &mdash; References</span>
                        <h2 class="pf-heading mt-5 text-3xl font-bold sm:text-4xl">What clients say</h2>
                        <p class="mt-4 text-(--pf-text-muted)">The part of this page that is not my own description of me.</p>
                    </div>

                    <div class="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($testimonials as $index => $testimonial)
                            <figure data-pf-reveal
                                class="pf-card pf-card-hover flex flex-col rounded-2xl p-6"
                                style="--pf-reveal-delay: {{ ($index % 3) * 90 }}ms">
                                {{-- Stars only when a rating was actually given: an
                                     empty rating row would read as a broken widget,
                                     which is worse than saying nothing. --}}
                                @if ($testimonial['rating'])
                                    <div class="flex gap-0.5 text-(--pf-primary)" role="img"
                                        aria-label="{{ $testimonial['rating'] }} out of 5">
                                        @for ($star = 1; $star <= 5; $star++)
                                            <svg class="size-4 {{ $star <= $testimonial['rating'] ? '' : 'opacity-25' }}"
                                                viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                <path d="M12 2l2.9 6.26 6.85.72-5.1 4.6 1.42 6.68L12 16.9l-6.07 3.36L7.35 13.6 2.25 8.98l6.85-.72L12 2z" />
                                            </svg>
                                        @endfor
                                    </div>
                                @endif

                                <blockquote class="mt-5 flex-1 text-sm leading-relaxed text-(--pf-text-muted)">
                                    &ldquo;{{ $testimonial['quote'] }}&rdquo;
                                </blockquote>

                                <figcaption class="mt-6 flex items-center gap-3 border-t border-(--pf-border) pt-5">
                                    <span class="pf-skill-mark" aria-hidden="true">
                                        {{ \Illuminate\Support\Str::of($testimonial['name'])->substr(0, 1)->upper() ?: '◆' }}
                                    </span>
                                    <span class="min-w-0">
                                        @if (filled($testimonial['name']))
                                            <span class="pf-heading block text-sm font-semibold">{{ $testimonial['name'] }}</span>
                                        @endif
                                        @if (filled($testimonial['role']))
                                            <span class="mt-0.5 block truncate text-[11px] text-(--pf-text-muted)">{{ $testimonial['role'] }}</span>
                                        @endif
                                    </span>
                                </figcaption>
                            </figure>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>

        {{-- Any additional CMS-authored sections get appended here --}}

        @foreach ($sections as $section)
            @continue(blank($section->localizedCards()))

            <section id="{{ $section->name }}" class="border-t border-(--pf-border) px-6 py-24">
                <div class="mx-auto max-w-6xl">
                    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($section->localizedCards() as $card)
                            <div class="pf-card pf-card-hover group overflow-hidden rounded-2xl">
                                @if ($card['image'])
                                    <div class="aspect-4/3 overflow-hidden">
                                        <img src="{{ $card['image'] }}" alt="{{ $card['title'] }}"
                                            class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                    </div>
                                @endif
                                <div class="p-5">
                                    @if ($card['title'])
                                        <h3 class="pf-heading text-base font-semibold">{{ $card['title'] }}</h3>
                                    @endif
                                    @if ($card['description'])
                                        <p class="mt-2 text-sm leading-relaxed text-(--pf-text-muted) line-clamp-2">{{ $card['description'] }}</p>
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
                <div data-pf-reveal class="max-w-2xl">
                    <span class="pf-eyebrow pf-mono">06 &mdash; Contact</span>
                    <h2 class="pf-heading mt-5 text-3xl font-bold sm:text-4xl">Get in touch</h2>
                    <p class="mt-4 text-(--pf-text-muted)">Have a project in mind? Tell me about it and I'll get back to you.</p>
                </div>

                {{-- One column until there is something to put side by side, and
                     nothing at all until there is something to put in either --
                     a heading with an empty card under it is worse than the
                     section being short. The #contact anchor itself always
                     exists, because the nav and the hero's "Contact Me" button
                     both point at it. --}}
                @if ($contactBlock || $hasContactDetails)
                    <div @class([
                        'mt-14 grid gap-6',
                        'lg:grid-cols-2' => $contactBlock && $hasContactDetails,
                    ])>
                        @if ($contactBlock)
                            <div data-pf-reveal class="pf-card pf-contact-form rounded-2xl p-6 sm:p-8">
                                @livewire($contactBlock)
                            </div>
                        @endif

                        @if ($hasContactDetails)
                            <div data-pf-reveal class="space-y-5" style="--pf-reveal-delay: 90ms">
                                @if ($hasContactInfo)
                                    <div class="pf-card rounded-2xl p-6">
                                        <h3 class="pf-heading mb-5 text-sm font-semibold">Contact information</h3>

                                        <div class="space-y-4">
                                            @if ($contactEmail)
                                                <div class="flex items-center gap-3.5">
                                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-(--pf-bg-inset) text-(--pf-primary)" aria-hidden="true">
                                                        <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <rect x="3" y="5" width="18" height="14" rx="2" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="m3 7 9 6 9-6" />
                                                        </svg>
                                                    </span>
                                                    <div class="min-w-0">
                                                        <p class="pf-mono text-[10px] tracking-wider text-(--pf-text-muted) uppercase">Email</p>
                                                        <a href="mailto:{{ $contactEmail }}" class="pf-heading block truncate text-sm font-semibold hover:text-(--pf-primary)">{{ $contactEmail }}</a>
                                                    </div>
                                                </div>
                                            @endif

                                            @if ($contactAddress || $profile['location'])
                                                <div class="flex items-center gap-3.5">
                                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-(--pf-bg-inset) text-(--pf-primary)" aria-hidden="true">
                                                        <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-6.1-7-11a7 7 0 0 1 14 0c0 4.9-7 11-7 11z" />
                                                            <circle cx="12" cy="10" r="2.5" />
                                                        </svg>
                                                    </span>
                                                    <div class="min-w-0">
                                                        <p class="pf-mono text-[10px] tracking-wider text-(--pf-text-muted) uppercase">Location</p>
                                                        <p class="pf-heading truncate text-sm font-semibold">{{ $contactAddress ?: $profile['location'] }}</p>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>

                                        @if ($profile['availability'])
                                            <div class="mt-6 flex items-center gap-2.5 rounded-lg px-4 py-3"
                                                style="background-color: color-mix(in srgb, var(--pf-primary) 10%, transparent)">
                                                <span class="pf-pulse-dot h-1.5 w-1.5 rounded-full"></span>
                                                <span class="pf-mono text-[11px] font-medium text-(--pf-primary)">{{ $profile['availability'] }}</span>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                @if ($socials->isNotEmpty())
                                    <div class="pf-card rounded-2xl p-6">
                                        <h3 class="pf-heading mb-5 text-sm font-semibold">Find me online</h3>
                                        @include('frontend.themes.portfolio.partials.social-links', ['variant' => 'tiles'])
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif
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
