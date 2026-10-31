<?php

namespace App\Services\Import;

use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Support\Slug;

/**
 * Turns the Posts screen's CSV back into posts.
 *
 * Columns, in the order the export writes them:
 *
 *   ID, Title (EN), Title (BN), Slug, Category, Status, Published At,
 *   Created At, Description (EN), Description (BN)
 *
 * Only Title (EN) is required for a new row. ID and Slug are used to find an
 * existing post, and are only overwritten when the "update existing" toggle is
 * on — see the import modal on the Posts screen.
 */
class PostCsvImporter extends CsvImporter
{
    protected function requiredColumn(): string
    {
        return 'title en';
    }

    protected function requiredColumnLabel(): string
    {
        return 'Title (EN)';
    }

    /**
     * @param  array<string, string>  $row
     * @return array{status: 'created'|'updated'|'skipped', warnings?: list<string>}
     */
    protected function importRow(array $row, bool $updateExisting, int $userId): array
    {
        $titleEn = $this->value($row, 'title en');

        if ($titleEn === '') {
            throw new \RuntimeException('Title (EN) is required.');
        }

        $existing = $this->findExisting($row);

        if ($existing && ! $updateExisting) {
            return ['status' => 'skipped'];
        }

        $creating = ! $existing;
        $warnings = [];

        $data = [
            'title' => $this->translations($row, 'title en', 'title bn') ?: ['en' => $titleEn],
        ];

        if ($this->hasAny($row, ['description en', 'description bn'])) {
            $data['description'] = $this->translations($row, 'description en', 'description bn') ?: null;
        }

        if ($this->has($row, 'category') && ! $this->empty($row, 'category')) {
            $data['category_id'] = $this->resolveCategory($this->value($row, 'category'), $warnings);
        }

        if (($status = $this->status($row, $creating)) !== null) {
            $data['status'] = $status;
        }

        if (! $this->empty($row, 'published at')) {
            $data['published_at'] = $this->date($row, 'published at');
        }

        if ($creating) {
            $data['user_id'] = $userId;
        }

        if ($existing) {
            $post = $existing;
            $post->update($data);
        } else {
            $post = Post::create($data);
        }

        // The status that's actually in effect after this row — used both to
        // stamp a first publish time and to keep the paired page in step.
        $status = $post->status;

        if ($status === 'active' && $post->published_at === null) {
            $post->update(['published_at' => now()]);
        }

        $slug = $this->desiredSlug($row, $post, $titleEn);

        Page::updateOrCreate(
            ['type' => 'post', 'post_id' => $post->id],
            [
                'user_id' => $userId,
                'title' => $post->title,
                'description' => $post->description,
                'slug' => $this->uniqueSlug($slug, $post->page?->id, 'post'),
                'status' => $post->status,
            ]
        );

        return [
            'status' => $creating ? 'created' : 'updated',
            'warnings' => $warnings,
        ];
    }

    /**
     * Finds the row's existing post by ID first, then by slug. ID wins because
     * it is the most precise identifier the export writes; slug is the fallback
     * for hand-made sheets that leave the ID blank.
     *
     * @param  array<string, string>  $row
     */
    protected function findExisting(array $row): ?Post
    {
        $id = (int) $this->value($row, 'id');

        if ($id > 0 && ($post = Post::find($id))) {
            return $post;
        }

        $slug = $this->value($row, 'slug');

        if ($slug !== '') {
            return Page::query()
                ->where('type', 'post')
                ->where('slug', Slug::lower($slug))
                ->first()
                ?->post;
        }

        return null;
    }

    /**
     * @param  list<string>  $warnings
     */
    protected function resolveCategory(string $name, array &$warnings): ?int
    {
        if ($name === '') {
            return null;
        }

        $category = PostCategory::query()
            ->where(fn ($query) => $query
                ->where('name->en', $name)
                ->orWhere('name->bn', $name))
            ->first();

        if (! $category) {
            $warnings[] = 'Category "'.$name.'" was not found; the post was imported without it.';

            return null;
        }

        return $category->id;
    }

    /**
     * @param  array<string, string>  $row
     */
    protected function desiredSlug(array $row, Post $post, string $titleEn): string
    {
        $slug = $this->value($row, 'slug');

        if ($slug !== '') {
            return $slug;
        }

        return $post->page?->slug ?? Slug::make($titleEn);
    }
}
