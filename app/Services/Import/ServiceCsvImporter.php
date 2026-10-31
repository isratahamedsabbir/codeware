<?php

namespace App\Services\Import;

use App\Models\Service;
use App\Support\Slug;
use RuntimeException;

/**
 * Turns the Services screen's CSV back into services.
 *
 * Columns, in the order the export writes them:
 *
 *   ID, Name (EN), Name (BN), Slug, Price, Status, Featured Image,
 *   Sort Order, Created At, Description (EN), Description (BN)
 *
 * Only Name (EN) is required for a new row. Existing services are found by ID,
 * then slug, and are only overwritten when the "update existing" toggle is on
 * — see the import modal on the Services screen.
 */
class ServiceCsvImporter extends CsvImporter
{
    protected function requiredColumn(): string
    {
        return 'name en';
    }

    protected function requiredColumnLabel(): string
    {
        return 'Name (EN)';
    }

    /**
     * @param  array<string, string>  $row
     * @return array{status: 'created'|'updated'|'skipped'}
     */
    protected function importRow(array $row, bool $updateExisting, int $userId): array
    {
        $nameEn = $this->value($row, 'name en');

        if ($nameEn === '') {
            throw new RuntimeException('Name (EN) is required.');
        }

        $existing = $this->findExisting($row);

        if ($existing && ! $updateExisting) {
            return ['status' => 'skipped'];
        }

        $creating = ! $existing;

        $data = [
            'name' => $this->translations($row, 'name en', 'name bn') ?: ['en' => $nameEn],
        ];

        if ($this->has($row, 'price') && ! $this->empty($row, 'price')) {
            $data['price'] = $this->decimal($row, 'price');
        } elseif ($creating) {
            $data['price'] = 0;
        }

        if ($this->has($row, 'sort order') && ! $this->empty($row, 'sort order')) {
            $data['sort_order'] = $this->integer($row, 'sort order');
        } elseif ($creating) {
            $data['sort_order'] = $this->nextSortOrder();
        }

        if ($this->has($row, 'featured image') && ! $this->empty($row, 'featured image')) {
            $data['featured_image'] = $this->value($row, 'featured image');
        }

        if ($this->hasAny($row, ['description en', 'description bn'])) {
            $data['description'] = $this->translations($row, 'description en', 'description bn') ?: null;
        }

        if (($status = $this->status($row, $creating)) !== null) {
            $data['status'] = $status;
        }

        $data['slug'] = $this->uniqueServiceSlug(
            $this->desiredSlug($row, $existing, $nameEn),
            $existing?->id,
        );

        if ($creating) {
            $data['created_by'] = $userId;
        }

        if ($existing) {
            $existing->update($data);

            return ['status' => 'updated'];
        }

        Service::create($data);

        return ['status' => 'created'];
    }

    /**
     * Finds the row's existing service by ID first, then by slug.
     *
     * @param  array<string, string>  $row
     */
    protected function findExisting(array $row): ?Service
    {
        $id = (int) $this->value($row, 'id');

        if ($id > 0 && ($service = Service::find($id))) {
            return $service;
        }

        $slug = $this->value($row, 'slug');

        if ($slug !== '') {
            return Service::query()->where('slug', Slug::lower($slug))->first();
        }

        return null;
    }

    /**
     * @param  array<string, string>  $row
     */
    protected function desiredSlug(array $row, ?Service $existing, string $nameEn): string
    {
        $slug = $this->value($row, 'slug');

        if ($slug !== '') {
            return $slug;
        }

        return $existing?->slug ?? $nameEn;
    }

    /**
     * Slugs are unique on `services` itself (unlike posts/products, whose slugs
     * live on their paired page), so append -2, -3... until one is free.
     */
    protected function uniqueServiceSlug(string $desired, ?int $ignoreId = null): string
    {
        $base = Slug::make($desired);

        if ($base === '') {
            // A name with no Latin characters (e.g. Bengali-only) slugifies to
            // nothing; keep the fallback rather than writing an empty slug.
            $base = 'service';
        }

        $slug = $base;
        $suffix = 2;

        while (Service::withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    protected function decimal(array $row, string $key): float
    {
        $value = $this->value($row, $key);

        if (! is_numeric($value)) {
            throw new RuntimeException('"'.$key.'" must be a number: '.$value);
        }

        return (float) $value;
    }

    protected function integer(array $row, string $key, int $default = 0): int
    {
        $value = $this->value($row, $key);

        if ($value === '') {
            return $default;
        }

        if (! is_numeric($value)) {
            throw new RuntimeException('"'.$key.'" must be a whole number: '.$value);
        }

        return max(0, (int) $value);
    }

    /**
     * Imported services with no order value append to the end of the list,
     * just as one created from the form would.
     */
    protected function nextSortOrder(): int
    {
        return ((int) Service::max('sort_order')) + 1;
    }
}
