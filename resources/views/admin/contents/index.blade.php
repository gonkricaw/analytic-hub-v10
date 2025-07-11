@extends('layouts.admin')

@section('title', 'Content Management')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Content Management</h1>
            <p class="mb-0 text-muted">Manage custom HTML content and secure embedded reports</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#bulkActionModal">
                <i class="fas fa-tasks"></i> Bulk Actions
            </button>
            <a href="{{ route('admin.contents.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create Content
            </a>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="filterForm" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" placeholder="Search content...">
                </div>
                <div class="col-md-2">
                    <label for="type" class="form-label">Type</label>
                    <select class="form-select" id="type" name="type">
                        <option value="">All Types</option>
                        <option value="custom">Custom HTML</option>
                        <option value="embedded">Embedded</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="created_by" class="form-label">Author</label>
                    <select class="form-select" id="created_by" name="created_by">
                        <option value="">All Authors</option>
                        <!-- Authors will be populated via AJAX -->
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="date_range" class="form-label">Date Range</label>
                    <select class="form-select" id="date_range" name="date_range">
                        <option value="">All Time</option>
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                        <option value="year">This Year</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-1">
                        <button type="submit" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-search"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetFilters()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Content Statistics -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Content</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalContent">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-alt fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Published</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="publishedContent">0</div>
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
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Views</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalViews">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-eye fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Embedded Content</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="embeddedContent">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-external-link-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Content List</h6>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="refreshTable()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-download"></i> Export
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" onclick="exportData('csv')">CSV</a></li>
                        <li><a class="dropdown-item" href="#" onclick="exportData('excel')">Excel</a></li>
                        <li><a class="dropdown-item" href="#" onclick="exportData('pdf')">PDF</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="contentTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="30">
                                <input type="checkbox" id="selectAll" class="form-check-input">
                            </th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Author</th>
                            <th>Views</th>
                            <th>Published</th>
                            <th>Updated</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via DataTables AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Action Modal -->
<div class="modal fade" id="bulkActionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Actions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="bulkActionForm">
                    <div class="mb-3">
                        <label for="bulkAction" class="form-label">Select Action</label>
                        <select class="form-select" id="bulkAction" name="action" required>
                            <option value="">Choose action...</option>
                            <option value="publish">Publish</option>
                            <option value="unpublish">Unpublish</option>
                            <option value="archive">Archive</option>
                            <option value="delete">Delete</option>
                            <option value="duplicate">Duplicate</option>
                        </select>
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Warning:</strong> This action will be applied to all selected content items.
                    </div>
                    <div id="selectedCount" class="text-muted">No items selected</div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="executeBulkAction()">Execute Action</button>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Content Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="previewFrame" style="width: 100%; height: 600px; border: none;"></iframe>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<link href="{{ asset('vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
<style>
    .content-type-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
    
    .status-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
    
    .action-buttons .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
        margin-right: 0.25rem;
    }
    
    .table td {
        vertical-align: middle;
    }
    
    .content-title {
        font-weight: 600;
        color: #2c3e50;
    }
    
    .content-excerpt {
        color: #6c757d;
        font-size: 0.85rem;
        margin-top: 0.25rem;
    }
    
    .view-count {
        font-weight: 600;
        color: #007bff;
    }
    
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
</style>
@endsection

