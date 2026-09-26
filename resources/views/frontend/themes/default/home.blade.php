<x-layouts::auth :title="$title ?? null" :noindex="false" :custom-code="true">
    @php
        $introHeading = \App\Models\Setting::get('theme_default_intro_heading');
        $introText = \App\Models\Setting::get('theme_default_intro_text');
    @endphp

    <div class="flex flex-col items-center gap-8 text-center">
        @if ($introHeading || $introText)
            <div class="max-w-md space-y-2">
                @if ($introHeading)
                    <h1 class="text-2xl font-bold text-white">{{ $introHeading }}</h1>
                @endif
                @if ($introText)
                    <p class="text-sm text-white/70">{{ $introText }}</p>
                @endif
            </div>
        @endif

        <div class="flex flex-col items-center gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('login') }}"
                    class="rounded-md bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-secondary transition-colors">
                    {{ __('Admin Login') }}
                </a>
                @if ($showVendorLogin ?? false)
                    <a href="{{ route('vendor.login') }}"
                        class="rounded-md border border-white/20 px-5 py-2.5 text-sm font-semibold text-white hover:bg-white/10 transition-colors">
                        {{ __('Vendor Login') }}
                    </a>
                @endif
                @if ($showDeliveryLogin ?? false)
                    <a href="{{ route('delivery.login') }}"
                        class="rounded-md border border-white/20 px-5 py-2.5 text-sm font-semibold text-white hover:bg-white/10 transition-colors">
                        {{ __('Delivery Login') }}
                    </a>
                @endif
            </div>
        </div>
    </div>
</x-layouts::auth>
