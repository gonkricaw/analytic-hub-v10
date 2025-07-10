<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SystemConfig;
use Illuminate\Support\Str;

/**
 * TermsConfigSeeder
 * 
 * Seeds system configurations for Terms & Conditions version management
 * and update notification system.
 */
class TermsConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates system configurations for T&C version tracking,
     * notification settings, and update management.
     */
    public function run(): void
    {
        $configs = [
            [
                'key' => 'terms.current_version',
                'display_name' => 'Current Terms & Conditions Version',
                'description' => 'Current version of Terms & Conditions that users must accept',
                'value' => '1.0',
                'default_value' => '1.0',
                'data_type' => 'string',
                'group' => 'terms',
                'category' => 'version_management',
                'sort_order' => 1,
                'is_public' => false,
                'is_editable' => true,
                'is_system_config' => true,
                'requires_restart' => false,
                'validation_rules' => json_encode([
                    'required' => true,
                    'string' => true,
                    'max' => 20
                ]),
                'input_type' => 'text',
                'help_text' => 'Version number for Terms & Conditions (e.g., 1.0, 1.1, 2.0)',
                'is_active' => true,
                'is_encrypted' => false,
                'environment' => 'all'
            ],
            [
                'key' => 'terms.last_updated',
                'display_name' => 'Terms & Conditions Last Updated',
                'description' => 'Timestamp when Terms & Conditions were last updated',
                'value' => now()->toDateTimeString(),
                'default_value' => now()->toDateTimeString(),
                'data_type' => 'datetime',
                'group' => 'terms',
                'category' => 'version_management',
                'sort_order' => 2,
                'is_public' => false,
                'is_editable' => false,
                'is_system_config' => true,
                'requires_restart' => false,
                'input_type' => 'datetime',
                'help_text' => 'Automatically updated when T&C version changes',
                'is_active' => true,
                'is_encrypted' => false,
                'environment' => 'all'
            ],
            [
                'key' => 'terms.notification_enabled',
                'display_name' => 'Enable T&C Update Notifications',
                'description' => 'Enable notifications when Terms & Conditions are updated',
                'value' => 'true',
                'default_value' => 'true',
                'data_type' => 'boolean',
                'group' => 'terms',
                'category' => 'notifications',
                'sort_order' => 3,
                'is_public' => false,
                'is_editable' => true,
                'is_system_config' => true,
                'requires_restart' => false,
                'validation_rules' => json_encode([
                    'required' => true,
                    'boolean' => true
                ]),
                'input_type' => 'checkbox',
                'help_text' => 'Send notifications to users when T&C are updated',
                'is_active' => true,
                'is_encrypted' => false,
                'environment' => 'all'
            ],
            [
                'key' => 'terms.force_reacceptance',
                'display_name' => 'Force Re-acceptance on Update',
                'description' => 'Force users to re-accept Terms & Conditions when updated',
                'value' => 'true',
                'default_value' => 'true',
                'data_type' => 'boolean',
                'group' => 'terms',
                'category' => 'notifications',
                'sort_order' => 4,
                'is_public' => false,
                'is_editable' => true,
                'is_system_config' => true,
                'requires_restart' => false,
                'validation_rules' => json_encode([
                    'required' => true,
                    'boolean' => true
                ]),
                'input_type' => 'checkbox',
                'help_text' => 'Require users to re-accept T&C when version changes',
                'is_active' => true,
                'is_encrypted' => false,
                'environment' => 'all'
            ],
            [
                'key' => 'terms.notification_title',
                'display_name' => 'T&C Update Notification Title',
                'description' => 'Title for Terms & Conditions update notifications',
                'value' => 'Terms & Conditions Updated',
                'default_value' => 'Terms & Conditions Updated',
                'data_type' => 'string',
                'group' => 'terms',
                'category' => 'notifications',
                'sort_order' => 5,
                'is_public' => false,
                'is_editable' => true,
                'is_system_config' => true,
                'requires_restart' => false,
                'validation_rules' => json_encode([
                    'required' => true,
                    'string' => true,
                    'max' => 255
                ]),
                'input_type' => 'text',
                'help_text' => 'Title displayed in T&C update notifications',
                'is_active' => true,
                'is_encrypted' => false,
                'environment' => 'all'
            ],
            [
                'key' => 'terms.notification_message',
                'display_name' => 'T&C Update Notification Message',
                'description' => 'Message content for Terms & Conditions update notifications',
                'value' => 'Our Terms & Conditions have been updated. Please review and accept the new terms to continue using the system.',
                'default_value' => 'Our Terms & Conditions have been updated. Please review and accept the new terms to continue using the system.',
                'data_type' => 'text',
                'group' => 'terms',
                'category' => 'notifications',
                'sort_order' => 6,
                'is_public' => false,
                'is_editable' => true,
                'is_system_config' => true,
                'requires_restart' => false,
                'validation_rules' => json_encode([
                    'required' => true,
                    'string' => true,
                    'max' => 1000
                ]),
                'input_type' => 'textarea',
                'help_text' => 'Message content for T&C update notifications',
                'is_active' => true,
                'is_encrypted' => false,
                'environment' => 'all'
            ]
        ];

        foreach ($configs as $config) {
            SystemConfig::updateOrCreate(
                ['key' => $config['key']],
                array_merge($config, [
                    'id' => Str::uuid(),
                    'last_changed_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ])
            );
        }

        $this->command->info('Terms & Conditions system configurations seeded successfully.');
    }
}
