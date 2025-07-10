@extends('layouts.auth')

@section('title', 'Terms & Conditions - Accept to Continue')

@section('content')
<div class="auth-container">
    <div class="auth-card terms-modal">
        <div class="auth-header">
            <div class="logo-container">
                <img src="{{ asset('images/logo.png') }}" alt="Analytics Hub" class="auth-logo">
            </div>
            <h2 class="auth-title">Terms & Conditions</h2>
            <p class="auth-subtitle">Please read and accept our Terms & Conditions to continue</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('message'))
            <div class="alert alert-info">
                {{ session('message') }}
            </div>
        @endif

        <div class="terms-content">
            <div class="terms-scroll-area">
                <div class="terms-text">
                    <h3>Analytics Hub - Terms & Conditions</h3>
                    
                    <h4>1. Acceptance of Terms</h4>
                    <p>By accessing and using the Analytics Hub application, you acknowledge that you have read, understood, and agree to be bound by these Terms & Conditions.</p>
                    
                    <h4>2. User Responsibilities</h4>
                    <p>Users are responsible for:</p>
                    <ul>
                        <li>Maintaining the confidentiality of their login credentials</li>
                        <li>Using the system only for authorized business purposes</li>
                        <li>Reporting any security incidents or unauthorized access</li>
                        <li>Complying with all applicable laws and regulations</li>
                    </ul>
                    
                    <h4>3. Data Security & Privacy</h4>
                    <p>We are committed to protecting your data and privacy:</p>
                    <ul>
                        <li>All data transmissions are encrypted using industry-standard protocols</li>
                        <li>User activities are logged for security and audit purposes</li>
                        <li>Personal information is handled in accordance with applicable privacy laws</li>
                        <li>Data access is restricted based on user roles and permissions</li>
                    </ul>
                    
                    <h4>4. System Usage</h4>
                    <p>The Analytics Hub system is provided for legitimate business use only. Prohibited activities include:</p>
                    <ul>
                        <li>Attempting to gain unauthorized access to system resources</li>
                        <li>Sharing login credentials with unauthorized individuals</li>
                        <li>Using the system for any illegal or unauthorized purposes</li>
                        <li>Attempting to circumvent security measures</li>
                    </ul>
                    
                    <h4>5. Password Policy</h4>
                    <p>Users must comply with the following password requirements:</p>
                    <ul>
                        <li>Minimum 8 characters with mixed case, numbers, and special characters</li>
                        <li>Passwords expire every 90 days and must be changed</li>
                        <li>Previous 5 passwords cannot be reused</li>
                        <li>Temporary passwords must be changed on first login</li>
                    </ul>
                    
                    <h4>6. Session Management</h4>
                    <p>For security purposes:</p>
                    <ul>
                        <li>Sessions automatically expire after 30 minutes of inactivity</li>
                        <li>Users are responsible for logging out when finished</li>
                        <li>Multiple concurrent sessions may be restricted</li>
                    </ul>
                    
                    <h4>7. Intellectual Property</h4>
                    <p>All content, reports, and data within the Analytics Hub are proprietary and confidential. Users may not:</p>
                    <ul>
                        <li>Copy, distribute, or share system content without authorization</li>
                        <li>Reverse engineer or attempt to extract system functionality</li>
                        <li>Use system data for purposes outside of authorized business activities</li>
                    </ul>
                    
                    <h4>8. Limitation of Liability</h4>
                    <p>The Analytics Hub system is provided "as is" without warranties. Users acknowledge that:</p>
                    <ul>
                        <li>System availability may be subject to maintenance and updates</li>
                        <li>Data accuracy depends on source systems and user input</li>
                        <li>Users are responsible for validating critical business decisions</li>
                    </ul>
                    
                    <h4>9. Compliance & Audit</h4>
                    <p>User activities within the system are subject to:</p>
                    <ul>
                        <li>Continuous monitoring and logging for security purposes</li>
                        <li>Periodic access reviews and compliance audits</li>
                        <li>Investigation of suspicious or unauthorized activities</li>
                    </ul>
                    
                    <h4>10. Changes to Terms</h4>
                    <p>These Terms & Conditions may be updated periodically. Users will be notified of significant changes and may be required to re-accept updated terms.</p>
                    
                    <div class="terms-footer">
                        <p><strong>Last Updated:</strong> {{ \App\Models\SystemConfig::get('terms.last_updated') ? \Carbon\Carbon::parse(\App\Models\SystemConfig::get('terms.last_updated'))->format('F j, Y') : date('F j, Y') }}</p>
                        <p><strong>Version:</strong> {{ \App\Models\SystemConfig::get('terms.current_version', '1.0') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('terms.accept.submit') }}" class="terms-form">
            @csrf
            
            <div class="form-group terms-checkbox">
                <label class="checkbox-container">
                    <input type="checkbox" id="accept_terms" name="accept_terms" value="1" required>
                    <span class="checkmark"></span>
                    <span class="checkbox-text">
                        I have read, understood, and agree to the Terms & Conditions
                    </span>
                </label>
            </div>
            
            <div class="form-actions">
                <button type="submit" id="accept-btn" class="btn btn-primary btn-block" disabled>
                    <i class="fas fa-check"></i>
                    Accept & Continue
                </button>
            </div>
        </form>
        
        <div class="auth-footer">
            <p class="text-muted">
                <i class="fas fa-shield-alt"></i>
                Your acceptance is logged for security and compliance purposes
            </p>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
