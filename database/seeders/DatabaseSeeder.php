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
        $this->call(VendorSeeder::class);

        // The random factory users (and the named Test User) above are created
        // before the 'customer' role exists, so they get no role at all —
        // assign it now, the same default a real registration gets (see
        // CreateNewUser), so the admin's Users list doesn't show "None" for
        // every seeded account. Admin/Staff/Vendor already have their own
        // roles from AdminSeeder, so this only reaches the roleless ones.
        User::whereDoesntHave('roles')->get()->each(fn (User $user) => $user->assignRole('customer'));

        $this->call(SettingsSeeder::class);
        $this->call(SocialLinkSeeder::class);
        $this->call(PaymentGatewaySeeder::class);
        $this->call(FeatureSeeder::class);
        $this->call(LanguageSeeder::class);
        $this->call(BangladeshLocationSeeder::class);
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
