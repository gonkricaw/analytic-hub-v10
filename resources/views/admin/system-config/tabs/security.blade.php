<form id="securitySettingsForm">
    @csrf
    <div class="row">
        <!-- Password Policies -->
        <div class="col-md-6">
            <div class="config-item">
                <h5 class="text-white mb-3">
                    <i class="fas fa-key text-primary"></i> Password Policies
                </h5>
                
                <!-- Minimum Password Length -->
                <div class="mb-3">
                    <label for="password_min_length" class="form-label text-white">Minimum Password Length</label>
                    <input type="number" class="form-control bg-dark text-white border-secondary" 
                           id="password_min_length" name="security.password.min_length" 
                           min="6" max="32" value="{{ $configs['security.password.min_length']->value ?? 8 }}"
                           onchange="SystemConfig.updateConfig('security.password.min_length', this.value, 'Updated minimum password length')">
                    <small class="text-muted">Minimum number of characters required (6-32)</small>
                </div>

                <!-- Password Complexity Requirements -->
                <div class="mb-3">
                    <label class="form-label text-white">Password Complexity Requirements</label>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="require_uppercase" 
                                       {{ ($configs['security.password.require_uppercase']->value ?? true) ? 'checked' : '' }}
                                       onchange="SystemConfig.updateConfig('security.password.require_uppercase', this.checked, 'Toggled uppercase requirement')">
                                <label class="form-check-label text-white" for="require_uppercase">
                                    Require Uppercase Letters
                                </label>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="require_lowercase" 
                                       {{ ($configs['security.password.require_lowercase']->value ?? true) ? 'checked' : '' }}
                                       onchange="SystemConfig.updateConfig('security.password.require_lowercase', this.checked, 'Toggled lowercase requirement')">
                                <label class="form-check-label text-white" for="require_lowercase">
                                    Require Lowercase Letters
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="require_numbers" 
                                       {{ ($configs['security.password.require_numbers']->value ?? true) ? 'checked' : '' }}
                                       onchange="SystemConfig.updateConfig('security.password.require_numbers', this.checked, 'Toggled numbers requirement')">
                                <label class="form-check-label text-white" for="require_numbers">
                                    Require Numbers
                                </label>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="require_symbols" 
                                       {{ ($configs['security.password.require_symbols']->value ?? true) ? 'checked' : '' }}
                                       onchange="SystemConfig.updateConfig('security.password.require_symbols', this.checked, 'Toggled symbols requirement')">
                                <label class="form-check-label text-white" for="require_symbols">
                                    Require Special Characters
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Password Expiry -->
                <div class="mb-3">
                    <label for="password_expiry_days" class="form-label text-white">Password Expiry (Days)</label>
                    <input type="number" class="form-control bg-dark text-white border-secondary" 
                           id="password_expiry_days" name="security.password.expiry_days" 
                           min="0" max="365" value="{{ $configs['security.password.expiry_days']->value ?? 90 }}"
                           onchange="SystemConfig.updateConfig('security.password.expiry_days', this.value, 'Updated password expiry period')">
                    <small class="text-muted">Days before password expires (0 = never expires)</small>
                </div>

                <!-- Password History -->
                <div class="mb-3">
                    <label for="password_history_count" class="form-label text-white">Password History Count</label>
                    <input type="number" class="form-control bg-dark text-white border-secondary" 
                           id="password_history_count" name="security.password.history_count" 
                           min="0" max="24" value="{{ $configs['security.password.history_count']->value ?? 5 }}"
                           onchange="SystemConfig.updateConfig('security.password.history_count', this.value, 'Updated password history count')">
                    <small class="text-muted">Number of previous passwords to remember (0 = no history)</small>
                </div>
            </div>
        </div>

        <!-- Login Security -->
        <div class="col-md-6">
            <div class="config-item">
                <h5 class="text-white mb-3">
                    <i class="fas fa-shield-alt text-success"></i> Login Security
                </h5>
                
                <!-- Failed Login Threshold -->
                <div class="mb-3">
                    <label for="failed_login_threshold" class="form-label text-white">Failed Login Threshold</label>
                    <input type="number" class="form-control bg-dark text-white border-secondary" 
                           id="failed_login_threshold" name="security.login.failed_threshold" 
                           min="3" max="20" value="{{ $configs['security.login.failed_threshold']->value ?? 5 }}"
                           onchange="SystemConfig.updateConfig('security.login.failed_threshold', this.value, 'Updated failed login threshold')">
                    <small class="text-muted">Number of failed attempts before account lockout (3-20)</small>
                </div>

                <!-- Account Lockout Duration -->
                <div class="mb-3">
                    <label for="lockout_duration" class="form-label text-white">Account Lockout Duration (Minutes)</label>
                    <input type="number" class="form-control bg-dark text-white border-secondary" 
                           id="lockout_duration" name="security.login.lockout_duration" 
                           min="5" max="1440" value="{{ $configs['security.login.lockout_duration']->value ?? 30 }}"
                           onchange="SystemConfig.updateConfig('security.login.lockout_duration', this.value, 'Updated lockout duration')">
                    <small class="text-muted">Minutes to lock account after failed attempts (5-1440)</small>
                </div>

                <!-- Rate Limiting -->
                <div class="mb-3">
                    <label for="rate_limit_attempts" class="form-label text-white">Rate Limit (Attempts per Minute)</label>
                    <input type="number" class="form-control bg-dark text-white border-secondary" 
                           id="rate_limit_attempts" name="security.login.rate_limit_attempts" 
                           min="1" max="60" value="{{ $configs['security.login.rate_limit_attempts']->value ?? 5 }}"
                           onchange="SystemConfig.updateConfig('security.login.rate_limit_attempts', this.value, 'Updated rate limit attempts')">
                    <small class="text-muted">Maximum login attempts per minute per IP (1-60)</small>
                </div>

                <!-- Two-Factor Authentication -->
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="require_2fa" 
                               {{ ($configs['security.2fa.required']->value ?? false) ? 'checked' : '' }}
                               onchange="SystemConfig.updateConfig('security.2fa.required', this.checked, 'Toggled 2FA requirement')">
                        <label class="form-check-label text-white" for="require_2fa">
                            Require Two-Factor Authentication
                        </label>
                        <small class="d-block text-muted">Force all users to enable 2FA</small>
                    </div>
                </div>

                <!-- Remember Me Duration -->
                <div class="mb-3">
                    <label for="remember_me_duration" class="form-label text-white">Remember Me Duration (Days)</label>
                    <input type="number" class="form-control bg-dark text-white border-secondary" 
                           id="remember_me_duration" name="security.login.remember_me_duration" 
                           min="1" max="365" value="{{ $configs['security.login.remember_me_duration']->value ?? 30 }}"
                           onchange="SystemConfig.updateConfig('security.login.remember_me_duration', this.value, 'Updated remember me duration')">
                    <small class="text-muted">Days to keep user logged in when "Remember Me" is checked (1-365)</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <!-- Session Security -->
        <div class="col-md-6">
            <div class="config-item">
                <h5 class="text-white mb-3">
                    <i class="fas fa-clock text-warning"></i> Session Security
                </h5>
                
                <!-- Session Timeout -->
                <div class="mb-3">
                    <label for="session_timeout_minutes" class="form-label text-white">Session Timeout (Minutes)</label>
                    <input type="number" class="form-control bg-dark text-white border-secondary" 
                           id="session_timeout_minutes" name="security.session.timeout_minutes" 
                           min="5" max="480" value="{{ $configs['security.session.timeout_minutes']->value ?? 120 }}"
                           onchange="SystemConfig.updateConfig('security.session.timeout_minutes', this.value, 'Updated session timeout')">
                    <small class="text-muted">Minutes of inactivity before session expires (5-480)</small>
                </div>

                <!-- Concurrent Sessions -->
                <div class="mb-3">
                    <label for="max_concurrent_sessions" class="form-label text-white">Max Concurrent Sessions</label>
                    <input type="number" class="form-control bg-dark text-white border-secondary" 
                           id="max_concurrent_sessions" name="security.session.max_concurrent" 
                           min="1" max="10" value="{{ $configs['security.session.max_concurrent']->value ?? 3 }}"
                           onchange="SystemConfig.updateConfig('security.session.max_concurrent', this.value, 'Updated max concurrent sessions')">
                    <small class="text-muted">Maximum number of concurrent sessions per user (1-10)</small>
                </div>

                <!-- Session Regeneration -->
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="regenerate_session_on_login" 
                               {{ ($configs['security.session.regenerate_on_login']->value ?? true) ? 'checked' : '' }}
                               onchange="SystemConfig.updateConfig('security.session.regenerate_on_login', this.checked, 'Toggled session regeneration on login')">
                        <label class="form-check-label text-white" for="regenerate_session_on_login">
                            Regenerate Session ID on Login
                        </label>
                        <small class="d-block text-muted">Enhance security by regenerating session ID after login</small>
                    </div>
                </div>

                <!-- Secure Cookies -->
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="secure_cookies" 
                               {{ ($configs['security.session.secure_cookies']->value ?? true) ? 'checked' : '' }}
                               onchange="SystemConfig.updateConfig('security.session.secure_cookies', this.checked, 'Toggled secure cookies')">
                        <label class="form-check-label text-white" for="secure_cookies">
                            Secure Cookies (HTTPS Only)
                        </label>
                        <small class="d-block text-muted">Only send cookies over HTTPS connections</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Security Monitoring -->
        <div class="col-md-6">
            <div class="config-item">
                <h5 class="text-white mb-3">
                    <i class="fas fa-eye text-info"></i> Security Monitoring
                </h5>
                
                <!-- Log Failed Logins -->
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="log_failed_logins" 
                               {{ ($configs['security.monitoring.log_failed_logins']->value ?? true) ? 'checked' : '' }}
                               onchange="SystemConfig.updateConfig('security.monitoring.log_failed_logins', this.checked, 'Toggled failed login logging')">
                        <label class="form-check-label text-white" for="log_failed_logins">
                            Log Failed Login Attempts
                        </label>
                        <small class="d-block text-muted">Record all failed login attempts for security analysis</small>
                    </div>
                </div>

                <!-- Log Successful Logins -->
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="log_successful_logins" 
                               {{ ($configs['security.monitoring.log_successful_logins']->value ?? true) ? 'checked' : '' }}
                               onchange="SystemConfig.updateConfig('security.monitoring.log_successful_logins', this.checked, 'Toggled successful login logging')">
                        <label class="form-check-label text-white" for="log_successful_logins">
                            Log Successful Logins
                        </label>
                        <small class="d-block text-muted">Record all successful login attempts for audit trail</small>
                    </div>
                </div>

                <!-- Suspicious Activity Detection -->
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="detect_suspicious_activity" 
                               {{ ($configs['security.monitoring.detect_suspicious']->value ?? true) ? 'checked' : '' }}
                               onchange="SystemConfig.updateConfig('security.monitoring.detect_suspicious', this.checked, 'Toggled suspicious activity detection')">
                        <label class="form-check-label text-white" for="detect_suspicious_activity">
                            Detect Suspicious Activity
                        </label>
                        <small class="d-block text-muted">Monitor for unusual login patterns and behaviors</small>
                    </div>
                </div>

                <!-- Email Security Alerts -->
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="email_security_alerts" 
                               {{ ($configs['security.monitoring.email_alerts']->value ?? true) ? 'checked' : '' }}
                               onchange="SystemConfig.updateConfig('security.monitoring.email_alerts', this.checked, 'Toggled email security alerts')">
                        <label class="form-check-label text-white" for="email_security_alerts">
                            Email Security Alerts
                        </label>
                        <small class="d-block text-muted">Send email notifications for security events</small>
                    </div>
                </div>

                <!-- Alert Threshold -->
                <div class="mb-3">
                    <label for="alert_threshold" class="form-label text-white">Security Alert Threshold</label>
                    <input type="number" class="form-control bg-dark text-white border-secondary" 
                           id="alert_threshold" name="security.monitoring.alert_threshold" 
                           min="1" max="100" value="{{ $configs['security.monitoring.alert_threshold']->value ?? 10 }}"
                           onchange="SystemConfig.updateConfig('security.monitoring.alert_threshold', this.value, 'Updated security alert threshold')">
                    <small class="text-muted">Number of failed attempts before sending security alert (1-100)</small>
                </div>
            </div>
        </div>
    </div>

    <!-- IP Restrictions -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="config-item">
                <h5 class="text-white mb-3">
                    <i class="fas fa-globe text-danger"></i> IP Address Restrictions
                </h5>
                
                <div class="row">
                    <div class="col-md-6">
                        <!-- Whitelist IPs -->
                        <div class="mb-3">
                            <label for="whitelist_ips" class="form-label text-white">Whitelisted IP Addresses</label>
                            <textarea class="form-control bg-dark text-white border-secondary font-monospace" 
                                      id="whitelist_ips" name="security.ip.whitelist" rows="4"
                                      placeholder="192.168.1.0/24&#10;10.0.0.1&#10;203.0.113.0/24"
                                      onchange="SystemConfig.updateConfig('security.ip.whitelist', this.value, 'Updated IP whitelist')">{{ $configs['security.ip.whitelist']->value ?? '' }}</textarea>
                            <small class="text-muted">One IP address or CIDR block per line. Leave empty to allow all IPs.</small>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <!-- Blacklist IPs -->
                        <div class="mb-3">
                            <label for="blacklist_ips" class="form-label text-white">Blacklisted IP Addresses</label>
                            <textarea class="form-control bg-dark text-white border-secondary font-monospace" 
                                      id="blacklist_ips" name="security.ip.blacklist" rows="4"
                                      placeholder="192.168.100.0/24&#10;203.0.113.50&#10;10.0.0.100"
                                      onchange="SystemConfig.updateConfig('security.ip.blacklist', this.value, 'Updated IP blacklist')">{{ $configs['security.ip.blacklist']->value ?? '' }}</textarea>
                            <small class="text-muted">One IP address or CIDR block per line. These IPs will be blocked.</small>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="enable_ip_restrictions" 
                                   {{ ($configs['security.ip.enabled']->value ?? false) ? 'checked' : '' }}
                                   onchange="SystemConfig.updateConfig('security.ip.enabled', this.checked, 'Toggled IP restrictions')">
                            <label class="form-check-label text-white" for="enable_ip_restrictions">
                                Enable IP Restrictions
                            </label>
                            <small class="d-block text-muted">Activate IP-based access control</small>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="log_ip_blocks" 
                                   {{ ($configs['security.ip.log_blocks']->value ?? true) ? 'checked' : '' }}
                                   onchange="SystemConfig.updateConfig('security.ip.log_blocks', this.checked, 'Toggled IP block logging')">
                            <label class="form-check-label text-white" for="log_ip_blocks">
                                Log Blocked IPs
                            </label>
                            <small class="d-block text-muted">Record all blocked IP access attempts</small>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="auto_block_suspicious" 
                                   {{ ($configs['security.ip.auto_block_suspicious']->value ?? false) ? 'checked' : '' }}
                                   onchange="SystemConfig.updateConfig('security.ip.auto_block_suspicious', this.checked, 'Toggled auto-block suspicious IPs')">
                            <label class="form-check-label text-white" for="auto_block_suspicious">
                                Auto-block Suspicious IPs
                            </label>
                            <small class="d-block text-muted">Automatically add suspicious IPs to blacklist</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Security Actions -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="config-item">
                <h5 class="text-white mb-3">
                    <i class="fas fa-tools text-warning"></i> Security Actions
                </h5>
                
                <div class="row">
                    <div class="col-md-3">
                        <button type="button" class="btn btn-outline-warning w-100 mb-2" onclick="clearFailedLogins()">
                            <i class="fas fa-eraser"></i> Clear Failed Login Records
                        </button>
                    </div>
                    
                    <div class="col-md-3">
                        <button type="button" class="btn btn-outline-danger w-100 mb-2" onclick="forceLogoutAllUsers()">
                            <i class="fas fa-sign-out-alt"></i> Force Logout All Users
                        </button>
                    </div>
                    
                    <div class="col-md-3">
                        <button type="button" class="btn btn-outline-info w-100 mb-2" onclick="generateSecurityReport()">
                            <i class="fas fa-file-alt"></i> Generate Security Report
                        </button>
                    </div>
                    
                    <div class="col-md-3">
                        <button type="button" class="btn btn-outline-success w-100 mb-2" onclick="testSecuritySettings()">
                            <i class="fas fa-check-circle"></i> Test Security Settings
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
// Security action functions
function clearFailedLogins() {
    if (confirm('Are you sure you want to clear all failed login records? This action cannot be undone.')) {
        fetch('/admin/system-config/security/clear-failed-logins', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                toastr.success(data.message);
            } else {
                toastr.error(data.message || 'Failed to clear failed login records');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            toastr.error('An error occurred while clearing failed login records');
        });
    }
}