.terms-modal {
    max-width: 800px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
}

.terms-content {
    flex: 1;
    overflow: hidden;
    margin: 20px 0;
}

.terms-scroll-area {
    height: 400px;
    overflow-y: auto;
    border: 2px solid #444;
    border-radius: 8px;
    padding: 20px;
    background: #1a1a1a;
}

.terms-text h3 {
    color: #FF7A00;
    margin-bottom: 20px;
    text-align: center;
    border-bottom: 2px solid #FF7A00;
    padding-bottom: 10px;
}

.terms-text h4 {
    color: #FF7A00;
    margin-top: 25px;
    margin-bottom: 10px;
    font-size: 1.1em;
}

.terms-text p {
    color: #FFFFFF;
    line-height: 1.6;
    margin-bottom: 15px;
}

.terms-text ul {
    color: #B0B0B0;
    margin-left: 20px;
    margin-bottom: 15px;
}

.terms-text li {
    margin-bottom: 5px;
    line-height: 1.5;
}

.terms-footer {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #444;
    text-align: center;
}

.terms-footer p {
    color: #B0B0B0;
    margin: 5px 0;
    font-size: 0.9em;
}

.terms-checkbox {
    margin: 20px 0;
    text-align: center;
}

.checkbox-container {
    display: inline-flex;
    align-items: center;
    cursor: pointer;
    font-size: 1.1em;
    color: #FFFFFF;
}

.checkbox-container input[type="checkbox"] {
    display: none;
}

.checkmark {
    width: 20px;
    height: 20px;
    border: 2px solid #FF7A00;
    border-radius: 4px;
    margin-right: 12px;
    position: relative;
    transition: all 0.3s ease;
}

.checkbox-container input[type="checkbox"]:checked + .checkmark {
    background-color: #FF7A00;
}

.checkbox-container input[type="checkbox"]:checked + .checkmark:after {
    content: "";
    position: absolute;
    left: 6px;
    top: 2px;
    width: 6px;
    height: 10px;
    border: solid #FFFFFF;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}

.checkbox-text {
    user-select: none;
}

.btn:disabled {
    background-color: #666;
    border-color: #666;
    cursor: not-allowed;
    opacity: 0.6;
}

.btn:disabled:hover {
    background-color: #666;
    border-color: #666;
}

/* Scrollbar styling */
.terms-scroll-area::-webkit-scrollbar {
    width: 8px;
}

.terms-scroll-area::-webkit-scrollbar-track {
    background: #2a2a2a;
    border-radius: 4px;
}

.terms-scroll-area::-webkit-scrollbar-thumb {
    background: #FF7A00;
    border-radius: 4px;
}

.terms-scroll-area::-webkit-scrollbar-thumb:hover {
    background: #e66a00;
}
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkbox = document.getElementById('accept_terms');
    const submitBtn = document.getElementById('accept-btn');
    
    checkbox.addEventListener('change', function() {
        submitBtn.disabled = !this.checked;
    });
    
    // Prevent form submission if checkbox is not checked
    document.querySelector('.terms-form').addEventListener('submit', function(e) {
        if (!checkbox.checked) {
            e.preventDefault();
            alert('Please accept the Terms & Conditions to continue.');
        }
    });
});
</script>
@endsection