@section('scripts')
<script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
<script>
$(document).ready(function() {
    // Initialize DataTable
    const table = $('#contentTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.contents.data") }}',
            data: function(d) {
                d.search = $('#search').val();
                d.type = $('#type').val();
                d.status = $('#status').val();
                d.created_by = $('#created_by').val();
                d.date_range = $('#date_range').val();
            }
        },
        columns: [
            {
                data: 'id',
                name: 'id',
                orderable: false,
                searchable: false,
                render: function(data) {
                    return `<input type="checkbox" class="form-check-input content-checkbox" value="${data}">`;
                }
            },
            {
                data: 'title',
                name: 'title',
                render: function(data, type, row) {
                    let html = `<div class="content-title">${data}</div>`;
                    if (row.excerpt) {
                        html += `<div class="content-excerpt">${row.excerpt.substring(0, 100)}...</div>`;
                    }
                    return html;
                }
            },
            {
                data: 'type',
                name: 'type',
                render: function(data) {
                    const badges = {
                        'custom': '<span class="badge bg-primary content-type-badge">Custom HTML</span>',
                        'embedded': '<span class="badge bg-info content-type-badge">Embedded</span>'
                    };
                    return badges[data] || data;
                }
            },
            {
                data: 'status',
                name: 'status',
                render: function(data) {
                    const badges = {
                        'draft': '<span class="badge bg-secondary status-badge">Draft</span>',
                        'published': '<span class="badge bg-success status-badge">Published</span>',
                        'archived': '<span class="badge bg-warning status-badge">Archived</span>'
                    };
                    return badges[data] || data;
                }
            },
            {
                data: 'created_by_user',
                name: 'created_by_user.name',
                render: function(data) {
                    return data ? data.name : 'Unknown';
                }
            },
            {
                data: 'view_count',
                name: 'view_count',
                render: function(data) {
                    return `<span class="view-count">${parseInt(data).toLocaleString()}</span>`;
                }
            },
            {
                data: 'published_at',
                name: 'published_at',
                render: function(data) {
                    return data ? new Date(data).toLocaleDateString() : 'Not published';
                }
            },
            {
                data: 'updated_at',
                name: 'updated_at',
                render: function(data) {
                    return new Date(data).toLocaleDateString();
                }
            },
            {
                data: 'id',
                name: 'actions',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    return `
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-outline-primary" onclick="previewContent(${data})" title="Preview">
                                <i class="fas fa-eye"></i>
                            </button>
                            <a href="/admin/contents/${data}/edit" class="btn btn-sm btn-outline-secondary" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button class="btn btn-sm btn-outline-info" onclick="duplicateContent(${data})" title="Duplicate">
                                <i class="fas fa-copy"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-${row.status === 'published' ? 'warning' : 'success'}" 
                                    onclick="toggleStatus(${data})" title="${row.status === 'published' ? 'Unpublish' : 'Publish'}">
                                <i class="fas fa-${row.status === 'published' ? 'pause' : 'play'}"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteContent(${data})" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        order: [[7, 'desc']],
        pageLength: 25,
        responsive: true,
        language: {
            processing: '<i class="fas fa-spinner fa-spin"></i> Loading content...'
        }
    });

    // Filter form submission
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        table.ajax.reload();
    });

    // Select all checkbox
    $('#selectAll').on('change', function() {
        $('.content-checkbox').prop('checked', this.checked);
        updateSelectedCount();
    });

    // Individual checkbox change
    $(document).on('change', '.content-checkbox', function() {
        updateSelectedCount();
        
        // Update select all checkbox
        const totalCheckboxes = $('.content-checkbox').length;
        const checkedCheckboxes = $('.content-checkbox:checked').length;
        $('#selectAll').prop('checked', totalCheckboxes === checkedCheckboxes);
    });

    // Load statistics
    loadStatistics();
    
    // Load authors for filter
    loadAuthors();
});

// Update selected count
function updateSelectedCount() {
    const count = $('.content-checkbox:checked').length;
    $('#selectedCount').text(`${count} item(s) selected`);
}

// Load statistics
function loadStatistics() {
    // This would typically be an AJAX call to get statistics
    // For now, we'll use placeholder values
    $('#totalContent').text('0');
    $('#publishedContent').text('0');
    $('#totalViews').text('0');
    $('#embeddedContent').text('0');
}

// Load authors for filter
function loadAuthors() {
    // This would typically be an AJAX call to get authors
    // For now, we'll leave it empty
}

// Reset filters
function resetFilters() {
    $('#filterForm')[0].reset();
    $('#contentTable').DataTable().ajax.reload();
}

// Refresh table
function refreshTable() {
    $('#contentTable').DataTable().ajax.reload();
    loadStatistics();
}

// Preview content
function previewContent(id) {
    $('#previewFrame').attr('src', `/admin/contents/${id}/preview`);
    $('#previewModal').modal('show');
}

// Duplicate content
function duplicateContent(id) {
    if (confirm('Are you sure you want to duplicate this content?')) {
        $.post(`/admin/contents/${id}/duplicate`, {
            _token: '{{ csrf_token() }}'
        })
        .done(function(response) {
            if (response.success) {
                Swal.fire('Success!', 'Content duplicated successfully.', 'success');
                $('#contentTable').DataTable().ajax.reload();
            } else {
                Swal.fire('Error!', response.message || 'Failed to duplicate content.', 'error');
            }
        })
        .fail(function() {
            Swal.fire('Error!', 'Failed to duplicate content.', 'error');
        });
    }
}

// Toggle content status
function toggleStatus(id) {
    $.post(`/admin/contents/${id}/toggle-status`, {
        _token: '{{ csrf_token() }}'
    })
    .done(function(response) {
        if (response.success) {
            Swal.fire('Success!', 'Content status updated successfully.', 'success');
            $('#contentTable').DataTable().ajax.reload();
        } else {
            Swal.fire('Error!', response.message || 'Failed to update status.', 'error');
        }
    })
    .fail(function() {
        Swal.fire('Error!', 'Failed to update status.', 'error');
    });
}

// Delete content
function deleteContent(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/admin/contents/${id}`,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                }
            })
            .done(function(response) {
                if (response.success) {
                    Swal.fire('Deleted!', 'Content has been deleted.', 'success');
                    $('#contentTable').DataTable().ajax.reload();
                } else {
                    Swal.fire('Error!', response.message || 'Failed to delete content.', 'error');
                }
            })
            .fail(function() {
                Swal.fire('Error!', 'Failed to delete content.', 'error');
            });
        }
    });
}

