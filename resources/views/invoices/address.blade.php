<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Address — {{ $order->order_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            color: #1f2937;
            margin: 0;
            padding: 32px;
        }
        .actions { text-align: center; margin-bottom: 24px; }
        .actions button {
            display: inline-block; padding: 9px 20px; border-radius: 8px; font-size: 13px; font-weight: 600;
            background: #fff; color: #111827; border: 1px solid #d1d5db; cursor: pointer;
        }
        .label {
            max-width: 560px;
            margin: 0 auto;
            border: 2px solid #111827;
            border-radius: 10px;
            padding: 28px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
        }
        .section-label {
            font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.08em;
            color: #6b7280; margin-bottom: 8px;
        }
        .order-number { font-size: 13px; color: #6b7280; margin-bottom: 20px; }
        .name { font-size: 20px; font-weight: bold; color: #111827; margin-bottom: 6px; }
        .phone { font-size: 15px; color: #374151; margin-bottom: 16px; }
        .address { font-size: 17px; line-height: 1.5; color: #111827; white-space: pre-line; }
        .qr-box { text-align: center; flex-shrink: 0; }
        .qr-box img { width: 110px; height: 110px; }
        .qr-caption { font-size: 10px; color: #9ca3af; margin-top: 4px; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
        }
    </style>
</head>
<body>

    <div class="actions no-print">
        <button onclick="window.print()">Print</button>
    </div>

    <div class="label">
        <div>
            <div class="order-number">Order {{ $order->order_number }}</div>

            <div class="section-label">Ship To</div>
            <div class="name">{{ $order->customer_name }}</div>
            <div class="phone">{{ $order->customer_phone }}</div>
            <div class="address">{{ $order->shipping_address }}</div>
        </div>

        <div class="qr-box">
            <img src="{{ $qrCode }}" alt="Order QR code">
            <div class="qr-caption">Scan for order details</div>
        </div>
    </div>

</body>
</html>
