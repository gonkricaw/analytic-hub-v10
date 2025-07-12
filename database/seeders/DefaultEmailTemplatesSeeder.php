<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\EmailTemplate;
use Illuminate\Support\Str;

/**
 * DefaultEmailTemplatesSeeder
 * 
 * Creates all default email templates for the Analytics Hub system.
 * This includes system templates for various email notifications and communications.
 */
class DefaultEmailTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates all default email templates required by the system
     */
    public function run(): void
    {
        $this->command->info('Creating default email templates...');
        
        $this->createPasswordResetTemplate();
        $this->createSuspensionNoticeTemplate();
        $this->createAnnouncementTemplate();
        $this->createWelcomeTemplate();
        $this->createAccountActivationTemplate();
        $this->createPasswordExpiryTemplate();
        $this->createSecurityAlertTemplate();
        $this->createSystemMaintenanceTemplate();
        
        $this->command->info('✅ All default email templates created successfully.');
    }

    /**
     * Create password reset email template
     */
    private function createPasswordResetTemplate(): void
    {
        $this->createTemplateIfNotExists([
            'name' => 'password_reset',
            'display_name' => 'Password Reset',
            'description' => 'Email template for password reset requests',
            'subject' => 'Reset Your {{company_name}} Password',
            'body_html' => $this->getPasswordResetHtmlTemplate(),
            'body_text' => $this->getPasswordResetTextTemplate(),
            'category' => EmailTemplate::CATEGORY_AUTHENTICATION,
            'type' => EmailTemplate::TYPE_SYSTEM,
            'event_trigger' => 'password.reset',
            'variables' => [
                'user_name' => 'User\'s full name',
                'user_email' => 'User\'s email address',
                'reset_link' => 'Password reset link',
                'reset_token' => 'Password reset token',
                'company_name' => 'Organization name',
                'expiry_time' => 'Link expiry time',
                'current_date' => 'Current date and time',
                'support_email' => 'Support email address'
            ]
        ]);
    }

    /**
     * Create suspension notice email template
     */
    private function createSuspensionNoticeTemplate(): void
    {
        $this->createTemplateIfNotExists([
            'name' => 'suspension_notice',
            'display_name' => 'Account Suspension Notice',
            'description' => 'Email template for account suspension notifications',
            'subject' => 'Important: Your {{company_name}} Account Has Been Suspended',
            'body_html' => $this->getSuspensionNoticeHtmlTemplate(),
            'body_text' => $this->getSuspensionNoticeTextTemplate(),
            'category' => EmailTemplate::CATEGORY_NOTIFICATION,
            'type' => EmailTemplate::TYPE_SYSTEM,
            'event_trigger' => 'user.suspended',
            'variables' => [
                'user_name' => 'User\'s full name',
                'user_email' => 'User\'s email address',
                'suspension_reason' => 'Reason for suspension',
                'suspension_date' => 'Date of suspension',
                'company_name' => 'Organization name',
                'admin_name' => 'Administrator name',
                'admin_email' => 'Administrator email',
                'appeal_process' => 'Appeal process information',
                'support_email' => 'Support email address'
            ]
        ]);
    }

    /**
     * Create announcement email template
     */
    private function createAnnouncementTemplate(): void
    {
        $this->createTemplateIfNotExists([
            'name' => 'announcement',
            'display_name' => 'System Announcement',
            'description' => 'Email template for system announcements and updates',
            'subject' => '{{announcement_title}} - {{company_name}}',
            'body_html' => $this->getAnnouncementHtmlTemplate(),
            'body_text' => $this->getAnnouncementTextTemplate(),
            'category' => EmailTemplate::CATEGORY_NOTIFICATION,
            'type' => EmailTemplate::TYPE_SYSTEM,
            'event_trigger' => 'announcement.send',
            'variables' => [
                'user_name' => 'User\'s full name',
                'announcement_title' => 'Announcement title',
                'announcement_content' => 'Announcement content',
                'announcement_date' => 'Announcement date',
                'company_name' => 'Organization name',
                'admin_name' => 'Administrator name',
                'priority_level' => 'Announcement priority level',
                'action_required' => 'Whether action is required',
                'deadline' => 'Action deadline if applicable'
            ]
        ]);
    }

    /**
     * Create welcome email template
     */
    private function createWelcomeTemplate(): void
    {
        $this->createTemplateIfNotExists([
            'name' => 'welcome',
            'display_name' => 'Welcome Email',
            'description' => 'Welcome email for new users after account activation',
            'subject' => 'Welcome to {{company_name}} Analytics Hub!',
            'body_html' => $this->getWelcomeHtmlTemplate(),
            'body_text' => $this->getWelcomeTextTemplate(),
            'category' => EmailTemplate::CATEGORY_AUTHENTICATION,
            'type' => EmailTemplate::TYPE_SYSTEM,
            'event_trigger' => 'user.welcomed',
            'variables' => [
                'user_name' => 'User\'s full name',
                'user_email' => 'User\'s email address',
                'company_name' => 'Organization name',
                'dashboard_url' => 'Dashboard URL',
                'getting_started_url' => 'Getting started guide URL',
                'support_email' => 'Support email address',
                'features_list' => 'List of available features'
            ]
        ]);
    }

    /**
     * Create account activation email template
     */
    private function createAccountActivationTemplate(): void
    {
        $this->createTemplateIfNotExists([
            'name' => 'account_activation',
            'display_name' => 'Account Activation',
            'description' => 'Email template for account activation notifications',
            'subject' => 'Activate Your {{company_name}} Account',
            'body_html' => $this->getAccountActivationHtmlTemplate(),
            'body_text' => $this->getAccountActivationTextTemplate(),
            'category' => EmailTemplate::CATEGORY_AUTHENTICATION,
            'type' => EmailTemplate::TYPE_SYSTEM,
            'event_trigger' => 'user.activation_required',
            'variables' => [
                'user_name' => 'User\'s full name',
                'user_email' => 'User\'s email address',
                'activation_link' => 'Account activation link',
                'activation_token' => 'Activation token',
                'company_name' => 'Organization name',
                'expiry_time' => 'Link expiry time',
                'support_email' => 'Support email address'
            ]
        ]);
    }

    /**
     * Create password expiry email template
     */
    private function createPasswordExpiryTemplate(): void
    {
        $this->createTemplateIfNotExists([
            'name' => 'password_expiry',
            'display_name' => 'Password Expiry Notice',
            'description' => 'Email template for password expiry warnings',
            'subject' => 'Your {{company_name}} Password Will Expire Soon',
            'body_html' => $this->getPasswordExpiryHtmlTemplate(),
            'body_text' => $this->getPasswordExpiryTextTemplate(),
            'category' => EmailTemplate::CATEGORY_NOTIFICATION,
            'type' => EmailTemplate::TYPE_SYSTEM,
            'event_trigger' => 'password.expiry_warning',
            'variables' => [
                'user_name' => 'User\'s full name',
                'user_email' => 'User\'s email address',
                'expiry_date' => 'Password expiry date',
                'days_remaining' => 'Days until expiry',
                'change_password_url' => 'Change password URL',
                'company_name' => 'Organization name',
                'support_email' => 'Support email address'
            ]
        ]);
    }

    /**
     * Create security alert email template
     */
    private function createSecurityAlertTemplate(): void
    {
        $this->createTemplateIfNotExists([
            'name' => 'security_alert',
            'display_name' => 'Security Alert',
            'description' => 'Email template for security-related notifications',
            'subject' => 'Security Alert: {{alert_type}} - {{company_name}}',
            'body_html' => $this->getSecurityAlertHtmlTemplate(),
            'body_text' => $this->getSecurityAlertTextTemplate(),
            'category' => EmailTemplate::CATEGORY_NOTIFICATION,
            'type' => EmailTemplate::TYPE_SYSTEM,
            'event_trigger' => 'security.alert',
            'variables' => [
                'user_name' => 'User\'s full name',
                'user_email' => 'User\'s email address',
                'alert_type' => 'Type of security alert',
                'alert_message' => 'Security alert message',
                'alert_date' => 'Date of security event',
                'ip_address' => 'IP address involved',
                'location' => 'Geographic location',
                'company_name' => 'Organization name',
                'support_email' => 'Support email address',
                'action_required' => 'Required action from user'
            ]
        ]);
    }

    /**
     * Create system maintenance email template
     */
    private function createSystemMaintenanceTemplate(): void
    {
        $this->createTemplateIfNotExists([
            'name' => 'system_maintenance',
            'display_name' => 'System Maintenance Notice',
            'description' => 'Email template for system maintenance notifications',
            'subject' => 'Scheduled Maintenance: {{company_name}} Analytics Hub',
            'body_html' => $this->getSystemMaintenanceHtmlTemplate(),
            'body_text' => $this->getSystemMaintenanceTextTemplate(),
            'category' => EmailTemplate::CATEGORY_NOTIFICATION,
            'type' => EmailTemplate::TYPE_SYSTEM,
            'event_trigger' => 'system.maintenance',
            'variables' => [
                'user_name' => 'User\'s full name',
                'maintenance_title' => 'Maintenance title',
                'maintenance_description' => 'Maintenance description',
                'start_time' => 'Maintenance start time',
                'end_time' => 'Maintenance end time',
                'duration' => 'Expected duration',
                'affected_services' => 'List of affected services',
                'company_name' => 'Organization name',
                'support_email' => 'Support email address'
            ]
        ]);
    }

    /**
     * Create template if it doesn't exist
     */
    private function createTemplateIfNotExists(array $templateData): void
    {
        $existingTemplate = EmailTemplate::where('name', $templateData['name'])
            ->where('is_system_template', true)
            ->first();

        if ($existingTemplate) {
            $this->command->info("Template '{$templateData['display_name']}' already exists. Skipping.");
            return;
        }

        $template = EmailTemplate::create(array_merge($templateData, [
            'id' => Str::uuid(),
            'is_active' => true,
            'is_system_template' => true,
            'default_data' => [
                'company_name' => 'Analytics Hub',
                'support_email' => config('mail.from.address')
            ],
            'from_email' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
            'language' => 'en',
            'priority' => EmailTemplate::PRIORITY_NORMAL,
            'version' => '1.0',
            'is_current_version' => true,
            'usage_count' => 0,
            'created_by' => null,
            'updated_by' => null,
            'created_at' => now(),
            'updated_at' => now()
        ]));

        $this->command->info("✅ Template '{$templateData['display_name']}' created successfully.");
    }

    // HTML Template Methods
    private function getPasswordResetHtmlTemplate(): string
    {
        return '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset - {{company_name}}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f4f4f4; }
        .container { background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #FF7A00; }
        .logo { font-size: 24px; font-weight: bold; color: #0E0E44; margin-bottom: 10px; }
        .title { color: #0E0E44; font-size: 28px; margin-bottom: 10px; }
        .reset-button { display: inline-block; background-color: #FF7A00; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
        .warning { background-color: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">{{company_name}}</div>
            <h1 class="title">Password Reset Request</h1>
        </div>
        <div class="content">
            <p>Hello <strong>{{user_name}}</strong>,</p>
            <p>We received a request to reset your password for your {{company_name}} account.</p>
            <div style="text-align: center;">
                <a href="{{reset_link}}" class="reset-button">Reset Your Password</a>
            </div>
            <div class="warning">
                <strong>Security Notice:</strong> This link will expire in {{expiry_time}}. If you did not request this password reset, please ignore this email or contact support.
            </div>
            <p>If the button above doesn\'t work, copy and paste this link into your browser:</p>
            <p><a href="{{reset_link}}">{{reset_link}}</a></p>
        </div>
        <div class="footer">
            <p>© {{company_name}} - Analytics Hub Platform</p>
            <p>Need help? Contact us at <a href="mailto:{{support_email}}">{{support_email}}</a></p>
        </div>
    </div>
</body>
</html>
        ';
    }

    private function getPasswordResetTextTemplate(): string
    {
        return '
Password Reset Request - {{company_name}}

Hello {{user_name}},

We received a request to reset your password for your {{company_name}} account.

To reset your password, please visit the following link:
{{reset_link}}

SECURITY NOTICE:
This link will expire in {{expiry_time}}. If you did not request this password reset, please ignore this email or contact support.

Need help? Contact us at {{support_email}}

© {{company_name}} - Analytics Hub Platform
        ';
    }

    private function getSuspensionNoticeHtmlTemplate(): string
    {
        return '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Suspension Notice - {{company_name}}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f4f4f4; }
        .container { background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #e74c3c; }
        .logo { font-size: 24px; font-weight: bold; color: #0E0E44; margin-bottom: 10px; }
        .title { color: #e74c3c; font-size: 28px; margin-bottom: 10px; }
        .suspension-box { background-color: #fadbd8; border: 2px solid #e74c3c; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">{{company_name}}</div>
            <h1 class="title">Account Suspension Notice</h1>
        </div>
        <div class="content">
            <p>Dear <strong>{{user_name}}</strong>,</p>
            <p>We regret to inform you that your {{company_name}} account has been suspended.</p>
            <div class="suspension-box">
                <h3>Suspension Details:</h3>
                <p><strong>Date:</strong> {{suspension_date}}</p>
                <p><strong>Reason:</strong> {{suspension_reason}}</p>
                <p><strong>Administrator:</strong> {{admin_name}}</p>
            </div>
            <p>{{appeal_process}}</p>
            <p>If you have any questions or believe this suspension was made in error, please contact:</p>
            <p><strong>{{admin_name}}</strong><br>Email: <a href="mailto:{{admin_email}}">{{admin_email}}</a></p>
        </div>
        <div class="footer">
            <p>© {{company_name}} - Analytics Hub Platform</p>
        </div>
    </div>
</body>
</html>
        ';
    }

    private function getSuspensionNoticeTextTemplate(): string
    {
        return '
Account Suspension Notice - {{company_name}}

Dear {{user_name}},

We regret to inform you that your {{company_name}} account has been suspended.

SUSPENSION DETAILS:
==================
Date: {{suspension_date}}
Reason: {{suspension_reason}}
Administrator: {{admin_name}}

{{appeal_process}}

If you have any questions or believe this suspension was made in error, please contact:
{{admin_name}}
Email: {{admin_email}}

© {{company_name}} - Analytics Hub Platform
        ';
    }

    // Additional template methods would continue here...
    // For brevity, I\'ll include simplified versions of the remaining templates
    
    private function getAnnouncementHtmlTemplate(): string
    {
        return $this->getBasicHtmlTemplate('{{announcement_title}}', '
            <p>Hello <strong>{{user_name}}</strong>,</p>
            <p>{{announcement_content}}</p>
            <p><strong>Date:</strong> {{announcement_date}}</p>
            <p><strong>Priority:</strong> {{priority_level}}</p>
            {{#if action_required}}
            <div class="warning">
                <strong>Action Required:</strong> {{action_required}}
                {{#if deadline}}<br><strong>Deadline:</strong> {{deadline}}{{/if}}
            </div>
            {{/if}}
        ');
    }

    private function getAnnouncementTextTemplate(): string
    {
        return $this->getBasicTextTemplate('{{announcement_title}}', '
Hello {{user_name}},

{{announcement_content}}

Date: {{announcement_date}}
Priority: {{priority_level}}

{{#if action_required}}
ACTION REQUIRED: {{action_required}}
{{#if deadline}}Deadline: {{deadline}}{{/if}}
{{/if}}
        ');
    }

    private function getWelcomeHtmlTemplate(): string
    {
        return $this->getBasicHtmlTemplate('Welcome to {{company_name}}!', '
            <p>Hello <strong>{{user_name}}</strong>,</p>
            <p>Welcome to {{company_name}} Analytics Hub! Your account is now active and ready to use.</p>
            <p><a href="{{dashboard_url}}" class="reset-button">Access Your Dashboard</a></p>
            <p>{{features_list}}</p>
            <p>Need help getting started? Check out our <a href="{{getting_started_url}}">Getting Started Guide</a>.</p>
        ');
    }

    private function getWelcomeTextTemplate(): string
    {
        return $this->getBasicTextTemplate('Welcome to {{company_name}}!', '
Hello {{user_name}},

Welcome to {{company_name}} Analytics Hub! Your account is now active and ready to use.

Access your dashboard: {{dashboard_url}}

{{features_list}}

Need help getting started? Check out our Getting Started Guide: {{getting_started_url}}
        ');
    }

    private function getAccountActivationHtmlTemplate(): string
    {
        return $this->getBasicHtmlTemplate('Activate Your Account', '
            <p>Hello <strong>{{user_name}}</strong>,</p>
            <p>Please activate your {{company_name}} account by clicking the button below:</p>
            <p><a href="{{activation_link}}" class="reset-button">Activate Account</a></p>
            <div class="warning">
                <strong>Note:</strong> This activation link will expire in {{expiry_time}}.
            </div>
        ');
    }

    private function getAccountActivationTextTemplate(): string
    {
        return $this->getBasicTextTemplate('Activate Your Account', '
Hello {{user_name}},

Please activate your {{company_name}} account by visiting:
{{activation_link}}

Note: This activation link will expire in {{expiry_time}}.
        ');
    }

    private function getPasswordExpiryHtmlTemplate(): string
    {
        return $this->getBasicHtmlTemplate('Password Expiry Notice', '
            <p>Hello <strong>{{user_name}}</strong>,</p>
            <p>Your {{company_name}} password will expire in {{days_remaining}} days on {{expiry_date}}.</p>
            <p><a href="{{change_password_url}}" class="reset-button">Change Password Now</a></p>
        ');
    }

    private function getPasswordExpiryTextTemplate(): string
    {
        return $this->getBasicTextTemplate('Password Expiry Notice', '
Hello {{user_name}},

Your {{company_name}} password will expire in {{days_remaining}} days on {{expiry_date}}.

Change your password: {{change_password_url}}
        ');
    }

    private function getSecurityAlertHtmlTemplate(): string
    {
        return $this->getBasicHtmlTemplate('Security Alert: {{alert_type}}', '
            <p>Hello <strong>{{user_name}}</strong>,</p>
            <p><strong>Security Alert:</strong> {{alert_message}}</p>
            <p><strong>Date:</strong> {{alert_date}}</p>
            <p><strong>IP Address:</strong> {{ip_address}}</p>
            <p><strong>Location:</strong> {{location}}</p>
            {{#if action_required}}
            <div class="warning">
                <strong>Action Required:</strong> {{action_required}}
            </div>
            {{/if}}
        ');
    }

    private function getSecurityAlertTextTemplate(): string
    {
        return $this->getBasicTextTemplate('Security Alert: {{alert_type}}', '
Hello {{user_name}},

Security Alert: {{alert_message}}

Date: {{alert_date}}
IP Address: {{ip_address}}
Location: {{location}}

{{#if action_required}}
ACTION REQUIRED: {{action_required}}
{{/if}}
        ');
    }

    private function getSystemMaintenanceHtmlTemplate(): string
    {
        return $this->getBasicHtmlTemplate('System Maintenance Notice', '
            <p>Hello <strong>{{user_name}}</strong>,</p>
            <p><strong>{{maintenance_title}}</strong></p>
            <p>{{maintenance_description}}</p>
            <p><strong>Start Time:</strong> {{start_time}}</p>
            <p><strong>End Time:</strong> {{end_time}}</p>
            <p><strong>Duration:</strong> {{duration}}</p>
            <p><strong>Affected Services:</strong> {{affected_services}}</p>
        ');
    }

    private function getSystemMaintenanceTextTemplate(): string
    {
        return $this->getBasicTextTemplate('System Maintenance Notice', '
Hello {{user_name}},

{{maintenance_title}}

{{maintenance_description}}

Start Time: {{start_time}}
End Time: {{end_time}}
Duration: {{duration}}
Affected Services: {{affected_services}}
        ');
    }

    private function getBasicHtmlTemplate(string $title, string $content): string
    {
        return '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . $title . ' - {{company_name}}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f4f4f4; }
        .container { background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #FF7A00; }
        .logo { font-size: 24px; font-weight: bold; color: #0E0E44; margin-bottom: 10px; }
        .title { color: #0E0E44; font-size: 28px; margin-bottom: 10px; }
        .reset-button { display: inline-block; background-color: #FF7A00; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
        .warning { background-color: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">{{company_name}}</div>
            <h1 class="title">' . $title . '</h1>
        </div>
        <div class="content">' . $content . '
        </div>
        <div class="footer">
            <p>© {{company_name}} - Analytics Hub Platform</p>
            <p>Need help? Contact us at <a href="mailto:{{support_email}}">{{support_email}}</a></p>
        </div>
    </div>
</body>
</html>
        ';
    }

    private function getBasicTextTemplate(string $title, string $content): string
    {
        return '
' . $title . ' - {{company_name}}
' . $content . '

Need help? Contact us at {{support_email}}

© {{company_name}} - Analytics Hub Platform
        ';
    }
}