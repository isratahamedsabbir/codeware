<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class AdminMenuSeeder extends Seeder
{
    /**
     * Reproduces the admin sidebar's previous hardcoded structure 1:1, so existing
     * translations (Settings, Pages, Posts, Media Library, ...) and tests asserting
     * breadcrumb text ("Access Control" > "Users") keep working unchanged.
     */
    public function run(): void
    {
        MenuItem::query()->where('group', MenuItem::GROUP_ADMIN_SIDEBAR)->delete();

        $this->standalone('Dashboard', 'home', 'admin.dashboard', 1);

        $this->standalone('Reports', 'chart-bar', 'admin.reports', 2);

        $this->standalone('Chat', 'chat-bubble-left-right', 'admin.chat', 3);

        // Categories and Tags are shared by Products and Blog (pick a type
        // when creating), so they live on their own rather than nested
        // under either — each has its own feature toggle, see routes/admin.php's
        // feature:categories and feature:tags groups.
        $this->standalone('Categories', 'squares-2x2', 'admin.categories', 4);

        $this->standalone('Tags', 'tag', 'admin.tags', 5);

        $this->standalone('Brands', 'building-storefront', 'admin.product-brands', 6);

        // Posts, Products and Services are listed one after another, right after Brands.
        $this->standalone('Posts', 'document-text', 'admin.posts', 7);

        $this->group('Products', 8, [
            ['Products', 'cube', 'admin.products'],
            ['Attributes', 'adjustments-horizontal', 'admin.product-attributes'],
        ]);

        $this->standalone('Services', 'wrench-screwdriver', 'admin.services', 9);

        // Advertisements — banners shown on the storefront (product page aside).
        $this->standalone('Advertisements', 'megaphone', 'admin.advertisements', 10);

        $this->standalone('Orders', 'shopping-bag', 'admin.orders', 11);

        // Vouchers get their own group — the voucher product list with the
        // record of vouchers actually sold.
        $this->group('Vouchers', 12, [
            ['Gift Vouchers', 'gift', 'admin.vouchers'],
            ['Voucher Sales', 'banknotes', 'admin.voucher-purchases'],
        ]);

        $this->standalone('Coupons', 'ticket', 'admin.coupons', 13);

        $this->standalone('Discounts', 'receipt-percent', 'admin.discounts', 14);

        $this->group('Library & System', 15, [
            ['Settings', 'cog-6-tooth', 'admin.settings'],
            ['Theme Settings', 'swatch', 'admin.theme-settings'],
            ['Developer Tools', 'command-line', 'admin.env'],
            ['Global SEO', 'magnifying-glass', 'admin.seo'],
            ['Social Links', 'share', 'admin.social'],
            ['Payment Gateways', 'credit-card', 'admin.payment-gateways'],
            ['Features', 'adjustments-horizontal', 'admin.features'],
            ['Media Library', 'photo', 'admin.media-library'],
            ['File Manager', 'folder', 'admin.file-manager'],
            ['Email Templates', 'envelope', 'admin.email-templates'],
            ['Audit Log', 'clock', 'admin.history'],
            ['Menu', 'bars-3', 'admin.menu'],
        ]);

        $this->standalone('Contacts', 'inbox', 'admin.contacts', 16);

        $this->standalone('Comments', 'chat-bubble-left-right', 'admin.comments', 17);

        $this->standalone('Reviews', 'star', 'admin.reviews', 18);

        $this->standalone('Subscribers', 'envelope-open', 'admin.subscribers', 19);

        $this->standalone('Pages', 'document', 'admin.pages', 20);

        $this->group('Localization', 21, [
            ['Languages', 'language', 'admin.languages'],
            ['Translations', 'chat-bubble-left-right', 'admin.translations'],
        ]);

        $this->group('Access Control', 22, [
            ['Roles', 'shield-check', 'admin.roles'],
            ['Permissions', 'lock-closed', 'admin.permissions'],
            ['Users', 'users', 'admin.users'],
            ['Vendors', 'briefcase', 'admin.product-vendors'],
        ]);

        $this->group('Location', 23, [
            ['Countries', 'flag', 'admin.countries'],
            ['Divisions', 'map', 'admin.divisions'],
            ['Districts (Zilla)', 'building-library', 'admin.districts'],
            ['Upazilas', 'map-pin', 'admin.upazilas'],
            ['Shipping', 'truck', 'admin.shipping-methods'],
        ]);

        $this->group('Advance', 24, [
            ['Sitemap', 'map', 'admin.advance.sitemap'],
            ['Robots.txt', 'globe-alt', 'admin.advance.robots'],
            ['Backup', 'archive-box', 'admin.advance.backup'],
            ['Password Generator', 'key', 'admin.advance.password-generator'],
        ]);

        $this->standalone('About', 'building-office', 'admin.about', 25);
    }

    protected function standalone(string $label, string $icon, string $routeName, int $sortOrder): void
    {
        MenuItem::create([
            'group' => MenuItem::GROUP_ADMIN_SIDEBAR,
            'label' => $label,
            'icon' => $icon,
            'route_name' => $routeName,
            'sort_order' => $sortOrder,
        ]);
    }

    /**
     * @param  array<int, array{0: string, 1: string, 2: string}>  $children
     */
    protected function group(string $label, int $sortOrder, array $children): void
    {
        $group = MenuItem::create([
            'group' => MenuItem::GROUP_ADMIN_SIDEBAR,
            'is_group' => true,
            'label' => $label,
            'sort_order' => $sortOrder,
        ]);

        foreach ($children as $index => [$childLabel, $icon, $routeName]) {
            MenuItem::create([
                'group' => MenuItem::GROUP_ADMIN_SIDEBAR,
                'parent_id' => $group->id,
                'label' => $childLabel,
                'icon' => $icon,
                'route_name' => $routeName,
                'sort_order' => $index + 1,
            ]);
        }
    }
}
