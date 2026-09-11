@push('page-header-actions')
    <flux:button variant="ghost" size="sm" icon="plus" href="{{ route('admin.posts.create') }}" wire:navigate>
        New post
    </flux:button>
@endpush

<div class="bg-white rounded-[5px] shadow-sm overflow-hidden">

    {{-- Header --}}
    <div class="flex items-center gap-3 p-4 flex-wrap">
        <x-per-page-select :options="$this->perPageOptions()" />

        {{-- Status filter --}}
        <select wire:model.live="statusFilter"
            class="px-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 bg-white appearance-none pr-8 min-w-[140px] transition-all"
            style="background-image:url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%23aaa' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E&quot;);background-repeat:no-repeat;background-position:right 10px center">
            <option value="">All statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>

        {{-- Search --}}
        <div class="relative max-w-xs w-full ml-auto">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-600" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search posts…"
                class="w-full pl-9 pr-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all" />
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <div class="border border-zinc-100 rounded-lg">
            <table class="w-full divide-y divide-gray-200">
                <colgroup>
                    <col style="width:5%">
                    <col class="hidden lg:table-column" style="width:5%">
                    <col style="width:26%">
                    <col class="hidden lg:table-column" style="width:22%">
                    <col class="hidden lg:table-column" style="width:16%">
                    <col style="width:11%">
                    <col style="width:15%">
                </colgroup>
                <thead>
                    <tr class="bg-zinc-50">
                        <th class="px-2 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider w-8"></th>
                        <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">#</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Title</th>
                        <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Slug</th>
                        <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Category</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Status</th>
                        <th class="sticky right-0 z-10 bg-zinc-50 border-l border-zinc-100 px-4 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($posts as $post)
                        <tr class="group/row hover:bg-indigo-50/30 transition-colors" @contextmenu.prevent="$el.querySelector('[data-actions-trigger]')?.click()">

                            {{-- Expand toggle (small screens only, where columns are hidden) --}}
                            <td class="px-2 py-2 text-center">
                                <x-admin-row-expand-toggle class="lg:hidden" :expanded="$viewingId === $post->id"
                                    wire:click="{{ $viewingId === $post->id ? 'closeDetails' : 'viewDetails('.$post->id.')' }}" />
                            </td>

                            {{-- ID --}}
                            <td class="hidden lg:table-cell px-4 py-2">
                                <span class="text-sm text-zinc-500 font-mono">{{ $post->id }}</span>
                            </td>

                            {{-- Title --}}
                            <td class="px-4 py-2">
                                <div class="font-medium text-zinc-900 text-sm leading-snug">
                                    <x-truncate :text="$post->getTranslation('title', 'en', false)" />
                                </div>
                                @if ($post->getTranslation('title', 'bn', false))
                                    <div class="text-xs text-zinc-600 mt-0.5">
                                        <x-truncate :text="$post->getTranslation('title', 'bn', false)" />
                                    </div>
                                @endif
                            </td>

                            {{-- Slug --}}
                            <td class="hidden lg:table-cell px-4 py-2">
                                <x-copy-text :text="$post->slug" class="font-mono text-xs text-zinc-600 block">
                                    <x-truncate :text="$post->slug" />
                                </x-copy-text>
                            </td>

                            {{-- Category --}}
                            <td class="hidden lg:table-cell px-4 py-2">
                                @if ($post->category)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-zinc-100 text-zinc-500 border border-zinc-200 w-max">
                                        <svg class="w-3 h-3 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" />
                                        </svg>
                                        <x-truncate :text="$post->category->getTranslation('name', 'en', false)" />
                                    </span>
                                @else
                                    <span class="text-zinc-300 text-sm">—</span>
                                @endif
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-2">
                                @if ($post->status === 'active')
                                    <button type="button" wire:click="toggleStatus({{ $post->id }})"
                                        aria-label="Deactivate"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-600 border border-green-200 cursor-pointer hover:bg-green-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                        Active
                                    </button>
                                @else
                                    <button type="button" wire:click="toggleStatus({{ $post->id }})"
                                        aria-label="Activate"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-600 border border-red-200 cursor-pointer hover:bg-red-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                        Inactive
                                    </button>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="sticky right-0 z-10 bg-white group-hover/row:bg-indigo-50/30 border-l border-zinc-100 px-4 py-2">
                                <x-admin-row-actions :actions="[
                                    ['href' => route('admin.posts.edit', $post->id), 'icon' => 'pencil', 'label' => 'Edit', 'color' => 'primary'],
                                    $post->page
                                        ? ['href' => route('admin.pages.edit', $post->page->id), 'icon' => 'document', 'label' => 'Page', 'color' => 'secondary']
                                        : ['icon' => 'document', 'label' => 'Page', 'color' => 'secondary', 'disabled' => true],
                                    ['wireClick' => 'openPuckEditor(' . $post->id . ')', 'icon' => 'squares', 'label' => 'Content', 'color' => 'cyan-500'],
                                    ['wireClick' => 'confirmDelete(' . $post->id . ')', 'icon' => 'trash', 'label' => 'Delete', 'color' => 'rose-500'],
                                ]" />
                            </td>

                        </tr>
                        @if ($viewingId === $post->id)
                            <x-admin-row-details colspan="7">
                                <x-admin-row-details.item label="Slug">
                                    @if ($post->slug)
                                        <x-copy-text :text="$post->slug" class="font-mono">{{ $post->slug }}</x-copy-text>
                                    @else
                                        —
                                    @endif
                                </x-admin-row-details.item>
                                <x-admin-row-details.item label="Category">{{ $post->category?->getTranslation('name', 'en', false) ?: '—' }}</x-admin-row-details.item>
                            </x-admin-row-details>
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <svg class="w-10 h-10 text-zinc-200 mx-auto mb-3" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.5">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                    <polyline points="14 2 14 8 20 8" />
                                    <line x1="9" y1="15" x2="15" y2="15" />
                                </svg>
                                <p class="text-sm text-zinc-600">No posts found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="px-6 py-3">
        {{ $posts->links() }}
    </div>

    {{-- Delete Modal --}}
    <flux:modal name="post-delete" class="md:w-80"
        x-on:open-modal.window="if ($event.detail.name === 'post-delete') $flux.modal('post-delete').show()"
        x-on:close-modal.window="if ($event.detail.name === 'post-delete') $flux.modal('post-delete').close()">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                    </svg>
                </div>
                <flux:heading>Delete post?</flux:heading>
            </div>
            <flux:text class="text-sm text-zinc-500">This action cannot be undone. The post will be soft-deleted.
            </flux:text>
            <div class="flex gap-2 pt-1">
                <button wire:click="delete"
                    class="inline-flex items-center gap-2 px-4 h-8 text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 transition-colors border-none cursor-pointer">
                    Delete
                </button>
                <flux:modal.close>
                    <flux:button size="sm" variant="ghost">Cancel</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

</div>
