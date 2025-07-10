@extends('layouts.auth')

@section('title', 'Password Expired - Change Required')

@section('content')
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="logo-container">
                <div class="logo-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h1 class="logo-text">Analytics Hub</h1>
            </div>
            <h2 class="auth-title">
                <i class="fas fa-clock"></i>
                Password Expired
            </h2>
            <p class="auth-subtitle">
                Your password has expired and must be changed to continue accessing the system.
            </p>
        </div>

        <!-- Expiry Information -->
        @if(isset($expiryInfo))
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Password Expired:</strong>
                @if(isset($expiryInfo['days_expired']) && $expiryInfo['days_expired'] > 0)
                    Your password expired {{ $expiryInfo['days_expired'] }} day(s) ago.
                @else
                    Your password has expired.
                @endif
                Please set a new password to continue.
            </div>
        @endif

        @if(session('message'))
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                {{ session('message') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Please fix the following errors:</strong>
                <ul class="error-list">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('password.expired.update') }}" class="auth-form" id="passwordChangeForm">
            @csrf

            <div class="form-group">
                <label for="current_password" class="form-label">
                    <i class="fas fa-lock"></i>
                    Current Password
                </label>
                <div class="password-input-wrapper">
                    <input 
                        id="current_password" 
                        type="password" 
                        class="form-control @error('current_password') is-invalid @enderror" 
                        name="current_password" 
                        required 
                        autocomplete="current-password"
                        placeholder="Enter your current password"
                    >
                    <button type="button" class="password-toggle" onclick="togglePassword('current_password')">
                        <i class="fas fa-eye" id="current_password_icon"></i>
                    </button>
                </div>
                @error('current_password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password" class="form-label">
                    <i class="fas fa-key"></i>
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
                        oninput="checkPasswordStrength()"
                    >
                    <button type="button" class="password-toggle" onclick="togglePassword('password')">
                        <i class="fas fa-eye" id="password_icon"></i>
                    </button>
                </div>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                
                <!-- Password Strength Indicator -->
                <div class="password-strength" id="passwordStrength">
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
                        oninput="checkPasswordMatch()"
                    >
                    <button type="button" class="password-toggle" onclick="togglePassword('password_confirmation')">
                        <i class="fas fa-eye" id="password_confirmation_icon"></i>
                    </button>
                </div>
                <div class="password-match" id="passwordMatch"></div>
            </div>

            <!-- Password Requirements -->
            <div class="password-requirements">
                <h4><i class="fas fa-shield-alt"></i> Password Requirements</h4>
                <ul class="requirements-list">
                    <li id="req-length">At least 8 characters long</li>
                    <li id="req-uppercase">Contains uppercase letter (A-Z)</li>
                    <li id="req-lowercase">Contains lowercase letter (a-z)</li>
                    <li id="req-number">Contains number (0-9)</li>
                    <li id="req-special">Contains special character (!@#$%^&*)</li>
                    <li id="req-history">Different from your last 5 passwords</li>
                </ul>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-block" id="submitBtn" disabled>
                    <i class="fas fa-sync-alt"></i>
                    Update Password
                </button>
            </div>
        </form>

        <div class="auth-footer">
            <div class="security-notice">
                <i class="fas fa-shield-alt"></i>
                <strong>Security Notice:</strong> Your new password will be valid for 90 days. You'll receive a reminder before it expires.
            </div>
            
            <div class="expiry-info">
                <i class="fas fa-calendar-alt"></i>
                <strong>Password Policy:</strong> Passwords expire every 90 days to maintain security.
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner">
        <i class="fas fa-spinner fa-spin"></i>
        <p>Updating your password...</p>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Password visibility toggle
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '_icon');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Password strength checker
function checkPasswordStrength() {
    const password = document.getElementById('password').value;
    const strengthFill = document.getElementById('strengthFill');
    const strengthText = document.getElementById('strengthText');
    
    let score = 0;
    let feedback = [];
    
    // Length check
    if (password.length >= 8) {
        score += 20;
        document.getElementById('req-length').classList.add('valid');
    } else {
        document.getElementById('req-length').classList.remove('valid');
        feedback.push('At least 8 characters');
    }
    
    // Uppercase check
    if (/[A-Z]/.test(password)) {
        score += 20;
        document.getElementById('req-uppercase').classList.add('valid');
    } else {
        document.getElementById('req-uppercase').classList.remove('valid');
        feedback.push('Uppercase letter');
    }
    
    // Lowercase check
    if (/[a-z]/.test(password)) {
        score += 20;
        document.getElementById('req-lowercase').classList.add('valid');
    } else {
        document.getElementById('req-lowercase').classList.remove('valid');
        feedback.push('Lowercase letter');
    }
    
    // Number check
    if (/[0-9]/.test(password)) {
        score += 20;
        document.getElementById('req-number').classList.add('valid');
    } else {
        document.getElementById('req-number').classList.remove('valid');
        feedback.push('Number');
    }
    
    // Special character check
    if (/[!@#$%^&*()_+\-=\[\]{}|;:,.<>?]/.test(password)) {
        score += 20;
        document.getElementById('req-special').classList.add('valid');
    } else {
        document.getElementById('req-special').classList.remove('valid');
        feedback.push('Special character');
    }
    
    // Update strength indicator
    strengthFill.style.width = score + '%';
    
    if (score < 40) {
        strengthFill.className = 'strength-fill weak';
        strengthText.textContent = 'Weak - Missing: ' + feedback.join(', ');
        strengthText.className = 'strength-text weak';
    } else if (score < 80) {
        strengthFill.className = 'strength-fill medium';
        strengthText.textContent = 'Medium - Missing: ' + feedback.join(', ');
        strengthText.className = 'strength-text medium';
    } else if (score < 100) {
        strengthFill.className = 'strength-fill good';
        strengthText.textContent = 'Good - Missing: ' + feedback.join(', ');
        strengthText.className = 'strength-text good';
    } else {
        strengthFill.className = 'strength-fill strong';
        strengthText.textContent = 'Strong password!';
        strengthText.className = 'strength-text strong';
    }
    
    checkFormValidity();
}

// Password match checker
function checkPasswordMatch() {
    const password = document.getElementById('password').value;
    const confirmation = document.getElementById('password_confirmation').value;
    const matchDiv = document.getElementById('passwordMatch');
    
    if (confirmation === '') {
        matchDiv.textContent = '';
        matchDiv.className = 'password-match';
    } else if (password === confirmation) {
        matchDiv.textContent = '✓ Passwords match';
        matchDiv.className = 'password-match valid';
    } else {
        matchDiv.textContent = '✗ Passwords do not match';
        matchDiv.className = 'password-match invalid';
    }
    
    checkFormValidity();
}

// Form validity checker
function checkFormValidity() {
    const password = document.getElementById('password').value;
    const confirmation = document.getElementById('password_confirmation').value;
    const currentPassword = document.getElementById('current_password').value;
    const submitBtn = document.getElementById('submitBtn');
    
    // Check all requirements
    const hasLength = password.length >= 8;
    const hasUpper = /[A-Z]/.test(password);
    const hasLower = /[a-z]/.test(password);
    const hasNumber = /[0-9]/.test(password);
    const hasSpecial = /[!@#$%^&*()_+\-=\[\]{}|;:,.<>?]/.test(password);
    const passwordsMatch = password === confirmation && confirmation !== '';
    const hasCurrentPassword = currentPassword !== '';
    
    const isValid = hasLength && hasUpper && hasLower && hasNumber && hasSpecial && passwordsMatch && hasCurrentPassword;
    
    submitBtn.disabled = !isValid;
    
    if (isValid) {
        submitBtn.classList.remove('btn-disabled');
        submitBtn.classList.add('btn-primary');
    } else {
        submitBtn.classList.add('btn-disabled');
        submitBtn.classList.remove('btn-primary');
    }
}

// Form submission handler
document.getElementById('passwordChangeForm').addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('submitBtn');
    const loadingOverlay = document.getElementById('loadingOverlay');
    
    if (!submitBtn.disabled) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating Password...';
        loadingOverlay.style.display = 'flex';
    }
});

// Initialize form validation on page load
document.addEventListener('DOMContentLoaded', function() {
    checkFormValidity();
    
    // Add event listeners
    document.getElementById('current_password').addEventListener('input', checkFormValidity);
    document.getElementById('password').addEventListener('input', function() {
        checkPasswordStrength();
        checkPasswordMatch();
    });
    document.getElementById('password_confirmation').addEventListener('input', checkPasswordMatch);
});
</script>
@endsection