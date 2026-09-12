<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Label — {{ $product->getTranslation('name', 'en', false) }}</title>
    <style>
        {{-- DomPDF applies its own default @page margin on top of any body
             padding — on a page this small that alone was enough to push
             content onto a second, near-empty page. Zeroing it makes the
             body's own padding the only margin, so everything fits on one
             page. --}}
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            color: #1f2937;
            margin: 0;
            padding: 22px 18px;
            text-align: center;
        }
        .logo { margin-bottom: 10px; }
        .brand-name { font-size: 15px; font-weight: bold; letter-spacing: 0.14em; color: #111827; }
        .divider { width: 46px; height: 1px; background: #d1d5db; margin: 16px auto; }
        .product-name { font-size: 19px; font-weight: bold; letter-spacing: 0.04em; color: #111827; line-height: 1.25; }
        .product-sub { font-size: 9px; letter-spacing: 0.16em; color: #6b7280; text-transform: uppercase; margin-top: 6px; }
        .price { font-size: 11px; color: #374151; margin-top: 14px; }
        .contact { font-size: 9px; color: #6b7280; margin-top: 6px; }
        .contact span { margin: 0 6px; }
        .barcode-wrap { margin-top: 18px; }
        .code { font-size: 9.5px; letter-spacing: 0.14em; color: #374151; margin-top: 5px; }
    </style>
</head>
<body>

    @if ($logo)
        {{-- Explicit height attribute (not CSS max-height) — dompdf doesn't
             reliably shrink images from CSS alone. --}}
        <img class="logo" src="{{ $logo }}" height="36" alt="{{ $siteName }}">
    @endif

    <div class="brand-name">{{ strtoupper($siteName) }}</div>

    <div class="divider"></div>

    <div class="product-name">{{ strtoupper($product->getTranslation('name', 'en', false)) }}</div>
    @if ($product->categories->isNotEmpty())
        <div class="product-sub">{{ $product->categories->map(fn ($c) => $c->getTranslation('name', 'en', false))->implode(' · ') }}</div>
    @endif

    <div class="price">{{ $currencySymbol }}{{ number_format((float) $product->price, 2) }}</div>

    @if ($contactPhone || $contactEmail)
        <div class="contact">
            @if ($contactPhone)<span>{{ $contactPhone }}</span>@endif
            @if ($contactPhone && $contactEmail)|@endif
            @if ($contactEmail)<span>{{ $contactEmail }}</span>@endif
        </div>
    @endif

    <div class="barcode-wrap">
        {{-- Same reasoning as the logo — explicit width/height, sized to fit
             this page regardless of the barcode's own intrinsic pixel size
             (which grows with the code's length). --}}
        <img src="{{ $barcode }}" width="150" height="44" alt="Barcode">
        <div class="code">{{ $code }}</div>
    </div>

</body>
</html>
