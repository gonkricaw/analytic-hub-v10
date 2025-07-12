<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Exception;

/**
 * EmailTemplateController
 * 
 * Handles email template management for the Analytics Hub system.
 * Provides CRUD operations, template variable system, preview functionality,
 * testing capabilities, versioning, and activation logic.
 * 
 * @package App\Http\Controllers\Admin
 * @author Analytics Hub Team
 * @version 1.0
 */
class EmailTemplateController extends Controller
{
    /**
     * Constructor - Apply middleware for authentication and authorization
     */
    public function __construct()
    {
        $this->middleware(['auth.user', 'check.status', 'role:admin,super_admin']);
    }

    /**
     * Display email templates listing page
     * 
     * @return View
     */
    public function index(): View
    {
        try {
            // Get template statistics for dashboard cards
            $stats = [
                'total_templates' => EmailTemplate::count(),
                'active_templates' => EmailTemplate::where('is_active', true)->count(),
                'system_templates' => EmailTemplate::where('is_system_template', true)->count(),
                'user_templates' => EmailTemplate::where('is_system_template', false)->count()
            ];

            // Get template categories and types for filters
            $categories = EmailTemplate::distinct()->pluck('category')->filter()->sort();
            $types = EmailTemplate::distinct()->pluck('type')->filter()->sort();

            return view('admin.email-templates.index', compact('stats', 'categories', 'types'));
        } catch (Exception $e) {
            Log::error('Error loading email templates index: ' . $e->getMessage());
            return view('admin.email-templates.index', [
                'stats' => ['total_templates' => 0, 'active_templates' => 0, 'system_templates' => 0, 'user_templates' => 0],
                'categories' => collect(),
                'types' => collect()
            ]);
        }
    }

