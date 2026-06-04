<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #1565C0; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border: 1px solid #e0e0e0; }
        .credentials { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #1565C0; }
        .credentials p { margin: 8px 0; }
        .label { font-weight: bold; color: #555; }
        .warning { background: #FFF3E0; padding: 15px; border-radius: 8px; color: #E65100; margin-top: 20px; }
        .footer { text-align: center; color: #999; font-size: 12px; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Mweela Cash Capital</h2>
        </div>
        <div class="content">
            <p>Hello <strong>{{ $name }}</strong>,</p>

            @if($isReset)
                <p>Your password has been reset by the administrator. Below are your updated login credentials:</p>
            @else
                <p>Welcome to Mweela Cash Capital! Your account has been created. Below are your login credentials:</p>
            @endif

            <div class="credentials">
                <p><span class="label">Email:</span> {{ $email }}</p>
                <p><span class="label">Password:</span> <code style="background:#f5f5f5;padding:4px 8px;border-radius:4px;font-size:14px;">{{ $password }}</code></p>
                <p><span class="label">Login URL:</span> <a href="{{ url('/login') }}">{{ url('/login') }}</a></p>
            </div>

            <div class="warning">
                <strong>⚠️ Security Notice:</strong> Please change your password immediately after your first login.
            </div>

            <p>If you have any questions, please contact your system administrator.</p>
        </div>
        <div class="footer">
            <p>© {{ date('Y') }} Mweela Cash Capital. All rights reserved.</p>
            <p>This is an automated message. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
