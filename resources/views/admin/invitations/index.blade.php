@extends('layouts.app')

@section('title', 'Invitation Management')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Invitation Management</h1>
            <p class="text-muted mb-0">Monitor and manage user invitations</p>
        </div>
        <div>
            <button type="button" class="btn btn-outline-secondary me-2" onclick="refreshStats()">
                <i class="fas fa-sync-alt me-1"></i>
                Refresh
            </button>
            <button type="button" class="btn btn-warning me-2" onclick="cleanupExpired()">
                <i class="fas fa-broom me-1"></i>
                Cleanup Expired
            </button>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                <i class="fas fa-user-plus me-1"></i>
                Invite New User
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-gradient rounded-circle p-3">
                                <i class="fas fa-paper-plane text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Sent</h6>
                            <h4 class="mb-0" id="totalSent">{{ $stats['total_sent'] ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-gradient rounded-circle p-3">
                                <i class="fas fa-check-circle text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Delivered</h6>
                            <h4 class="mb-0" id="totalDelivered">{{ $stats['total_delivered'] ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-gradient rounded-circle p-3">
                                <i class="fas fa-clock text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Pending</h6>
                            <h4 class="mb-0" id="totalPending">{{ $stats['total_pending'] ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-danger bg-gradient rounded-circle p-3">
                                <i class="fas fa-exclamation-triangle text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Failed</h6>
                            <h4 class="mb-0" id="totalFailed">{{ $stats['total_failed'] ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Invitations -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i>
                    Recent Invitations
                </h5>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="refreshInvitations()">
                        <i class="fas fa-sync-alt me-1"></i>
                        Refresh
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            @if($recentInvitations->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Sent At</th>
                                <th>Delivered At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="invitationsTable">
                            @foreach($recentInvitations as $invitation)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 14px; color: white;">
                                                {{ strtoupper(substr($invitation->user->first_name ?? 'U', 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="fw-bold">{{ $invitation->user->full_name ?? 'Unknown User' }}</div>
                                                <small class="text-muted">{{ $invitation->user->username ?? 'N/A' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $invitation->to_email }}</td>
                                    <td>
                                        @php
                                            $statusClass = match($invitation->status) {
                                                'sent', 'delivered' => 'success',
                                                'pending', 'queued', 'processing' => 'warning',
                                                'failed', 'bounced' => 'danger',
                                                'cancelled' => 'secondary',
                                                default => 'info'
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $statusClass }}">
                                            {{ ucfirst($invitation->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($invitation->sent_at)
                                            <span title="{{ $invitation->sent_at->format('Y-m-d H:i:s') }}">
                                                {{ $invitation->sent_at->diffForHumans() }}
                                            </span>
                                        @else
                                            <span class="text-muted">Not sent</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($invitation->delivered_at)
                                            <span title="{{ $invitation->delivered_at->format('Y-m-d H:i:s') }}">
                                                {{ $invitation->delivered_at->diffForHumans() }}
                                            </span>
                                        @else
                                            <span class="text-muted">Not delivered</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            @if($invitation->user)
                                                <button type="button" class="btn btn-outline-info" onclick="viewHistory({{ $invitation->user->id }})" title="View History">
                                                    <i class="fas fa-history"></i>
                                                </button>
                                                
                                                @if(in_array($invitation->status, ['failed', 'bounced']))
                                                    <button type="button" class="btn btn-outline-warning" onclick="resendInvitation({{ $invitation->user->id }})" title="Resend">
                                                        <i class="fas fa-redo"></i>
                                                    </button>
                                                @endif
                                                
                                                @if(in_array($invitation->status, ['pending', 'queued', 'processing']))
                                                    <button type="button" class="btn btn-outline-danger" onclick="cancelInvitation({{ $invitation->user->id }})" title="Cancel">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-4">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No Recent Invitations</h5>
                    <p class="text-muted">Invitation history will appear here once you start inviting users.</p>
                    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                        <i class="fas fa-user-plus me-1"></i>
                        Invite Your First User
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Invitation History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-history me-2"></i>
                    Invitation History
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="historyContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading invitation history...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Resend Invitation Modal -->
<div class="modal fade" id="resendModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-redo me-2"></i>
                    Resend Invitation
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="resendForm">
                    <input type="hidden" id="resendUserId" name="user_id">
                    
                    <div class="mb-3">
                        <label for="resendCustomMessage" class="form-label">Custom Message (Optional)</label>
                        <textarea class="form-control" id="resendCustomMessage" name="custom_message" rows="3" maxlength="1000" placeholder="Add a personal message..."></textarea>
                        <div class="form-text text-muted">
                            <span id="resendMessageCount">0</span>/1000 characters
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="resendTemplateId" class="form-label">Email Template (Optional)</label>
                        <select class="form-select" id="resendTemplateId" name="template_id">
                            <option value="">Use Default Template</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" onclick="submitResend()">
                    <i class="fas fa-redo me-1"></i>
                    Resend Invitation
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Character counter for resend message
    $('#resendCustomMessage').on('input', function() {
        const length = $(this).val().length;
        $('#resendMessageCount').text(length);
        
        if (length > 900) {
            $('#resendMessageCount').addClass('text-warning');
        } else {
            $('#resendMessageCount').removeClass('text-warning');
        }
    });
});

/**
 * Refresh statistics
 */
function refreshStats() {
    $.get('{{ route("admin.invitations.stats") }}')
        .done(function(response) {
            if (response.success) {
                $('#totalSent').text(response.data.total_sent || 0);
                $('#totalDelivered').text(response.data.total_delivered || 0);
                $('#totalPending').text(response.data.total_pending || 0);
                $('#totalFailed').text(response.data.total_failed || 0);
                
                showToast('Statistics refreshed successfully', 'success');
            }
        })
        .fail(function() {
            showToast('Failed to refresh statistics', 'error');
        });
}

/**
 * Refresh invitations table
 */
function refreshInvitations() {
    location.reload();
}

/**
 * View invitation history for a user
 */
function viewHistory(userId) {
    $('#historyModal').modal('show');
    
    $.get(`{{ route('admin.invitations.history', ':user') }}`.replace(':user', userId))
        .done(function(response) {
            if (response.success) {
                let html = '';
                
                if (response.data.length > 0) {
                    html = '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Status</th><th>Sent</th><th>Delivered</th><th>Error</th></tr></thead><tbody>';
                    
                    response.data.forEach(function(item) {
                        const statusClass = getStatusClass(item.status);
                        html += `<tr>
                            <td><span class="badge bg-${statusClass}">${item.status}</span></td>
                            <td>${item.sent_at || 'Not sent'}</td>
                            <td>${item.delivered_at || 'Not delivered'}</td>
                            <td>${item.error_message || '-'}</td>
                        </tr>`;
                    });
                    
                    html += '</tbody></table></div>';
                } else {
                    html = '<div class="text-center py-4"><p class="text-muted">No invitation history found.</p></div>';
                }
                
                $('#historyContent').html(html);
            }
        })
        .fail(function() {
            $('#historyContent').html('<div class="alert alert-danger">Failed to load invitation history.</div>');
        });
}

/**
 * Resend invitation
 */
function resendInvitation(userId) {
    $('#resendUserId').val(userId);
    $('#resendModal').modal('show');
}

/**
 * Submit resend form
 */
function submitResend() {
    const formData = {
        user_id: $('#resendUserId').val(),
        custom_message: $('#resendCustomMessage').val(),
        template_id: $('#resendTemplateId').val()
    };
    
    $.post('{{ route("admin.invitations.resend") }}', formData)
        .done(function(response) {
            if (response.success) {
                $('#resendModal').modal('hide');
                showToast('Invitation resent successfully', 'success');
                refreshInvitations();
            } else {
                showToast(response.message || 'Failed to resend invitation', 'error');
            }
        })
        .fail(function() {
            showToast('Failed to resend invitation', 'error');
        });
}

/**
 * Cancel invitation
 */
function cancelInvitation(userId) {
    if (confirm('Are you sure you want to cancel this invitation?')) {
        $.post('{{ route("admin.invitations.cancel") }}', { user_id: userId })
            .done(function(response) {
                if (response.success) {
                    showToast('Invitation cancelled successfully', 'success');
                    refreshInvitations();
                } else {
                    showToast(response.message || 'Failed to cancel invitation', 'error');
                }
            })
            .fail(function() {
                showToast('Failed to cancel invitation', 'error');
            });
    }
}

/**
 * Cleanup expired invitations
 */
function cleanupExpired() {
    if (confirm('Are you sure you want to cleanup expired invitations? This action cannot be undone.')) {
        $.post('{{ route("admin.invitations.cleanup") }}')
            .done(function(response) {
                if (response.success) {
                    showToast(response.message, 'success');
                    refreshStats();
                    refreshInvitations();
                } else {
                    showToast(response.message || 'Failed to cleanup invitations', 'error');
                }
            })
            .fail(function() {
                showToast('Failed to cleanup invitations', 'error');
            });
    }
}

/**
 * Get status class for badges
 */
function getStatusClass(status) {
    switch(status) {
        case 'sent':
        case 'delivered':
            return 'success';
        case 'pending':
        case 'queued':
        case 'processing':
            return 'warning';
        case 'failed':
        case 'bounced':
            return 'danger';
        case 'cancelled':
            return 'secondary';
        default:
            return 'info';
    }
}

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    // Implementation depends on your toast system
    // This is a placeholder - you might use Bootstrap Toast, Toastr, etc.
    console.log(`${type.toUpperCase()}: ${message}`);
}
</script>
@endpush