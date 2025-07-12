@extends('layouts.app')

@section('title', 'Create Email Template')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Create Email Template</h1>
            <p class="mb-0 text-muted">Create a new email template for system communications</p>
        </div>
        <div>
            <a href="{{ route('admin.email-templates.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Templates
            </a>
        </div>
    </div>

    <form action="{{ route('admin.email-templates.store') }}" method="POST" id="templateForm">
        @csrf
        
        <div class="row">
            <!-- Main Form -->
            <div class="col-lg-8">
                <!-- Basic Information -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Basic Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Template Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name') }}" required>
                                    <div class="form-text">Unique identifier for the template (lowercase, underscores allowed)</div>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="display_name" class="form-label">Display Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('display_name') is-invalid @enderror" 
                                           id="display_name" name="display_name" value="{{ old('display_name') }}" required>
                                    <div class="form-text">Human-readable name for the template</div>
                                    @error('display_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            <div class="form-text">Brief description of the template's purpose</div>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                                    <select class="form-select @error('category') is-invalid @enderror" 
                                            id="category" name="category" required>
                                        <option value="">Select Category</option>
                                        @foreach($categories as $value => $label)
                                            <option value="{{ $value }}" {{ old('category') == $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('category')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                                    <select class="form-select @error('type') is-invalid @enderror" 
                                            id="type" name="type" required>
                                        <option value="">Select Type</option>
                                        @foreach($types as $value => $label)
                                            <option value="{{ $value }}" {{ old('type') == $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="event_trigger" class="form-label">Event Trigger</label>
                                    <input type="text" class="form-control @error('event_trigger') is-invalid @enderror" 
                                           id="event_trigger" name="event_trigger" value="{{ old('event_trigger') }}">
                                    <div class="form-text">System event that triggers this template</div>
                                    @error('event_trigger')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Email Content -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Email Content</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject Line <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('subject') is-invalid @enderror" 
                                   id="subject" name="subject" value="{{ old('subject') }}" required>
                            <div class="form-text">Email subject line (variables supported: {{user_name}}, {{company_name}}, etc.)</div>
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="body_html" class="form-label">HTML Content <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('body_html') is-invalid @enderror" 
                                      id="body_html" name="body_html" rows="15" required>{{ old('body_html') }}</textarea>
                            <div class="form-text">HTML version of the email content</div>
                            @error('body_html')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="body_text" class="form-label">Plain Text Content</label>
                            <textarea class="form-control @error('body_text') is-invalid @enderror" 
                                      id="body_text" name="body_text" rows="10">{{ old('body_text') }}</textarea>
                            <div class="form-text">Plain text version of the email content (optional but recommended)</div>
                            @error('body_text')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Email Settings -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Email Settings</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="from_email" class="form-label">From Email</label>
                                    <input type="email" class="form-control @error('from_email') is-invalid @enderror" 
                                           id="from_email" name="from_email" value="{{ old('from_email') }}">
                                    <div class="form-text">Override default from email (leave blank to use system default)</div>
                                    @error('from_email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="from_name" class="form-label">From Name</label>
                                    <input type="text" class="form-control @error('from_name') is-invalid @enderror" 
                                           id="from_name" name="from_name" value="{{ old('from_name') }}">
                                    <div class="form-text">Override default from name (leave blank to use system default)</div>
                                    @error('from_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="reply_to" class="form-label">Reply To</label>
                                    <input type="email" class="form-control @error('reply_to') is-invalid @enderror" 
                                           id="reply_to" name="reply_to" value="{{ old('reply_to') }}">
                                    <div class="form-text">Email address for replies</div>
                                    @error('reply_to')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="priority" class="form-label">Priority <span class="text-danger">*</span></label>
                                    <select class="form-select @error('priority') is-invalid @enderror" 
                                            id="priority" name="priority" required>
                                        @foreach($priorities as $value => $label)
                                            <option value="{{ $value }}" {{ old('priority', 3) == $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('priority')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="language" class="form-label">Language <span class="text-danger">*</span></label>
                                    <select class="form-select @error('language') is-invalid @enderror" 
                                            id="language" name="language" required>
                                        <option value="id" {{ old('language', 'id') == 'id' ? 'selected' : '' }}>Indonesian</option>
                                        <option value="en" {{ old('language') == 'en' ? 'selected' : '' }}>English</option>
                                    </select>
                                    @error('language')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="cc_emails" class="form-label">CC Emails</label>
                                    <input type="text" class="form-control @error('cc_emails') is-invalid @enderror" 
                                           id="cc_emails" name="cc_emails_input" value="{{ old('cc_emails_input') }}">
                                    <div class="form-text">Comma-separated email addresses for CC</div>
                                    @error('cc_emails')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="bcc_emails" class="form-label">BCC Emails</label>
                                    <input type="text" class="form-control @error('bcc_emails') is-invalid @enderror" 
                                           id="bcc_emails" name="bcc_emails_input" value="{{ old('bcc_emails_input') }}">
                                    <div class="form-text">Comma-separated email addresses for BCC</div>
                                    @error('bcc_emails')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" 
                                   {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Active Template
                            </label>
                            <div class="form-text">Inactive templates cannot be used for sending emails</div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <button type="button" class="btn btn-info" id="previewBtn">
                                    <i class="fas fa-eye me-2"></i>Preview Template
                                </button>
                                <button type="button" class="btn btn-success" id="testBtn">
                                    <i class="fas fa-paper-plane me-2"></i>Send Test Email
                                </button>
                            </div>
                            <div>
                                <a href="{{ route('admin.email-templates.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Create Template
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Available Variables -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Available Variables</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-text mb-3">
                            Click on any variable to insert it into your template content.
                        </div>
                        
                        @foreach($availableVariables as $category => $variables)
                            <div class="mb-3">
                                <h6 class="text-capitalize fw-bold">{{ str_replace('_', ' ', $category) }}</h6>
                                @foreach($variables as $variable => $description)
                                    <div class="variable-item mb-2 p-2 border rounded cursor-pointer" 
                                         data-variable="{{ $variable }}" title="{{ $description }}">
                                        <code class="text-primary">{{{{ $variable }}}}</code>
                                        <br><small class="text-muted">{{ $description }}</small>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Template Tips -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Template Tips</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="fas fa-lightbulb text-warning me-2"></i>
                                Use variables in double curly braces: <code>{{user_name}}</code>
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-lightbulb text-warning me-2"></i>
                                Always provide both HTML and plain text versions
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-lightbulb text-warning me-2"></i>
                                Test your template before activating it
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-lightbulb text-warning me-2"></i>
                                Keep subject lines under 50 characters for better deliverability
                            </li>
                            <li class="mb-0">
                                <i class="fas fa-lightbulb text-warning me-2"></i>
                                Use descriptive template names for easy identification
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Template Examples -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Quick Templates</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <button type="button" class="btn btn-outline-primary btn-sm w-100 mb-2 load-template" 
                                    data-template="welcome">
                                <i class="fas fa-user-plus me-2"></i>Welcome Email
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm w-100 mb-2 load-template" 
                                    data-template="notification">
                                <i class="fas fa-bell me-2"></i>Notification Email
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm w-100 mb-2 load-template" 
                                    data-template="announcement">
                                <i class="fas fa-bullhorn me-2"></i>Announcement Email
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">Template Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="previewContent">
                    <!-- Preview content will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Test Email Modal -->
<div class="modal fade" id="testEmailModal" tabindex="-1" aria-labelledby="testEmailModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="testEmailModalLabel">Send Test Email</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="testEmailForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="testEmail" class="form-label">Test Email Address</label>
                        <input type="email" class="form-control" id="testEmail" name="test_email" 
                               value="{{ Auth::user()->email }}" required>
                        <div class="form-text">Enter the email address where you want to send the test email.</div>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        The test email will be sent with sample data and will be prefixed with "[TEST]" in the subject line.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Send Test Email
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
<style>
.variable-item {
    cursor: pointer;
    transition: all 0.2s;
}
.variable-item:hover {
    background-color: #f8f9fc;
    border-color: #4e73df !important;
}
.cursor-pointer {
    cursor: pointer;
}
.template-preview {
    border: 1px solid #e3e6f0;
    border-radius: 0.35rem;
    padding: 1rem;
    background-color: #f8f9fc;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize Summernote for HTML content
    $('#body_html').summernote({
        height: 300,
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'underline', 'clear']],
            ['fontname', ['fontname']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link', 'picture', 'video']],
            ['view', ['fullscreen', 'codeview', 'help']]
        ],
        callbacks: {
            onInit: function() {
                // Load default template if needed
            }
        }
    });

    // Auto-generate template name from display name
    $('#display_name').on('input', function() {
        let displayName = $(this).val();
        let templateName = displayName.toLowerCase()
            .replace(/[^a-z0-9\s]/g, '')
            .replace(/\s+/g, '_')
            .substring(0, 100);
        $('#name').val(templateName);
    });

    // Insert variable into content
    $('.variable-item').click(function() {
        let variable = $(this).data('variable');
        let variableText = '{{' + variable + '}}';
        
        // Insert into currently focused field
        let activeElement = document.activeElement;
        
        if (activeElement.id === 'subject') {
            let currentValue = $('#subject').val();
            let cursorPos = activeElement.selectionStart;
            let newValue = currentValue.substring(0, cursorPos) + variableText + currentValue.substring(cursorPos);
            $('#subject').val(newValue);
        } else if (activeElement.id === 'body_text') {
            let currentValue = $('#body_text').val();
            let cursorPos = activeElement.selectionStart;
            let newValue = currentValue.substring(0, cursorPos) + variableText + currentValue.substring(cursorPos);
            $('#body_text').val(newValue);
        } else {
            // Insert into Summernote
            $('#body_html').summernote('insertText', variableText);
        }
        
        // Show feedback
        $(this).addClass('bg-success text-white');
        setTimeout(() => {
            $(this).removeClass('bg-success text-white');
        }, 500);
    });

    // Process CC and BCC emails before form submission
    $('#templateForm').submit(function(e) {
        // Convert comma-separated emails to arrays
        let ccEmails = $('#cc_emails').val().split(',').map(email => email.trim()).filter(email => email);
        let bccEmails = $('#bcc_emails').val().split(',').map(email => email.trim()).filter(email => email);
        
        // Add hidden inputs for arrays
        if (ccEmails.length > 0) {
            ccEmails.forEach((email, index) => {
                $(this).append(`<input type="hidden" name="cc_emails[${index}]" value="${email}">`);
            });
        }
        
        if (bccEmails.length > 0) {
            bccEmails.forEach((email, index) => {
                $(this).append(`<input type="hidden" name="bcc_emails[${index}]" value="${email}">`);
            });
        }
    });

    // Preview template
    $('#previewBtn').click(function() {
        let formData = {
            subject: $('#subject').val(),
            body_html: $('#body_html').summernote('code'),
            body_text: $('#body_text').val(),
            from_email: $('#from_email').val(),
            from_name: $('#from_name').val()
        };
        
        $('#previewModal').modal('show');
        $('#previewContent').html(`
            <div class="text-center">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Generating preview...</p>
            </div>
        `);
        
        // Generate preview with sample data
        let sampleData = {
            user_name: 'John Doe',
            user_email: 'john.doe@example.com',
            company_name: 'Analytics Hub',
            current_date: new Date().toLocaleString(),
            admin_name: '{{ Auth::user()->name }}',
            login_url: '{{ config("app.url") }}/login'
        };
        
        let processedSubject = processVariables(formData.subject, sampleData);
        let processedHtml = processVariables(formData.body_html, sampleData);
        let processedText = processVariables(formData.body_text, sampleData);
        
        $('#previewContent').html(`
            <div class="template-preview">
                <h6>Subject:</h6>
                <p class="fw-bold">${processedSubject}</p>
                
                <h6>From:</h6>
                <p>${formData.from_name || '{{ config("mail.from.name") }}'} &lt;${formData.from_email || '{{ config("mail.from.address") }}'}&gt;</p>
                
                <h6>HTML Content:</h6>
                <div class="border p-3 mb-3" style="max-height: 400px; overflow-y: auto;">
                    ${processedHtml}
                </div>
                
                ${processedText ? `
                    <h6>Text Content:</h6>
                    <div class="border p-3 mb-3" style="max-height: 200px; overflow-y: auto; white-space: pre-wrap; font-family: monospace;">
                        ${processedText}
                    </div>
                ` : ''}
                
                <h6>Sample Data Used:</h6>
                <div class="border p-3" style="max-height: 200px; overflow-y: auto;">
                    <pre>${JSON.stringify(sampleData, null, 2)}</pre>
                </div>
            </div>
        `);
    });

    // Send test email
    $('#testBtn').click(function() {
        if (!$('#subject').val() || !$('#body_html').summernote('code')) {
            alert('Please fill in the subject and HTML content before sending a test email.');
            return;
        }
        $('#testEmailModal').modal('show');
    });

    // Handle test email form submission
    $('#testEmailForm').submit(function(e) {
        e.preventDefault();
        
        let submitBtn = $(this).find('button[type="submit"]');
        let originalText = submitBtn.html();
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Sending...');
        
        // Simulate sending test email (since template doesn't exist yet)
        setTimeout(() => {
            $('#testEmailModal').modal('hide');
            showAlert('info', 'Test email functionality will be available after the template is created.');
            submitBtn.prop('disabled', false).html(originalText);
        }, 2000);
    });

    // Load quick templates
    $('.load-template').click(function() {
        let templateType = $(this).data('template');
        
        if (confirm('This will replace the current content. Are you sure?')) {
            loadQuickTemplate(templateType);
        }
    });

    // Helper function to process variables
    function processVariables(content, data) {
        if (!content) return '';
        
        for (let key in data) {
            let placeholder = '{{' + key + '}}';
            content = content.replace(new RegExp(placeholder.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g'), data[key]);
        }
        
        return content;
    }

    // Helper function to load quick templates
    function loadQuickTemplate(type) {
        let templates = {
            welcome: {
                subject: 'Welcome to {{company_name}}, {{user_name}}!',
                html: `
                    <h2>Welcome to {{company_name}}!</h2>
                    <p>Dear {{user_name}},</p>
                    <p>We're excited to have you join our platform. Your account has been successfully created.</p>
                    <p><strong>Your login details:</strong></p>
                    <ul>
                        <li>Email: {{user_email}}</li>
                        <li>Temporary Password: {{temp_password}}</li>
                    </ul>
                    <p><a href="{{login_url}}" style="background-color: #4e73df; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Login Now</a></p>
                    <p>Best regards,<br>{{company_name}} Team</p>
                `,
                text: `Welcome to {{company_name}}!\n\nDear {{user_name}},\n\nWe're excited to have you join our platform. Your account has been successfully created.\n\nYour login details:\nEmail: {{user_email}}\nTemporary Password: {{temp_password}}\n\nLogin at: {{login_url}}\n\nBest regards,\n{{company_name}} Team`
            },
            notification: {
                subject: 'Important Notification - {{company_name}}',
                html: `
                    <h2>Important Notification</h2>
                    <p>Dear {{user_name}},</p>
                    <p>We have an important update to share with you regarding your account.</p>
                    <p>[Your notification content goes here]</p>
                    <p>If you have any questions, please don't hesitate to contact us.</p>
                    <p>Best regards,<br>{{company_name}} Team</p>
                    <p><small>This email was sent on {{current_date}}</small></p>
                `,
                text: `Important Notification\n\nDear {{user_name}},\n\nWe have an important update to share with you regarding your account.\n\n[Your notification content goes here]\n\nIf you have any questions, please don't hesitate to contact us.\n\nBest regards,\n{{company_name}} Team\n\nThis email was sent on {{current_date}}`
            },
            announcement: {
                subject: 'Announcement: [Your Subject Here]',
                html: `
                    <h2>Important Announcement</h2>
                    <p>Dear {{user_name}},</p>
                    <p>We're pleased to announce [your announcement here].</p>
                    <p><strong>Key highlights:</strong></p>
                    <ul>
                        <li>Feature 1</li>
                        <li>Feature 2</li>
                        <li>Feature 3</li>
                    </ul>
                    <p>For more information, please visit our website or contact support.</p>
                    <p>Thank you for being a valued member of {{company_name}}.</p>
                    <p>Best regards,<br>{{admin_name}}<br>{{company_name}} Team</p>
                `,
                text: `Important Announcement\n\nDear {{user_name}},\n\nWe're pleased to announce [your announcement here].\n\nKey highlights:\n- Feature 1\n- Feature 2\n- Feature 3\n\nFor more information, please visit our website or contact support.\n\nThank you for being a valued member of {{company_name}}.\n\nBest regards,\n{{admin_name}}\n{{company_name}} Team`
            }
        };
        
        if (templates[type]) {
            $('#subject').val(templates[type].subject);
            $('#body_html').summernote('code', templates[type].html);
            $('#body_text').val(templates[type].text);
        }
    }

    // Helper function to show alerts
    function showAlert(type, message) {
        let alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-info-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        $('.container-fluid').prepend(alertHtml);
        
        setTimeout(() => {
            $('.alert').fadeOut();
        }, 5000);
    }
});
</script>
@endpush