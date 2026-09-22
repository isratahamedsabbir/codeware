<div class="max-w-[1600px] space-y-6">
    @php
        $colors = [
            'facebook' => '#1877f2',
            'twitter' => '#000000',
            'instagram' => '#e4405f',
            'youtube' => '#ff0000',
            'linkedin' => '#0a66c2',
            'tiktok' => '#000000',
            'whatsapp' => '#25d366',
        ];
    @endphp

    <x-admin-section-card icon="share" title="Social Links"
        description="Where each platform icon should link to. The default platforms are listed below — add new ones whenever you need more.">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 items-start">
            @foreach ($links as $index => $link)
                <div class="@if ($link['id'] === null) border-dashed @endif rounded-lg border border-zinc-300 overflow-hidden">
                    <div class="flex items-center justify-between gap-2 px-3 py-2.5 bg-zinc-50 border-b border-zinc-300">
                        <div class="min-w-0 flex-1">
                            @if ($link['id'] !== null)
                                <div class="flex items-center gap-2 text-sm text-zinc-500">
                                    <span class="inline-flex size-5 shrink-0 items-center justify-center rounded-full text-[10px] font-bold text-white"
                                        style="background-color: {{ $colors[$link['platform']] ?? '#71717a' }}">
                                        {{ strtoupper(substr($link['label'], 0, 1)) }}
                                    </span>
                                    <span class="truncate font-medium text-zinc-700">{{ $link['label'] }}</span>
                                </div>
                            @else
                                <input type="text" wire:model="links.{{ $index }}.platform"
                                    placeholder="{{ __('Platform (e.g. Discord)') }}"
                                    class="w-full border-0 bg-transparent px-0 py-0 text-sm font-medium text-zinc-700 placeholder:text-zinc-400 focus:outline-none focus:ring-0" />
                            @endif
                        </div>
                        <button type="button" wire:click="removeLink({{ $index }})"
                            title="{{ __('Remove this link') }}"
                            class="inline-flex size-6 shrink-0 items-center justify-center rounded-full text-zinc-400 transition hover:bg-red-50 hover:text-red-600 cursor-pointer">
                            <flux:icon.trash class="size-3.5" />
                        </button>
                    </div>

                    <div class="space-y-2.5 px-3 py-3">
                        @if ($link['id'] === null)
                            <input type="text" wire:model="links.{{ $index }}.label"
                                placeholder="{{ __('Label (e.g. Discord)') }}"
                                class="w-full rounded-md border border-zinc-300 px-2.5 py-1.5 text-sm text-zinc-700 placeholder:text-zinc-400 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" />
                        @endif

                        <div class="flex items-center rounded-md border border-zinc-300 overflow-hidden focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/20">
                            <span class="shrink-0 px-2.5 bg-zinc-50 border-r border-zinc-300 text-[11px] font-semibold uppercase tracking-wide text-zinc-400">
                                URL
                            </span>
                            <input type="text" wire:model="links.{{ $index }}.url"
                                placeholder="{{ $link['platform'] === 'whatsapp' ? '+8801XXXXXXXXX' : 'https://' }}"
                                class="flex-1 min-w-0 border-0 px-2.5 py-1.5 text-sm text-zinc-700 placeholder:text-zinc-400 focus:outline-none focus:ring-0" />
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-5">
            <flux:button size="sm" variant="subtle" icon="plus" wire:click="addLink">
                {{ __('Add social link') }}
            </flux:button>
        </div>
    </x-admin-section-card>

    <div>
        <flux:button variant="primary" size="sm" wire:click="save" wire:loading.attr="disabled">
            Save Settings
        </flux:button>
    </div>
</div>