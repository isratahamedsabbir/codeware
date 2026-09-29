<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your verification code</title>
</head>
<body style="margin:0;background:#f0f4f8;font-family:Arial,sans-serif;color:#172033;">
    <div style="padding:32px 16px;">
        <div style="max-width:480px;margin:0 auto;background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
            <div style="padding:28px 36px;background:#1e7bc4;color:#fff;">
                <h1 style="margin:0;font-size:22px;">{{ __('Confirm it is you') }}</h1>
                <p style="margin:8px 0 0;font-size:14px;">
                    @if ($reason === 'order')
                        {{ __('Hi :name, use this code to confirm your email. We made an account for you so you can see the order you placed with this address.', ['name' => $name]) }}
                    @else
                        {{ __('Hi :name, enter this code to choose a new password for your account.', ['name' => $name]) }}
                    @endif
                </p>
            </div>
            <div style="padding:32px 36px;text-align:center;">
                <span style="display:inline-block;font-size:32px;font-weight:bold;letter-spacing:8px;color:#172033;background:#f0f4f8;padding:16px 24px;border-radius:8px;">
                    {{ $code }}
                </span>
                <p style="margin:20px 0 0;font-size:13px;color:#64748b;">{{ __('This code expires in :minutes minutes and can only be used once.', ['minutes' => $minutes]) }}</p>
            </div>
            <div style="padding:18px 36px;background:#f8fafc;border-top:1px solid #e2e8f0;text-align:center;color:#64748b;font-size:12px;">
                {{ config('app.name') }}
            </div>
        </div>
    </div>
</body>
</html>
