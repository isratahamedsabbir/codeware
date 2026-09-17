<?php

namespace App\Http\Controllers;

use App\Models\VoucherPurchase;
use App\Services\VoucherPdfService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves the designed voucher as a browser page or a PDF download — the public
 * side is reached through permanent signed links (the voucher email and the
 * QR code printed on the voucher itself), so a customer never needs to log in.
 * Mirrors InvoiceController.
 */
class VoucherController extends Controller
{
    public function __construct(
        private readonly VoucherPdfService $pdf,
    ) {}

    public function publicShow(Request $request, VoucherPurchase $voucher): View
    {
        return view('vouchers.show', $this->pdf->data(
            $voucher,
            URL::signedRoute('vouchers.public.download', ['voucher' => $voucher->code]),
            forPdf: false,
        ));
    }

    public function publicDownload(Request $request, VoucherPurchase $voucher): Response
    {
        return $this->pdf->make($voucher)->download($this->pdf->fileName($voucher));
    }

    public function adminDownload(Request $request, VoucherPurchase $purchase): Response
    {
        return $this->pdf->make($purchase)->download($this->pdf->fileName($purchase));
    }
}
