<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Setting;
use App\Support\PrintAssets;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Symfony\Component\HttpFoundation\Response;

class ProductLabelController extends Controller
{
    /**
     * A small printable label — name, store logo, and a scannable barcode —
     * separate from the full invoice/address prints, meant for sticking on
     * the product itself or its packaging rather than the shipment.
     */
    public function download(Request $request, int $id): Response
    {
        // withTrashed() — the product's own Show page (admin.products.show)
        // can be viewed for a soft-deleted product, so printing its label
        // from there shouldn't 404 just because it's since been deleted.
        $product = Product::withTrashed()->with('categories')->findOrFail($id);

        $code = $product->sku ?: 'PROD-'.$product->id;

        $barcode = (new BarcodeGeneratorPNG)->getBarcode(
            $code,
            BarcodeGeneratorPNG::TYPE_CODE_128,
            widthFactor: 2,
            height: 60,
        );

        $pdf = Pdf::loadView('products.label', [
            'product' => $product,
            'code' => $code,
            'barcode' => 'data:image/png;base64,'.base64_encode($barcode),
            'logo' => PrintAssets::logoDataUri(),
            'siteName' => Setting::get('site_name', 'Codeware'),
            'contactPhone' => Setting::get('contact_phone'),
            'contactEmail' => Setting::get('contact_email'),
            'currencySymbol' => Setting::get('currency_symbol', '৳'),
        ])->setPaper([0, 0, 216, 324]); // 3in x 4.5in — portrait label

        return $pdf->download("label-{$code}.pdf");
    }
}
