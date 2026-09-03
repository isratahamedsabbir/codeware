<div>
    {{-- Greeting & Overview Header --}}
    <div class="admin-dashboard-hero flex flex-col sm:flex-row sm:items-center rounded-lg! justify-between gap-5 mb-5 px-4 py-2 sm:px-4"> 
        <div>
            <!-- <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-600 px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-white shadow-sm">
                <flux:icon.sparkles class="size-3.5" />
                Control center
            </span> -->  
            <h1 class="text-xl font-extrabold tracking-tight text-zinc-900 sm:text-xl mb-0.5">
                Welcome back, {{ auth()->user()->name }}
            </h1>
            @include('partials.admin-breadcrumbs')
        </div>   

        <div class="flex flex-wrap items-center gap-3 text-sm text-zinc-500 font-medium"> 
            <span class="inline-flex items-center gap-1.5">
                <flux:icon.chart-bar class="size-4 text-primary" />
                System Overview
            </span>
            <span class="text-zinc-300 select-none">&bull;</span>
            <span class="inline-flex items-center gap-1.5">
                <flux:icon.calendar class="size-4 text-zinc-400" />
                {{ now()->toDisplay('l, d F Y') }}
            </span>
        </div>
        {{-- Quick Stat/Status Badge --}}
        <!-- <div class="flex items-center gap-2 self-start sm:self-center">
            <span class="inline-flex items-center gap-2 rounded-xl bg-white/90 px-4 py-2.5 text-xs font-bold text-emerald-700 shadow-sm ring-1 ring-emerald-600/10">
                <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                System Operational
            </span>
        </div> -->
    </div>

    {{-- Stats Grid (Clean, flat SaaS cards) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-5">

        {{-- Products Card --}}
        <div class="admin-card admin-stat-card admin-showcase-stat relative border-0! border-t-4! border-primary! shadow-sm!">
            <div class="flex items-start justify-between"> 
                <div>
                    <p class="text-sm font-semibold text-zinc-500">Total Products</p>
                    <p class="admin-stat-value mt-2 font-extrabold text-zinc-900 leading-6 ">{{ $totalProducts }}</p>
                </div>
                <div class="admin-stat-icon bg-blue-100">
                    <flux:icon.cube class="size-6 text-blue-600" />
                </div>
            </div>
            <!-- <p class="mt-2 text-xs font-medium text-zinc-400">{{ $totalCategories }} categories · {{ $productsThisMonth }} added this month</p> -->
        </div>

        {{-- Blog Posts Card --}}
        <div class="admin-card admin-stat-card admin-showcase-stat relative border-0! border-t-4! border-secondary! shadow-sm!">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-semibold text-zinc-500">Blog Posts</p>
                    <p class="admin-stat-value mt-2 font-extrabold text-zinc-900 leading-6">{{ $totalPosts }}</p>
                </div>
                <div class="admin-stat-icon bg-emerald-100">
                    <flux:icon.document-text class="size-6 text-emerald-600" />
                </div>
            </div>
            <!-- <div class="flex items-center gap-3 mt-2 text-xs font-medium">
                <span class="flex items-center gap-1 text-emerald-600">
                    <span class="size-1.5 rounded-full bg-emerald-500"></span>
                    {{ $publishedPosts }} Published
                </span>
                <span class="flex items-center gap-1 text-zinc-400">
                    <span class="size-1.5 rounded-full bg-zinc-300"></span>
                    {{ $draftPosts }} Drafts
                </span>
            </div> -->
        </div>

        {{-- Pages Card --}}
        <div class="admin-card admin-stat-card admin-showcase-stat relative border-0! border-t-4! border-indigo-500! shadow-sm!">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-semibold text-zinc-500">CMS Pages</p>
                    <p class="admin-stat-value mt-2 font-extrabold text-zinc-900 leading-6">{{ $totalPages }}</p>
                </div>
                <div class="admin-stat-icon bg-indigo-100">
                    <flux:icon.document class="size-6 text-indigo-600" />
                </div>
            </div>
            <!-- <p class="mt-2 text-xs font-medium text-zinc-400">{{ $pagesThisMonth }} updated this month</p> -->
        </div>

        {{-- Media Card --}}
        <div class="admin-card admin-stat-card admin-showcase-stat relative border-0! border-t-4! border-sky-500! shadow-sm!">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-semibold text-zinc-500">Media Files</p>
                    <p class="admin-stat-value mt-2 font-extrabold text-zinc-900 leading-6">{{ $totalMedia }}</p>
                </div>
                <div class="admin-stat-icon bg-sky-100">
                    <flux:icon.photo class="size-6 text-sky-600" />
                </div>
            </div>
            <!-- <p class="mt-2 text-xs font-medium text-zinc-400">{{ $totalMediaSize }} storage consumed · {{ $mediaThisMonth }} new</p> -->
        </div>

    </div>

    {{-- Charts Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">

        {{-- Bar Chart: Content by Type --}}
        <div class="admin-card admin-chart-card p-5 sm:p-5 shadow-sm! lg:col-span-2">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-bold text-zinc-800">Workspace overview</h2>
                    <p class="text-sm text-zinc-400 mt-0.5">Content distribution across your workspace</p>
                </div>
                <span class="inline-flex items-center rounded-full bg-primary px-3.5 py-1.5 text-xs font-bold text-white shadow-sm shadow-blue-900/10">
                    {{ number_format($totalProducts + $totalPosts + $totalPages + $totalMedia) }} items
                </span>
            </div>
            <div class="h-72">
                <canvas id="admin-content-bar-chart"
                    data-labels="{{ json_encode(['Products', 'Posts', 'Pages', 'Media']) }}"
                    data-values="{{ json_encode([$totalProducts, $totalPosts, $totalPages, $totalMedia]) }}"></canvas>
            </div>
        </div>

        {{-- Donut Chart: Posts by Status --}}
        <div class="admin-card admin-chart-card p-5 sm:p-6 shadow-sm!">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-bold text-zinc-800">Posts by status</h2>
                    <p class="text-sm text-zinc-400 mt-0.5">Publishing breakdown</p>
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

    {{-- Quick Actions Section --}}
    <div class="mb-6"> 
        <h2 class="text-xs font-bold text-zinc-800 uppercase tracking-widest mb-4">Workspace Quick Actions</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">

            {{-- Catalog action --}}
            <a href="{{ route('admin.products') }}" wire:navigate.hover
               class="admin-card p-4 flex flex-col sm:flex-row items-center gap-4 hover:border-blue-200 transition-colors group no-underline text-center sm:text-left cursor-pointer shadow-sm!">
                <div class="size-10 rounded-lg bg-blue-50 flex items-center justify-center shrink-0 border border-blue-100">
                    <flux:icon.cube class="size-5 text-primary" />
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-zinc-800 truncate group-hover:text-primary transition-colors">Products Catalog</p>
                    <p class="text-xs text-zinc-400 truncate mt-0.5">Manage products</p>
                </div>
            </a>

            {{-- New Post action --}}
            <a href="{{ route('admin.posts') }}" wire:navigate.hover
               class="admin-card p-4 flex flex-col sm:flex-row items-center gap-4 hover:border-green-200 transition-colors group no-underline text-center sm:text-left cursor-pointer shadow-sm!">
                <div class="size-10 rounded-lg bg-green-50 flex items-center justify-center shrink-0 border border-green-100">
                    <flux:icon.document-text class="size-5 text-secondary" />
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-zinc-800 truncate group-hover:text-secondary transition-colors">Publish Post</p>
                    <p class="text-xs text-zinc-400 truncate mt-0.5">Write news article</p>
                </div>
            </a>

            {{-- System Settings Action --}}
            <a href="{{ route('admin.settings') }}" wire:navigate.hover
               class="admin-card p-4 flex flex-col sm:flex-row items-center gap-4 hover:border-zinc-300 transition-colors group no-underline text-center sm:text-left cursor-pointer shadow-sm!">
                <div class="size-10 rounded-lg bg-zinc-50 flex items-center justify-center shrink-0 border border-zinc-200">
                    <flux:icon.cog-6-tooth class="size-5 text-zinc-600" />
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-zinc-800 truncate group-hover:text-zinc-900 transition-colors">System Settings</p>
                    <p class="text-xs text-zinc-400 truncate mt-0.5">Configure platform</p>
                </div>
            </a>

        </div>
    </div>

    {{-- Activity & Feed Lists --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

        {{-- Recent Products Feed --}}
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-xs font-bold text-zinc-800 uppercase tracking-widest">Recent Products Feed</h2>
                <a href="{{ route('admin.products') }}" wire:navigate.hover
                   class="text-xs font-bold text-primary hover:text-blue-700 transition-colors flex items-center gap-1">
                    View Catalogue
                    <flux:icon.chevron-right class="size-3" />
                </a>
            </div>

            <div class="admin-card divide-y divide-zinc-100 shadow-sm! border-0!">
                @forelse($recentProducts as $product)
                    <div class="flex items-center justify-between px-5 py-4 gap-4 hover:bg-slate-50/50 transition-colors duration-200">
                        <div class="min-w-0 flex items-center gap-3">
                            <div class="size-8.5 rounded-lg bg-blue-50 flex items-center justify-center shrink-0 border border-blue-100/50">
                                <flux:icon.cube class="size-4.5 text-primary" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-zinc-800 truncate">
                                    {{ $product->getTranslation('name', 'en', false) }}
                                </p>
                                @if($product->category)
                                    <p class="text-xs text-zinc-400 truncate mt-0.5">{{ $product->category->getTranslation('name', 'en', false) }}</p>
                                @endif
                            </div>
                        </div>
                        <span class="shrink-0 inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold
                            {{ $product->status === 'published' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/10' : 'bg-slate-50 text-zinc-500 ring-1 ring-zinc-600/10' }}">
                            {{ ucfirst($product->status) }}
                        </span>
                    </div>
                @empty
                    <div class="px-5 py-10 text-sm text-zinc-400 text-center flex flex-col items-center justify-center gap-2">
                        <flux:icon.cube class="size-8 text-zinc-200" />
                        <p class="font-medium">No products cataloged yet.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Recent Posts Feed --}}
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-xs font-bold text-zinc-800 uppercase tracking-widest">Recent Blog Posts</h2>
                <a href="{{ route('admin.posts') }}" wire:navigate.hover
                   class="text-xs font-bold text-primary hover:text-blue-700 transition-colors flex items-center gap-1">
                    View All Posts
                    <flux:icon.chevron-right class="size-3" />
                </a>
            </div>

            <div class="admin-card divide-y divide-zinc-100 shadow-sm! border-0!">
                @forelse($recentPosts as $post)
                    <div class="flex items-center justify-between px-5 py-4 gap-4 hover:bg-slate-50/50 transition-colors duration-200">
                        <div class="min-w-0 flex items-center gap-3">
                            <div class="size-8.5 rounded-lg bg-green-50 flex items-center justify-center shrink-0 border border-green-100/50">
                                <flux:icon.document-text class="size-4.5 text-secondary" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-zinc-800 truncate">
                                    {{ $post->getTranslation('title', 'en', false) }}
                                </p>
                                @if($post->category)
                                    <p class="text-xs text-zinc-400 truncate mt-0.5">{{ $post->category->getTranslation('name', 'en', false) }}</p>
                                @endif
                            </div>
                        </div>
                        <span class="shrink-0 inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold
                            {{ $post->status === 'published' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/10' : 'bg-slate-50 text-zinc-500 ring-1 ring-zinc-600/10' }}">
                            {{ ucfirst($post->status) }}
                        </span>
                    </div>
                @empty
                    <div class="px-5 py-10 text-sm text-zinc-400 text-center flex flex-col items-center justify-center gap-2">
                        <flux:icon.document-text class="size-8 text-zinc-200" />
                        <p class="font-medium">No published news or posts yet.</p>
                    </div>
                @endforelse
            </div>
        </div>

    </div>
</div>