    /**
     * Get email templates data for DataTables
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getData(Request $request): JsonResponse
    {
        try {
            $query = EmailTemplate::with(['createdByUser', 'updatedByUser'])
                ->select([
                    'id', 'name', 'display_name', 'subject', 'category', 'type',
                    'is_active', 'is_system_template', 'version', 'usage_count',
                    'last_used_at', 'created_by', 'updated_by', 'created_at', 'updated_at'
                ]);

            // Apply filters
            if ($request->filled('category')) {
                $query->where('category', $request->category);
            }

            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }

            if ($request->filled('status')) {
                $query->where('is_active', $request->status === 'active');
            }

            if ($request->filled('template_type')) {
                $query->where('is_system_template', $request->template_type === 'system');
            }

            return DataTables::of($query)
                ->addColumn('status_badge', function ($template) {
                    $badgeClass = $template->is_active ? 'success' : 'secondary';
                    $statusText = $template->is_active ? 'Active' : 'Inactive';
                    return '<span class="badge bg-' . $badgeClass . '">' . $statusText . '</span>';
                })
                ->addColumn('type_badge', function ($template) {
                    $badgeClass = $template->is_system_template ? 'primary' : 'info';
                    $typeText = $template->is_system_template ? 'System' : 'Custom';
                    return '<span class="badge bg-' . $badgeClass . '">' . $typeText . '</span>';
                })
                ->addColumn('category_badge', function ($template) {
                    $categoryColors = [
                        'authentication' => 'warning',
                        'notification' => 'info',
                        'report' => 'success',
                        'marketing' => 'purple',
                        'system' => 'dark'
                    ];
                    $color = $categoryColors[$template->category] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . ucfirst($template->category) . '</span>';
                })
                ->addColumn('usage_info', function ($template) {
                    $lastUsed = $template->last_used_at ? $template->last_used_at->diffForHumans() : 'Never';
                    return '<div><small class="text-muted">Used: ' . $template->usage_count . ' times</small><br><small class="text-muted">Last: ' . $lastUsed . '</small></div>';
                })
                ->addColumn('actions', function ($template) {
                    $actions = '<div class="btn-group" role="group">';
                    
                    // View/Edit button
                    $actions .= '<a href="' . route('admin.email-templates.show', $template->id) . '" class="btn btn-sm btn-outline-primary" title="View Details"><i class="fas fa-eye"></i></a>';
                    
                    // Edit button (only for non-system templates or super admin)
                    if (!$template->is_system_template || Auth::user()->hasRole('super_admin')) {
                        $actions .= '<a href="' . route('admin.email-templates.edit', $template->id) . '" class="btn btn-sm btn-outline-warning" title="Edit Template"><i class="fas fa-edit"></i></a>';
                    }
                    
                    // Preview button
                    $actions .= '<button type="button" class="btn btn-sm btn-outline-info preview-template" data-id="' . $template->id . '" title="Preview Template"><i class="fas fa-search"></i></button>';
                    
                    // Test button
                    $actions .= '<button type="button" class="btn btn-sm btn-outline-success test-template" data-id="' . $template->id . '" title="Test Template"><i class="fas fa-paper-plane"></i></button>';
                    
                    // Toggle status button
                    if (!$template->is_system_template || Auth::user()->hasRole('super_admin')) {
                        $statusAction = $template->is_active ? 'deactivate' : 'activate';
                        $statusIcon = $template->is_active ? 'fa-toggle-off' : 'fa-toggle-on';
                        $statusClass = $template->is_active ? 'btn-outline-secondary' : 'btn-outline-success';
                        $actions .= '<button type="button" class="btn btn-sm ' . $statusClass . ' toggle-status" data-id="' . $template->id . '" data-action="' . $statusAction . '" title="' . ucfirst($statusAction) . ' Template"><i class="fas ' . $statusIcon . '"></i></button>';
                    }
                    
                    // Version history button
                    $actions .= '<button type="button" class="btn btn-sm btn-outline-dark version-history" data-id="' . $template->id . '" title="Version History"><i class="fas fa-history"></i></button>';
                    
                    $actions .= '</div>';
                    return $actions;
                })
                ->rawColumns(['status_badge', 'type_badge', 'category_badge', 'usage_info', 'actions'])
                ->make(true);
        } catch (Exception $e) {
            Log::error('Error fetching email templates data: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load templates data'], 500);
        }
    }

    /**
     * Show the form for creating a new email template
     * 
     * @return View
     */
    public function create(): View
    {
        try {
            // Get available variables for template creation
            $availableVariables = $this->getAvailableVariables();
            
            // Get template categories and types
            $categories = [
                EmailTemplate::CATEGORY_AUTHENTICATION => 'Authentication',
                EmailTemplate::CATEGORY_NOTIFICATION => 'Notification',
                EmailTemplate::CATEGORY_REPORT => 'Report',
                EmailTemplate::CATEGORY_MARKETING => 'Marketing',
                EmailTemplate::CATEGORY_SYSTEM => 'System'
            ];
            
            $types = [
                EmailTemplate::TYPE_SYSTEM => 'System',
                EmailTemplate::TYPE_USER => 'User',
                EmailTemplate::TYPE_AUTOMATED => 'Automated'
            ];
            
            $priorities = [
                EmailTemplate::PRIORITY_HIGH => 'High',
                EmailTemplate::PRIORITY_NORMAL => 'Normal',
                EmailTemplate::PRIORITY_LOW => 'Low'
            ];

            return view('admin.email-templates.create', compact('availableVariables', 'categories', 'types', 'priorities'));
        } catch (Exception $e) {
            Log::error('Error loading email template create form: ' . $e->getMessage());
            return redirect()->route('admin.email-templates.index')
                ->with('error', 'Failed to load template creation form.');
        }
    }

