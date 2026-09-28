{{--
    The default theme is a way in, not a page: Admin / Vendor / Delivery login
    links and an owner-written intro, and nothing a search engine has any reason
    to keep. So it asks the auth layout for noindex + nofollow, and does not ask
    for the seo-meta block at all — a canonical URL and a set of og: tags on a
    page we are telling crawlers to drop is a sitemap entry and a social preview
    for a login screen. Themes::isIndexable() reads the same answer off this
    theme's theme.json for the sitemap, so the page and the sitemap cannot
    disagree about whether / is worth listing.
--}}
<x-layouts::auth :title="$title ?? null" :noindex="true" :custom-code="true">
    @php
        // The intro block is this theme's own settings, read from the theme.json
        // beside this file. See default/settings.blade.php for the admin side.
        $introHeading = \App\Support\ThemeSettings::text('default', 'theme_default_intro_heading');
        $introText = \App\Support\ThemeSettings::text('default', 'theme_default_intro_text');
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
