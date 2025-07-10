<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\EmailTemplate;
use Illuminate\Support\Str;

/**
 * TermsEmailTemplateSeeder
 * 
 * Creates the Terms & Conditions update notification email template
 * for the Analytics Hub system.
 */
class TermsEmailTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates the T&C update notification email template with proper
     * configuration for automated sending when terms are updated.
     */
    public function run(): void
    {
        // Check if template already exists
        $existingTemplate = EmailTemplate::where('name', 'terms_update_notification')->first();
        
        if ($existingTemplate) {
            $this->command->info('Terms update notification template already exists. Skipping...');
            return;
        }

        // Create the T&C update notification email template
        EmailTemplate::create([
            'name' => 'terms_update_notification',
            'display_name' => 'Terms & Conditions Update Notification',
            'description' => 'Email template sent to users when Terms & Conditions are updated, requiring re-acceptance.',
            'subject' => 'Important: Terms & Conditions Updated - Action Required',
            'body_html' => $this->getHtmlTemplate(),
            'body_text' => $this->getTextTemplate(),
            'category' => EmailTemplate::CATEGORY_NOTIFICATION,
            'type' => EmailTemplate::TYPE_SYSTEM,
            'event_trigger' => 'terms.updated',
            'is_active' => true,
            'is_system_template' => true,
            'variables' => [
                'user' => [
                    'name' => 'User full name',
                    'email' => 'User email address'
                ],
                'newVersion' => 'New T&C version number',
                'previousVersion' => 'Previous T&C version number (optional)',
                'updateReason' => 'Reason for the update (optional)',
                'keyChanges' => 'Array of key changes made (optional)',
                'termsUrl' => 'URL to view the updated terms',
                'effectiveDate' => 'Date when new terms become effective'
            ],
            'from_name' => 'Analytics Hub',
            'language' => 'en',
            'priority' => 2, // High priority for important notifications
            'usage_count' => 0
        ]);

        $this->command->info('✅ Terms & Conditions update notification email template created successfully.');
    }

    /**
     * Get the HTML template content
     * 
     * @return string
     */
    private function getHtmlTemplate(): string
    {
        return file_get_contents(resource_path('views/emails/terms-update.blade.php'));
    }

    /**
     * Get the plain text template content
     * 
     * @return string
     */
    private function getTextTemplate(): string
    {
        return "
Analytics Hub - Terms & Conditions Updated

Hello {{ \$user->name }},

We are writing to inform you that our Terms & Conditions have been updated.

Version Information:
- Previous Version: {{ \$previousVersion ?? 'N/A' }}
- New Version: {{ \$newVersion }}
- Effective Date: {{ now()->format('F j, Y') }}

@if(isset(\$updateReason) && \$updateReason)
Reason for Update:
{{ \$updateReason }}
@endif

⚠️ ACTION REQUIRED:
To continue using Analytics Hub, you must review and accept the updated Terms & Conditions. You will be prompted to accept these terms the next time you log in to your account.

Review the updated terms at: {{ \$termsUrl }}

Your Privacy Matters:
These updates are designed to better protect your privacy and improve your experience with our platform. We remain committed to safeguarding your personal information and providing transparent communication about our policies.

If you have any questions about these changes or need assistance, please don't hesitate to contact our support team.

Thank you for your continued trust in Analytics Hub.

Best regards,
The Analytics Hub Team

---
This is an automated notification from Analytics Hub.
If you did not expect this email, please contact our support team immediately.

© {{ date('Y') }} Analytics Hub. All rights reserved.

Note: This email was sent to {{ \$user->email }} because you have an active account with Analytics Hub. You cannot unsubscribe from important account and security notifications.
";
    }
}
