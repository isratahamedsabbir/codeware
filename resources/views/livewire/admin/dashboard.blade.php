<div>
    {{-- Greeting & Overview Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl">
                Welcome back, <span class="text-transparent bg-clip-text bg-gradient-to-r from-brand-blue to-emerald-500 font-extrabold">{{ auth()->user()->name }}</span>
            </h1>
            <div class="flex flex-wrap items-center gap-3 mt-1.5 text-sm text-zinc-500 font-medium">
                <span class="inline-flex items-center gap-1.5">
                    <flux:icon.chart-bar class="size-4 text-brand-blue" />
                    System Overview
                </span>
                <span class="text-zinc-300 select-none">&bull;</span>
                <span class="inline-flex items-center gap-1.5">
                    <flux:icon.calendar class="size-4 text-emerald-500" />
                    {{ now()->format('l, d F Y') }}
                </span>
            </div>
        </div>
        
        {{-- Quick Stat/Status Badge --}}
        <div class="flex items-center gap-2 self-start sm:self-center">
            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-600/10">
                <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                System Operational
            </span>
        </div>
    </div>

    {{-- Stats Grid (Vibrant Premium Cards) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">

        {{-- Products Card --}}
        <div class="admin-card p-6 relative overflow-hidden group">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-brand-blue to-cyan-400"></div>
            <div class="flex items-start justify-between">
                <div class="space-y-1">
                    <p class="text-xs font-bold text-zinc-400 uppercase tracking-widest select-none">Total Products</p>
                    <p class="text-3xl font-extrabold text-zinc-900 tracking-tight transition-transform duration-300 group-hover:scale-105 origin-left">{{ $totalProducts }}</p>
                </div>
                <div class="size-12 rounded-xl bg-gradient-to-br from-blue-500/10 to-cyan-500/10 flex items-center justify-center shrink-0 border border-blue-500/20 shadow-sm shadow-blue-500/5 group-hover:from-blue-500/20 group-hover:to-cyan-500/20 transition-all duration-300">
                    <flux:icon.cube class="size-6 text-brand-blue" />
                </div>
            </div>
            <div class="flex items-center gap-1.5 mt-4 text-xs font-semibold text-zinc-500">
                <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-emerald-700 font-bold">{{ $totalCategories }}</span>
                <span>product categories cataloged</span>
            </div>
        </div>

        {{-- Blog Posts Card --}}
        <div class="admin-card p-6 relative overflow-hidden group">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-[#7cc242] to-emerald-400"></div>
            <div class="flex items-start justify-between">
                <div class="space-y-1">
                    <p class="text-xs font-bold text-zinc-400 uppercase tracking-widest select-none">Blog Posts</p>
                    <p class="text-3xl font-extrabold text-zinc-900 tracking-tight transition-transform duration-300 group-hover:scale-105 origin-left">{{ $totalPosts }}</p>
                </div>
                <div class="size-12 rounded-xl bg-gradient-to-br from-emerald-500/10 to-green-500/10 flex items-center justify-center shrink-0 border border-emerald-500/20 shadow-sm shadow-emerald-500/5 group-hover:from-emerald-500/20 group-hover:to-green-500/20 transition-all duration-300">
                    <flux:icon.document-text class="size-6 text-[#7cc242]" />
                </div>
            </div>
            <div class="flex items-center gap-3 mt-4 text-xs font-semibold">
                <span class="flex items-center gap-1 text-emerald-600">
                    <span class="size-1.5 rounded-full bg-emerald-500"></span>
                    {{ $publishedPosts }} Published
                </span>
                <span class="text-zinc-300 font-light">|</span>
                <span class="flex items-center gap-1 text-zinc-500">
                    <span class="size-1.5 rounded-full bg-zinc-400"></span>
                    {{ $draftPosts }} Drafts
                </span>
            </div>
        </div>

        {{-- Pages Card --}}
        <div class="admin-card p-6 relative overflow-hidden group">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-brand-blue to-purple-400"></div>
            <div class="flex items-start justify-between">
                <div class="space-y-1">
                    <p class="text-xs font-bold text-zinc-400 uppercase tracking-widest select-none">CMS Pages</p>
                    <p class="text-3xl font-extrabold text-zinc-900 tracking-tight transition-transform duration-300 group-hover:scale-105 origin-left">{{ $totalPages }}</p>
                </div>
                <div class="size-12 rounded-xl bg-gradient-to-br from-indigo-500/10 to-purple-500/10 flex items-center justify-center shrink-0 border border-indigo-500/20 shadow-sm shadow-indigo-500/5 group-hover:from-indigo-500/20 group-hover:to-purple-500/20 transition-all duration-300">
                    <flux:icon.document class="size-6 text-indigo-600" />
                </div>
            </div>
            <p class="text-xs text-zinc-400 mt-4 font-medium">Landing layouts &amp; live publishing revisions</p>
        </div>

        {{-- Media Card --}}
        <div class="admin-card p-6 relative overflow-hidden group">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-400 to-[#7cc242]"></div>
            <div class="flex items-start justify-between">
                <div class="space-y-1">
                    <p class="text-xs font-bold text-zinc-400 uppercase tracking-widest select-none">Media Files</p>
                    <p class="text-3xl font-extrabold text-zinc-900 tracking-tight transition-transform duration-300 group-hover:scale-105 origin-left">{{ $totalMedia }}</p>
                </div>
                <div class="size-12 rounded-xl bg-gradient-to-br from-sky-500/10 to-blue-500/10 flex items-center justify-center shrink-0 border border-sky-500/20 shadow-sm shadow-sky-500/5 group-hover:from-sky-500/20 group-hover:to-blue-500/20 transition-all duration-300">
                    <flux:icon.photo class="size-6 text-sky-600" />
                </div>
            </div>
            <div class="flex items-center gap-1.5 mt-4 text-xs font-semibold text-zinc-500">
                <span>Storage space consumed:</span>
                <span class="inline-flex items-center rounded-md bg-sky-50 px-2 py-0.5 text-sky-700 font-bold">{{ $totalMediaSize }}</span>
            </div>
        </div>

    </div>

    {{-- Quick Actions Section (Premium Overhaul) --}}
    <div class="mb-10">
        <h2 class="text-xs font-bold text-zinc-400 uppercase tracking-widest mb-4">Workspace Quick Actions</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            
            {{-- Catalog action --}}
            <a href="{{ route('admin.products') }}" wire:navigate.hover
               class="admin-card p-5 flex flex-col sm:flex-row items-center gap-4 hover:border-blue-200 transition-all group no-underline text-center sm:text-left cursor-pointer">
                <div class="size-10 rounded-xl bg-blue-50 flex items-center justify-center shrink-0 group-hover:bg-blue-100 group-hover:scale-105 transition-all duration-200 shadow-xs border border-blue-100">
                    <flux:icon.cube class="size-5 text-[#1e7bc4]" />
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-zinc-800 truncate group-hover:text-[#1e7bc4] transition-colors">Products Catalog</p>
                    <p class="text-xs text-zinc-400 truncate mt-0.5">Manage products</p>
                </div>
            </a>

            {{-- New Post action --}}
            <a href="{{ route('admin.posts') }}" wire:navigate.hover
               class="admin-card p-5 flex flex-col sm:flex-row items-center gap-4 hover:border-emerald-200 transition-all group no-underline text-center sm:text-left cursor-pointer">
                <div class="size-10 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0 group-hover:bg-emerald-100 group-hover:scale-105 transition-all duration-200 shadow-xs border border-emerald-100">
                    <flux:icon.document-text class="size-5 text-[#7cc242]" />
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-zinc-800 truncate group-hover:text-[#7cc242] transition-colors">Publish Post</p>
                    <p class="text-xs text-zinc-400 truncate mt-0.5">Write news article</p>
                </div>
            </a>

            {{-- System Settings Action --}}
            <a href="{{ route('admin.settings') }}" wire:navigate.hover
               class="admin-card p-5 flex flex-col sm:flex-row items-center gap-4 hover:border-zinc-300 transition-all group no-underline text-center sm:text-left cursor-pointer">
                <div class="size-10 rounded-xl bg-zinc-50 flex items-center justify-center shrink-0 group-hover:bg-zinc-100 group-hover:scale-105 transition-all duration-200 shadow-xs border border-zinc-200/60">
                    <flux:icon.cog-6-tooth class="size-5 text-zinc-600" />
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-zinc-800 truncate group-hover:text-zinc-900 transition-colors">System Settings</p>
                    <p class="text-xs text-zinc-400 truncate mt-0.5">Configure platform</p>
                </div>
            </a>
            
        </div>
    </div>

    {{-- Activity & Feed Lists (Premium Grid Layout) --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

        {{-- Recent Products Feed --}}
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-xs font-bold text-zinc-400 uppercase tracking-widest">Recent Products Feed</h2>
                <a href="{{ route('admin.products') }}" wire:navigate.hover
                   class="text-xs font-bold text-brand-blue hover:text-blue-700 transition-colors flex items-center gap-1">
                    View Catalogue
                    <flux:icon.chevron-right class="size-3" />
                </a>
            </div>
            
            <div class="admin-card divide-y divide-zinc-100 bg-white shadow-lg shadow-zinc-100/50">
                @forelse($recentProducts as $product)
                    <div class="flex items-center justify-between px-5 py-4 gap-4 hover:bg-slate-50/50 transition-colors duration-200">
                        <div class="min-w-0 flex items-center gap-3">
                            <div class="size-8.5 rounded-lg bg-blue-50 flex items-center justify-center shrink-0 border border-blue-100/50">
                                <flux:icon.cube class="size-4.5 text-brand-blue" />
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
                <h2 class="text-xs font-bold text-zinc-400 uppercase tracking-widest">Recent Blog Posts</h2>
                <a href="{{ route('admin.posts') }}" wire:navigate.hover
                   class="text-xs font-bold text-brand-blue hover:text-blue-700 transition-colors flex items-center gap-1">
                    View All Posts
                    <flux:icon.chevron-right class="size-3" />
                </a>
            </div>
            
            <div class="admin-card divide-y divide-zinc-100 bg-white shadow-lg shadow-zinc-100/50">
                @forelse($recentPosts as $post)
                    <div class="flex items-center justify-between px-5 py-4 gap-4 hover:bg-slate-50/50 transition-colors duration-200">
                        <div class="min-w-0 flex items-center gap-3">
                            <div class="size-8.5 rounded-lg bg-emerald-50 flex items-center justify-center shrink-0 border border-emerald-100/50">
                                <flux:icon.document-text class="size-4.5 text-[#7cc242]" />
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
