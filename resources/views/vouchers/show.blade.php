<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Voucher {{ $purchase->code }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            color: #1f2937;
            margin: 0;
            padding: 28px;
            background: #ffffff;
        }
        .actions { text-align: center; margin-bottom: 20px; }
        .actions a, .actions button {
            display: inline-block; padding: 9px 20px; border-radius: 8px; font-size: 13px; font-weight: 600;
            text-decoration: none; margin: 0 4px; cursor: pointer; border: 1px solid #d1d5db;
        }
        .actions .primary { background: #111827; color: #fff; border-color: #111827; }
        .actions .secondary { background: #fff; color: #111827; }

        .voucher {
            border: 2px solid #111827;
            border-radius: 14px;
            overflow: hidden;
        }
        .voucher-head {
            background: #111827;
            color: #ffffff;
            padding: 20px 26px;
        }
        .voucher-head table { width: 100%; border-collapse: collapse; }
        .voucher-head td { vertical-align: middle; padding: 0; }
        .logo { max-height: 42px; }
        .brand-name { font-size: 18px; font-weight: bold; color: #ffffff; }
        .voucher-label { text-align: right; font-size: 22px; font-weight: bold; letter-spacing: 0.14em; }

        .voucher-body { padding: 26px; }
        .clearfix { clear: both; }
        .voucher-value { font-size: 46px; font-weight: bold; color: #111827; line-height: 1; }
        .voucher-value small { font-size: 16px; font-weight: normal; color: #6b7280; }
        .voucher-name { font-size: 17px; font-weight: bold; color: #111827; margin-top: 8px; }
        .voucher-desc { color: #6b7280; font-size: 12.5px; margin-top: 6px; }

        .code-box {
            float: right;
            width: 46%;
            border: 1px dashed #9ca3af;
            border-radius: 10px;
            padding: 16px;
            text-align: center;
        }
        .code-label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.12em; color: #6b7280; }
        .code-value { font-size: 24px; font-weight: bold; letter-spacing: 0.06em; color: #111827; margin-top: 6px; word-break: break-all; }
        .code-meta { font-size: 11px; color: #6b7280; margin-top: 8px; }

        .divider { border-top: 1px solid #e5e7eb; margin: 24px 0; }
        .section-label { font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.08em; color: #6b7280; margin-bottom: 4px; }
        .meta-table { width: 100%; border-collapse: collapse; }
        .meta-table td { vertical-align: top; padding: 0 18px 0 0; }
        .message { margin-top: 20px; padding: 14px 16px; background: #f9fafb; border-left: 3px solid #111827; border-radius: 6px; color: #374151; font-size: 12.5px; }

        .qr-box { text-align: center; }
        .qr-box img { width: 104px; height: 104px; }
        .qr-caption { font-size: 10px; color: #9ca3af; margin-top: 4px; }

        .footer { margin-top: 22px; text-align: center; color: #9ca3af; font-size: 11px; }

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

    <div class="voucher">
        <div class="voucher-head">
            <table>
                <tr>
                    <td style="width: 60%;">
                        @if ($logo)
                            <img class="logo" src="{{ $logo }}" alt="{{ \App\Models\Setting::get('site_name', 'Codeware') }}">
                        @endif
                        <div class="brand-name">{{ \App\Models\Setting::get('site_name', 'Codeware') }}</div>
                    </td>
                    <td style="width: 40%;">
                        <div class="voucher-label">GIFT VOUCHER</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="voucher-body">
            <div class="code-box">
                <div class="code-label">Voucher Code</div>
                <div class="code-value">{{ $purchase->code }}</div>
                <div class="code-meta">Present this code or the attached PDF to redeem.</div>
            </div>

            <div style="width: 50%;">
                <div class="voucher-value">
                    {{ $purchase->currency }} {{ number_format((float) $purchase->value, 2) }}
                </div>
                <div class="voucher-name">{{ $purchase->voucherName() }}</div>
                @if ($voucher?->getTranslation('description', 'en', false))
                    <div class="voucher-desc">{{ $voucher->getTranslation('description', 'en', false) }}</div>
                @endif
            </div>
            <div class="clearfix"></div>

            <div class="divider"></div>

            <table class="meta-table">
                <tr>
                    <td style="width: 30%;">
                        <div class="section-label">Issued To</div>
                        <div>{{ $purchase->customer_name }}</div>
                    </td>
                    <td style="width: 30%;">
                        <div class="section-label">Purchase Date</div>
                        <div>{{ $purchase->purchased_at?->toDisplay('M d, Y') ?: '—' }}</div>
                    </td>
                    <td style="width: 40%;">
                        <div class="section-label">Valid Until</div>
                        <div>{{ $purchase->expires_at?->toDisplay('M d, Y') ?: 'Never expires' }}</div>
                    </td>
                </tr>
                @if ($purchase->recipient_name)
                    <tr>
                        <td colspan="3" style="padding-top: 18px;">
                            <div class="section-label">For</div>
                            <div>{{ $purchase->recipient_name }}</div>
                        </td>
                    </tr>
                @endif
            </table>

            @if ($purchase->message)
                <div class="message">“{{ $purchase->message }}”</div>
            @endif

            <table style="margin-top: 24px;">
                <tr>
                    <td style="width: 70%; vertical-align: middle; color: #9ca3af; font-size: 11px;">
                        This voucher is issued for {{ $purchase->currency }} {{ number_format((float) $purchase->value, 2) }} and carries no cash value.
                    </td>
                    <td style="width: 30%;">
                        <div class="qr-box">
                            <img src="{{ $qrCode }}" alt="QR code">
                            <div class="qr-caption">Scan to view online</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="footer">
        &copy; {{ $purchase->purchased_at?->toDisplay('Y') ?: date('Y') }} {{ \App\Models\Setting::get('site_name', 'Codeware') }}. All rights reserved.
    </div>

</body>
</html>
