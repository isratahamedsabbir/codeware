<?php

namespace App\Services\Export;

use App\Models\Post;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class PostCsvExporter extends CsvExporter
{
    /**
     * @param  list<int>  $ids
     * @return Collection<int, Post>
     */
    protected function records(array $ids, string $search, string $status): Collection
    {
        return Post::query()
            ->with('category')
            ->when($ids !== [], fn ($q) => $q->whereIn('id', $ids), fn ($q) => $q
                ->when($search, fn ($q) => $q->where(fn ($q) => $q
                    ->where('title->en', 'like', "%{$search}%")
                    ->orWhere('title->bn', 'like', "%{$search}%")
                    ->orWhereHas('page', fn ($p) => $p->where('slug', 'like', "%{$search}%"))))
                ->when($status, fn ($q) => $q->where('status', $status)))
            ->latest()
            ->get();
    }

    /**
     * @return list<string>
     */
    protected function headers(): array
    {
        return [
            'ID', 'Title (EN)', 'Title (BN)', 'Slug', 'Category', 'Status',
            'Published At', 'Created At', 'Description (EN)', 'Description (BN)',
        ];
    }

    /**
     * @return list<int|string|null>
     */
    protected function row(Model $record): array
    {
        /** @var Post $record */
        return [
            $record->id,
            $record->getTranslation('title', 'en', false),
            $record->getTranslation('title', 'bn', false),
            $record->slug,
            $record->category?->getTranslation('name', 'en', false),
            $record->status,
            $record->published_at?->toDateTimeString(),
            $record->created_at?->toDateTimeString(),
            $record->getTranslation('description', 'en', false),
            $record->getTranslation('description', 'bn', false),
        ];
    }

    protected function fileBaseName(): string
    {
        return 'posts';
    }
}
