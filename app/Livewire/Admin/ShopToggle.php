<?php

namespace App\Livewire\Admin;

use App\Models\Setting;
use App\Support\AdminActivity;
use Livewire\Component;

class ShopToggle extends Component
{
    public bool $enabled = true;

    public function mount(): void
    {
        $this->enabled = (bool) Setting::get('shop_enabled', true);
    }

    /**
     * Flips the `shop_enabled` setting read by OrderController::store() to
     * reject new orders while off — global, same as every other Setting.
     */
    public function toggle(): void
    {
        $this->enabled = ! $this->enabled;

        Setting::set('shop_enabled', $this->enabled ? '1' : '0');

        AdminActivity::log('updated', $this->enabled ? 'Shop turned on' : 'Shop turned off');

        $this->dispatch(
            'notify',
            message: $this->enabled
                ? 'Shop is now on — customers can place orders.'
                : 'Shop is now off — customers cannot place new orders.'
        );
    }

    public function render()
    {
        return view('livewire.admin.shop-toggle');
    }
}
