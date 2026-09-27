<?php

namespace Database\Seeders;

use App\Models\ProductBrand;
use App\Models\Type;
use Illuminate\Database\Seeder;

/**
 * Dummy brands for local/dev seeding. Brands live in the shared taxonomy
 * (`categories`) table as `kind = brand` rows — see the ProductBrand model.
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

        $typeId = Type::idFor(Type::PRODUCT);

        $existing = ProductBrand::where('type_id', $typeId)
            ->get()
            ->map(fn (ProductBrand $brand) => $brand->getTranslation('name', 'en', false))
            ->all();

        foreach ($brands as $i => $names) {
            if (in_array($names['en'], $existing, true)) {
                continue;
            }

            ProductBrand::create([
                'type_id' => $typeId,
                'name' => $names,
                'logo' => null,
                'status' => 'active',
                'sort_order' => $i + 1,
            ]);
        }
    }
}
