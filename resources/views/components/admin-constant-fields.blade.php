@props([
    'items' => [],
    'openConstants' => [],
    'model' => 'constants',
    'keyPlaceholder' => 'e.g. support_email',
    'valuePlaceholder' => 'e.g. support@example.com',
    'emptyTitle' => 'No constants yet',
    'emptyHint' => null,
])

<div class="space-y-3">
    @forelse ($items as $i => $pair)
        @php
            $constantOpen = in_array($i, $openConstants, true);
            $constantIsFile = ($pair['type'] ?? 'textarea') === 'file';
        @endphp
        <div wire:key="constant-row-{{ $i }}"
            class="group overflow-hidden rounded-lg border bg-white transition-all dark:bg-zinc-800/40 {{ $constantOpen ? 'border-zinc-300 shadow-sm dark:border-zinc-600' : 'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700' }}">
            <div wire:click="toggleConstant({{ $i }})" role="button" tabindex="0"
                class="flex cursor-pointer select-none items-center justify-between gap-4 px-4 py-3">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex size-8 shrink-0 items-center justify-center rounded-lg transition-colors {{ $constantOpen ? 'bg-primary/10 text-primary' : 'bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500' }}">
                        <flux:icon.variable class="size-4" />
                    </div>
                    <div class="min-w-0">
                        <p class="truncate font-mono text-sm font-semibold text-zinc-800 dark:text-zinc-100">
                            {{ filled($pair['key'] ?? '') ? $pair['key'] : 'Field '.($i + 1) }}
                        </p>
                        @if (! $constantOpen && filled($pair['value'] ?? ''))
                            <p class="truncate text-xs text-zinc-400 dark:text-zinc-500">{{ $pair['value'] }}</p>
                        @endif
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-1.5">
                    <div class="flex rounded-lg bg-zinc-100 p-0.5 dark:bg-zinc-800">
                        <button type="button" wire:click.stop="setConstantType({{ $i }}, 'textarea')"
                            class="flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-medium transition-colors cursor-pointer {{ $constantIsFile ? 'text-zinc-400 hover:text-zinc-600' : 'bg-white text-primary shadow-sm ring-1 ring-zinc-200 dark:bg-zinc-700 dark:ring-zinc-600' }}"
                            title="Textarea (plain text)">
                            <flux:icon.bars-3 class="size-3.5" />
                            <span class="hidden md:inline">Textarea</span>
                        </button>
                        <button type="button" wire:click.stop="setConstantType({{ $i }}, 'file')"
                            class="flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-medium transition-colors cursor-pointer {{ $constantIsFile ? 'bg-white text-primary shadow-sm ring-1 ring-zinc-200 dark:bg-zinc-700 dark:ring-zinc-600' : 'text-zinc-400 hover:text-zinc-600' }}"
                            title="File (media library asset)">
                            <flux:icon.document-text class="size-3.5" />
                            <span class="hidden md:inline">File</span>
                        </button>
                    </div>

                    <button type="button" wire:click.stop="removeConstant({{ $i }})"
                        class="flex size-7 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-rose-50 hover:text-rose-500 cursor-pointer" aria-label="Remove field">
                        <flux:icon.x-mark class="size-4" />
                    </button>
                    <button type="button" wire:click.stop="toggleConstant({{ $i }})"
                        class="flex size-7 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-600 cursor-pointer"
                        aria-expanded="{{ $constantOpen ? 'true' : 'false' }}" aria-label="Toggle field">
                        <flux:icon.chevron-down class="size-4 transition-transform {{ $constantOpen ? 'rotate-180' : '' }}" />
                    </button>
                </div>
            </div>

            <div class="space-y-4 border-t border-zinc-100 p-4 {{ $constantOpen ? '' : 'hidden' }} dark:border-zinc-700">
                <div>
                    <flux:label>Key</flux:label>
                    <flux:input wire:model.live="{{ $model }}.{{ $i }}.key" placeholder="{{ $keyPlaceholder }}" class="font-mono" />
                    <flux:error name="{{ $model }}.{{ $i }}.key" />
                </div>

                @if ($constantIsFile)
                    <div>
                        <flux:label>Value</flux:label>
                        <x-media-picker model="{{ $model }}.{{ $i }}.value" label="Value" dropzone drop-height="h-[6.5rem]" />
                        <flux:error name="{{ $model }}.{{ $i }}.value" />
                    </div>
                @else
                    <div>
                        <flux:label>Value</flux:label>
                        <flux:textarea wire:model="{{ $model }}.{{ $i }}.value" class="h-[6.5rem] resize-none" placeholder="{{ $valuePlaceholder }}" />
                        <flux:error name="{{ $model }}.{{ $i }}.value" />
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div class="rounded-lg border border-dashed border-zinc-200 py-12 text-center dark:border-zinc-700">
            <div class="mx-auto mb-3 flex size-10 items-center justify-center rounded-full bg-zinc-50 text-zinc-300 dark:bg-zinc-800 dark:text-zinc-600">
                <flux:icon.variable class="size-5" />
            </div>
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-300">{{ $emptyTitle }}</p>
            @if ($emptyHint)
                <p class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">{{ $emptyHint }}</p>
            @endif
        </div>
    @endforelse
    </div>