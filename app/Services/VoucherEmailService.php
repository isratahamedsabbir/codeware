<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\VoucherPurchase;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Throwable;

/**
 * Emails a bought voucher to the customer immediately after purchase, with the
 * designed PDF attached — see the 'voucher_purchase' template seeded by
 * EmailTemplatesSeeder. Called from Api\V1\VoucherController::store() and the
 * admin "Resend" action. Follows OrderEmailService's best-effort contract: a
 * mail transport failure is logged, never thrown, so it can't break a sale.
 */
class VoucherEmailService
{
    public function __construct(
        private readonly EmailTemplateService $templates,
        private readonly VoucherPdfService $pdf,
    ) {}

    public function sendVoucher(VoucherPurchase $purchase): bool
    {
        try {
            $attachment = Attachment::fromData(
                fn () => $this->pdf->bytes($purchase),
                $this->pdf->fileName($purchase),
            )->withMime('application/pdf');

            return $this->templates->send(
                'voucher_purchase',
                $purchase->customer_email,
                $this->variables($purchase),
                [$attachment],
            );
        } catch (Throwable $e) {
            Log::error('Failed to send voucher email', [
                'voucher_purchase_id' => $purchase->id,
                'code' => $purchase->code,
                'recipient' => $purchase->customer_email,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @return array<string, string>
     */
    private function variables(VoucherPurchase $purchase): array
    {
        return [
            'customer_name' => $purchase->customer_name,
            'voucher_name' => $purchase->voucherName(),
            'voucher_code' => $purchase->code,
            'voucher_value' => $purchase->currency.' '.number_format((float) $purchase->value, 2),
            'voucher_price' => $purchase->currency.' '.number_format((float) $purchase->price_paid, 2),
            'expires_at' => $purchase->expires_at?->toDisplay('M d, Y') ?? 'Never',
            'voucher_url' => URL::signedRoute('vouchers.public.show', ['voucher' => $purchase->code]),
            'site_name' => Setting::get('site_name', 'Codeware'),
        ];
    }
}
