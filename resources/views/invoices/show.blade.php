<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->order_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            color: #1f2937;
            font-size: 13px;
            margin: 0;
            padding: 32px;
        }
        table { border-collapse: collapse; width: 100%; }
        .head-table td { vertical-align: top; padding: 0; }
        .brand-name { font-size: 20px; font-weight: bold; color: #111827; }
        .invoice-title { font-size: 24px; font-weight: bold; color: #111827; text-align: right; }
        .invoice-meta { text-align: right; color: #6b7280; font-size: 12px; margin-top: 4px; }
        .logo { max-height: 48px; margin-bottom: 6px; }
        .section-label { font-size: 10.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.06em; color: #6b7280; margin-bottom: 6px; }
        .divider { border-top: 1px solid #e5e7eb; margin: 24px 0; }
        .items-table th {
            background: #f9fafb; text-align: left; font-size: 10.5px; text-transform: uppercase;
            letter-spacing: 0.05em; color: #6b7280; padding: 8px 10px; border-bottom: 1px solid #e5e7eb;
        }
        .items-table td { padding: 8px 10px; border-bottom: 1px solid #f3f4f6; font-size: 12.5px; }
        .text-right { text-align: right; }
        .totals-table td { padding: 6px 10px; font-size: 12.5px; }
        .totals-table .label { text-align: right; color: #6b7280; }
        .totals-table .value { text-align: right; width: 130px; }
        .totals-table .grand td { font-size: 14px; font-weight: bold; color: #111827; border-top: 1px solid #e5e7eb; }
        .badge {
            display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 11px;
            font-weight: bold; text-transform: capitalize; background: #f3f4f6; color: #374151;
        }
        .qr-box { text-align: center; }
        .qr-box img { width: 110px; height: 110px; }
        .qr-caption { font-size: 10px; color: #9ca3af; margin-top: 4px; }
        .footer { margin-top: 32px; text-align: center; color: #9ca3af; font-size: 11px; }
        .actions { text-align: center; margin-bottom: 24px; }
        .actions a, .actions button {
            display: inline-block; padding: 9px 20px; border-radius: 8px; font-size: 13px; font-weight: 600;
            text-decoration: none; margin: 0 4px; cursor: pointer; border: 1px solid #d1d5db;
        }
        .actions .primary { background: #111827; color: #fff; border-color: #111827; }
        .actions .secondary { background: #fff; color: #111827; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
        }
    </style>
</head>
<body>

    @unless ($forPdf)
        <div class="actions no-print">
            <button class="secondary" onclick="window.print()">Print</button>
            <a class="primary" href="{{ $downloadUrl }}">Download PDF</a>
        </div>
    @endunless

    <table class="head-table">
        <tr>
            <td style="width: 55%;">
                @if ($logo)
                    <img class="logo" src="{{ $logo }}" alt="{{ \App\Models\Setting::get('site_name', 'Codeware') }}">
                @endif
                <div class="brand-name">{{ \App\Models\Setting::get('site_name', 'Codeware') }}</div>
                @if ($email = \App\Models\Setting::get('contact_email'))
                    <div style="color: #6b7280; font-size: 11.5px;">{{ $email }}</div>
                @endif
            </td>
            <td style="width: 45%;">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-meta">
                    #{{ $order->order_number }}<br>
                    {{ $order->created_at->format('M d, Y') }}
                </div>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <table class="head-table">
        <tr>
            <td style="width: 55%;">
                <div class="section-label">Bill To</div>
                <div>{{ $order->customer_name }}</div>
                <div style="color: #6b7280;">{{ $order->customer_email }}</div>
                <div style="color: #6b7280;">{{ $order->customer_phone }}</div>
            </td>
            <td style="width: 45%;">
                <div class="section-label">Shipping Address</div>
                <div style="color: #374151; white-space: pre-line;">{{ $order->shipping_address }}</div>
            </td>
        </tr>
    </table>

    <table class="head-table" style="margin-top: 18px;">
        <tr>
            <td style="width: 33%;">
                <div class="section-label">Order Status</div>
                <span class="badge">{{ $order->status }}</span>
            </td>
            <td style="width: 33%;">
                <div class="section-label">Payment Method</div>
                <div>{{ \App\Support\PaymentMethods::label($order->payment_method) }}</div>
            </td>
            <td style="width: 34%;">
                <div class="section-label">Payment Status</div>
                <span class="badge">{{ $order->payment_status }}</span>
            </td>
        </tr>
    </table>

    <table class="items-table" style="margin-top: 24px;">
        <thead>
            <tr>
                <th>Product</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Line Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td class="text-right">{{ number_format((float) $item->unit_price, 2) }}</td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">{{ number_format((float) $item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table style="margin-top: 12px;">
        <tr>
            <td style="width: 60%; vertical-align: bottom;">
                @if ($order->notes)
                    <div class="section-label">Notes</div>
                    <div style="color: #374151;">{{ $order->notes }}</div>
                @endif
            </td>
            <td style="width: 40%;">
                <table class="totals-table">
                    <tr>
                        <td class="label">Subtotal</td>
                        <td class="value">{{ number_format((float) $order->subtotal, 2) }} {{ $order->currency }}</td>
                    </tr>
                    <tr class="grand">
                        <td class="label">Total</td>
                        <td class="value">{{ number_format((float) $order->total, 2) }} {{ $order->currency }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <table>
        <tr>
            <td style="width: 70%; vertical-align: middle; color: #9ca3af; font-size: 11px;">
                This is a computer-generated invoice for order {{ $order->order_number }}.
            </td>
            <td style="width: 30%;">
                <div class="qr-box">
                    <img src="{{ $qrCode }}" alt="QR code">
                    <div class="qr-caption">Scan to view online</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        &copy; {{ $order->created_at->format('Y') }} {{ \App\Models\Setting::get('site_name', 'Codeware') }}. All rights reserved.
    </div>

</body>
</html>
