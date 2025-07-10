<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\EmailQueue;
use App\Services\UserInvitationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

/**
 * InvitationController
 * 
 * Handles user invitation operations including sending, resending,
 * tracking, and managing invitation status.
 */
class InvitationController extends Controller
{
    /**
     * The user invitation service
     */
    protected UserInvitationService $invitationService;

    /**
     * Create a new controller instance.
     * 
     * @param UserInvitationService $invitationService
     */
    public function __construct(UserInvitationService $invitationService)
    {
        $this->middleware('auth');
        $this->middleware('admin');
        $this->invitationService = $invitationService;
    }

    /**
     * Display invitation management dashboard
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        try {
            // Get invitation statistics
            $stats = $this->invitationService->getInvitationStats();
            
            // Get recent invitations
            $recentInvitations = EmailQueue::where('email_type', 'invitation')
                ->with(['user:id,first_name,last_name,email,username,created_at'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            return view('admin.invitations.index', compact('stats', 'recentInvitations'));
        } catch (\Exception $e) {
            Log::error('Error loading invitation dashboard', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return redirect()->back()->with('error', 'Failed to load invitation dashboard.');
        }
    }

    /**
     * Send invitation to a user
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function send(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'custom_message' => 'nullable|string|max:1000',
                'template_id' => 'nullable|exists:email_templates,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::findOrFail($request->user_id);
            $sender = Auth::user();

            // Check if user is eligible for invitation
            if (!$this->invitationService->canSendInvitation($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not eligible for invitation. They may already be active or have pending invitations.'
                ], 400);
            }

            // Send invitation
            $result = $this->invitationService->sendInvitation(
                $user,
                $sender,
                $request->custom_message,
                $request->template_id
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Invitation sent successfully.',
                    'data' => [
                        'queue_id' => $result['queue_id'],
                        'temporary_password' => $result['temporary_password']
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to send invitation.'
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error sending invitation', [
                'user_id' => $request->user_id,
                'sender_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while sending the invitation.'
            ], 500);
        }
    }

    /**
     * Resend invitation to a user
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function resend(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'custom_message' => 'nullable|string|max:1000',
                'template_id' => 'nullable|exists:email_templates,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::findOrFail($request->user_id);
            $sender = Auth::user();

            // Check if user can receive resend
            if (!$this->invitationService->canResendInvitation($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot resend invitation. User may be active or maximum resend attempts reached.'
                ], 400);
            }

            // Resend invitation
            $result = $this->invitationService->resendInvitation(
                $user,
                $sender,
                $request->custom_message,
                $request->template_id
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Invitation resent successfully.',
                    'data' => [
                        'queue_id' => $result['queue_id'],
                        'resend_count' => $result['resend_count']
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to resend invitation.'
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error resending invitation', [
                'user_id' => $request->user_id,
                'sender_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while resending the invitation.'
            ], 500);
        }
    }

    /**
     * Get invitation status for a user
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function status(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::findOrFail($request->user_id);
            $status = $this->invitationService->getInvitationStatus($user);

            return response()->json([
                'success' => true,
                'data' => $status
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting invitation status', [
                'user_id' => $request->user_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while getting invitation status.'
            ], 500);
        }
    }

    /**
     * Get invitation history for a user
     * 
     * @param User $user
     * @return JsonResponse
     */
    public function history(User $user): JsonResponse
    {
        try {
            $history = EmailQueue::where('to_user_id', $user->id)
                ->where('email_type', 'invitation')
                ->with(['sender:id,first_name,last_name,email'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($email) {
                    return [
                        'id' => $email->id,
                        'status' => $email->status,
                        'sent_at' => $email->sent_at?->format('Y-m-d H:i:s'),
                        'delivered_at' => $email->delivered_at?->format('Y-m-d H:i:s'),
                        'opened_at' => $email->opened_at?->format('Y-m-d H:i:s'),
                        'clicked_at' => $email->clicked_at?->format('Y-m-d H:i:s'),
                        'failed_at' => $email->failed_at?->format('Y-m-d H:i:s'),
                        'error_message' => $email->error_message,
                        'retry_count' => $email->retry_count,
                        'sender' => $email->sender ? [
                            'name' => $email->sender->full_name,
                            'email' => $email->sender->email
                        ] : null,
                        'created_at' => $email->created_at->format('Y-m-d H:i:s')
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $history
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting invitation history', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while getting invitation history.'
            ], 500);
        }
    }

    /**
     * Cancel pending invitation
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function cancel(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::findOrFail($request->user_id);
            
            // Cancel pending invitations
            $cancelled = EmailQueue::where('to_user_id', $user->id)
                ->where('email_type', 'invitation')
                ->whereIn('status', ['pending', 'queued', 'processing'])
                ->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancelled_by' => Auth::id(),
                    'updated_at' => now()
                ]);

            // Log the cancellation
            Log::info('Invitation cancelled', [
                'user_id' => $user->id,
                'cancelled_by' => Auth::id(),
                'cancelled_count' => $cancelled
            ]);

            return response()->json([
                'success' => true,
                'message' => "Cancelled {$cancelled} pending invitation(s).",
                'data' => [
                    'cancelled_count' => $cancelled
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error cancelling invitation', [
                'user_id' => $request->user_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while cancelling the invitation.'
            ], 500);
        }
    }

    /**
     * Clean up expired invitations
     * 
     * @return JsonResponse
     */
    public function cleanup(): JsonResponse
    {
        try {
            $result = $this->invitationService->cleanupExpiredInvitations();

            return response()->json([
                'success' => true,
                'message' => "Cleaned up {$result['cleaned_count']} expired invitation(s).",
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Error cleaning up invitations', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while cleaning up invitations.'
            ], 500);
        }
    }

    /**
     * Get invitation statistics
     * 
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->invitationService->getInvitationStats();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting invitation stats', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while getting invitation statistics.'
            ], 500);
        }
    }
}