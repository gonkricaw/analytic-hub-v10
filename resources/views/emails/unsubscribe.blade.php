@extends('layouts.app')

@section('title', 'Unsubscribe from Email Notifications')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-envelope-open-text me-2"></i>
                        Unsubscribe from Email Notifications
                    </h4>
                </div>
                
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        We're sorry to see you go! You can unsubscribe from our email notifications below.
                    </div>
                    
                    <form method="POST" action="{{ route('email.unsubscribe.process') }}" id="unsubscribeForm">
                        @csrf
                        
                        @if($messageId)
                            <input type="hidden" name="message_id" value="{{ $messageId }}">
                        @endif
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email', $email) }}" 
                                   required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Enter the email address you want to unsubscribe from our notifications.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Unsubscribe Options</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="unsubscribe_type" id="unsubscribe_all" value="all" checked>
                                <label class="form-check-label" for="unsubscribe_all">
                                    <strong>Unsubscribe from all emails</strong>
                                    <small class="d-block text-muted">You will no longer receive any email notifications from us.</small>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="unsubscribe_type" id="unsubscribe_marketing" value="marketing">
                                <label class="form-check-label" for="unsubscribe_marketing">
                                    <strong>Unsubscribe from marketing emails only</strong>
                                    <small class="d-block text-muted">You will still receive important account and security notifications.</small>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="unsubscribe_type" id="unsubscribe_newsletters" value="newsletters">
                                <label class="form-check-label" for="unsubscribe_newsletters">
                                    <strong>Unsubscribe from newsletters only</strong>
                                    <small class="d-block text-muted">You will still receive account updates and marketing emails.</small>
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason for Unsubscribing (Optional)</label>
                            <select class="form-select" id="reason" name="reason">
                                <option value="">Please select a reason...</option>
                                <option value="too_frequent">Emails are too frequent</option>
                                <option value="not_relevant">Content is not relevant to me</option>
                                <option value="never_signed_up">I never signed up for this</option>
                                <option value="technical_issues">Technical issues with emails</option>
                                <option value="privacy_concerns">Privacy concerns</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div class="mb-3" id="otherReasonDiv" style="display: none;">
                            <label for="other_reason" class="form-label">Please specify</label>
                            <textarea class="form-control" id="other_reason" name="other_reason" rows="3" placeholder="Please tell us more about your reason for unsubscribing..."></textarea>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Important:</strong> If you unsubscribe from all emails, you may miss important account security notifications and system updates.
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="{{ url('/') }}" class="btn btn-secondary me-md-2">
                                <i class="fas fa-arrow-left me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-danger" id="unsubscribeBtn">
                                <i class="fas fa-unlink me-2"></i>Unsubscribe
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Alternative Options -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-cog me-2"></i>
                        Alternative Options
                    </h5>
                </div>
                <div class="card-body">
                    <p>Instead of unsubscribing completely, you might want to:</p>
                    <ul>
                        <li><strong>Update your email preferences:</strong> Choose which types of emails you want to receive</li>
                        <li><strong>Change email frequency:</strong> Receive emails less frequently</li>
                        <li><strong>Update your profile:</strong> Make sure we're sending you relevant content</li>
                    </ul>
                    <a href="{{ url('/profile') }}" class="btn btn-outline-primary">
                        <i class="fas fa-user-cog me-2"></i>Manage Email Preferences
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Show/hide other reason textarea
document.getElementById('reason').addEventListener('change', function() {
    const otherReasonDiv = document.getElementById('otherReasonDiv');
    if (this.value === 'other') {
        otherReasonDiv.style.display = 'block';
        document.getElementById('other_reason').required = true;
    } else {
        otherReasonDiv.style.display = 'none';
        document.getElementById('other_reason').required = false;
    }
});

// Confirmation before unsubscribing
document.getElementById('unsubscribeForm').addEventListener('submit', function(e) {
    const unsubscribeType = document.querySelector('input[name="unsubscribe_type"]:checked').value;
    let message = 'Are you sure you want to unsubscribe?';
    
    if (unsubscribeType === 'all') {
        message = 'Are you sure you want to unsubscribe from ALL emails? You will no longer receive any notifications from us, including important security alerts.';
    }
    
    if (!confirm(message)) {
        e.preventDefault();
    }
});
</script>
@endsection