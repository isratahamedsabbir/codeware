<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Demo</title>
</head>
<body style="margin:0;background:#f0f4f8;font-family:Arial,sans-serif;color:#172033;">
    <div style="padding:32px 16px;">
        <div style="max-width:580px;margin:0 auto;background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
            <div style="padding:28px 36px;background:#2563eb;color:#fff;">
                <h1 style="margin:0;font-size:24px;">Book a Demo</h1>
                <p style="margin:8px 0 0;font-size:14px;">A new demo booking request has been submitted.</p>
            </div>
            <div style="padding:32px 36px;font-size:15px;line-height:1.8;">
                {!! $bodyHtml !!}
            </div>
            <div style="padding:18px 36px;background:#f8fafc;border-top:1px solid #e2e8f0;text-align:center;color:#64748b;font-size:12px;">
                {{ config('app.name') }}
            </div>
        </div>
    </div>
</body>
</html>
