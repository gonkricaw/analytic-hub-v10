<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailQueue;
use App\Services\EmailQueueService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Exception;

/**
 * EmailQueueController
 * 
 * Handles email queue monitoring and management for administrators.
 * Provides interfaces for viewing email logs, statistics, retry management, and bulk operations.
 * 
 * Features:
 * - Email queue monitoring dashboard
 * - Email delivery statistics
 * - Failed email retry management
 * - Bulk email operations
 * - Email log viewing and filtering
 * 
 * @package App\Http\Controllers\Admin
 * @author Analytics Hub Team
 * @version 1.0.0
 */
class EmailQueueController extends Controller
{
    /**
     * Email queue service instance
     */
    protected EmailQueueService $emailQueueService;

    /**
     * Create a new controller instance
     */
    public function __construct(EmailQueueService $emailQueueService)
    {
        $this->emailQueueService = $emailQueueService;
        $this->middleware('auth');
        $this->middleware('permission:email_queue.view')->only(['index', 'show', 'data', 'statistics']);
        $this->middleware('permission:email_queue.manage')->only(['retry', 'cancel', 'cleanup']);
    }

    /**
     * Display email queue monitoring dashboard
     * 
     * @return View
     */
    public function index(): View
    {
        try {
            // Get queue statistics
            $statistics = $this->emailQueueService->getQueueStatistics([
                'date_from' => now()->subDays(30)
            ]);
            
            // Get recent activity
            $recentActivity = EmailQueue::with('template')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
            
            // Get failed emails count
            $failedEmailsCount = EmailQueue::where('status', EmailQueue::STATUS_FAILED)
                ->where('attempts', '<', 'max_attempts')
                ->count();
            
            return view('admin.email-queue.index', compact(
                'statistics',
                'recentActivity',
                'failedEmailsCount'
            ));
            
        } catch (Exception $e) {
            Log::error('Failed to load email queue dashboard', [
                'error' => $e->getMessage()
            ]);
            
            return view('admin.email-queue.index')->withErrors([
                'error' => 'Failed to load email queue data. Please try again.'
            ]);
        }
    }

