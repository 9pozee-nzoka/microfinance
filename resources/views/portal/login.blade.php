<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Portal — GetCash Capital</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0F1923 0%, #1A2E3B 50%, #0F1923 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-wrapper {
            display: flex;
            width: 100%;
            max-width: 900px;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(0,0,0,0.4);
        }

        /* Left panel */
        .login-left {
            flex: 1;
            background: linear-gradient(160deg, #00BCD4 0%, #0097A7 100%);
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            color: white;
        }

        .login-left .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .login-left .brand-icon {
            width: 44px; height: 44px;
            background: rgba(255,255,255,0.2);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; font-weight: 700;
        }

        .login-left .brand-name {
            font-size: 20px;
            font-weight: 700;
        }

        .login-left .brand-sub {
            font-size: 12px;
            opacity: 0.8;
        }

        .login-left .hero-text {
            margin-top: 40px;
        }

        .login-left .hero-text h2 {
            font-size: 28px;
            font-weight: 700;
            line-height: 1.3;
            margin-bottom: 16px;
        }

        .login-left .hero-text p {
            font-size: 14px;
            opacity: 0.85;
            line-height: 1.7;
        }

        .login-left .features {
            margin-top: 32px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .login-left .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 13px;
            opacity: 0.9;
        }

        .login-left .feature-item i {
            width: 28px; height: 28px;
            background: rgba(255,255,255,0.2);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px;
            flex-shrink: 0;
        }

        .login-left .footer-note {
            font-size: 12px;
            opacity: 0.6;
        }

        /* Right panel */
        .login-right {
            width: 400px;
            background: white;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-right h1 {
            font-size: 24px;
            font-weight: 700;
            color: #1A2332;
            margin-bottom: 6px;
        }

        .login-right .subtitle {
            font-size: 14px;
            color: #6B7A8D;
            margin-bottom: 32px;
        }

        .form-group { margin-bottom: 20px; }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #1A2332;
            margin-bottom: 7px;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #9AA5B4;
            font-size: 14px;
        }

        .input-wrap input {
            width: 100%;
            padding: 12px 14px 12px 40px;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            outline: none;
            transition: border-color 0.15s;
        }

        .input-wrap input:focus {
            border-color: #00BCD4;
            box-shadow: 0 0 0 3px rgba(0,188,212,0.1);
        }

        .remember-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 24px;
            font-size: 13px;
            color: #6B7A8D;
        }

        .remember-row input[type="checkbox"] {
            accent-color: #00BCD4;
            width: 15px; height: 15px;
        }

        .btn-login {
            width: 100%;
            padding: 13px;
            background: #00BCD4;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s;
            font-family: inherit;
        }

        .btn-login:hover { background: #00ACC1; }

        .error-box {
            background: #FFEBEE;
            color: #C62828;
            border: 1px solid #FFCDD2;
            padding: 12px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .staff-link {
            margin-top: 24px;
            text-align: center;
            font-size: 12px;
            color: #9AA5B4;
        }

        .staff-link a {
            color: #00BCD4;
            text-decoration: none;
            font-weight: 600;
        }

        @media (max-width: 700px) {
            .login-left { display: none; }
            .login-right { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        {{-- Left panel --}}
        <div class="login-left">
            <div class="brand">
                <div class="brand-icon">G</div>
                <div>
                    <div class="brand-name">GetCash Capital</div>
                    <div class="brand-sub">SACCO Microfinance</div>
                </div>
            </div>

            <div class="hero-text">
                <h2>Manage your loans from anywhere</h2>
                <p>Access your account 24/7, track your repayment progress, and make payments directly from your phone or computer.</p>

                <div class="features">
                    <div class="feature-item">
                        <i class="fas fa-chart-line"></i>
                        <span>View your loan balance and repayment schedule</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-money-bill-wave"></i>
                        <span>Submit loan repayments via M-Pesa or bank</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-history"></i>
                        <span>Full transaction history at your fingertips</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-star"></i>
                        <span>Track your credit score progress</span>
                    </div>
                </div>
            </div>

            <div class="footer-note">
                Your account credentials were provided when your account was activated.
            </div>
        </div>

        {{-- Right panel --}}
        <div class="login-right">
            <h1>Welcome back</h1>
            <p class="subtitle">Sign in to your customer portal</p>

            @if($errors->any())
                <div class="error-box">
                    <i class="fas fa-exclamation-circle"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('portal.login.post') }}">
                @csrf

                <div class="form-group">
                    <label>Email Address</label>
                    <div class="input-wrap">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" value="{{ old('email') }}"
                               placeholder="your@email.com" required autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div class="input-wrap">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="Enter your password" required>
                    </div>
                </div>

                <div class="remember-row">
                    <input type="checkbox" name="remember" id="remember">
                    <label for="remember" style="cursor:pointer;">Keep me signed in</label>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>

            <div class="staff-link">
                Are you a staff member?
                <a href="{{ route('login') }}">Use the staff portal →</a>
            </div>
        </div>
    </div>
</body>
</html>
