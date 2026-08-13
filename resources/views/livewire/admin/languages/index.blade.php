<div class="bg-white rounded-lg border border-zinc-100 shadow-sm overflow-hidden">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-3 px-6 py-5 border-b border-zinc-100 flex-wrap">
        <p class="text-sm text-zinc-500">
            {{ __('Languages available in the admin panel. The default language is used when no other is selected.') }}
        </p>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.translations') }}" wire:navigate
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg border border-zinc-200 text-zinc-600 bg-white hover:bg-zinc-50 transition-colors">
                <flux:icon.language class="size-4" />
                {{ __('Translations') }}
            </a>
            <a href="{{ route('admin.languages.create') }}" wire:navigate
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg text-white transition-colors"
                style="background:#22c55e" onmouseover="this.style.background='#16a34a'"
                onmouseout="this.style.background='#22c55e'">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="12" y1="5" x2="12" y2="19" />
                    <line x1="5" y1="12" x2="19" y2="12" />
                </svg>
                {{ __('New language') }}
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex gap-3 px-6 py-3 border-b border-zinc-100 flex-wrap">
        <div class="relative flex-1 min-w-[180px]">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-600" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="{{ __('Search languages…') }}"
                class="w-full pl-9 pr-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all" />
        </div>
        <select wire:model.live="statusFilter"
            class="px-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 bg-white appearance-none pr-8 min-w-[140px] transition-all"
            style="background-image:url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%23aaa' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E&quot;);background-repeat:no-repeat;background-position:right 10px center">
            <option value="">{{ __('All statuses') }}</option>
            <option value="active">{{ __('Active') }}</option>
            <option value="inactive">{{ __('Inactive') }}</option>
        </select>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto px-6 py-4">
        <div class="border border-zinc-100 rounded-lg">
            <table class="w-full border-collapse" style="table-layout:fixed">
                <colgroup>
                    <col style="width:5%">
                    <col style="width:28%">
                    <col style="width:10%">
                    <col style="width:22%">
                    <col style="width:12%">
                    <col style="width:23%">
                </colgroup>
                <thead>
                    <tr class="bg-zinc-50 border-b border-zinc-100">
                        <th class="px-2 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider w-8">#</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">{{ __('Language') }}</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">{{ __('Code') }}</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">{{ __('Translated') }}</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">{{ __('Status') }}</th>
                        <th class="px-4 py-2.5 text-right text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($languages as $language)
                        @php
                            $done    = (int) ($translated[$language->code] ?? 0);
                            $percent = $totalKeys > 0 ? (int) round(min($done, $totalKeys) / $totalKeys * 100) : 0;
                        @endphp
                        <tr class="border-b border-zinc-50 hover:bg-indigo-50/30 transition-colors">

                            {{-- Id --}}
                            <td class="px-2 py-3.5 text-center text-xs text-zinc-500">
                                {{ $language->id }}
                            </td>

                            {{-- Name --}}
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-2">
                                    @if ($language->flag)
                                        <span class="text-base leading-none">{{ $language->flag }}</span>
                                    @endif
                                    <div class="min-w-0">
                                        <div class="font-medium text-zinc-900 text-sm leading-snug truncate">
                                            {{ $language->name }}
                                            @if ($language->is_default)
                                                <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-indigo-50 text-indigo-600 align-middle">
                                                    {{ __('Default') }}
                                                </span>
                                            @endif
                                        </div>
                                        @if ($language->native_name && $language->native_name !== $language->name)
                                            <div class="text-xs text-zinc-600 mt-0.5 truncate">{{ $language->native_name }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Code --}}
                            <td class="px-4 py-3.5">
                                <span class="font-mono text-xs text-zinc-600">{{ $language->code }}</span>
                                @if ($language->direction === 'rtl')
                                    <span class="block text-[10px] text-zinc-400 mt-0.5">RTL</span>
                                @endif
                            </td>

                            {{-- Completion --}}
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-1.5 rounded-full bg-zinc-100 overflow-hidden min-w-[60px]">
                                        <div class="h-full rounded-full transition-all"
                                            style="width:{{ $percent }}%;background:{{ $percent >= 100 ? '#22c55e' : ($percent >= 50 ? '#6366f1' : '#f59e0b') }}"></div>
                                    </div>
                                    <span class="text-xs text-zinc-500 tabular-nums shrink-0">{{ $percent }}%</span>
                                </div>
                                <div class="text-[10px] text-zinc-400 mt-1">
                                    {{ $done }} / {{ $totalKeys }}
                                </div>
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-3.5">
                                @if ($language->is_active)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 inline-block"></span>
                                        {{ __('Active') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-50 text-yellow-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-yellow-400 inline-block"></span>
                                        {{ __('Inactive') }}
                                    </span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-3.5">
                                <div class="flex items-center justify-end gap-1.5">

                                    {{-- Make default --}}
                                    @unless ($language->is_default)
                                        <div class="relative group">
                                            <button wire:click="makeDefault({{ $language->id }})"
                                                aria-label="{{ __('Set as default') }}"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg border transition-all duration-150 bg-amber-50 text-amber-500 hover:bg-amber-500 hover:text-white hover:-translate-y-px">
                                                <flux:icon.star class="w-3.5 h-3.5" />
                                            </button>
                                            <span class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 rounded text-[11px] font-medium bg-amber-500 text-white whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity z-10">
                                                {{ __('Set as default') }}
                                                <span class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-amber-500"></span>
                                            </span>
                                        </div>
                                    @endunless

                                    {{-- Toggle active --}}
                                    <div class="relative group">
                                        <button wire:click="toggleActive({{ $language->id }})"
                                            aria-label="{{ $language->is_active ? __('Deactivate') : __('Activate') }}"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg border transition-all duration-150 bg-zinc-50 text-zinc-500 hover:bg-zinc-600 hover:text-white hover:-translate-y-px">
                                            @if ($language->is_active)
                                                <flux:icon.eye-slash class="w-3.5 h-3.5" />
                                            @else
                                                <flux:icon.eye class="w-3.5 h-3.5" />
                                            @endif
                                        </button>
                                        <span class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 rounded text-[11px] font-medium bg-zinc-600 text-white whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity z-10">
                                            {{ $language->is_active ? __('Deactivate') : __('Activate') }}
                                            <span class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-zinc-600"></span>
                                        </span>
                                    </div>

                                    {{-- Edit --}}
                                    <div class="relative group">
                                        <a href="{{ route('admin.languages.edit', $language->id) }}" wire:navigate
                                            aria-label="{{ __('Edit language') }}"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg border transition-all duration-150 bg-indigo-50 text-indigo-500 hover:bg-indigo-500 hover:text-white hover:-translate-y-px">
                                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                                            </svg>
                                        </a>
                                        <span class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 rounded text-[11px] font-medium bg-indigo-500 text-white whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity z-10">
                                            {{ __('Edit') }}
                                            <span class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-indigo-500"></span>
                                        </span>
                                    </div>

                                    {{-- Delete --}}
                                    @unless ($language->is_default)
                                        <div class="relative group">
                                            <button wire:click="confirmDelete({{ $language->id }})"
                                                aria-label="{{ __('Delete language') }}"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg border transition-all duration-150 bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white hover:-translate-y-px">
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none"
                                                    stroke="currentColor" stroke-width="2">
                                                    <polyline points="3 6 5 6 21 6" />
                                                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                                                    <path d="M10 11v6" />
                                                    <path d="M14 11v6" />
                                                    <path d="M9 6V4h6v2" />
                                                </svg>
                                            </button>
                                            <span class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 rounded text-[11px] font-medium bg-rose-500 text-white whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity z-10">
                                                {{ __('Delete') }}
                                                <span class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-rose-500"></span>
                                            </span>
                                        </div>
                                    @endunless

                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <flux:icon.language class="w-10 h-10 text-zinc-200 mx-auto mb-3" />
                                <p class="text-sm text-zinc-600">{{ __('No languages found.') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="px-6 py-3 border-t border-zinc-100">
        {{ $languages->links() }}
    </div>

    {{-- Delete Modal --}}
    <flux:modal name="language-delete" class="md:w-96"
        x-on:open-modal.window="if ($event.detail.name === 'language-delete') $flux.modal('language-delete').show()"
        x-on:close-modal.window="if ($event.detail.name === 'language-delete') $flux.modal('language-delete').close()">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                    </svg>
                </div>
                <flux:heading>{{ __('Delete language?') }}</flux:heading>
            </div>
            <flux:text class="text-sm text-zinc-500">
                {{ __('This action cannot be undone. Every translation stored for this language will be deleted too.') }}
            </flux:text>
            <div class="flex gap-2 pt-1">
                <button wire:click="delete"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 transition-colors border-none cursor-pointer">
                    {{ __('Delete') }}
                </button>
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

</div>
