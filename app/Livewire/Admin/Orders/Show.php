<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use App\Models\User;
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

    /** Assigned delivery rider's user id, '' for none — see assignDeliveryBoy(). */
    public string $deliveryBoyId = '';

    public function mount(int $id): void
    {
        $order = Order::findOrFail($id);

        $this->orderId = $order->id;
        $this->status = $order->status;
        $this->paymentStatus = $order->payment_status;
        $this->deliveryBoyId = (string) ($order->delivery_boy_id ?? '');
    }

    /**
     * Hands the order to a delivery rider (or unassigns it) — the rider then
     * sees it in the delivery portal and completes it with the customer's OTP.
     */
    public function assignDeliveryBoy(): void
    {
        $order = Order::findOrFail($this->orderId);

        if (in_array($order->status, ['delivered', 'cancelled'], true)) {
            $this->addError('deliveryBoyId', "This order is already {$order->status}.");

            return;
        }

        if ($this->deliveryBoyId !== '' && ! User::deliveryBoys()->whereKey($this->deliveryBoyId)->exists()) {
            $this->addError('deliveryBoyId', 'Pick a valid delivery boy.');

            return;
        }

        $order->update(['delivery_boy_id' => $this->deliveryBoyId !== '' ? (int) $this->deliveryBoyId : null]);

        AdminActivity::log('updated', "Order #{$order->order_number}: delivery boy ".($order->delivery_boy_id ? 'assigned' : 'unassigned'));

        $this->dispatch('notify', message: $order->delivery_boy_id ? 'Delivery boy assigned' : 'Delivery boy removed');
    }

    public function updateStatus(): void
    {
        $this->validate();

        $order = Order::findOrFail($this->orderId);

        if ($this->status === 'cancelled' && $order->status !== 'cancelled' && ! $order->canBeCancelled()) {
            $this->addError('status', "This order can no longer be cancelled — it has already reached \"{$order->status}\".");

            return;
        }

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
        $order = Order::with(['items.product', 'transactions', 'deliveryBoy'])->findOrFail($this->orderId);

        return view('livewire.admin.orders.show', [
            'order' => $order,
            'deliveryBoys' => User::deliveryBoys()->orderBy('name')->get(['id', 'name', 'email']),
        ])->layout('layouts.admin', ['title' => "Order {$order->order_number}"]);
    }
}