function forceLogoutAllUsers() {
    if (confirm('Are you sure you want to force logout all users? This will terminate all active sessions except yours.')) {
        fetch('/admin/system-config/security/force-logout-all', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                toastr.success(data.message);
            } else {
                toastr.error(data.message || 'Failed to force logout users');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            toastr.error('An error occurred while forcing user logout');
        });
    }
}

function generateSecurityReport() {
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
    button.disabled = true;
    
    fetch('/admin/system-config/security/generate-report', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => {
        if (response.headers.get('content-type').includes('application/pdf')) {
            return response.blob().then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'security-report-' + new Date().toISOString().split('T')[0] + '.pdf';
                a.click();
                window.URL.revokeObjectURL(url);
                toastr.success('Security report generated and downloaded');
            });
        } else {
            return response.json().then(data => {
                if (data.success) {
                    toastr.success(data.message);
                } else {
                    toastr.error(data.message || 'Failed to generate security report');
                }
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        toastr.error('An error occurred while generating security report');
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

function testSecuritySettings() {
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
    button.disabled = true;
    
    fetch('/admin/system-config/security/test-settings', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            toastr.success('Security settings test completed successfully');
            
            // Display test results
            let resultsHtml = '<div class="mt-3"><h6>Test Results:</h6><ul>';
            data.results.forEach(result => {
                const icon = result.passed ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>';
                resultsHtml += `<li>${icon} ${result.test}: ${result.message}</li>`;
            });
            resultsHtml += '</ul></div>';
            
            // You could display this in a modal or append to the page
            console.log('Security test results:', data.results);
        } else {
            toastr.error(data.message || 'Security settings test failed');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        toastr.error('An error occurred while testing security settings');
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// Password strength indicator
function updatePasswordStrengthIndicator() {
    const requirements = {
        minLength: document.getElementById('password_min_length').value,
        requireUppercase: document.getElementById('require_uppercase').checked,
        requireLowercase: document.getElementById('require_lowercase').checked,
        requireNumbers: document.getElementById('require_numbers').checked,
        requireSymbols: document.getElementById('require_symbols').checked
    };
    
    // Update password strength display (you could add a visual indicator here)
    console.log('Password requirements updated:', requirements);
}

// Add event listeners for password policy changes
document.addEventListener('DOMContentLoaded', function() {
    const passwordInputs = [
        'password_min_length',
        'require_uppercase',
        'require_lowercase', 
        'require_numbers',
        'require_symbols'
    ];
    
    passwordInputs.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('change', updatePasswordStrengthIndicator);
        }
    });
    
    // Initialize password strength indicator
    updatePasswordStrengthIndicator();
});
</script>