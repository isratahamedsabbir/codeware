@php
    // Everything the Display options dropdown can hide. The Low Stock card is
    // only offered while the Products feature is on, because it is not rendered
    // otherwise and a toggle for it would do nothing.
    $dashboardCards = [
        'products' => ['label' => 'Products', 'icon' => 'cube', 'group' => 'stats', 'accent' => 'bg-blue-50 text-blue-600 dark:bg-blue-500/15 dark:text-blue-400'],
        'posts' => ['label' => 'Posts', 'icon' => 'document-text', 'group' => 'stats', 'accent' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400'],
        'pages' => ['label' => 'Pages', 'icon' => 'document', 'group' => 'stats', 'accent' => 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-400'],
        'media' => ['label' => 'Media files', 'icon' => 'photo', 'group' => 'stats', 'accent' => 'bg-sky-50 text-sky-600 dark:bg-sky-500/15 dark:text-sky-400'],
        'overview' => ['label' => 'Workspace overview', 'icon' => 'chart-bar', 'group' => 'charts', 'accent' => 'bg-primary/10 text-primary dark:bg-primary/20 dark:text-primary'],
        'post-status' => ['label' => 'Posts by status', 'icon' => 'chart-pie', 'group' => 'charts', 'accent' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400'],
        'low-stock' => ['label' => 'Low stock alerts', 'icon' => 'exclamation-triangle', 'group' => 'alerts', 'accent' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400'],
    ];

    if (! $productsEnabled) {
        unset($dashboardCards['low-stock']);
    }

    $dashboardCardGroups = [
        'stats' => 'Stat cards',
        'charts' => 'Charts',
        'alerts' => 'Alerts',
    ];
@endphp

<div class="max-w-[1600px]"
     x-data="{
         cardIds: [@foreach (array_keys($dashboardCards) as $cardId)'{{ $cardId }}'@if (! $loop->last), @endif @endforeach],
         hiddenCards: (() => {
             try {
                 const saved = JSON.parse(localStorage.getItem('admin.dashboard.hidden-cards') || '[]');
                 return Array.isArray(saved) ? saved : [];
             } catch (error) {
                 return [];
             }
         })(),
         isCardHidden(id) {
             return this.hiddenCards.includes(id);
         },
         allCardsHidden() {
             return this.cardIds.length > 0 && this.cardIds.every(id => this.isCardHidden(id));
         },
         toggleCard(id) {
             this.hiddenCards = this.isCardHidden(id)
                 ? this.hiddenCards.filter(card => card !== id)
                 : [...this.hiddenCards, id];
             localStorage.setItem('admin.dashboard.hidden-cards', JSON.stringify(this.hiddenCards));
         },
         showAllCards() {
             this.hiddenCards = [];
             localStorage.removeItem('admin.dashboard.hidden-cards');
         },
     }">

    {{-- Greeting hero --}}
    <div class="admin-dashboard-hero flex flex-col lg:flex-row lg:items-center justify-between gap-4 rounded-xl! px-5 py-4 mb-6">
        <div class="min-w-0">
            <div class="flex items-center gap-2">
                <h1 class="text-xl font-extrabold tracking-tight text-zinc-900">Welcome back, {{ auth()->user()->name }}</h1>
            </div>
            <div class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-zinc-500 font-medium">
                <span class="inline-flex items-center gap-1.5">
                    <flux:icon.calendar class="size-4 text-primary" />
                    {{ now()->toDisplay('l, d F Y') }}
                </span>
                <span class="text-zinc-300 select-none hidden sm:inline">&bull;</span>
                <span class="inline-flex items-center gap-1.5">
                    <flux:icon.chart-bar class="size-4 text-secondary" />
                    {{ number_format($totalProducts + $totalPosts + $totalPages + $totalMedia) }} items in your workspace
                </span>
                <span class="text-zinc-300 select-none hidden sm:inline">&bull;</span>
                <span class="inline-flex items-center gap-1.5">
                    <flux:icon.server-stack class="size-4 text-indigo-500" />
                    {{ $totalMediaSize }} of media
                </span>
            </div>
        </div>

        <div class="flex items-center gap-3 lg:justify-end">
            <div class="hidden lg:flex items-center -space-x-2">
                <span class="size-9 rounded-full bg-primary/10 text-primary flex items-center justify-center ring-2 ring-white">
                    <flux:icon.sparkles class="size-4" />
                </span>
                <span class="size-9 rounded-full bg-secondary/10 text-secondary flex items-center justify-center ring-2 ring-white">
                    <flux:icon.chart-pie class="size-4" />
                </span>
                <span class="size-9 rounded-full bg-indigo-50 text-indigo-500 flex items-center justify-center ring-2 ring-white">
                    <flux:icon.square-2-stack class="size-4" />
                </span>
            </div>

            {{-- Display options: which dashboard cards are visible. Saved in
                 localStorage so each admin keeps their own layout without a
                 round-trip (and the charts are never torn down and rebuilt). --}}
            <flux:dropdown position="bottom" align="end">
                <flux:button size="sm" square icon="adjustments-horizontal"
                             aria-label="Display options" title="Display options"
                             class="!size-9 !rounded-full !border-white/70 !bg-white/75 !text-zinc-600 shadow-sm backdrop-blur-md transition-colors hover:!bg-white hover:!text-zinc-900 dark:!border-white/10 dark:!bg-zinc-800/70 dark:!text-zinc-300 dark:hover:!bg-zinc-800 dark:hover:!text-white">
                </flux:button>

                <flux:menu class="w-[21rem] !rounded-xl !border-zinc-200/80 !p-0 !shadow-xl dark:!border-zinc-600/70">
                    {{-- Header --}}
                    <div class="flex items-start gap-3 border-b border-zinc-100 px-4 py-3.5 dark:border-zinc-600/60">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <flux:icon.adjustments-horizontal class="size-5" />
                        </span>
                        <div class="min-w-0">
                            <p class="text-[13px] font-bold text-zinc-800 dark:text-white">Customize dashboard</p>
                            <p class="mt-0.5 text-[11px] font-medium text-zinc-400">Pick the cards you want to see</p>
                        </div>
                    </div>

                    {{-- Cards, grouped --}}
                    <div class="max-h-[22rem] overflow-y-auto p-2">
                        @foreach ($dashboardCardGroups as $groupKey => $groupLabel)
                            @php $groupCards = array_filter($dashboardCards, fn ($card) => $card['group'] === $groupKey); @endphp
                            @if ($groupCards !== [])
                                <div class="px-2 pb-1 pt-2 text-[10px] font-bold uppercase tracking-[0.14em] text-zinc-400">
                                    {{ $groupLabel }}
                                </div>
                                @foreach ($groupCards as $cardId => $card)
                                    {{-- A div, not a <button>: Flux turns every button inside a
                                         menu into a closable menu item, and this panel has to stay
                                         open while several cards are toggled. --}}
                                    <div role="menuitemcheckbox" tabindex="0"
                                         x-bind:aria-checked="! isCardHidden('{{ $cardId }}')"
                                         x-on:click="toggleCard('{{ $cardId }}')"
                                         x-on:keydown.enter.prevent="toggleCard('{{ $cardId }}')"
                                         x-on:keydown.space.prevent="toggleCard('{{ $cardId }}')"
                                         class="group flex w-full cursor-pointer items-center gap-3 rounded-lg px-2 py-2 text-start transition-colors hover:bg-zinc-50 focus-visible:bg-zinc-50 focus:outline-none dark:hover:bg-zinc-700/60 dark:focus-visible:bg-zinc-700/60">
                                        <span class="flex size-8 shrink-0 items-center justify-center rounded-lg {{ $card['accent'] }}">
                                            <flux:icon :name="$card['icon']" class="size-4" />
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-[13px] font-semibold text-zinc-700 dark:text-zinc-100">
                                                {{ $card['label'] }}
                                            </span>
                                            <span class="mt-0.5 block text-[10.5px] font-medium text-zinc-400"
                                                  x-text="isCardHidden('{{ $cardId }}') ? 'Hidden from dashboard' : 'Visible on dashboard'"></span>
                                        </span>
                                        <span class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors duration-200"
                                              x-bind:class="isCardHidden('{{ $cardId }}') ? 'bg-zinc-200 dark:bg-zinc-600' : 'bg-primary'">
                                            <span class="absolute left-0.5 size-4 rounded-full bg-white shadow-sm transition-transform duration-200"
                                                  x-bind:class="isCardHidden('{{ $cardId }}') ? 'translate-x-0' : 'translate-x-4'"></span>
                                        </span>
                                    </div>
                                @endforeach
                            @endif
                        @endforeach
                    </div>

                    {{-- Footer --}}
                    <div class="flex items-center justify-between gap-3 rounded-b-xl border-t border-zinc-100 bg-zinc-50/70 px-3 py-2 dark:border-zinc-600/60 dark:bg-zinc-800/40">
                        <div role="button" tabindex="0"
                             x-on:click="showAllCards()"
                             x-on:keydown.enter.prevent="showAllCards()"
                             x-on:keydown.space.prevent="showAllCards()"
                             class="inline-flex cursor-pointer items-center gap-1.5 rounded-md px-1.5 py-1 text-[11px] font-semibold text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-zinc-800 focus:outline-none dark:text-zinc-400 dark:hover:bg-zinc-700 dark:hover:text-white">
                            <flux:icon.arrow-path class="size-3" />
                            Reset
                        </div>
                        <span class="inline-flex items-center gap-1 text-[10px] font-medium text-zinc-400">
                            <flux:icon.check-circle class="size-3" />
                            Saved on this device
                        </span>
                    </div>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6"
         x-show="! isCardHidden('products') || ! isCardHidden('posts') || ! isCardHidden('pages') || ! isCardHidden('media')">

        <a href="{{ route('admin.products') }}" wire:navigate.hover
           x-show="! isCardHidden('products')"
           class="admin-card admin-stat-card admin-showcase-stat relative border-0! shadow-sm! block group no-underline">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-zinc-500">Products</p>
                    <p class="admin-stat-value mt-1 font-extrabold text-zinc-900 leading-6 tabular-nums">{{ $totalProducts }}</p>
                    <p class="mt-1.5 inline-flex items-center gap-1 text-[11px] font-medium text-zinc-400">
                        <span class="inline-flex items-center gap-0.5 rounded-full bg-blue-50 px-1.5 py-0.5 text-blue-600 font-semibold">
                            <flux:icon.arrow-trending-up class="size-3" />
                            +{{ $productsThisMonth }}
                        </span>
                        this month
                    </p>
                </div>
                <div class="admin-stat-icon bg-blue-100 group-hover:scale-110 group-hover:-rotate-3 transition-transform duration-300">
                    <flux:icon.cube class="size-6 text-blue-600" />
                </div>
            </div>
        </a>

        <a href="{{ route('admin.posts') }}" wire:navigate.hover
           x-show="! isCardHidden('posts')"
           class="admin-card admin-stat-card admin-showcase-stat relative border-0! shadow-sm! block group no-underline">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-zinc-500">Posts</p>
                    <p class="admin-stat-value mt-1 font-extrabold text-zinc-900 leading-6 tabular-nums">{{ $totalPosts }}</p>
                    <p class="mt-1.5 inline-flex items-center gap-1 text-[11px] font-medium text-zinc-400">
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-1.5 py-0.5 text-emerald-600 font-semibold">
                            <flux:icon.circle-stack class="size-3" />
                            {{ $publishedPosts }} published
                        </span>
                        <span class="inline-flex items-center gap-0.5 rounded-full bg-blue-50 px-1.5 py-0.5 text-blue-600 font-semibold">
                            <flux:icon.arrow-trending-up class="size-3" />
                            +{{ $postsThisMonth }}
                        </span>
                    </p>
                </div>
                <div class="admin-stat-icon bg-emerald-100 group-hover:scale-110 group-hover:-rotate-3 transition-transform duration-300">
                    <flux:icon.document-text class="size-6 text-emerald-600" />
                </div>
            </div>
        </a>

        <a href="{{ route('admin.pages') }}" wire:navigate.hover
           x-show="! isCardHidden('pages')"
           class="admin-card admin-stat-card admin-showcase-stat relative border-0! shadow-sm! block group no-underline">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-zinc-500">Pages</p>
                    <p class="admin-stat-value mt-1 font-extrabold text-zinc-900 leading-6 tabular-nums">{{ $totalPages }}</p>
                    <p class="mt-1.5 inline-flex items-center gap-1 text-[11px] font-medium text-zinc-400">
                        <span class="inline-flex items-center gap-0.5 rounded-full bg-indigo-50 px-1.5 py-0.5 text-indigo-600 font-semibold">
                            <flux:icon.arrow-trending-up class="size-3" />
                            +{{ $pagesThisMonth }}
                        </span>
                        this month
                    </p>
                </div>
                <div class="admin-stat-icon bg-indigo-100 group-hover:scale-110 group-hover:-rotate-3 transition-transform duration-300">
                    <flux:icon.document class="size-6 text-indigo-600" />
                </div>
            </div>
        </a>

        <a href="{{ route('admin.media-library') }}" wire:navigate.hover
           x-show="! isCardHidden('media')"
           class="admin-card admin-stat-card admin-showcase-stat relative border-0! shadow-sm! block group no-underline">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-zinc-500">Media Files</p>
                    <p class="admin-stat-value mt-1 font-extrabold text-zinc-900 leading-6 tabular-nums">{{ $totalMedia }}</p>
                    <p class="mt-1.5 inline-flex items-center gap-1 text-[11px] font-medium text-zinc-400">
                        <span class="inline-flex items-center gap-0.5 rounded-full bg-sky-50 px-1.5 py-0.5 text-sky-600 font-semibold">
                            <flux:icon.arrow-trending-up class="size-3" />
                            +{{ $mediaThisMonth }}
                        </span>
                        this month &bull; <span class="font-semibold tabular-nums text-zinc-500">{{ $totalMediaSize }}</span>
                    </p>
                </div>
                <div class="admin-stat-icon bg-sky-100 group-hover:scale-110 group-hover:-rotate-3 transition-transform duration-300">
                    <flux:icon.photo class="size-6 text-sky-600" />
                </div>
            </div>
        </a>

    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6"
         x-show="! isCardHidden('overview') || ! isCardHidden('post-status')">
        <div class="admin-card p-5 shadow-sm! lg:col-span-2 relative overflow-hidden"
             x-show="! isCardHidden('overview')">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="size-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <flux:icon.chart-bar class="size-5" />
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-zinc-800">Workspace overview</h2>
                        <p class="text-xs text-zinc-400 mt-0.5">Content distribution across your workspace</p>
                    </div>
                </div>
                <span class="hidden sm:inline-flex items-center gap-1.5 rounded-full bg-primary/5 px-3 py-1 text-xs font-semibold text-primary tabular-nums ring-1 ring-primary/10">
                    <span class="size-1.5 rounded-full bg-primary"></span>
                    {{ number_format($totalProducts + $totalPosts + $totalPages + $totalMedia) }} items
                </span>
            </div>
            <div class="h-64">
                <canvas id="admin-content-bar-chart"
                    data-labels="{{ json_encode(['Products', 'Posts', 'Pages', 'Media']) }}"
                    data-values="{{ json_encode([$totalProducts, $totalPosts, $totalPages, $totalMedia]) }}"></canvas>
            </div>
        </div>

        <div class="admin-card p-5 shadow-sm! relative overflow-hidden"
             x-show="! isCardHidden('post-status')"
             x-bind:class="isCardHidden('overview') ? 'lg:col-span-3' : ''">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="size-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 ring-1 ring-emerald-100">
                        <flux:icon.chart-pie class="size-5" />
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-zinc-800">Posts by status</h2>
                        <p class="text-xs text-zinc-400 mt-0.5">Publishing breakdown</p>
                    </div>
                </div>
            </div>
            @if ($totalPosts > 0)
                <div class="h-56">
                    <canvas id="admin-posts-donut-chart"
                        data-labels="{{ json_encode(['Published', 'Draft']) }}"
                        data-values="{{ json_encode([$publishedPosts, $draftPosts]) }}"></canvas>
                </div>
                <div class="flex items-center justify-center gap-4 mt-3 text-xs font-medium text-zinc-500">
                    <span class="flex items-center gap-1.5">
                        <span class="admin-chart-legend-dot bg-chart-published"></span>
                        Published ({{ $publishedPosts }})
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="admin-chart-legend-dot bg-chart-draft"></span>
                        Draft ({{ $draftPosts }})
                    </span>
                </div>
            @else
                <div class="h-48 flex flex-col items-center justify-center gap-2 text-zinc-400">
                    <flux:icon.chart-pie class="size-8 text-zinc-200" />
                    <p class="text-sm font-medium">No posts yet.</p>
                </div>
            @endif
        </div>
    </div>

    @assets
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js" crossorigin="anonymous"></script>
    @endassets

    @script
        <script>
            (() => {
                const rootStyles = getComputedStyle(document.documentElement);
                const cssColor = (name) => rootStyles.getPropertyValue(name).trim();

                const barColors = ['--color-chart-bar-1', '--color-chart-bar-2', '--color-chart-bar-3', '--color-chart-bar-4'].map(cssColor);
                const pieColors = ['--color-chart-published', '--color-chart-draft'].map(cssColor);
                const tooltipBg = cssColor('--color-chart-tooltip-bg');
                const axisTextColor = cssColor('--color-chart-axis-text');
                const gridColor = cssColor('--color-chart-grid');
                const categoryTextColor = cssColor('--color-chart-category-text');
                const white = cssColor('--color-white');
                const charts = {};

                const destroy = (id) => {
                    if (charts[id]) {
                        charts[id].destroy();
                        delete charts[id];
                    }
                };

                const renderCharts = () => {
                    const barCanvas = document.getElementById('admin-content-bar-chart');
                    if (barCanvas) {
                        destroy('bar');
                        charts.bar = new Chart(barCanvas, {
                            type: 'bar',
                            data: {
                                labels: JSON.parse(barCanvas.dataset.labels),
                                datasets: [{
                                    data: JSON.parse(barCanvas.dataset.values),
                                    backgroundColor: barColors,
                                    borderRadius: { topLeft: 7, topRight: 7, bottomLeft: 0, bottomRight: 0 },
                                    borderSkipped: false,
                                    maxBarThickness: 40,
                                }],
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        backgroundColor: tooltipBg,
                                        padding: 10,
                                        cornerRadius: 8,
                                        displayColors: false,
                                    },
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: { precision: 0, color: axisTextColor, font: { size: 11 } },
                                        grid: { color: gridColor },
                                        border: { display: false },
                                    },
                                    x: {
                                        ticks: { color: categoryTextColor, font: { size: 12, weight: '600' } },
                                        grid: { display: false },
                                        border: { display: false },
                                    },
                                },
                            },
                        });
                    }

                    const donutCanvas = document.getElementById('admin-posts-donut-chart');
                    if (donutCanvas) {
                        destroy('donut');
                        charts.donut = new Chart(donutCanvas, {
                            type: 'pie',
                            data: {
                                labels: JSON.parse(donutCanvas.dataset.labels),
                                datasets: [{
                                    data: JSON.parse(donutCanvas.dataset.values),
                                    backgroundColor: pieColors,
                                    borderColor: white,
                                    borderWidth: 2,
                                }],
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        backgroundColor: tooltipBg,
                                        padding: 10,
                                        cornerRadius: 8,
                                        displayColors: false,
                                    },
                                },
                            },
                        });
                    }
                };

                renderCharts();
            })();
        </script>
    @endscript

    {{-- Low Stock Alerts -- only while the Products feature is enabled. --}}
    @if ($productsEnabled)
        <div class="mb-6" x-show="! isCardHidden('low-stock')">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-xs font-bold text-zinc-800 uppercase tracking-widest inline-flex items-center gap-2">
                    <span class="size-6 rounded-lg bg-amber-50 text-amber-500 flex items-center justify-center ring-1 ring-amber-100">
                        <flux:icon.exclamation-triangle class="size-3.5" />
                    </span>
                    Low Stock Alerts
                    @if ($lowStockProducts->count() > 0)
                        <span class="rounded-full bg-amber-100 text-amber-700 px-2 py-0.5 text-[10px] font-bold tabular-nums">
                            {{ $lowStockProducts->count() }}
                        </span>
                    @endif
                </h2>
                <a href="{{ route('admin.products') }}" wire:navigate.hover
                   class="text-xs font-bold text-primary hover:text-blue-700 transition-colors flex items-center gap-1">
                    Manage Products
                    <flux:icon.chevron-right class="size-3" />
                </a>
            </div>

            <div class="admin-card shadow-sm! overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-zinc-50 text-left">
                                <th class="px-4 py-2.5 text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Product</th>
                                <th class="hidden md:table-cell px-4 py-2.5 text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Category</th>
                                <th class="px-4 py-2.5 text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Stock</th>
                                <th class="px-4 py-2.5 text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider text-right">Price</th>
                                <th class="px-4 py-2.5 text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider text-center">Status</th>
                                <th class="px-4 py-2.5 text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            @forelse ($lowStockProducts as $product)
                                <tr class="hover:bg-zinc-50/70 transition-colors">
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-zinc-800">
                                            {{ $product->getTranslation('name', 'en', false) }}
                                        </p>
                                        @if ($product->code)
                                            <p class="font-mono text-[11px] text-zinc-400 leading-none mt-0.5">{{ $product->code }}</p>
                                        @endif
                                    </td>
                                    <td class="hidden md:table-cell px-4 py-3 text-zinc-500">
                                        {{ $product->categories->isNotEmpty() ? $product->categories->map(fn ($c) => $c->getTranslation('name', 'en', false))->implode(', ') : '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ((int) $product->quantity === 0)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-rose-50 text-rose-600 border border-rose-200 whitespace-nowrap">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                                Out of stock
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200 whitespace-nowrap">
                                                <flux:icon.exclamation-triangle class="w-3 h-3" />
                                                {{ (int) $product->quantity }} left
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right tabular-nums text-zinc-700 font-medium">
                                        {{ number_format((float) $product->price, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium whitespace-nowrap
                                            {{ $product->status === 'active' ? 'bg-green-50 text-green-600 border border-green-200' : 'bg-red-50 text-red-600 border border-red-200' }}">
                                            {{ ucfirst($product->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <a href="{{ route('admin.products.edit', $product->id) }}" wire:navigate.hover
                                           class="text-xs font-bold text-primary hover:text-blue-700 transition-colors">
                                            Restock
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-10 text-center">
                                        <flux:icon.check-circle class="size-8 text-zinc-200 mx-auto mb-2" />
                                        <p class="text-sm font-medium text-zinc-400">All products are well stocked.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- Shown only when every optional card is hidden, so the page never looks
         broken and there is always a way back. --}}
    <div x-show="allCardsHidden()" x-cloak class="admin-card p-10 text-center shadow-sm!">
        <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-zinc-100 text-zinc-400">
            <flux:icon.eye-slash class="size-6" />
        </div>
        <h2 class="mt-3 text-sm font-bold text-zinc-800">All cards are hidden</h2>
        <p class="mt-1 text-xs font-medium text-zinc-400">
            Use Display options above to bring individual cards back, or show them all at once.
        </p>
        <flux:button size="sm" variant="filled" icon="eye" class="mt-4" x-on:click="showAllCards()">
            Show all cards
        </flux:button>
    </div>

</div>