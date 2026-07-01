<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Login Code — Mweela Cash Capital</title>
    <style>
        body { margin:0; padding:0; background:#F5F7FA; font-family:'Helvetica Neue',Helvetica,Arial,sans-serif; }
        .wrapper { max-width:520px; margin:40px auto; padding:0 16px; }
        .card { background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,0.08); }
        .header { background:#00BCD4; padding:28px 32px; text-align:center; }
        .header-title { color:#fff; font-size:20px; font-weight:700; margin:0; }
        .header-sub { color:rgba(255,255,255,0.85); font-size:13px; margin:4px 0 0; }
        .body { padding:32px; }
        .greeting { font-size:15px; color:#2C3E50; margin:0 0 16px; }
        .otp-box { background:#F0FBFD; border:2px dashed #00BCD4; border-radius:10px;
                   text-align:center; padding:24px 16px; margin:20px 0; }
        .otp-code { font-size:48px; font-weight:800; letter-spacing:12px;
                    color:#00ACC1; font-family:'Courier New',monospace; }
        .otp-label { font-size:12px; color:#7F8C8D; margin-top:6px; }
        .expiry { font-size:13px; color:#E65100; background:#FFF3E0;
                  border-radius:6px; padding:10px 14px; margin:16px 0; }

        /* ── One-click verify button ───────────────── */
        .btn-wrap { text-align:center; margin:24px 0 20px; }
        .btn-verify {
            display:inline-block;
            background:#00BCD4;
            color:#ffffff !important;
            text-decoration:none;
            font-size:16px;
            font-weight:700;
            padding:15px 40px;
            border-radius:10px;
            letter-spacing:0.3px;
        }
        .btn-verify:hover { background:#00ACC1; }
        .divider-text {
            text-align:center;
            font-size:12px;
            color:#95A5A6;
            margin:8px 0 4px;
        }

        .message { font-size:13px; color:#5D6D7E; line-height:1.7; margin:12px 0; }
        .divider { border:none; border-top:1px solid #E8ECF1; margin:20px 0; }
        .footer { padding:20px 32px; background:#FAFBFC; text-align:center; }
        .footer p { font-size:12px; color:#95A5A6; margin:4px 0; }
        .url-fallback {
            word-break:break-all;
            font-size:11px;
            color:#95A5A6;
            background:#F5F7FA;
            border-radius:6px;
            padding:8px 10px;
            margin:8px 0 0;
            font-family:'Courier New',monospace;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="card">
        <div class="header">
            <p class="header-title">Mweela Cash Capital</p>
            <p class="header-sub">Secure Login Verification</p>
        </div>
        <div class="body">
            <p class="greeting">Hello, <strong>{{ $user->name }}</strong>,</p>
            <p class="message">
                We received a login request for your account. You can verify instantly by
                clicking the button below, or enter the code manually on the verification page.
            </p>

            {{-- ── One-click verify button ── --}}
            @if($verifyUrl)
            <div class="btn-wrap">
                <a href="{{ $verifyUrl }}" class="btn-verify">
                    ✓ &nbsp; Verify &amp; Sign In Now
                </a>
            </div>
            <p class="divider-text">— or enter the code manually —</p>
            @endif

            {{-- ── Manual OTP code ── --}}
            <div class="otp-box">
                <div class="otp-code">{{ $otp }}</div>
                <div class="otp-label">One-Time Login Code</div>
            </div>

            <div class="expiry">
                <strong>⏱ This code and button expire in {{ $expiryMinutes }} minutes.</strong>
                Both can only be used once. Do <strong>not</strong> share with anyone.
            </div>

            <p class="message">
                If you did not attempt to log in, please contact your administrator immediately
                and change your password.
            </p>

            @if($verifyUrl)
            <hr class="divider">
            <p class="message" style="font-size:12px; color:#95A5A6;">
                If the button above does not work, copy and paste this link into your browser:
            </p>
            <p class="url-fallback">{{ $verifyUrl }}</p>
            @endif

            <hr class="divider">
            <p class="message" style="font-size:12px; color:#95A5A6;">
                This is an automated security message from <strong>Mweela Cash Capital</strong>.
                Please do not reply.
            </p>
        </div>
        <div class="footer">
            <p>© {{ date('Y') }} Mweela Cash Capital Ltd · Mutomo, Kitui, Kenya</p>
            <p>mweelacredit.co.ke</p>
        </div>
    </div>
</div>
</body>
</html>
