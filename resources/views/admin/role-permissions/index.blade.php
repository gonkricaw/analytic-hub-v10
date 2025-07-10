@extends('layouts.app')

@section('title', 'Role-Permission Assignment')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card bg-dark text-white">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-users-cog me-2"></i>
                        Role-Permission Assignment Matrix
                    </h4>
                    <div>
                        <button type="button" class="btn btn-success me-2" id="bulkAssignBtn">
                            <i class="fas fa-plus-circle me-1"></i>
                            Bulk Assign
                        </button>
                        <button type="button" class="btn btn-info me-2" id="syncPermissionsBtn">
                            <i class="fas fa-sync me-1"></i>
                            Sync Permissions
                        </button>
                        <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary me-2">
                            <i class="fas fa-users me-1"></i>
                            Manage Roles
                        </a>
                        <a href="{{ route('admin.permissions.index') }}" class="btn btn-secondary">
                            <i class="fas fa-key me-1"></i>
                            Manage Permissions
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Filters -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <label for="roleFilter" class="form-label">
                                <i class="fas fa-filter me-1"></i>
                                Filter by Role
                            </label>
                            <select class="form-select bg-dark text-white border-secondary" id="roleFilter">
                                <option value="">All Roles</option>
                                <!-- Will be populated dynamically -->
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="moduleFilter" class="form-label">
                                <i class="fas fa-cube me-1"></i>
                                Filter by Module
                            </label>
                            <select class="form-select bg-dark text-white border-secondary" id="moduleFilter">
                                <option value="">All Modules</option>
                                <!-- Will be populated dynamically -->
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="statusFilter" class="form-label">
                                <i class="fas fa-toggle-on me-1"></i>
                                Filter by Status
                            </label>
                            <select class="form-select bg-dark text-white border-secondary" id="statusFilter">
                                <option value="">All Statuses</option>
                                <option value="active">Active Only</option>
                                <option value="inactive">Inactive Only</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="searchInput" class="form-label">
                                <i class="fas fa-search me-1"></i>
                                Search
                            </label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" id="searchInput" placeholder="Search permissions...">
                        </div>
                    </div>

                    <!-- Matrix Controls -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-success" id="selectAllBtn">
                                        <i class="fas fa-check-square me-1"></i>
                                        Select All
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-warning" id="deselectAllBtn">
                                        <i class="fas fa-square me-1"></i>
                                        Deselect All
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-info" id="toggleSelectionBtn">
                                        <i class="fas fa-exchange-alt me-1"></i>
                                        Toggle Selection
                                    </button>
                                </div>
                                <div>
                                    <span class="badge bg-info" id="selectedCount">0 selected</span>
                                    <span class="badge bg-secondary" id="totalCount">0 total</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Assignment Matrix -->
                    <div class="table-responsive">
                        <div id="matrixContainer">
                            <div class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-3 text-muted">Loading assignment matrix...</p>
                            </div>
                        </div>
                    </div>

                    <!-- Legend -->
                    <div class="mt-4">
                        <h6 class="text-muted mb-3">
                            <i class="fas fa-info-circle me-2"></i>
                            Legend
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <span>Permission assigned to role</span>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-times-circle text-danger me-2"></i>
                                    <span>Permission not assigned to role</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge bg-warning text-dark me-2">S</span>
                                    <span>System role (protected)</span>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge bg-secondary me-2">I</span>
                                    <span>Inherited permission</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Assign Modal -->
