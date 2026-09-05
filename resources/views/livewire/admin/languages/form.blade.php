<div class="max-w-[1600px] w-full mx-auto flex-1">

    @push('page-header-actions')
        <flux:button variant="ghost" size="sm" class="admin-back-btn" icon="arrow-left" href="{{ route('admin.languages') }}" wire:navigate>
            {{ __('Back') }}
        </flux:button>
    @endpush

    <div class="flex gap-5 items-start max-lg:flex-col">

        {{-- ── MAIN ── --}}
        <div class="flex-1 min-w-0 bg-white rounded-lg border border-zinc-100 shadow-sm p-6 space-y-4">

            <flux:field>
                <flux:label>{{ __('Name') }} <span class="text-red-500 ml-0.5">*</span></flux:label>
                <flux:input wire:model="name" placeholder="{{ __('e.g. Arabic') }}" />
                <flux:description>{{ __('The English name of the language, shown in this admin panel.') }}</flux:description>
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Native name') }}</flux:label>
                <flux:input wire:model="native_name" placeholder="{{ __('e.g. العربية') }}" />
                <flux:description>{{ __('How speakers write the language themselves. Used in the language switcher.') }}</flux:description>
                <flux:error name="native_name" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Locale code') }} <span class="text-red-500 ml-0.5">*</span></flux:label>
                <flux:input wire:model="code" placeholder="{{ __('e.g. ar or pt-br') }}"
                    :disabled="(bool) $languageId" class="font-mono" />
                <flux:description>
                    @if ($languageId)
                        {{ __('The code cannot be changed — existing translations are stored against it.') }}
                    @else
                        {{ __('An ISO code, optionally with a region: en, bn, ar, pt-br.') }}
                    @endif
                </flux:description>
                <flux:error name="code" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Flag') }}</flux:label>
                <flux:input wire:model="flag" placeholder="🇧🇩" class="max-w-24 text-lg" />
                <flux:description>{{ __('Optional emoji shown next to the language name.') }}</flux:description>
                <flux:error name="flag" />
            </flux:field>

        </div>

        {{-- ── SIDEBAR ── --}}
        <div class="w-[320px] max-lg:w-full shrink-0 space-y-4">

            <x-admin-section-card icon="adjustments-horizontal" :title="__('Settings')" body-class="px-4 py-3 space-y-4"
                :description="__('Text direction and ordering in the switcher.')">

                <flux:field>
                    <flux:label>{{ __('Text direction') }}</flux:label>
                    <flux:select wire:model="direction">
                        <flux:select.option value="ltr">{{ __('Left to right (LTR)') }}</flux:select.option>
                        <flux:select.option value="rtl">{{ __('Right to left (RTL)') }}</flux:select.option>
                    </flux:select>
                    <flux:error name="direction" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Sort order') }}</flux:label>
                    <flux:input type="number" wire:model="sort_order" min="0" />
                    <flux:description>{{ __('Lower numbers appear first in the switcher.') }}</flux:description>
                    <flux:error name="sort_order" />
                </flux:field>

                <flux:field variant="inline">
                    <flux:switch wire:model="is_active" />
                    <flux:label>{{ __('Active') }}</flux:label>
                    <flux:description>{{ __('Inactive languages are hidden from the switcher.') }}</flux:description>
                </flux:field>

            </x-admin-section-card>

            @unless ($languageId)
                <div class="rounded-lg border border-indigo-100 bg-indigo-50/60 px-4 py-3">
                    <p class="text-xs text-indigo-700 leading-relaxed">
                        {{ __('Once saved, every existing translation key gets a blank entry for this language. Fill them in from the Translations screen.') }}
                    </p>
                </div>
            @endunless

            {{-- Footer --}}
            <div class="flex items-center gap-3 flex-wrap">
                <button wire:click="save" wire:loading.attr="disabled"
                    class="admin-btn-save inline-flex items-center gap-2 px-5 py-2 text-sm font-medium rounded-lg text-white disabled:opacity-60 transition-colors">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                        <polyline points="17 21 17 13 7 13 7 21" />
                        <polyline points="7 3 7 8 15 8" />
                    </svg>
                    {{ $languageId ? __('Update Language') : __('Create Language') }}
                </button>
            </div>

        </div>

    </div>
</div>
