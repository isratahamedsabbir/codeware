@php
    $colors = [
        'pending' => 'bg-amber-50 text-amber-700 ring-amber-600/10',
        'processing' => 'bg-sky-50 text-sky-700 ring-sky-600/10',
        'shipped' => 'bg-violet-50 text-violet-700 ring-violet-600/10',
        'delivered' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/10',
        'cancelled' => 'bg-rose-50 text-rose-700 ring-rose-600/10',
    ];
@endphp
<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ring-1 {{ $colors[$status] ?? 'bg-slate-50 text-zinc-500 ring-zinc-600/10' }}">
    {{ ucfirst($status) }}
</span>
