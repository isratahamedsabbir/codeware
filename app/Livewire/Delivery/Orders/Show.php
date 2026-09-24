<?php

namespace App\Livewire\Delivery\Orders;

use App\Mail\DeliveryOtpMail;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Throwable;

/**
 * One assigned order, plus the delivery handoff: the rider sends a 6-digit
 * code to the customer's email, the customer reads it out at the door, and
 * only a matching code marks the order delivered — so a rider can't close
 * an order without actually reaching the customer.
 */
class Show extends Component
{
    public const OTP_TTL_MINUTES = 10;

    public const RESEND_COOLDOWN_SECONDS = 60;

    public const MAX_ATTEMPTS = 5;

    public int $orderId;

    public string $otp = '';

    public bool $otpSent = false;

    /**
     * 404s (not 403s) when the order isn't assigned to this rider —
     * confirming another rider's order exists would leak information.
     */
    public function mount(int $orderId): void
    {
        $order = $this->findOrder($orderId);

        $this->orderId = $order->id;
        $this->otpSent = Cache::has($this->otpKey());
    }

    public function sendOtp(): void
    {
        $order = $this->findOrder($this->orderId);

        if (! $order->isAwaitingDelivery()) {
            $this->addError('otp', 'This order is already '.$order->status.'.');

            return;
        }

        if (Cache::has($this->cooldownKey())) {
            $this->addError('otp', 'Please wait a minute before sending another code.');

            return;
        }

        $code = (string) random_int(100000, 999999);

        try {
            Mail::to($order->customer_email)->send(new DeliveryOtpMail($order, $code));
        } catch (Throwable $e) {
            Log::warning('Delivery OTP email failed', ['order' => $order->order_number, 'error' => $e->getMessage()]);
            $this->addError('otp', 'Could not send the code to the customer. Please try again.');

            return;
        }

        // Hashed, so a leaked cache row alone can't be used to close an order.
        Cache::put($this->otpKey(), Hash::make($code), now()->addMinutes(self::OTP_TTL_MINUTES));
        Cache::put($this->cooldownKey(), true, now()->addSeconds(self::RESEND_COOLDOWN_SECONDS));
        Cache::forget($this->attemptsKey());

        $this->otp = '';
        $this->otpSent = true;
        $this->resetErrorBag();

        $this->dispatch('notify', message: 'Code sent to '.$this->maskEmail($order->customer_email));
    }

    public function confirmDelivery(): void
    {
        $this->validate(['otp' => ['required', 'digits:6']]);

        $order = $this->findOrder($this->orderId);

        if (! $order->isAwaitingDelivery()) {
            $this->addError('otp', 'This order is already '.$order->status.'.');

            return;
        }

        $hash = Cache::get($this->otpKey());

        if (! $hash) {
            $this->otpSent = false;
            $this->addError('otp', 'The code has expired. Send a new one.');

            return;
        }

        if (! Hash::check($this->otp, $hash)) {
            Cache::add($this->attemptsKey(), 0, now()->addMinutes(self::OTP_TTL_MINUTES));
            $attempts = Cache::increment($this->attemptsKey());

            // Too many wrong guesses burns the code — a new one must be sent.
            if ($attempts >= self::MAX_ATTEMPTS) {
                Cache::forget($this->otpKey());
                Cache::forget($this->attemptsKey());
                $this->otpSent = false;
                $this->addError('otp', 'Too many wrong attempts. Send a new code.');

                return;
            }

            $this->addError('otp', 'That code is incorrect.');

            return;
        }

        $order->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);

        Cache::forget($this->otpKey());
        Cache::forget($this->attemptsKey());

        $this->otp = '';
        $this->otpSent = false;

        $this->dispatch('notify', message: "Order {$order->order_number} marked as delivered");
    }

    private function findOrder(int $orderId): Order
    {
        return Auth::user()->assignedDeliveries()->with('items')->findOrFail($orderId);
    }

    private function otpKey(): string
    {
        return "delivery-otp:{$this->orderId}";
    }

    private function cooldownKey(): string
    {
        return "delivery-otp-cooldown:{$this->orderId}";
    }

    private function attemptsKey(): string
    {
        return "delivery-otp-attempts:{$this->orderId}";
    }

    /** "jo***@example.com" — enough for the rider to confirm with the customer. */
    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return mb_substr($local, 0, 2).'***@'.$domain;
    }

    public function render()
    {
        $order = $this->findOrder($this->orderId);

        return view('livewire.delivery.orders.show', [
            'order' => $order,
        ])->layout('layouts.delivery', ['title' => "Order {$order->order_number}"]);
    }
}
