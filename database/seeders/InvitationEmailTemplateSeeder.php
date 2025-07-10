<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\EmailTemplate;
use Illuminate\Support\Str;

/**
 * InvitationEmailTemplateSeeder
 * 
 * Creates the user invitation email template for the Analytics Hub system.
 * This template is used when administrators invite new users to the platform.
 */
class InvitationEmailTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates the invitation email template with proper configuration
     * for sending user invitations with temporary passwords.
     */
    public function run(): void
    {
        // Check if invitation template already exists
        $existingTemplate = EmailTemplate::where('name', 'user_invitation')
            ->where('is_system_template', true)
            ->first();

        if ($existingTemplate) {
            $this->command->info('Invitation email template already exists. Skipping creation.');
            return;
        }

        // Create the invitation email template
        $template = EmailTemplate::create([
            'id' => Str::uuid(),
            'name' => 'user_invitation',
            'display_name' => 'User Invitation',
            'description' => 'Email template for inviting new users to the Analytics Hub platform',
            'subject' => 'Welcome to Analytics Hub - Your Account Invitation',
            'body_html' => $this->getHtmlTemplate(),
            'body_text' => $this->getTextTemplate(),
            'category' => EmailTemplate::CATEGORY_AUTHENTICATION,
            'type' => EmailTemplate::TYPE_SYSTEM,
            'event_trigger' => 'user_invitation',
            'is_active' => true,
            'is_system_template' => true,
            'variables' => [
                'user_name' => 'Recipient\'s full name',
                'user_email' => 'Recipient\'s email address',
                'temp_password' => 'Temporary password for first login',
                'login_url' => 'Application login page URL',
                'current_date' => 'Current date and time',
                'company_name' => 'Organization name',
                'admin_name' => 'Administrator who sent the invitation',
                'admin_email' => 'Administrator\'s email address'
            ],
            'default_data' => [
                'company_name' => 'Analytics Hub',
                'login_url' => config('app.url') . '/login'
            ],
            'from_email' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
            'language' => 'en',
            'priority' => EmailTemplate::PRIORITY_HIGH,
            'version' => '1.0',
            'is_current_version' => true,
            'usage_count' => 0,
            'created_by' => null, // System template
            'updated_by' => null, // System template
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $this->command->info('Invitation email template created successfully with ID: ' . $template->id);
    }

    /**
     * Get the HTML template content
     * 
     * @return string
     */
    private function getHtmlTemplate(): string
    {
        return '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{company_name}}</title>
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
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
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
            color: #0E0E44;
            margin-bottom: 10px;
        }
        .welcome-title {
            color: #0E0E44;
            font-size: 28px;
            margin-bottom: 10px;
        }
        .content {
            margin-bottom: 30px;
        }
        .credentials-box {
            background-color: #f8f9fa;
            border: 2px solid #FF7A00;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        .credentials-title {
            color: #0E0E44;
            font-weight: bold;
            margin-bottom: 15px;
            font-size: 18px;
        }
        .credential-item {
            margin: 10px 0;
            font-size: 16px;
        }
        .credential-label {
            font-weight: bold;
            color: #0E0E44;
        }
        .credential-value {
            color: #FF7A00;
            font-family: monospace;
            font-size: 18px;
            font-weight: bold;
        }
        .login-button {
            display: inline-block;
            background-color: #FF7A00;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 20px 0;
            text-align: center;
        }
        .login-button:hover {
            background-color: #e66a00;
        }
        .instructions {
            background-color: #e8f4fd;
            border-left: 4px solid #0E0E44;
            padding: 15px;
            margin: 20px 0;
        }
        .instructions h3 {
            color: #0E0E44;
            margin-top: 0;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 14px;
        }
        .contact-info {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">{{company_name}}</div>
            <h1 class="welcome-title">Welcome to Analytics Hub!</h1>
        </div>
        
        <div class="content">
            <p>Dear <strong>{{user_name}}</strong>,</p>
            
            <p>You have been invited to join <strong>{{company_name}}</strong> Analytics Hub platform. Your account has been created and you can now access our analytics dashboard.</p>
            
            <div class="credentials-box">
                <div class="credentials-title">Your Login Credentials</div>
                <div class="credential-item">
                    <span class="credential-label">Email:</span><br>
                    <span class="credential-value">{{user_email}}</span>
                </div>
                <div class="credential-item">
                    <span class="credential-label">Temporary Password:</span><br>
                    <span class="credential-value">{{temp_password}}</span>
                </div>
            </div>
            
            <div class="instructions">
                <h3>First Login Instructions:</h3>
                <ol>
                    <li>Click the login button below or visit: <a href="{{login_url}}">{{login_url}}</a></li>
                    <li>Enter your email and temporary password</li>
                    <li>You will be required to change your password on first login</li>
                    <li>Accept the Terms & Conditions to complete your account setup</li>
                    <li>Start exploring the Analytics Hub dashboard</li>
                </ol>
            </div>
            
            <div style="text-align: center;">
                <a href="{{login_url}}" class="login-button">Login to Analytics Hub</a>
            </div>
            
            <div class="warning">
                <strong>Security Notice:</strong> This temporary password is valid for your first login only. You will be required to create a new secure password that meets our security requirements (minimum 8 characters with uppercase, lowercase, numbers, and special characters).
            </div>
            
            <div class="contact-info">
                <h3>Need Help?</h3>
                <p>If you have any questions or need assistance, please contact your administrator:</p>
                <p><strong>{{admin_name}}</strong><br>
                Email: <a href="mailto:{{admin_email}}">{{admin_email}}</a></p>
            </div>
        </div>
        
        <div class="footer">
            <p>This invitation was sent on {{current_date}}</p>
            <p>© {{company_name}} - Analytics Hub Platform</p>
            <p><em>This is an automated message. Please do not reply to this email.</em></p>
        </div>
    </div>
</body>
</html>
        ';
    }

    /**
     * Get the plain text template content
     * 
     * @return string
     */
    private function getTextTemplate(): string
    {
        return '
Welcome to {{company_name}} Analytics Hub!

Dear {{user_name}},

You have been invited to join {{company_name}} Analytics Hub platform. Your account has been created and you can now access our analytics dashboard.

YOUR LOGIN CREDENTIALS:
========================
Email: {{user_email}}
Temporary Password: {{temp_password}}

FIRST LOGIN INSTRUCTIONS:
========================
1. Visit: {{login_url}}
2. Enter your email and temporary password
3. You will be required to change your password on first login
4. Accept the Terms & Conditions to complete your account setup
5. Start exploring the Analytics Hub dashboard

SECURITY NOTICE:
===============
This temporary password is valid for your first login only. You will be required to create a new secure password that meets our security requirements (minimum 8 characters with uppercase, lowercase, numbers, and special characters).

NEED HELP?
==========
If you have any questions or need assistance, please contact your administrator:
{{admin_name}}
Email: {{admin_email}}

This invitation was sent on {{current_date}}

© {{company_name}} - Analytics Hub Platform
This is an automated message. Please do not reply to this email.
        ';
    }
}