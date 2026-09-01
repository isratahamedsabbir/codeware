<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $user->name }} — ID Card</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            width: 240.94pt;
            height: 155.91pt;
            font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
        }

        .card {
            position: relative;
            width: 100%;
            height: 100%;
            padding: 10pt;
            border: 1pt solid #e2e8f0;
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

        .signature-block {
            position: absolute;
            bottom: 10pt;
            right: 10pt;
            text-align: center;
        }

        .signature-img { height: 18pt; max-width: 90pt; }

        .signature-label {
            font-size: 6pt;
            color: #94a3b8;
            border-top: 0.5pt solid #cbd5e1;
            padding-top: 2pt;
            margin-top: 2pt;
        }
    </style>
</head>
<body>
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

        @if ($signature)
            <div class="signature-block">
                <img src="{{ $signature }}" class="signature-img">
                <div class="signature-label">Signature</div>
            </div>
        @endif
    </div>
</body>
</html>
