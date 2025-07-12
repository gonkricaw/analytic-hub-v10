<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\Role;
use App\Services\ContentEncryptionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

/**
 * Class ContentController
 * 
 * Handles CRUD operations for content management in Analytics Hub.
 * Supports both custom HTML content and encrypted embedded content.
 * Implements role-based access control and content versioning.
 * 
 * @package App\Http\Controllers\Admin
 */
class ContentController extends Controller
{
    /**
     * Content encryption service instance
     * 
     * @var ContentEncryptionService
     */
    protected ContentEncryptionService $encryptionService;

    /**
     * Constructor
     * 
     * @param ContentEncryptionService $encryptionService
     */
    public function __construct(ContentEncryptionService $encryptionService)
    {
        $this->encryptionService = $encryptionService;
        
        // Apply middleware for permission checking
        $this->middleware('permission:content.view')->only(['index', 'show', 'preview']);
        $this->middleware('permission:content.create')->only(['create', 'store']);
        $this->middleware('permission:content.edit')->only(['edit', 'update']);
        $this->middleware('permission:content.delete')->only(['destroy']);
        $this->middleware('permission:content.manage')->only(['bulkAction', 'assignRoles']);
    }

    /**
     * Display a listing of content with DataTables support.
     * 
     * Provides server-side processing for large content datasets.
     * Includes filtering by type, status, and role-based visibility.
     * 
     * @param Request $request HTTP request instance
     * @return View|JsonResponse Content listing view or DataTables JSON response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Content::with(['author:id,first_name,last_name', 'editor:id,first_name,last_name'])
                ->select([
                    'id', 'title', 'slug', 'type', 'status', 'published_at', 
                    'expires_at', 'view_count', 'is_featured', 'author_id', 
                    'editor_id', 'created_at', 'updated_at'
                ]);

            // Apply filters
            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('category')) {
                $query->where('category', $request->category);
            }

            return DataTables::of($query)
                ->addColumn('action', function ($content) {
                    $actions = [];
                    
                    if (auth()->user()->can('content.view')) {
                        $actions[] = '<a href="' . route('admin.content.show', $content->id) . '" class="btn btn-sm btn-info" title="View"><i class="fas fa-eye"></i></a>';
                        $actions[] = '<a href="' . route('admin.content.preview', $content->id) . '" class="btn btn-sm btn-secondary" title="Preview" target="_blank"><i class="fas fa-external-link-alt"></i></a>';
                    }
                    
                    if (auth()->user()->can('content.edit')) {
                        $actions[] = '<a href="' . route('admin.content.edit', $content->id) . '" class="btn btn-sm btn-warning" title="Edit"><i class="fas fa-edit"></i></a>';
                    }
                    
                    if (auth()->user()->can('content.delete')) {
                        $actions[] = '<button type="button" class="btn btn-sm btn-danger delete-content" data-id="' . $content->id . '" title="Delete"><i class="fas fa-trash"></i></button>';
                    }
                    
                    return implode(' ', $actions);
                })
                ->addColumn('author_name', function ($content) {
                    return $content->author ? $content->author->first_name . ' ' . $content->author->last_name : 'N/A';
                })
                ->addColumn('status_badge', function ($content) {
                    $badges = [
                        'draft' => 'secondary',
                        'published' => 'success',
                        'archived' => 'warning',
                        'scheduled' => 'info'
                    ];
                    
                    $class = $badges[$content->status] ?? 'secondary';
                    return '<span class="badge bg-' . $class . '">' . ucfirst($content->status) . '</span>';
                })
                ->addColumn('type_badge', function ($content) {
                    $badges = [
                        'page' => 'primary',
                        'post' => 'info',
                        'announcement' => 'warning',
                        'help' => 'success',
                        'faq' => 'secondary',
                        'widget' => 'dark'
                    ];
                    
                    $class = $badges[$content->type] ?? 'secondary';
                    return '<span class="badge bg-' . $class . '">' . ucfirst($content->type) . '</span>';
                })
                ->editColumn('published_at', function ($content) {
                    return $content->published_at ? $content->published_at->format('M d, Y H:i') : 'Not published';
                })
                ->editColumn('expires_at', function ($content) {
                    return $content->expires_at ? $content->expires_at->format('M d, Y H:i') : 'Never';
                })
                ->editColumn('view_count', function ($content) {
                    return number_format($content->view_count);
                })
                ->rawColumns(['action', 'status_badge', 'type_badge'])
                ->make(true);
        }

        // Get filter options for the view
        $types = Content::distinct()->pluck('type')->filter()->sort();
        $categories = Content::distinct()->pluck('category')->filter()->sort();
        $statuses = [Content::STATUS_DRAFT, Content::STATUS_PUBLISHED, Content::STATUS_ARCHIVED, Content::STATUS_SCHEDULED];

        return view('admin.content.index', compact('types', 'categories', 'statuses'));
    }

    /**
     * Show the form for creating new content.
     * 
     * Displays content creation form with rich text editor and role assignment.
     * Supports both custom HTML and embedded content types.
     * 
     * @return View Content creation form
     */
    public function create(): View
    {
        $roles = Role::where('status', 'active')->orderBy('display_name')->get();
        $contentTypes = [
            Content::TYPE_PAGE => 'Page',
            Content::TYPE_POST => 'Post',
            Content::TYPE_ANNOUNCEMENT => 'Announcement',
            Content::TYPE_HELP => 'Help',
            Content::TYPE_FAQ => 'FAQ',
            Content::TYPE_WIDGET => 'Widget'
        ];
        
        $templates = $this->getAvailableTemplates();
        
        return view('admin.content.create', compact('roles', 'contentTypes', 'templates'));
    }

