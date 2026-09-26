<x-layouts::auth.split :title="$title ?? null" :description="$description ?? null" :noindex="$noindex ?? null" :custom-code="$customCode ?? false" :seo-meta="$seoMeta ?? false">
    {{ $slot }}
</x-layouts::auth.split>
