@extends('layouts.app')

@section('title', 'View Email Template')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">{{ $template->display_name }}</h1>
            <p class="mb-0 text-muted">Email Template Details</p>
        </div>
        <div>
            <a href="{{ route('admin.email-templates.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Templates
            </a>
            @if($template->type !== 'system' || Auth::user()->hasRole(['super_admin']))
                <a href="{{ route('admin.email-templates.edit', $template) }}" class="btn btn-primary">
                    <i class="fas fa-edit me-2"></i>Edit Template
                </a>
            @endif
        </div>
    </div>

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Template Information -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Template Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-bold">Template Name:</td>
                                    <td><code>{{ $template->name }}</code></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Display Name:</td>
                                    <td>{{ $template->display_name }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Category:</td>
                                    <td>
                                        <span class="badge bg-info">{{ ucfirst($template->category) }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Type:</td>
                                    <td>
                                        <span class="badge bg-{{ $template->type === 'system' ? 'warning' : 'primary' }}">
                                            {{ ucfirst($template->type) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Status:</td>
                                    <td>
                                        <span class="badge bg-{{ $template->is_active ? 'success' : 'secondary' }}">
                                            {{ $template->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-bold">Version:</td>
                                    <td>{{ $template->version }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Language:</td>
                                    <td>
                                        <span class="badge bg-secondary">{{ strtoupper($template->language) }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Priority:</td>
                                    <td>
                                        @php
                                            $priorityColors = [1 => 'danger', 2 => 'warning', 3 => 'info', 4 => 'secondary', 5 => 'secondary'];
                                            $priorityLabels = [1 => 'Highest', 2 => 'High', 3 => 'Normal', 4 => 'Low', 5 => 'Lowest'];
                                        @endphp
                                        <span class="badge bg-{{ $priorityColors[$template->priority] ?? 'secondary' }}">
                                            {{ $priorityLabels[$template->priority] ?? 'Normal' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Event Trigger:</td>
                                    <td>{{ $template->event_trigger ?: 'Manual' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Usage Count:</td>
                                    <td>{{ $template->usage_count ?? 0 }} times</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    @if($template->description)
                        <div class="mt-3">
                            <h6 class="fw-bold">Description:</h6>
                            <p class="text-muted">{{ $template->description }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Email Content -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Email Content</h6>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="previewBtn">
                            <i class="fas fa-eye me-1"></i>Preview
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success" id="testBtn">
                            <i class="fas fa-paper-plane me-1"></i>Test
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h6 class="fw-bold">Subject Line:</h6>
                        <div class="p-3 bg-light border rounded">
                            <code>{{ $template->subject }}</code>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="fw-bold">HTML Content:</h6>
                        <div class="border rounded" style="max-height: 400px; overflow-y: auto;">
                            <div class="p-3">
                                {!! $template->body_html !!}
                            </div>
                        </div>
                    </div>
                    
                    @if($template->body_text)
                        <div class="mb-4">
                            <h6 class="fw-bold">Plain Text Content:</h6>
                            <div class="p-3 bg-light border rounded" style="max-height: 200px; overflow-y: auto; white-space: pre-wrap; font-family: monospace;">
                                {{ $template->body_text }}
                            </div>
                        </div>
                    @endif
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
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-bold">From Email:</td>
                                    <td>{{ $template->from_email ?: config('mail.from.address') }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">From Name:</td>
                                    <td>{{ $template->from_name ?: config('mail.from.name') }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Reply To:</td>
                                    <td>{{ $template->reply_to ?: 'Not set' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-bold">CC Emails:</td>
                                    <td>
                                        @if($template->cc_emails && count($template->cc_emails) > 0)
                                            @foreach($template->cc_emails as $email)
                                                <span class="badge bg-secondary me-1">{{ $email }}</span>
                                            @endforeach
                                        @else
                                            <span class="text-muted">None</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">BCC Emails:</td>
                                    <td>
                                        @if($template->bcc_emails && count($template->bcc_emails) > 0)
                                            @foreach($template->bcc_emails as $email)
                                                <span class="badge bg-secondary me-1">{{ $email }}</span>
                                            @endforeach
                                        @else
                                            <span class="text-muted">None</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Template Variables -->
            @if($template->variables && count($template->variables) > 0)
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Template Variables</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach($template->variables as $variable => $description)
                                <div class="col-md-6 mb-3">
                                    <div class="p-3 border rounded">
                                        <code class="text-primary">{{{{ $variable }}}}</code>
                                        <br><small class="text-muted">{{ $description }}</small>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if($template->type !== 'system' || Auth::user()->hasRole(['super_admin']))
                            <a href="{{ route('admin.email-templates.edit', $template) }}" class="btn btn-primary">
                                <i class="fas fa-edit me-2"></i>Edit Template
                            </a>
                        @endif
                        <button type="button" class="btn btn-success" id="testEmailBtn">
                            <i class="fas fa-paper-plane me-2"></i>Send Test Email
                        </button>
                        <button type="button" class="btn btn-info" id="duplicateBtn">
                            <i class="fas fa-copy me-2"></i>Duplicate Template
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="exportBtn">
                            <i class="fas fa-download me-2"></i>Export Template
                        </button>
                        @if($template->type !== 'system')
                            <hr>
                            <button type="button" class="btn btn-outline-danger" id="deleteBtn">
                                <i class="fas fa-trash me-2"></i>Delete Template
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Template Statistics -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-end">
                                <h4 class="text-primary mb-0">{{ $template->usage_count ?? 0 }}</h4>
                                <small class="text-muted">Times Used</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h4 class="text-success mb-0">{{ $template->version }}</h4>
                            <small class="text-muted">Current Version</small>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <small class="text-muted">
                            <strong>Created:</strong> {{ $template->created_at->format('M d, Y H:i') }}<br>
                            <strong>Modified:</strong> {{ $template->updated_at->format('M d, Y H:i') }}<br>
                            <strong>Last Used:</strong> {{ $template->last_used_at ? $template->last_used_at->format('M d, Y H:i') : 'Never' }}
                        </small>
                    </div>
                </div>
            </div>

            <!-- Version History -->
            @if($template->versions && $template->versions->count() > 0)
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Version History</h6>
                    </div>
                    <div class="card-body">
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
                                            @if($version->version != $template->version && ($template->type !== 'system' || Auth::user()->hasRole(['super_admin'])))
                                                <button type="button" class="btn btn-sm btn-outline-primary view-version" 
                                                        data-version="{{ $version->id }}">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            @endif
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
                    </div>
                </div>
            @endif

            <!-- Template Usage -->
            @if($template->usage_count > 0)
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Recent Usage</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">This template has been used {{ $template->usage_count }} times.</p>
                        
                        @if($template->last_used_at)
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Last used {{ $template->last_used_at->diffForHumans() }}
                            </div>
                        @endif
                        
                        <div class="text-center">
                            <a href="{{ route('admin.email-queue.index', ['template_id' => $template->id]) }}" 
                               class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-list me-2"></i>View Email History
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
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

<!-- Version Details Modal -->
<div class="modal fade" id="versionModal" tabindex="-1" aria-labelledby="versionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="versionModalLabel">Version Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="versionContent">
                    <!-- Version content will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="restoreVersionBtn" style="display: none;">
                    <i class="fas fa-undo me-2"></i>Restore This Version
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
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
<script>
$(document).ready(function() {
    // Preview template
    $('#previewBtn, #previewEmailBtn').click(function() {
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
            login_url: '{{ config("app.url") }}/login',
            reset_link: '{{ config("app.url") }}/password/reset/sample-token',
            temp_password: 'TempPass123!'
        };
        
        let processedSubject = processVariables('{{ addslashes($template->subject) }}', sampleData);
        let processedHtml = processVariables(`{!! addslashes($template->body_html) !!}`, sampleData);
        let processedText = processVariables(`{{ addslashes($template->body_text ?? '') }}`, sampleData);
        
        $('#previewContent').html(`
            <div class="template-preview">
                <h6>Subject:</h6>
                <p class="fw-bold">${processedSubject}</p>
                
                <h6>From:</h6>
                <p>{{ $template->from_name ?: config('mail.from.name') }} &lt;{{ $template->from_email ?: config('mail.from.address') }}&gt;</p>
                
                @if($template->cc_emails && count($template->cc_emails) > 0)
                    <h6>CC:</h6>
                    <p>{{ implode(', ', $template->cc_emails) }}</p>
                @endif
                
                @if($template->bcc_emails && count($template->bcc_emails) > 0)
                    <h6>BCC:</h6>
                    <p>{{ implode(', ', $template->bcc_emails) }}</p>
                @endif
                
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
    $('#testBtn, #testEmailBtn').click(function() {
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

    // View version details
    $('.view-version').click(function() {
        let versionId = $(this).data('version');
        
        $('#versionModal').modal('show');
        $('#versionContent').html(`
            <div class="text-center">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading version details...</p>
            </div>
        `);
        
        // Load version details via AJAX
        $.ajax({
            url: '{{ route("admin.email-templates.version-details", $template) }}',
            method: 'GET',
            data: {
                version_id: versionId
            },
            success: function(response) {
                $('#versionContent').html(response.html);
                $('#restoreVersionBtn').show().data('version-id', versionId);
            },
            error: function(xhr) {
                $('#versionContent').html('<div class="alert alert-danger">Failed to load version details.</div>');
            }
        });
    });

    // Restore version
    $('#restoreVersionBtn').click(function() {
        let versionId = $(this).data('version-id');
        
        if (confirm('Are you sure you want to restore this version? This will replace the current content.')) {
            $.ajax({
                url: '{{ route("admin.email-templates.restore-version", $template) }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    version_id: versionId
                },
                success: function(response) {
                    $('#versionModal').modal('hide');
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