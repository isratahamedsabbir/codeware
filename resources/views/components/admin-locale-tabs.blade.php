@props(['locales' => null])
@php
    $locales = $locales ?? \App\Support\Locale::active();

    // Before an admin has ever visited /admin/languages the languages table
    // is empty — fall back to a single tab for the primary locale so the
    // form still has somewhere to bind its translatable fields.
    if ($locales->isEmpty()) {
        $primary = \App\Support\Locale::primary();
        $locales = collect([(object) ['code' => $primary, 'name' => strtoupper($primary), 'native_name' => null, 'flag' => null]]);
    }

    $first = $locales->first()->code;
@endphp
<div x-data="{ locale: '{{ $first }}' }">
    <div class="flex gap-2 -mx-6 px-6 pb-3 mb-3 border-b border-zinc-200 dark:border-zinc-700 flex-wrap">
        @foreach ($locales as $language)
            <button type="button"
                :class="locale === '{{ $language->code }}' ? 'bg-zinc-900 text-white' : 'bg-zinc-100 text-zinc-500 hover:bg-zinc-200'"
                class="px-3.5 py-1.5 text-xs font-medium rounded-md transition-colors"
                @click="locale='{{ $language->code }}'">{{ $language->flag ? $language->flag.' ' : '' }}{{ strtoupper($language->code) }}</button>
        @endforeach
    </div>

    {{ $slot }}
</div>
