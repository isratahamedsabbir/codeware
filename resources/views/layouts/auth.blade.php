<x-layouts::auth.split :title="$title ?? null" :description="$description ?? null" :noindex="$noindex ?? null">
    {{ $slot }}
</x-layouts::auth.split>
