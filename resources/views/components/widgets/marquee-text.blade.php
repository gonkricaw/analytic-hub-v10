{{--
    Marquee Text Widget Component
    
    Displays scrolling text announcements across the dashboard
    Features:
    - Configurable scroll speed and direction
    - Auto-refresh capability
    - Responsive design
    - Error handling
--}}

<x-widget-container 
    :title="$title ?? 'Announcements'" 
    :icon="$icon ?? 'fas fa-bullhorn'" 
    :id="$id ?? 'marquee-widget'" 
    :refresh-interval="$refreshInterval ?? 3600" 
    :refreshable="$refreshable ?? true" 
    :permission="$permission ?? null" 
    :size="$size ?? 'col-12'"
    :initial-loading="$initialLoading ?? false"
>
    <div class="marquee-container" data-widget="marquee">
        <div class="marquee-content">
            <div class="marquee-text" id="marquee-text">
                Loading announcements...
            </div>
        </div>
    </div>
    
    <style>
        .marquee-container {
            overflow: hidden;
            white-space: nowrap;
            background: linear-gradient(135deg, #FF7A00 0%, #0E0E44 100%);
            color: white;
            padding: 12px 0;
            border-radius: 8px;
            position: relative;
        }
        
        .marquee-content {
            display: inline-block;
            animation: marquee 30s linear infinite;
        }
        
        .marquee-text {
            display: inline-block;
            padding-left: 100%;
            font-weight: 500;
            font-size: 14px;
        }
        
        @keyframes marquee {
            0% {
                transform: translate3d(100%, 0, 0);
            }
            100% {
                transform: translate3d(-100%, 0, 0);
            }
        }
        
        .marquee-container:hover .marquee-content {
            animation-play-state: paused;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .marquee-text {
                font-size: 12px;
            }
            
            .marquee-container {
                padding: 8px 0;
            }
        }
        
        /* Loading state */
        .marquee-container.loading .marquee-text {
            opacity: 0.6;
            animation: pulse 1.5s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 0.6;
            }
            50% {
                opacity: 1;
            }
        }
        
        /* Error state */
        .marquee-container.error {
            background: #dc3545;
        }
        
        .marquee-container.error .marquee-text {
            color: white;
        }
    </style>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const marqueeWidget = {
                container: document.querySelector('[data-widget="marquee"]'),
                textElement: document.getElementById('marquee-text'),
                
                init() {
                    this.loadData();
                    
                    // Register with widget manager
                    if (window.WidgetManager) {
                        window.WidgetManager.registerWidget('marquee', {
                            refresh: () => this.loadData(),
                            container: this.container.closest('.widget-container')
                        });
                    }
                },
                
                async loadData() {
                    try {
                        this.setLoading(true);
                        
                        const response = await fetch('/api/widgets/marquee');
                        const result = await response.json();
                        
                        if (result.success) {
                            this.updateContent(result.data);
                            this.setError(false);
                        } else {
                            throw new Error(result.error || 'Failed to load marquee data');
                        }
                    } catch (error) {
                        console.error('Marquee widget error:', error);
                        this.setError(true, error.message);
                    } finally {
                        this.setLoading(false);
                    }
                },
                
                updateContent(data) {
                    this.textElement.textContent = data.text || 'No announcements available';
                    
                    // Update animation speed if provided
                    if (data.speed) {
                        const duration = Math.max(10, 100 - data.speed) + 's';
                        this.container.querySelector('.marquee-content').style.animationDuration = duration;
                    }
                },
                
                setLoading(loading) {
                    this.container.classList.toggle('loading', loading);
                },
                
                setError(hasError, message = '') {
                    this.container.classList.toggle('error', hasError);
                    if (hasError) {
                        this.textElement.textContent = message || 'Error loading announcements';
                    }
                }
            };
            
            marqueeWidget.init();
        });
    </script>
</x-widget-container>