<div class="modal fade" id="bulkAssignModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle me-2"></i>
                    Bulk Assign Permissions
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="bulkAssignForm">
                    <div class="mb-3">
                        <label for="bulkRoles" class="form-label">
                            <i class="fas fa-users me-1"></i>
                            Select Roles <span class="text-danger">*</span>
                        </label>
                        <select class="form-select bg-dark text-white border-secondary" id="bulkRoles" name="roles[]" multiple required>
                            <!-- Will be populated dynamically -->
                        </select>
                        <div class="form-text text-muted">
                            Hold Ctrl/Cmd to select multiple roles
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="bulkPermissions" class="form-label">
                            <i class="fas fa-key me-1"></i>
                            Select Permissions <span class="text-danger">*</span>
                        </label>
                        <select class="form-select bg-dark text-white border-secondary" id="bulkPermissions" name="permissions[]" multiple required>
                            <!-- Will be populated dynamically -->
                        </select>
                        <div class="form-text text-muted">
                            Hold Ctrl/Cmd to select multiple permissions
                        </div>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="overwriteExisting" name="overwrite">
                        <label class="form-check-label" for="overwriteExisting">
                            Overwrite existing assignments
                        </label>
                        <div class="form-text text-muted">
                            If checked, existing assignments will be replaced
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>
                    Cancel
                </button>
                <button type="button" class="btn btn-success" id="confirmBulkAssign">
                    <i class="fas fa-plus-circle me-1"></i>
                    Assign Permissions
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Sync Permissions Modal -->
<div class="modal fade" id="syncModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">
                    <i class="fas fa-sync me-2"></i>
                    Sync Role Permissions
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="syncForm">
                    <div class="mb-3">
                        <label for="syncRole" class="form-label">
                            <i class="fas fa-user-tag me-1"></i>
                            Select Role <span class="text-danger">*</span>
                        </label>
                        <select class="form-select bg-dark text-white border-secondary" id="syncRole" name="role_id" required>
                            <option value="">Choose a role...</option>
                            <!-- Will be populated dynamically -->
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="syncPermissions" class="form-label">
                            <i class="fas fa-key me-1"></i>
                            Select Permissions <span class="text-danger">*</span>
                        </label>
                        <select class="form-select bg-dark text-white border-secondary" id="syncPermissions" name="permissions[]" multiple required>
                            <!-- Will be populated dynamically -->
                        </select>
                        <div class="form-text text-muted">
                            Only these permissions will be assigned to the role (others will be removed)
                        </div>
                    </div>
                </form>
                
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> This will replace all current permissions for the selected role.
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>
                    Cancel
                </button>
                <button type="button" class="btn btn-info" id="confirmSync">
                    <i class="fas fa-sync me-1"></i>
                    Sync Permissions
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .matrix-table {
        font-size: 0.875rem;
    }
    
    .matrix-table th {
        background-color: #2a2a70;
        position: sticky;
        top: 0;
        z-index: 10;
        border: 1px solid #444;
        padding: 0.5rem 0.25rem;
        text-align: center;
        vertical-align: middle;
    }
    
    .matrix-table td {
        border: 1px solid #444;
        padding: 0.25rem;
        text-align: center;
        vertical-align: middle;
    }
    
    .permission-cell {
        cursor: pointer;
        transition: all 0.2s ease;
        min-width: 40px;
        height: 40px;
    }
    
    .permission-cell:hover {
        background-color: rgba(255, 122, 0, 0.2);
    }
    
    .permission-cell.assigned {
        background-color: rgba(40, 167, 69, 0.3);
    }
    
    .permission-cell.not-assigned {
        background-color: rgba(220, 53, 69, 0.3);
    }
    
    .permission-cell.system-protected {
        background-color: rgba(255, 193, 7, 0.3);
        cursor: not-allowed;
    }
    
    .permission-cell.inherited {
        background-color: rgba(108, 117, 125, 0.3);
    }
    
    .permission-name {
        writing-mode: vertical-rl;
        text-orientation: mixed;
        white-space: nowrap;
        max-width: 150px;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .role-name {
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .form-control:focus,
    .form-select:focus {
        background-color: #2a2a70;
        border-color: #FF7A00;
        box-shadow: 0 0 0 0.2rem rgba(255, 122, 0, 0.25);
        color: white;
    }
    
    .table-responsive {
        max-height: 70vh;
        overflow-y: auto;
    }
    
    .spinner-border {
        width: 3rem;
        height: 3rem;
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    let matrixData = {};
    let filteredData = {};
    
    // Initialize
    loadMatrix();
    loadFilters();
    
    // Filter events
    $('#roleFilter, #moduleFilter, #statusFilter').on('change', applyFilters);
    $('#searchInput').on('input', debounce(applyFilters, 300));
    
    // Matrix control events
    $('#selectAllBtn').on('click', () => toggleAllSelections(true));
    $('#deselectAllBtn').on('click', () => toggleAllSelections(false));
    $('#toggleSelectionBtn').on('click', toggleAllSelections);
    
    // Modal events
    $('#bulkAssignBtn').on('click', openBulkAssignModal);
    $('#syncPermissionsBtn').on('click', openSyncModal);
    $('#confirmBulkAssign').on('click', performBulkAssign);
    $('#confirmSync').on('click', performSync);
    
    function loadMatrix() {
        $.ajax({
            url: '{{ route("admin.role-permissions.matrix") }}',
            method: 'GET',
            success: function(response) {
                matrixData = response;
                filteredData = response;
                renderMatrix(response);
                updateCounts();
            },
            error: function() {
                $('#matrixContainer').html(`
                    <div class="alert alert-danger text-center">
                        <i class="fas fa-exclamation-circle fa-2x mb-3"></i>
                        <h5>Failed to Load Matrix</h5>
                        <p>Unable to load the role-permission assignment matrix. Please refresh the page.</p>
                        <button class="btn btn-outline-danger" onclick="location.reload()">
                            <i class="fas fa-redo me-1"></i>
                            Refresh Page
                        </button>
                    </div>
                `);
            }
        });
    }
    
    function loadFilters() {
        // Load roles for filter
        $.ajax({
            url: '{{ route("admin.roles.data") }}',
            method: 'GET',
            data: { all: true },
            success: function(response) {
                const roleFilter = $('#roleFilter');
                const bulkRoles = $('#bulkRoles');
                const syncRole = $('#syncRole');
                
                response.data.forEach(role => {
                    const option = `<option value="${role.id}">${role.display_name}</option>`;
                    roleFilter.append(option);
                    bulkRoles.append(option);
                    syncRole.append(option);
                });
            }
        });
        
        // Load permissions for bulk assign
        $.ajax({
            url: '{{ route("admin.permissions.data") }}',
            method: 'GET',
            data: { all: true },
            success: function(response) {
                const bulkPermissions = $('#bulkPermissions');
                const syncPermissions = $('#syncPermissions');
                
                response.data.forEach(permission => {
                    const option = `<option value="${permission.id}">${permission.display_name} (${permission.module})</option>`;
                    bulkPermissions.append(option);
                    syncPermissions.append(option);
                });
            }
        });
    }
    
    function renderMatrix(data) {
        if (!data.roles || !data.permissions) {
            $('#matrixContainer').html(`
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle fa-2x mb-3"></i>
                    <h5>No Data Available</h5>
                    <p>No roles or permissions found to display in the matrix.</p>
                </div>
            `);
            return;
        }
        
        let html = '<table class="table table-dark table-bordered matrix-table">';
        
        // Header row
        html += '<thead><tr><th class="text-start">Permission \\ Role</th>';
        data.roles.forEach(role => {
            html += `<th class="permission-name" title="${role.display_name}">`;
            html += `<div class="role-name">${role.display_name}</div>`;
            if (role.is_system) {
                html += '<span class="badge bg-warning text-dark mt-1">S</span>';
            }
            html += '</th>';
        });
        html += '</tr></thead>';
        
        // Body rows
        html += '<tbody>';
        
        // Group permissions by module
        const groupedPermissions = {};
        data.permissions.forEach(permission => {
            if (!groupedPermissions[permission.module]) {
                groupedPermissions[permission.module] = [];
            }
            groupedPermissions[permission.module].push(permission);
        });
        
        Object.keys(groupedPermissions).forEach(module => {
            // Module header
            html += `<tr class="table-secondary">`;
            html += `<td colspan="${data.roles.length + 1}" class="fw-bold">`;
            html += `<i class="fas fa-cube me-2"></i>${module.toUpperCase()}`;
            html += '</td></tr>';
            
            // Module permissions
            groupedPermissions[module].forEach(permission => {
                html += '<tr>';
                html += `<td class="text-start">`;
                html += `<div class="d-flex align-items-center">`;
                html += `<span class="me-2">${permission.display_name}</span>`;
                if (permission.is_system) {
                    html += '<span class="badge bg-warning text-dark">System</span>';
                }
                html += '</div>';
                html += `<small class="text-muted">${permission.name}</small>`;
                html += '</td>';
                
                data.roles.forEach(role => {
                    const hasPermission = data.assignments[role.id] && data.assignments[role.id].includes(permission.id);
                    const isSystemProtected = role.is_system && permission.is_system;
                    const isInherited = false; // You can implement inheritance logic here
                    
                    let cellClass = 'permission-cell';
                    let icon = '';
                    let title = '';
                    
                    if (isSystemProtected) {
                        cellClass += ' system-protected';
                        icon = '<i class="fas fa-shield-alt text-warning"></i>';
                        title = 'System protected - cannot be modified';
                    } else if (hasPermission) {
                        cellClass += ' assigned';
                        icon = '<i class="fas fa-check-circle text-success"></i>';
                        title = 'Permission assigned';
                    } else {
                        cellClass += ' not-assigned';
                        icon = '<i class="fas fa-times-circle text-danger"></i>';
                        title = 'Permission not assigned';
                    }
                    
                    html += `<td class="${cellClass}" `;
                    html += `data-role-id="${role.id}" `;
                    html += `data-permission-id="${permission.id}" `;
                    html += `data-assigned="${hasPermission}" `;
                    html += `data-system-protected="${isSystemProtected}" `;
                    html += `title="${title}">`;
                    html += icon;
                    html += '</td>';
                });
                
                html += '</tr>';
            });
        });
        
        html += '</tbody></table>';
        
        $('#matrixContainer').html(html);
        
        // Bind click events
        $('.permission-cell:not(.system-protected)').on('click', function() {
            togglePermission($(this));
        });
        
        // Populate module filter
        const moduleFilter = $('#moduleFilter');
        moduleFilter.find('option:not(:first)').remove();
        Object.keys(groupedPermissions).forEach(module => {
            moduleFilter.append(`<option value="${module}">${module}</option>`);
        });
    }
    
    function togglePermission(cell) {
        const roleId = cell.data('role-id');
        const permissionId = cell.data('permission-id');
        const isAssigned = cell.data('assigned');
        
        const action = isAssigned ? 'remove' : 'assign';
        const url = isAssigned ? 
            '{{ route("admin.role-permissions.remove", ["", ""]) }}'.replace(/\/+$/, '') + `/${roleId}/${permissionId}` :
            '{{ route("admin.role-permissions.assign", ["", ""]) }}'.replace(/\/+$/, '') + `/${roleId}/${permissionId}`;
        
        // Show loading state
        cell.html('<i class="fas fa-spinner fa-spin"></i>');
        
        $.ajax({
            url: url,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    // Update cell state
                    const newAssigned = !isAssigned;
                    cell.data('assigned', newAssigned);
                    
                    if (newAssigned) {
                        cell.removeClass('not-assigned').addClass('assigned');
                        cell.html('<i class="fas fa-check-circle text-success"></i>');
                        cell.attr('title', 'Permission assigned');
                    } else {
                        cell.removeClass('assigned').addClass('not-assigned');
                        cell.html('<i class="fas fa-times-circle text-danger"></i>');
                        cell.attr('title', 'Permission not assigned');
                    }
                    
                    updateCounts();
                    showAlert('success', response.message);
                } else {
                    showAlert('error', response.message || 'Failed to update permission');
                    // Restore original state
                    const icon = isAssigned ? 
                        '<i class="fas fa-check-circle text-success"></i>' :
                        '<i class="fas fa-times-circle text-danger"></i>';
                    cell.html(icon);
                }
            },
            error: function() {
                showAlert('error', 'Failed to update permission assignment');
                // Restore original state
                const icon = isAssigned ? 
                    '<i class="fas fa-check-circle text-success"></i>' :
                    '<i class="fas fa-times-circle text-danger"></i>';
                cell.html(icon);
            }
        });
    }
    
    function applyFilters() {
        const roleFilter = $('#roleFilter').val();
        const moduleFilter = $('#moduleFilter').val();
        const statusFilter = $('#statusFilter').val();
        const searchTerm = $('#searchInput').val().toLowerCase();
        
        // Filter logic would go here
        // For now, we'll just reload the matrix
        // In a real implementation, you'd filter the existing data
        loadMatrix();
    }
    
    function toggleAllSelections(select) {
        const cells = $('.permission-cell:not(.system-protected)');
        
        if (select === undefined) {
            // Toggle based on current state
            const assignedCount = $('.permission-cell.assigned:not(.system-protected)').length;
            const totalCount = cells.length;
            select = assignedCount < totalCount / 2;
        }
        
        cells.each(function() {
            const cell = $(this);
            const isAssigned = cell.data('assigned');
            
            if ((select && !isAssigned) || (!select && isAssigned)) {
                togglePermission(cell);
            }
        });
    }
    
    function updateCounts() {
        const totalCells = $('.permission-cell:not(.system-protected)').length;
        const assignedCells = $('.permission-cell.assigned:not(.system-protected)').length;
        
        $('#selectedCount').text(`${assignedCells} assigned`);
        $('#totalCount').text(`${totalCells} total`);
    }
    
    function openBulkAssignModal() {
        $('#bulkAssignModal').modal('show');
    }
    
    function openSyncModal() {
        $('#syncModal').modal('show');
    }
    
    function performBulkAssign() {
        const formData = {
            roles: $('#bulkRoles').val(),
            permissions: $('#bulkPermissions').val(),
            overwrite: $('#overwriteExisting').is(':checked'),
            _token: '{{ csrf_token() }}'
        };
        
        if (!formData.roles || !formData.permissions) {
            showAlert('error', 'Please select both roles and permissions');
            return;
        }
        
        $('#confirmBulkAssign').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Assigning...');
        
        $.ajax({
            url: '{{ route("admin.role-permissions.bulk-assign") }}',
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#bulkAssignModal').modal('hide');
                    loadMatrix();
                    showAlert('success', response.message);
                } else {
                    showAlert('error', response.message || 'Failed to assign permissions');
                }
            },
            error: function() {
                showAlert('error', 'Failed to assign permissions');
            },
            complete: function() {
                $('#confirmBulkAssign').prop('disabled', false).html('<i class="fas fa-plus-circle me-1"></i>Assign Permissions');
            }
        });
    }
    
    function performSync() {
        const formData = {
            role_id: $('#syncRole').val(),
            permissions: $('#syncPermissions').val(),
            _token: '{{ csrf_token() }}'
        };
        
        if (!formData.role_id || !formData.permissions) {
            showAlert('error', 'Please select both role and permissions');
            return;
        }
        
        $('#confirmSync').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Syncing...');
        
        $.ajax({
            url: '{{ route("admin.role-permissions.sync") }}',
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#syncModal').modal('hide');
                    loadMatrix();
                    showAlert('success', response.message);
                } else {
                    showAlert('error', response.message || 'Failed to sync permissions');
                }
            },
            error: function() {
                showAlert('error', 'Failed to sync permissions');
            },
            complete: function() {
                $('#confirmSync').prop('disabled', false).html('<i class="fas fa-sync me-1"></i>Sync Permissions');
            }
        });
    }
    
    function showAlert(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
        
        const alert = $(`
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                <i class="fas ${icon} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `);
        
        $('.card-body').prepend(alert);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            alert.alert('close');
        }, 5000);
    }
    
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
});
</script>
@endpush