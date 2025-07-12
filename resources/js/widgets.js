/**
 * Dashboard Widgets JavaScript Module
 * 
 * Handles widget functionality including:
 * - Auto-refresh mechanisms
 * - Loading states
 * - Error handling
 * - Widget configuration
 * - Layout management
 */

class WidgetManager {
    constructor() {
        this.widgets = new Map();
        this.refreshIntervals = new Map();
        this.defaultSettings = {
            refreshInterval: 60, // seconds
            animationSpeed: 'normal',
            enableSounds: true,
            layout: 'default'
        };
        this.settings = { ...this.defaultSettings };
        
        this.init();
    }
    
    /**
     * Initialize widget manager
     */
    init() {
        this.loadSettings();
        this.bindEvents();
        this.initializeWidgets();
        this.setupAutoRefresh();
        
        console.log('Widget Manager initialized');
    }
    
    /**
     * Load widget settings from localStorage
     */
    loadSettings() {
        const saved = localStorage.getItem('dashboard_widget_settings');
        if (saved) {
            try {
                this.settings = { ...this.defaultSettings, ...JSON.parse(saved) };
            } catch (e) {
                console.warn('Failed to load widget settings:', e);
            }
        }
    }
    
    /**
     * Save widget settings to localStorage
     */
    saveSettings() {
        localStorage.setItem('dashboard_widget_settings', JSON.stringify(this.settings));
    }
    
