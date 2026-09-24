<?php

namespace App\Livewire\Frontend;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The product page's Reviews section: the approved reviews with a rating
 * summary, plus a review form for customers who actually bought the product
 * (Product::wasPurchasedBy). One review per customer per product; new reviews
 * start `pending` and appear once approved in Admin → Reviews.
 */
class ProductReviews extends Component
{
    /** How many reviews are listed before "Show more". */
    private const PAGE = 5;

    #[Locked]
    public int $productId;

    public int $rating = 0;

    public string $title = '';

    public string $body = '';

    public int $visible = self::PAGE;

    public function mount(int $productId): void
    {
        $this->productId = $productId;
    }

    #[Computed]
    public function product(): Product
    {
        return Product::findOrFail($this->productId);
    }

    /** This customer's own review of the product, in any status. */
    #[Computed]
    public function myReview(): ?Review
    {
        return auth()->check()
            ? $this->product->reviews()->where('user_id', auth()->id())->latest()->first()
            : null;
    }

    #[Computed]
    public function hasPurchased(): bool
    {
        return auth()->check() && $this->product->wasPurchasedBy(auth()->user());
    }

    public function canReview(): bool
    {
        return $this->hasPurchased && ! $this->myReview;
    }

    public function submit(): void
    {
        abort_unless(auth()->check(), 403);

        if (! $this->canReview()) {
            $this->addError('body', __('Only customers who bought this product can review it, once.'));

            return;
        }

        $validated = $this->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'rating.between' => __('Please choose a star rating.'),
        ]);

        $this->product->reviews()->create([
            'user_id' => auth()->id(),
            'rating' => $validated['rating'],
            'title' => filled($validated['title']) ? $validated['title'] : null,
            'body' => $validated['body'],
            'status' => 'pending',
        ]);

        $this->reset('rating', 'title', 'body');
        unset($this->myReview);

        $this->dispatch('notify', message: __('Thanks! Your review will appear once it is approved.'), type: 'success');
    }

    public function showMore(): void
    {
        $this->visible += self::PAGE;
    }

    public function render()
    {
        $approved = $this->product->reviews()->approved();

        // Rating summary: 5 → 1 star counts over the approved reviews.
        $counts = (clone $approved)->selectRaw('rating, count(*) as total')->groupBy('rating')->pluck('total', 'rating');
        $total = (int) $counts->sum();

        $reviews = (clone $approved)->with('user')->latest()->limit($this->visible)->get();

        return view('livewire.frontend.product-reviews', [
            'reviews' => $reviews,
            'total' => $total,
            'average' => $total ? round($counts->map(fn ($n, $stars) => $n * $stars)->sum() / $total, 1) : null,
            'breakdown' => collect(range(5, 1))->mapWithKeys(fn ($stars) => [$stars => (int) ($counts[$stars] ?? 0)]),
            'verifiedBuyers' => $this->verifiedBuyers($reviews),
        ]);
    }

    /**
     * Ids of the listed reviewers who bought the product (by account), for the
     * "Verified purchase" badge — one query for the whole page of reviews.
     *
     * @return Collection<int, int>
     */
    private function verifiedBuyers(Collection $reviews): Collection
    {
        $userIds = $reviews->pluck('user_id')->filter()->unique();

        if ($userIds->isEmpty()) {
            return collect();
        }

        return OrderItem::where('product_id', $this->productId)
            ->whereHas('order', fn ($q) => $q->whereIn('user_id', $userIds)->where('status', '!=', 'cancelled'))
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->pluck('orders.user_id')
            ->unique()
            ->values();
    }
}
