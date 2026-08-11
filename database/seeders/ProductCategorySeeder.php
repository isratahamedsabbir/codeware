<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['en' => 'Micronutrients',     'bn' => 'মাইক্রোনিউট্রিয়েন্ট', 'sort_order' => 1],
            ['en' => 'Fertilizers',        'bn' => 'সার', 'sort_order' => 2],
            ['en' => 'Seeds',              'bn' => 'বীজ', 'sort_order' => 3],
            ['en' => 'Mulching Film',      'bn' => 'মালচিং ফিল্ম', 'sort_order' => 4],
            ['en' => 'Agricultural Tools', 'bn' => 'কৃষি সরঞ্জাম', 'sort_order' => 5],
        ];

        foreach ($categories as $cat) {
            ProductCategory::firstOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($cat['en'])],
                [
                    'name'       => ['en' => $cat['en'], 'bn' => $cat['bn']],
                    'sort_order' => $cat['sort_order'],
                ]
            );
        }
    }
}
