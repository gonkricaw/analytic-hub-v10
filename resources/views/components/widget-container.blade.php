{{--
/**
 * Widget Container Component
 * 
 * A reusable container component for dashboard widgets that provides:
 * - Consistent styling and layout
 * - Loading states
 * - Error handling
 * - Refresh functionality
 * - Widget permissions
 * 
 * @param string $title - Widget title
 * @param string $icon - FontAwesome icon class
 * @param string $id - Unique widget identifier
 * @param int $refreshInterval - Auto-refresh interval in seconds (0 = no auto-refresh)
 * @param bool $canRefresh - Whether manual refresh is allowed
 * @param string $permission - Required permission to view widget
 * @param string $size - Widget size class (col-md-3, col-md-6, col-md-12)
 * @param bool $loading - Initial loading state
 */
--}}

@props([
    'title' => 'Widget',
    'icon' => 'fas fa-chart-bar',
    'id' => 'widget-' . uniqid(),
    'refreshInterval' => 0,
    'canRefresh' => true,
    'permission' => null,
    'size' => 'col-md-6',
    'loading' => false
])

@php
    // Check widget permission if specified
    $hasPermission = true;
    if ($permission && auth()->check()) {
        $hasPermission = auth()->user()->can($permission);
    }
@endphp

@if($hasPermission)
<div class="{{ $size }} mb-4">
    <div class="card widget-container h-100" data-widget-id="{{ $id }}" data-refresh-interval="{{ $refreshInterval }}">
        <!-- Widget Header -->
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="{{ $icon }} me-2"></i>
                {{ $title }}
            </h5>
            
            <div class="widget-controls">
                @if($canRefresh)
                    <button type="button" class="btn btn-sm btn-outline-secondary widget-refresh" 
                            data-widget-id="{{ $id }}" title="Refresh Widget">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                @endif
                
                <button type="button" class="btn btn-sm btn-outline-secondary widget-minimize ms-1" 
                        data-widget-id="{{ $id }}" title="Minimize Widget">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>
        
        <!-- Widget Body -->
        <div class="card-body widget-content" id="widget-content-{{ $id }}">
            <!-- Loading State -->
            <div class="widget-loading {{ $loading ? '' : 'd-none' }}" id="widget-loading-{{ $id }}">
                <div class="d-flex justify-content-center align-items-center" style="min-height: 150px;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <span class="ms-2">Loading widget data...</span>
                </div>
            </div>
            
            <!-- Error State -->
            <div class="widget-error d-none" id="widget-error-{{ $id }}">
                <div class="alert alert-danger text-center" role="alert">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <h6>Widget Error</h6>
                    <p class="mb-2">Failed to load widget data.</p>
                    @if($canRefresh)
                        <button type="button" class="btn btn-sm btn-outline-danger widget-retry" 
                                data-widget-id="{{ $id }}">
                            <i class="fas fa-redo me-1"></i>
                            Retry
                        </button>
                    @endif
                </div>
            </div>
            
            <!-- Widget Content -->
            <div class="widget-data {{ $loading ? 'd-none' : '' }}" id="widget-data-{{ $id }}">
                {{ $slot }}
            </div>
        </div>
        
        <!-- Widget Footer (optional) -->
        @if(isset($footer))
        <div class="card-footer text-muted">
            {{ $footer }}
        </div>
        @endif
        
        <!-- Last Updated Timestamp -->
        <div class="widget-timestamp text-muted small px-3 pb-2">
            <i class="fas fa-clock me-1"></i>
            Last updated: <span id="widget-timestamp-{{ $id }}">{{ now()->format('M d, Y H:i:s') }}</span>
        </div>
    </div>
</div>
@endif

@push('styles')
<style>
    .widget-container {
        background: var(--dark-bg);
        border: 1px solid #333;
        transition: all 0.3s ease;
    }
    
    .widget-container:hover {
        border-color: var(--accent-color);
        box-shadow: 0 4px 15px rgba(255, 122, 0, 0.1);
    }
    
    .widget-container.minimized .card-body {
        display: none;
    }
    
    .widget-controls .btn {
        border: none;
        padding: 0.25rem 0.5rem;
    }
    
    .widget-controls .btn:hover {
        background-color: var(--accent-color);
        color: white;
    }
    
    .widget-refresh.refreshing i {
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    .widget-loading {
        background: rgba(0, 0, 0, 0.05);
        border-radius: 0.375rem;
    }
    
    .widget-timestamp {
        font-size: 0.75rem;
        border-top: 1px solid #333;
    }
</style>
@endpush