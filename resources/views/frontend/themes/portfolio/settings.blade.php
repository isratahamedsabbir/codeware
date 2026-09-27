{{--
    Portfolio theme settings — bound to the Theme Settings screen (Admin →

    Theme Settings) via wire:model="settings.theme_{slug}_*".

    Everything on this one-pager is edited from this screen. Projects,
    Experience, Skills and Testimonials used to have their own tables and admin
    CRUD screens; they are ordinary lists here now, so the whole portfolio is one
    place with one save button rather than four places an owner has to find.

    Scalar fields persist through the component's generic $settings bag. List
    fields (stats, services, projects, experience, skills, testimonials,
    education, certifications) bind to the separate $repeaters bag instead, each
    stored as one JSON setting. The theme settings component discovers them by
    parsing this file for `setting-key="theme_*"` (which is also where it reads
    each one's `:max` from, so the add button and the save-time cap agree) and
    grows the add/remove/reorder UI for each. See
    App\Livewire\Admin\ThemeSettings\Index.

    Read back by the one-pager through App\Support\PortfolioProfile, e.g.
        \App\Support\PortfolioProfile::hero()['role']
        \App\Support\PortfolioProfile::services()
        \App\Support\PortfolioProfile::projects()

    A section with no rows is not rendered on the storefront at all, so leaving
    these empty is a valid state: a smaller finished portfolio beats a
    half-filled one that advertises the gaps.
--}}
{{-- The wire:key is load-bearing, not decoration. This partial and
     ecommerce/settings.blade.php are swapped into the same slot on the Theme
     Settings screen by a plain @include, and both root elements are an
     unkeyed <div x-data="{ tab, open }"> with the same tag. Without a key,
     Livewire morphs one into the other in place, and Alpine keeps the x-data
     object it parsed first — so after switching to ecommerce, this scope is
     still driving the page: `tab` is 'profile', which matches neither of
     ecommerce's 'banners' / 'colors' panels, so Theme Settings looks empty, and
     clicking Colors then reveals only Colors because the stale open() happens to
     set the same property. Keying on the slug makes the node unique, so
     Livewire replaces it and Alpine re-initialises with the right scope. --}}
<div
    wire:key="theme-settings-{{ $themeSlug }}"
    x-data="{
        tab: (() => { try { const saved = localStorage.getItem('theme-portfolio-tab') || 'profile'; return saved === 'work' ? 'projects' : saved } catch (e) { return 'profile' } })(),
        open(name) { this.tab = name; try { localStorage.setItem('theme-portfolio-tab', name) } catch (e) {} },
    }"
    class="grid gap-5 lg:grid-cols-[15rem_minmax(0,1fr)] lg:gap-6"
