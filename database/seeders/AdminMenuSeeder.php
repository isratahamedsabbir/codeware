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

        // "Accessories" holds everything that shapes a product rather than being
        // one: the taxonomy trio (Categories, Tags, Brands) that classifies it,
        // the Advertisements that decide where it gets seen, and the price levers
        // (Coupons, Discounts) that change what it costs. All six are supporting
        // vocabulary of the catalogue, not content types of their own, so they
        // read as one section instead of six loose top-level links. Each still has
        // its own feature toggle, see routes/admin.php's feature:categories,
        // feature:tags, feature:brands, feature:advertisements, feature:orders
        // and feature:discounts groups — turning one off empties just that link,
        // not the whole group.
        $this->group('Accessories', 4, [
            ['Categories', 'squares-2x2', 'admin.categories'],
            ['Tags', 'tag', 'admin.tags'],
            ['Brands', 'building-storefront', 'admin.product-brands'],
            ['Advertisements', 'megaphone', 'admin.advertisements'],
            ['Coupons', 'ticket', 'admin.coupons'],
            ['Discounts', 'receipt-percent', 'admin.discounts'],
        ]);

        // Posts, Products and Services are listed one after another, right after
        // the Accessories group.
        $this->standalone('Posts', 'document-text', 'admin.posts', 7);

        // Orders lives inside this group rather than beside it. An order is a
        // basket of these products - order_items point at products - so an
        // "Orders" link floating in the top level next to a "Products" group
        // splits one subject across two places in the sidebar.
        //
        // Attributes first, then the products themselves, then their orders:
        // the catalogue is defined before the things being sold, and the things
        // being sold before what came out of them.
        $this->group('Products', 8, [
            ['Attributes', 'adjustments-horizontal', 'admin.product-attributes'],
            ['Products', 'cube', 'admin.products'],
            ['Orders', 'shopping-bag', 'admin.orders'],
        ]);

        // One "Service" group, with the Bookings inbox the storefront form
        // generates. Nested rather than as two siblings because a booking only
        // exists because of a service - the inbox belongs under the thing that
        // produces it. Both sit behind the same feature:services toggle (see
        // routes/admin.php), so a site with services off gets neither.
        $this->group('Service', 9, [
            ['Services', 'wrench-screwdriver', 'admin.services'],
            ['Bookings', 'inbox', 'admin.bookings'],
        ]);

        // Vouchers get their own group — the voucher product list with the
        // record of vouchers actually sold.
        $this->group('Vouchers', 12, [
            ['Gift Vouchers', 'gift', 'admin.vouchers'],
            ['Voucher Sales', 'banknotes', 'admin.voucher-purchases'],
        ]);

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

        // The four inbound inboxes - contact form submissions, comments on
        // content, product reviews and newsletter signups - grouped as "Client
        // Queries" rather than sitting as four loose top-level links. All four
        // are somebody else talking to the site owner, not a content type of
        // their own, so they read as one section. Each still has its own feature
        // toggle, see routes/admin.php's feature:contacts, feature:comments,
        // feature:reviews and feature:newsletter groups - turning one off empties
        // just that link, and only once the last one is off does the whole group
        // drop (an empty group is never rendered).
        $this->group('Client Queries', 16, [
            ['Contacts', 'inbox', 'admin.contacts'],
            ['Comments', 'chat-bubble-left-right', 'admin.comments'],
            ['Reviews', 'star', 'admin.reviews'],
            ['Subscribers', 'envelope-open', 'admin.subscribers'],
        ]);

        $this->standalone('Pages', 'document', 'admin.pages', 20);

        // No Portfolio group: projects, experience, skills and testimonials are
        // edited on the Theme Settings screen, which is already in the sidebar
        // under Library & System. A second group of links to the same data in a
        // different place is how an owner ends up editing yesterday's version.

        $this->group('Localization', 22, [
            ['Languages', 'language', 'admin.languages'],
            ['Translations', 'chat-bubble-left-right', 'admin.translations'],
        ]);

        $this->group('Access Control', 23, [
            ['Roles', 'shield-check', 'admin.roles'],
            ['Permissions', 'lock-closed', 'admin.permissions'],
            ['Users', 'users', 'admin.users'],
            ['Vendors', 'briefcase', 'admin.product-vendors'],
        ]);

        $this->group('Location', 24, [
            ['Countries', 'flag', 'admin.countries'],
            ['Divisions', 'map', 'admin.divisions'],
            ['Districts (Zilla)', 'building-library', 'admin.districts'],
            ['Upazilas', 'map-pin', 'admin.upazilas'],
            ['Shipping', 'truck', 'admin.shipping-methods'],
        ]);

        $this->group('Advance', 25, [
            ['Sitemap', 'map', 'admin.advance.sitemap'],
            ['Robots.txt', 'globe-alt', 'admin.advance.robots'],
            ['Backup', 'archive-box', 'admin.advance.backup'],
            ['Password Generator', 'key', 'admin.advance.password-generator'],
        ]);

        $this->standalone('About', 'building-office', 'admin.about', 26);
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