    /**
     * Store a newly created email template
     * 
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:100|unique:idbi_email_templates,name',
                'display_name' => 'required|string|max:200',
                'description' => 'nullable|string|max:500',
                'subject' => 'required|string|max:255',
                'body_html' => 'required|string',
                'body_text' => 'nullable|string',
                'category' => 'required|in:' . implode(',', [
                    EmailTemplate::CATEGORY_AUTHENTICATION,
                    EmailTemplate::CATEGORY_NOTIFICATION,
                    EmailTemplate::CATEGORY_REPORT,
                    EmailTemplate::CATEGORY_MARKETING,
                    EmailTemplate::CATEGORY_SYSTEM
                ]),
                'type' => 'required|in:' . implode(',', [
                    EmailTemplate::TYPE_SYSTEM,
                    EmailTemplate::TYPE_USER,
                    EmailTemplate::TYPE_AUTOMATED
                ]),
                'event_trigger' => 'nullable|string|max:100',
                'from_email' => 'nullable|email|max:255',
                'from_name' => 'nullable|string|max:100',
                'reply_to' => 'nullable|email|max:255',
                'cc_emails' => 'nullable|array',
                'cc_emails.*' => 'email',
                'bcc_emails' => 'nullable|array',
                'bcc_emails.*' => 'email',
                'priority' => 'required|integer|in:1,3,5',
                'language' => 'required|string|max:10',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            DB::beginTransaction();

            // Extract variables from template content
            $variables = $this->extractVariablesFromContent($request->body_html, $request->body_text);

            // Create the email template
            $template = EmailTemplate::create([
                'name' => $request->name,
                'display_name' => $request->display_name,
                'description' => $request->description,
                'subject' => $request->subject,
                'body_html' => $request->body_html,
                'body_text' => $request->body_text,
                'category' => $request->category,
                'type' => $request->type,
                'event_trigger' => $request->event_trigger,
                'is_active' => $request->boolean('is_active', true),
                'is_system_template' => false, // User-created templates are never system templates
                'variables' => $variables,
                'from_email' => $request->from_email,
                'from_name' => $request->from_name,
                'reply_to' => $request->reply_to,
                'cc_emails' => $request->cc_emails,
                'bcc_emails' => $request->bcc_emails,
                'language' => $request->language ?? 'id',
                'priority' => $request->priority ?? EmailTemplate::PRIORITY_NORMAL,
                'version' => '1.0',
                'is_current_version' => true,
                'usage_count' => 0,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            DB::commit();

            Log::info('Email template created successfully', [
                'template_id' => $template->id,
                'template_name' => $template->name,
                'created_by' => Auth::id()
            ]);

            return redirect()->route('admin.email-templates.show', $template->id)
                ->with('success', 'Email template created successfully.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creating email template: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to create email template. Please try again.')
                ->withInput();
        }
    }

    /**
     * Display the specified email template
     * 
     * @param string $id
     * @return View|RedirectResponse
     */
    public function show(string $id)
    {
        try {
            $template = EmailTemplate::with(['createdByUser', 'updatedByUser', 'versions'])
                ->findOrFail($id);

            // Get template usage statistics
            $usageStats = [
                'total_sent' => $template->usage_count,
                'last_used' => $template->last_used_at,
                'success_rate' => $this->getTemplateSuccessRate($template->id),
                'avg_monthly_usage' => $this->getAverageMonthlyUsage($template->id)
            ];

            // Get available variables for reference
            $availableVariables = $this->getAvailableVariables();

            return view('admin.email-templates.show', compact('template', 'usageStats', 'availableVariables'));
        } catch (Exception $e) {
            Log::error('Error loading email template: ' . $e->getMessage());
            return redirect()->route('admin.email-templates.index')
                ->with('error', 'Email template not found.');
        }
    }

    /**
     * Show the form for editing the specified email template
     * 
     * @param string $id
     * @return View|RedirectResponse
     */
    public function edit(string $id)
    {
        try {
            $template = EmailTemplate::findOrFail($id);

            // Check if user can edit this template
            if ($template->is_system_template && !Auth::user()->hasRole('super_admin')) {
                return redirect()->route('admin.email-templates.show', $id)
                    ->with('error', 'You do not have permission to edit system templates.');
            }

            // Get available variables for template editing
            $availableVariables = $this->getAvailableVariables();
            
            // Get template categories and types
            $categories = [
                EmailTemplate::CATEGORY_AUTHENTICATION => 'Authentication',
                EmailTemplate::CATEGORY_NOTIFICATION => 'Notification',
                EmailTemplate::CATEGORY_REPORT => 'Report',
                EmailTemplate::CATEGORY_MARKETING => 'Marketing',
                EmailTemplate::CATEGORY_SYSTEM => 'System'
            ];
            
            $types = [
                EmailTemplate::TYPE_SYSTEM => 'System',
                EmailTemplate::TYPE_USER => 'User',
                EmailTemplate::TYPE_AUTOMATED => 'Automated'
            ];
            
            $priorities = [
                EmailTemplate::PRIORITY_HIGH => 'High',
                EmailTemplate::PRIORITY_NORMAL => 'Normal',
                EmailTemplate::PRIORITY_LOW => 'Low'
            ];

            return view('admin.email-templates.edit', compact('template', 'availableVariables', 'categories', 'types', 'priorities'));
        } catch (Exception $e) {
            Log::error('Error loading email template for editing: ' . $e->getMessage());
            return redirect()->route('admin.email-templates.index')
                ->with('error', 'Email template not found.');
        }
    }

