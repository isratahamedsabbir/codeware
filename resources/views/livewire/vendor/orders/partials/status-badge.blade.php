@php
    $colors = [
        'pending' => 'bg-amber-50 text-amber-600 border-amber-200',
        'processing' => 'bg-sky-50 text-sky-600 border-sky-200',
        'shipped' => 'bg-violet-50 text-violet-600 border-violet-200',
        'delivered' => 'bg-emerald-50 text-emerald-600 border-emerald-200',
        'cancelled' => 'bg-rose-50 text-rose-600 border-rose-200',
    ];
@endphp
<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border {{ $colors[$status] ?? 'bg-zinc-100 text-zinc-500 border-zinc-200' }}">
    {{ ucfirst($status) }}
</span>
