<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('is_admin', true)->first();

        $categories = [
            ['en' => 'Micronutrients',     'bn' => 'মাইক্রোনিউট্রিয়েন্ট', 'sort_order' => 1],
            ['en' => 'Fertilizers',        'bn' => 'সার', 'sort_order' => 2],
            ['en' => 'Seeds',              'bn' => 'বীজ', 'sort_order' => 3],
            ['en' => 'Mulching Film',      'bn' => 'মালচিং ফিল্ম', 'sort_order' => 4],
            ['en' => 'Agricultural Tools', 'bn' => 'কৃষি সরঞ্জাম', 'sort_order' => 5],
        ];

        foreach ($categories as $cat) {
            $slug = Str::slug($cat['en']);

            $category = ProductCategory::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => ['en' => $cat['en'], 'bn' => $cat['bn']],
                    'sort_order' => $cat['sort_order'],
                ]
            );

            Page::updateOrCreate(
                ['type' => 'product_category', 'category_id' => $category->id],
                [
                    'user_id' => $admin?->id,
                    'title' => ['en' => $cat['en'], 'bn' => $cat['bn']],
                    'slug' => $slug,
                    'status' => 'active',
                    'sort_order' => $cat['sort_order'],
                ]
            );
        }
    }
}
