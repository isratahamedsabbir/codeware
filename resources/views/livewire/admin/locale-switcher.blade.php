<div>
    @if ($languages->count() > 1)
        <flux:dropdown position="bottom" align="end">
            <flux:button variant="subtle" icon-trailing="chevron-down"
                :title="__('Change language')" :aria-label="__('Change language')"
                class="text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-100 transition-colors">
                <span class="flex items-center gap-1.5">
                    <flux:icon.language class="size-5" />
                    <span class="text-xs font-semibold uppercase max-sm:hidden">{{ $current }}</span>
                </span>
            </flux:button>

            <flux:menu class="min-w-48">
                <flux:menu.group :heading="__('Language')">
                    @foreach ($languages as $language)
                        <flux:menu.item wire:click="switchTo('{{ $language->code }}')"
                            :icon="$language->code === $current ? 'check' : null">
                            <span class="flex items-center gap-2">
                                <span>{{ $language->native_name ?: $language->name }}</span>
                                <span class="text-[10px] font-semibold uppercase text-zinc-400">{{ $language->code }}</span>
                            </span>
                        </flux:menu.item>
                    @endforeach
                </flux:menu.group>

                <flux:menu.separator />

                <flux:menu.item :href="route('admin.translations')" wire:navigate icon="language">
                    {{ __('Manage translations') }}
                </flux:menu.item>
            </flux:menu>
        </flux:dropdown>
    @endif
</div>
