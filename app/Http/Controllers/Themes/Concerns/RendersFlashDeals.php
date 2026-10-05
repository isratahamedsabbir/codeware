<?php

namespace App\Http\Controllers\Themes\Concerns;

use App\Models\FlashDeal;

/**
 * The flash-deals page — every deal that is live right now with its products at
 * the deal price. Lives on a theme's own controller like the other Renders*
 * concerns; what the page looks like is that theme's flash-deals.blade.php.
 */
trait RendersFlashDeals
{
    public function flashDeals()
    {
        $deals = FlashDeal::live()
            ->with(['products' => fn ($q) => $q->active()
                ->with(['categories.page', 'brand', 'tags', 'page', 'flashDeals'])
                ->withSoldQuantity()
                ->orderBy('sort_order')])
            ->orderBy('ends_at')
            ->get()
            ->filter(fn (FlashDeal $deal) => $deal->products->isNotEmpty())
            ->values();

        return $this->view('flash-deals', [
            'deals' => $deals,
            'title' => __('Flash Deals'),
            'currentSlug' => 'flash-deals',
        ]);
    }
}
