<?php

namespace App\Services;

use App\Models\VoucherPurchase;
use App\Support\PrintAssets;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\URL;

/**
 * Renders the designed voucher PDF — shared by the emailed attachment
 * (VoucherEmailService) and the public/admin download routes, so all three
 * always show the same artwork. Mirrors InvoiceController's DomPDF recipe.
 */
class VoucherPdfService
{
    /**
     * @return array<string, mixed>
     */
    public function data(VoucherPurchase $purchase, ?string $downloadUrl, bool $forPdf): array
    {
        $purchase->loadMissing('voucher');

        return [
            'purchase' => $purchase,
            'voucher' => $purchase->voucher,
            'qrCode' => $this->qrCode($purchase),
            'logo' => PrintAssets::logoDataUri(),
            'downloadUrl' => $downloadUrl,
            'forPdf' => $forPdf,
        ];
    }

    public function make(VoucherPurchase $purchase): PdfDocument
    {
        return Pdf::loadView('vouchers.show', $this->data($purchase, null, true))->setPaper('a4');
    }

    public function bytes(VoucherPurchase $purchase): string
    {
        return $this->make($purchase)->output();
    }

    public function fileName(VoucherPurchase $purchase): string
    {
        return "voucher-{$purchase->code}.pdf";
    }

    /**
     * QR points at the permanent, signed public voucher page — scanning it lets
     * the customer view/print/download the voucher without logging in.
     */
    private function qrCode(VoucherPurchase $purchase): string
    {
        $url = URL::signedRoute('vouchers.public.show', ['voucher' => $purchase->code]);

        $result = (new Builder(writer: new PngWriter))->build(
            data: $url,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 220,
            margin: 8,
        );

        return $result->getDataUri();
    }
}
