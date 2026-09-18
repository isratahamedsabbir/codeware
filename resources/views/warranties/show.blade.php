<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Warranty Card {{ $order->order_number }}</title>
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
        .doc-title { font-size: 24px; font-weight: bold; color: #111827; text-align: right; }
        .doc-meta { text-align: right; color: #6b7280; font-size: 12px; margin-top: 4px; }
        .logo { max-height: 48px; margin-bottom: 6px; }
        .section-label { font-size: 10.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.06em; color: #6b7280; margin-bottom: 6px; }
        .divider { border-top: 1px solid #e5e7eb; margin: 24px 0; }
        .card {
            border: 1px solid #e5e7eb; border-radius: 10px; padding: 18px 20px;
            margin-top: 16px; page-break-inside: avoid;
        }
        .card-title { font-size: 15px; font-weight: bold; color: #111827; }
        .field-grid td { padding: 6px 0; font-size: 12.5px; vertical-align: top; }
        .field-label { color: #6b7280; width: 140px; }
        .field-value { color: #111827; font-weight: 600; }
        .footer { margin-top: 32px; text-align: center; color: #9ca3af; font-size: 11px; }
    </style>
</head>
<body>

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
                <div class="doc-title">WARRANTY CARD</div>
                <div class="doc-meta">
                    Order #{{ $order->order_number }}<br>
                    {{ $order->created_at->toDisplay('M d, Y') }}
                </div>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <div class="section-label">Customer</div>
    <div>{{ $order->customer_name }}</div>
    <div style="color: #6b7280;">{{ $order->customer_email }}</div>
    <div style="color: #6b7280;">{{ $order->customer_phone }}</div>

    @foreach ($items as $item)
        @php
            $months = $item->product->warranty_months;
            $expiresAt = $order->created_at->copy()->addMonths($months);
        @endphp
        <div class="card">
            <div class="card-title">{{ $item->item_name }}</div>
            <table class="field-grid">
                <tr>
                    <td class="field-label">Warranty Period</td>
                    <td class="field-value">{{ $months }} {{ \Illuminate\Support\Str::plural('month', $months) }}</td>
                </tr>
                <tr>
                    <td class="field-label">Quantity</td>
                    <td class="field-value">{{ $item->quantity }}</td>
                </tr>
                <tr>
                    <td class="field-label">Purchase Date</td>
                    <td class="field-value">{{ $order->created_at->toDisplay('M d, Y') }}</td>
                </tr>
                <tr>
                    <td class="field-label">Valid Until</td>
                    <td class="field-value">{{ $expiresAt->toDisplay('M d, Y') }}</td>
                </tr>
            </table>
        </div>
    @endforeach

    <div class="footer">
        &copy; {{ $order->created_at->toDisplay('Y') }} {{ \App\Models\Setting::get('site_name', 'Codeware') }}. All rights reserved.
        This warranty card must be presented, along with the original invoice, when making a claim.
    </div>

</body>
</html>
