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

        $this->standalone('Overview', 'home', 'admin.dashboard', 1);

        $this->standalone('Chat', 'chat-bubble-left-right', 'admin.chat', 2);

        $this->group('Products', 3, [
            ['Products', 'cube', 'admin.products'],
            ['Attributes', 'adjustments-horizontal', 'admin.product-attributes'],
            ['Vendors', 'briefcase', 'admin.product-vendors'],
        ]);

        // Categories and Tags are shared by Products and Blog (pick a type
        // when creating), so they live on their own rather than nested
        // under either — see routes/admin.php's feature:taxonomy group.
        $this->standalone('Categories', 'squares-2x2', 'admin.categories', 4);

        $this->standalone('Tags', 'tag', 'admin.tags', 5);

        $this->standalone('Brands', 'star', 'admin.product-brands', 6);

        $this->standalone('Services', 'wrench-screwdriver', 'admin.services', 7);

        $this->group('Sales', 8, [
            ['Orders', 'shopping-bag', 'admin.orders'],
            ['Coupons', 'ticket', 'admin.coupons'],
            ['Shipping', 'truck', 'admin.shipping-methods'],
            ['Reports', 'chart-bar', 'admin.reports'],
        ]);

        $this->standalone('Posts', 'document-text', 'admin.posts', 9);

        $this->group('Library & System', 10, [
            ['Settings', 'cog-6-tooth', 'admin.settings'],
            ['Env', 'command-line', 'admin.env'],
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

        $this->standalone('Contacts', 'inbox', 'admin.contacts', 11);

        $this->standalone('Comments', 'chat-bubble-left-right', 'admin.comments', 12);

        $this->standalone('Reviews', 'star', 'admin.reviews', 13);

        $this->standalone('Subscribers', 'envelope-open', 'admin.subscribers', 14);

        $this->standalone('Pages', 'document', 'admin.pages', 15);

        $this->group('Localization', 16, [
            ['Languages', 'language', 'admin.languages'],
            ['Translations', 'chat-bubble-left-right', 'admin.translations'],
        ]);

        $this->group('Access Control', 17, [
            ['Roles', 'shield-check', 'admin.roles'],
            ['Permissions', 'lock-closed', 'admin.permissions'],
            ['Users', 'users', 'admin.users'],
        ]);

        $this->group('Location', 18, [
            ['Countries', 'flag', 'admin.countries'],
            ['Divisions', 'map', 'admin.divisions'],
            ['Districts (Zilla)', 'building-library', 'admin.districts'],
            ['Upazilas', 'map-pin', 'admin.upazilas'],
        ]);

        $this->group('Advance', 19, [
            ['Sitemap', 'map', 'admin.advance.sitemap'],
            ['Robots.txt', 'globe-alt', 'admin.advance.robots'],
            ['Database', 'circle-stack', 'admin.advance.database'],
            ['Backup', 'archive-box', 'admin.advance.backup'],
            ['Password Generator', 'key', 'admin.advance.password-generator'],
        ]);

        $this->standalone('About', 'building-office', 'admin.about', 20);
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
