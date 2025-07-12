<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * ContentRole Pivot Model
 * 
 * Handles content-role assignments with comprehensive access control features.
 * Manages permissions, restrictions, and audit trails for content access.
 * 
 * Features:
 * - Granular permission control (view, edit, delete, publish, comment, share)
 * - Content visibility settings and restrictions
 * - Temporal assignments with expiration
 * - Assignment tracking and audit trails
 * - Usage statistics and access monitoring
 * - Approval workflows and compliance features
 * - Risk assessment and security controls
 * 
 * @package App\Models
 * 
 * @property string $id UUID primary key
 * @property string $content_id Content reference
 * @property string $role_id Role reference
 * @property bool $is_granted Access granted/denied
 * @property string $access_type Access level (view, edit, manage, full)
 * @property string|null $access_conditions Conditional access rules
 * @property array|null $restrictions Access restrictions
 * @property bool $can_view Can view content
 * @property bool $can_edit Can edit content
 * @property bool $can_delete Can delete content
 * @property bool $can_publish Can publish content
 * @property bool $can_comment Can comment on content
 * @property bool $can_share Can share content
 * @property bool $is_visible Content visibility
 * @property bool $show_in_listings Show in content lists
 * @property bool $show_metadata Show content metadata
 * @property bool $allow_download Allow content download
 * @property string|null $assignment_reason Reason for assignment
 * @property array|null $assignment_data Additional assignment data
 * @property string|null $notes Assignment notes
 * @property string $priority Assignment priority
 * @property Carbon|null $granted_at When access was granted
 * @property Carbon|null $expires_at Access expiration
 * @property bool $is_temporary Temporary assignment
 * @property int|null $duration_hours Assignment duration
 * @property string|null $granted_by Who granted access
 * @property string|null $revoked_by Who revoked access
 * @property Carbon|null $revoked_at Revocation timestamp
 * @property string|null $revocation_reason Revocation reason
 * @property bool $overrides_default Override default content access
 * @property string|null $overridden_content_id Content being overridden
 * @property string|null $override_justification Override reason
 * @property int $view_count Number of views
 * @property int $edit_count Number of edits
 * @property Carbon|null $last_viewed_at Last view time
 * @property Carbon|null $last_edited_at Last edit time
 * @property Carbon|null $first_accessed_at First access time
 * @property array|null $access_statistics Access statistics
 * @property int $comment_count Comments made
 * @property int $share_count Times shared
 * @property int $download_count Downloads performed
 * @property array|null $interaction_data Interaction details
 * @property bool $is_active Assignment active status
 * @property bool $requires_approval Needs approval
 * @property string|null $approval_status Approval status
 * @property string|null $approved_by Who approved
 * @property Carbon|null $approved_at Approval timestamp
 * @property bool $is_sensitive Sensitive content access
 * @property bool $requires_justification Needs justification
 * @property string|null $compliance_notes Compliance information
 * @property string $risk_level Risk level
 * @property bool $audit_access Audit all access
 * @property string|null $workflow_status Workflow status
 * @property string|null $reviewer_id Content reviewer
 * @property Carbon|null $reviewed_at Review timestamp
 * @property string|null $review_notes Review comments
 * @property bool $notify_on_update Notify on content update
 * @property bool $notify_on_comment Notify on new comment
 * @property bool $notify_on_share Notify on content share
 * @property string|null $created_by Creator user ID
 * @property string|null $updated_by Last updater user ID
 * @property Carbon $created_at Creation timestamp
 * @property Carbon $updated_at Last update timestamp
 */
class ContentRole extends Model
{
    use HasFactory, HasUuid;

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'idbi_content_roles';

    /**
     * Access type constants
     */
    public const ACCESS_VIEW = 'view';
    public const ACCESS_EDIT = 'edit';
    public const ACCESS_MANAGE = 'manage';
    public const ACCESS_FULL = 'full';

    /**
     * Priority constants
     */
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_CRITICAL = 'critical';

    /**
     * Approval status constants
     */
    public const APPROVAL_PENDING = 'pending';
    public const APPROVAL_APPROVED = 'approved';
    public const APPROVAL_REJECTED = 'rejected';

    /**
     * Risk level constants
     */
    public const RISK_LOW = 'low';
    public const RISK_MEDIUM = 'medium';
    public const RISK_HIGH = 'high';
    public const RISK_CRITICAL = 'critical';

