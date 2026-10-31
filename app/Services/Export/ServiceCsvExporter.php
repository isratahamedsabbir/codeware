<?php

namespace App\Services\Export;

use App\Models\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ServiceCsvExporter extends CsvExporter
{
    /**
     * @param  list<int>  $ids
     * @return Collection<int, Service>
     */
    protected function records(array $ids, string $search, string $status): Collection
    {
        return Service::query()
            ->when($ids !== [], fn ($q) => $q->whereIn('id', $ids), fn ($q) => $q
                ->when($search, fn ($q) => $q->where(fn ($q) => $q
                    ->where('name->en', 'like', "%{$search}%")
                    ->orWhere('name->bn', 'like', "%{$search}%")))
                ->when($status, fn ($q) => $q->where('status', $status)))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return list<string>
     */
    protected function headers(): array
    {
        return [
            'ID', 'Name (EN)', 'Name (BN)', 'Slug', 'Price', 'Status',
            'Featured Image', 'Sort Order', 'Created At',
            'Description (EN)', 'Description (BN)',
        ];
    }

    /**
     * @return list<int|float|string|null>
     */
    protected function row(Model $record): array
    {
        /** @var Service $record */
        return [
            $record->id,
            $record->getTranslation('name', 'en', false),
            $record->getTranslation('name', 'bn', false),
            $record->slug,
            $record->price,
            $record->status,
            $record->featured_image,
            $record->sort_order,
            $record->created_at?->toDateTimeString(),
            $record->getTranslation('description', 'en', false),
            $record->getTranslation('description', 'bn', false),
        ];
    }

    protected function fileBaseName(): string
    {
        return 'services';
    }
}
