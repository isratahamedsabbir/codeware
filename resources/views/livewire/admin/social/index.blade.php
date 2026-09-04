<div class="max-w-[1600px] space-y-6">
    <flux:text class="text-zinc-500">
        Social profile links for your site. These appear across the site (footer, contact sections, etc.).
    </flux:text>

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

    <x-admin-section-card icon="share" title="Social Links" class="max-w-md"
        description="Where each platform icon should link to.">
        @foreach ($links as $index => $link)
            <flux:field>
                <flux:label>
                    <span class="inline-flex items-center gap-2">
                        <span class="inline-flex size-5 items-center justify-center rounded-full text-[10px] font-bold text-white"
                            style="background-color: {{ $colors[$link['platform']] ?? '#71717a' }}">
                            {{ strtoupper(substr($link['label'], 0, 1)) }}
                        </span>
                        {{ $link['label'] }}
                    </span>
                </flux:label>
                <flux:input wire:model="links.{{ $index }}.url"
                    placeholder="{{ $link['platform'] === 'whatsapp' ? '+8801XXXXXXXXX' : 'https://' }}" />
            </flux:field>
        @endforeach
    </x-admin-section-card>

    <div>
        <flux:button variant="primary" size="sm" wire:click="save" wire:loading.attr="disabled">
            Save Settings
        </flux:button>
    </div>
</div>
