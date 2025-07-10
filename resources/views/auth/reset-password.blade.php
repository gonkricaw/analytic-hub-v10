@extends('layouts.auth')

@section('title', 'Reset Password')

@section('content')
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">
                <h2>Analytics Hub</h2>
            </div>
            <h3>Reset Password</h3>
            <p class="auth-subtitle">Enter your new password below. Make sure it meets our security requirements.</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle"></i>
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="auth-form" id="resetPasswordForm">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">

            <div class="form-group">
                <label for="email" class="form-label">
                    <i class="fas fa-envelope"></i>
                    Email Address
                </label>
                <input 
                    id="email_display" 
                    type="email" 
                    class="form-control" 
                    value="{{ $email }}" 
                    disabled
                    readonly
                >
                <small class="form-text text-muted">This is the email address associated with your account.</small>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">
                    <i class="fas fa-lock"></i>
                    New Password
                </label>
                <div class="password-input-wrapper">
                    <input 
                        id="password" 
                        type="password" 
                        class="form-control @error('password') is-invalid @enderror" 
                        name="password" 
                        required 
                        autocomplete="new-password"
                        placeholder="Enter your new password"
                    >
                    <button type="button" class="password-toggle" onclick="togglePassword('password')">
                        <i class="fas fa-eye" id="password-eye"></i>
                    </button>
                </div>
                @error('password')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
                <div class="password-strength" id="passwordStrength" style="display: none;">
                    <div class="strength-bar">
                        <div class="strength-fill" id="strengthFill"></div>
                    </div>
                    <div class="strength-text" id="strengthText"></div>
                </div>
            </div>

            <div class="form-group">
                <label for="password_confirmation" class="form-label">
                    <i class="fas fa-lock"></i>
                    Confirm New Password
                </label>
                <div class="password-input-wrapper">
                    <input 
                        id="password_confirmation" 
                        type="password" 
                        class="form-control" 
                        name="password_confirmation" 
                        required 
                        autocomplete="new-password"
                        placeholder="Confirm your new password"
                    >
                    <button type="button" class="password-toggle" onclick="togglePassword('password_confirmation')">
                        <i class="fas fa-eye" id="password_confirmation-eye"></i>
                    </button>
                </div>
                <div class="password-match" id="passwordMatch" style="display: none;"></div>
            </div>

            <div class="password-requirements">
                <div class="requirements-header">
                    <i class="fas fa-info-circle"></i>
                    Password Requirements
                </div>
                <ul class="requirements-list">
                    <li id="req-length">At least 8 characters long</li>
                    <li id="req-uppercase">Contains uppercase letter (A-Z)</li>
                    <li id="req-lowercase">Contains lowercase letter (a-z)</li>
                    <li id="req-number">Contains number (0-9)</li>
                    <li id="req-special">Contains special character (!@#$%^&*)</li>
                </ul>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block" id="submitBtn">
                    <span class="btn-text">
                        <i class="fas fa-key"></i>
                        Reset Password
                    </span>
                    <span class="btn-loading" style="display: none;">
                        <i class="fas fa-spinner fa-spin"></i>
                        Resetting...
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
                Security Notice
            </div>
            <ul class="security-list">
                <li>Your new password will be encrypted and stored securely</li>
                <li>You cannot reuse your last 5 passwords</li>
                <li>This reset link will expire and cannot be used again</li>
                <li>You'll be automatically logged out from all devices</li>
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
    max-width: 500px;
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

.form-control:disabled {
    background-color: #e9ecef;
    opacity: 1;
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

.form-text {
    font-size: 12px;
    color: #6c757d;
    margin-top: 5px;
}

.password-input-wrapper {
    position: relative;
}

.password-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #6c757d;
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    transition: color 0.3s ease;
}

.password-toggle:hover {
    color: #667eea;
}

.password-strength {
    margin-top: 8px;
}

.strength-bar {
    height: 4px;
    background-color: #e1e5e9;
    border-radius: 2px;
    overflow: hidden;
    margin-bottom: 5px;
}

.strength-fill {
    height: 100%;
    transition: all 0.3s ease;
    border-radius: 2px;
}

.strength-text {
    font-size: 12px;
    font-weight: 500;
}

.password-match {
    margin-top: 5px;
    font-size: 12px;
    font-weight: 500;
}

.password-requirements {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    border-left: 4px solid #667eea;
}

.requirements-header {
    font-weight: 600;
    color: #333;
    margin-bottom: 10px;
    font-size: 14px;
}

.requirements-header i {
    margin-right: 8px;
    color: #667eea;
}

.requirements-list {
    margin: 0;
    padding-left: 20px;
    font-size: 13px;
    line-height: 1.6;
}

.requirements-list li {
    margin-bottom: 5px;
    color: #dc3545;
    transition: color 0.3s ease;
}

.requirements-list li.valid {
    color: #28a745;
}

.requirements-list li.valid::before {
    content: '✓ ';
    font-weight: bold;
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

.btn-primary:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none !important;
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
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const eye = document.getElementById(fieldId + '-eye');
    
    if (field.type === 'password') {
        field.type = 'text';
        eye.classList.remove('fa-eye');
        eye.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        eye.classList.remove('fa-eye-slash');
        eye.classList.add('fa-eye');
    }
}

function checkPasswordStrength(password) {
    let score = 0;
    let feedback = [];
    
    // Length check
    if (password.length >= 8) score += 20;
    if (password.length >= 12) score += 10;
    
    // Character variety
    if (/[a-z]/.test(password)) score += 20;
    if (/[A-Z]/.test(password)) score += 20;
    if (/[0-9]/.test(password)) score += 15;
    if (/[^A-Za-z0-9]/.test(password)) score += 15;
    
    // Determine strength level
    let level, color, text;
    if (score < 40) {
        level = 'weak';
        color = '#dc3545';
        text = 'Weak';
    } else if (score < 70) {
        level = 'medium';
        color = '#ffc107';
        text = 'Medium';
    } else if (score < 90) {
        level = 'strong';
        color = '#28a745';
        text = 'Strong';
    } else {
        level = 'very-strong';
        color = '#20c997';
        text = 'Very Strong';
    }
    
    return { score, level, color, text };
}

function updatePasswordRequirements(password) {
    const requirements = {
        'req-length': password.length >= 8,
        'req-uppercase': /[A-Z]/.test(password),
        'req-lowercase': /[a-z]/.test(password),
        'req-number': /[0-9]/.test(password),
        'req-special': /[^A-Za-z0-9]/.test(password)
    };
    
    Object.keys(requirements).forEach(reqId => {
        const element = document.getElementById(reqId);
        if (requirements[reqId]) {
            element.classList.add('valid');
        } else {
            element.classList.remove('valid');
        }
    });
    
    return Object.values(requirements).every(req => req);
}

function checkPasswordMatch() {
    const password = document.getElementById('password').value;
    const confirmation = document.getElementById('password_confirmation').value;
    const matchElement = document.getElementById('passwordMatch');
    
    if (confirmation.length > 0) {
        matchElement.style.display = 'block';
        if (password === confirmation) {
            matchElement.textContent = '✓ Passwords match';
            matchElement.style.color = '#28a745';
            return true;
        } else {
            matchElement.textContent = '✗ Passwords do not match';
            matchElement.style.color = '#dc3545';
            return false;
        }
    } else {
        matchElement.style.display = 'none';
        return false;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const passwordField = document.getElementById('password');
    const confirmationField = document.getElementById('password_confirmation');
    const strengthElement = document.getElementById('passwordStrength');
    const strengthFill = document.getElementById('strengthFill');
    const strengthText = document.getElementById('strengthText');
    const submitBtn = document.getElementById('submitBtn');
    const form = document.getElementById('resetPasswordForm');
    
    passwordField.addEventListener('input', function() {
        const password = this.value;
        
        if (password.length > 0) {
            strengthElement.style.display = 'block';
            
            const strength = checkPasswordStrength(password);
            strengthFill.style.width = strength.score + '%';
            strengthFill.style.backgroundColor = strength.color;
            strengthText.textContent = strength.text;
            strengthText.style.color = strength.color;
        } else {
            strengthElement.style.display = 'none';
        }
        
        updatePasswordRequirements(password);
        checkPasswordMatch();
    });
    
    confirmationField.addEventListener('input', checkPasswordMatch);
    
    form.addEventListener('submit', function() {
        const btnText = submitBtn.querySelector('.btn-text');
        const btnLoading = submitBtn.querySelector('.btn-loading');
        
        submitBtn.disabled = true;
        btnText.style.display = 'none';
        btnLoading.style.display = 'inline-flex';
    });
});
</script>
@endsection