    /**
     * Bind event handlers
     */
    bindEvents() {
        // Widget refresh buttons
        $(document).on('click', '.widget-refresh', (e) => {
            const widgetId = $(e.currentTarget).data('widget-id');
            this.refreshWidget(widgetId);
        });
        
        // Widget retry buttons
        $(document).on('click', '.widget-retry', (e) => {
            const widgetId = $(e.currentTarget).data('widget-id');
            this.retryWidget(widgetId);
        });
        
        // Widget minimize/maximize
        $(document).on('click', '.widget-minimize', (e) => {
            const widgetId = $(e.currentTarget).data('widget-id');
            this.toggleWidget(widgetId);
        });
        
        // Layout switcher
        $(document).on('click', '.layout-switch', (e) => {
            const layout = $(e.currentTarget).data('layout');
            this.changeLayout(layout);
        });
        
        // Refresh all widgets
        $(document).on('click', '.refresh-all-widgets', () => {
            this.refreshAllWidgets();
        });
        
        // Save widget settings
        $(document).on('click', '.save-widget-settings', () => {
            this.saveWidgetSettings();
        });
        
        // Window visibility change (pause/resume auto-refresh)
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.pauseAutoRefresh();
            } else {
                this.resumeAutoRefresh();
            }
        });
    }
    
    /**
     * Initialize all widgets on the page
     */
    initializeWidgets() {
        $('.widget-container').each((index, element) => {
            const $widget = $(element);
            const widgetId = $widget.data('widget-id');
            const refreshInterval = $widget.data('refresh-interval') || 0;
            
            this.registerWidget(widgetId, {
                element: $widget,
                refreshInterval: refreshInterval,
                lastRefresh: Date.now(),
                isLoading: false,
                hasError: false
            });
        });
        
        // Check if grid is empty
        this.checkEmptyGrid();
    }
    
    /**
     * Register a widget
     */
    registerWidget(widgetId, config) {
        this.widgets.set(widgetId, config);
        
        // Set up auto-refresh if enabled
        if (config.refreshInterval > 0) {
            this.setupWidgetAutoRefresh(widgetId);
        }
    }
    
    /**
     * Setup auto-refresh for all widgets
     */
    setupAutoRefresh() {
        this.widgets.forEach((widget, widgetId) => {
            if (widget.refreshInterval > 0) {
                this.setupWidgetAutoRefresh(widgetId);
            }
        });
    }
    
    /**
     * Setup auto-refresh for a specific widget
     */
    setupWidgetAutoRefresh(widgetId) {
        // Clear existing interval
        if (this.refreshIntervals.has(widgetId)) {
            clearInterval(this.refreshIntervals.get(widgetId));
        }
        
        const widget = this.widgets.get(widgetId);
        if (!widget || widget.refreshInterval <= 0) return;
        
        const interval = setInterval(() => {
            if (!document.hidden && !widget.isLoading) {
                this.refreshWidget(widgetId);
            }
        }, widget.refreshInterval * 1000);
        
        this.refreshIntervals.set(widgetId, interval);
    }
    
    /**
     * Refresh a specific widget
     */
    async refreshWidget(widgetId) {
        const widget = this.widgets.get(widgetId);
        if (!widget || widget.isLoading) return;
        
        try {
            this.setWidgetLoading(widgetId, true);
            
            // Get widget data endpoint
            const endpoint = this.getWidgetEndpoint(widgetId);
            if (!endpoint) {
                throw new Error('Widget endpoint not configured');
            }
            
            // Fetch widget data
            const response = await fetch(endpoint, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            
            // Update widget content
            this.updateWidgetContent(widgetId, data);
            this.setWidgetError(widgetId, false);
            
            // Update timestamp
            this.updateWidgetTimestamp(widgetId);
            
            // Update last refresh time
            widget.lastRefresh = Date.now();
            
        } catch (error) {
            console.error(`Failed to refresh widget ${widgetId}:`, error);
            this.setWidgetError(widgetId, true, error.message);
        } finally {
            this.setWidgetLoading(widgetId, false);
        }
    }
    
    /**
     * Get widget data endpoint
     */
    getWidgetEndpoint(widgetId) {
        const endpoints = {
            'digital-clock': '/api/widgets/clock',
            'login-activity': '/api/widgets/login-activity',
            'active-users': '/api/widgets/active-users',
            'online-users': '/api/widgets/online-users',
            'popular-content': '/api/widgets/popular-content',
            'latest-announcements': '/api/widgets/announcements',
            'new-users': '/api/widgets/new-users',
            'marquee-text': '/api/widgets/marquee',
            'image-banner': '/api/widgets/banner'
        };
        
        return endpoints[widgetId] || null;
    }
    
    /**
     * Update widget content
     */
    updateWidgetContent(widgetId, data) {
        const widget = this.widgets.get(widgetId);
        if (!widget) return;
        
        const $content = widget.element.find(`#widget-data-${widgetId}`);
        
        if (data.html) {
            // Direct HTML content
            $content.html(data.html);
        } else if (data.chart) {
            // Chart data
            this.updateWidgetChart(widgetId, data.chart);
        } else {
            // Custom update based on widget type
            this.updateWidgetByType(widgetId, data);
        }
        
        // Trigger custom event
        widget.element.trigger('widget:updated', [data]);
    }
    
    /**
     * Update widget chart
     */
    updateWidgetChart(widgetId, chartData) {
        const widget = this.widgets.get(widgetId);
        if (!widget) return;
        
        const canvas = widget.element.find('canvas')[0];
        if (!canvas) return;
        
        // Update existing chart or create new one
        if (widget.chart) {
            widget.chart.data = chartData;
            widget.chart.update('none'); // No animation for updates
        } else {
            // Create new chart (this should be handled by individual widget components)
            console.warn(`Chart not initialized for widget ${widgetId}`);
        }
    }
    
    /**
     * Update widget by type
     */
    updateWidgetByType(widgetId, data) {
        switch (widgetId) {
            case 'digital-clock':
                this.updateClockWidget(widgetId, data);
                break;
            case 'online-users':
                this.updateOnlineUsersWidget(widgetId, data);
                break;
            default:
                console.warn(`Unknown widget type: ${widgetId}`);
        }
    }
    
    /**
     * Update clock widget
     */
    updateClockWidget(widgetId, data) {
        const widget = this.widgets.get(widgetId);
        if (!widget) return;
        
        const $content = widget.element.find(`#widget-data-${widgetId}`);
        $content.find('.clock-time').text(data.time || '');
        $content.find('.clock-date').text(data.date || '');
    }
    
    /**
     * Update online users widget
     */
    updateOnlineUsersWidget(widgetId, data) {
        const widget = this.widgets.get(widgetId);
        if (!widget) return;
        
        const $content = widget.element.find(`#widget-data-${widgetId}`);
        $content.find('.online-count').text(data.count || 0);
    }
    
    /**
     * Set widget loading state
     */
    setWidgetLoading(widgetId, isLoading) {
        const widget = this.widgets.get(widgetId);
        if (!widget) return;
        
        widget.isLoading = isLoading;
        
        const $loading = widget.element.find(`#widget-loading-${widgetId}`);
        const $data = widget.element.find(`#widget-data-${widgetId}`);
        const $refresh = widget.element.find('.widget-refresh');
        
        if (isLoading) {
            $loading.removeClass('d-none');
            $data.addClass('d-none');
            $refresh.addClass('refreshing').prop('disabled', true);
        } else {
            $loading.addClass('d-none');
            $data.removeClass('d-none');
            $refresh.removeClass('refreshing').prop('disabled', false);
        }
    }
    
    /**
     * Set widget error state
     */
    setWidgetError(widgetId, hasError, message = '') {
        const widget = this.widgets.get(widgetId);
        if (!widget) return;
        
        widget.hasError = hasError;
        
        const $error = widget.element.find(`#widget-error-${widgetId}`);
        const $data = widget.element.find(`#widget-data-${widgetId}`);
        
        if (hasError) {
            $error.removeClass('d-none');
            $data.addClass('d-none');
            
            if (message) {
                $error.find('p').text(message);
            }
        } else {
            $error.addClass('d-none');
            $data.removeClass('d-none');
        }
    }
    
    /**
     * Update widget timestamp
     */
    updateWidgetTimestamp(widgetId) {
        const widget = this.widgets.get(widgetId);
        if (!widget) return;
        
        const now = new Date();
        const timestamp = now.toLocaleString();
        
        widget.element.find(`#widget-timestamp-${widgetId}`).text(timestamp);
    }
    
    /**
     * Toggle widget minimize/maximize
     */
    toggleWidget(widgetId) {
        const widget = this.widgets.get(widgetId);
        if (!widget) return;
        
        const $widget = widget.element;
        const $icon = $widget.find('.widget-minimize i');
        
        $widget.toggleClass('minimized');
        
        if ($widget.hasClass('minimized')) {
            $icon.removeClass('fa-minus').addClass('fa-plus');
        } else {
            $icon.removeClass('fa-plus').addClass('fa-minus');
        }
    }
    
    /**
     * Retry widget loading
     */
    retryWidget(widgetId) {
        this.setWidgetError(widgetId, false);
        this.refreshWidget(widgetId);
    }
    
    /**
     * Refresh all widgets
     */
    refreshAllWidgets() {
        this.widgets.forEach((widget, widgetId) => {
            if (!widget.isLoading) {
                setTimeout(() => this.refreshWidget(widgetId), Math.random() * 1000);
            }
        });
    }
    
    /**
     * Change grid layout
     */
    changeLayout(layout) {
        this.settings.layout = layout;
        this.saveSettings();
        
        // Update grid layout
        $('.widget-grid').attr('data-layout', layout);
        
        // Update active button
        $('.layout-switch').removeClass('active');
        $(`.layout-switch[data-layout="${layout}"]`).addClass('active');
        
        // Trigger layout change event
        $(document).trigger('widget:layout-changed', [layout]);
    }
    
    /**
     * Save widget settings from modal
     */
    saveWidgetSettings() {
        // Get settings from modal
        const refreshInterval = parseInt($('#refresh-interval').val()) || 0;
        const animationSpeed = $('#animation-speed').val() || 'normal';
        const enableSounds = $('#enable-sounds').is(':checked');
        
        // Update settings
        this.settings.refreshInterval = refreshInterval;
        this.settings.animationSpeed = animationSpeed;
        this.settings.enableSounds = enableSounds;
        
        // Save to localStorage
        this.saveSettings();
        
        // Update widget refresh intervals
        this.updateRefreshIntervals();
        
        // Close modal
        $('#widgetSettingsModal').modal('hide');
        
        // Show success message
        this.showNotification('Widget settings saved successfully!', 'success');
    }
    
    /**
     * Update refresh intervals for all widgets
     */
    updateRefreshIntervals() {
        this.widgets.forEach((widget, widgetId) => {
            if (this.settings.refreshInterval > 0) {
                widget.refreshInterval = this.settings.refreshInterval;
                this.setupWidgetAutoRefresh(widgetId);
            } else {
                // Disable auto-refresh
                if (this.refreshIntervals.has(widgetId)) {
                    clearInterval(this.refreshIntervals.get(widgetId));
                    this.refreshIntervals.delete(widgetId);
                }
            }
        });
    }
    
    /**
     * Pause auto-refresh (when page is hidden)
     */
    pauseAutoRefresh() {
        console.log('Pausing widget auto-refresh');
        // Auto-refresh is already paused by visibility check in intervals
    }
    
    /**
     * Resume auto-refresh (when page is visible)
     */
    resumeAutoRefresh() {
        console.log('Resuming widget auto-refresh');
        // Auto-refresh will resume automatically
    }
    
    /**
     * Check if grid is empty and show/hide empty state
     */
    checkEmptyGrid() {
        const hasVisibleWidgets = $('.widget-container:visible').length > 0;
        
        if (hasVisibleWidgets) {
            $('#widget-grid-empty').addClass('d-none');
            $('#widget-grid-container').removeClass('d-none');
        } else {
            $('#widget-grid-empty').removeClass('d-none');
            $('#widget-grid-container').addClass('d-none');
        }
    }
    
    /**
     * Show notification
     */
    showNotification(message, type = 'info') {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: type,
                title: message,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        } else {
            console.log(`${type.toUpperCase()}: ${message}`);
        }
    }
    
    /**
     * Destroy widget manager
     */
    destroy() {
        // Clear all intervals
        this.refreshIntervals.forEach(interval => clearInterval(interval));
        this.refreshIntervals.clear();
        
        // Clear widgets
        this.widgets.clear();
        
        console.log('Widget Manager destroyed');
    }
}

// Initialize widget manager when DOM is ready
$(document).ready(function() {
    window.widgetManager = new WidgetManager();
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = WidgetManager;
}