    /**
     * Update the specified email template
     * 
     * @param Request $request
     * @param string $id
     * @return RedirectResponse
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        try {
            $template = EmailTemplate::findOrFail($id);

            // Check if user can edit this template
            if ($template->is_system_template && !Auth::user()->hasRole('super_admin')) {
                return redirect()->route('admin.email-templates.show', $id)
                    ->with('error', 'You do not have permission to edit system templates.');
            }

            // Validate the request
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:100|unique:idbi_email_templates,name,' . $id,
                'display_name' => 'required|string|max:200',
                'description' => 'nullable|string|max:500',
                'subject' => 'required|string|max:255',
                'body_html' => 'required|string',
                'body_text' => 'nullable|string',
                'category' => 'required|in:' . implode(',', [
                    EmailTemplate::CATEGORY_AUTHENTICATION,
                    EmailTemplate::CATEGORY_NOTIFICATION,
                    EmailTemplate::CATEGORY_REPORT,
                    EmailTemplate::CATEGORY_MARKETING,
                    EmailTemplate::CATEGORY_SYSTEM
                ]),
                'type' => 'required|in:' . implode(',', [
                    EmailTemplate::TYPE_SYSTEM,
                    EmailTemplate::TYPE_USER,
                    EmailTemplate::TYPE_AUTOMATED
                ]),
                'event_trigger' => 'nullable|string|max:100',
                'from_email' => 'nullable|email|max:255',
                'from_name' => 'nullable|string|max:100',
                'reply_to' => 'nullable|email|max:255',
                'cc_emails' => 'nullable|array',
                'cc_emails.*' => 'email',
                'bcc_emails' => 'nullable|array',
                'bcc_emails.*' => 'email',
                'priority' => 'required|integer|in:1,3,5',
                'language' => 'required|string|max:10',
                'is_active' => 'boolean',
                'create_version' => 'boolean'
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            DB::beginTransaction();

            // Check if we need to create a new version
            if ($request->boolean('create_version') && $this->hasSignificantChanges($template, $request)) {
                // Create new version
                $newVersion = $this->createNewVersion($template, $request);
                $updatedTemplate = $newVersion;
            } else {
                // Update existing template
                $variables = $this->extractVariablesFromContent($request->body_html, $request->body_text);

                $template->update([
                    'name' => $request->name,
                    'display_name' => $request->display_name,
                    'description' => $request->description,
                    'subject' => $request->subject,
                    'body_html' => $request->body_html,
                    'body_text' => $request->body_text,
                    'category' => $request->category,
                    'type' => $request->type,
                    'event_trigger' => $request->event_trigger,
                    'is_active' => $request->boolean('is_active', true),
                    'variables' => $variables,
                    'from_email' => $request->from_email,
                    'from_name' => $request->from_name,
                    'reply_to' => $request->reply_to,
                    'cc_emails' => $request->cc_emails,
                    'bcc_emails' => $request->bcc_emails,
                    'language' => $request->language ?? 'id',
                    'priority' => $request->priority ?? EmailTemplate::PRIORITY_NORMAL,
                    'updated_by' => Auth::id()
                ]);
                $updatedTemplate = $template;
            }

            DB::commit();

            Log::info('Email template updated successfully', [
                'template_id' => $updatedTemplate->id,
                'template_name' => $updatedTemplate->name,
                'updated_by' => Auth::id(),
                'version_created' => $request->boolean('create_version')
            ]);

            return redirect()->route('admin.email-templates.show', $updatedTemplate->id)
                ->with('success', 'Email template updated successfully.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating email template: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update email template. Please try again.')
                ->withInput();
        }
    }

    /**
     * Preview email template with sample data
     * 
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function preview(Request $request, string $id): JsonResponse
    {
        try {
            $template = EmailTemplate::findOrFail($id);
            
            // Get sample data for preview
            $sampleData = $this->getSampleTemplateData($template);
            
            // Override with any provided data
            if ($request->has('preview_data')) {
                $sampleData = array_merge($sampleData, $request->preview_data);
            }
            
            // Process the template
            $processedSubject = $this->processTemplateVariables($template->subject, $sampleData);
            $processedHtml = $this->processTemplateVariables($template->body_html, $sampleData);
            $processedText = $template->body_text ? $this->processTemplateVariables($template->body_text, $sampleData) : null;
            
            return response()->json([
                'success' => true,
                'preview' => [
                    'subject' => $processedSubject,
                    'html_body' => $processedHtml,
                    'text_body' => $processedText,
                    'from_email' => $template->from_email ?: config('mail.from.address'),
                    'from_name' => $template->from_name ?: config('mail.from.name'),
                    'sample_data' => $sampleData
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Error previewing email template: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate template preview.'
            ], 500);
        }
    }

    /**
     * Send test email using the template
     * 
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function sendTest(Request $request, string $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'test_email' => 'required|email',
                'test_data' => 'nullable|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid test email address.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $template = EmailTemplate::findOrFail($id);
            
            // Get test data
            $testData = $request->test_data ?: $this->getSampleTemplateData($template);
            
            // Process the template
            $processedSubject = $this->processTemplateVariables($template->subject, $testData);
            $processedHtml = $this->processTemplateVariables($template->body_html, $testData);
            $processedText = $template->body_text ? $this->processTemplateVariables($template->body_text, $testData) : null;
            
            // Send test email
            Mail::send([], [], function ($message) use ($template, $processedSubject, $processedHtml, $processedText, $request) {
                $message->to($request->test_email)
                    ->subject('[TEST] ' . $processedSubject)
                    ->from(
                        $template->from_email ?: config('mail.from.address'),
                        $template->from_name ?: config('mail.from.name')
                    );
                
                if ($processedHtml) {
                    $message->html($processedHtml);
                }
                
                if ($processedText) {
                    $message->text($processedText);
                }
            });
            
            Log::info('Test email sent successfully', [
                'template_id' => $template->id,
                'template_name' => $template->name,
                'test_email' => $request->test_email,
                'sent_by' => Auth::id()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully to ' . $request->test_email
            ]);
        } catch (Exception $e) {
            Log::error('Error sending test email: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test email. Please try again.'
            ], 500);
        }
    }

    /**
     * Toggle template activation status
     * 
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function toggleStatus(Request $request, string $id): JsonResponse
    {
        try {
            $template = EmailTemplate::findOrFail($id);

            // Check if user can modify this template
            if ($template->is_system_template && !Auth::user()->hasRole('super_admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to modify system templates.'
                ], 403);
            }

            $newStatus = !$template->is_active;
            $template->update([
                'is_active' => $newStatus,
                'updated_by' => Auth::id()
            ]);

            $statusText = $newStatus ? 'activated' : 'deactivated';
            
            Log::info('Email template status changed', [
                'template_id' => $template->id,
                'template_name' => $template->name,
                'new_status' => $newStatus,
                'changed_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Template ' . $statusText . ' successfully.',
                'new_status' => $newStatus
            ]);
        } catch (Exception $e) {
            Log::error('Error toggling template status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update template status.'
            ], 500);
        }
    }

    /**
     * Get template version history
     * 
     * @param string $id
     * @return JsonResponse
     */
    public function getVersionHistory(string $id): JsonResponse
    {
        try {
            $template = EmailTemplate::findOrFail($id);
            
            // Get all versions of this template
            $versions = EmailTemplate::where(function ($query) use ($template) {
                $query->where('id', $template->id)
                    ->orWhere('parent_template_id', $template->id)
                    ->orWhere('parent_template_id', $template->parent_template_id);
            })
            ->with(['createdByUser', 'updatedByUser'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($version) {
                return [
                    'id' => $version->id,
                    'version' => $version->version,
                    'is_current' => $version->is_current_version,
                    'created_at' => $version->created_at->format('Y-m-d H:i:s'),
                    'created_by' => $version->createdByUser ? $version->createdByUser->name : 'System',
                    'usage_count' => $version->usage_count,
                    'is_active' => $version->is_active
                ];
            });

            return response()->json([
                'success' => true,
                'versions' => $versions
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching template version history: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load version history.'
            ], 500);
        }
    }

    /**
     * Restore a specific template version
     * 
     * @param Request $request
     * @param string $id
     * @param string $versionId
     * @return JsonResponse
     */
    public function restoreVersion(Request $request, string $id, string $versionId): JsonResponse
    {
        try {
            $currentTemplate = EmailTemplate::findOrFail($id);
            $versionTemplate = EmailTemplate::findOrFail($versionId);

            // Check if user can modify this template
            if ($currentTemplate->is_system_template && !Auth::user()->hasRole('super_admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to modify system templates.'
                ], 403);
            }

            DB::beginTransaction();

            // Mark all versions as not current
            EmailTemplate::where(function ($query) use ($currentTemplate) {
                $query->where('id', $currentTemplate->id)
                    ->orWhere('parent_template_id', $currentTemplate->id)
                    ->orWhere('parent_template_id', $currentTemplate->parent_template_id);
            })->update(['is_current_version' => false]);

            // Mark the selected version as current
            $versionTemplate->update([
                'is_current_version' => true,
                'updated_by' => Auth::id()
            ]);

            DB::commit();

            Log::info('Email template version restored', [
                'template_id' => $currentTemplate->id,
                'restored_version_id' => $versionTemplate->id,
                'restored_version' => $versionTemplate->version,
                'restored_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Template version ' . $versionTemplate->version . ' restored successfully.',
                'redirect_url' => route('admin.email-templates.show', $versionTemplate->id)
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error restoring template version: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to restore template version.'
            ], 500);
        }
    }

