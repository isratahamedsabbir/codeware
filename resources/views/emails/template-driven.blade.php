<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>{!! $subjectLine !!}</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
  body {
    margin: 0;
    padding: 0;
    background-color: #f0f4f8;
    font-family: 'Poppins', Arial, sans-serif;
    -webkit-font-smoothing: antialiased;
  }
  a { text-decoration: none; }
  @media (max-width: 600px) {
    .email-wrapper { padding: 16px 8px !important; }
    .body-pad { padding: 28px 24px !important; }
    .hero-strip { padding: 22px 24px !important; }
    .header-pad { padding: 24px !important; }
    .contact-row { flex-direction: column !important; }
    .footer-pad { padding: 18px 24px !important; }
  }
</style>
</head>
<body>

<div class="email-wrapper" style="background:#f0f4f8;padding:32px 16px;min-height:100vh;">
  <div style="max-width:580px;margin:0 auto;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e2e8f0;">

    <!-- ── HERO STRIP ── -->
    <div class="hero-strip" style="background:#7cc242;padding:28px 40px;display:flex;align-items:center;gap:16px;">
      <div style="background:#ffffff;border-radius:14px;padding:10px 22px;box-shadow:0 4px 12px rgba(0,0,0,0.08);margin:0 auto;">
        <img src="{{ asset('agrosal_logo.png') }}"
             alt="{{ config('app.name') }}"
             style="max-width:180px;height:auto;display:block;margin:0 auto">
      </div>
    </div>

    <!-- ── BODY ── -->
    <div class="body-pad" style="padding:36px 40px;">

      {!! $bodyHtml !!}

      <!-- divider -->
      <div style="border-top:1px solid #f1f5f9;margin-bottom:28px;"></div>

      <!-- section label -->
      <p style="margin:0 0 14px;font-size:11px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:1.2px;">Get in touch</p>

      <!-- Contact cards -->
      <div class="contact-row" style="display:flex;gap:12px;">

        <!-- Email -->
        @if (\App\Models\Setting::get('contact_email'))
        <a href="mailto:{{ \App\Models\Setting::get('contact_email') }}"
           style="flex:1;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:16px;display:flex;align-items:center;gap:12px;color:inherit;">
          <div>
            <p style="margin:0;font-size:11px;color:#94a3b8;font-weight:400;">Email</p>
            <p style="margin:3px 0 0;font-size:12px;color:#5d9c1f;font-weight:600;">{{ \App\Models\Setting::get('contact_email') }}</p>
          </div>
        </a>
        @endif

        <!-- Website -->
        @if (config('app.frontend_url'))
        <a href="{{ config('app.frontend_url') }}"
           style="flex:1;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:16px;display:flex;align-items:center;gap:12px;color:inherit;">
          <div>
            <p style="margin:0;font-size:11px;color:#94a3b8;font-weight:400;">Website</p>
            <p style="margin:3px 0 0;font-size:12px;color:#5d9c1f;font-weight:600;">{{ config('app.name') }}</p>
          </div>
        </a>
        @endif

      </div>

      <!-- CTA Button -->
      @if (config('app.frontend_url'))
      <div style="text-align:center;margin-top:32px;">
        <a href="{{ config('app.frontend_url') }}"
           style="display:inline-block;background:#7cc242;color:#ffffff;font-family:'Poppins',Arial,sans-serif;font-size:14px;font-weight:600;padding:14px 40px;border-radius:50px;letter-spacing:0.4px;">
          Visit Our Website
        </a>
      </div>
      @endif

    </div>

    <!-- ── FOOTER ── -->
    <div class="footer-pad" style="background:#f8fafc;border-top:1px solid #f1f5f9;padding:20px 40px;text-align:center;">
      <p style="margin:0;font-size:12px;color:#94a3b8;font-weight:400;">© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>

  </div>
</div>

</body>
</html>
