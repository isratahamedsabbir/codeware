<?php

namespace App\Livewire\Delivery;

use App\Livewire\Vendor\Profile as VendorProfile;
use App\Models\UserDocument;

/**
 * The rider's own profile — identical to the vendor portal's (name, photo,
 * signature, documents, password; nothing vendor-specific in it), just
 * rendered inside the delivery portal's layout.
 */
class Profile extends VendorProfile
{
    public function render()
    {
        return view('livewire.vendor.profile', [
            'user' => auth()->user(),
            'documents' => UserDocument::where('user_id', auth()->id())->latest()->get(),
        ])->layout('layouts.delivery', ['title' => 'My Profile']);
    }
}