    /**
     * Store newly created content in storage.
     * 
     * Validates input, handles content encryption for embedded URLs,
     * creates UUID-based URL masking, and assigns roles.
     * 
     * @param Request $request HTTP request with content data
     * @return RedirectResponse Redirect to content list or edit form
     */
    public function store(Request $request): RedirectResponse
    {
        $validator = $this->validateContentRequest($request);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $data = $request->validated();
            $data['author_id'] = Auth::id();
            $data['created_by'] = Auth::id();
            
            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['title']);
            }
            
            // Ensure unique slug
            $data['slug'] = $this->ensureUniqueSlug($data['slug']);
            
            // Handle embedded content URL encryption
            if ($request->input('content_type') === 'embedded' && $request->filled('embedded_url')) {
                $data['content'] = $this->encryptionService->encryptUrl($request->input('embedded_url'));
                $data['custom_fields'] = array_merge($data['custom_fields'] ?? [], [
                    'content_type' => 'embedded',
                    'masked_url' => $this->encryptionService->generateMaskedUrl($request->input('embedded_url')),
                    'original_url_hash' => hash('sha256', $request->input('embedded_url'))
                ]);
            }
            
            // Set publication date for scheduled content
            if ($data['status'] === Content::STATUS_SCHEDULED && $request->filled('scheduled_at')) {
                $data['published_at'] = $request->input('scheduled_at');
            } elseif ($data['status'] === Content::STATUS_PUBLISHED && !$request->filled('published_at')) {
                $data['published_at'] = now();
            }

            $content = Content::create($data);

            // Assign roles if provided
            if ($request->filled('roles')) {
                $content->roles()->sync($request->input('roles'));
            }

            DB::commit();

            // Log activity
            Log::info('Content created', [
                'content_id' => $content->id,
                'title' => $content->title,
                'type' => $content->type,
                'user_id' => Auth::id()
            ]);

            return redirect()->route('admin.content.index')
                ->with('success', 'Content created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Content creation failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->except(['content', 'embedded_url'])
            ]);

            return redirect()->back()
                ->with('error', 'Failed to create content. Please try again.')
                ->withInput();
        }
    }

    /**
     * Display the specified content.
     * 
     * Shows detailed content information including metadata,
     * role assignments, and access statistics.
     * 
     * @param Content $content Content model instance
     * @return View Content detail view
     */
    public function show(Content $content): View
    {
        $content->load(['author', 'editor', 'roles', 'parent']);
        
        // Get content statistics
        $stats = [
            'total_views' => $content->view_count,
            'unique_visitors' => $this->getUniqueVisitorCount($content->id),
            'avg_time_on_page' => $this->getAverageTimeOnPage($content->id),
            'bounce_rate' => $this->getBounceRate($content->id)
        ];
        
        return view('admin.content.show', compact('content', 'stats'));
    }

    /**
     * Show the form for editing the specified content.
     * 
     * Loads content with decrypted embedded URLs for editing.
     * Maintains version history and role assignments.
     * 
     * @param Content $content Content model instance
     * @return View Content edit form
     */
    public function edit(Content $content): View
    {
        $content->load(['roles']);
        
        $roles = Role::where('status', 'active')->orderBy('display_name')->get();
        $contentTypes = [
            Content::TYPE_PAGE => 'Page',
            Content::TYPE_POST => 'Post',
            Content::TYPE_ANNOUNCEMENT => 'Announcement',
            Content::TYPE_HELP => 'Help',
            Content::TYPE_FAQ => 'FAQ',
            Content::TYPE_WIDGET => 'Widget'
        ];
        
        $templates = $this->getAvailableTemplates();
        
        // Decrypt embedded URL if content is embedded type
        $embeddedUrl = null;
        if (isset($content->custom_fields['content_type']) && $content->custom_fields['content_type'] === 'embedded') {
            $embeddedUrl = $this->encryptionService->decryptUrl($content->content);
        }
        
        return view('admin.content.edit', compact('content', 'roles', 'contentTypes', 'templates', 'embeddedUrl'));
    }

    /**
     * Update the specified content in storage.
     * 
     * Handles content versioning, URL re-encryption for embedded content,
     * and role assignment updates.
     * 
     * @param Request $request HTTP request with updated content data
     * @param Content $content Content model instance
     * @return RedirectResponse Redirect to content list or edit form
     */
    public function update(Request $request, Content $content): RedirectResponse
    {
        $validator = $this->validateContentRequest($request, $content->id);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $data = $request->validated();
            $data['editor_id'] = Auth::id();
            $data['updated_by'] = Auth::id();
            
            // Handle slug updates
            if ($request->filled('slug') && $request->input('slug') !== $content->slug) {
                $data['slug'] = $this->ensureUniqueSlug($request->input('slug'), $content->id);
            }
            
            // Handle embedded content URL encryption
            if ($request->input('content_type') === 'embedded' && $request->filled('embedded_url')) {
                $data['content'] = $this->encryptionService->encryptUrl($request->input('embedded_url'));
                $data['custom_fields'] = array_merge($content->custom_fields ?? [], [
                    'content_type' => 'embedded',
                    'masked_url' => $this->encryptionService->generateMaskedUrl($request->input('embedded_url')),
                    'original_url_hash' => hash('sha256', $request->input('embedded_url'))
                ]);
            }
            
            // Handle status changes
            if ($data['status'] === Content::STATUS_PUBLISHED && $content->status !== Content::STATUS_PUBLISHED) {
                $data['published_at'] = now();
            } elseif ($data['status'] === Content::STATUS_SCHEDULED && $request->filled('scheduled_at')) {
                $data['published_at'] = $request->input('scheduled_at');
            }

            $content->update($data);

            // Update role assignments
            if ($request->has('roles')) {
                $content->roles()->sync($request->input('roles', []));
            }

            DB::commit();

            // Log activity
            Log::info('Content updated', [
                'content_id' => $content->id,
                'title' => $content->title,
                'user_id' => Auth::id()
            ]);

            return redirect()->route('admin.content.index')
                ->with('success', 'Content updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Content update failed', [
                'content_id' => $content->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to update content. Please try again.')
                ->withInput();
        }
    }

    /**
     * Remove the specified content from storage.
     * 
     * Performs soft delete to maintain data integrity.
     * Logs deletion activity for audit purposes.
     * 
     * @param Content $content Content model instance
     * @return JsonResponse JSON response with operation status
     */
    public function destroy(Content $content): JsonResponse
    {
        try {
            $content->delete();

            // Log activity
            Log::info('Content deleted', [
                'content_id' => $content->id,
                'title' => $content->title,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Content deleted successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Content deletion failed', [
                'content_id' => $content->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete content.'
            ], 500);
        }
    }

    /**
     * Preview content in a secure iframe or direct rendering.
     * 
     * For embedded content, decrypts URL and renders in secure iframe.
     * For custom content, renders HTML with security measures.
     * 
     * @param Content $content Content model instance
     * @return View Content preview
     */
    public function preview(Content $content): View
    {
        // Check if content is published or user has edit permissions
        if ($content->status !== Content::STATUS_PUBLISHED && !auth()->user()->can('content.edit')) {
            abort(404);
        }
        
        $isEmbedded = isset($content->custom_fields['content_type']) && $content->custom_fields['content_type'] === 'embedded';
        $decryptedUrl = null;
        
        if ($isEmbedded) {
            $decryptedUrl = $this->encryptionService->decryptUrl($content->content);
        }
        
        return view('admin.content.preview', compact('content', 'isEmbedded', 'decryptedUrl'));
    }

    /**
     * Validate content request data.
     * 
     * @param Request $request HTTP request instance
     * @param string|null $contentId Content ID for update validation
     * @return \Illuminate\Validation\Validator
     */
    protected function validateContentRequest(Request $request, ?string $contentId = null)
    {
        $rules = [
            'title' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('idbi_contents', 'slug')->ignore($contentId)
            ],
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required_if:content_type,custom|string',
            'embedded_url' => 'required_if:content_type,embedded|url',
            'type' => 'required|in:' . implode(',', [Content::TYPE_PAGE, Content::TYPE_POST, Content::TYPE_ANNOUNCEMENT, Content::TYPE_HELP, Content::TYPE_FAQ, Content::TYPE_WIDGET]),
            'category' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'status' => 'required|in:' . implode(',', [Content::STATUS_DRAFT, Content::STATUS_PUBLISHED, Content::STATUS_ARCHIVED, Content::STATUS_SCHEDULED]),
            'published_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:published_at',
            'is_featured' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|array',
            'meta_keywords.*' => 'string|max:50',
            'featured_image' => 'nullable|url',
            'allow_comments' => 'boolean',
            'is_searchable' => 'boolean',
            'template' => 'nullable|string|max:100',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:idbi_roles,id',
            'content_type' => 'required|in:custom,embedded',
            'scheduled_at' => 'required_if:status,' . Content::STATUS_SCHEDULED . '|date|after:now'
        ];

        return Validator::make($request->all(), $rules);
    }

    /**
     * Ensure slug uniqueness by appending numbers if necessary.
     * 
     * @param string $slug Base slug
     * @param string|null $excludeId Content ID to exclude from uniqueness check
     * @return string Unique slug
     */
    protected function ensureUniqueSlug(string $slug, ?string $excludeId = null): string
    {
        $originalSlug = $slug;
        $counter = 1;
        
        while (Content::where('slug', $slug)
            ->when($excludeId, fn($query) => $query->where('id', '!=', $excludeId))
            ->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    /**
     * Get available content templates.
     * 
     * @return array Available templates
     */
    protected function getAvailableTemplates(): array
    {
        return [
            'default' => 'Default Template',
            'full-width' => 'Full Width Template',
            'sidebar' => 'Sidebar Template',
            'landing' => 'Landing Page Template',
            'embedded' => 'Embedded Content Template'
        ];
    }

    /**
     * Get unique visitor count for content.
     * 
     * @param string $contentId Content ID
     * @return int Unique visitor count
     */
    protected function getUniqueVisitorCount(string $contentId): int
    {
        // This would typically query a user activities table
        // For now, return a placeholder
        return 0;
    }

    /**
     * Get average time spent on content page.
     * 
     * @param string $contentId Content ID
     * @return float Average time in minutes
     */
    protected function getAverageTimeOnPage(string $contentId): float
    {
        // This would typically calculate from user session data
        // For now, return a placeholder
        return 0.0;
    }

    /**
     * Get bounce rate for content page.
     * 
     * @param string $contentId Content ID
     * @return float Bounce rate percentage
     */
    protected function getBounceRate(string $contentId): float
    {
        // This would typically calculate from user navigation data
        // For now, return a placeholder
        return 0.0;
    }

    /**
     * Get DataTables data for content listing.
     * 
     * @param Request $request HTTP request instance
     * @return JsonResponse DataTables JSON response
     */
    public function getData(Request $request): JsonResponse
    {
        return $this->index($request);
    }

    /**
     * Duplicate existing content.
     * 
     * Creates a copy of the specified content with "Copy of" prefix.
     * Maintains all settings except publication status (set to draft).
     * 
     * @param Request $request HTTP request instance
     * @param Content $content Content model instance
     * @return JsonResponse JSON response with operation status
     */
    public function duplicate(Request $request, Content $content): JsonResponse
    {
        try {
            DB::beginTransaction();

            $duplicateData = $content->toArray();
            
            // Remove unique fields and set new values
            unset($duplicateData['id'], $duplicateData['created_at'], $duplicateData['updated_at'], $duplicateData['deleted_at']);
            
            $duplicateData['title'] = 'Copy of ' . $content->title;
            $duplicateData['slug'] = $this->ensureUniqueSlug('copy-of-' . $content->slug);
            $duplicateData['status'] = Content::STATUS_DRAFT;
            $duplicateData['published_at'] = null;
            $duplicateData['view_count'] = 0;
            $duplicateData['like_count'] = 0;
            $duplicateData['comment_count'] = 0;
            $duplicateData['author_id'] = Auth::id();
            $duplicateData['editor_id'] = null;
            $duplicateData['created_by'] = Auth::id();
            $duplicateData['updated_by'] = null;

            $duplicate = Content::create($duplicateData);

            // Copy role assignments
            $duplicate->roles()->sync($content->roles->pluck('id'));

            DB::commit();

            // Log activity
            Log::info('Content duplicated', [
                'original_content_id' => $content->id,
                'duplicate_content_id' => $duplicate->id,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Content duplicated successfully.',
                'redirect' => route('admin.contents.edit', $duplicate->id)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Content duplication failed', [
                'content_id' => $content->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate content.'
            ], 500);
        }
    }

    /**
     * Toggle content status between published and draft.
     * 
     * @param Request $request HTTP request instance
     * @param Content $content Content model instance
     * @return JsonResponse JSON response with operation status
     */
    public function toggleStatus(Request $request, Content $content): JsonResponse
    {
        try {
            $newStatus = $content->status === Content::STATUS_PUBLISHED 
                ? Content::STATUS_DRAFT 
                : Content::STATUS_PUBLISHED;

            $content->update([
                'status' => $newStatus,
                'published_at' => $newStatus === Content::STATUS_PUBLISHED ? now() : null,
                'editor_id' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            // Log activity
            Log::info('Content status toggled', [
                'content_id' => $content->id,
                'old_status' => $content->getOriginal('status'),
                'new_status' => $newStatus,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Content status updated successfully.',
                'new_status' => $newStatus
            ]);

        } catch (\Exception $e) {
            Log::error('Content status toggle failed', [
                'content_id' => $content->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update content status.'
            ], 500);
        }
    }

    /**
     * Get content version history.
     * 
     * @param Content $content Content model instance
     * @return JsonResponse JSON response with version data
     */
    public function versions(Content $content): JsonResponse
    {
        try {
            $versions = $content->versions()
                ->with('createdBy:id,first_name,last_name')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($version) {
                    return [
                        'id' => $version->id,
                        'version_number' => $version->version_number,
                        'description' => $version->description,
                        'change_type' => $version->change_type,
                        'changes_summary' => $version->changes_summary,
                        'created_by' => $version->createdBy ? 
                            $version->createdBy->first_name . ' ' . $version->createdBy->last_name : 'Unknown',
                        'created_at' => $version->created_at->format('M d, Y H:i'),
                        'size_diff' => $version->size_diff
                    ];
                });

            return response()->json([
                'success' => true,
                'versions' => $versions
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch content versions', [
                'content_id' => $content->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch version history.'
            ], 500);
        }
    }

    /**
     * Restore content from a specific version.
     * 
     * @param Request $request HTTP request instance
     * @param Content $content Content model instance
     * @param string $versionId Version ID to restore
     * @return JsonResponse JSON response with operation status
     */
    public function restoreVersion(Request $request, Content $content, string $versionId): JsonResponse
    {
        try {
            DB::beginTransaction();

            $version = $content->versions()->findOrFail($versionId);
            
            // Create new version before restoring
            $content->createVersion('restore', 'Restored from version ' . $version->version_number);
            
            // Restore content from version
            $restored = $version->restoreContent();
            
            if (!$restored) {
                throw new \Exception('Failed to restore content from version');
            }

            DB::commit();

            // Log activity
            Log::info('Content restored from version', [
                'content_id' => $content->id,
                'version_id' => $versionId,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Content restored successfully from version ' . $version->version_number . '.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Content version restore failed', [
                'content_id' => $content->id,
                'version_id' => $versionId,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to restore content from version.'
            ], 500);
        }
    }

    /**
     * Assign roles to content.
     * 
     * @param Request $request HTTP request instance
     * @param Content $content Content model instance
     * @return JsonResponse JSON response with assignment status
     */
    public function assignRoles(Request $request, Content $content): JsonResponse
    {
        $request->validate([
            'roles' => 'required|array|min:1',
            'roles.*.role_id' => 'required|exists:idbi_roles,id',
            'roles.*.permissions' => 'sometimes|array',
            'roles.*.permissions.can_view' => 'sometimes|boolean',
            'roles.*.permissions.can_edit' => 'sometimes|boolean',
            'roles.*.permissions.can_delete' => 'sometimes|boolean',
            'roles.*.permissions.can_publish' => 'sometimes|boolean',
            'roles.*.permissions.can_comment' => 'sometimes|boolean',
            'roles.*.permissions.can_share' => 'sometimes|boolean',
            'roles.*.assignment_reason' => 'sometimes|string|max:500',
            'roles.*.notes' => 'sometimes|string|max:1000',
            'roles.*.expires_at' => 'sometimes|date|after:now',
            'roles.*.is_temporary' => 'sometimes|boolean'
        ]);

        try {
            DB::beginTransaction();

            $results = [];
            $grantedBy = Auth::id();

            foreach ($request->input('roles') as $roleData) {
                $roleId = $roleData['role_id'];
                $permissions = $roleData['permissions'] ?? [];
                
                // Merge default permissions with provided ones
                $assignmentData = array_merge([
                    'assignment_reason' => $roleData['assignment_reason'] ?? 'Manual assignment',
                    'notes' => $roleData['notes'] ?? null,
                    'expires_at' => $roleData['expires_at'] ?? null,
                    'is_temporary' => $roleData['is_temporary'] ?? false
                ], $permissions);

                $result = $content->assignRole($roleId, $assignmentData, $grantedBy);
                $results[] = [
                    'role_id' => $roleId,
                    'success' => $result !== null,
                    'assignment_id' => $result?->id
                ];
            }

            DB::commit();

            Log::info('Content roles assigned', [
                'content_id' => $content->id,
                'roles_assigned' => count($results),
                'assigned_by' => $grantedBy
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Roles assigned successfully.',
                'results' => $results
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Content role assignment failed', [
                'content_id' => $content->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to assign roles to content.'
            ], 500);
        }
    }

    /**
     * Remove a role from content.
     * 
     * @param Request $request HTTP request instance
     * @param Content $content Content model instance
     * @param Role $role Role model instance
     * @return JsonResponse JSON response with removal status
     */
    public function removeRole(Request $request, Content $content, Role $role): JsonResponse
    {
        $request->validate([
            'revocation_reason' => 'sometimes|string|max:500'
        ]);

        try {
            $revokedBy = Auth::id();
            $reason = $request->input('revocation_reason', 'Manual revocation');
            
            $success = $content->removeRole($role->id, $revokedBy, $reason);

            if ($success) {
                Log::info('Content role removed', [
                    'content_id' => $content->id,
                    'role_id' => $role->id,
                    'revoked_by' => $revokedBy,
                    'reason' => $reason
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Role removed successfully.'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Role assignment not found or already revoked.'
                ], 404);
            }

        } catch (\Exception $e) {
            Log::error('Content role removal failed', [
                'content_id' => $content->id,
                'role_id' => $role->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove role from content.'
            ], 500);
        }
    }

    /**
     * Get content role assignments.
     * 
     * @param Content $content Content model instance
     * @return JsonResponse JSON response with role assignments
     */
    public function getRoles(Content $content): JsonResponse
    {
        try {
            $roleAssignments = $content->contentRoles()
                ->with(['role:id,name,display_name', 'grantedByUser:id,first_name,last_name'])
                ->active()
                ->get()
                ->map(function ($assignment) {
                    return [
                        'id' => $assignment->id,
                        'role' => [
                            'id' => $assignment->role->id,
                            'name' => $assignment->role->name,
                            'display_name' => $assignment->role->display_name
                        ],
                        'permissions' => [
                            'can_view' => $assignment->can_view,
                            'can_edit' => $assignment->can_edit,
                            'can_delete' => $assignment->can_delete,
                            'can_publish' => $assignment->can_publish,
                            'can_comment' => $assignment->can_comment,
                            'can_share' => $assignment->can_share
                        ],
                        'settings' => [
                            'is_visible' => $assignment->is_visible,
                            'show_in_listings' => $assignment->show_in_listings,
                            'show_metadata' => $assignment->show_metadata,
                            'allow_download' => $assignment->allow_download
                        ],
                        'assignment_info' => [
                            'granted_at' => $assignment->granted_at,
                            'expires_at' => $assignment->expires_at,
                            'is_temporary' => $assignment->is_temporary,
                            'assignment_reason' => $assignment->assignment_reason,
                            'notes' => $assignment->notes,
                            'granted_by' => $assignment->grantedByUser ? [
                                'id' => $assignment->grantedByUser->id,
                                'name' => $assignment->grantedByUser->first_name . ' ' . $assignment->grantedByUser->last_name
                            ] : null
                        ]
                    ];
                });

            return response()->json([
                'success' => true,
                'role_assignments' => $roleAssignments
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get content roles', [
                'content_id' => $content->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve content roles.'
            ], 500);
        }
    }

    /**
     * Perform bulk actions on multiple content items.
     * 
     * @param Request $request HTTP request instance
     * @return JsonResponse JSON response with operation status
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:delete,publish,unpublish,archive',
            'content_ids' => 'required|array|min:1',
            'content_ids.*' => 'exists:contents,id'
        ]);

        try {
            DB::beginTransaction();

            $contentIds = $request->input('content_ids');
            $action = $request->input('action');
            $affectedCount = 0;

            switch ($action) {
                case 'delete':
                    $affectedCount = Content::whereIn('id', $contentIds)->delete();
                    break;

                case 'publish':
                    $affectedCount = Content::whereIn('id', $contentIds)
                        ->update([
                            'status' => Content::STATUS_PUBLISHED,
                            'published_at' => now(),
                            'editor_id' => Auth::id(),
                            'updated_by' => Auth::id()
                        ]);
                    break;

                case 'unpublish':
                    $affectedCount = Content::whereIn('id', $contentIds)
                        ->update([
                            'status' => Content::STATUS_DRAFT,
                            'editor_id' => Auth::id(),
                            'updated_by' => Auth::id()
                        ]);
                    break;

                case 'archive':
                    $affectedCount = Content::whereIn('id', $contentIds)
                        ->update([
                            'status' => Content::STATUS_ARCHIVED,
                            'editor_id' => Auth::id(),
                            'updated_by' => Auth::id()
                        ]);
                    break;
            }

            DB::commit();

            // Log activity
            Log::info('Bulk content action performed', [
                'action' => $action,
                'content_ids' => $contentIds,
                'affected_count' => $affectedCount,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Successfully {$action}ed {$affectedCount} content item(s)."
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk content action failed', [
                'action' => $request->input('action'),
                'content_ids' => $request->input('content_ids'),
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to perform bulk action.'
            ], 500);
        }
    }

    // ==========================================
    // CONTENT EXPIRY MANAGEMENT
    // ==========================================

    /**
     * Get content expiry status and statistics.
     * 
     * @param Content $content Content model instance
     * @return JsonResponse JSON response with expiry information
     */
    public function getExpiryStatus(Content $content): JsonResponse
    {
        try {
            $expiryStatus = $content->getExpiryStatus();
            
            return response()->json([
                'success' => true,
                'expiry_status' => $expiryStatus
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get content expiry status', [
                'content_id' => $content->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve expiry status.'
            ], 500);
        }
    }

    /**
     * Extend content expiry date.
     * 
     * @param Request $request HTTP request instance
     * @param Content $content Content model instance
     * @return JsonResponse JSON response with operation status
     */
    public function extendExpiry(Request $request, Content $content): JsonResponse
    {
        $request->validate([
            'days' => 'required|integer|min:1|max:365',
            'reason' => 'nullable|string|max:500'
        ]);

        try {
            $days = $request->input('days');
            $reason = $request->input('reason');
            $userId = Auth::id();

            $success = $content->extendExpiry($days, $reason, $userId);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => "Content expiry extended by {$days} day(s).",
                    'new_expiry_status' => $content->fresh()->getExpiryStatus()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to extend content expiry.'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Failed to extend content expiry', [
                'content_id' => $content->id,
                'days' => $request->input('days'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to extend content expiry.'
            ], 500);
        }
    }

    /**
     * Set new expiry date for content.
     * 
     * @param Request $request HTTP request instance
     * @param Content $content Content model instance
     * @return JsonResponse JSON response with operation status
     */
    public function setExpiry(Request $request, Content $content): JsonResponse
    {
        $request->validate([
            'expires_at' => 'required|date|after:now',
            'reason' => 'nullable|string|max:500'
        ]);

        try {
            $expiryDate = $request->input('expires_at');
            $reason = $request->input('reason');
            $userId = Auth::id();

            $success = $content->setExpiry($expiryDate, $reason, $userId);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Content expiry date updated successfully.',
                    'new_expiry_status' => $content->fresh()->getExpiryStatus()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to set content expiry date.'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Failed to set content expiry', [
                'content_id' => $content->id,
                'expires_at' => $request->input('expires_at'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to set content expiry date.'
            ], 500);
        }
    }

    /**
     * Remove expiry date from content.
     * 
     * @param Request $request HTTP request instance
     * @param Content $content Content model instance
     * @return JsonResponse JSON response with operation status
     */
    public function removeExpiry(Request $request, Content $content): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500'
        ]);

        try {
            $reason = $request->input('reason');
            $userId = Auth::id();

            $success = $content->removeExpiry($reason, $userId);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Content expiry date removed successfully.',
                    'new_expiry_status' => $content->fresh()->getExpiryStatus()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to remove content expiry date.'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Failed to remove content expiry', [
                'content_id' => $content->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove content expiry date.'
            ], 500);
        }
    }

    /**
     * Get expired content list with pagination.
     * 
     * @param Request $request HTTP request instance
     * @return JsonResponse JSON response with expired content
     */
    public function getExpiredContent(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $sortBy = $request->input('sort_by', 'expires_at');
            $sortOrder = $request->input('sort_order', 'desc');

            $expiredContent = Content::expired()
                ->with(['author:id,first_name,last_name', 'editor:id,first_name,last_name'])
                ->orderBy($sortBy, $sortOrder)
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'expired_content' => $expiredContent
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get expired content', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve expired content.'
            ], 500);
        }
    }

    /**
     * Get content expiring soon with pagination.
     * 
     * @param Request $request HTTP request instance
     * @return JsonResponse JSON response with expiring content
     */
    public function getExpiringContent(Request $request): JsonResponse
    {
        try {
            $days = $request->input('days', 7);
            $perPage = $request->input('per_page', 15);
            $sortBy = $request->input('sort_by', 'expires_at');
            $sortOrder = $request->input('sort_order', 'asc');

            $expiringContent = Content::expiringSoon($days)
                ->with(['author:id,first_name,last_name', 'editor:id,first_name,last_name'])
                ->orderBy($sortBy, $sortOrder)
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'expiring_content' => $expiringContent,
                'days_ahead' => $days
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get expiring content', [
                'days' => $request->input('days', 7),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve expiring content.'
            ], 500);
        }
    }

    /**
     * Get content expiry statistics.
     * 
     * @return JsonResponse JSON response with expiry statistics
     */
    public function getExpiryStatistics(): JsonResponse
    {
        try {
            $statistics = Content::getExpiryStatistics();
            
            return response()->json([
                'success' => true,
                'statistics' => $statistics
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get expiry statistics', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve expiry statistics.'
            ], 500);
        }
    }

    /**
     * Bulk extend expiry for multiple content items.
     * 
     * @param Request $request HTTP request instance
     * @return JsonResponse JSON response with operation status
     */
    public function bulkExtendExpiry(Request $request): JsonResponse
    {
        $request->validate([
            'content_ids' => 'required|array|min:1',
            'content_ids.*' => 'exists:contents,id',
            'days' => 'required|integer|min:1|max:365',
            'reason' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            $contentIds = $request->input('content_ids');
            $days = $request->input('days');
            $reason = $request->input('reason');
            $userId = Auth::id();
            
            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            foreach ($contentIds as $contentId) {
                try {
                    $content = Content::findOrFail($contentId);
                    $success = $content->extendExpiry($days, $reason, $userId);
                    
                    if ($success) {
                        $successCount++;
                    } else {
                        $errorCount++;
                        $errors[] = "Failed to extend expiry for content ID: {$contentId}";
                    }
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = "Error processing content ID {$contentId}: {$e->getMessage()}";
                }
            }

            DB::commit();

            $message = "Successfully extended expiry for {$successCount} content item(s).";
            if ($errorCount > 0) {
                $message .= " {$errorCount} item(s) failed.";
            }

            return response()->json([
                'success' => $errorCount === 0,
                'message' => $message,
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk extend expiry failed', [
                'content_ids' => $request->input('content_ids'),
                'days' => $request->input('days'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to perform bulk expiry extension.'
            ], 500);
        }
    }

    // ==========================================
    // CONTENT VISIT TRACKING & ANALYTICS
    // ==========================================

    /**
     * Get comprehensive visit analytics for content.
     * 
     * @param Request $request HTTP request instance
     * @param Content $content Content model instance
     * @return JsonResponse JSON response with analytics data
     */
    public function getVisitAnalytics(Request $request, Content $content): JsonResponse
    {
        try {
            $visitTracker = app(\App\Services\ContentVisitTracker::class);
            
            $options = [
                'start_date' => $request->input('start_date', now()->subDays(30)),
                'end_date' => $request->input('end_date', now()),
                'include_bots' => $request->boolean('include_bots', false),
                'group_by' => $request->input('group_by', 'day')
            ];
            
            $analytics = $visitTracker->getContentAnalytics($content, $options);
            
            return response()->json([
                'success' => true,
                'analytics' => $analytics,
                'content' => [
                    'id' => $content->id,
                    'title' => $content->title,
                    'slug' => $content->slug,
                    'type' => $content->type
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get content visit analytics', [
                'content_id' => $content->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve visit analytics.'
            ], 500);
        }
    }

    /**
     * Get popular content based on visit metrics.
     * 
     * @param Request $request HTTP request instance
     * @return JsonResponse JSON response with popular content data
     */
    public function getPopularContent(Request $request): JsonResponse
    {
        try {
            $visitTracker = app(\App\Services\ContentVisitTracker::class);
            
            $options = [
                'limit' => $request->input('limit', 10),
                'period' => $request->input('period', '30_days'),
                'type' => $request->input('type'),
                'category' => $request->input('category')
            ];
            
            $popularContent = $visitTracker->getPopularContent($options);
            
            return response()->json([
                'success' => true,
                'popular_content' => $popularContent,
                'options' => $options
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get popular content', [
                'options' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve popular content.'
            ], 500);
        }
    }

    /**
     * Get trending content based on growth metrics.
     * 
     * @param Request $request HTTP request instance
     * @return JsonResponse JSON response with trending content data
     */
    public function getTrendingContent(Request $request): JsonResponse
    {
        try {
            $visitTracker = app(\App\Services\ContentVisitTracker::class);
            
            $options = [
                'limit' => $request->input('limit', 10),
                'min_growth_rate' => $request->input('min_growth_rate', 10),
                'type' => $request->input('type')
            ];
            
            $trendingContent = $visitTracker->getTrendingContent($options);
            
            return response()->json([
                'success' => true,
                'trending_content' => $trendingContent,
                'options' => $options
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get trending content', [
                'options' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve trending content.'
            ], 500);
        }
    }

    /**
     * Get real-time visit statistics.
     * 
     * @param Request $request HTTP request instance
     * @return JsonResponse JSON response with real-time stats
     */
    public function getRealTimeStats(Request $request): JsonResponse
    {
        try {
            $visitTracker = app(\App\Services\ContentVisitTracker::class);
            $realTimeStats = $visitTracker->getRealTimeStats();
            
            return response()->json([
                'success' => true,
                'real_time_stats' => $realTimeStats,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get real-time visit stats', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve real-time statistics.'
            ], 500);
        }
    }

    /**
     * Track reading progress for content analytics.
     * 
     * @param Request $request HTTP request instance
     * @param Content $content Content model instance
     * @return JsonResponse JSON response with tracking status
     */
    public function trackReadingProgress(Request $request, Content $content): JsonResponse
    {
        $request->validate([
            'scroll_depth' => 'required|numeric|min:0|max:100',
            'time_spent' => 'required|integer|min:0',
            'reading_speed' => 'nullable|numeric|min:0',
            'engagement_events' => 'nullable|array'
        ]);

        try {
            $visitTracker = app(\App\Services\ContentVisitTracker::class);
            
            $progressData = [
                'scroll_depth' => $request->input('scroll_depth'),
                'time_spent' => $request->input('time_spent'),
                'reading_speed' => $request->input('reading_speed'),
                'engagement_events' => $request->input('engagement_events', []),
                'user_agent' => $request->userAgent(),
                'session_id' => session()->getId()
            ];
            
            $visitTracker->trackReadingProgress($content, $progressData);
            
            return response()->json([
                'success' => true,
                'message' => 'Reading progress tracked successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to track reading progress', [
                'content_id' => $content->id,
                'progress_data' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to track reading progress.'
            ], 500);
        }
    }

    /**
     * Get content visit summary for dashboard.
     * 
     * @param Request $request HTTP request instance
     * @return JsonResponse JSON response with visit summary
     */
    public function getVisitSummary(Request $request): JsonResponse
    {
        try {
            $period = $request->input('period', '7_days');
            $contentType = $request->input('type');
            
            // Get summary statistics from database view
            $query = DB::table('v_popular_content')
                ->where('status', 'published')
                ->whereNull('deleted_at');
                
            if ($contentType) {
                $query->where('type', $contentType);
            }
            
            $summary = [
                'total_content' => $query->count(),
                'total_views' => $query->sum('total_views'),
                'unique_viewers' => $query->sum('unique_viewers_30_days'),
                'avg_engagement_score' => round($query->avg('engagement_score'), 2),
                'top_performing' => $query->orderByDesc('engagement_score')->limit(5)->get(['title', 'total_views', 'engagement_score']),
                'period' => $period,
                'generated_at' => now()->toISOString()
            ];
            
            return response()->json([
                'success' => true,
                'visit_summary' => $summary
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get visit summary', [
                'period' => $request->input('period'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve visit summary.'
            ], 500);
        }
    }

    /**
     * Export visit analytics data.
     * 
     * @param Request $request HTTP request instance
     * @return \Illuminate\Http\Response CSV download response
     */
    public function exportVisitAnalytics(Request $request)
    {
        try {
            $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
            $endDate = $request->input('end_date', now()->format('Y-m-d'));
            $contentType = $request->input('type');
            
            // Get analytics data
            $query = DB::table('content_access_logs as cal')
                ->join('idbi_contents as c', 'cal.content_id', '=', 'c.id')
                ->whereBetween('cal.created_at', [$startDate, $endDate])
                ->where('cal.access_result', 'success');
                
            if ($contentType) {
                $query->where('c.type', $contentType);
            }
            
            $data = $query->select([
                'c.title',
                'c.type',
                'c.category',
                'cal.access_type',
                'cal.device_type',
                'cal.browser',
                'cal.country_code',
                'cal.session_duration',
                'cal.created_at'
            ])->get();
            
            // Generate CSV
            $filename = 'content_visit_analytics_' . date('Y-m-d_H-i-s') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ];
            
            $callback = function() use ($data) {
                $file = fopen('php://output', 'w');
                
                // CSV headers
                fputcsv($file, [
                    'Content Title',
                    'Content Type',
                    'Category',
                    'Access Type',
                    'Device Type',
                    'Browser',
                    'Country',
                    'Session Duration (s)',
                    'Visit Date'
                ]);
                
                // CSV data
                foreach ($data as $row) {
                    fputcsv($file, [
                        $row->title,
                        $row->type,
                        $row->category,
                        $row->access_type,
                        $row->device_type,
                        $row->browser,
                        $row->country_code,
                        $row->session_duration,
                        $row->created_at
                    ]);
                }
                
                fclose($file);
            };
            
            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Failed to export visit analytics', [
                'request_data' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to export analytics data.'
            ], 500);
        }
    }
}