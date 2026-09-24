<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The code a customer reads out to the delivery rider at the door — see
 * App\Livewire\Delivery\Orders\Show, which marks the order delivered only
 * once the rider enters it.
 */
class DeliveryOtpMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Order $order,
        public string $code,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Delivery code for order {$this->order->order_number}: {$this->code}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.delivery-otp',
            with: [
                'code' => $this->code,
                'name' => $this->order->customer_name,
                'orderNumber' => $this->order->order_number,
            ],
        );
    }
}
