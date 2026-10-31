<?php

namespace App\Services\Export;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ProductCsvExporter extends CsvExporter
{
    /**
     * @param  list<int>  $ids
     * @return Collection<int, Product>
     */
    protected function records(array $ids, string $search, string $status): Collection
    {
        return Product::query()
            ->with(['categories', 'page', 'brand', 'vendor'])
            ->when($ids !== [], fn ($q) => $q->whereIn('id', $ids), function ($q) use ($search, $status) {
                $q->when($search, fn ($q) => $q->where(fn ($q) => $q
                    ->where('name->en', 'like', "%{$search}%")
                    ->orWhere('name->bn', 'like', "%{$search}%")
                    ->orWhereHas('page', fn ($p) => $p->where('slug', 'like', "%{$search}%"))))
                    ->when($status, fn ($q) => $q->where('status', $status));
            })
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
            'ID', 'Name (EN)', 'Name (BN)', 'Slug', 'Category', 'Price',
            'Status', 'Featured', 'Sort Order', 'Created At', 'SKU', 'Quantity',
            'Discount Price', 'Product Type', 'Charge Shipping', 'Upcoming',
            'Brand', 'Vendor', 'Excerpt (EN)', 'Excerpt (BN)', 'Description (EN)', 'Description (BN)',
        ];
    }

    /**
     * @return list<int|float|string|null>
     */
    protected function row(Model $record): array
    {
        /** @var Product $record */
        return [
            $record->id,
            $record->getTranslation('name', 'en', false),
            $record->getTranslation('name', 'bn', false),
            $record->slug,
            $record->categories->map(fn ($c) => $c->getTranslation('name', 'en', false))->implode(', '),
            $record->price,
            $record->status,
            $record->is_featured ? 'Yes' : 'No',
            $record->sort_order,
            $record->created_at?->toDateTimeString(),
            $record->sku,
            $record->quantity,
            $record->discount_price,
            $record->product_type,
            $record->charge_shipping ? 'Yes' : 'No',
            $record->is_upcoming ? 'Yes' : 'No',
            $record->brand?->getTranslation('name', 'en', false),
            $record->vendor?->name,
            $record->getTranslation('excerpt', 'en', false),
            $record->getTranslation('excerpt', 'bn', false),
            $record->getTranslation('description', 'en', false),
            $record->getTranslation('description', 'bn', false),
        ];
    }

    protected function fileBaseName(): string
    {
        return 'products';
    }
}