    /**
     * Get email queue data for DataTables
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function data(Request $request): JsonResponse
    {
        try {
            $query = EmailQueue::with('template')
                ->select([
                    'id', 'message_id', 'template_id', 'subject', 'to_email', 'to_name',
                    'status', 'priority', 'email_type', 'attempts', 'max_attempts',
                    'created_at', 'sent_at', 'failed_at', 'error_message'
                ]);
            
            // Apply filters
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            
            if ($request->filled('email_type')) {
                $query->where('email_type', $request->email_type);
            }
            
            if ($request->filled('priority')) {
                $query->where('priority', $request->priority);
            }
            
            if ($request->filled('date_from')) {
                $query->where('created_at', '>=', $request->date_from);
            }
            
            if ($request->filled('date_to')) {
                $query->where('created_at', '<=', $request->date_to);
            }
            
            return DataTables::of($query)
                ->addColumn('recipient', function ($email) {
                    $name = $email->to_name ? $email->to_name . ' <br>' : '';
                    return $name . '<small class="text-muted">' . $email->to_email . '</small>';
                })
                ->addColumn('template', function ($email) {
                    return $email->template ? $email->template->display_name : '-';
                })
                ->addColumn('status_badge', function ($email) {
                    $badgeClass = match ($email->status) {
                        EmailQueue::STATUS_QUEUED => 'badge-warning',
                        EmailQueue::STATUS_PROCESSING => 'badge-info',
                        EmailQueue::STATUS_SENT => 'badge-success',
                        EmailQueue::STATUS_FAILED => 'badge-danger',
                        EmailQueue::STATUS_CANCELLED => 'badge-secondary',
                        EmailQueue::STATUS_EXPIRED => 'badge-dark',
                        default => 'badge-light'
                    };
                    
                    return '<span class="badge ' . $badgeClass . '">' . ucfirst($email->status) . '</span>';
                })
                ->addColumn('priority_badge', function ($email) {
                    $badgeClass = match ($email->priority) {
                        EmailQueue::PRIORITY_URGENT => 'badge-danger',
                        EmailQueue::PRIORITY_HIGH => 'badge-warning',
                        EmailQueue::PRIORITY_NORMAL => 'badge-info',
                        EmailQueue::PRIORITY_LOW => 'badge-secondary',
                        default => 'badge-light'
                    };
                    
                    return '<span class="badge ' . $badgeClass . '">' . ucfirst($email->priority) . '</span>';
                })
                ->addColumn('attempts_info', function ($email) {
                    $color = $email->attempts >= $email->max_attempts ? 'text-danger' : 'text-info';
                    return '<span class="' . $color . '">' . $email->attempts . '/' . $email->max_attempts . '</span>';
                })
                ->addColumn('timing', function ($email) {
                    $created = $email->created_at->format('M j, Y H:i');
                    $sent = $email->sent_at ? '<br><small class="text-success">Sent: ' . $email->sent_at->format('M j, H:i') . '</small>' : '';
                    $failed = $email->failed_at ? '<br><small class="text-danger">Failed: ' . $email->failed_at->format('M j, H:i') . '</small>' : '';
                    
                    return $created . $sent . $failed;
                })
                ->addColumn('actions', function ($email) {
                    $actions = '<div class="btn-group btn-group-sm" role="group">';
                    
                    // View button
                    $actions .= '<button type="button" class="btn btn-outline-info" onclick="viewEmail(\'' . $email->id . '\')">'
                        . '<i class="fas fa-eye"></i></button>';
                    
                    // Retry button for failed emails
                    if ($email->status === EmailQueue::STATUS_FAILED && $email->attempts < $email->max_attempts) {
                        $actions .= '<button type="button" class="btn btn-outline-warning" onclick="retryEmail(\'' . $email->id . '\')">'
                            . '<i class="fas fa-redo"></i></button>';
                    }
                    
                    // Cancel button for queued emails
                    if (in_array($email->status, [EmailQueue::STATUS_QUEUED, EmailQueue::STATUS_PROCESSING])) {
                        $actions .= '<button type="button" class="btn btn-outline-danger" onclick="cancelEmail(\'' . $email->id . '\')">'
                            . '<i class="fas fa-times"></i></button>';
                    }
                    
                    $actions .= '</div>';
                    
                    return $actions;
                })
                ->rawColumns(['recipient', 'status_badge', 'priority_badge', 'attempts_info', 'timing', 'actions'])
                ->make(true);
                
        } catch (Exception $e) {
            Log::error('Failed to load email queue data', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to load email queue data'
            ], 500);
        }
    }

    /**
     * Show email details
     * 
     * @param string $id Email queue ID
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        try {
            $email = EmailQueue::with('template')->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'email' => [
                    'id' => $email->id,
                    'message_id' => $email->message_id,
                    'template' => $email->template ? [
                        'id' => $email->template->id,
                        'name' => $email->template->name,
                        'display_name' => $email->template->display_name
                    ] : null,
                    'subject' => $email->subject,
                    'to_email' => $email->to_email,
                    'to_name' => $email->to_name,
                    'cc_recipients' => $email->cc_recipients,
                    'bcc_recipients' => $email->bcc_recipients,
                    'from_email' => $email->from_email,
                    'from_name' => $email->from_name,
                    'html_body' => $email->html_body,
                    'text_body' => $email->text_body,
                    'template_data' => $email->template_data,
                    'attachments' => $email->attachments,
                    'status' => $email->status,
                    'priority' => $email->priority,
                    'email_type' => $email->email_type,
                    'attempts' => $email->attempts,
                    'max_attempts' => $email->max_attempts,
                    'error_message' => $email->error_message,
                    'error_details' => $email->error_details,
                    'created_at' => $email->created_at->format('Y-m-d H:i:s'),
                    'scheduled_at' => $email->scheduled_at ? $email->scheduled_at->format('Y-m-d H:i:s') : null,
                    'sent_at' => $email->sent_at ? $email->sent_at->format('Y-m-d H:i:s') : null,
                    'failed_at' => $email->failed_at ? $email->failed_at->format('Y-m-d H:i:s') : null,
                    'delivered_at' => $email->delivered_at ? $email->delivered_at->format('Y-m-d H:i:s') : null,
                    'opened_at' => $email->opened_at ? $email->opened_at->format('Y-m-d H:i:s') : null,
                    'clicked_at' => $email->clicked_at ? $email->clicked_at->format('Y-m-d H:i:s') : null,
                    'open_count' => $email->open_count,
                    'click_count' => $email->click_count
                ]
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to load email details', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Email not found or failed to load details'
            ], 404);
        }
    }

    /**
     * Get queue statistics
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $filters = [];
            
            if ($request->filled('date_from')) {
                $filters['date_from'] = $request->date_from;
            }
            
            if ($request->filled('date_to')) {
                $filters['date_to'] = $request->date_to;
            }
            
            if ($request->filled('email_type')) {
                $filters['email_type'] = $request->email_type;
            }
            
            $statistics = $this->emailQueueService->getQueueStatistics($filters);
            
            return response()->json([
                'success' => true,
                'statistics' => $statistics
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to load queue statistics', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load statistics'
            ], 500);
        }
    }

    /**
     * Retry failed emails
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function retry(Request $request): JsonResponse
    {
        try {
            $emailIds = $request->input('email_ids', []);
            
            if (empty($emailIds)) {
                // Retry all eligible failed emails
                $retryCount = $this->emailQueueService->retryFailedEmails();
            } else {
                // Retry specific emails
                $retryCount = $this->emailQueueService->retryFailedEmails($emailIds);
            }
            
            return response()->json([
                'success' => true,
                'message' => "Successfully queued {$retryCount} emails for retry",
                'retry_count' => $retryCount
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to retry emails', [
                'error' => $e->getMessage(),
                'email_ids' => $request->input('email_ids', [])
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retry emails: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel queued emails
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function cancel(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email_ids' => 'required|array',
                'email_ids.*' => 'required|string',
                'reason' => 'nullable|string|max:255'
            ]);
            
            $emailIds = $request->email_ids;
            $reason = $request->reason ?: 'Cancelled by administrator';
            
            $cancelledCount = $this->emailQueueService->cancelEmails($emailIds, $reason);
            
            return response()->json([
                'success' => true,
                'message' => "Successfully cancelled {$cancelledCount} emails",
                'cancelled_count' => $cancelledCount
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to cancel emails', [
                'error' => $e->getMessage(),
                'email_ids' => $request->input('email_ids', [])
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel emails: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clean up old email records
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function cleanup(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'days_old' => 'nullable|integer|min:1|max:365'
            ]);
            
            $daysOld = $request->input('days_old', 30);
            $deletedCount = $this->emailQueueService->cleanupOldEmails($daysOld);
            
            return response()->json([
                'success' => true,
                'message' => "Successfully cleaned up {$deletedCount} old email records",
                'deleted_count' => $deletedCount
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to cleanup old emails', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to cleanup old emails: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send bulk emails
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function sendBulk(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'template_id' => 'required|exists:idbi_email_templates,id',
                'recipients' => 'required|array|min:1',
                'recipients.*.email' => 'required|email',
                'recipients.*.name' => 'nullable|string|max:100',
                'recipients.*.template_data' => 'nullable|array',
                'subject' => 'nullable|string|max:255',
                'priority' => 'nullable|in:low,normal,high,urgent',
                'scheduled_at' => 'nullable|date|after:now'
            ]);
            
            $emailData = [
                'template_id' => $request->template_id,
                'subject' => $request->subject,
                'priority' => $request->priority ?: EmailQueue::PRIORITY_NORMAL,
                'email_type' => EmailQueue::TYPE_MARKETING,
                'scheduled_at' => $request->scheduled_at,
                'template_data' => $request->input('global_template_data', [])
            ];
            
            $queuedEmails = $this->emailQueueService->queueBulkEmails(
                $request->recipients,
                $emailData,
                $request->input('batch_size', 50)
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Bulk emails queued successfully',
                'queued_count' => count($queuedEmails),
                'total_recipients' => count($request->recipients)
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to send bulk emails', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to queue bulk emails: ' . $e->getMessage()
            ], 500);
        }
    }
}