<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $user->name }} — ID Card</title>
    <style>
        @page { margin: 0; size: 240.94pt 155.91pt; }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
            @unless ($forPdf)
                background: #f1f5f9;
                padding: 32px 16px;
            @endunless
        }

        .actions { text-align: center; margin-bottom: 20px; }
        .actions a, .actions button {
            display: inline-block; padding: 9px 20px; border-radius: 8px; font-size: 13px; font-weight: 600;
            text-decoration: none; margin: 0 4px; cursor: pointer; border: 1px solid #d1d5db;
            font-family: inherit;
        }
        .actions .primary { background: #111827; color: #fff; border-color: #111827; }
        .actions .secondary { background: #fff; color: #111827; }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; padding: 0; }
        }

        .card-wrap {
            @unless ($forPdf)
                width: 240.94pt;
                margin: 0 auto;
            @endunless
        }

        .card {
            position: relative;
            @if ($forPdf)
                {{-- dompdf doesn't honor box-sizing: border-box — border and
                     padding add on top of width/height instead of being
                     contained within it, so the box is pre-shrunk here to
                     land on the same 240.94pt x 155.91pt outer size once
                     the 1pt border + 10pt padding are added back on. --}}
                width: 218.94pt;
                height: 133.91pt;
            @else
                width: 240.94pt;
                height: 155.91pt;
            @endif
            padding: 10pt;
            border: 1pt solid #e2e8f0;
            background: #ffffff;
            @unless ($forPdf)
                border-radius: 8pt;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            @endunless
        }

        .header-table { width: 100%; }

        .photo-cell { width: 54pt; vertical-align: top; }

        .photo {
            width: 48pt;
            height: 48pt;
            border-radius: 4pt;
            border: 1pt solid #e2e8f0;
            background: #f1f5f9;
            text-align: center;
            vertical-align: middle;
            font-size: 15pt;
            font-weight: bold;
            color: #94a3b8;
        }

        .photo img { width: 48pt; height: 48pt; border-radius: 4pt; }

        .info-cell { vertical-align: top; padding-left: 8pt; }

        .company {
            font-size: 6.5pt;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5pt;
        }

        .name { font-size: 12pt; font-weight: bold; color: #0f172a; margin-top: 2pt; }

        .email { font-size: 7.5pt; color: #475569; margin-top: 2pt; }

        .badge {
            display: inline-block;
            margin-top: 5pt;
            padding: 2pt 6pt;
            font-size: 6.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3pt;
            color: #ffffff;
            background: #2563eb;
            border-radius: 3pt;
        }

        .qr-block {
            position: absolute;
            bottom: 10pt;
            left: 10pt;
            text-align: center;
        }

        .qr-block img { width: 26pt; height: 26pt; }

        .signature-block {
            position: absolute;
            bottom: 10pt;
            right: 10pt;
            text-align: center;
        }

        .signature-img { height: 18pt; max-width: 90pt; }

        .caption {
            font-size: 5.5pt;
            color: #94a3b8;
            border-top: 0.5pt solid #cbd5e1;
            padding-top: 2pt;
            margin-top: 2pt;
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

    <div class="card-wrap">
        <div class="card">
            <table class="header-table">
                <tr>
                    <td class="photo-cell">
                        <div class="photo">
                            @if ($photo)
                                <img src="{{ $photo }}">
                            @else
                                {{ $user->initials() }}
                            @endif
                        </div>
                    </td>
                    <td class="info-cell">
                        <div class="company">{{ config('app.name') }}</div>
                        <div class="name">{{ $user->name }}</div>
                        <div class="email">{{ $user->email }}</div>
                        <div class="badge">{{ $tier }}</div>
                    </td>
                </tr>
            </table>

            <div class="qr-block">
                <img src="{{ $qrCode }}">
                <div class="caption">Scan for profile</div>
            </div>

            @if ($signature)
                <div class="signature-block">
                    <img src="{{ $signature }}" class="signature-img">
                    <div class="caption">Signature</div>
                </div>
            @endif
        </div>
    </div>

</body>
</html>
