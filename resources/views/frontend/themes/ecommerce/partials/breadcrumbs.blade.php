@php
    $crumbs = $crumbs ?? [['label' => __('Home'), 'url' => url('/')]];
@endphp

<nav aria-label="{{ __('Breadcrumb') }}" class="mx-auto max-w-7xl px-4 pt-6 sm:px-6">
    <ol class="flex flex-wrap items-center gap-1.5 text-sm text-zinc-500">
        @foreach ($crumbs as $index => $crumb)
            @if ($index > 0)
                <li aria-hidden="true" class="text-zinc-300">/</li>
            @endif
            <li>
                @if (filled($crumb['url'] ?? null) && $index < count($crumbs) - 1)
                    <a href="{{ $crumb['url'] }}" class="hover:text-brand">{{ $crumb['label'] }}</a>
                @else
                    <span class="text-zinc-700">{{ $crumb['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>