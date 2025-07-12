@extends('layouts.app')

@section('title', 'Edit Email Template')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Edit Email Template</h1>
            <p class="mb-0 text-muted">Modify email template: {{ $template->display_name }}</p>
        </div>
        <div>
            <a href="{{ route('admin.email-templates.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Templates
            </a>
            <a href="{{ route('admin.email-templates.show', $template) }}" class="btn btn-info">
                <i class="fas fa-eye me-2"></i>View Template
            </a>
        </div>
    </div>

    <!-- Template Info Alert -->
    <div class="alert alert-info mb-4">
        <div class="row">
            <div class="col-md-3">
                <strong>Template ID:</strong> {{ $template->id }}
            </div>
            <div class="col-md-3">
                <strong>Version:</strong> {{ $template->version }}
            </div>
            <div class="col-md-3">
                <strong>Status:</strong> 
                <span class="badge bg-{{ $template->is_active ? 'success' : 'secondary' }}">
                    {{ $template->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
            <div class="col-md-3">
                <strong>Last Modified:</strong> {{ $template->updated_at->format('M d, Y H:i') }}
            </div>
        </div>
    </div>

    <form action="{{ route('admin.email-templates.update', $template) }}" method="POST" id="templateForm">
        @csrf
        @method('PUT')
        
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
                                           id="name" name="name" value="{{ old('name', $template->name) }}" required
                                           {{ $template->type === 'system' ? 'readonly' : '' }}>
                                    <div class="form-text">Unique identifier for the template (lowercase, underscores allowed)</div>
                                    @if($template->type === 'system')
                                        <div class="form-text text-warning">System templates cannot have their names changed</div>
                                    @endif
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="display_name" class="form-label">Display Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('display_name') is-invalid @enderror" 
                                           id="display_name" name="display_name" value="{{ old('display_name', $template->display_name) }}" required>
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
                                      id="description" name="description" rows="3">{{ old('description', $template->description) }}</textarea>
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
                                            <option value="{{ $value }}" {{ old('category', $template->category) == $value ? 'selected' : '' }}>
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
                                            id="type" name="type" required {{ $template->type === 'system' ? 'disabled' : '' }}>
                                        <option value="">Select Type</option>
                                        @foreach($types as $value => $label)
                                            <option value="{{ $value }}" {{ old('type', $template->type) == $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if($template->type === 'system')
                                        <input type="hidden" name="type" value="{{ $template->type }}">
                                        <div class="form-text text-warning">System template type cannot be changed</div>
                                    @endif
                                    @error('type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="event_trigger" class="form-label">Event Trigger</label>
                                    <input type="text" class="form-control @error('event_trigger') is-invalid @enderror" 
                                           id="event_trigger" name="event_trigger" value="{{ old('event_trigger', $template->event_trigger) }}">
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
                                   id="subject" name="subject" value="{{ old('subject', $template->subject) }}" required>
                            <div class="form-text">Email subject line (variables supported: {{user_name}}, {{company_name}}, etc.)</div>
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="body_html" class="form-label">HTML Content <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('body_html') is-invalid @enderror" 
                                      id="body_html" name="body_html" rows="15" required>{{ old('body_html', $template->body_html) }}</textarea>
                            <div class="form-text">HTML version of the email content</div>
                            @error('body_html')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="body_text" class="form-label">Plain Text Content</label>
                            <textarea class="form-control @error('body_text') is-invalid @enderror" 
                                      id="body_text" name="body_text" rows="10">{{ old('body_text', $template->body_text) }}</textarea>
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
                                           id="from_email" name="from_email" value="{{ old('from_email', $template->from_email) }}">
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
                                           id="from_name" name="from_name" value="{{ old('from_name', $template->from_name) }}">
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
                                           id="reply_to" name="reply_to" value="{{ old('reply_to', $template->reply_to) }}">
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
                                            <option value="{{ $value }}" {{ old('priority', $template->priority) == $value ? 'selected' : '' }}>
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
                                        <option value="id" {{ old('language', $template->language) == 'id' ? 'selected' : '' }}>Indonesian</option>
                                        <option value="en" {{ old('language', $template->language) == 'en' ? 'selected' : '' }}>English</option>
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
                                           id="cc_emails" name="cc_emails_input" 
                                           value="{{ old('cc_emails_input', is_array($template->cc_emails) ? implode(', ', $template->cc_emails) : '') }}">
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
                                           id="bcc_emails" name="bcc_emails_input" 
                                           value="{{ old('bcc_emails_input', is_array($template->bcc_emails) ? implode(', ', $template->bcc_emails) : '') }}">
                                    <div class="form-text">Comma-separated email addresses for BCC</div>
                                    @error('bcc_emails')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" 
                                   {{ old('is_active', $template->is_active) ? 'checked' : '' }}>
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
                                <button type="button" class="btn btn-warning" id="createVersionBtn">
                                    <i class="fas fa-code-branch me-2"></i>Create New Version
                                </button>
                            </div>
                            <div>
                                <a href="{{ route('admin.email-templates.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Template
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Template Statistics -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Template Statistics</h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="border-end">
                                    <h5 class="text-primary mb-0">{{ $template->usage_count ?? 0 }}</h5>
                                    <small class="text-muted">Times Used</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <h5 class="text-success mb-0">{{ $template->version }}</h5>
                                <small class="text-muted">Current Version</small>
                            </div>
                        </div>
                        <hr>
                        <div class="text-center">
                            <small class="text-muted">
                                Created: {{ $template->created_at->format('M d, Y') }}<br>
                                Last Used: {{ $template->last_used_at ? $template->last_used_at->format('M d, Y H:i') : 'Never' }}
                            </small>
                        </div>
                    </div>
                </div>

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

                <!-- Version History -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Version History</h6>
                    </div>
                    <div class="card-body">
                        @if($template->versions && $template->versions->count() > 0)
                            <div class="timeline">
                                @foreach($template->versions->take(5) as $version)
                                    <div class="timeline-item mb-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>v{{ $version->version }}</strong>
                                                @if($version->version == $template->version)
                                                    <span class="badge bg-success ms-2">Current</span>
                                                @endif
                                            </div>
                                            <div>
                                                <button type="button" class="btn btn-sm btn-outline-primary restore-version" 
                                                        data-version="{{ $version->id }}" 
                                                        {{ $version->version == $template->version ? 'disabled' : '' }}>
                                                    <i class="fas fa-undo"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <small class="text-muted">
                                            {{ $version->created_at->format('M d, Y H:i') }}<br>
                                            by {{ $version->created_by_name ?? 'System' }}
                                        </small>
                                        @if($version->change_notes)
                                            <div class="mt-1">
                                                <small class="text-info">{{ $version->change_notes }}</small>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                            
                            @if($template->versions->count() > 5)
                                <div class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="viewAllVersions">
                                        View All {{ $template->versions->count() }} Versions
                                    </button>
                                </div>
                            @endif
                        @else
                            <p class="text-muted mb-0">No version history available.</p>
                        @endif
                    </div>
                </div>

                <!-- Template Actions -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Template Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-outline-primary" id="duplicateBtn">
                                <i class="fas fa-copy me-2"></i>Duplicate Template
                            </button>
                            <button type="button" class="btn btn-outline-info" id="exportBtn">
                                <i class="fas fa-download me-2"></i>Export Template
                            </button>
                            @if($template->type !== 'system')
                                <button type="button" class="btn btn-outline-danger" id="deleteBtn">
                                    <i class="fas fa-trash me-2"></i>Delete Template
                                </button>
                            @endif
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

<!-- Create Version Modal -->
<div class="modal fade" id="createVersionModal" tabindex="-1" aria-labelledby="createVersionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createVersionModalLabel">Create New Version</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createVersionForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="changeNotes" class="form-label">Change Notes</label>
                        <textarea class="form-control" id="changeNotes" name="change_notes" rows="3" 
                                  placeholder="Describe the changes made in this version..."></textarea>
                        <div class="form-text">Optional notes about what changed in this version.</div>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Creating a new version will save the current state as a new version and increment the version number.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-code-branch me-2"></i>Create Version
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
.timeline-item {
    position: relative;
    padding-left: 1rem;
    border-left: 2px solid #e3e6f0;
}
.timeline-item:last-child {
    border-left: none;
}
.timeline-item::before {
    content: '';
    position: absolute;
    left: -5px;
    top: 0;
    width: 8px;
    height: 8px;
    background-color: #4e73df;
    border-radius: 50%;
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
        ]
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
        $('#testEmailModal').modal('show');
    });

    // Handle test email form submission
    $('#testEmailForm').submit(function(e) {
        e.preventDefault();
        
        let submitBtn = $(this).find('button[type="submit"]');
        let originalText = submitBtn.html();
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Sending...');
        
        // Send test email via AJAX
        $.ajax({
            url: '{{ route("admin.email-templates.test", $template) }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                test_email: $('#testEmail').val()
            },
            success: function(response) {
                $('#testEmailModal').modal('hide');
                showAlert('success', 'Test email sent successfully!');
            },
            error: function(xhr) {
                let message = xhr.responseJSON?.message || 'Failed to send test email';
                showAlert('danger', message);
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Create new version
    $('#createVersionBtn').click(function() {
        $('#createVersionModal').modal('show');
    });

    // Handle create version form submission
    $('#createVersionForm').submit(function(e) {
        e.preventDefault();
        
        let submitBtn = $(this).find('button[type="submit"]');
        let originalText = submitBtn.html();
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Creating...');
        
        // Create version via AJAX
        $.ajax({
            url: '{{ route("admin.email-templates.create-version", $template) }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                change_notes: $('#changeNotes').val()
            },
            success: function(response) {
                $('#createVersionModal').modal('hide');
                showAlert('success', 'New version created successfully!');
                // Reload page to show new version
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            },
            error: function(xhr) {
                let message = xhr.responseJSON?.message || 'Failed to create version';
                showAlert('danger', message);
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Restore version
    $('.restore-version').click(function() {
        let versionId = $(this).data('version');
        
        if (confirm('Are you sure you want to restore this version? This will replace the current content.')) {
            $.ajax({
                url: '{{ route("admin.email-templates.restore-version", $template) }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    version_id: versionId
                },
                success: function(response) {
                    showAlert('success', 'Version restored successfully!');
                    // Reload page to show restored content
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                },
                error: function(xhr) {
                    let message = xhr.responseJSON?.message || 'Failed to restore version';
                    showAlert('danger', message);
                }
            });
        }
    });

    // Duplicate template
    $('#duplicateBtn').click(function() {
        if (confirm('Create a duplicate of this template?')) {
            window.location.href = '{{ route("admin.email-templates.duplicate", $template) }}';
        }
    });

    // Export template
    $('#exportBtn').click(function() {
        window.location.href = '{{ route("admin.email-templates.export", $template) }}';
    });

    // Delete template
    $('#deleteBtn').click(function() {
        if (confirm('Are you sure you want to delete this template? This action cannot be undone.')) {
            let form = $('<form>', {
                method: 'POST',
                action: '{{ route("admin.email-templates.destroy", $template) }}'
            });
            
            form.append('{{ csrf_field() }}');
            form.append('{{ method_field("DELETE") }}');
            
            $('body').append(form);
            form.submit();
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

    // Helper function to show alerts
    function showAlert(type, message) {
        let alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'danger' ? 'fa-exclamation-circle' : 'fa-info-circle'} me-2"></i>
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