<?php

namespace App\Livewire\Frontend;

use App\Support\Favorites;
use Livewire\Attributes\On;
use Livewire\Component;

class WishlistCount extends Component
{
    public int $count = 0;

    public function mount(): void
    {
        $this->count = Favorites::count();
    }

    #[On('wishlist-updated')]
    public function refreshCount(): void
    {
        $this->count = Favorites::count();
    }

    public function render()
    {
        return view('livewire.frontend.wishlist-count');
    }
}
