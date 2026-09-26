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
<div
    x-data="{
        tab: (() => { try { return localStorage.getItem('theme-portfolio-tab') || 'profile' } catch (e) { return 'profile' } })(),
        open(name) { this.tab = name; try { localStorage.setItem('theme-portfolio-tab', name) } catch (e) {} },
    }"
    class="space-y-5"
>
    <div role="tablist" class="flex gap-1 border-b border-zinc-200 dark:border-zinc-700">
        @foreach ([
            'profile' => ['Profile', 'user-circle'],
            'sections' => ['Sections', 'squares-2x2'],
            'work' => ['Projects & experience', 'briefcase'],
            'skills' => ['Skills', 'sparkles'],
            'testimonials' => ['Testimonials', 'chat-bubble-left-right'],
            'credentials' => ['Credentials', 'academic-cap'],
        ] as $tabKey => [$tabLabel, $tabIcon])
            <button type="button" role="tab" @click="open('{{ $tabKey }}')"
                :aria-selected="tab === '{{ $tabKey }}'"
                :class="tab === '{{ $tabKey }}'
                    ? 'border-primary text-primary'
                    : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:hover:text-zinc-200'"
                class="-mb-px inline-flex items-center gap-2 rounded-none! border-b-2 px-4 py-2.5 text-sm font-semibold transition-colors">
                <flux:icon :name="$tabIcon" variant="mini" class="size-4" />
                {{ $tabLabel }}
            </button>
        @endforeach
    </div>

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

        <div class="grid grid-cols-1 gap-6 p-5 lg:grid-cols-[1fr_15rem]">
            <div class="space-y-5">
                <flux:field>
                    <flux:label>Display Name<x-field-hint text="Leave blank to use the site name from Settings." /></flux:label>
                    <flux:input wire:model="settings.theme_portfolio_name" placeholder="e.g. Sabbir Hossain" />
                </flux:field>

                <flux:field>
                    <flux:label>Role<x-field-hint text="The line under your name — 'Full Stack Developer', 'Laravel & React Engineer'." /></flux:label>
                    <flux:input wire:model="settings.theme_portfolio_hero_title" placeholder="Full Stack Developer" />
                </flux:field>

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
            </div>

            <div class="space-y-4">
                <x-media-picker model="settings.theme_portfolio_photo" label="Hero Photo"
                    size-hint="A portrait, roughly 4:5" only-images />

                <p class="text-[11px] leading-relaxed text-zinc-400">
                    Without a photo the hero shows your initials on a tinted panel, so the page still reads as finished.
                </p>

                <div class="space-y-4 border-t border-zinc-100 pt-4 dark:border-zinc-700">
                    <flux:field>
                        <flux:label>Résumé URL<x-field-hint text="Blank hides the download button." /></flux:label>
                        <flux:input wire:model="settings.theme_portfolio_resume_url" placeholder="https://…/resume.pdf" />
                    </flux:field>

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
                    <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Hero stats &amp; services</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">The numbers under the hero, and what a visitor can hire you for.</p>
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
                :fields="[
                    ['name' => 'value', 'label' => 'Value', 'placeholder' => '5+'],
                    ['name' => 'label', 'label' => 'Label', 'placeholder' => 'Years experience'],
                ]" />

            <div class="border-t border-zinc-100 pt-6 dark:border-zinc-700">
                <x-admin-repeatable-fields
                    :repeaters="$repeaters"
                    setting-key="theme_portfolio_services"
                    label="What I do"
                    hint="Two to four reads best; three fills a desktop row exactly."
                    empty-title="No services yet"
                    empty-hint="The whole section stays hidden until you add one."
                    add-label="Add service"
                    :max="9"
                    :fields="[
                        ['name' => 'title', 'label' => 'Title', 'placeholder' => 'Laravel Application Development'],
                        ['name' => 'icon', 'label' => 'Icon', 'placeholder' => '⚙️'],
                        ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'placeholder' => 'What the client gets, in one or two lines.'],
                    ]" />
            </div>
        </div>
    </section>

    {{-- ══ Work ════════════════════════════════════════════════════════════ --}}
    {{-- Projects and Experience. Both used to be table-backed with their own
         CRUD screens; they are lists here so the whole portfolio is edited in
         one place. --}}
    <section role="tabpanel" x-show="tab === 'work'"
        class="isolate overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-700 dark:bg-zinc-900">
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-100 px-5 py-4 dark:border-zinc-700">
            <div class="flex items-center gap-3">
                <span class="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                    <flux:icon.briefcase variant="mini" class="size-5" />
                </span>
                <div>
                    <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Projects &amp; experience</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">The work you want to be asked about, and where you did it. The storefront shows them in the order given — put your strongest project first.</p>
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
                :fields="[
                    ['name' => 'title', 'label' => 'Project name', 'placeholder' => 'Hotel Booking System'],
                    ['name' => 'icon', 'label' => 'Icon', 'placeholder' => '🏨'],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'placeholder' => 'What it does and what you built.'],
                    ['name' => 'tech', 'label' => 'Tech stack', 'placeholder' => 'Laravel, MySQL, Stripe'],
                    ['name' => 'stats', 'label' => 'Badge', 'placeholder' => '12 clients'],
                    ['name' => 'link', 'label' => 'Link', 'placeholder' => 'https://example.com'],
                ]" />

            <div class="border-t border-zinc-100 pt-6 dark:border-zinc-700">
                <x-admin-repeatable-fields
                    :repeaters="$repeaters"
                    setting-key="theme_portfolio_experiences"
                    label="Experience"
                    hint="Leave the company blank for freelance or contract work — the company line simply disappears, so an empty value is not a gap on the page."
                    empty-title="No experience yet"
                    empty-hint="The timeline stays hidden until you add one."
                    add-label="Add role"
                    :max="12"
                    :fields="[
                        ['name' => 'role', 'label' => 'Role', 'placeholder' => 'Backend Developer'],
                        ['name' => 'company', 'label' => 'Company', 'placeholder' => 'Company name (optional)'],
                        ['name' => 'period', 'label' => 'Period', 'placeholder' => '2022 — 2024'],
                        ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'placeholder' => 'What you owned, and what it changed.'],
                    ]" />
            </div>
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
                :fields="[
                    ['name' => 'quote', 'label' => 'Quote', 'type' => 'textarea', 'placeholder' => 'What they said about the work.'],
                    ['name' => 'name', 'label' => 'Name', 'placeholder' => 'Client name'],
                    ['name' => 'role', 'label' => 'Role / company', 'placeholder' => 'Founder, Acme Ltd'],
                    ['name' => 'rating', 'label' => 'Rating (1-5)', 'placeholder' => '5'],
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
                    ['name' => 'title', 'label' => 'Degree / Qualification', 'placeholder' => 'B.Sc. in Computer Science'],
                    ['name' => 'period', 'label' => 'Period', 'placeholder' => '2018 — 2022'],
                    ['name' => 'description', 'label' => 'Institution', 'placeholder' => 'University name'],
                ]" />

            <x-admin-repeatable-fields
                :repeaters="$repeaters"
                setting-key="theme_portfolio_certifications"
                label="Certifications"
                empty-title="No certifications yet"
                add-label="Add certification"
                :max="12"
                :fields="[
                    ['name' => 'title', 'label' => 'Certification', 'placeholder' => 'AWS Certified Developer'],
                    ['name' => 'period', 'label' => 'Year', 'placeholder' => '2024'],
                    ['name' => 'description', 'label' => 'Issuer', 'placeholder' => 'Amazon Web Services'],
                ]" />
        </div>
    </section>
</div>
