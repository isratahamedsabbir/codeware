<a
    href="{{ route('favorites') }}"
    aria-label="{{ __('My favorites') }} ({{ $count }})"
    title="{{ __('My favorites') }}"
    class="relative flex h-10 w-10 items-center justify-center rounded-full text-white transition hover:bg-white/10"
>
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-6 w-6">
        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
    </svg>
    @if ($count > 0)
        <span class="absolute -right-0.5 -top-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-white text-xs font-bold text-brand">{{ $count }}</span>
    @endif
</a>