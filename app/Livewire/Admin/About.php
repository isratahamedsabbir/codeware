<?php

namespace App\Livewire\Admin;

use Livewire\Component;

class About extends Component
{
    public function render()
    {
        return view('livewire.admin.about')->layout('layouts.admin', ['title' => 'About']);
    }
}
