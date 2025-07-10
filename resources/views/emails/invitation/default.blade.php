<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Analytics Hub</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 300;
        }
        .content {
            padding: 40px 30px;
        }
        .welcome-message {
            font-size: 18px;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        .credentials-box {
            background-color: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            padding: 20px;
            margin: 25px 0;
            text-align: center;
        }
        .credentials-box h3 {
            margin-top: 0;
            color: #495057;
            font-size: 16px;
        }
        .credential-item {
            margin: 10px 0;
            font-family: 'Courier New', monospace;
        }
        .credential-label {
            font-weight: bold;
            color: #6c757d;
            display: inline-block;
            width: 100px;
            text-align: left;
        }
        .credential-value {
            background-color: #ffffff;
            padding: 8px 12px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-weight: bold;
            color: #495057;
        }
        .login-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin: 20px 0;
            transition: transform 0.2s;
        }
        .login-button:hover {
            transform: translateY(-2px);
            text-decoration: none;
            color: white;
        }
        .instructions {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin: 20px 0;
        }
        .instructions h4 {
            margin-top: 0;
            color: #1976d2;
        }
        .custom-message {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        .custom-message h4 {
            margin-top: 0;
            color: #856404;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
            font-size: 14px;
            color: #6c757d;
        }
        .resend-notice {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        .security-note {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            font-size: 14px;
        }
        .security-note h4 {
            margin-top: 0;
            color: #721c24;
        }
        @media (max-width: 600px) {
            body {
                padding: 10px;
            }
            .content {
                padding: 20px 15px;
            }
            .header {
                padding: 20px 15px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <h1>{{ config('app.name', 'Analytics Hub') }}</h1>
            <p>Welcome to our platform</p>
        </div>

        <!-- Content -->
        <div class="content">
            @if($isResend)
                <div class="resend-notice">
                    <strong>📧 Resent Invitation</strong><br>
                    This is a resent invitation. Your previous invitation may have expired or been missed.
                </div>
            @endif

            <div class="welcome-message">
                <strong>Hello {{ $user->first_name }},</strong>
            </div>

            <p>You have been invited to join <strong>{{ config('app.name', 'Analytics Hub') }}</strong> by {{ $sender->full_name }}. We're excited to have you on board!</p>

            @if($customMessage)
                <div class="custom-message">
                    <h4>📝 Message from {{ $sender->first_name }}:</h4>
                    <p>{{ $customMessage }}</p>
                </div>
            @endif

            <!-- Login Credentials -->
            <div class="credentials-box">
                <h3>🔐 Your Login Credentials</h3>
                <div class="credential-item">
                    <span class="credential-label">Email:</span>
                    <span class="credential-value">{{ $user->email }}</span>
                </div>
                <div class="credential-item">
                    <span class="credential-label">Username:</span>
                    <span class="credential-value">{{ $user->username }}</span>
                </div>
                <div class="credential-item">
                    <span class="credential-label">Temporary Password:</span>
                    <span class="credential-value">{{ $temporaryPassword }}</span>
                </div>
            </div>

            <!-- Login Button -->
            <div style="text-align: center;">
                <a href="{{ route('login') }}" class="login-button">
                    🚀 Access Your Account
                </a>
            </div>

            <!-- Instructions -->
            <div class="instructions">
                <h4>📋 Getting Started:</h4>
                <ol>
                    <li>Click the "Access Your Account" button above or visit: <a href="{{ route('login') }}">{{ route('login') }}</a></li>
                    <li>Log in using your email/username and the temporary password provided</li>
                    <li>You'll be prompted to create a new secure password on your first login</li>
                    <li>Complete your profile setup to get started</li>
                </ol>
            </div>

            <!-- Security Notice -->
            <div class="security-note">
                <h4>🔒 Important Security Information:</h4>
                <ul>
                    <li><strong>Change your password immediately</strong> after your first login</li>
                    <li>This temporary password will expire in <strong>7 days</strong></li>
                    <li>Never share your login credentials with anyone</li>
                    <li>If you didn't expect this invitation, please contact our support team</li>
                </ul>
            </div>

            <p>If you have any questions or need assistance, please don't hesitate to contact {{ $sender->full_name }} at <a href="mailto:{{ $sender->email }}">{{ $sender->email }}</a> or our support team.</p>

            <p>Welcome aboard!</p>
            <p><strong>The {{ config('app.name', 'Analytics Hub') }} Team</strong></p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>This invitation was sent by {{ $sender->full_name }} ({{ $sender->email }}) on {{ now()->format('F j, Y \a\t g:i A') }}.</p>
            <p>© {{ date('Y') }} {{ config('app.name', 'Analytics Hub') }}. All rights reserved.</p>
            @if(config('app.url'))
                <p><a href="{{ config('app.url') }}">{{ config('app.url') }}</a></p>
            @endif
        </div>
    </div>
</body>
</html>