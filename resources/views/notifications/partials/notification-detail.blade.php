<div class="notification-detail">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <div class="d-flex align-items-center mb-2">
                <!-- Type Badge -->
                <span class="badge me-2 fs-6
                    @if($notification->type === 'system') bg-primary
                    @elseif($notification->type === 'announcement') bg-info
                    @elseif($notification->type === 'alert') bg-danger
                    @else bg-secondary
                    @endif">
                    {{ ucfirst($notification->type) }}
                </span>
                
                <!-- Priority Badge -->
                @if($notification->priority === 'high')
                    <span class="badge bg-danger fs-6 me-2">High Priority</span>
                @elseif($notification->priority === 'normal')
                    <span class="badge bg-primary fs-6 me-2">Normal Priority</span>
                @else
                    <span class="badge bg-secondary fs-6 me-2">Low Priority</span>
                @endif
                
                <!-- Status Badge -->
                @if($notification->pivot->is_read)
                    <span class="badge bg-success fs-6">Read</span>
                @else
                    <span class="badge bg-warning fs-6">Unread</span>
                @endif
            </div>
            
            <!-- Category -->
            @if($notification->category)
                <div class="mb-2">
                    <small class="text-muted">
                        <i class="fas fa-tag me-1"></i>
                        Category: <strong>{{ $notification->category }}</strong>
                    </small>
                </div>
            @endif
        </div>
        
        <!-- Timestamp -->
        <div class="text-end">
            <small class="text-muted">
                <i class="fas fa-clock me-1"></i>
                {{ $notification->created_at->format('M d, Y H:i:s') }}
            </small>
            <br>
            <small class="text-muted">
                {{ $notification->created_at->diffForHumans() }}
            </small>
        </div>
    </div>
    
    <!-- Title -->
    <h4 class="mb-3">{{ $notification->title }}</h4>
    
    <!-- Message -->
    <div class="notification-message mb-4">
        <div class="border rounded p-3 bg-light">
            {!! nl2br(e($notification->message)) !!}
        </div>
    </div>
    
    <!-- Action Button -->
    @if($notification->action_url)
        <div class="mb-4">
            <a href="{{ $notification->action_url }}" 
               class="btn btn-primary" 
               target="_blank"
               onclick="markAsRead({{ $notification->id }})">
                <i class="fas fa-external-link-alt me-2"></i>
                {{ $notification->action_text ?: 'View Details' }}
            </a>
        </div>
    @endif
    
    <!-- Expiry Information -->
    @if($notification->expires_at)
        <div class="alert alert-warning mb-4">
            <i class="fas fa-hourglass-end me-2"></i>
            <strong>Expiry Notice:</strong>
            @if($notification->expires_at->isFuture())
                This notification will expire on {{ $notification->expires_at->format('M d, Y H:i:s') }}
                ({{ $notification->expires_at->diffForHumans() }}).
            @else
                This notification expired on {{ $notification->expires_at->format('M d, Y H:i:s') }}
                ({{ $notification->expires_at->diffForHumans() }}).
            @endif
        </div>
    @endif
    
    <!-- Read Status -->
    @if($notification->pivot->is_read)
        <div class="alert alert-success mb-4">
            <i class="fas fa-check-circle me-2"></i>
            <strong>Read:</strong> 
            You read this notification on {{ $notification->pivot->read_at->format('M d, Y H:i:s') }}
            ({{ $notification->pivot->read_at->diffForHumans() }}).
        </div>
    @endif
    
    <!-- Metadata -->
    <div class="border-top pt-3 mt-4">
        <div class="row">
            <div class="col-md-6">
                <small class="text-muted">
                    <strong>Notification ID:</strong> {{ $notification->id }}
                </small>
            </div>
            <div class="col-md-6 text-end">
                <small class="text-muted">
                    <strong>Received:</strong> {{ $notification->pivot->created_at->format('M d, Y H:i:s') }}
                </small>
            </div>
        </div>
    </div>
</div>

<style>
.notification-detail .notification-message {
    font-size: 1.1rem;
    line-height: 1.6;
}

.notification-detail .badge {
    font-size: 0.8rem;
}

.notification-detail .alert {
    border-left: 4px solid;
}

.notification-detail .alert-warning {
    border-left-color: #f6c23e;
}

.notification-detail .alert-success {
    border-left-color: #1cc88a;
}
</style>