    /**
     * Workflow status constants
     */
    public const WORKFLOW_DRAFT = 'draft';
    public const WORKFLOW_REVIEW = 'review';
    public const WORKFLOW_APPROVED = 'approved';
    public const WORKFLOW_PUBLISHED = 'published';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'content_id',
        'role_id',
        'is_granted',
        'access_type',
        'access_conditions',
        'restrictions',
        'can_view',
        'can_edit',
        'can_delete',
        'can_publish',
        'can_comment',
        'can_share',
        'is_visible',
        'show_in_listings',
        'show_metadata',
        'allow_download',
        'assignment_reason',
        'assignment_data',
        'notes',
        'priority',
        'granted_at',
        'expires_at',
        'is_temporary',
        'duration_hours',
        'granted_by',
        'revoked_by',
        'revoked_at',
        'revocation_reason',
        'overrides_default',
        'overridden_content_id',
        'override_justification',
        'view_count',
        'edit_count',
        'last_viewed_at',
        'last_edited_at',
        'first_accessed_at',
        'access_statistics',
        'comment_count',
        'share_count',
        'download_count',
        'interaction_data',
        'is_active',
        'requires_approval',
        'approval_status',
        'approved_by',
        'approved_at',
        'is_sensitive',
        'requires_justification',
        'compliance_notes',
        'risk_level',
        'audit_access',
        'workflow_status',
        'reviewer_id',
        'reviewed_at',
        'review_notes',
        'notify_on_update',
        'notify_on_comment',
        'notify_on_share',
        'created_by',
        'updated_by'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_granted' => 'boolean',
        'restrictions' => 'array',
        'can_view' => 'boolean',
        'can_edit' => 'boolean',
        'can_delete' => 'boolean',
        'can_publish' => 'boolean',
        'can_comment' => 'boolean',
        'can_share' => 'boolean',
        'is_visible' => 'boolean',
        'show_in_listings' => 'boolean',
        'show_metadata' => 'boolean',
        'allow_download' => 'boolean',
        'assignment_data' => 'array',
        'granted_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_temporary' => 'boolean',
        'revoked_at' => 'datetime',
        'overrides_default' => 'boolean',
        'last_viewed_at' => 'datetime',
        'last_edited_at' => 'datetime',
        'first_accessed_at' => 'datetime',
        'access_statistics' => 'array',
        'interaction_data' => 'array',
        'is_active' => 'boolean',
        'requires_approval' => 'boolean',
        'approved_at' => 'datetime',
        'is_sensitive' => 'boolean',
        'requires_justification' => 'boolean',
        'audit_access' => 'boolean',
        'reviewed_at' => 'datetime',
        'notify_on_update' => 'boolean',
        'notify_on_comment' => 'boolean',
        'notify_on_share' => 'boolean'
    ];

    /**
     * Get the content that this assignment belongs to.
     * 
     * @return BelongsTo
     */
    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class, 'content_id');
    }

    /**
     * Get the role that this assignment belongs to.
     * 
     * @return BelongsTo
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Get the user who granted this assignment.
     * 
     * @return BelongsTo
     */
    public function grantor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    /**
     * Get the user who revoked this assignment.
     * 
     * @return BelongsTo
     */
    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    /**
     * Get the user who approved this assignment.
     * 
     * @return BelongsTo
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the reviewer for this assignment.
     * 
     * @return BelongsTo
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Get the creator of this assignment.
     * 
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the last updater of this assignment.
     * 
     * @return BelongsTo
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if the assignment is currently active and valid.
     * 
     * @return bool
     */
    public function isActive(): bool
    {
        if (!$this->is_active || !$this->is_granted) {
            return false;
        }

        // Check if assignment has expired
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        // Check if assignment has been revoked
        if ($this->revoked_at) {
            return false;
        }

        // Check approval status if approval is required
        if ($this->requires_approval && $this->approval_status !== self::APPROVAL_APPROVED) {
            return false;
        }

        return true;
    }

    /**
     * Check if the assignment has expired.
     * 
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if the assignment is temporary.
     * 
     * @return bool
     */
    public function isTemporary(): bool
    {
        return $this->is_temporary || $this->expires_at !== null;
    }

    /**
     * Check if the assignment has been revoked.
     * 
     * @return bool
     */
    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    /**
     * Check if the assignment requires approval.
     * 
     * @return bool
     */
    public function needsApproval(): bool
    {
        return $this->requires_approval && $this->approval_status === self::APPROVAL_PENDING;
    }

    /**
     * Check if the assignment is approved.
     * 
     * @return bool
     */
    public function isApproved(): bool
    {
        return $this->approval_status === self::APPROVAL_APPROVED;
    }

    /**
     * Check if the assignment is high risk.
     * 
     * @return bool
     */
    public function isHighRisk(): bool
    {
        return in_array($this->risk_level, [self::RISK_HIGH, self::RISK_CRITICAL]);
    }

    /**
     * Check if the assignment is for sensitive content.
     * 
     * @return bool
     */
    public function isSensitive(): bool
    {
        return $this->is_sensitive;
    }

    /**
     * Grant access for this assignment.
     * 
     * @param string|null $grantedBy User ID who is granting access
     * @param string|null $reason Reason for granting access
     * @return bool
     */
    public function grantAccess(?string $grantedBy = null, ?string $reason = null): bool
    {
        try {
            $this->update([
                'is_granted' => true,
                'is_active' => true,
                'granted_by' => $grantedBy ?? Auth::id(),
                'granted_at' => now(),
                'assignment_reason' => $reason,
                'revoked_by' => null,
                'revoked_at' => null,
                'revocation_reason' => null,
                'updated_by' => Auth::id()
            ]);

            Log::info('Content role access granted', [
                'content_role_id' => $this->id,
                'content_id' => $this->content_id,
                'role_id' => $this->role_id,
                'granted_by' => $grantedBy ?? Auth::id(),
                'reason' => $reason
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to grant content role access', [
                'content_role_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Revoke access for this assignment.
     * 
     * @param string|null $revokedBy User ID who is revoking access
     * @param string|null $reason Reason for revoking access
     * @return bool
     */
    public function revokeAccess(?string $revokedBy = null, ?string $reason = null): bool
    {
        try {
            $this->update([
                'is_granted' => false,
                'is_active' => false,
                'revoked_by' => $revokedBy ?? Auth::id(),
                'revoked_at' => now(),
                'revocation_reason' => $reason,
                'updated_by' => Auth::id()
            ]);

            Log::info('Content role access revoked', [
                'content_role_id' => $this->id,
                'content_id' => $this->content_id,
                'role_id' => $this->role_id,
                'revoked_by' => $revokedBy ?? Auth::id(),
                'reason' => $reason
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to revoke content role access', [
                'content_role_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Approve this assignment.
     * 
     * @param string|null $approvedBy User ID who is approving
     * @param string|null $notes Approval notes
     * @return bool
     */
    public function approve(?string $approvedBy = null, ?string $notes = null): bool
    {
        try {
            $this->update([
                'approval_status' => self::APPROVAL_APPROVED,
                'approved_by' => $approvedBy ?? Auth::id(),
                'approved_at' => now(),
                'review_notes' => $notes,
                'updated_by' => Auth::id()
            ]);

            Log::info('Content role assignment approved', [
                'content_role_id' => $this->id,
                'approved_by' => $approvedBy ?? Auth::id(),
                'notes' => $notes
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to approve content role assignment', [
                'content_role_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Reject this assignment.
     * 
     * @param string|null $rejectedBy User ID who is rejecting
     * @param string|null $reason Rejection reason
     * @return bool
     */
    public function reject(?string $rejectedBy = null, ?string $reason = null): bool
    {
        try {
            $this->update([
                'approval_status' => self::APPROVAL_REJECTED,
                'approved_by' => $rejectedBy ?? Auth::id(),
                'approved_at' => now(),
                'review_notes' => $reason,
                'is_active' => false,
                'updated_by' => Auth::id()
            ]);

            Log::info('Content role assignment rejected', [
                'content_role_id' => $this->id,
                'rejected_by' => $rejectedBy ?? Auth::id(),
                'reason' => $reason
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to reject content role assignment', [
                'content_role_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Record content access for this assignment.
     * 
     * @param string $accessType Type of access (view, edit, download, etc.)
     * @return void
     */
    public function recordAccess(string $accessType = 'view'): void
    {
        try {
            $now = now();
            $updates = ['updated_by' => Auth::id()];

            // Update first access time if not set
            if (!$this->first_accessed_at) {
                $updates['first_accessed_at'] = $now;
            }

            // Update specific access counters and timestamps
            switch ($accessType) {
                case 'view':
                    $updates['view_count'] = $this->view_count + 1;
                    $updates['last_viewed_at'] = $now;
                    break;
                case 'edit':
                    $updates['edit_count'] = $this->edit_count + 1;
                    $updates['last_edited_at'] = $now;
                    break;
                case 'comment':
                    $updates['comment_count'] = $this->comment_count + 1;
                    break;
                case 'share':
                    $updates['share_count'] = $this->share_count + 1;
                    break;
                case 'download':
                    $updates['download_count'] = $this->download_count + 1;
                    break;
            }

            $this->update($updates);

            // Log access if auditing is enabled
            if ($this->audit_access) {
                Log::info('Content role access recorded', [
                    'content_role_id' => $this->id,
                    'content_id' => $this->content_id,
                    'role_id' => $this->role_id,
                    'access_type' => $accessType,
                    'user_id' => Auth::id()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to record content role access', [
                'content_role_id' => $this->id,
                'access_type' => $accessType,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Scope to get active assignments.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where('is_granted', true)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    })
                    ->whereNull('revoked_at');
    }

    /**
     * Scope to get assignments for a specific content.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $contentId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForContent($query, string $contentId)
    {
        return $query->where('content_id', $contentId);
    }

    /**
     * Scope to get assignments for a specific role.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $roleId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForRole($query, string $roleId)
    {
        return $query->where('role_id', $roleId);
    }

    /**
     * Scope to get assignments that need approval.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNeedsApproval($query)
    {
        return $query->where('requires_approval', true)
                    ->where('approval_status', self::APPROVAL_PENDING);
    }

    /**
     * Scope to get high-risk assignments.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHighRisk($query)
    {
        return $query->whereIn('risk_level', [self::RISK_HIGH, self::RISK_CRITICAL]);
    }

    /**
     * Scope to get sensitive content assignments.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSensitive($query)
    {
        return $query->where('is_sensitive', true);
    }

    /**
     * Scope to get expired assignments.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
                    ->where('expires_at', '<', now());
    }

    /**
     * Boot the model.
     * 
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // Set created_by and updated_by on creation
        static::creating(function ($model) {
            if (Auth::check()) {
                $model->created_by = Auth::id();
                $model->updated_by = Auth::id();
            }
        });

        // Set updated_by on update
        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });
    }
}