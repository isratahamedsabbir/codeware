<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    public function show(Request $request, Order $order): View
    {
        return $this->render($order, route('admin.orders.invoice.download', $order));
    }

    public function download(Request $request, Order $order): Response
    {
        return $this->buildPdf($order)->download("invoice-{$order->order_number}.pdf");
    }

    public function publicShow(Request $request, Order $order): View
    {
        return $this->render($order, URL::signedRoute('invoices.public.download', ['order' => $order->order_number]));
    }

    public function publicDownload(Request $request, Order $order): Response
    {
        return $this->buildPdf($order)->download("invoice-{$order->order_number}.pdf");
    }

    private function render(Order $order, string $downloadUrl): View
    {
        $order->load(['items.product', 'transactions']);

        return view('invoices.show', [
            'order' => $order,
            'qrCode' => $this->qrCode($order),
            'logo' => $this->logoDataUri(),
            'downloadUrl' => $downloadUrl,
            'forPdf' => false,
        ]);
    }

    private function buildPdf(Order $order): PdfDocument
    {
        $order->load(['items.product', 'transactions']);

        return Pdf::loadView('invoices.show', [
            'order' => $order,
            'qrCode' => $this->qrCode($order),
            'logo' => $this->logoDataUri(),
            'downloadUrl' => null,
            'forPdf' => true,
        ])->setPaper('a4');
    }

    /**
     * Embedded as a data URI (rather than an <img src="..."> URL) so it renders
     * identically in the browser and in dompdf, which has remote image fetching
     * disabled by default. Prefers the admin-configured site icon, resolved back
     * to a local file on the public disk; falls back to the bundled logo.
     */
    private function logoDataUri(): ?string
    {
        $siteIcon = Setting::get('site_icon');

        if ($siteIcon && ($path = $this->publicDiskPath($siteIcon)) && is_file($path)) {
            return $this->fileToDataUri($path);
        }

        $path = public_path('default/logo.png');

        return is_file($path) ? $this->fileToDataUri($path) : null;
    }

    /**
     * Resolves a site_icon URL (relative or absolute) back to the local file
     * on the "public" disk it was uploaded to, or null if it isn't one.
     */
    private function publicDiskPath(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH) ?? $url;

        if (! str_starts_with($path, '/storage/')) {
            return null;
        }

        return Storage::disk('public')->path(substr($path, strlen('/storage/')));
    }

    private function fileToDataUri(string $path): string
    {
        $mime = mime_content_type($path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($path));
    }

    /**
     * QR always points at the permanent, signed public invoice page — scanning
     * it lets a customer view/print/download the invoice without logging in,
     * whether the invoice was printed from the admin panel or handed over
     * physically.
     */
    private function qrCode(Order $order): string
    {
        $url = URL::signedRoute('invoices.public.show', ['order' => $order->order_number]);

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
