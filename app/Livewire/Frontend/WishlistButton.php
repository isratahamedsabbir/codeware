<?php

namespace App\Livewire\Frontend;

use App\Support\Favorites;
use Livewire\Attributes\On;
use Livewire\Component;

class WishlistButton extends Component
{
    public int $productId;

    public bool $isFavorited = false;

    public function mount(int $productId): void
    {
        $this->productId = $productId;
        $this->isFavorited = Favorites::isFavorited($productId);
    }

    public function toggle(): void
    {
        $this->isFavorited = Favorites::toggle($this->productId);
        $this->dispatch('wishlist-updated');
    }

    #[On('wishlist-updated')]
    public function refreshState(): void
    {
        $this->isFavorited = Favorites::isFavorited($this->productId);
    }

    public function render()
    {
        return view('livewire.frontend.wishlist-button');
    }
}