>
    {{-- The sections are a menu, not a tab strip: a strip at the top of a form
         this long scrolls out of reach, this holds its place beside the form.
         `lg:top-14` clears the 56px sticky admin header. --}}
    <x-admin-settings-nav class="lg:sticky lg:top-14 lg:self-start" :tabs="[
        'profile' => ['Profile', 'user-circle'],
        'sections' => ['Sections', 'squares-2x2'],
        'projects' => ['Projects', 'folder'],
        'experience' => ['Experience', 'briefcase'],
        'skills' => ['Skills', 'sparkles'],
        'testimonials' => ['Testimonials', 'chat-bubble-left-right'],
        'credentials' => ['Credentials', 'academic-cap'],
    ]" />

    {{-- `x-cloak` on the whole column: panels stay mounted (x-show, not x-if) so
         the media pickers keep their Livewire bindings, which means every panel
         is in the DOM at once and would otherwise all flash before Alpine boots. --}}
    <div class="min-w-0 space-y-5" x-cloak>


    {{-- ══ Profile ═══════════════════════════════════════════════════════ --}}
    {{-- Both panels stay mounted (x-show) so the media picker keeps its Livewire
         binding while switching tabs; the last tab is remembered per browser. --}}
    <section role="tabpanel" x-show="tab === 'profile'"
        class="isolate overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-700 dark:bg-zinc-900">
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-100 px-5 py-4 dark:border-zinc-700">
            <div class="flex items-center gap-3">
                <span class="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                    <flux:icon.user-circle variant="mini" class="size-5" />
                </span>
                <div>
                    <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Identity</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">The name, role, photo and résumé button at the top of the page.</p>
                </div>
            </div>
        </header>

        {{-- Direct uploads rather than Media Library pickers: a new file
             replaces the old one and the old file is deleted on save
             (see App\Livewire\Admin\ThemeSettings\Index::saveUploads()). --}}
        <div class="grid grid-cols-1 items-start gap-6 p-5 lg:grid-cols-[15rem_minmax(0,1fr)]">
            <x-admin-theme-upload upload-key="theme_portfolio_photo" type="image" label="Hero Photo"
                hint="Without a photo the hero shows your initials on a tinted panel, so the page still reads as finished."
                size-hint="Portrait, roughly 4:5"
                :value="$settings['theme_portfolio_photo'] ?? ''" :pending="$uploads['theme_portfolio_photo'] ?? null" />

            <div class="space-y-5">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>Display Name<x-field-hint text="Leave blank to use the site name from Settings." /></flux:label>
                        <flux:input wire:model="settings.theme_portfolio_name" placeholder="e.g. Sabbir Hossain" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Role<x-field-hint text="The line under your name — 'Full Stack Developer', 'Laravel & React Engineer'." /></flux:label>
                        <flux:input wire:model="settings.theme_portfolio_hero_title" placeholder="Full Stack Developer" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>Tagline<x-field-hint text="One or two sentences on what you build." /></flux:label>
                    <flux:textarea wire:model="settings.theme_portfolio_hero_tagline" class="h-24"
                        placeholder="Building fast, reliable, and scalable web applications with modern tools." />
                </flux:field>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>Location<x-field-hint text="Shown under the tagline and in the contact card." /></flux:label>
                        <flux:input wire:model="settings.theme_portfolio_location" placeholder="e.g. Dhaka, Bangladesh" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Availability<x-field-hint text="The status pill in the hero. Blank hides it." /></flux:label>
                        <flux:input wire:model="settings.theme_portfolio_availability" placeholder="Available for new projects" />
                    </flux:field>
                </div>

                <div class="grid grid-cols-1 items-start gap-5 border-t border-zinc-100 pt-5 sm:grid-cols-2 dark:border-zinc-700">
                    <x-admin-theme-upload upload-key="theme_portfolio_resume_url" type="pdf" label="Résumé (PDF)"
                        hint="No file hides the download button."
                        :value="$settings['theme_portfolio_resume_url'] ?? ''" :pending="$uploads['theme_portfolio_resume_url'] ?? null" />

                    <flux:field>
                        <flux:label>Résumé Button Label</flux:label>
                        <flux:input wire:model="settings.theme_portfolio_resume_label" placeholder="Download CV" />
                    </flux:field>
                </div>
            </div>
        </div>
    </section>

    {{-- ══ Sections ══════════════════════════════════════════════════════ --}}
    <section role="tabpanel" x-show="tab === 'sections'"
        class="isolate overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-700 dark:bg-zinc-900">
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-100 px-5 py-4 dark:border-zinc-700">
            <div class="flex items-center gap-3">
                <span class="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                    <flux:icon.chart-bar variant="mini" class="size-5" />
                </span>
                <div>
                    <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Hero stats</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">The numbers under the hero.</p>
                </div>
            </div>
        </header>

        <div class="space-y-8 p-5">
            <x-admin-repeatable-fields
                :repeaters="$repeaters"
                setting-key="theme_portfolio_stats"
                label="Hero stats"
                hint="Both halves matter — a bare number reads as filler, so give each one a label. The first row is also repeated on your photo."
                empty-title="No stats yet"
                empty-hint="The strip is hidden until you add at least one."
                add-label="Add stat"
                :max="4"
                :columns="2"
                :fields="[
                    ['name' => 'value', 'label' => 'Value', 'placeholder' => '5+'],
                    ['name' => 'label', 'label' => 'Label', 'placeholder' => 'Years experience'],
                ]" />

            {{-- Services used to be a second repeater on this screen, right under
                 the stats. They are not any more: a service is something bookable,
                 so it has to be a real Service row that a booking can point a
                 foreign key at — and that is edited under Services in the admin.
                 Left in place, the two were about to disagree, with a service
                 edited in one place and not the other. So the repeater and
                 PortfolioProfile::services() were removed rather than kept as a
                 second source. "What I do" on the storefront now renders the
                 active Service rows and hands each one a booking form. --}}
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Services are managed under Services in the admin menu, not here.') }}
            </p>
        </div>
    </section>


    {{-- ══ Projects ════════════════════════════════════════════════════════ --}}
    {{-- Projects and Experience both used to be table-backed with their own
         CRUD screens; they are lists here so the whole portfolio is edited in
         one place. --}}
    <section role="tabpanel" x-show="tab === 'projects'"
        class="isolate overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-700 dark:bg-zinc-900">
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-100 px-5 py-4 dark:border-zinc-700">
            <div class="flex items-center gap-3">
                <span class="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                    <flux:icon.folder variant="mini" class="size-5" />
                </span>
                <div>
                    <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Projects</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">The work you want to be asked about. The storefront shows them in the order given — put your strongest project first.</p>
                </div>
            </div>
        </header>

        <div class="space-y-8 p-5">
            <x-admin-repeatable-fields
                :repeaters="$repeaters"
                setting-key="theme_portfolio_projects"
                label="Projects"
                hint="A project without a description is a title with nothing behind it — one sentence on the problem and your part in it goes a long way."
                empty-title="No projects yet"
                empty-hint="The whole section stays hidden until you add one."
                add-label="Add project"
                :max="12"
                :columns="2"
                :fields="[
                    ['name' => 'title', 'label' => 'Project name', 'placeholder' => 'Hotel Booking System', 'wide' => true],
                    ['name' => 'image', 'label' => 'Screenshot', 'type' => 'media', 'pickerLabel' => 'Project screenshot', 'sizeHint' => 'A 16:10 screenshot, roughly 1280×800'],
                    ['name' => 'icon', 'label' => 'Icon', 'placeholder' => '🏨', 'wide' => true],
                    ['name' => 'description', 'label' => 'What it does', 'type' => 'textarea', 'placeholder' => 'The problem it solved, and your part in it.'],
                    ['name' => 'tech', 'label' => 'Tech stack', 'placeholder' => 'Laravel, MySQL, Stripe', 'wide' => true],
                    ['name' => 'stats', 'label' => 'Badge', 'placeholder' => '12 clients', 'wide' => true],
                    ['name' => 'link', 'label' => 'Live site', 'placeholder' => 'https://example.com', 'wide' => true],
                    ['name' => 'repo', 'label' => 'Source code', 'placeholder' => 'https://github.com/you/project', 'wide' => true],
                ]" />
        </div>
    </section>

    {{-- ══ Experience ══════════════════════════════════════════════════════ --}}
    <section role="tabpanel" x-show="tab === 'experience'"
        class="isolate overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-700 dark:bg-zinc-900">
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-100 px-5 py-4 dark:border-zinc-700">
            <div class="flex items-center gap-3">
                <span class="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                    <flux:icon.briefcase variant="mini" class="size-5" />
                </span>
                <div>
                    <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Experience</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Where you did the work, shown as a timeline in the order given.</p>
                </div>
            </div>
        </header>

        <div class="space-y-8 p-5">
            <x-admin-repeatable-fields
                :repeaters="$repeaters"
                setting-key="theme_portfolio_experiences"
                label="Experience"
                hint="Leave the company blank for freelance or contract work — the company line simply disappears, so an empty value is not a gap on the page."
                empty-title="No experience yet"
                empty-hint="The timeline stays hidden until you add one."
                add-label="Add role"
                :max="12"
                :columns="2"
                :fields="[
                    ['name' => 'role', 'label' => 'Role', 'placeholder' => 'Backend Developer', 'wide' => true],
                    ['name' => 'company', 'label' => 'Company', 'placeholder' => 'Company name (optional)', 'wide' => true],
                    ['name' => 'period', 'label' => 'Period', 'placeholder' => '2022 — 2024', 'wide' => true],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'placeholder' => 'What you owned, and what it changed.'],
                ]" />
        </div>
    </section>

    {{-- ══ Skills ══════════════════════════════════════════════════════════ --}}
    <section role="tabpanel" x-show="tab === 'skills'"
        class="isolate overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-700 dark:bg-zinc-900">
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-100 px-5 py-4 dark:border-zinc-700">
            <div class="flex items-center gap-3">
                <span class="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                    <flux:icon.sparkles variant="mini" class="size-5" />
                </span>
                <div>
                    <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Skills</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Shown as labelled columns, one per group. Reuse a group name exactly to collect skills under the same heading.</p>
                </div>
            </div>
        </header>

        <div class="p-5">
            <x-admin-repeatable-fields
                :repeaters="$repeaters"
                setting-key="theme_portfolio_skills"
                label="Skills"
                hint="A skill with no group is not shown at all, so give every row one — the group is what turns a long list into something scannable."
                empty-title="No skills yet"
                empty-hint="The whole section stays hidden until you add one."
                add-label="Add skill"
                :max="40"
                :columns="2"
                :fields="[
                    ['name' => 'name', 'label' => 'Skill', 'placeholder' => 'Laravel'],
                    ['name' => 'group', 'label' => 'Group', 'placeholder' => 'Backend'],
                    ['name' => 'icon', 'label' => 'Icon', 'placeholder' => '🔴'],
                    ['name' => 'description', 'label' => 'Description', 'placeholder' => 'Framework of choice'],
                ]" />
        </div>
    </section>

    {{-- ══ Testimonials ═════════════════════════════════════════════════════ --}}
    <section role="tabpanel" x-show="tab === 'testimonials'"
        class="isolate overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-700 dark:bg-zinc-900">
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-100 px-5 py-4 dark:border-zinc-700">
            <div class="flex items-center gap-3">
                <span class="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                    <flux:icon.chat-bubble-left-right variant="mini" class="size-5" />
                </span>
                <div>
                    <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Testimonials</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">What clients say about working with you. A quote without an attribution is not a testimonial, so the name and role are worth filling in.</p>
                </div>
            </div>
        </header>

        <div class="p-5">
            <x-admin-repeatable-fields
                :repeaters="$repeaters"
                setting-key="theme_portfolio_testimonials"
                label="Testimonials"
                hint="Only add quotes you were actually given — an invented testimonial is the one thing on this page that can cost you a client."
                empty-title="No testimonials yet"
                empty-hint="The section stays hidden until you add one."
                add-label="Add testimonial"
                :max="12"
                :columns="2"
                :fields="[
                    ['name' => 'quote', 'label' => 'Quote', 'type' => 'textarea', 'placeholder' => 'What they said about the work.'],
                    ['name' => 'name', 'label' => 'Name', 'placeholder' => 'Client name', 'wide' => true],
                    ['name' => 'role', 'label' => 'Role / company', 'placeholder' => 'Founder, Acme Ltd', 'wide' => true],
                    ['name' => 'rating', 'label' => 'Rating (1-5)', 'placeholder' => '5', 'wide' => true],
                ]" />
        </div>
    </section>

    {{-- ══ Credentials ═══════════════════════════════════════════════════ --}}
    <section role="tabpanel" x-show="tab === 'credentials'"
        class="isolate overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-700 dark:bg-zinc-900">
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-100 px-5 py-4 dark:border-zinc-700">
            <div class="flex items-center gap-3">
                <span class="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                    <flux:icon.academic-cap variant="mini" class="size-5" />
                </span>
                <div>
                    <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Education &amp; certifications</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Shown beside the work timeline. Each block hides itself when empty, so a gap is never advertised.</p>
                </div>
            </div>
        </header>

        <div class="grid grid-cols-1 gap-8 p-5 xl:grid-cols-2">
            <x-admin-repeatable-fields
                :repeaters="$repeaters"
                setting-key="theme_portfolio_education"
                label="Education"
                empty-title="No education yet"
                add-label="Add qualification"
                :max="8"
                :fields="[
                    ['name' => 'title', 'label' => 'Degree / Qualification', 'placeholder' => 'B.Sc. in Computer Science', 'wide' => true],
                    ['name' => 'period', 'label' => 'Period', 'placeholder' => '2018 — 2022', 'wide' => true],
                    ['name' => 'description', 'label' => 'Institution', 'placeholder' => 'University name', 'wide' => true],
                ]" />

            <x-admin-repeatable-fields
                :repeaters="$repeaters"
                setting-key="theme_portfolio_certifications"
                label="Certifications"
                empty-title="No certifications yet"
                add-label="Add certification"
                :max="12"
                :fields="[
                    ['name' => 'title', 'label' => 'Certification', 'placeholder' => 'AWS Certified Developer', 'wide' => true],
                    ['name' => 'period', 'label' => 'Year', 'placeholder' => '2024', 'wide' => true],
                    ['name' => 'description', 'label' => 'Issuer', 'placeholder' => 'Amazon Web Services', 'wide' => true],
                ]" />
        </div>
    </section>
    </div>
</div>

