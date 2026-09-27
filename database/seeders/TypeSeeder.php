<?php

namespace Database\Seeders;

use App\Models\Type;
use Illuminate\Database\Seeder;

/**
 * The two content pools every category, brand and tag is filed under. Created
 * before the taxonomy seeders so the demo content has a pool to land in, and
 * idempotent by slug so re-running it (or running it against an install that
 * already has the rows from the add_type_id migration) changes nothing.
 */
class TypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['slug' => Type::PRODUCT, 'name' => ['en' => 'Product', 'bn' => 'পণ্য'], 'sort_order' => 1],
            ['slug' => Type::POST, 'name' => ['en' => 'Post', 'bn' => 'পোস্ট'], 'sort_order' => 2],
        ];

        foreach ($types as $type) {
            $existing = Type::withTrashed()->where('slug', $type['slug'])->first();

            if ($existing) {
                // Only un-delete — a Type the admin renamed or re-ordered is
                // theirs, not this seeder's to reset on every run.
                if ($existing->trashed()) {
                    $existing->restore();
                }

                continue;
            }

            Type::create([...$type, 'status' => 'active']);
        }
    }
}
