<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Login — Mweela Cash Capital</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #F5F7FA;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            width: 100%;
            max-width: 420px;
        }
        @media (max-width:480px) {
            body { align-items:flex-start; padding:24px 0; background:white; }
            .card { padding:32px 20px; border-radius:0; box-shadow:none; min-height:100vh; }
        }
        .brand { text-align:center; margin-bottom:28px; }
        .brand-icon {
            width:56px; height:56px; background:#00BCD4; border-radius:12px;
            display:flex; align-items:center; justify-content:center;
            color:white; font-size:28px; font-weight:700; margin:0 auto 12px;
        }
        .brand h1 { font-size:20px; color:#2C3E50; }
        .brand p  { font-size:13px; color:#7F8C8D; margin-top:4px; }

        .info-box {
            background:#E3F2FD; border:1px solid #90CAF9; border-radius:8px;
            padding:12px 16px; font-size:13px; color:#1565C0; margin-bottom:20px;
            line-height:1.6;
        }
        .otp-inputs {
            display: flex; gap:10px; justify-content:center; margin:20px 0;
        }
        .otp-inputs input {
            width: 52px; height:60px;
            border: 2px solid #E8ECF1; border-radius:10px;
            font-size: 26px; font-weight:700; text-align:center;
            outline:none; transition:border-color 0.2s, box-shadow 0.2s;
            color: #2C3E50;
        }
        .otp-inputs input:focus {
            border-color: #00BCD4;
            box-shadow: 0 0 0 3px rgba(0,188,212,0.15);
        }
        .otp-inputs input.filled { border-color:#00BCD4; background:#F0FBFD; }

        .btn-verify {
            width:100%; padding:14px; background:#00BCD4; color:white;
            border:none; border-radius:8px; font-size:15px; font-weight:600;
            cursor:pointer; transition:background 0.2s;
        }
        .btn-verify:hover { background:#00ACC1; }
        .btn-verify:disabled { background:#B0BEC5; cursor:not-allowed; }

        .resend-row {
            text-align:center; margin-top:18px; font-size:13px; color:#7F8C8D;
        }
        .resend-row a { color:#00BCD4; text-decoration:none; font-weight:600; }
        .resend-row a:hover { text-decoration:underline; }

        .back-row {
            text-align:center; margin-top:14px; font-size:12px;
        }
        .back-row a { color:#95A5A6; text-decoration:none; }
        .back-row a:hover { color:#00BCD4; }

        .error-msg {
            background:#FFEBEE; border:1px solid #FFCDD2; border-radius:8px;
            padding:10px 14px; font-size:13px; color:#C62828; margin-bottom:16px;
        }
        .countdown { font-weight:700; color:#E65100; }

        /* Hidden full code input for form submission */
        #otpFull { display:none; }
    </style>
</head>
<body>
<div class="card">
    <div class="brand">
        <div class="brand-icon">M</div>
        <h1>Verify Your Identity</h1>
        <p>Enter the 6-digit code sent to<br><strong>{{ $maskedEmail }}</strong></p>
    </div>

    @if(session('error'))
    <div class="error-msg"><i>⚠</i> {{ session('error') }}</div>
    @endif
    @if($errors->any())
    <div class="error-msg"><i>⚠</i> {{ $errors->first() }}</div>
    @endif
    @if(session('success'))
    <div class="info-box">✓ {{ session('success') }}</div>
    @endif

    <div class="info-box">
        A one-time login code was emailed to you. It expires in
        <span class="countdown" id="countdown">10:00</span>.
    </div>

    <form method="POST" action="{{ route('otp.verify') }}" id="otpForm">
        @csrf
        <input type="hidden" name="otp" id="otpFull">

        <div class="otp-inputs" id="otpInputs">
            <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off" id="d0">
            <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off" id="d1">
            <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off" id="d2">
            <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off" id="d3">
            <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off" id="d4">
            <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off" id="d5">
        </div>

        <button type="submit" class="btn-verify" id="btnVerify" disabled>
            Verify & Sign In
        </button>
    </form>

    <div class="resend-row">
        Didn't receive it?
        <form method="POST" action="{{ route('otp.resend') }}" style="display:inline;">
            @csrf
            <button type="submit" style="background:none;border:none;cursor:pointer;color:#00BCD4;font-size:13px;font-weight:600;padding:0;font-family:inherit;">
                Resend code
            </button>
        </form>
    </div>

    <div class="back-row">
        <a href="{{ route('login') }}">← Back to login</a>
    </div>
</div>

<script>
const inputs = Array.from(document.querySelectorAll('#otpInputs input'));
const btn    = document.getElementById('btnVerify');
const full   = document.getElementById('otpFull');

inputs.forEach((el, i) => {
    el.addEventListener('input', (e) => {
        // Only allow digits
        el.value = el.value.replace(/\D/g, '').slice(-1);
        el.classList.toggle('filled', el.value !== '');
        if (el.value && i < 5) inputs[i + 1].focus();
        syncFull();
    });

    el.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && !el.value && i > 0) {
            inputs[i - 1].focus();
            inputs[i - 1].value = '';
            inputs[i - 1].classList.remove('filled');
            syncFull();
        }
    });

    el.addEventListener('paste', (e) => {
        e.preventDefault();
        const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
        paste.split('').forEach((ch, idx) => {
            if (inputs[idx]) {
                inputs[idx].value = ch;
                inputs[idx].classList.add('filled');
            }
        });
        syncFull();
        if (paste.length === 6) btn.focus();
    });
});

function syncFull() {
    const code = inputs.map(el => el.value).join('');
    full.value = code;
    btn.disabled = code.length !== 6;
}

// Auto-focus first input
inputs[0].focus();

// Countdown timer — 10 minutes
let remaining = 10 * 60;
const countdown = document.getElementById('countdown');
const timer = setInterval(() => {
    remaining--;
    const m = String(Math.floor(remaining / 60)).padStart(2, '0');
    const s = String(remaining % 60).padStart(2, '0');
    countdown.textContent = `${m}:${s}`;
    if (remaining <= 0) {
        clearInterval(timer);
        countdown.textContent = 'expired';
        countdown.style.color = '#C62828';
        btn.disabled = true;
        btn.textContent = 'Code expired — resend to continue';
    }
}, 1000);
</script>
</body>
</html>
