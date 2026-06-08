{{-- resources/views/auth/login.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Mweela Cash Capital - Login to access your microfinance account. Quick loans, business financing, and financial services in Kenya.">
    <meta name="robots" content="noindex, nofollow">
    <title>Login - Mweela Cash Capital</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #F5F7FA;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            width: 100%;
            max-width: 400px;
        }
        @media (max-width: 480px) {
            body { align-items: flex-start; padding: 24px 0; background: white; }
            .login-card {
                padding: 32px 20px;
                border-radius: 0;
                box-shadow: none;
                min-height: 100vh;
            }
        }
        .brand {
            text-align: center;
            margin-bottom: 30px;
        }
        .brand-icon {
            width: 60px;
            height: 60px;
            background: #00BCD4;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            font-weight: 700;
            margin: 0 auto 15px;
        }
        .brand h1 {
            font-size: 24px;
            color: #2C3E50;
        }
        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #2C3E50;
            margin-bottom: 6px;
        }
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #E8ECF1;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }
        .form-control:focus {
            border-color: #00BCD4;
        }
        .btn-login {
            width: 100%;
            padding: 14px;
            background: #00BCD4;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-login:hover {
            background: #00ACC1;
        }
    </style>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "FinancialService",
      "name": "Mweela Cash Capital",
      "description": "Trusted microfinance loans and financial services in Kenya. Quick business loans, personal loans, and SME financing.",
      "url": "https://mweelacredit.co.ke",
      "logo": "https://mweelacredit.co.ke/favicon.ico",
      "address": {
        "@type": "PostalAddress",
        "addressCountry": "KE",
        "addressLocality": "Mutomo",
        "addressRegion": "Kitui"
      },
      "contactPoint": {
        "@type": "ContactPoint",
        "telephone": "+254700000001",
        "contactType": "customer service",
        "availableLanguage": ["English", "Swahili"]
      },
      "sameAs": [
        "https://mweelacredit.co.ke"
      ]
    }
    </script>
</head>
<body>
    <div class="login-card">
        <div class="brand">
            <div class="brand-icon">M</div>
            <h1>Mweela Cash Capital</h1>
            <p style="color: #7F8C8D; font-size: 14px; margin-top: 5px;">SACCO Microfinance System</p>
        </div>

        @if($errors->any())
            <div class="flash-error">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="Enter your email" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn-login">Sign In</button>
        </form>
    </div>
</body>
</html>