@php
    $items = [
        [
            'label' => __('My Account'),
            'href' => route('account.dashboard'),
            'active' => request()->routeIs('account.dashboard'),
        ],
        [
            'label' => __('My Orders'),
            'href' => route('account.orders'),
            'active' => request()->routeIs('account.orders', 'account.orders.show'),
        ],
        [
            'label' => __('Profile'),
            'href' => route('account.profile'),
            'active' => request()->routeIs('account.profile'),
        ],
    ];
@endphp

<aside class="shrink-0 md:w-64">
    <nav class="flex flex-col gap-1 rounded-card border border-zinc-200 bg-white p-2.5 md:sticky md:top-20">
        @foreach ($items as $item)
            <a href="{{ $item['href'] }}"
                class="rounded-md px-3 py-2.5 text-sm font-semibold transition-colors {{ $item['active'] ? 'bg-sf-button text-sf-button-text' : 'text-zinc-700 hover:bg-gray-50 hover:text-brand' }}">
                {{ $item['label'] }}
            </a>
        @endforeach

        <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t border-zinc-100 pt-2">
            @csrf
            <button type="submit"
                class="flex w-full items-center gap-2 rounded-md px-3 py-2.5 text-left text-sm font-semibold text-red-600 transition-colors hover:bg-red-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                </svg>
                {{ __('Log out') }}
            </button>
        </form>
    </nav>
</aside>