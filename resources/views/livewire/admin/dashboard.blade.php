<div class="max-w-[1600px]">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4 flex-wrap mb-6">
        <div class="min-w-0">
            <h1 class="text-lg font-bold tracking-tight text-zinc-900">Welcome back, {{ auth()->user()->name }}</h1>
            <div class="mt-0.5">@include('partials.admin-breadcrumbs')</div>
        </div>
        <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-zinc-100 text-sm text-zinc-500 font-medium">
            <flux:icon.calendar class="size-4 text-zinc-400" />
            {{ now()->toDisplay('l, d F Y') }}
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

        <div class="admin-card group p-5 shadow-sm! transition-all duration-300 hover:-translate-y-1 hover:shadow-lg!">
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Products</p>
                    <p class="mt-1 text-2xl font-extrabold tracking-tight text-zinc-900 tabular-nums">{{ $totalProducts }}</p>
                </div>
                <div class="size-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 transition-transform duration-300 group-hover:scale-110 group-hover:rotate-3">
                    <flux:icon.cube class="size-5" />
                </div>
            </div>
        </div>

        <div class="admin-card group p-5 shadow-sm! transition-all duration-300 hover:-translate-y-1 hover:shadow-lg!">
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Posts</p>
                    <p class="mt-1 text-2xl font-extrabold tracking-tight text-zinc-900 tabular-nums">{{ $totalPosts }}</p>
                </div>
                <div class="size-10 rounded-xl bg-green-50 text-green-600 flex items-center justify-center shrink-0 transition-transform duration-300 group-hover:scale-110 group-hover:rotate-3">
                    <flux:icon.document-text class="size-5" />
                </div>
            </div>
        </div>

        <div class="admin-card group p-5 shadow-sm! transition-all duration-300 hover:-translate-y-1 hover:shadow-lg!">
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Pages</p>
                    <p class="mt-1 text-2xl font-extrabold tracking-tight text-zinc-900 tabular-nums">{{ $totalPages }}</p>
                </div>
                <div class="size-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 transition-transform duration-300 group-hover:scale-110 group-hover:rotate-3">
                    <flux:icon.document class="size-5" />
                </div>
            </div>
        </div>

        <div class="admin-card group p-5 shadow-sm! transition-all duration-300 hover:-translate-y-1 hover:shadow-lg!">
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Media Files</p>
                    <p class="mt-1 text-2xl font-extrabold tracking-tight text-zinc-900 tabular-nums">{{ $totalMedia }}</p>
                </div>
                <div class="size-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0 transition-transform duration-300 group-hover:scale-110 group-hover:rotate-3">
                    <flux:icon.photo class="size-5" />
                </div>
            </div>
        </div>

    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="admin-card p-5 shadow-sm! lg:col-span-2">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-sm font-bold text-zinc-800">Workspace overview</h2>
                    <p class="text-xs text-zinc-400 mt-0.5">Content distribution across your workspace</p>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-500 tabular-nums">
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

        <div class="admin-card p-5 shadow-sm!">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-sm font-bold text-zinc-800">Posts by status</h2>
                    <p class="text-xs text-zinc-400 mt-0.5">Publishing breakdown</p>
                </div>
                <div class="size-8 rounded-lg bg-zinc-50 flex items-center justify-center border border-zinc-100">
                    <flux:icon.chart-pie class="size-4 text-zinc-400" />
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
                    <flux:icon.exclamation-triangle class="size-3.5 text-amber-500" />
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