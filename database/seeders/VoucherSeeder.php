<?php

namespace Database\Seeders;

use App\Models\Voucher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class VoucherSeeder extends Seeder
{
    /**
     * A few showcase gift voucher products — real example pricing, safe to seed
     * on a fresh install (unlike DemoContentSeeder's random data).
     */
    public function run(): void
    {
        $vouchers = [
            [
                'name' => ['en' => 'Gift Voucher ৳500', 'bn' => 'গিফট ভাউচার ৳৫০০'],
                'description' => ['en' => 'A ৳500 gift voucher — the perfect last-minute gift.', 'bn' => ''],
                'price' => 500,
                'value' => 500,
                'valid_days' => 365,
                'sort_order' => 1,
            ],
            [
                'name' => ['en' => 'Gift Voucher ৳1000', 'bn' => 'গিফট ভাউচার ৳১০০০'],
                'description' => ['en' => 'A ৳1000 gift voucher for something a little special.', 'bn' => ''],
                'price' => 1000,
                'value' => 1000,
                'valid_days' => 365,
                'sort_order' => 2,
            ],
            [
                'name' => ['en' => 'Gift Voucher ৳2000', 'bn' => 'গিফট ভাউচার ৳২০০০'],
                'description' => ['en' => 'A ৳2000 gift voucher — save ৳100 on the face value.', 'bn' => ''],
                'price' => 1900,
                'value' => 2000,
                'valid_days' => 365,
                'sort_order' => 3,
            ],
        ];

        foreach ($vouchers as $voucher) {
            Voucher::updateOrCreate(
                ['slug' => Str::slug($voucher['name']['en'])],
                $voucher + ['currency' => 'BDT', 'status' => 'active'],
            );
        }
    }
}
