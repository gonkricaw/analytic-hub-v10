<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $templateData['subject'] ?? 'Welcome to Analytics Hub' }}</title>
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
        .template-content {
            padding: 30px;
        }
        .resend-notice {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }
        .custom-message {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 6px 6px 0;
        }
        .custom-message h4 {
            margin-top: 0;
            color: #1976d2;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
            font-size: 14px;
            color: #6c757d;
        }
        /* Ensure template content styles don't conflict */
        .template-content * {
            max-width: 100%;
        }
        .template-content img {
            height: auto;
        }
        .template-content table {
            width: 100%;
            border-collapse: collapse;
        }
        @media (max-width: 600px) {
            body {
                padding: 10px;
            }
            .template-content {
                padding: 20px 15px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="template-content">
            @if($isResend)
                <div class="resend-notice">
                    📧 Resent Invitation - This is a resent invitation. Your previous invitation may have expired or been missed.
                </div>
            @endif

            @if($customMessage)
                <div class="custom-message">
                    <h4>📝 Message from {{ $sender->first_name }}:</h4>
                    <p>{{ $customMessage }}</p>
                </div>
            @endif

            <!-- Render the custom HTML template content -->
            {!! $htmlContent !!}
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