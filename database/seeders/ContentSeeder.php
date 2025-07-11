<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Content;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * ContentSeeder
 * 
 * Seeds the database with sample content for testing.
 * Creates various types of content with different statuses and roles.
 * 
 * @package Database\Seeders
 * @author Analytics Hub Team
 * @version 1.0.0
 */
class ContentSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     * 
     * Creates sample content:
     * - Articles
     * - Pages
     * - Reports
     * - Documentation
     *
     * @return void
     */
    public function run(): void
    {
        try {
            DB::beginTransaction();

            Log::info('Starting content seeding process');

            // Get users for content creation
            $superAdmin = User::where('email', 'superadmin@analyticshub.com')->first();
            $admin = User::where('email', 'admin@analyticshub.com')->first();
            $manager = User::where('email', 'michael.johnson@analyticshub.com')->first();
            $user = User::where('email', 'david.brown@analyticshub.com')->first();

            // Get roles for content access
            $adminRole = Role::where('name', 'admin')->first();
            $managerRole = Role::where('name', 'manager')->first();
            $userRole = Role::where('name', 'user')->first();
            $guestRole = Role::where('name', 'guest')->first();

            // Clear existing content
            Content::truncate();
            DB::table('idbi_content_roles')->truncate();

            $contents = [
                // Welcome and Getting Started
                [
                    'title' => 'Welcome to Analytics Hub',
                    'slug' => 'welcome-to-analytics-hub',
                    'content' => '<h1>Welcome to Analytics Hub</h1><p>Analytics Hub is your comprehensive platform for data analysis, reporting, and business intelligence. This platform provides powerful tools to help you make data-driven decisions.</p><h2>Key Features</h2><ul><li>Advanced Analytics Dashboard</li><li>Real-time Reporting</li><li>User Management System</li><li>Role-based Access Control</li><li>Customizable Menus</li></ul><p>Get started by exploring the dashboard and familiarizing yourself with the available features.</p>',
                    'excerpt' => 'Welcome to Analytics Hub - your comprehensive platform for data analysis and business intelligence.',
                    'type' => 'page',
                    'status' => 'published',
    
                    'is_featured' => true,
                    'meta_title' => 'Welcome to Analytics Hub',
                    'meta_description' => 'Get started with Analytics Hub - comprehensive data analysis and business intelligence platform.',
                    'meta_keywords' => 'analytics, dashboard, business intelligence, data analysis',
                    'published_at' => now()->subDays(30),
                    'created_by' => $superAdmin?->id,
                    'updated_by' => $admin?->id,
                    'roles' => [$userRole, $guestRole]
                ],

                // User Guide
                [
                    'title' => 'User Guide: Getting Started',
                    'slug' => 'user-guide-getting-started',
                    'content' => '<h1>Getting Started Guide</h1><p>This guide will help you get started with Analytics Hub quickly and efficiently.</p><h2>First Steps</h2><ol><li><strong>Login:</strong> Use your credentials to access the system</li><li><strong>Dashboard:</strong> Familiarize yourself with the main dashboard</li><li><strong>Navigation:</strong> Explore the menu system</li><li><strong>Profile:</strong> Update your profile information</li></ol><h2>Basic Operations</h2><p>Learn how to perform basic operations like viewing reports, managing content, and customizing your experience.</p><h3>Viewing Reports</h3><p>Navigate to the Reports section to access various analytics and data visualizations.</p><h3>Managing Content</h3><p>If you have the appropriate permissions, you can create, edit, and manage content through the Content Management section.</p>',
                    'excerpt' => 'Complete guide to getting started with Analytics Hub platform.',
                    'type' => 'help',
                    'status' => 'published',
                    'is_featured' => false,
                    'meta_title' => 'User Guide: Getting Started with Analytics Hub',
                    'meta_description' => 'Step-by-step guide to help you get started with Analytics Hub platform.',
                    'meta_keywords' => 'user guide, getting started, tutorial, help',
                    'published_at' => now()->subDays(25),
                    'created_by' => $manager?->id,
                    'updated_by' => $manager?->id,
                    'roles' => [$userRole, $guestRole]
                ],

                // System Administration Guide
                [
                    'title' => 'System Administration Guide',
                    'slug' => 'system-administration-guide',
                    'content' => '<h1>System Administration Guide</h1><p>This comprehensive guide covers system administration tasks for Analytics Hub.</p><h2>User Management</h2><p>Learn how to manage users, roles, and permissions effectively.</p><h3>Creating Users</h3><p>Step-by-step instructions for creating new user accounts and assigning appropriate roles.</p><h3>Role Management</h3><p>Understanding the role hierarchy and how to assign permissions to roles.</p><h2>Menu Management</h2><p>Customize the navigation menu to match your organization\'s needs.</p><h2>System Configuration</h2><p>Configure system settings, email templates, and other administrative options.</p><h2>Security Best Practices</h2><ul><li>Regular password updates</li><li>Role-based access control</li><li>Activity monitoring</li><li>System backups</li></ul>',
                    'excerpt' => 'Comprehensive guide for system administrators managing Analytics Hub.',
                    'type' => 'help',
                    'status' => 'published',
                    'is_featured' => true,
                    'meta_title' => 'System Administration Guide - Analytics Hub',
                    'meta_description' => 'Complete administration guide for managing Analytics Hub system.',
                    'meta_keywords' => 'administration, system management, user management, security',
                    'published_at' => now()->subDays(20),
                    'created_by' => $superAdmin?->id,
                    'updated_by' => $admin?->id,
                    'roles' => [$adminRole]
                ],

                // Analytics Report
                [
                    'title' => 'Monthly Analytics Report - ' . now()->format('F Y'),
                    'slug' => 'monthly-analytics-report-' . now()->format('Y-m'),
                    'content' => '<h1>Monthly Analytics Report</h1><p>This report provides insights into system usage and performance for ' . now()->format('F Y') . '.</p><h2>Key Metrics</h2><ul><li><strong>Active Users:</strong> 150 (+12% from last month)</li><li><strong>Page Views:</strong> 2,450 (+8% from last month)</li><li><strong>Reports Generated:</strong> 89 (+15% from last month)</li><li><strong>System Uptime:</strong> 99.8%</li></ul><h2>User Activity</h2><p>User engagement has increased significantly this month, with more users accessing the reporting features.</p><h2>Popular Features</h2><ol><li>Dashboard Analytics</li><li>User Management</li><li>Report Generation</li><li>Content Management</li></ol><h2>Recommendations</h2><p>Based on the data, we recommend focusing on improving the report generation interface and adding more visualization options.</p>',
                    'excerpt' => 'Monthly analytics report showing system usage and performance metrics.',
                    'type' => 'post',
                    'status' => 'published',
                    'is_featured' => true,
                    'meta_title' => 'Monthly Analytics Report - ' . now()->format('F Y'),
                    'meta_description' => 'Comprehensive analytics report for ' . now()->format('F Y') . ' showing system metrics and insights.',
                    'meta_keywords' => 'analytics, report, metrics, performance, statistics',
                    'published_at' => now()->subDays(5),
                    'created_by' => $manager?->id,
                    'updated_by' => $manager?->id,
                    'roles' => [$adminRole, $managerRole]
                ],

                // FAQ Page
                [
                    'title' => 'Frequently Asked Questions',
                    'slug' => 'frequently-asked-questions',
                    'content' => '<h1>Frequently Asked Questions</h1><h2>General Questions</h2><h3>What is Analytics Hub?</h3><p>Analytics Hub is a comprehensive business intelligence platform designed to help organizations analyze data and make informed decisions.</p><h3>How do I reset my password?</h3><p>You can reset your password by clicking the "Forgot Password" link on the login page and following the instructions sent to your email.</p><h2>Technical Questions</h2><h3>What browsers are supported?</h3><p>Analytics Hub supports all modern browsers including Chrome, Firefox, Safari, and Edge.</p><h3>How often is data updated?</h3><p>Data is updated in real-time for most features, with some reports refreshed every 15 minutes.</p><h2>Account Questions</h2><h3>How do I change my profile information?</h3><p>Navigate to your profile settings from the user menu in the top right corner of the interface.</p><h3>Who can I contact for support?</h3><p>Contact your system administrator or use the help desk feature in the application.</p>',
                    'excerpt' => 'Common questions and answers about using Analytics Hub.',
                    'type' => 'page',
                    'status' => 'published',
                    'is_featured' => false,
                    'meta_title' => 'FAQ - Analytics Hub',
                    'meta_description' => 'Find answers to frequently asked questions about Analytics Hub.',
                    'meta_keywords' => 'FAQ, help, questions, support, troubleshooting',
                    'published_at' => now()->subDays(15),
                    'created_by' => $user?->id,
                    'updated_by' => $manager?->id,
                    'roles' => [$userRole, $guestRole]
                ],

                // Privacy Policy
                [
                    'title' => 'Privacy Policy',
                    'slug' => 'privacy-policy',
                    'content' => '<h1>Privacy Policy</h1><p>This Privacy Policy describes how Analytics Hub collects, uses, and protects your information.</p><h2>Information We Collect</h2><p>We collect information you provide directly to us, such as when you create an account, use our services, or contact us for support.</p><h2>How We Use Your Information</h2><ul><li>To provide and maintain our services</li><li>To improve our platform</li><li>To communicate with you</li><li>To ensure security and prevent fraud</li></ul><h2>Data Security</h2><p>We implement appropriate security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.</p><h2>Contact Us</h2><p>If you have any questions about this Privacy Policy, please contact us through the appropriate channels.</p>',
                    'excerpt' => 'Privacy policy outlining how Analytics Hub handles user data and privacy.',
                    'type' => 'page',
                    'status' => 'published',
                    'is_featured' => false,
                    'meta_title' => 'Privacy Policy - Analytics Hub',
                    'meta_description' => 'Learn about our privacy practices and how we protect your data.',
                    'meta_keywords' => 'privacy, policy, data protection, security',
                    'published_at' => now()->subDays(45),
                    'created_by' => $admin?->id,
                    'updated_by' => $admin?->id,
                    'roles' => [$userRole, $guestRole]
                ],

                // Draft Content
                [
                    'title' => 'Advanced Analytics Features (Draft)',
                    'slug' => 'advanced-analytics-features-draft',
                    'content' => '<h1>Advanced Analytics Features</h1><p>This document outlines the advanced analytics features available in Analytics Hub.</p><p><em>Note: This is a draft document and is still being developed.</em></p><h2>Planned Features</h2><ul><li>Machine Learning Integration</li><li>Predictive Analytics</li><li>Custom Dashboard Builder</li><li>API Integration</li></ul>',
                    'excerpt' => 'Draft documentation for upcoming advanced analytics features.',
                    'type' => 'help',
                    'status' => 'draft',
                    'is_featured' => false,
                    'meta_title' => 'Advanced Analytics Features (Draft)',
                    'meta_description' => 'Draft documentation for advanced analytics features.',
                    'meta_keywords' => 'advanced analytics, machine learning, predictive analytics',
                    'published_at' => null,
                    'created_by' => $manager?->id,
                    'updated_by' => $manager?->id,
                    'roles' => [$adminRole, $managerRole]
                ]
            ];

            // Get all roles first
            $roles = Role::all()->keyBy('name');
            
            if ($roles->isEmpty()) {
                Log::warning('No roles found. Skipping content creation.');
                DB::rollBack();
                return;
            }
            
            // Create content and assign roles
            foreach ($contents as $contentData) {
                $contentRoles = $contentData['roles'] ?? [];
                unset($contentData['roles']);
                
                $contentData['id'] = \Illuminate\Support\Str::uuid();
                $contentData['author_id'] = $contentData['created_by'];
                $content = Content::create($contentData);
                
                // Assign roles to content
                foreach ($contentRoles as $role) {
                    $roleName = is_object($role) ? $role->name : $role;
                    $roleRecord = $roles->get($roleName);
                    if ($roleRecord) {
                        DB::table('idbi_content_roles')->insert([
                            'id' => \Illuminate\Support\Str::uuid(),
                            'content_id' => $content->id,
                            'role_id' => $roleRecord->id,
                            'granted_by' => $content->created_by ?? null,
                            'granted_at' => now(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
                
                $roleNames = collect($contentRoles)->map(function($role) {
                    return is_object($role) ? $role->name : $role;
                })->toArray();
                
                Log::info("Created content: {$content->title} with roles: " . implode(', ', $roleNames) . " (ID: {$content->id})");
            }

            DB::commit();
            Log::info('Content seeding completed successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Content seeding failed: ' . $e->getMessage());
            throw $e;
        }
    }
}