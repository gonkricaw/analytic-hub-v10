<form id="emailSettingsForm">
    @csrf
    <div class="row">
        <!-- SMTP Configuration -->
        <div class="col-md-6">
            <div class="config-item">
                <h5 class="text-white mb-3">
                    <i class="fas fa-server text-primary"></i> SMTP Configuration
                </h5>
                
                <!-- Mail Driver -->
                <div class="mb-3">
                    <label for="mail_driver" class="form-label text-white">Mail Driver</label>
                    <select class="form-select bg-dark text-white border-secondary" 
                            id="mail_driver" name="mail.driver"
                            onchange="toggleSMTPFields(this.value); SystemConfig.updateConfig('mail.driver', this.value, 'Updated mail driver')">
                        @php
                            $currentDriver = $configs['mail.driver']->value ?? 'smtp';
                        @endphp
                        <option value="smtp" {{ $currentDriver === 'smtp' ? 'selected' : '' }}>SMTP</option>
                        <option value="sendmail" {{ $currentDriver === 'sendmail' ? 'selected' : '' }}>Sendmail</option>
                        <option value="mailgun" {{ $currentDriver === 'mailgun' ? 'selected' : '' }}>Mailgun</option>
                        <option value="ses" {{ $currentDriver === 'ses' ? 'selected' : '' }}>Amazon SES</option>
                        <option value="log" {{ $currentDriver === 'log' ? 'selected' : '' }}>Log (Testing)</option>
                    </select>
                    <small class="text-muted">Email delivery method</small>
                </div>

                <div id="smtp-fields" style="{{ $currentDriver === 'smtp' ? 'display: block;' : 'display: none;' }}">
                    <!-- SMTP Host -->
                    <div class="mb-3">
                        <label for="mail_host" class="form-label text-white">SMTP Host</label>
                        <input type="text" class="form-control bg-dark text-white border-secondary" 
                               id="mail_host" name="mail.host" 
                               value="{{ $configs['mail.host']->value ?? 'smtp.gmail.com' }}"
                               placeholder="smtp.gmail.com"
                               onchange="SystemConfig.updateConfig('mail.host', this.value, 'Updated SMTP host')">
                        <small class="text-muted">SMTP server hostname</small>
                    </div>

                    <!-- SMTP Port -->
                    <div class="mb-3">
                        <label for="mail_port" class="form-label text-white">SMTP Port</label>
                        <select class="form-select bg-dark text-white border-secondary" 
                                id="mail_port" name="mail.port"
                                onchange="SystemConfig.updateConfig('mail.port', this.value, 'Updated SMTP port')">
                            @php
                                $currentPort = $configs['mail.port']->value ?? 587;
                            @endphp
                            <option value="25" {{ $currentPort == 25 ? 'selected' : '' }}>25 (Standard)</option>
                            <option value="465" {{ $currentPort == 465 ? 'selected' : '' }}>465 (SSL)</option>
                            <option value="587" {{ $currentPort == 587 ? 'selected' : '' }}>587 (TLS)</option>
                            <option value="2525" {{ $currentPort == 2525 ? 'selected' : '' }}>2525 (Alternative)</option>
                        </select>
                        <small class="text-muted">SMTP server port</small>
                    </div>

                    <!-- SMTP Username -->
                    <div class="mb-3">
                        <label for="mail_username" class="form-label text-white">SMTP Username</label>
                        <input type="text" class="form-control bg-dark text-white border-secondary" 
                               id="mail_username" name="mail.username" 
                               value="{{ $configs['mail.username']->value ?? '' }}"
                               placeholder="your-email@gmail.com"
                               onchange="SystemConfig.updateConfig('mail.username', this.value, 'Updated SMTP username')">
                        <small class="text-muted">SMTP authentication username</small>
                    </div>

                    <!-- SMTP Password -->
                    <div class="mb-3">
                        <label for="mail_password" class="form-label text-white">SMTP Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control bg-dark text-white border-secondary" 
                                   id="mail_password" name="mail.password" 
                                   value="{{ $configs['mail.password']->value ?? '' }}"
                                   placeholder="Enter SMTP password"
                                   onchange="SystemConfig.updateConfig('mail.password', this.value, 'Updated SMTP password')">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('mail_password')">
                                <i class="fas fa-eye" id="mail_password_icon"></i>
                            </button>
                        </div>
                        <small class="text-muted">SMTP authentication password or app password</small>
                    </div>

                    <!-- SMTP Encryption -->
                    <div class="mb-3">
                        <label for="mail_encryption" class="form-label text-white">Encryption</label>
                        <select class="form-select bg-dark text-white border-secondary" 
                                id="mail_encryption" name="mail.encryption"
                                onchange="SystemConfig.updateConfig('mail.encryption', this.value, 'Updated SMTP encryption')">
                            @php
                                $currentEncryption = $configs['mail.encryption']->value ?? 'tls';
                            @endphp
                            <option value="" {{ $currentEncryption === '' ? 'selected' : '' }}>None</option>
                            <option value="tls" {{ $currentEncryption === 'tls' ? 'selected' : '' }}>TLS</option>
                            <option value="ssl" {{ $currentEncryption === 'ssl' ? 'selected' : '' }}>SSL</option>
                        </select>
                        <small class="text-muted">Email encryption method</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Email Settings -->
        <div class="col-md-6">
            <div class="config-item">
                <h5 class="text-white mb-3">
                    <i class="fas fa-envelope text-success"></i> Email Settings
                </h5>
                
                <!-- From Address -->
                <div class="mb-3">
                    <label for="mail_from_address" class="form-label text-white">From Email Address</label>
                    <input type="email" class="form-control bg-dark text-white border-secondary" 
                           id="mail_from_address" name="mail.from.address" 
                           value="{{ $configs['mail.from.address']->value ?? 'noreply@example.com' }}"
                           onchange="SystemConfig.updateConfig('mail.from.address', this.value, 'Updated from email address')">
                    <small class="text-muted">Default sender email address</small>
                </div>

                <!-- From Name -->
                <div class="mb-3">
                    <label for="mail_from_name" class="form-label text-white">From Name</label>
                    <input type="text" class="form-control bg-dark text-white border-secondary" 
                           id="mail_from_name" name="mail.from.name" 
                           value="{{ $configs['mail.from.name']->value ?? 'Analytics Hub' }}"
                           onchange="SystemConfig.updateConfig('mail.from.name', this.value, 'Updated from name')">
                    <small class="text-muted">Default sender name</small>
                </div>

                <!-- Reply To Address -->
                <div class="mb-3">
                    <label for="mail_reply_to" class="form-label text-white">Reply-To Address</label>
                    <input type="email" class="form-control bg-dark text-white border-secondary" 
                           id="mail_reply_to" name="mail.reply_to" 
                           value="{{ $configs['mail.reply_to']->value ?? '' }}"
                           placeholder="support@example.com"
                           onchange="SystemConfig.updateConfig('mail.reply_to', this.value, 'Updated reply-to address')">
                    <small class="text-muted">Email address for replies (optional)</small>
                </div>

                <!-- Email Queue -->
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="mail_queue_enabled" 
                               {{ ($configs['mail.queue.enabled']->value ?? true) ? 'checked' : '' }}
                               onchange="SystemConfig.updateConfig('mail.queue.enabled', this.checked, 'Toggled email queue')">
                        <label class="form-check-label text-white" for="mail_queue_enabled">
                            Enable Email Queue
                        </label>
                        <small class="d-block text-muted">Queue emails for background processing</small>
                    </div>
                </div>

                <!-- Queue Connection -->
                <div class="mb-3">
                    <label for="mail_queue_connection" class="form-label text-white">Queue Connection</label>
                    <select class="form-select bg-dark text-white border-secondary" 
                            id="mail_queue_connection" name="mail.queue.connection"
                            onchange="SystemConfig.updateConfig('mail.queue.connection', this.value, 'Updated queue connection')">
                        @php
                            $currentQueueConnection = $configs['mail.queue.connection']->value ?? 'database';
                        @endphp
                        <option value="sync" {{ $currentQueueConnection === 'sync' ? 'selected' : '' }}>Sync (Immediate)</option>
                        <option value="database" {{ $currentQueueConnection === 'database' ? 'selected' : '' }}>Database</option>
                        <option value="redis" {{ $currentQueueConnection === 'redis' ? 'selected' : '' }}>Redis</option>
                        <option value="sqs" {{ $currentQueueConnection === 'sqs' ? 'selected' : '' }}>Amazon SQS</option>
                    </select>
                    <small class="text-muted">Queue driver for email processing</small>
                </div>

                <!-- Email Timeout -->
                <div class="mb-3">
                    <label for="mail_timeout" class="form-label text-white">Email Timeout (seconds)</label>
                    <input type="number" class="form-control bg-dark text-white border-secondary" 
                           id="mail_timeout" name="mail.timeout" 
                           min="10" max="300" value="{{ $configs['mail.timeout']->value ?? 60 }}"
                           onchange="SystemConfig.updateConfig('mail.timeout', this.value, 'Updated email timeout')">
                    <small class="text-muted">Maximum time to wait for email sending (10-300 seconds)</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <!-- Email Templates -->
        <div class="col-md-6">
            <div class="config-item">
                <h5 class="text-white mb-3">
                    <i class="fas fa-file-alt text-warning"></i> Email Templates
                </h5>
                
                <!-- Welcome Email -->
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="send_welcome_email" 
                               {{ ($configs['mail.templates.welcome_enabled']->value ?? true) ? 'checked' : '' }}
                               onchange="SystemConfig.updateConfig('mail.templates.welcome_enabled', this.checked, 'Toggled welcome email')">
                        <label class="form-check-label text-white" for="send_welcome_email">
                            Send Welcome Email to New Users
                        </label>
                        <small class="d-block text-muted">Automatically send welcome email when user registers</small>
                    </div>
                </div>

                <!-- Password Reset Email -->
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="send_password_reset_email" 
                               {{ ($configs['mail.templates.password_reset_enabled']->value ?? true) ? 'checked' : '' }}
                               onchange="SystemConfig.updateConfig('mail.templates.password_reset_enabled', this.checked, 'Toggled password reset email')">
                        <label class="form-check-label text-white" for="send_password_reset_email">
                            Send Password Reset Email
                        </label>
                        <small class="d-block text-muted">Send email when user requests password reset</small>
                    </div>
                </div>

                <!-- Security Alert Email -->
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="send_security_alert_email" 
                               {{ ($configs['mail.templates.security_alert_enabled']->value ?? true) ? 'checked' : '' }}
                               onchange="SystemConfig.updateConfig('mail.templates.security_alert_enabled', this.checked, 'Toggled security alert email')">
                        <label class="form-check-label text-white" for="send_security_alert_email">
                            Send Security Alert Email
                        </label>
                        <small class="d-block text-muted">Send email for suspicious login activities</small>
                    </div>
                </div>

                <!-- Email Verification -->
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="require_email_verification" 
                               {{ ($configs['mail.verification.required']->value ?? true) ? 'checked' : '' }}
                               onchange="SystemConfig.updateConfig('mail.verification.required', this.checked, 'Toggled email verification requirement')">
                        <label class="form-check-label text-white" for="require_email_verification">
                            Require Email Verification
                        </label>
                        <small class="d-block text-muted">Users must verify email before accessing system</small>
                    </div>
                </div>

                <!-- Email Template Language -->
                <div class="mb-3">
                    <label for="email_template_language" class="form-label text-white">Email Template Language</label>
                    <select class="form-select bg-dark text-white border-secondary" 
                            id="email_template_language" name="mail.templates.language"
                            onchange="SystemConfig.updateConfig('mail.templates.language', this.value, 'Updated email template language')">
                        @php
                            $currentEmailLang = $configs['mail.templates.language']->value ?? 'en';
                            $languages = [
                                'en' => 'English',
                                'es' => 'Español',
                                'fr' => 'Français',
                                'de' => 'Deutsch',
                                'it' => 'Italiano',
                                'pt' => 'Português'
                            ];
                        @endphp
                        @foreach($languages as $code => $name)
                            <option value="{{ $code }}" {{ $currentEmailLang === $code ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Default language for email templates</small>
                </div>
            </div>
        </div>

        <!-- Email Notifications -->
        <div class="col-md-6">
            <div class="config-item">
                <h5 class="text-white mb-3">
                    <i class="fas fa-bell text-info"></i> Email Notifications
                </h5>
                
                <!-- Admin Notification Email -->
                <div class="mb-3">
                    <label for="admin_notification_email" class="form-label text-white">Admin Notification Email</label>
                    <input type="email" class="form-control bg-dark text-white border-secondary" 
                           id="admin_notification_email" name="mail.notifications.admin_email" 
                           value="{{ $configs['mail.notifications.admin_email']->value ?? 'admin@example.com' }}"
                           onchange="SystemConfig.updateConfig('mail.notifications.admin_email', this.value, 'Updated admin notification email')">
                    <small class="text-muted">Email address to receive admin notifications</small>
                </div>

                <!-- System Error Notifications -->
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="notify_system_errors" 
                               {{ ($configs['mail.notifications.system_errors']->value ?? true) ? 'checked' : '' }}
                               onchange="SystemConfig.updateConfig('mail.notifications.system_errors', this.checked, 'Toggled system error notifications')">
                        <label class="form-check-label text-white" for="notify_system_errors">
                            System Error Notifications
                        </label>
                        <small class="d-block text-muted">Send email when system errors occur</small>
                    </div>
                </div>

                <!-- User Registration Notifications -->
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="notify_user_registration" 
                               {{ ($configs['mail.notifications.user_registration']->value ?? true) ? 'checked' : '' }}
                               onchange="SystemConfig.updateConfig('mail.notifications.user_registration', this.checked, 'Toggled user registration notifications')">
                        <label class="form-check-label text-white" for="notify_user_registration">
                            User Registration Notifications
                        </label>
                        <small class="d-block text-muted">Notify admin when new users register</small>
                    </div>
                </div>

                <!-- Security Incident Notifications -->
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="notify_security_incidents" 
                               {{ ($configs['mail.notifications.security_incidents']->value ?? true) ? 'checked' : '' }}
                               onchange="SystemConfig.updateConfig('mail.notifications.security_incidents', this.checked, 'Toggled security incident notifications')">
                        <label class="form-check-label text-white" for="notify_security_incidents">
                            Security Incident Notifications
                        </label>
                        <small class="d-block text-muted">Notify admin of security incidents</small>
                    </div>
                </div>

                <!-- Daily Summary Email -->
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="send_daily_summary" 
                               {{ ($configs['mail.notifications.daily_summary']->value ?? false) ? 'checked' : '' }}
                               onchange="SystemConfig.updateConfig('mail.notifications.daily_summary', this.checked, 'Toggled daily summary email')">
                        <label class="form-check-label text-white" for="send_daily_summary">
                            Daily Summary Email
                        </label>
                        <small class="d-block text-muted">Send daily activity summary to admin</small>
                    </div>
                </div>

                <!-- Email Rate Limiting -->
                <div class="mb-3">
                    <label for="email_rate_limit" class="form-label text-white">Email Rate Limit (per hour)</label>
                    <input type="number" class="form-control bg-dark text-white border-secondary" 
                           id="email_rate_limit" name="mail.rate_limit" 
                           min="1" max="1000" value="{{ $configs['mail.rate_limit']->value ?? 100 }}"
                           onchange="SystemConfig.updateConfig('mail.rate_limit', this.value, 'Updated email rate limit')">
                    <small class="text-muted">Maximum emails to send per hour (1-1000)</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Email Testing -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="config-item">
                <h5 class="text-white mb-3">
                    <i class="fas fa-vial text-danger"></i> Email Testing
                </h5>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="test_email_address" class="form-label text-white">Test Email Address</label>
                            <input type="email" class="form-control bg-dark text-white border-secondary" 
                                   id="test_email_address" name="test_email" 
                                   placeholder="test@example.com">
                            <small class="text-muted">Email address to send test email</small>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="test_email_type" class="form-label text-white">Test Email Type</label>
                            <select class="form-select bg-dark text-white border-secondary" id="test_email_type">
                                <option value="basic">Basic Test Email</option>
                                <option value="welcome">Welcome Email Template</option>
                                <option value="password_reset">Password Reset Template</option>
                                <option value="security_alert">Security Alert Template</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-3">
                        <button type="button" class="btn btn-outline-primary w-100 mb-2" onclick="sendTestEmail()">
                            <i class="fas fa-paper-plane"></i> Send Test Email
                        </button>
                    </div>
                    
                    <div class="col-md-3">
                        <button type="button" class="btn btn-outline-success w-100 mb-2" onclick="testSMTPConnection()">
                            <i class="fas fa-plug"></i> Test SMTP Connection
                        </button>
                    </div>
                    
                    <div class="col-md-3">
                        <button type="button" class="btn btn-outline-warning w-100 mb-2" onclick="viewEmailQueue()">
                            <i class="fas fa-list"></i> View Email Queue
                        </button>
                    </div>
                    
                    <div class="col-md-3">
                        <button type="button" class="btn btn-outline-info w-100 mb-2" onclick="clearEmailQueue()">
                            <i class="fas fa-trash"></i> Clear Email Queue
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
// Toggle SMTP fields based on mail driver
function toggleSMTPFields(driver) {
    const smtpFields = document.getElementById('smtp-fields');
    smtpFields.style.display = driver === 'smtp' ? 'block' : 'none';
}

// Toggle password visibility
function togglePasswordVisibility(inputId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(inputId + '_icon');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Send test email
function sendTestEmail() {
    const emailAddress = document.getElementById('test_email_address').value;
    const emailType = document.getElementById('test_email_type').value;
    
    if (!emailAddress) {
        toastr.error('Please enter a test email address');
        return;
    }
    
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    button.disabled = true;
    
    fetch('/admin/system-config/email/send-test', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            email: emailAddress,
            type: emailType
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            toastr.success(data.message);
        } else {
            toastr.error(data.message || 'Failed to send test email');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        toastr.error('An error occurred while sending test email');
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// Test SMTP connection
function testSMTPConnection() {
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
    button.disabled = true;
    
    fetch('/admin/system-config/email/test-connection', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            toastr.success('SMTP connection test successful');
        } else {
            toastr.error(data.message || 'SMTP connection test failed');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        toastr.error('An error occurred while testing SMTP connection');
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// View email queue
function viewEmailQueue() {
    fetch('/admin/system-config/email/queue-status')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let message = `Email Queue Status:\n`;
                message += `Pending: ${data.queue.pending}\n`;
                message += `Failed: ${data.queue.failed}\n`;
                message += `Processed: ${data.queue.processed}`;
                
                alert(message); // You could replace this with a modal
            } else {
                toastr.error('Failed to retrieve queue status');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            toastr.error('An error occurred while retrieving queue status');
        });
}

// Clear email queue
function clearEmailQueue() {
    if (confirm('Are you sure you want to clear the email queue? This will remove all pending emails.')) {
        fetch('/admin/system-config/email/clear-queue', {
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
                toastr.error(data.message || 'Failed to clear email queue');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            toastr.error('An error occurred while clearing email queue');
        });
    }
}

// Initialize email settings
document.addEventListener('DOMContentLoaded', function() {
    // Set initial SMTP fields visibility
    const mailDriver = document.getElementById('mail_driver').value;
    toggleSMTPFields(mailDriver);
    
    // Auto-update port based on encryption
    const encryptionSelect = document.getElementById('mail_encryption');
    const portSelect = document.getElementById('mail_port');
    
    encryptionSelect.addEventListener('change', function() {
        if (this.value === 'ssl') {
            portSelect.value = '465';
        } else if (this.value === 'tls') {
            portSelect.value = '587';
        }
    });
});
</script>