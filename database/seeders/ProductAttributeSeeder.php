<?php

namespace Database\Seeders;

use App\Models\ProductAttribute;
use Illuminate\Database\Seeder;

/**
 * Dummy product attributes (and their predefined values) for local/dev seeding.
 * The product form only lets admins pick these values when building variations,
 * so each attribute carries the values that make sense for it — see
 * Admin\ProductAttributes\Form. Real installs should replace these (or skip
 * this seeder) with actual attributes.
 */
class ProductAttributeSeeder extends Seeder
{
    public function run(): void
    {
        $attributes = [
            ['name' => 'Color',    'values' => ['Red', 'Blue', 'Black', 'White', 'Silver', 'Gold']],
            ['name' => 'Size',     'values' => ['S', 'M', 'L', 'XL', 'XXL']],
            ['name' => 'Material', 'values' => ['Cotton', 'Polyester', 'Wool', 'Leather', 'Denim']],
            ['name' => 'Storage',  'values' => ['64GB', '128GB', '256GB', '512GB', '1TB']],
        ];

        foreach ($attributes as $attribute) {
            if (ProductAttribute::where('name', $attribute['name'])->exists()) {
                continue;
            }

            ProductAttribute::create($attribute);
        }
    }
}
