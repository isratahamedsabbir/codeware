{{-- A single root element wraps the whole file (Livewire requires exactly
     one) — the page heading below and the card after it used to be two
     top-level sibling divs, which meant only one of them was actually
     inside Livewire's tracked root and the other silently stopped updating
     after the first render. --}}
<div>

    <div class="mb-3 flex items-center justify-between gap-4 flex-wrap">
        <div>
            @include('partials.admin-breadcrumbs', ['routeName' => 'admin.comments'])
        </div>
        @if (count($selectedIds) > 0)
            <div class="flex items-center gap-2 shrink-0">
                <flux:button variant="danger" size="sm" icon="trash" wire:click="confirmBulkDelete">
                    Delete ({{ count($selectedIds) }})
                </flux:button>
            </div>
        @endif
    </div>

<div class="bg-white rounded-[5px] shadow-sm overflow-hidden">

    {{-- Filters --}}
    <div class="flex items-center gap-3 p-4 flex-wrap">
        <x-per-page-select :options="$this->perPageOptions()" />
        <select wire:model.live="statusFilter"
            class="px-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 bg-white appearance-none pr-8 min-w-[140px] transition-all"
            style="background-image:url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%23aaa' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E&quot;);background-repeat:no-repeat;background-position:right 10px center">
            <option value="">All statuses</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
        </select>
        <select wire:model.live="typeFilter"
            class="px-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 bg-white appearance-none pr-8 min-w-[140px] transition-all"
            style="background-image:url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%23aaa' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E&quot;);background-repeat:no-repeat;background-position:right 10px center">
            <option value="">All types</option>
            <option value="post">Post</option>
            <option value="product">Product</option>
            <option value="service">Service</option>
        </select>
        <div class="relative max-w-xs ml-auto">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-600" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by comment or author…"
                class="w-full pl-9 pr-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all" />
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <div class="border border-zinc-100 rounded-lg">
            <table class="w-full divide-y divide-gray-200" style="table-layout:fixed">
                <colgroup>
                    <col style="width:5%">
                    <col style="width:5%">
                    <col style="width:14%">
                    <col style="width:12%">
                    <col style="width:29%">
                    <col style="width:10%">
                    <col class="hidden lg:table-column" style="width:12%">
                    <col style="width:13%">
                </colgroup>
                <thead>
                    <tr class="bg-zinc-50">
                        <th class="px-2 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider w-8"></th>
                        <th class="px-2 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider w-8"></th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Author</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">On</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Comment</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Replies</th>
                        <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Date</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($comments as $comment)
                        @php
                            $commentableName = match (true) {
                                $comment->commentable instanceof \App\Models\Post => $comment->commentable->getTranslation('title', 'en', false),
                                $comment->commentable instanceof \App\Models\Product,
                                $comment->commentable instanceof \App\Models\Service => $comment->commentable->getTranslation('name', 'en', false),
                                default => '(deleted)',
                            };
                        @endphp
                        <tr class="group/row hover:bg-indigo-50/30 transition-colors cursor-default {{ in_array($comment->id, $selectedIds, true) ? 'bg-indigo-50 ring-1 ring-inset ring-indigo-300' : '' }}"
                            @click="if ($event.ctrlKey || $event.metaKey) { $event.preventDefault(); $wire.toggleSelect({{ $comment->id }}) }">

                            {{-- Expand toggle, small screens only, where columns are hidden. --}}
                            <td class="px-1 py-2 text-center">
                                <div class="flex items-center justify-center gap-1" @click.stop>
                                    <x-admin-row-expand-toggle class="lg:hidden" :expanded="$viewingId === $comment->id"
                                        wire:click="{{ $viewingId === $comment->id ? 'closeDetails' : 'viewDetails('.$comment->id.')' }}" />
                                </div>
                            </td>

                            {{-- Bulk-select checkbox — its own dedicated column so it never
                                 crowds into neighboring cells; stays hidden until a selection
                                 is already in progress. --}}
                            <td class="px-1 py-2 text-center">
                                <div @click.stop>
                                    @if (count($selectedIds) > 0)
                                        <input type="checkbox" wire:click="toggleSelect({{ $comment->id }})"
                                            @checked(in_array($comment->id, $selectedIds, true))
                                            class="size-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" />
                                    @endif
                                </div>
                            </td>

                            {{-- Author --}}
                            <td class="px-4 py-2">
                                <span class="text-sm font-medium text-zinc-900"><x-truncate :text="$comment->user?->name ?? '(deleted user)'" /></span>
                            </td>

                            {{-- On — which post/product/service this comment belongs to --}}
                            <td class="px-4 py-2">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10.5px] font-medium bg-zinc-100 text-zinc-600">
                                    {{ $this->typeLabel($comment->commentable_type) }}
                                </span>
                                <div class="text-xs text-zinc-500 mt-0.5"><x-truncate :text="$commentableName" /></div>
                            </td>

                            {{-- Comment body --}}
                            <td class="px-4 py-2">
                                <p class="text-sm text-zinc-600 truncate"><x-truncate :text="$comment->body" /></p>
                            </td>

                            {{-- Replies --}}
                            <td class="px-4 py-2 text-sm text-zinc-500">
                                {{ $comment->replies_count }}
                            </td>

                            {{-- Date --}}
                            <td class="hidden lg:table-cell px-4 py-2">
                                <span class="text-sm text-zinc-500 whitespace-nowrap">{{ $comment->created_at->toDisplay() }}</span>
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-2">
                                <select wire:change="updateStatus({{ $comment->id }}, $event.target.value)"
                                    class="text-sm rounded-lg border border-zinc-200 bg-white py-1.5 pl-2.5 pr-7 text-zinc-700 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all appearance-none"
                                    style="background-image:url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%23aaa' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E&quot;);background-repeat:no-repeat;background-position:right 8px center">
                                    <option value="pending" @selected($comment->status === 'pending')>Pending</option>
                                    <option value="approved" @selected($comment->status === 'approved')>Approved</option>
                                    <option value="rejected" @selected($comment->status === 'rejected')>Rejected</option>
                                </select>
                            </td>

                        </tr>
                        @if ($viewingId === $comment->id)
                            <x-admin-row-details colspan="8">
                                <x-admin-row-details.item label="Author">{{ $comment->user?->email ?? '—' }}</x-admin-row-details.item>
                                <x-admin-row-details.item label="Full comment">{{ $comment->body }}</x-admin-row-details.item>
                                <x-admin-row-details.item label="Date">{{ $comment->created_at->toDisplay() }}</x-admin-row-details.item>
                                @if ($comment->replies->isNotEmpty())
                                    <x-admin-row-details.item label="Replies">
                                        <div class="space-y-2 text-left">
                                            @foreach ($comment->replies as $reply)
                                                <div class="text-xs border-l-2 border-zinc-200 pl-2">
                                                    <span class="font-medium text-zinc-700">{{ $reply->user?->name ?? '(deleted user)' }}</span>
                                                    <span class="text-zinc-400">— {{ ucfirst($reply->status) }}</span>
                                                    <p class="text-zinc-600">{{ $reply->body }}</p>
                                                </div>
                                            @endforeach
                                        </div>
                                    </x-admin-row-details.item>
                                @endif
                            </x-admin-row-details>
                        @endif
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <flux:icon.chat-bubble-left-right class="w-10 h-10 text-zinc-200 mx-auto mb-3" />
                                <p class="text-sm text-zinc-600">No comments found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="px-6 py-3">
        {{ $comments->links() }}
    </div>

    {{-- Delete Modal --}}
    <flux:modal name="comment-delete" class="md:w-80"
        x-on:open-modal.window="if ($event.detail.name === 'comment-delete') $flux.modal('comment-delete').show()"
        x-on:close-modal.window="if ($event.detail.name === 'comment-delete') $flux.modal('comment-delete').close()">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                    </svg>
                </div>
                <flux:heading>Delete comment?</flux:heading>
            </div>
            <flux:text class="text-sm text-zinc-500">This action cannot be undone. Any replies to this comment will be deleted too.</flux:text>
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

    {{-- Bulk Delete Modal --}}
    <flux:modal name="comment-bulk-delete" class="md:w-80"
        x-on:open-modal.window="if ($event.detail.name === 'comment-bulk-delete') $flux.modal('comment-bulk-delete').show()"
        x-on:close-modal.window="if ($event.detail.name === 'comment-bulk-delete') $flux.modal('comment-bulk-delete').close()">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                    </svg>
                </div>
                <flux:heading>Delete {{ count($selectedIds) }} {{ \Illuminate\Support\Str::plural('comment', count($selectedIds)) }}?</flux:heading>
            </div>
            <flux:text class="text-sm text-zinc-500">This action cannot be undone. Any replies to these comments will be deleted too.</flux:text>
            <div class="flex gap-2 pt-1">
                <button wire:click="bulkDelete"
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

</div>
