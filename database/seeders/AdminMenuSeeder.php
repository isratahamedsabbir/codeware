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
            ['Product Categories', 'squares-2x2', 'admin.product-categories'],
            ['Products', 'cube', 'admin.products'],
        ]);

        $this->group('Sales', 4, [
            ['Orders', 'shopping-bag', 'admin.orders'],
            ['Coupons', 'ticket', 'admin.coupons'],
            ['Reports', 'chart-bar', 'admin.reports'],
            ['Payment Gateways', 'credit-card', 'admin.payment-gateways'],
        ]);

        $this->group('Blog', 5, [
            ['Post Categories', 'squares-2x2', 'admin.post-categories'],
            ['Tags', 'tag', 'admin.tags'],
            ['Posts', 'document-text', 'admin.posts'],
        ]);

        $this->group('Library & System', 6, [
            ['Settings', 'cog-6-tooth', 'admin.settings'],
            ['SEO', 'magnifying-glass', 'admin.seo'],
            ['Social Links', 'share', 'admin.social'],
            ['Features', 'adjustments-horizontal', 'admin.features'],
            ['Media Library', 'photo', 'admin.media-library'],
            ['File Manager', 'folder', 'admin.file-manager'],
            ['Email Templates', 'envelope', 'admin.email-templates'],
            ['Admin History', 'clock', 'admin.history'],
            ['Menu', 'bars-3', 'admin.menu'],
        ]);

        $this->standalone('Contacts', 'inbox', 'admin.contacts', 7);

        $this->standalone('Pages', 'document', 'admin.pages', 8);

        $this->group('Localization', 9, [
            ['Languages', 'language', 'admin.languages'],
            ['Translations', 'chat-bubble-left-right', 'admin.translations'],
        ]);

        $this->group('Access Control', 10, [
            ['Roles', 'shield-check', 'admin.roles'],
            ['Permissions', 'lock-closed', 'admin.permissions'],
            ['Users', 'users', 'admin.users'],
        ]);

        $this->group('Advance', 11, [
            ['Sitemap', 'map', 'admin.advance.sitemap'],
            ['Robots.txt', 'globe-alt', 'admin.advance.robots'],
            ['Database', 'circle-stack', 'admin.advance.database'],
            ['Backup', 'archive-box', 'admin.advance.backup'],
        ]);
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
