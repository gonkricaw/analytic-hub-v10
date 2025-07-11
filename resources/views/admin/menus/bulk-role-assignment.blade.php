@extends('layouts.admin')

@section('title', 'Bulk Menu Role Assignment')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-users-cog"></i> Bulk Menu Role Assignment
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.menus.index') }}">Menus</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Bulk Role Assignment</li>
                </ol>
            </nav>
        </div>
        <div class="btn-group" role="group">
            <a href="{{ route('admin.menus.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Menus
            </a>
            <button type="button" class="btn btn-info" id="clearAllCacheBtn">
                <i class="fas fa-sync-alt"></i> Clear All Cache
            </button>
        </div>
    </div>

    <!-- Bulk Assignment Form -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-tasks"></i> Bulk Role Assignment
                    </h6>
                </div>
                <div class="card-body">
                    <form id="bulkAssignmentForm">
                        @csrf
                        
                        <!-- Menu Selection -->
                        <div class="form-group">
                            <label for="menu_ids">Select Menus <span class="text-danger">*</span></label>
                            <div class="row">
                                <div class="col-md-8">
                                    <select name="menu_ids[]" id="menu_ids" class="form-control select2" multiple required>
                                        @foreach($menus as $menu)
                                            <option value="{{ $menu->id }}">
                                                {{ str_repeat('— ', $menu->level) }}{{ $menu->title }}
                                                @if(!$menu->is_active)
                                                    (Inactive)
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted">
                                        Select one or more menus for bulk assignment.
                                    </small>
                                </div>
                                <div class="col-md-4">
                                    <div class="btn-group-vertical w-100" role="group">
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="selectAllMenus">
                                            <i class="fas fa-check-double"></i> Select All
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="selectActiveMenus">
                                            <i class="fas fa-check"></i> Active Only
                                        </button>
                                        <button type="button" class="btn btn-outline-warning btn-sm" id="clearMenuSelection">
                                            <i class="fas fa-times"></i> Clear
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Role Selection -->
                        <div class="form-group">
                            <label for="roles">Select Roles <span class="text-danger">*</span></label>
                            <div class="row">
                                <div class="col-md-8">
                                    <select name="roles[]" id="roles" class="form-control select2" multiple required>
                                        @foreach($roles as $role)
                                            <option value="{{ $role->id }}">
                                                {{ $role->name }}
                                                @if($role->description)
                                                    - {{ $role->description }}
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted">
                                        Select one or more roles to assign or remove.
                                    </small>
                                </div>
                                <div class="col-md-4">
                                    <div class="btn-group-vertical w-100" role="group">
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="selectAllRoles">
                                            <i class="fas fa-check-double"></i> Select All
                                        </button>
                                        <button type="button" class="btn btn-outline-warning btn-sm" id="clearRoleSelection">
                                            <i class="fas fa-times"></i> Clear
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Type -->
                        <div class="form-group">
                            <label for="action">Action <span class="text-danger">*</span></label>
                            <div class="row">
                                <div class="col-md-6">
                                    <select name="action" id="action" class="form-control" required>
                                        <option value="assign">Assign Roles</option>
                                        <option value="remove">Remove Roles</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <select name="access_type" id="access_type" class="form-control">
                                        <option value="view">View Only</option>
                                        <option value="edit">Edit Access</option>
                                        <option value="full">Full Access</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Visibility Options -->
                        <div class="form-group" id="visibilityOptions">
                            <label class="font-weight-bold">Visibility Options</label>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="is_visible" name="is_visible" value="1" checked>
                                        <label class="custom-control-label" for="is_visible">
                                            Visible to Role
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="show_in_navigation" name="show_in_navigation" value="1" checked>
                                        <label class="custom-control-label" for="show_in_navigation">
                                            Show in Navigation
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="show_children" name="show_children" value="1" checked>
                                        <label class="custom-control-label" for="show_children">
                                            Show Children
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-cogs"></i> Execute Bulk Assignment
                            </button>
                            <button type="button" class="btn btn-secondary btn-lg" id="resetForm">
                                <i class="fas fa-undo"></i> Reset Form
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Preview & Summary -->
        <div class="col-lg-4">
            <!-- Selection Summary -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-info-circle"></i> Selection Summary
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-right">
                                <h4 class="text-primary" id="selectedMenusCount">0</h4>
                                <small class="text-muted">Menus Selected</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h4 class="text-success" id="selectedRolesCount">0</h4>
                            <small class="text-muted">Roles Selected</small>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <h5 class="text-info" id="totalOperations">0</h5>
                        <small class="text-muted">Total Operations</small>
                    </div>
                </div>
            </div>

            <!-- Quick Presets -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-magic"></i> Quick Presets
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-success btn-sm preset-btn" data-preset="admin-all">
                            <i class="fas fa-crown"></i> Admin Access to All
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm preset-btn" data-preset="user-navigation">
                            <i class="fas fa-compass"></i> User Navigation Only
                        </button>
                        <button type="button" class="btn btn-outline-warning btn-sm preset-btn" data-preset="remove-all">
                            <i class="fas fa-eraser"></i> Remove All Assignments
                        </button>
                    </div>
                </div>
            </div>

            <!-- Recent Operations -->
            <div class="card">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-history"></i> Tips
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas fa-lightbulb text-warning"></i>
                            <small>Use "Assign" to add roles without removing existing ones.</small>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-lightbulb text-warning"></i>
                            <small>"Remove" will only detach the selected roles.</small>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-lightbulb text-warning"></i>
                            <small>Cache is automatically cleared after operations.</small>
                        </li>
                        <li>
                            <i class="fas fa-lightbulb text-warning"></i>
                            <small>Inactive menus can still have role assignments.</small>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="{{ asset('vendor/select2/css/select2.min.css') }}" rel="stylesheet">
