@props(['colspan'])

<tr class="bg-zinc-50/60 lg:hidden">
    <td colspan="{{ $colspan }}" class="px-4 py-1">
        <div class="divide-y divide-zinc-100 max-w-xl">
            {{ $slot }}
        </div>
    </td>
</tr>