// Execute bulk action
function executeBulkAction() {
    const action = $('#bulkAction').val();
    const selectedIds = $('.content-checkbox:checked').map(function() {
        return this.value;
    }).get();
    
    if (!action) {
        Swal.fire('Error!', 'Please select an action.', 'error');
        return;
    }
    
    if (selectedIds.length === 0) {
        Swal.fire('Error!', 'Please select at least one content item.', 'error');
        return;
    }
    
    const confirmText = action === 'delete' ? 
        'This will permanently delete the selected content items!' : 
        `This will ${action} the selected content items.`;
    
    Swal.fire({
        title: 'Are you sure?',
        text: confirmText,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: action === 'delete' ? '#d33' : '#3085d6',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, proceed!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('{{ route("admin.contents.bulk-action") }}', {
                _token: '{{ csrf_token() }}',
                action: action,
                ids: selectedIds
            })
            .done(function(response) {
                if (response.success) {
                    Swal.fire('Success!', response.message, 'success');
                    $('#contentTable').DataTable().ajax.reload();
                    $('#bulkActionModal').modal('hide');
                    $('#selectAll').prop('checked', false);
                } else {
                    Swal.fire('Error!', response.message || 'Bulk action failed.', 'error');
                }
            })
            .fail(function() {
                Swal.fire('Error!', 'Bulk action failed.', 'error');
            });
        }
    });
}

// Export data
function exportData(format) {
    const params = new URLSearchParams({
        format: format,
        search: $('#search').val(),
        type: $('#type').val(),
        status: $('#status').val(),
        created_by: $('#created_by').val(),
        date_range: $('#date_range').val()
    });
    
    window.open(`/admin/contents/export?${params.toString()}`, '_blank');
}
</script>
@endsection