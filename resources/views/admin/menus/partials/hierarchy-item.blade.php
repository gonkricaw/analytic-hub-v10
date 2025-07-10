{{--
    Menu Hierarchy Item Partial
    Displays a single menu item with its children in a hierarchical tree structure
    
    @param Menu $menu - The menu item to display
    @param int $level - The current nesting level (0 = root)
--}}

<div class="menu-item menu-level-{{ $level }}" data-menu-id="{{ $menu->id }}">
    <div class="d-flex justify-content-between align-items-center py-2">
        <div class="menu-info d-flex align-items-center">
            {{-- Level indicator --}}
            @if($level > 0)
                <span class="text-muted me-2">
                    @for($i = 0; $i < $level; $i++)
                        @if($i == $level - 1)
                            ├─
                        @else
                            │&nbsp;&nbsp;
                        @endif
                    @endfor
                </span>
            @endif
            
            {{-- Sortable handle --}}
            <span class="sortable-handle me-2" style="cursor: move;" title="Drag to reorder">
                <i class="fas fa-grip-vertical text-muted"></i>
            </span>
            
            {{-- Menu icon --}}
            @if($menu->icon)
                <i class="{{ $menu->icon }} menu-icon"></i>
            @else
                <i class="fas fa-circle menu-icon" style="font-size: 0.5em;"></i>
            @endif
            
            {{-- Menu title and details --}}
            <div class="menu-details">
                <strong class="menu-title">{{ $menu->title }}</strong>
                <small class="text-muted d-block">
                    {{ $menu->name }}
                    @if($menu->url)
                        | <span class="text-info">{{ $menu->url }}</span>
                    @endif
                    @if($menu->is_external)
                        <i class="fas fa-external-link-alt text-warning" title="External Link"></i>
                    @endif
                </small>
                
                {{-- Menu badges --}}
                <div class="menu-badges mt-1">
                    {{-- Status badge --}}
                    @if($menu->is_active)
                        <span class="badge badge-success badge-sm">Active</span>
                    @else
                        <span class="badge badge-secondary badge-sm">Inactive</span>
                    @endif
                    
                    {{-- System menu badge --}}
                    @if($menu->is_system_menu)
                        <span class="badge badge-info badge-sm">System</span>
                    @endif
                    
                    {{-- Level badge --}}
                    <span class="badge badge-light badge-sm">Level {{ $menu->level }}</span>
                    
                    {{-- Order badge --}}
                    <span class="badge badge-outline-secondary badge-sm">Order: {{ $menu->sort_order }}</span>
                    
                    {{-- Children count --}}
                    @if($menu->children && $menu->children->count() > 0)
                        <span class="badge badge-primary badge-sm">
                            {{ $menu->children->count() }} 
                            {{ Str::plural('child', $menu->children->count()) }}
                        </span>
                    @endif
                    
                    {{-- Roles count --}}
                    @if($menu->roles && $menu->roles->count() > 0)
                        <span class="badge badge-warning badge-sm" title="{{ $menu->roles->pluck('name')->join(', ') }}">
                            {{ $menu->roles->count() }} 
                            {{ Str::plural('role', $menu->roles->count()) }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
        
        {{-- Action buttons --}}
        <div class="menu-actions">
            {{-- Quick toggle status --}}
            <button type="button" 
                    class="btn btn-sm {{ $menu->is_active ? 'btn-success' : 'btn-secondary' }}" 
                    onclick="toggleMenuStatus({{ $menu->id }})" 
                    title="{{ $menu->is_active ? 'Deactivate' : 'Activate' }} Menu">
                <i class="fas {{ $menu->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
            </button>
            
            {{-- Preview button --}}
            <button type="button" 
                    class="btn btn-sm btn-info" 
                    onclick="previewMenu({{ $menu->id }})" 
                    title="Preview Menu">
                <i class="fas fa-eye"></i>
            </button>
            
            {{-- Edit button --}}
            <a href="{{ route('admin.menus.edit', $menu->id) }}" 
               class="btn btn-sm btn-primary" 
               title="Edit Menu">
                <i class="fas fa-edit"></i>
            </a>
            
            {{-- Duplicate button --}}
            <button type="button" 
                    class="btn btn-sm btn-warning" 
                    onclick="duplicateMenu({{ $menu->id }})" 
                    title="Duplicate Menu">
                <i class="fas fa-copy"></i>
            </button>
            
            {{-- Delete button (only if not system menu) --}}
            @unless($menu->is_system_menu)
                <button type="button" 
                        class="btn btn-sm btn-danger" 
                        onclick="deleteMenu({{ $menu->id }})" 
                        title="Delete Menu">
                    <i class="fas fa-trash"></i>
                </button>
            @endunless
            
            {{-- Add child button --}}
            <a href="{{ route('admin.menus.create', ['parent_id' => $menu->id]) }}" 
               class="btn btn-sm btn-success" 
               title="Add Child Menu">
                <i class="fas fa-plus"></i>
            </a>
        </div>
    </div>
    
    {{-- Description (if available) --}}
    @if($menu->description)
        <div class="menu-description text-muted small" style="margin-left: {{ ($level * 20) + 40 }}px;">
            <i class="fas fa-info-circle"></i> {{ $menu->description }}
        </div>
    @endif
    
    {{-- Required permission info --}}
    @if($menu->required_permission_id)
        <div class="menu-permission text-warning small" style="margin-left: {{ ($level * 20) + 40 }}px;">
            <i class="fas fa-lock"></i> 
            Permission Required: {{ $menu->requiredPermission->name ?? 'Unknown Permission' }}
        </div>
    @endif
    
    {{-- Required roles info --}}
    @if($menu->required_roles && !empty($menu->required_roles))
        <div class="menu-roles text-info small" style="margin-left: {{ ($level * 20) + 40 }}px;">
            <i class="fas fa-users"></i> 
            Required Roles: {{ implode(', ', $menu->required_roles) }}
        </div>
    @endif
</div>

{{-- Render children recursively --}}
@if($menu->children && $menu->children->count() > 0)
    @foreach($menu->children->sortBy('sort_order') as $child)
        @include('admin.menus.partials.hierarchy-item', ['menu' => $child, 'level' => $level + 1])
    @endforeach
@endif

{{-- JavaScript for this component --}}
@once
@push('scripts')
<script>
/**
 * Toggle menu status (active/inactive)
 * @param {number} menuId - The ID of the menu to toggle
 */
function toggleMenuStatus(menuId) {
    $.ajax({
        url: '/admin/menus/' + menuId + '/toggle-status',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                // Reload the page to reflect changes
                location.reload();
            } else {
                toastr.error(response.message || 'Failed to toggle menu status');
            }
        },
        error: function(xhr) {
            var errorMessage = 'Failed to toggle menu status';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            toastr.error(errorMessage);
        }
    });
}

