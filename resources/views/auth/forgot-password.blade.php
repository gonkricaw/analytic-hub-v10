@extends('layouts.auth')

@section('title', 'Forgot Password')

@section('content')
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">
                <h2>Analytics Hub</h2>
            </div>
            <h3>Forgot Password</h3>
            <p class="auth-subtitle">Enter your email address and we'll send you a link to reset your password.</p>
        </div>

        @if (session('status'))
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle"></i>
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle"></i>
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="auth-form" id="forgotPasswordForm">
            @csrf

            <div class="form-group">
                <label for="email" class="form-label">
                    <i class="fas fa-envelope"></i>
                    Email Address
                </label>
                <input 
                    id="email" 
                    type="email" 
                    class="form-control @error('email') is-invalid @enderror" 
                    name="email" 
                    value="{{ old('email') }}" 
                    required 
                    autocomplete="email" 
                    autofocus
                    placeholder="Enter your email address"
                >
                @error('email')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block" id="submitBtn">
                    <span class="btn-text">
                        <i class="fas fa-paper-plane"></i>
                        Send Reset Link
                    </span>
                    <span class="btn-loading" style="display: none;">
                        <i class="fas fa-spinner fa-spin"></i>
                        Sending...
                    </span>
                </button>
            </div>

            <div class="auth-links">
                <a href="{{ route('login') }}" class="auth-link">
                    <i class="fas fa-arrow-left"></i>
                    Back to Login
                </a>
            </div>
        </form>

        <div class="security-notice">
            <div class="security-header">
                <i class="fas fa-shield-alt"></i>
                Security Information
            </div>
            <ul class="security-list">
                <li>Reset links expire in 2 hours for security</li>
                <li>Each link can only be used once</li>
                <li>You can request a new link after 30 seconds</li>
                <li>Maximum 5 requests per hour per email/IP</li>
            </ul>
        </div>
    </div>
</div>

<style>
.auth-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 20px;
}

.auth-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    padding: 40px;
    width: 100%;
    max-width: 450px;
    position: relative;
}

.auth-header {
    text-align: center;
    margin-bottom: 30px;
}

.auth-logo h2 {
    color: #667eea;
    font-weight: 700;
    margin-bottom: 10px;
    font-size: 28px;
}

.auth-header h3 {
    color: #333;
    font-weight: 600;
    margin-bottom: 10px;
    font-size: 24px;
}

.auth-subtitle {
    color: #666;
    font-size: 14px;
    line-height: 1.5;
    margin-bottom: 0;
}

.alert {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    border: none;
    font-size: 14px;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
}

.alert i {
    margin-right: 8px;
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
    font-size: 14px;
}

.form-label i {
    margin-right: 8px;
    color: #667eea;
    width: 16px;
}

.form-control {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e1e5e9;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
    background-color: #f8f9fa;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
    background-color: white;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-control.is-invalid {
    border-color: #dc3545;
}

.invalid-feedback {
    color: #dc3545;
    font-size: 12px;
    margin-top: 5px;
    display: block;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.btn-primary:active {
    transform: translateY(0);
}

.btn-block {
    width: 100%;
}

.btn i {
    margin-right: 8px;
}

.auth-links {
    text-align: center;
    margin-top: 20px;
}

.auth-link {
    color: #667eea;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: color 0.3s ease;
}

.auth-link:hover {
    color: #764ba2;
    text-decoration: none;
}

.auth-link i {
    margin-right: 6px;
}

.security-notice {
    margin-top: 30px;
    padding: 20px;
    background-color: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #667eea;
}

.security-header {
    font-weight: 600;
    color: #333;
    margin-bottom: 10px;
    font-size: 14px;
}

.security-header i {
    margin-right: 8px;
    color: #667eea;
}

.security-list {
    margin: 0;
    padding-left: 20px;
    color: #666;
    font-size: 13px;
    line-height: 1.6;
}

.security-list li {
    margin-bottom: 5px;
}

/* Loading state */
.btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none !important;
}

/* Responsive */
@media (max-width: 480px) {
    .auth-card {
        padding: 30px 20px;
        margin: 10px;
    }
    
    .auth-logo h2 {
        font-size: 24px;
    }
    
    .auth-header h3 {
        font-size: 20px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('forgotPasswordForm');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = submitBtn.querySelector('.btn-text');
    const btnLoading = submitBtn.querySelector('.btn-loading');
    
    form.addEventListener('submit', function() {
        // Show loading state
        submitBtn.disabled = true;
        btnText.style.display = 'none';
        btnLoading.style.display = 'inline-flex';
        
        // Re-enable after 5 seconds (in case of network issues)
        setTimeout(function() {
            submitBtn.disabled = false;
            btnText.style.display = 'inline-flex';
            btnLoading.style.display = 'none';
        }, 5000);
    });
    
    // Auto-hide success messages after 10 seconds
    const successAlert = document.querySelector('.alert-success');
    if (successAlert) {
        setTimeout(function() {
            successAlert.style.opacity = '0';
            setTimeout(function() {
                successAlert.style.display = 'none';
            }, 300);
        }, 10000);
    }
});
</script>
@endsection