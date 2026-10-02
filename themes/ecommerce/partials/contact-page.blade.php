{{--
    The /contact page: contact details (from Settings → General) beside the
    contact form. Only the details that are filled in are shown. The form
    component is shared with other themes; its colors come from the --form-*
    variables set on the card below (tied to the storefront button color).
--}}
@php
    $contactPhone = \App\Models\Setting::get('contact_phone');
    $contactEmail = \App\Models\Setting::get('contact_email');
    $contactAddress = \App\Models\Setting::get('contact_address');

    $socials = collect([
        'facebook' => 'Facebook',
        'twitter' => 'Twitter / X',
        'instagram' => 'Instagram',
        'youtube' => 'YouTube',
        'linkedin' => 'LinkedIn',
    ])->map(fn ($label, $platform) => ['url' => \App\Models\SocialLink::url($platform), 'label' => $label])
        ->filter(fn ($social) => filled($social['url']));

    $socialStyles = [
        'facebook' => 'bg-[#0866ff]',
        'twitter' => 'bg-[#1f1f1f]',
        'youtube' => 'bg-[#ff0033]',
        'linkedin' => 'bg-[#0a66c2]',
        'instagram' => 'bg-[linear-gradient(90deg,#740ff3,#f502c8,#f72a25,#f79003,#f70f58)]',
    ];

    $details = array_filter([
        filled($contactPhone) ? [
            'label' => __('Call us'),
            'value' => $contactPhone,
            'href' => 'tel:'.preg_replace('/[^0-9+]/', '', $contactPhone),
            'icon' => 'M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z',
        ] : null,
        filled($contactEmail) ? [
            'label' => __('Email us'),
            'value' => $contactEmail,
            'href' => 'mailto:'.$contactEmail,
            'icon' => 'M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75',
        ] : null,
        filled($contactAddress) ? [
            'label' => __('Visit us'),
            'value' => $contactAddress,
            'href' => null,
            'icon' => 'M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z',
        ] : null,
    ]);
@endphp

{{-- The shared form's button label follows the storefront button text color. --}}
<style>[data-contact-page] .contact-form-submit { color: var(--color-sf-button-text); }</style>

<div class="bg-page-bg" data-contact-page>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:py-14">
        <div class="mb-8 max-w-2xl">
            <h1 class="text-3xl font-extrabold tracking-tight text-sf-heading md:text-4xl">{{ $page->getTranslation('title', 'en', false) }}</h1>
            <p class="mt-2 text-base text-zinc-500">{{ __('Questions about an order or a product? Send us a message and our team will get back to you.') }}</p>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[22rem_1fr] lg:items-start">
            {{-- Contact details --}}
            @if ($details !== [] || $socials->isNotEmpty())
                <aside class="overflow-hidden rounded-card border border-zinc-200 bg-white shadow-sm">
                    <div class="border-b border-zinc-100 bg-zinc-50/60 px-5 py-4">
                        <h2 class="text-base font-bold text-sf-heading">{{ __('Contact information') }}</h2>
                        <p class="mt-0.5 text-xs text-zinc-500">{{ __('Reach us directly through any of these.') }}</p>
                    </div>

                    <ul class="divide-y divide-zinc-100">
                        @foreach ($details as $detail)
                            <li class="flex items-start gap-4 px-5 py-4">
                                <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-brand/10 text-brand">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $detail['icon'] }}" />
                                    </svg>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ $detail['label'] }}</p>
                                    @if ($detail['href'])
                                        <a href="{{ $detail['href'] }}" class="mt-0.5 block break-words text-[15px] font-semibold text-sf-heading transition hover:text-brand">{{ $detail['value'] }}</a>
                                    @else
                                        <p class="mt-0.5 whitespace-pre-line text-[15px] font-medium leading-relaxed text-sf-heading">{{ $detail['value'] }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    @if ($socials->isNotEmpty())
                        <div class="border-t border-zinc-100 px-5 py-4">
                            <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ __('Follow us') }}</p>
                            <div class="flex flex-wrap gap-2.5">
                                @foreach ($socials as $platform => $social)
                                    <a href="{{ $social['url'] }}" target="_blank" rel="noopener" aria-label="{{ $social['label'] }}" title="{{ $social['label'] }}"
                                        class="flex h-9 w-9 items-center justify-center rounded-full text-white transition hover:scale-105 {{ $socialStyles[$platform] ?? 'bg-zinc-700' }}">
                                        @if ($platform === 'facebook')
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4"><path d="M15 3h3v4h-3c-1.1 0-2 .9-2 2v2h4l-1 4h-3v7h-4v-7H7v-4h3V9c0-3 2-6 5-6Z"/></svg>
                                        @elseif ($platform === 'twitter')
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4"><path d="M17.7 3h3.1l-6.8 7.8L22 21h-6.3l-4.9-6.4L5 21H1.9l7.3-8.3L2 3h6.4l4.4 5.8L17.7 3Zm-1.1 16h1.7L7.5 4.7H5.6L16.6 19Z"/></svg>
                                        @elseif ($platform === 'youtube')
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4"><path d="M23 7.5c0-1.7-1.2-3-2.7-3.2C18.5 4 15.3 4 12 4s-6.5 0-8.3.3C2.2 4.5 1 5.8 1 7.5 1 9.2 1 12 1 12s0 2.8 1 4.5c0 1.7 1.2 3 2.7 3.2C5.5 20 8.7 20 12 20s6.5 0 8.3-.3c1.5-.2 2.7-1.5 2.7-3.2 1-1.7 1-4.5 1-4.5s0-2.8-1-4.5ZM10 15.5v-7l6 3.5-6 3.5Z"/></svg>
                                        @elseif ($platform === 'linkedin')
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4"><path d="M4.98 3.5A2.49 2.49 0 0 0 2.5 6a2.49 2.49 0 0 0 2.48 2.5A2.5 2.5 0 0 0 7.5 6a2.5 2.5 0 0 0-2.52-2.5ZM3 9.75h4v11H3v-11Zm6.5 0h3.8v1.5h.05c.53-1 1.84-2.05 3.78-2.05 4.05 0 4.8 2.66 4.8 6.13v5.42h-4v-4.8c0-1.15-.02-2.62-1.6-2.62-1.6 0-1.84 1.25-1.84 2.54v4.88h-4v-11Z"/></svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4"><path d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5Zm0 2a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3H7Zm5 3.5a4.5 4.5 0 1 1 0 9 4.5 4.5 0 0 1 0-9Zm0 2a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5Zm5.25-3.25a1 1 0 1 1 0 2 1 1 0 0 1 0-2Z"/></svg>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </aside>
            @endif

            {{-- Message form --}}
            <section class="overflow-hidden rounded-card border border-zinc-200 bg-white shadow-sm @if ($details === [] && $socials->isEmpty()) lg:col-span-2 @endif"
                style="--form-accent: var(--color-sf-button); --form-accent-hover: var(--color-sf-button); --form-label: var(--color-sf-heading)">
                <div class="border-b border-zinc-100 bg-zinc-50/60 px-5 py-4 sm:px-6">
                    <h2 class="text-base font-bold text-sf-heading">{{ __('Send us a message') }}</h2>
                    <p class="mt-0.5 text-xs text-zinc-500">{{ __("Fill in the form and we'll get back to you by email.") }}</p>
                </div>
                <div class="p-5 sm:p-6">
                    <livewire:frontend.contact-form :message-placeholder="__('How can we help you?')" />
                </div>
            </section>
        </div>
    </div>
</div>
