<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory(20)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call(RolePermissionSeeder::class);
        $this->call(AdminSeeder::class);

        $this->call(SettingsSeeder::class);
        $this->call(SocialLinkSeeder::class);
        $this->call(PaymentGatewaySeeder::class);
        $this->call(FeatureSeeder::class);
        $this->call(LanguageSeeder::class);
        $this->call(AdminMenuSeeder::class);
        $this->call(ProductCategorySeeder::class);
        $this->call(PageSeeder::class);
        $this->call(FrontendMenuSeeder::class);
        $this->call(EmailTemplatesSeeder::class);
        $this->call(NotificationsSeeder::class);

        // Fake demo data (products, blog, orders) — not real content, safe for
        // local/dev seeding but skip this on a real deploy's first seed.
        $this->call(DemoContentSeeder::class);
        $this->call(OrderSeeder::class);
        $this->call(CouponSeeder::class);
    }
}