    /**
     * Get available template variables
     * 
     * @return array
     */
    private function getAvailableVariables(): array
    {
        return [
            'user' => [
                'user_name' => 'Recipient\'s full name',
                'user_email' => 'Recipient\'s email address',
                'user_role' => 'User\'s role in the system'
            ],
            'authentication' => [
                'temp_password' => 'Temporary password for first login',
                'reset_link' => 'Password reset URL',
                'login_url' => 'Application login page URL'
            ],
            'system' => [
                'current_date' => 'Current date and time',
                'company_name' => 'Organization name',
                'app_name' => 'Application name',
                'app_url' => 'Application URL'
            ],
            'admin' => [
                'admin_name' => 'Administrator who triggered the email',
                'admin_email' => 'Administrator\'s email address'
            ],
            'content' => [
                'content_title' => 'Content title',
                'content_url' => 'Content URL',
                'content_type' => 'Type of content'
            ]
        ];
    }

    /**
     * Extract variables from template content
     * 
     * @param string $htmlContent
     * @param string|null $textContent
     * @return array
     */
    private function extractVariablesFromContent(string $htmlContent, ?string $textContent = null): array
    {
        $variables = [];
        $content = $htmlContent . ' ' . ($textContent ?? '');
        
        // Extract variables in {{variable}} format
        preg_match_all('/\{\{\s*([a-zA-Z_][a-zA-Z0-9_.]*)\s*\}\}/', $content, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $variable) {
                $variables[$variable] = 'Dynamic variable: ' . $variable;
            }
        }
        
