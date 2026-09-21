<?php

namespace Database\Seeders;

use App\Models\ProductBrand;
use Illuminate\Database\Seeder;

/**
 * Dummy brands for local/dev seeding. Brands live in the shared taxonomy
 * (`categories`) table as `product_brand` rows — see the ProductBrand model.
 * Real installs should replace these (or skip this seeder) with actual brands.
 */
class ProductBrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            ['en' => 'Samsung',  'bn' => 'স্যামসাং'],
            ['en' => 'Apple',    'bn' => 'অ্যাপল'],
            ['en' => 'Xiaomi',   'bn' => 'শাওমি'],
            ['en' => 'Huawei',   'bn' => 'হুয়াওয়ে'],
            ['en' => 'OnePlus',  'bn' => 'ওয়ানপ্লাস'],
            ['en' => 'Sony',     'bn' => 'সনি'],
            ['en' => 'LG',       'bn' => 'এলজি'],
            ['en' => 'Asus',     'bn' => 'আসুস'],
            ['en' => 'Dell',     'bn' => 'ডেল'],
            ['en' => 'HP',       'bn' => 'এইচপি'],
        ];

        $existing = ProductBrand::where('type', ProductBrand::TYPE_PRODUCT)
            ->get()
            ->map(fn (ProductBrand $brand) => $brand->getTranslation('name', 'en', false))
            ->all();

        foreach ($brands as $i => $names) {
            if (in_array($names['en'], $existing, true)) {
                continue;
            }

            ProductBrand::create([
                'type' => ProductBrand::TYPE_PRODUCT,
                'name' => $names,
                'logo' => null,
                'status' => 'active',
                'sort_order' => $i + 1,
            ]);
        }
    }
}
