<?php

namespace App\Livewire\Frontend;

use App\Models\Setting;
use Livewire\Component;

/**
 * One-time announcement popup for visitors (all ecommerce theme pages via the
 * footer partial). The overlay itself is Alpine-driven: on first load it shows
 * unless the visitor already dismissed it (browser localStorage), and closing it
 * writes that flag so it never reappears in that browser. The whole overlay is
 * skipped server-side when the admin switches it off from
 * Theme Settings → Popup ("Show Announcement Popup").
 */
class AnnouncePopup extends Component
{
    public function render()
    {
        if (! (bool) Setting::get('popup_enabled', false)) {
            return '<div></div>';
        }

        return view('livewire.frontend.announce-popup', [
            'image' => Setting::get('popup_image'),
            'title' => Setting::get('popup_title', ''),
            'description' => Setting::get('popup_description', ''),
            'buttonLabel' => Setting::get('popup_button_label', ''),
            'buttonUrl' => Setting::get('popup_button_url', ''),
        ]);
    }
}
