<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use App\Support\AdminActivity;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Show extends Component
{
    public int $orderId;

    #[Validate('required|in:pending,processing,shipped,delivered,cancelled')]
    public string $status = 'pending';

    #[Validate('required|in:pending,paid,failed,refunded')]
    public string $paymentStatus = 'pending';

    public function mount(int $id): void
    {
        $order = Order::findOrFail($id);

        $this->orderId = $order->id;
        $this->status = $order->status;
        $this->paymentStatus = $order->payment_status;
    }

    public function updateStatus(): void
    {
        $this->validate();

        $order = Order::findOrFail($this->orderId);
        $order->update([
            'status' => $this->status,
            'payment_status' => $this->paymentStatus,
        ]);

        // Payment marked paid outside a real gateway callback (COD collected on
        // delivery, or a structural-placeholder gateway confirmed manually) — log
        // it against the order's transaction so the reporting numbers stay
        // consistent with what's actually on the order.
        if ($this->paymentStatus === 'paid') {
            $order->transactions()->latest()->first()?->update([
                'status' => 'success',
                'paid_at' => now(),
            ]);
        }

        AdminActivity::log('updated', "Order #{$order->order_number}: status updated");

        $this->dispatch('notify', message: 'Order updated successfully');
    }

    public function render()
    {
        $order = Order::with(['items.product', 'transactions'])->findOrFail($this->orderId);

        return view('livewire.admin.orders.show', [
            'order' => $order,
        ])->layout('layouts.admin', ['title' => "Order {$order->order_number}"]);
    }
}
