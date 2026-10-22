<div class="mb-8">
    <div class="mb-2 flex items-center justify-center gap-3">
        <h2 class="text-center text-[20px] font-bold uppercase tracking-wide text-sf-text md:text-[25px]">{{ $title }}</h2>
    </div>
    @if (filled($subtitle ?? null))
        <p class="text-center text-sm text-gray-600 md:text-base">{{ $subtitle }}</p>
    @endif
</div>