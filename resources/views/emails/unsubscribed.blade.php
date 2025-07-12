@extends('layouts.app')

@section('title', 'Successfully Unsubscribed')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                    </div>
                    
                    <h2 class="text-success mb-3">Successfully Unsubscribed</h2>
                    
                    <p class="lead mb-4">
                        You have been successfully unsubscribed from our email notifications.
                    </p>
                    
                    @if($email)
                        <div class="alert alert-info">
                            <i class="fas fa-envelope me-2"></i>
                            The email address <strong>{{ $email }}</strong> has been removed from our mailing list.
                        </div>
                    @endif
                    
                    <div class="mb-4">
                        <h5>What happens next?</h5>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="fas fa-clock text-primary me-2"></i>
                                It may take up to 24 hours for the changes to take effect
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-shield-alt text-primary me-2"></i>
                                You may still receive important security and account notifications
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-undo text-primary me-2"></i>
                                You can resubscribe at any time from your account settings
                            </li>
                        </ul>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Note:</strong> If you have an active account with us, you may still receive essential account-related communications such as password resets, security alerts, and billing notifications.
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="{{ url('/') }}" class="btn btn-primary me-md-2">
                            <i class="fas fa-home me-2"></i>Return to Homepage
                        </a>
                        <a href="{{ url('/profile') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-user-cog me-2"></i>Manage Account Settings
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Feedback Section -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-comment-alt me-2"></i>
                        Help Us Improve
                    </h5>
                </div>
                <div class="card-body">
                    <p>We're sorry to see you go! Your feedback helps us improve our email communications.</p>
                    
                    <form action="{{ route('feedback.submit') }}" method="POST" class="feedback-form">
                        @csrf
                        <input type="hidden" name="type" value="unsubscribe_feedback">
                        <input type="hidden" name="email" value="{{ $email }}">
                        
                        <div class="mb-3">
                            <label for="feedback" class="form-label">What could we have done better?</label>
                            <textarea class="form-control" id="feedback" name="feedback" rows="4" placeholder="Your feedback is valuable to us..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="contact_allowed" name="contact_allowed" value="1">
                                <label class="form-check-label" for="contact_allowed">
                                    It's okay to contact me about this feedback
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-paper-plane me-2"></i>Send Feedback
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Resubscribe Section -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-undo me-2"></i>
                        Changed Your Mind?
                    </h5>
                </div>
                <div class="card-body">
                    <p>If you change your mind, you can easily resubscribe to our emails:</p>
                    
                    <ul>
                        <li>Log into your account and update your email preferences</li>
                        <li>Contact our support team for assistance</li>
                        <li>Sign up again using our newsletter subscription form</li>
                    </ul>
                    
                    <div class="d-grid gap-2 d-md-flex">
                        <a href="{{ url('/profile/email-preferences') }}" class="btn btn-success me-md-2">
                            <i class="fas fa-envelope me-2"></i>Resubscribe Now
                        </a>
                        <a href="{{ url('/contact') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-headset me-2"></i>Contact Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.feedback-form {
    background-color: #f8f9fa;
    padding: 1.5rem;
    border-radius: 0.5rem;
    border: 1px solid #dee2e6;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.text-success {
    color: #198754 !important;
}

.alert {
    border: none;
    border-radius: 0.5rem;
}

.btn {
    border-radius: 0.375rem;
    font-weight: 500;
}

.list-unstyled li {
    padding: 0.25rem 0;
}
</style>

<script>
// Handle feedback form submission
document.querySelector('.feedback-form')?.addEventListener('submit', function(e) {
    const feedback = document.getElementById('feedback').value.trim();
    
    if (!feedback) {
        e.preventDefault();
        alert('Please provide some feedback before submitting.');
        return;
    }
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending...';
    submitBtn.disabled = true;
    
    // Note: In a real implementation, you'd handle the AJAX submission here
    // For now, we'll let the form submit normally
});

// Auto-hide success messages after 5 seconds
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert-success');
    alerts.forEach(function(alert) {
        alert.style.transition = 'opacity 0.5s';
        alert.style.opacity = '0';
        setTimeout(function() {
            alert.remove();
        }, 500);
    });
}, 5000);
</script>
@endsection