        return $variables;
    }

    /**
     * Process template variables with actual data
     * 
     * @param string $content
     * @param array $data
     * @return string
     */
    private function processTemplateVariables(string $content, array $data): string
    {
        foreach ($data as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $content = str_replace($placeholder, $value, $content);
        }
        
        return $content;
    }

    /**
     * Get sample data for template preview
     * 
     * @param EmailTemplate $template
     * @return array
     */
    private function getSampleTemplateData(EmailTemplate $template): array
    {
        $sampleData = [
            'user_name' => 'John Doe',
            'user_email' => 'john.doe@example.com',
            'user_role' => 'User',
            'temp_password' => 'TempPass123',
            'reset_link' => config('app.url') . '/reset-password/sample-token',
            'login_url' => config('app.url') . '/login',
            'current_date' => Carbon::now()->format('Y-m-d H:i:s'),
            'company_name' => 'Analytics Hub',
            'app_name' => config('app.name'),
            'app_url' => config('app.url'),
            'admin_name' => Auth::user()->name ?? 'System Administrator',
            'admin_email' => Auth::user()->email ?? 'admin@example.com',
            'content_title' => 'Sample Content Title',
            'content_url' => config('app.url') . '/content/sample',
            'content_type' => 'Article'
        ];
        
        // Merge with template's default data if available
        if ($template->default_data) {
            $sampleData = array_merge($sampleData, $template->default_data);
        }
        
        return $sampleData;
    }

    /**
     * Check if template changes are significant enough to warrant a new version
     * 
     * @param EmailTemplate $template
     * @param Request $request
     * @return bool
     */
    private function hasSignificantChanges(EmailTemplate $template, Request $request): bool
    {
        $significantFields = ['subject', 'body_html', 'body_text'];
        
        foreach ($significantFields as $field) {
            if ($template->$field !== $request->$field) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Create a new version of the template
     * 
     * @param EmailTemplate $template
     * @param Request $request
     * @return EmailTemplate
     */
    private function createNewVersion(EmailTemplate $template, Request $request): EmailTemplate
    {
        // Mark current version as not current
        $template->update(['is_current_version' => false]);
        
        // Generate new version number
        $versionParts = explode('.', $template->version);
        $versionParts[1] = (int)$versionParts[1] + 1;
        $newVersion = implode('.', $versionParts);
        
        // Extract variables from new content
        $variables = $this->extractVariablesFromContent($request->body_html, $request->body_text);
        
        // Create new version
        return EmailTemplate::create([
            'name' => $request->name,
            'display_name' => $request->display_name,
            'description' => $request->description,
            'subject' => $request->subject,
            'body_html' => $request->body_html,
            'body_text' => $request->body_text,
            'category' => $request->category,
            'type' => $request->type,
            'event_trigger' => $request->event_trigger,
            'is_active' => $request->boolean('is_active', true),
            'is_system_template' => $template->is_system_template,
            'variables' => $variables,
            'from_email' => $request->from_email,
            'from_name' => $request->from_name,
            'reply_to' => $request->reply_to,
            'cc_emails' => $request->cc_emails,
            'bcc_emails' => $request->bcc_emails,
            'language' => $request->language ?? 'id',
            'priority' => $request->priority ?? EmailTemplate::PRIORITY_NORMAL,
            'version' => $newVersion,
            'parent_template_id' => $template->parent_template_id ?: $template->id,
            'is_current_version' => true,
            'usage_count' => 0,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id()
        ]);
    }

    /**
     * Get template success rate from email queue
     * 
     * @param string $templateId
     * @return float
     */
    private function getTemplateSuccessRate(string $templateId): float
    {
        try {
            $totalEmails = DB::table('idbi_email_queue')
                ->where('template_id', $templateId)
                ->count();
            
            if ($totalEmails === 0) {
                return 0.0;
            }
            
            $successfulEmails = DB::table('idbi_email_queue')
                ->where('template_id', $templateId)
                ->where('status', 'sent')
                ->count();
            
            return round(($successfulEmails / $totalEmails) * 100, 2);
        } catch (Exception $e) {
            Log::error('Error calculating template success rate: ' . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Get average monthly usage for template
     * 
     * @param string $templateId
     * @return float
     */
    private function getAverageMonthlyUsage(string $templateId): float
    {
        try {
            $template = EmailTemplate::find($templateId);
            if (!$template || !$template->created_at) {
                return 0.0;
            }
            
            $monthsSinceCreation = $template->created_at->diffInMonths(Carbon::now()) ?: 1;
            return round($template->usage_count / $monthsSinceCreation, 2);
        } catch (Exception $e) {
            Log::error('Error calculating average monthly usage: ' . $e->getMessage());
            return 0.0;
        }
    }
}