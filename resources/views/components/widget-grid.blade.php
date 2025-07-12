{{--
/**
 * Widget Grid Layout Component
 * 
 * Provides a responsive grid system for organizing dashboard widgets with:
 * - Responsive breakpoints
 * - Drag and drop support (future enhancement)
 * - Grid configuration management
 * - Auto-layout optimization
 * 
 * @param string $layout - Grid layout type (default, compact, wide)
 * @param bool $sortable - Enable drag and drop sorting
 * @param string $class - Additional CSS classes
 */
--}}

@props([
    'layout' => 'default',
    'sortable' => false,
    'class' => ''
])

<div class="widget-grid {{ $class }}" data-layout="{{ $layout }}" {{ $sortable ? 'data-sortable=true' : '' }}>
    <div class="container-fluid">
        <!-- Grid Controls -->
        <div class="widget-grid-controls mb-3 d-flex justify-content-between align-items-center">
            <div class="grid-info">
                <span class="text-muted small">
                    <i class="fas fa-th me-1"></i>
                    Dashboard Layout: <span class="text-capitalize">{{ $layout }}</span>
                </span>
            </div>
            
            <div class="grid-actions">
                <!-- Layout Switcher -->
                <div class="btn-group btn-group-sm" role="group" aria-label="Layout options">
                    <button type="button" class="btn btn-outline-secondary layout-switch {{ $layout === 'compact' ? 'active' : '' }}" 
                            data-layout="compact" title="Compact Layout">
                        <i class="fas fa-th-large"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary layout-switch {{ $layout === 'default' ? 'active' : '' }}" 
                            data-layout="default" title="Default Layout">
                        <i class="fas fa-th"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary layout-switch {{ $layout === 'wide' ? 'active' : '' }}" 
                            data-layout="wide" title="Wide Layout">
                        <i class="fas fa-grip-horizontal"></i>
                    </button>
                </div>
                
                <!-- Refresh All Widgets -->
                <button type="button" class="btn btn-sm btn-outline-primary ms-2 refresh-all-widgets" 
                        title="Refresh All Widgets">
                    <i class="fas fa-sync-alt me-1"></i>
                    Refresh All
                </button>
                
                <!-- Widget Settings -->
                <button type="button" class="btn btn-sm btn-outline-secondary ms-1 widget-settings" 
                        title="Widget Settings" data-bs-toggle="modal" data-bs-target="#widgetSettingsModal">
                    <i class="fas fa-cog"></i>
                </button>
            </div>
        </div>
        
        <!-- Widget Grid Container -->
        <div class="row widget-grid-container" id="widget-grid-container">
            {{ $slot }}
        </div>
        
        <!-- Empty State -->
        <div class="widget-grid-empty d-none" id="widget-grid-empty">
            <div class="text-center py-5">
                <i class="fas fa-th-large fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No Widgets Available</h5>
                <p class="text-muted">Configure widgets to display dashboard data.</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#widgetSettingsModal">
                    <i class="fas fa-plus me-1"></i>
                    Add Widgets
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Widget Settings Modal -->
<div class="modal fade" id="widgetSettingsModal" tabindex="-1" aria-labelledby="widgetSettingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="widgetSettingsModalLabel">
                    <i class="fas fa-cog me-2"></i>
                    Widget Configuration
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Available Widgets</h6>
                        <div class="list-group available-widgets">
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-clock me-2"></i>
                                    Digital Clock
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="widget-clock" checked>
                                </div>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-chart-line me-2"></i>
                                    Login Activity Chart
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="widget-login-chart" checked>
                                </div>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-users me-2"></i>
                                    Top Active Users
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="widget-active-users" checked>
                                </div>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-eye me-2"></i>
                                    Popular Content
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="widget-popular-content" checked>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>Widget Settings</h6>
                        <div class="mb-3">
                            <label for="refresh-interval" class="form-label">Auto-refresh Interval</label>
                            <select class="form-select" id="refresh-interval">
                                <option value="0">Disabled</option>
                                <option value="30">30 seconds</option>
                                <option value="60" selected>1 minute</option>
                                <option value="300">5 minutes</option>
                                <option value="600">10 minutes</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="animation-speed" class="form-label">Animation Speed</label>
                            <select class="form-select" id="animation-speed">
                                <option value="fast">Fast</option>
                                <option value="normal" selected>Normal</option>
                                <option value="slow">Slow</option>
                            </select>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="enable-sounds" checked>
                            <label class="form-check-label" for="enable-sounds">
                                Enable notification sounds
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary save-widget-settings">
                    <i class="fas fa-save me-1"></i>
                    Save Settings
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .widget-grid {
        min-height: 400px;
    }
    
    .widget-grid-controls {
        background: var(--dark-bg);
        border: 1px solid #333;
        border-radius: 0.375rem;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    
    .layout-switch.active {
        background-color: var(--accent-color);
        border-color: var(--accent-color);
        color: white;
    }
    
    .widget-grid-container.sortable {
        min-height: 200px;
    }
    
    .widget-grid-container.sortable .widget-container {
        cursor: move;
    }
    
    .widget-grid-container.sortable .widget-container:hover {
        opacity: 0.8;
    }
    
    .widget-grid-empty {
        background: var(--dark-bg);
        border: 2px dashed #333;
        border-radius: 0.375rem;
        margin: 2rem 0;
    }
    
    /* Layout Variations */
    .widget-grid[data-layout="compact"] .widget-container {
        margin-bottom: 1rem;
    }
    
    .widget-grid[data-layout="compact"] .card-body {
        padding: 1rem;
    }
    
    .widget-grid[data-layout="wide"] .widget-container {
        margin-bottom: 2rem;
    }
    
    .widget-grid[data-layout="wide"] .card-body {
        padding: 2rem;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .widget-grid-controls {
            flex-direction: column;
            gap: 1rem;
        }
        
        .grid-actions {
            width: 100%;
            justify-content: center;
        }
    }
</style>
@endpush