/**
 * Initialize sortable functionality for menu hierarchy
 */
$(document).ready(function() {
    // Make menu items sortable within their level
    $('.menu-hierarchy').sortable({
        items: '.menu-item',
        handle: '.sortable-handle',
        placeholder: 'ui-sortable-placeholder',
        tolerance: 'pointer',
        cursor: 'move',
        opacity: 0.8,
        helper: function(e, ui) {
            ui.children().each(function() {
                $(this).width($(this).width());
            });
            return ui;
        },
        start: function(event, ui) {
            ui.placeholder.height(ui.item.height());
        },
        update: function(event, ui) {
            var items = [];
            var currentLevel = null;
            var orderCounter = 1;
            
            $('.menu-item').each(function(index) {
                var menuId = $(this).data('menu-id');
                var level = $(this).hasClass('menu-level-0') ? 0 : 
                           $(this).hasClass('menu-level-1') ? 1 : 
                           $(this).hasClass('menu-level-2') ? 2 : 0;
                
                // Reset counter for new level
                if (currentLevel !== level) {
                    currentLevel = level;
                    orderCounter = 1;
                }
                
                items.push({
                    id: menuId,
                    sort_order: orderCounter++,
                    level: level
                });
            });
            
            // Send AJAX request to update order
            $.ajax({
                url: '{{ route("admin.menus.update-order") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    items: items
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message || 'Menu order updated successfully');
                        // Optionally reload to reflect changes
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        toastr.error(response.message || 'Failed to update menu order');
                        // Revert the change
                        $('.menu-hierarchy').sortable('cancel');
                    }
                },
                error: function(xhr) {
                    var errorMessage = 'Failed to update menu order';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    toastr.error(errorMessage);
                    // Revert the change
                    $('.menu-hierarchy').sortable('cancel');
                }
            });
        }
    });
    
    // Initialize tooltips for badges
    $('[title]').tooltip({
        placement: 'top',
        trigger: 'hover'
    });
});
</script>
@endpush
@endonce