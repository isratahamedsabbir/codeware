<?php

namespace App\Livewire\Delivery;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $assigned = Auth::user()->assignedDeliveries();

        return view('livewire.delivery.dashboard', [
            'toDeliverCount' => (clone $assigned)->whereNotIn('status', ['delivered', 'cancelled'])->count(),
            'deliveredTodayCount' => (clone $assigned)->where('status', 'delivered')->whereDate('delivered_at', today())->count(),
            'deliveredCount' => (clone $assigned)->where('status', 'delivered')->count(),
            'nextOrders' => (clone $assigned)
                ->whereNotIn('status', ['delivered', 'cancelled'])
                ->oldest()
                ->take(5)
                ->get(),
        ])->layout('layouts.delivery', ['title' => 'Dashboard', 'hideHeading' => true]);
    }
}
