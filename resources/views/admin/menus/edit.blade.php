@extends('layouts.app')

@section('title', 'Edit Menu: ' . $menu->title)

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-white">Edit Menu</h1>
                    <p class="text-muted mb-0">Modify menu item: {{ $menu->title }}</p>
                </div>
                <div>
                    <a href="{{ route('admin.menus.show', $menu->id) }}" class="btn btn-info">
                        <i class="fas fa-eye"></i> View
                    </a>
                    <a href="{{ route('admin.menus.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Menus
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Form -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-edit"></i> Menu Information
                    </h5>
                </div>
                <div class="card-body">
                    <form id="menuForm" action="{{ route('admin.menus.update', $menu->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <!-- Parent Menu Selection -->
                        <div class="form-group mb-3">
                            <label for="parent_id" class="form-label">
                                <i class="fas fa-sitemap"></i> Parent Menu
                                <span class="text-muted">(Optional)</span>
                            </label>
                            <select name="parent_id" id="parent_id" class="form-control select2" data-placeholder="Select parent menu (leave empty for root menu)">
                                <option value="">-- Root Menu --</option>
                                @foreach($parentMenus as $parentMenu)
                                    <option value="{{ $parentMenu->id }}" 
                                            data-level="{{ $parentMenu->level }}" 
                                            {{ old('parent_id', $menu->parent_id) == $parentMenu->id ? 'selected' : '' }}
                                            {{ $parentMenu->id == $menu->id ? 'disabled' : '' }}>
                                        {{ str_repeat('└─ ', $parentMenu->level) }}{{ $parentMenu->title }}
                                        {{ $parentMenu->id == $menu->id ? ' (Cannot select self)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('parent_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Select a parent menu to create a submenu. Leave empty to create a root menu.
                                @if($menu->children->count() > 0)
                                    <br><strong>Note:</strong> This menu has {{ $menu->children->count() }} child(ren). Moving it will also move its children.
                                @endif
                            </small>
                        </div>

                        <!-- Menu Name -->
                        <div class="form-group mb-3">
                            <label for="name" class="form-label required">
                                <i class="fas fa-tag"></i> Menu Name
                            </label>
                            <input type="text" 
                                   name="name" 
                                   id="name" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   value="{{ old('name', $menu->name) }}" 
                                   placeholder="e.g., user-management" 
                                   required
                                   {{ $menu->is_system_menu ? 'readonly' : '' }}>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Unique identifier for the menu (lowercase, hyphens allowed). Used for routing and permissions.
                                @if($menu->is_system_menu)
                                    <br><strong>Note:</strong> This is a system menu. Name cannot be changed.
                                @endif
                            </small>
                        </div>

                        <!-- Menu Title -->
                        <div class="form-group mb-3">
                            <label for="title" class="form-label required">
                                <i class="fas fa-heading"></i> Menu Title
                            </label>
                            <input type="text" 
                                   name="title" 
                                   id="title" 
                                   class="form-control @error('title') is-invalid @enderror" 
                                   value="{{ old('title', $menu->title) }}" 
                                   placeholder="e.g., User Management" 
                                   required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Display title shown in the navigation menu.
                            </small>
                        </div>

                        <!-- Menu Description -->
                        <div class="form-group mb-3">
                            <label for="description" class="form-label">
                                <i class="fas fa-info-circle"></i> Description
                                <span class="text-muted">(Optional)</span>
                            </label>
                            <textarea name="description" 
                                      id="description" 
                                      class="form-control @error('description') is-invalid @enderror" 
                                      rows="3" 
                                      placeholder="Brief description of this menu item">{{ old('description', $menu->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Menu URL -->
                        <div class="form-group mb-3">
                            <label for="url" class="form-label">
                                <i class="fas fa-link"></i> URL
                                <span class="text-muted">(Optional for parent menus)</span>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fas fa-globe"></i>
                                    </span>
                                </div>
                                <input type="url" 
                                       name="url" 
                                       id="url" 
                                       class="form-control @error('url') is-invalid @enderror" 
                                       value="{{ old('url', $menu->url) }}" 
                                       placeholder="e.g., /admin/users or https://external-site.com">
                            </div>
                            @error('url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Internal route (e.g., /admin/users) or external URL (e.g., https://example.com).
                            </small>
                        </div>

                        <!-- Menu Icon -->
                        <div class="form-group mb-3">
                            <label for="icon" class="form-label">
                                <i class="fas fa-icons"></i> Icon
                                <span class="text-muted">(Optional)</span>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text" id="iconPreview">
                                        @if($menu->icon)
                                            <i class="{{ $menu->icon }}"></i>
                                        @else
                                            <i class="fas fa-circle" style="font-size: 0.8em;"></i>
                                        @endif
                                    </span>
                                </div>
                                <input type="text" 
                                       name="icon" 
                                       id="icon" 
                                       class="form-control @error('icon') is-invalid @enderror" 
                                       value="{{ old('icon', $menu->icon) }}" 
                                       placeholder="e.g., fas fa-users">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary" data-toggle="modal" data-target="#iconModal">
                                        <i class="fas fa-search"></i> Browse
                                    </button>
                                </div>
                            </div>
                            @error('icon')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                FontAwesome icon class (e.g., fas fa-users, far fa-file).
                            </small>
                        </div>

                        <!-- Sort Order -->
                        <div class="form-group mb-3">
                            <label for="sort_order" class="form-label">
                                <i class="fas fa-sort-numeric-up"></i> Sort Order
                            </label>
                            <input type="number" 
                                   name="sort_order" 
                                   id="sort_order" 
                                   class="form-control @error('sort_order') is-invalid @enderror" 
                                   value="{{ old('sort_order', $menu->sort_order) }}" 
                                   min="1" 
                                   max="999">
                            @error('sort_order')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Display order in the menu (1 = first, higher numbers appear later).
                            </small>
                        </div>

                        <!-- Menu Type -->
                        <div class="form-group mb-3">
                            <label for="type" class="form-label">
                                <i class="fas fa-list"></i> Menu Type
                            </label>
                            <select name="type" id="type" class="form-control @error('type') is-invalid @enderror">
                                <option value="link" {{ old('type', $menu->type) == 'link' ? 'selected' : '' }}>Link</option>
                                <option value="dropdown" {{ old('type', $menu->type) == 'dropdown' ? 'selected' : '' }}>Dropdown</option>
                                <option value="separator" {{ old('type', $menu->type) == 'separator' ? 'selected' : '' }}>Separator</option>
                                <option value="header" {{ old('type', $menu->type) == 'header' ? 'selected' : '' }}>Header</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Target -->
                        <div class="form-group mb-3">
                            <label for="target" class="form-label">
                                <i class="fas fa-external-link-alt"></i> Link Target
                            </label>
                            <select name="target" id="target" class="form-control @error('target') is-invalid @enderror">
                                <option value="_self" {{ old('target', $menu->target) == '_self' ? 'selected' : '' }}>Same Window (_self)</option>
                                <option value="_blank" {{ old('target', $menu->target) == '_blank' ? 'selected' : '' }}>New Window (_blank)</option>
                                <option value="_parent" {{ old('target', $menu->target) == '_parent' ? 'selected' : '' }}>Parent Frame (_parent)</option>
                                <option value="_top" {{ old('target', $menu->target) == '_top' ? 'selected' : '' }}>Top Frame (_top)</option>
                            </select>
                            @error('target')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Status Toggles -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" 
                                               name="is_active" 
                                               id="is_active" 
                                               class="custom-control-input" 
                                               value="1" 
                                               {{ old('is_active', $menu->is_active) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="is_active">
                                            <i class="fas fa-toggle-on"></i> Active
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">Menu will be visible in navigation</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" 
                                               name="is_external" 
                                               id="is_external" 
                                               class="custom-control-input" 
                                               value="1" 
                                               {{ old('is_external', $menu->is_external) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="is_external">
                                            <i class="fas fa-external-link-alt"></i> External Link
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">Link points to external website</small>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="form-group mb-0">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Menu
                                    </button>
                                    <button type="button" class="btn btn-success" onclick="saveAndContinue()">
                                        <i class="fas fa-check"></i> Save & Continue Editing
                                    </button>
                                </div>
                                <div>
                                    <button type="reset" class="btn btn-secondary">
                                        <i class="fas fa-undo"></i> Reset
                                    </button>
                                    <a href="{{ route('admin.menus.index') }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Permission & Role Settings -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-shield-alt"></i> Access Control
                    </h6>
                </div>
                <div class="card-body">
                    <!-- Required Permission -->
                    <div class="form-group mb-3">
                        <label for="required_permission_id" class="form-label">
                            <i class="fas fa-key"></i> Required Permission
                            <span class="text-muted">(Optional)</span>
                        </label>
                        <select name="required_permission_id" 
                                id="required_permission_id" 
                                class="form-control select2" 
                                data-placeholder="Select required permission"
                                form="menuForm">
                            <option value="">-- No Permission Required --</option>
                            @foreach($permissions as $permission)
                                <option value="{{ $permission->id }}" 
                                        {{ old('required_permission_id', $menu->required_permission_id) == $permission->id ? 'selected' : '' }}>
                                    {{ $permission->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">
                            Users must have this permission to see the menu.
                        </small>
                    </div>

                    <!-- Required Roles -->
                    <div class="form-group mb-0">
                        <label for="required_roles" class="form-label">
                            <i class="fas fa-users"></i> Required Roles
                            <span class="text-muted">(Optional)</span>
                        </label>
                        <select name="required_roles[]" 
                                id="required_roles" 
                                class="form-control select2" 
                                multiple 
                                data-placeholder="Select required roles"
                                form="menuForm">
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" 
                                        {{ in_array($role->name, old('required_roles', $menu->required_roles ?? [])) ? 'selected' : '' }}>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">
                            Users must have at least one of these roles to see the menu.
                        </small>
                    </div>
                </div>
            </div>

            <!-- Menu Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle"></i> Menu Information
                    </h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td><strong>ID:</strong></td>
                            <td>{{ $menu->id }}</td>
                        </tr>
                        <tr>
                            <td><strong>UUID:</strong></td>
                            <td><small>{{ $menu->uuid }}</small></td>
                        </tr>
                        <tr>
                            <td><strong>Level:</strong></td>
                            <td>{{ $menu->level }}</td>
                        </tr>
                        <tr>
                            <td><strong>Children:</strong></td>
                            <td>{{ $menu->children->count() }}</td>
                        </tr>
                        <tr>
                            <td><strong>System Menu:</strong></td>
                            <td>
                                @if($menu->is_system_menu)
                                    <span class="badge badge-info">Yes</span>
                                @else
                                    <span class="badge badge-secondary">No</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Created:</strong></td>
                            <td><small>{{ $menu->created_at->format('M d, Y H:i') }}</small></td>
                        </tr>
                        <tr>
                            <td><strong>Updated:</strong></td>
                            <td><small>{{ $menu->updated_at->format('M d, Y H:i') }}</small></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Menu Preview -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-eye"></i> Live Preview
                    </h6>
                </div>
                <div class="card-body">
                    <div id="menuPreview" class="menu-preview">
                        <div class="nav-item">
                            <a href="{{ $menu->url ?: '#' }}" class="nav-link">
                                @if($menu->icon)
                                    <i class="{{ $menu->icon }}"></i>
                                @else
                                    <i class="fas fa-circle" style="font-size: 0.8em;"></i>
                                @endif
                                <span>{{ $menu->title }}</span>
                            </a>
                        </div>
                    </div>
                    <small class="text-muted">Preview updates as you type</small>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-bolt"></i> Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-info btn-sm" onclick="previewMenu({{ $menu->id }})">
                            <i class="fas fa-eye"></i> Preview Menu
                        </button>
                        <button type="button" class="btn btn-warning btn-sm" onclick="duplicateMenu({{ $menu->id }})">
                            <i class="fas fa-copy"></i> Duplicate Menu
                        </button>
                        <a href="{{ route('admin.menus.create', ['parent_id' => $menu->id]) }}" class="btn btn-success btn-sm">
                            <i class="fas fa-plus"></i> Add Child Menu
                        </a>
                        @unless($menu->is_system_menu)
                            <button type="button" class="btn btn-danger btn-sm" onclick="deleteMenu({{ $menu->id }})">
                                <i class="fas fa-trash"></i> Delete Menu
                            </button>
                        @endunless
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Icon Selection Modal -->
<div class="modal fade" id="iconModal" tabindex="-1" role="dialog" aria-labelledby="iconModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="iconModalLabel">
                    <i class="fas fa-icons"></i> Select Icon
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12 mb-3">
                        <input type="text" id="iconSearch" class="form-control" placeholder="Search icons...">
                    </div>
                </div>
                <div class="row" id="iconGrid">
                    <!-- Icons will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
.required::after {
    content: ' *';
    color: #dc3545;
}

.menu-preview {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 1rem;
}

.menu-preview .nav-link {
    color: #495057;
    padding: 0.5rem 0.75rem;
    border-radius: 0.25rem;
    text-decoration: none;
    display: flex;
    align-items: center;
}

.menu-preview .nav-link:hover {
    background-color: #e9ecef;
}

.menu-preview .nav-link i {
    margin-right: 0.5rem;
    width: 1rem;
    text-align: center;
}

.icon-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
    gap: 10px;
    max-height: 400px;
    overflow-y: auto;
}

.icon-item {
    text-align: center;
    padding: 10px;
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.2s;
}

.icon-item:hover {
    background-color: #e9ecef;
    border-color: #007bff;
}

.icon-item.selected {
    background-color: #007bff;
    color: white;
    border-color: #007bff;
}

.icon-item i {
    font-size: 1.5rem;
    margin-bottom: 5px;
}

.icon-item small {
    display: block;
    font-size: 0.7rem;
}

.select2-container {
    width: 100% !important;
}

.select2-container--default .select2-selection--single {
    height: calc(1.5em + 0.75rem + 2px);
    padding: 0.375rem 0.75rem;
    border: 1px solid #ced4da;
}

.select2-container--default .select2-selection--multiple {
    min-height: calc(1.5em + 0.75rem + 2px);
    padding: 0.375rem 0.75rem;
    border: 1px solid #ced4da;
}

.d-grid {
    display: grid;
}

.gap-2 {
    gap: 0.5rem;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%'
    });

    // Live preview updates
    function updatePreview() {
        var title = $('#title').val() || '{{ $menu->title }}';
        var icon = $('#icon').val() || '{{ $menu->icon }}' || 'fas fa-circle';
        var url = $('#url').val() || '{{ $menu->url }}' || '#';
        
        var previewHtml = `
            <div class="nav-item">
                <a href="${url}" class="nav-link">
                    <i class="${icon}" style="font-size: 0.8em;"></i>
                    <span>${title}</span>
                </a>
            </div>
        `;
        
        $('#menuPreview').html(previewHtml);
    }

    // Update icon preview
    function updateIconPreview() {
        var icon = $('#icon').val() || 'fas fa-circle';
        $('#iconPreview').html(`<i class="${icon}" style="font-size: 0.8em;"></i>`);
    }

    // Bind events
    $('#title, #icon, #url').on('input', updatePreview);
    $('#icon').on('input', updateIconPreview);

    // Load popular icons
    var popularIcons = [
        'fas fa-home', 'fas fa-users', 'fas fa-user', 'fas fa-cog', 'fas fa-chart-bar',
        'fas fa-file', 'fas fa-folder', 'fas fa-envelope', 'fas fa-phone', 'fas fa-calendar',
        'fas fa-search', 'fas fa-plus', 'fas fa-edit', 'fas fa-trash', 'fas fa-download',
        'fas fa-upload', 'fas fa-print', 'fas fa-share', 'fas fa-heart', 'fas fa-star',
        'fas fa-bookmark', 'fas fa-tag', 'fas fa-tags', 'fas fa-comment', 'fas fa-comments',
        'fas fa-bell', 'fas fa-lock', 'fas fa-unlock', 'fas fa-key', 'fas fa-shield-alt',
        'fas fa-database', 'fas fa-server', 'fas fa-cloud', 'fas fa-globe', 'fas fa-link',
        'fas fa-external-link-alt', 'fas fa-arrow-left', 'fas fa-arrow-right', 'fas fa-arrow-up', 'fas fa-arrow-down'
    ];

    function loadIcons() {
        var iconGrid = $('#iconGrid');
        iconGrid.empty();
        
        popularIcons.forEach(function(iconClass) {
            var iconName = iconClass.replace('fas fa-', '').replace('far fa-', '').replace('fab fa-', '');
            var iconHtml = `
                <div class="col-2 mb-2">
                    <div class="icon-item" data-icon="${iconClass}">
                        <i class="${iconClass}"></i>
                        <small>${iconName}</small>
                    </div>
                </div>
            `;
            iconGrid.append(iconHtml);
        });
    }

    // Load icons when modal opens
    $('#iconModal').on('shown.bs.modal', function() {
        loadIcons();
    });

    // Icon selection
    $(document).on('click', '.icon-item', function() {
        $('.icon-item').removeClass('selected');
        $(this).addClass('selected');
        var iconClass = $(this).data('icon');
        $('#icon').val(iconClass);
        updateIconPreview();
        updatePreview();
        $('#iconModal').modal('hide');
    });

    // Icon search
    $('#iconSearch').on('input', function() {
        var searchTerm = $(this).val().toLowerCase();
        $('.icon-item').each(function() {
            var iconName = $(this).find('small').text().toLowerCase();
            if (iconName.includes(searchTerm)) {
                $(this).parent().show();
            } else {
                $(this).parent().hide();
            }
        });
    });

    // Save and continue editing
    window.saveAndContinue = function() {
        var form = $('#menuForm');
        var action = form.attr('action');
        form.attr('action', action + '?continue=1');
        form.submit();
    };

    // Form validation
    $('#menuForm').on('submit', function(e) {
        var isValid = true;
        
        // Check required fields
        $('input[required], select[required]').each(function() {
            if (!$(this).val()) {
                $(this).addClass('is-invalid');
                isValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            toastr.error('Please fill in all required fields');
        }
    });

    // Quick action functions (from index page)
    window.previewMenu = function(menuId) {
        // Implementation from index page
        window.location.href = '{{ route("admin.menus.show", $menu->id) }}';
    };

    window.duplicateMenu = function(menuId) {
        if (confirm('Are you sure you want to duplicate this menu?')) {
            $.ajax({
                url: '/admin/menus/' + menuId + '/duplicate',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        setTimeout(function() {
                            window.location.href = '{{ route("admin.menus.index") }}';
                        }, 1500);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function() {
                    toastr.error('Failed to duplicate menu');
                }
            });
        }
    };

    window.deleteMenu = function(menuId) {
        if (confirm('Are you sure you want to delete this menu? This action cannot be undone.')) {
            $.ajax({
                url: '/admin/menus/' + menuId,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        setTimeout(function() {
                            window.location.href = '{{ route("admin.menus.index") }}';
                        }, 1500);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function() {
                    toastr.error('Failed to delete menu');
                }
            });
        }
    };

    // Initial preview update
    updatePreview();
});
</script>
@endpush