<div class="max-w-[1600px]">

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
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

        <a href="{{ route('admin.products') }}" wire:navigate.hover
           class="admin-card admin-stat-card admin-showcase-stat relative border-0! border-t-4! border-primary! shadow-sm! block group no-underline">
            <span class="admin-stat-card-bar bg-primary"></span>
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
           class="admin-card admin-stat-card admin-showcase-stat relative border-0! border-t-4! border-secondary! shadow-sm! block group no-underline">
            <span class="admin-stat-card-bar bg-secondary"></span>
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
           class="admin-card admin-stat-card admin-showcase-stat relative border-0! border-t-4! border-indigo-500! shadow-sm! block group no-underline">
            <span class="admin-stat-card-bar bg-indigo-500"></span>
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
           class="admin-card admin-stat-card admin-showcase-stat relative border-0! border-t-4! border-sky-500! shadow-sm! block group no-underline">
            <span class="admin-stat-card-bar bg-sky-500"></span>
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
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="admin-card p-5 shadow-sm! lg:col-span-2 relative overflow-hidden">
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

        <div class="admin-card p-5 shadow-sm! relative overflow-hidden">
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
        <div class="mb-6">
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

</div>