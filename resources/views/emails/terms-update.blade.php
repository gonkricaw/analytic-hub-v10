<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Conditions Updated</title>
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
            border-bottom: 2px solid #FF7A00;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #FF7A00;
            margin-bottom: 10px;
        }
        .content {
            margin-bottom: 30px;
        }
        .action-button {
            display: inline-block;
            background-color: #FF7A00;
            color: #ffffff;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 20px 0;
            text-align: center;
        }
        .action-button:hover {
            background-color: #e66a00;
        }
        .update-info {
            background-color: #fff3e0;
            padding: 15px;
            border-left: 4px solid #FF7A00;
            margin: 20px 0;
        }
        .version-info {
            background-color: #e7f3ff;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            font-size: 12px;
            color: #6c757d;
            text-align: center;
        }
        .important {
            color: #dc3545;
            font-weight: bold;
        }
        .highlight {
            background-color: #fffbf0;
            padding: 10px;
            border-radius: 4px;
            margin: 15px 0;
            border: 1px solid #ffd54f;
        }
        .changes-list {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
        }
        .changes-list ul {
            margin: 0;
            padding-left: 20px;
        }
        .changes-list li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Analytics Hub</div>
            <h2>📋 Terms & Conditions Updated</h2>
        </div>
        
        <div class="content">
            <p>Hello {{ $user->name }},</p>
            
            <p>We are writing to inform you that our Terms & Conditions have been updated. As a valued user of Analytics Hub, we want to ensure you are aware of these important changes.</p>
            
            <div class="version-info">
                <strong>📄 Version Information:</strong><br>
                Previous Version: {{ $previousVersion ?? 'N/A' }}<br>
                New Version: <strong>{{ $newVersion }}</strong><br>
                Effective Date: {{ now()->format('F j, Y') }}
            </div>
            
            @if(isset($updateReason) && $updateReason)
            <div class="update-info">
                <strong>📝 Reason for Update:</strong><br>
                {{ $updateReason }}
            </div>
            @endif
            
            @if(isset($keyChanges) && is_array($keyChanges) && count($keyChanges) > 0)
            <div class="changes-list">
                <strong>🔍 Key Changes Include:</strong>
                <ul>
                    @foreach($keyChanges as $change)
                    <li>{{ $change }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            
            <div class="highlight">
                <p class="important">⚠️ Action Required:</p>
                <p>To continue using Analytics Hub, you must review and accept the updated Terms & Conditions. You will be prompted to accept these terms the next time you log in to your account.</p>
            </div>
            
            <p>You can review the updated terms by clicking the button below or by logging into your account:</p>
            
            <div style="text-align: center;">
                <a href="{{ $termsUrl }}" class="action-button">Review Updated Terms</a>
            </div>
            
            <div class="update-info">
                <strong>🔒 Your Privacy Matters:</strong><br>
                These updates are designed to better protect your privacy and improve your experience with our platform. We remain committed to safeguarding your personal information and providing transparent communication about our policies.
            </div>
            
            <p>If you have any questions about these changes or need assistance, please don't hesitate to contact our support team.</p>
            
            <p>Thank you for your continued trust in Analytics Hub.</p>
            
            <p>Best regards,<br>
            <strong>The Analytics Hub Team</strong></p>
        </div>
        
        <div class="footer">
            <p>This is an automated notification from Analytics Hub.</p>
            <p>If you did not expect this email, please contact our support team immediately.</p>
            <p>&copy; {{ date('Y') }} Analytics Hub. All rights reserved.</p>
            
            <p style="margin-top: 15px; font-size: 11px;">
                <strong>Note:</strong> This email was sent to {{ $user->email }} because you have an active account with Analytics Hub. 
                You cannot unsubscribe from important account and security notifications.
            </p>
        </div>
    </div>
</body>
</html>