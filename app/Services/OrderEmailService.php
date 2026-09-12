<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use App\Support\PaymentMethods;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Throwable;

/**
 * Sends the two order-placement emails (customer confirmation, admin
 * notification) via the template-driven mail system — see
 * EmailTemplateService and the 'order_confirmation'/'order_admin_notification'
 * templates seeded by EmailTemplatesSeeder. Used both for the automatic send
 * on order creation (Order::booted()) and for a manual resend from the admin
 * Orders list (Livewire\Admin\Orders\Index::resendEmail()).
 */
class OrderEmailService
{
    public function __construct(
        private readonly EmailTemplateService $templates,
    ) {}

    public function sendCustomerConfirmation(Order $order): bool
    {
        return $this->attempt(
            'order_confirmation',
            $order->customer_email,
            $this->customerVariables($order),
            'customer',
            $order,
        );
    }

    public function sendAdminNotification(Order $order): bool
    {
        $recipient = Setting::get('order_email') ?: Setting::get('contact_email');

        if (! $recipient) {
            return false;
        }

        return $this->attempt(
            'order_admin_notification',
            $recipient,
            $this->adminVariables($order),
            'admin',
            $order,
        );
    }

    /**
     * A transport failure (bad SMTP config, network blip, ...) must never
     * take the rest of order creation down with it — that's exactly the
     * "sometimes it doesn't send" scenario the manual resend action exists
     * for, so a failure here is swallowed and logged rather than thrown.
     */
    private function attempt(string $key, string $recipient, array $variables, string $audience, Order $order): bool
    {
        try {
            return $this->templates->send($key, $recipient, $variables);
        } catch (Throwable $e) {
            Log::error("Failed to send order {$audience} email", [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'recipient' => $recipient,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @return array<string, string>
     */
    private function customerVariables(Order $order): array
    {
        return [
            'customer_name' => $order->customer_name,
            'order_id' => $order->order_number,
            'order_date' => $order->created_at?->toDisplay() ?? '',
            'payment_method' => PaymentMethods::label($order->payment_method),
            'order_total' => $order->currency.' '.number_format((float) $order->total, 2),
            'account_url' => URL::signedRoute('invoices.public.show', ['order' => $order->order_number]),
            'site_name' => Setting::get('site_name', 'Codeware'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function adminVariables(Order $order): array
    {
        return [
            'order_id' => $order->order_number,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'customer_email' => $order->customer_email,
            'payment_method' => PaymentMethods::label($order->payment_method),
            'order_total' => $order->currency.' '.number_format((float) $order->total, 2),
            'order_date' => $order->created_at?->toDisplay() ?? '',
        ];
    }
}
