<div class="bg-white rounded-[5px] shadow-sm overflow-hidden">

    {{-- Filters --}}
    <div class="flex gap-3 px-6 py-4 flex-wrap items-center">
        <x-per-page-select :options="$this->perPageOptions()" />

        <select wire:model.live="actionFilter"
            class="px-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 bg-white appearance-none pr-8 min-w-[130px] transition-all"
            style="background-image:url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%23aaa' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E&quot;);background-repeat:no-repeat;background-position:right 10px center">
            <option value="">All actions</option>
            @foreach ($actions as $action)
                <option value="{{ $action }}">{{ ucfirst($action) }}</option>
            @endforeach
        </select>

        <select wire:model.live="userFilter"
            class="px-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 bg-white appearance-none pr-8 min-w-[150px] transition-all"
            style="background-image:url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%23aaa' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E&quot;);background-repeat:no-repeat;background-position:right 10px center">
            <option value="">All admins</option>
            @foreach ($admins as $admin)
                <option value="{{ $admin->id }}">{{ $admin->name }}</option>
            @endforeach
        </select>

        @if ($search || $actionFilter || $userFilter)
            <button wire:click="clearFilters"
                class="inline-flex items-center gap-1.5 px-3 h-8 text-sm font-medium rounded-lg text-zinc-500 hover:text-zinc-800 hover:bg-zinc-100 transition-colors cursor-pointer">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18" />
                    <path d="M6 6l12 12" />
                </svg>
                Clear
            </button>
        @endif

        <div class="relative flex-1 min-w-[200px]">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-600" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search description, URL or IP…"
                class="w-full pl-9 pr-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all" />
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <div class="border border-zinc-100 rounded-lg">
            <table class="w-full divide-y divide-gray-200">
                <thead>
                    <tr class="bg-zinc-50">
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Time</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Admin</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Action</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Details</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">URL</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">IP Address</th>
                        <th class="px-4 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($logs as $log)
                        <tr class="hover:bg-indigo-50/30 transition-colors">
                            <td class="px-4 py-3 text-sm text-zinc-500 whitespace-nowrap">
                                {{ $log->created_at->toDisplay() }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="size-7 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs font-bold shrink-0">
                                        {{ strtoupper(substr($log->user?->name ?? '?', 0, 1)) }}
                                    </span>
                                    <span class="text-sm font-medium text-zinc-800"><x-truncate :text="$log->user?->name ?? 'Unknown'" /></span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                                    {{ match ($log->action) {
                                        'login' => 'bg-emerald-50 text-emerald-700',
                                        'logout' => 'bg-zinc-100 text-zinc-600',
                                        'visit' => 'bg-blue-50 text-blue-700',
                                        'created' => 'bg-green-50 text-green-700',
                                        'updated' => 'bg-amber-50 text-amber-700',
                                        'deleted' => 'bg-rose-50 text-rose-700',
                                        default => 'bg-zinc-100 text-zinc-600',
                                    } }}">
                                    {{ ucfirst($log->action) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-sm text-zinc-700"><x-truncate :text="$log->description ?? 'Page viewed'" /></span>
                            </td>
                            <td class="px-4 py-3">
                                @if ($log->url)
                                    <span class="font-mono text-xs text-zinc-500 truncate block max-w-[240px]"><x-truncate :text="$log->url" /></span>
                                @else
                                    <span class="text-zinc-300 text-sm">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-mono text-xs text-zinc-600">{{ $log->ip_address ?? '—' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <x-admin-row-actions :actions="[
                                    ['wireClick' => 'viewLog(' . $log->id . ')', 'icon' => 'eye', 'label' => 'View details', 'color' => 'primary'],
                                ]" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <svg class="w-10 h-10 text-zinc-200 mx-auto mb-3" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.5">
                                    <circle cx="12" cy="12" r="10" />
                                    <polyline points="12 6 12 12 16 14" />
                                </svg>
                                <p class="text-sm text-zinc-600">No activity found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="px-6 py-3">
        {{ $logs->links() }}
    </div>

    {{-- View Details Modal --}}
    <flux:modal name="view-log-details" class="md:w-[600px]"
        x-on:open-modal.window="if ($event.detail.name === 'view-log-details') $flux.modal('view-log-details').show()">
        @if ($viewingLogId)
            @php $log = \App\Models\AdminActivityLog::with('user')->find($viewingLogId); @endphp
            @if ($log)
                <div class="space-y-4">
                    <div class="flex items-center justify-between border-b border-zinc-100 pb-3">
                        <flux:heading>{{ ucfirst($log->action) }}</flux:heading>
                        <flux:modal.close>
                            <button wire:click="closeLogDetails" class="text-zinc-400 hover:text-zinc-600 transition-colors">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" />
                                </svg>
                            </button>
                        </flux:modal.close>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div><span class="text-zinc-400">Admin:</span> <span class="text-zinc-900 font-medium">{{ $log->user?->name ?? 'Unknown' }}</span></div>
                        <div><span class="text-zinc-400">Date:</span> <span class="text-zinc-900">{{ $log->created_at->toDisplay() }}</span></div>
                        <div><span class="text-zinc-400">Method:</span> <span class="text-zinc-900 font-mono">{{ $log->method ?? '—' }}</span></div>
                        <div><span class="text-zinc-400">IP Address:</span> <span class="text-zinc-900 font-mono">{{ $log->ip_address ?? '—' }}</span></div>
                    </div>
                    <div class="border-t border-zinc-100 pt-3 space-y-3">
                        <div>
                            <span class="text-zinc-400 text-sm">Description:</span>
                            <p class="text-sm text-zinc-700 leading-relaxed whitespace-pre-wrap mt-1">{{ $log->description ?? 'Page viewed' }}</p>
                        </div>
                        @if ($log->url)
                            <div>
                                <span class="text-zinc-400 text-sm">URL:</span>
                                <p class="text-xs font-mono text-zinc-600 break-all mt-1">{{ $log->url }}</p>
                            </div>
                        @endif
                        @if ($log->user_agent)
                            <div>
                                <span class="text-zinc-400 text-sm">User Agent:</span>
                                <p class="text-xs font-mono text-zinc-600 break-all mt-1">{{ $log->user_agent }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        @endif
    </flux:modal>

</div>
