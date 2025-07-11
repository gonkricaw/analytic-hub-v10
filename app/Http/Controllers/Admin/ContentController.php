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
}