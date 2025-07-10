<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Request</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #007bff;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }
        .content {
            margin-bottom: 30px;
        }
        .reset-button {
            display: inline-block;
            background-color: #007bff;
            color: #ffffff;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 20px 0;
            text-align: center;
        }
        .reset-button:hover {
            background-color: #0056b3;
        }
        .security-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #ffc107;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            font-size: 12px;
            color: #6c757d;
            text-align: center;
        }
        .warning {
            color: #dc3545;
            font-weight: bold;
        }
        .expiry-info {
            background-color: #e7f3ff;
            padding: 10px;
            border-radius: 4px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Analytics Hub</div>
            <h2>Password Reset Request</h2>
        </div>
        
        <div class="content">
            <p>Hello {{ $user->name }},</p>
            
            <p>We received a request to reset the password for your Analytics Hub account associated with <strong>{{ $user->email }}</strong>.</p>
            
            <p>If you made this request, click the button below to reset your password:</p>
            
            <div style="text-align: center;">
                <a href="{{ $resetUrl }}" class="reset-button">Reset My Password</a>
            </div>
            
            <div class="expiry-info">
                <strong>⏰ Important:</strong> This password reset link will expire in <strong>{{ $expiryMinutes }} minutes</strong> ({{ now()->addMinutes($expiryMinutes)->format('M j, Y \\a\\t g:i A') }}).
            </div>
            
            <div class="security-info">
                <strong>🔒 Security Information:</strong>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>This link can only be used once</li>
                    <li>If you don't use it within {{ $expiryMinutes }} minutes, you'll need to request a new one</li>
                    <li>For security, we don't store your password - you'll need to create a new one</li>
                </ul>
            </div>
            
            <p>If the button above doesn't work, you can copy and paste this link into your browser:</p>
            <p style="word-break: break-all; background-color: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace;">{{ $resetUrl }}</p>
            
            <div style="margin-top: 30px; padding: 15px; background-color: #fff3cd; border-radius: 4px;">
                <p class="warning">⚠️ If you didn't request this password reset:</p>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>You can safely ignore this email</li>
                    <li>Your password will remain unchanged</li>
                    <li>Consider changing your password if you suspect unauthorized access</li>
                    <li>Contact our support team if you have concerns</li>
                </ul>
            </div>
        </div>
        
        <div class="footer">
            <p><strong>Analytics Hub Security Team</strong></p>
            <p>This is an automated message. Please do not reply to this email.</p>
            <p>If you need assistance, please contact our support team.</p>
            <hr style="margin: 15px 0; border: none; border-top: 1px solid #dee2e6;">
            <p style="font-size: 11px;">Request Details:</p>
            <p style="font-size: 11px;">Time: {{ now()->format('M j, Y \\a\\t g:i:s A T') }}</p>
            <p style="font-size: 11px;">If this wasn't you, please secure your account immediately.</p>
        </div>
    </div>
</body>
</html>