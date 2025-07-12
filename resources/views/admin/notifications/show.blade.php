@extends('layouts.admin')

@section('title', 'View Notification')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-eye"></i> View Notification
            </h1>
            <p class="mb-0 text-muted">Notification details and delivery status</p>
        </div>
        <div>
            @if($notification->status !== 'sent')
                <a href="{{ route('admin.notifications.edit', $notification) }}" class="btn btn-warning me-2">
                    <i class="fas fa-edit"></i> Edit
                </a>
            @endif
            <a href="{{ route('admin.notifications.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Notifications
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Notification Details -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-info-circle"></i> Notification Details
                    </h6>
                    <div>
                        @if($notification->status === 'draft')
                            <span class="badge bg-secondary fs-6">Draft</span>
                        @elseif($notification->status === 'scheduled')
                            <span class="badge bg-info fs-6">Scheduled</span>
                        @elseif($notification->status === 'sent')
                            <span class="badge bg-success fs-6">Sent</span>
                        @elseif($notification->status === 'failed')
                            <span class="badge bg-danger fs-6">Failed</span>
                        @endif
                        
                        @if($notification->priority === 'high')
                            <span class="badge bg-danger fs-6 ms-1">High Priority</span>
                        @elseif($notification->priority === 'normal')
                            <span class="badge bg-primary fs-6 ms-1">Normal Priority</span>
                        @else
                            <span class="badge bg-secondary fs-6 ms-1">Low Priority</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Title:</strong><br>
                            <span class="text-dark">{{ $notification->title }}</span>
                        </div>
                        <div class="col-md-3">
                            <strong>Type:</strong><br>
                            <span class="badge bg-info">{{ ucfirst($notification->type) }}</span>
                        </div>
                        <div class="col-md-3">
                            <strong>Category:</strong><br>
                            <span class="text-muted">{{ $notification->category ?: 'None' }}</span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Message:</strong><br>
                        <div class="border rounded p-3 bg-light">
                            {!! nl2br(e($notification->message)) !!}
                        </div>
                    </div>
                    
                    @if($notification->action_url)
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <strong>Action URL:</strong><br>
                                <a href="{{ $notification->action_url }}" target="_blank" class="text-primary">
                                    {{ $notification->action_url }}
                                    <i class="fas fa-external-link-alt ms-1"></i>
                                </a>
                            </div>
                            <div class="col-md-4">
                                <strong>Action Text:</strong><br>
                                <span class="text-dark">{{ $notification->action_text ?: 'View Details' }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Targeting Information -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-users"></i> Targeting Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Target Type:</strong><br>
                            <span class="badge bg-primary">{{ ucwords(str_replace('_', ' ', $notification->target_type)) }}</span>
                        </div>
                        <div class="col-md-6">
                            <strong>Estimated Recipients:</strong><br>
                            <span class="text-dark">{{ number_format($notification->estimated_recipients ?? 0) }} users</span>
                        </div>
                    </div>
                    
                    @if($notification->target_type === 'specific_users' && $notification->target_users)
                        <div class="mb-3">
                            <strong>Target Users:</strong><br>
                            <div class="mt-2">
                                @foreach($notification->targetUsers ?? [] as $user)
                                    <span class="badge bg-light text-dark me-1 mb-1">
                                        {{ $user->name }} ({{ $user->email }})
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    
                    @if($notification->target_type === 'role_based' && $notification->target_roles)
                        <div class="mb-3">
                            <strong>Target Roles:</strong><br>
                            <div class="mt-2">
                                @foreach($notification->targetRoles ?? [] as $role)
                                    <span class="badge bg-light text-dark me-1 mb-1">
                                        {{ $role->name }} ({{ $role->users_count ?? 0 }} users)
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Delivery Statistics -->
            @if($notification->status === 'sent')
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-chart-bar"></i> Delivery Statistics
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <div class="border rounded p-3">
                                    <h4 class="text-primary mb-1">{{ number_format($deliveryStats['total_sent'] ?? 0) }}</h4>
                                    <small class="text-muted">Total Sent</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border rounded p-3">
                                    <h4 class="text-success mb-1">{{ number_format($deliveryStats['total_read'] ?? 0) }}</h4>
                                    <small class="text-muted">Read</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border rounded p-3">
                                    <h4 class="text-info mb-1">{{ number_format($deliveryStats['total_unread'] ?? 0) }}</h4>
                                    <small class="text-muted">Unread</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border rounded p-3">
                                    <h4 class="text-warning mb-1">{{ number_format($deliveryStats['total_dismissed'] ?? 0) }}</h4>
                                    <small class="text-muted">Dismissed</small>
                                </div>
                            </div>
                        </div>
                        
                        @if(($deliveryStats['total_sent'] ?? 0) > 0)
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <div class="progress" style="height: 25px;">
                                        @php
                                            $total = $deliveryStats['total_sent'];
                                            $readPercent = $total > 0 ? ($deliveryStats['total_read'] / $total) * 100 : 0;
                                            $dismissedPercent = $total > 0 ? ($deliveryStats['total_dismissed'] / $total) * 100 : 0;
                                            $unreadPercent = 100 - $readPercent - $dismissedPercent;
                                        @endphp
                                        
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: {{ $readPercent }}%" 
                                             title="Read: {{ number_format($readPercent, 1) }}%">
                                            @if($readPercent > 10) {{ number_format($readPercent, 1) }}% @endif
                                        </div>
                                        <div class="progress-bar bg-warning" role="progressbar" 
                                             style="width: {{ $dismissedPercent }}%" 
                                             title="Dismissed: {{ number_format($dismissedPercent, 1) }}%">
                                            @if($dismissedPercent > 10) {{ number_format($dismissedPercent, 1) }}% @endif
                                        </div>
                                        <div class="progress-bar bg-info" role="progressbar" 
                                             style="width: {{ $unreadPercent }}%" 
                                             title="Unread: {{ number_format($unreadPercent, 1) }}%">
                                            @if($unreadPercent > 10) {{ number_format($unreadPercent, 1) }}% @endif
                                        </div>
                                    </div>
                                    <div class="text-center mt-2">
                                        <small class="text-muted">
                                            <span class="badge bg-success">Read</span>
                                            <span class="badge bg-warning">Dismissed</span>
                                            <span class="badge bg-info">Unread</span>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Status & Timing -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-clock"></i> Status & Timing
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Created:</strong><br>
                        <span class="text-muted">{{ $notification->created_at->format('M d, Y H:i:s') }}</span><br>
                        <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                    </div>
                    
                    @if($notification->scheduled_at)
                        <div class="mb-3">
                            <strong>Scheduled For:</strong><br>
                            <span class="text-info">{{ $notification->scheduled_at->format('M d, Y H:i:s') }}</span><br>
                            <small class="text-muted">
                                @if($notification->scheduled_at->isFuture())
                                    {{ $notification->scheduled_at->diffForHumans() }}
                                @else
                                    {{ $notification->scheduled_at->diffForHumans() }}
                                @endif
                            </small>
                        </div>
                    @endif
                    
                    @if($notification->sent_at)
                        <div class="mb-3">
                            <strong>Sent At:</strong><br>
                            <span class="text-success">{{ $notification->sent_at->format('M d, Y H:i:s') }}</span><br>
                            <small class="text-muted">{{ $notification->sent_at->diffForHumans() }}</small>
                        </div>
                    @endif
                    
                    @if($notification->expires_at)
                        <div class="mb-3">
                            <strong>Expires At:</strong><br>
                            <span class="text-warning">{{ $notification->expires_at->format('M d, Y H:i:s') }}</span><br>
                            <small class="text-muted">
                                @if($notification->expires_at->isFuture())
                                    Expires {{ $notification->expires_at->diffForHumans() }}
                                @else
                                    Expired {{ $notification->expires_at->diffForHumans() }}
                                @endif
                            </small>
                        </div>
                    @endif
                    
                    @if($notification->updated_at && $notification->updated_at != $notification->created_at)
                        <div class="mb-3">
                            <strong>Last Updated:</strong><br>
                            <span class="text-muted">{{ $notification->updated_at->format('M d, Y H:i:s') }}</span><br>
                            <small class="text-muted">{{ $notification->updated_at->diffForHumans() }}</small>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Actions -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-cog"></i> Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if($notification->status !== 'sent')
                            <a href="{{ route('admin.notifications.edit', $notification) }}" class="btn btn-warning">
                                <i class="fas fa-edit"></i> Edit Notification
                            </a>
                            
                            @if($notification->status === 'draft')
                                <form action="{{ route('admin.notifications.update', $notification) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" name="action" value="send_now" class="btn btn-success w-100"
                                            onclick="return confirm('Are you sure you want to send this notification now?')">
                                        <i class="fas fa-paper-plane"></i> Send Now
                                    </button>
                                </form>
                            @endif
                        @endif
                        
                        @if($notification->status === 'sent')
                            <button class="btn btn-info" onclick="showDeliveryDetails()">
                                <i class="fas fa-chart-line"></i> View Delivery Details
                            </button>
                        @endif
                        
                        <form action="{{ route('admin.notifications.destroy', $notification) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger w-100"
                                    onclick="return confirm('Are you sure you want to delete this notification? This action cannot be undone.')">
                                <i class="fas fa-trash"></i> Delete Notification
                            </button>
                        </form>
                        
                        <a href="{{ route('admin.notifications.create') }}" class="btn btn-outline-primary">
                            <i class="fas fa-plus"></i> Create Similar
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Metadata -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-info"></i> Metadata
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong>ID:</strong> <code>{{ $notification->id }}</code>
                    </div>
                    
                    @if($notification->created_by)
                        <div class="mb-2">
                            <strong>Created By:</strong><br>
                            <span class="text-muted">{{ $notification->creator->name ?? 'System' }}</span>
                        </div>
                    @endif
                    
                    <div class="mb-2">
                        <strong>Target Type:</strong><br>
                        <code>{{ $notification->target_type }}</code>
                    </div>
                    
                    @if($notification->target_users)
                        <div class="mb-2">
                            <strong>Target User IDs:</strong><br>
                            <code>{{ implode(', ', $notification->target_users) }}</code>
                        </div>
                    @endif
                    
                    @if($notification->target_roles)
                        <div class="mb-2">
                            <strong>Target Role IDs:</strong><br>
                            <code>{{ implode(', ', $notification->target_roles) }}</code>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delivery Details Modal -->
<div class="modal fade" id="deliveryDetailsModal" tabindex="-1" aria-labelledby="deliveryDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deliveryDetailsModalLabel">
                    <i class="fas fa-chart-line"></i> Delivery Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="deliveryDetailsContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function showDeliveryDetails() {
    $('#deliveryDetailsModal').modal('show');
    
    // Load delivery details via AJAX
    $.ajax({
        url: '{{ route("admin.notifications.show", $notification) }}',
        data: { delivery_details: 1 },
        success: function(response) {
            $('#deliveryDetailsContent').html(response);
        },
        error: function() {
            $('#deliveryDetailsContent').html('<div class="alert alert-danger">Failed to load delivery details.</div>');
        }
    });
}

// Auto-refresh statistics for sent notifications
@if($notification->status === 'sent')
setInterval(function() {
    // Refresh delivery statistics every 30 seconds
    $.ajax({
        url: '{{ route("admin.notifications.show", $notification) }}',
        data: { stats_only: 1 },
        success: function(response) {
            // Update statistics section
            $('.delivery-stats').html(response);
        }
    });
}, 30000);
@endif
</script>
@endpush