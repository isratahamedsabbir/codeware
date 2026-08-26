<div class="bg-white rounded-lg shadow-sm overflow-hidden">

    {{-- Completion summary --}}
    @if ($locales->isNotEmpty())
        <div class="flex gap-3 px-6 py-4 border-b border-zinc-100 flex-wrap">
            @foreach ($locales as $language)
                @php $stat = $stats[$language->code] ?? ['translated' => 0, 'total' => 0, 'percent' => 0]; @endphp
                <div class="flex-1 min-w-[180px] rounded-lg border border-zinc-100 px-4 py-3">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="text-sm font-medium text-zinc-700 flex items-center gap-1.5 min-w-0">
                            <span class="truncate">{{ $language->native_name ?: $language->name }}</span>
                        </span>
                        <span class="text-xs font-semibold text-zinc-500 tabular-nums shrink-0">{{ $stat['percent'] }}%</span>
                    </div>
                    <div class="h-1.5 rounded-full bg-zinc-100 overflow-hidden">
                        <div class="h-full rounded-full transition-all"
                            style="width:{{ $stat['percent'] }}%;background:{{ $stat['percent'] >= 100 ? 'var(--color-success)' : ($stat['percent'] >= 50 ? 'var(--color-progress-mid)' : 'var(--color-progress-low)') }}"></div>
                    </div>
                    <div class="text-[10px] text-zinc-400 mt-1.5 tabular-nums">
                        {{ $stat['translated'] }} / {{ $stat['total'] }} {{ __('keys') }}
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Actions --}}
    <div class="flex items-center justify-between gap-3 px-6 py-4 border-b border-zinc-100 flex-wrap">
        <p class="text-sm text-zinc-500">
            {{ __('Edit the wording used across the admin panel. Blank entries fall back to the default language.') }}
        </p>
        <div class="flex items-center gap-3 flex-wrap">
            <button wire:click="scan" wire:loading.attr="disabled" wire:target="scan"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg border border-zinc-200 text-zinc-600 bg-white hover:bg-zinc-50 disabled:opacity-60 transition-colors">
                <flux:icon.arrow-path class="size-4" wire:loading.class="animate-spin" wire:target="scan" />
                {{ __('Scan source') }}
            </button>

            <flux:modal.trigger name="translation-add">
                <button
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg border border-zinc-200 text-zinc-600 bg-white hover:bg-zinc-50 transition-colors">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="12" y1="5" x2="12" y2="19" />
                        <line x1="5" y1="12" x2="19" y2="12" />
                    </svg>
                    {{ __('Add key') }}
                </button>
            </flux:modal.trigger>

            <button wire:click="save" wire:loading.attr="disabled" wire:target="save"
                class="admin-btn-save inline-flex items-center gap-2 px-5 py-2 text-sm font-medium rounded-lg text-white disabled:opacity-60 transition-colors">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                    <polyline points="17 21 17 13 7 13 7 21" />
                    <polyline points="7 3 7 8 15 8" />
                </svg>
                {{ __('Save changes') }}
            </button>
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
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="{{ __('Search keys or translations…') }}"
                class="w-full pl-9 pr-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all" />
        </div>

        <select wire:model.live="filter"
            class="px-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 bg-white appearance-none pr-8 min-w-[160px] transition-all"
            style="background-image:url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%23aaa' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E&quot;);background-repeat:no-repeat;background-position:right 10px center">
            <option value="all">{{ __('All keys') }}</option>
            <option value="untranslated">{{ __('Untranslated only') }}</option>
            <option value="translated">{{ __('Translated only') }}</option>
        </select>

        @if ($filter !== 'all')
            <select wire:model.live="localeFilter"
                class="px-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 bg-white appearance-none pr-8 min-w-[140px] transition-all"
                style="background-image:url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%23aaa' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E&quot;);background-repeat:no-repeat;background-position:right 10px center">
                @foreach ($locales as $language)
                    <option value="{{ $language->code }}">{{ $language->name }}</option>
                @endforeach
            </select>
        @endif

        @if ($groups->count() > 1)
            <select wire:model.live="groupFilter"
                class="px-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 bg-white appearance-none pr-8 min-w-[140px] transition-all"
                style="background-image:url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%23aaa' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E&quot;);background-repeat:no-repeat;background-position:right 10px center">
                <option value="">{{ __('All groups') }}</option>
                @foreach ($groups as $group)
                    <option value="{{ $group }}">{{ $group === '*' ? __('UI strings') : $group }}</option>
                @endforeach
            </select>
        @endif
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <div class="border border-zinc-100 rounded-lg">
            <table class="w-full divide-y divide-gray-200">
                <thead>
                    <tr class="bg-zinc-50">
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider min-w-[220px]">
                            {{ __('Key') }}
                        </th>
                        @foreach ($locales as $language)
                            <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider min-w-[220px]">
                                <span class="flex items-center gap-1.5">
                                    {{ $language->name }}
                                    @if ($language->is_default)
                                        <span class="text-[9px] font-semibold text-indigo-500 normal-case">({{ __('default') }})</span>
                                    @endif
                                </span>
                            </th>
                        @endforeach
                        <th class="px-4 py-2.5 text-right text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider w-16">
                            {{ __('Actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($rows as $row)
                        @php $cellsForRow = $cells["{$row->group}|{$row->key}"] ?? collect(); @endphp
                        <tr class="hover:bg-indigo-50/20 transition-colors align-top">

                            {{-- Key --}}
                            <td class="px-4 py-3">
                                <div class="font-mono text-xs text-zinc-700 break-words leading-relaxed">{{ $row->key }}</div>
                                @if ($row->group !== '*')
                                    <span class="inline-flex items-center px-1.5 py-0.5 mt-1 rounded text-[10px] font-medium bg-zinc-100 text-zinc-500">
                                        {{ $row->group }}
                                    </span>
                                @endif
                            </td>

                            {{-- One editable cell per language --}}
                            @foreach ($locales as $language)
                                @php $cell = $cellsForRow->get($language->code); @endphp
                                <td class="px-4 py-3">
                                    @if ($cell)
                                        <textarea wire:model="values.{{ $cell->id }}" rows="1"
                                            dir="{{ $language->direction }}"
                                            placeholder="{{ $language->is_default ? $row->key : __('Not translated') }}"
                                            class="w-full px-2.5 py-1.5 text-sm border rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all resize-y min-h-[38px] {{ blank($cell->value) ? 'border-amber-200 bg-amber-50/40' : 'border-zinc-200' }}">{{ $values[$cell->id] ?? $cell->value }}</textarea>
                                    @else
                                        <span class="text-xs text-zinc-400">—</span>
                                    @endif
                                </td>
                            @endforeach

                            {{-- Actions --}}
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end">
                                    <div class="relative group">
                                        <button wire:click="confirmDelete(@js($row->group), @js($row->key))"
                                            aria-label="{{ __('Delete key') }}"
                                            class="inline-flex items-center justify-center w-7 h-7 rounded border transition-all duration-150 border-rose-500 text-rose-500 hover:bg-rose-500 hover:text-white hover:-translate-y-px"
                                            style="box-shadow:none"
                                            onmouseover="this.style.boxShadow='0 3px 8px rgba(225,29,72,.35)'"
                                            onmouseout="this.style.boxShadow='none'">
                                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2">
                                                <polyline points="3 6 5 6 21 6" />
                                                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                                                <path d="M10 11v6" />
                                                <path d="M14 11v6" />
                                                <path d="M9 6V4h6v2" />
                                            </svg>
                                        </button>
                                        <span class="pointer-events-none absolute bottom-full right-0 mb-2 px-2 py-1 rounded text-[11px] font-medium bg-rose-500 text-white whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity z-10">
                                            {{ __('Delete key') }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $locales->count() + 2 }}" class="px-6 py-16 text-center">
                                <flux:icon.language class="w-10 h-10 text-zinc-200 mx-auto mb-3" />
                                <p class="text-sm text-zinc-600">{{ __('No translation keys found.') }}</p>
                                <p class="text-xs text-zinc-400 mt-1">
                                    {{ __('Use “Scan source” to pull translatable strings out of the codebase.') }}
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="px-6 py-3">
        {{ $rows->links() }}
    </div>

    {{-- Add Key Modal --}}
    <flux:modal name="translation-add" class="md:w-96">
        <div class="space-y-4">
            <flux:heading>{{ __('Add translation key') }}</flux:heading>
            <flux:text class="text-sm text-zinc-500">
                {{ __('Add a key you reference with __() in code but that has not been scanned yet.') }}
            </flux:text>

            <flux:field>
                <flux:label>{{ __('Key') }}</flux:label>
                <flux:input wire:model="newKey" placeholder="{{ __('e.g. Save changes') }}" />
                <flux:error name="newKey" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Group') }}</flux:label>
                <flux:input wire:model="newGroup" placeholder="*" class="font-mono" />
                <flux:description>{{ __('Leave as * for plain __("string") keys.') }}</flux:description>
            </flux:field>

            <div class="flex gap-2 pt-1">
                <button wire:click="addKey" wire:loading.attr="disabled" wire:target="addKey"
                    class="admin-btn-save inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg text-white disabled:opacity-60 transition-colors border-none cursor-pointer">
                    {{ __('Add key') }}
                </button>
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    {{-- Delete Key Modal --}}
    <flux:modal name="translation-delete" class="md:w-96"
        x-on:open-modal.window="if ($event.detail.name === 'translation-delete') $flux.modal('translation-delete').show()"
        x-on:close-modal.window="if ($event.detail.name === 'translation-delete') $flux.modal('translation-delete').close()">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                    </svg>
                </div>
                <flux:heading>{{ __('Delete this key?') }}</flux:heading>
            </div>
            <flux:text class="text-sm text-zinc-500">
                {{ __('Its translations in every language will be removed. The key reappears on the next scan if it is still used in code.') }}
            </flux:text>
            @if ($deletingKey)
                <p class="font-mono text-xs text-zinc-600 bg-zinc-50 border border-zinc-100 rounded px-3 py-2 break-words">
                    {{ $deletingKey }}
                </p>
            @endif
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

    {{-- Surface the scan summary for debugging --}}
    <div x-data x-on:translations-scanned.window="console.log($event.detail.output)"></div>

</div>