<link href="{{ asset('vendor/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}" rel="stylesheet">
<style>
.select2-container--bootstrap4 .select2-selection--multiple {
    min-height: calc(1.5em + 0.75rem + 2px);
}

.preset-btn {
    margin-bottom: 5px;
}

#visibilityOptions {
    transition: opacity 0.3s ease;
}

#visibilityOptions.disabled {
    opacity: 0.5;
    pointer-events: none;
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('vendor/select2/js/select2.min.js') }}"></script>
<script>
$(document).ready(function() {
    // Initialize Select2
    $('#menu_ids, #roles').select2({
        theme: 'bootstrap4',
        placeholder: function() {
            return $(this).data('placeholder');
        },
        allowClear: true
    });

    $('#menu_ids').attr('data-placeholder', 'Select menus...');
    $('#roles').attr('data-placeholder', 'Select roles...');

    // Update counters
    function updateCounters() {
        const menuCount = $('#menu_ids').val() ? $('#menu_ids').val().length : 0;
        const roleCount = $('#roles').val() ? $('#roles').val().length : 0;
        const totalOps = menuCount * roleCount;

        $('#selectedMenusCount').text(menuCount);
        $('#selectedRolesCount').text(roleCount);
        $('#totalOperations').text(totalOps);
    }

    // Update visibility options based on action
    function updateVisibilityOptions() {
        const action = $('#action').val();
        if (action === 'remove') {
            $('#visibilityOptions').addClass('disabled');
            $('#access_type').prop('disabled', true);
        } else {
            $('#visibilityOptions').removeClass('disabled');
            $('#access_type').prop('disabled', false);
        }
    }

    // Event listeners
    $('#menu_ids, #roles').on('change', updateCounters);
    $('#action').on('change', updateVisibilityOptions);

    // Selection helpers
    $('#selectAllMenus').on('click', function() {
        $('#menu_ids option').prop('selected', true);
        $('#menu_ids').trigger('change');
    });

    $('#selectActiveMenus').on('click', function() {
        $('#menu_ids option').each(function() {
            const text = $(this).text();
            $(this).prop('selected', !text.includes('(Inactive)'));
        });
        $('#menu_ids').trigger('change');
    });

    $('#clearMenuSelection').on('click', function() {
        $('#menu_ids').val([]).trigger('change');
    });

    $('#selectAllRoles').on('click', function() {
        $('#roles option').prop('selected', true);
        $('#roles').trigger('change');
    });

    $('#clearRoleSelection').on('click', function() {
        $('#roles').val([]).trigger('change');
    });

    // Preset configurations
    $('.preset-btn').on('click', function() {
        const preset = $(this).data('preset');
        
        switch(preset) {
            case 'admin-all':
                $('#menu_ids option').prop('selected', true);
                $('#roles option').filter(function() {
                    return $(this).text().toLowerCase().includes('admin');
                }).prop('selected', true);
                $('#action').val('assign');
                $('#access_type').val('full');
                $('#is_visible, #show_in_navigation, #show_children').prop('checked', true);
                break;
                
            case 'user-navigation':
                $('#menu_ids option').filter(function() {
                    const text = $(this).text();
                    return !text.includes('(Inactive)') && !text.toLowerCase().includes('admin');
                }).prop('selected', true);
                $('#roles option').filter(function() {
                    return $(this).text().toLowerCase().includes('user');
                }).prop('selected', true);
                $('#action').val('assign');
                $('#access_type').val('view');
                $('#is_visible, #show_in_navigation').prop('checked', true);
                $('#show_children').prop('checked', false);
                break;
                
            case 'remove-all':
                $('#menu_ids option').prop('selected', true);
                $('#roles option').prop('selected', true);
                $('#action').val('remove');
                break;
        }
        
        $('#menu_ids, #roles').trigger('change');
        updateVisibilityOptions();
    });

    // Form submission
    $('#bulkAssignmentForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            menu_ids: $('#menu_ids').val(),
            roles: $('#roles').val(),
            action: $('#action').val(),
            access_type: $('#access_type').val(),
            is_visible: $('#is_visible').is(':checked'),
            show_in_navigation: $('#show_in_navigation').is(':checked'),
            show_children: $('#show_children').is(':checked'),
            _token: $('input[name="_token"]').val()
        };

        // Validation
        if (!formData.menu_ids || formData.menu_ids.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Menus Selected',
                text: 'Please select at least one menu.'
            });
            return;
        }

        if (!formData.roles || formData.roles.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Roles Selected',
                text: 'Please select at least one role.'
            });
            return;
        }

        // Confirmation
        const actionText = formData.action === 'assign' ? 'assign' : 'remove';
        const totalOps = formData.menu_ids.length * formData.roles.length;
        
        Swal.fire({
            title: `${actionText.charAt(0).toUpperCase() + actionText.slice(1)} Roles?`,
            text: `This will ${actionText} ${formData.roles.length} role(s) to/from ${formData.menu_ids.length} menu(s) (${totalOps} operations).`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: `Yes, ${actionText}!`
        }).then((result) => {
            if (result.isConfirmed) {
                performBulkAssignment(formData);
            }
        });
    });

    // Perform bulk assignment
    function performBulkAssignment(formData) {
        $.ajax({
            url: '{{ route("admin.menus.bulk-role-assignment") }}',
            method: 'POST',
            data: formData,
            beforeSend: function() {
                $('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        html: `
                            <p>${response.message}</p>
                            <small class="text-muted">
                                Affected: ${response.affected_menus} menu(s), ${response.affected_roles} role(s)
                            </small>
                        `,
                        timer: 3000,
                        showConfirmButton: false
                    });
                    
                    // Reset form
                    $('#resetForm').click();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.message
                    });
                }
            },
            error: function(xhr) {
                let message = 'An error occurred during bulk assignment.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: message
                });
            },
            complete: function() {
                $('button[type="submit"]').prop('disabled', false).html('<i class="fas fa-cogs"></i> Execute Bulk Assignment');
            }
        });
    }

    // Reset form
    $('#resetForm').on('click', function() {
        $('#menu_ids, #roles').val([]).trigger('change');
        $('#action').val('assign');
        $('#access_type').val('view');
        $('#is_visible, #show_in_navigation, #show_children').prop('checked', true);
        updateVisibilityOptions();
    });

    // Clear all cache
    $('#clearAllCacheBtn').on('click', function() {
        $.ajax({
            url: '{{ route("admin.menus.clear-role-cache") }}',
            method: 'GET',
            beforeSend: function() {
                $('#clearAllCacheBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Clearing...');
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Cache Cleared!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Failed to clear cache.'
                });
            },
            complete: function() {
                $('#clearAllCacheBtn').prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Clear All Cache');
            }
        });
    });

    // Initialize
    updateCounters();
    updateVisibilityOptions();
});
</script>
@endpush