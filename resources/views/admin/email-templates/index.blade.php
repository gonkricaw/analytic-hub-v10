@extends('layouts.app')

@section('title', 'Email Templates')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Email Templates</h1>
            <p class="mb-0 text-muted">Manage email templates for system notifications and communications</p>
        </div>
        <div>
            <a href="{{ route('admin.email-templates.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Create Template
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Templates
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_templates'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-envelope fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Active Templates
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['active_templates'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                System Templates
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['system_templates'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-cog fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Custom Templates
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['user_templates'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-edit fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Templates</h6>
        </div>
        <div class="card-body">
            <form id="filterForm" class="row g-3">
                <div class="col-md-3">
                    <label for="categoryFilter" class="form-label">Category</label>
                    <select class="form-select" id="categoryFilter" name="category">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category }}">{{ ucfirst($category) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="typeFilter" class="form-label">Type</label>
                    <select class="form-select" id="typeFilter" name="type">
                        <option value="">All Types</option>
                        @foreach($types as $type)
                            <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="statusFilter" class="form-label">Status</label>
                    <select class="form-select" id="statusFilter" name="status">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="templateTypeFilter" class="form-label">Template Type</label>
                    <select class="form-select" id="templateTypeFilter" name="template_type">
                        <option value="">All Templates</option>
                        <option value="system">System Templates</option>
                        <option value="custom">Custom Templates</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="button" class="btn btn-primary" id="applyFilters">
                        <i class="fas fa-filter me-2"></i>Apply Filters
                    </button>
                    <button type="button" class="btn btn-secondary" id="clearFilters">
                        <i class="fas fa-times me-2"></i>Clear Filters
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Templates Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Email Templates</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="templatesTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Display Name</th>
                            <th>Subject</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Usage</th>
                            <th>Version</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via DataTables -->
                    </tbody>
                </table>
            </div>
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
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading preview...</p>
                    </div>
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
                        <input type="email" class="form-control" id="testEmail" name="test_email" required>
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

<!-- Version History Modal -->
<div class="modal fade" id="versionHistoryModal" tabindex="-1" aria-labelledby="versionHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="versionHistoryModalLabel">Version History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="versionHistoryContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading version history...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="{{ asset('vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
<style>
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}
.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}
.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}
.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}
.template-preview {
    border: 1px solid #e3e6f0;
    border-radius: 0.35rem;
    padding: 1rem;
    background-color: #f8f9fc;
}
.version-item {
    border: 1px solid #e3e6f0;
    border-radius: 0.35rem;
    padding: 1rem;
    margin-bottom: 0.5rem;
}
.version-item.current {
    background-color: #d1ecf1;
    border-color: #bee5eb;
}
.btn-group .btn {
    margin-right: 2px;
}
.btn-group .btn:last-child {
    margin-right: 0;
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
<script>
$(document).ready(function() {
    // Initialize DataTable
    let table = $('#templatesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.email-templates.data") }}',
            data: function(d) {
                d.category = $('#categoryFilter').val();
                d.type = $('#typeFilter').val();
                d.status = $('#statusFilter').val();
                d.template_type = $('#templateTypeFilter').val();
            }
        },
        columns: [
            { data: 'name', name: 'name' },
            { data: 'display_name', name: 'display_name' },
            { data: 'subject', name: 'subject', orderable: false },
            { data: 'category_badge', name: 'category', orderable: false },
            { data: 'type_badge', name: 'type', orderable: false },
            { data: 'status_badge', name: 'is_active', orderable: false },
            { data: 'usage_info', name: 'usage_count', orderable: false },
            { data: 'version', name: 'version' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'asc']],
        pageLength: 25,
        responsive: true,
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
        }
    });

    // Apply filters
    $('#applyFilters').click(function() {
        table.ajax.reload();
    });

    // Clear filters
    $('#clearFilters').click(function() {
        $('#filterForm')[0].reset();
        table.ajax.reload();
    });

    // Preview template
    $(document).on('click', '.preview-template', function() {
        let templateId = $(this).data('id');
        
        $('#previewModal').modal('show');
        $('#previewContent').html(`
            <div class="text-center">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading preview...</p>
            </div>
        `);
        
        $.ajax({
            url: `/admin/email-templates/${templateId}/preview`,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    let preview = response.preview;
                    $('#previewContent').html(`
                        <div class="template-preview">
                            <h6>Subject:</h6>
                            <p class="fw-bold">${preview.subject}</p>
                            
                            <h6>From:</h6>
                            <p>${preview.from_name} &lt;${preview.from_email}&gt;</p>
                            
                            <h6>HTML Content:</h6>
                            <div class="border p-3 mb-3" style="max-height: 400px; overflow-y: auto;">
                                ${preview.html_body}
                            </div>
                            
                            ${preview.text_body ? `
                                <h6>Text Content:</h6>
                                <div class="border p-3 mb-3" style="max-height: 200px; overflow-y: auto; white-space: pre-wrap; font-family: monospace;">
                                    ${preview.text_body}
                                </div>
                            ` : ''}
                            
                            <h6>Sample Data Used:</h6>
                            <div class="border p-3" style="max-height: 200px; overflow-y: auto;">
                                <pre>${JSON.stringify(preview.sample_data, null, 2)}</pre>
                            </div>
                        </div>
                    `);
                } else {
                    $('#previewContent').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ${response.message || 'Failed to load preview'}
                        </div>
                    `);
                }
            },
            error: function() {
                $('#previewContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Failed to load template preview. Please try again.
                    </div>
                `);
            }
        });
    });

    // Test template
    let currentTestTemplateId = null;
    $(document).on('click', '.test-template', function() {
        currentTestTemplateId = $(this).data('id');
        $('#testEmailModal').modal('show');
        $('#testEmail').val('{{ Auth::user()->email }}'); // Pre-fill with current user's email
    });

    // Send test email
    $('#testEmailForm').submit(function(e) {
        e.preventDefault();
        
        let submitBtn = $(this).find('button[type="submit"]');
        let originalText = submitBtn.html();
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Sending...');
        
        $.ajax({
            url: `/admin/email-templates/${currentTestTemplateId}/test`,
            method: 'POST',
            data: $(this).serialize(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $('#testEmailModal').modal('hide');
                    showAlert('success', response.message);
                } else {
                    showAlert('danger', response.message);
                }
            },
            error: function(xhr) {
                let message = 'Failed to send test email. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                showAlert('danger', message);
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Toggle template status
    $(document).on('click', '.toggle-status', function() {
        let templateId = $(this).data('id');
        let action = $(this).data('action');
        let button = $(this);
        
        if (confirm(`Are you sure you want to ${action} this template?`)) {
            $.ajax({
                url: `/admin/email-templates/${templateId}/toggle-status`,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        showAlert('success', response.message);
                        table.ajax.reload(null, false);
                    } else {
                        showAlert('danger', response.message);
                    }
                },
                error: function(xhr) {
                    let message = 'Failed to update template status. Please try again.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    showAlert('danger', message);
                }
            });
        }
    });

    // Version history
    $(document).on('click', '.version-history', function() {
        let templateId = $(this).data('id');
        
        $('#versionHistoryModal').modal('show');
        $('#versionHistoryContent').html(`
            <div class="text-center">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading version history...</p>
            </div>
        `);
        
        $.ajax({
            url: `/admin/email-templates/${templateId}/versions`,
            method: 'GET',
            success: function(response) {
                if (response.success && response.versions.length > 0) {
                    let html = '';
                    response.versions.forEach(function(version) {
                        html += `
                            <div class="version-item ${version.is_current ? 'current' : ''}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">
                                            Version ${version.version}
                                            ${version.is_current ? '<span class="badge bg-primary ms-2">Current</span>' : ''}
                                        </h6>
                                        <small class="text-muted">
                                            Created: ${version.created_at} by ${version.created_by}<br>
                                            Usage: ${version.usage_count} times
                                            ${version.is_active ? '<span class="badge bg-success ms-2">Active</span>' : '<span class="badge bg-secondary ms-2">Inactive</span>'}
                                        </small>
                                    </div>
                                    <div>
                                        ${!version.is_current ? `
                                            <button type="button" class="btn btn-sm btn-outline-primary restore-version" 
                                                    data-template-id="${templateId}" data-version-id="${version.id}">
                                                <i class="fas fa-undo me-1"></i>Restore
                                            </button>
                                        ` : ''}
                                        <a href="/admin/email-templates/${version.id}" class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-eye me-1"></i>View
                                        </a>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    $('#versionHistoryContent').html(html);
                } else {
                    $('#versionHistoryContent').html(`
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            No version history available for this template.
                        </div>
                    `);
                }
            },
            error: function() {
                $('#versionHistoryContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Failed to load version history. Please try again.
                    </div>
                `);
            }
        });
    });

    // Restore version
    $(document).on('click', '.restore-version', function() {
        let templateId = $(this).data('template-id');
        let versionId = $(this).data('version-id');
        
        if (confirm('Are you sure you want to restore this version? This will make it the current active version.')) {
            $.ajax({
                url: `/admin/email-templates/${templateId}/versions/${versionId}/restore`,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        $('#versionHistoryModal').modal('hide');
                        showAlert('success', response.message);
                        if (response.redirect_url) {
                            setTimeout(() => {
                                window.location.href = response.redirect_url;
                            }, 1500);
                        } else {
                            table.ajax.reload(null, false);
                        }
                    } else {
                        showAlert('danger', response.message);
                    }
                },
                error: function(xhr) {
                    let message = 'Failed to restore version. Please try again.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    showAlert('danger', message);
                }
            });
        }
    });

    // Helper function to show alerts
    function showAlert(type, message) {
        let alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        // Remove existing alerts
        $('.alert').remove();
        
        // Add new alert at the top of the container
        $('.container-fluid').prepend(alertHtml);
        
        // Auto-hide success alerts after 5 seconds
        if (type === 'success') {
            setTimeout(() => {
                $('.alert-success').fadeOut();
            }, 5000);
        }
    }
});
</script>
@endpush