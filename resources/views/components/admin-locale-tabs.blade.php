@props(['locales' => null])
@php
    $locales = $locales ?? \App\Support\Locale::translatable();

    $first = $locales->first()->code;
@endphp
<div x-data="{ locale: '{{ $first }}' }">
    <div class="mb-4">
        <div class="flex gap-2 flex-wrap">
            @foreach ($locales as $language)
                <button type="button"
                    :class="locale === '{{ $language->code }}' ? 'bg-zinc-900 text-white' : 'bg-zinc-100 text-zinc-500 hover:bg-zinc-200'"
                    class="px-3.5 py-1.5 text-xs font-medium rounded-md transition-colors"
                    @click="locale='{{ $language->code }}'">{{ $language->flag ? $language->flag.' ' : '' }}{{ strtoupper($language->code) }}</button>
            @endforeach
        </div>
        <div class="mt-3 h-px bg-linear-to-r from-zinc-300 via-zinc-200 to-transparent dark:from-zinc-600 dark:via-zinc-700"></div>
    </div>

    {{ $slot }}
</div>
