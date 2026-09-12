<div class="max-w-[1600px] space-y-5">

    {{-- Company header --}}
    <div class="rounded-[5px] bg-linear-to-br from-primary to-secondary p-8 text-white shadow-sm">
        <div class="flex items-center gap-5">
            <img src="{{ \App\Models\Setting::get('site_icon') ?: '/default/logo.png' }}" alt="Codeware Limited"
                class="size-16 rounded-lg bg-white/10 p-2 shrink-0 object-contain" />
            <div class="min-w-0">
                <flux:heading size="xl" class="text-white!">Codeware Limited</flux:heading>
                <p class="mt-1 text-white/80 text-sm">A passionate creative team to design &amp; development of beautiful creations.</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">

        {{-- About + Services --}}
        <div class="lg:col-span-2 space-y-5">
            <x-admin-section-card icon="building-office" title="About the Company">
                <flux:text class="text-sm leading-relaxed">
                    Codeware Limited is a Bangladesh and UK-based software company specializing in high-quality
                    development of software, web, and mobile app solutions. Our team of experienced IT professionals
                    is dedicated to understanding client needs and implementing strategic, reliable solutions.
                </flux:text>
            </x-admin-section-card>

            <x-admin-section-card icon="squares-2x2" title="Services" body-class="px-6 py-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach ([
                        ['globe-alt', 'Web Design & Development'],
                        ['paint-brush', 'Graphic Design'],
                        ['device-phone-mobile', 'Android & iOS App Development'],
                        ['code-bracket', 'Custom Software Development'],
                        ['cloud', 'SaaS Development'],
                        ['megaphone', 'Digital Marketing'],
                        ['share', 'Social Media Marketing'],
                        ['magnifying-glass', 'Search Engine Optimization'],
                    ] as [$icon, $service])
                        <div class="flex items-center gap-3 rounded-lg border border-zinc-200 dark:border-zinc-700 px-3.5 py-2.5">
                            <span class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-primary shrink-0">
                                <x-dynamic-component :component="'flux::icon.'.$icon" class="size-4" />
                            </span>
                            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $service }}</span>
                        </div>
                    @endforeach
                </div>
            </x-admin-section-card>
        </div>

        {{-- Contact + Social --}}
        <div class="space-y-5">
            <x-admin-section-card icon="phone" title="Contact">
                <div class="space-y-3 text-sm">
                    <div class="flex items-start gap-2.5">
                        <flux:icon.map-pin class="size-4 text-zinc-400 shrink-0 mt-0.5" />
                        <span class="text-zinc-600 dark:text-zinc-400">House #629-685, Road #12, Baitul Aman Housing Society, Adabor, Mohammadpur, Dhaka-1207, Bangladesh</span>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <flux:icon.envelope class="size-4 text-zinc-400 shrink-0" />
                        <a href="mailto:info@codewareltd.com" class="text-zinc-600 dark:text-zinc-400 hover:text-primary transition-colors">info@codewareltd.com</a>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <flux:icon.phone class="size-4 text-zinc-400 shrink-0" />
                        <a href="tel:+8801672691228" class="text-zinc-600 dark:text-zinc-400 hover:text-primary transition-colors">+880 1672-691228</a>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <flux:icon.globe-alt class="size-4 text-zinc-400 shrink-0" />
                        <a href="https://www.codewareltd.com" target="_blank" rel="noopener" class="text-zinc-600 dark:text-zinc-400 hover:text-primary transition-colors">codewareltd.com</a>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-800 flex items-center gap-2">
                    <a href="https://www.facebook.com/CodeWareLTD" target="_blank" rel="noopener"
                        class="flex size-8 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-500 hover:bg-primary/10 hover:text-primary transition-colors" aria-label="Facebook">
                        <flux:icon.globe-alt class="size-4" />
                    </a>
                    <a href="https://twitter.com/codewareltd" target="_blank" rel="noopener"
                        class="flex size-8 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-500 hover:bg-primary/10 hover:text-primary transition-colors" aria-label="Twitter">
                        <flux:icon.at-symbol class="size-4" />
                    </a>
                    <a href="https://www.linkedin.com/company/codeware-limited" target="_blank" rel="noopener"
                        class="flex size-8 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-500 hover:bg-primary/10 hover:text-primary transition-colors" aria-label="LinkedIn">
                        <flux:icon.briefcase class="size-4" />
                    </a>
                </div>
            </x-admin-section-card>

            <x-admin-section-card icon="cube" title="This Software">
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 divide-y divide-zinc-100 dark:divide-zinc-800 text-sm">
                    <div class="flex items-center justify-between px-4 py-2.5">
                        <span class="text-zinc-500">Version</span>
                        <span class="font-mono text-zinc-700 dark:text-zinc-300">{{ config('app.version') }}</span>
                    </div>
                    <div class="flex items-center justify-between px-4 py-2.5">
                        <span class="text-zinc-500">Laravel</span>
                        <span class="font-mono text-zinc-700 dark:text-zinc-300">{{ app()->version() }}</span>
                    </div>
                    <div class="flex items-center justify-between px-4 py-2.5">
                        <span class="text-zinc-500">PHP</span>
                        <span class="font-mono text-zinc-700 dark:text-zinc-300">{{ PHP_VERSION }}</span>
                    </div>
                </div>
                <flux:text class="text-xs text-zinc-400 text-center mt-3">
                    &copy; {{ date('Y') }} {{ \App\Models\Setting::get('copyright_name', 'Codeware Limited') }}. All rights reserved.
                </flux:text>
            </x-admin-section-card>
        </div>

    </div>
</div>
