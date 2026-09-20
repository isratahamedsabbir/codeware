@php
    $palettes = [
        'pending' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
        'processing' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
        'shipped' => 'bg-violet-50 text-violet-700 ring-violet-600/20',
        'delivered' => 'bg-green-50 text-green-700 ring-green-600/20',
        'cancelled' => 'bg-red-50 text-red-700 ring-red-600/20',
        'failed' => 'bg-red-50 text-red-700 ring-red-600/20',
        'paid' => 'bg-green-50 text-green-700 ring-green-600/20',
        'refunded' => 'bg-zinc-100 text-zinc-600 ring-zinc-500/20',
    ];
@endphp

<span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $palettes[$status] ?? 'bg-gray-50 text-gray-700 ring-gray-500/20' }}">
    {{ ucfirst($status) }}
</span>