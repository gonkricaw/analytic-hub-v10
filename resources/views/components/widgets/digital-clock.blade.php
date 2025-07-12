{{--
    Digital Clock Widget Component
    
    Displays real-time digital clock with date
    Features:
    - Real-time updates every second
    - Multiple time formats
    - Timezone display
    - Responsive design
    - Smooth transitions
--}}

<x-widget-container 
    :title="$title ?? 'Digital Clock'" 
    :icon="$icon ?? 'fas fa-clock'" 
    :id="$id ?? 'clock-widget'" 
    :refresh-interval="$refreshInterval ?? 1" 
    :refreshable="$refreshable ?? true" 
    :permission="$permission ?? null" 
    :size="$size ?? 'col-md-6 col-lg-4'"
    :initial-loading="$initialLoading ?? false"
>
    <div class="clock-container" data-widget="clock">
        <div class="time-display">
            <div class="time" id="clock-time">--:--:--</div>
            <div class="date" id="clock-date">Loading...</div>
        </div>
        <div class="timezone" id="clock-timezone">--</div>
    </div>
    
    <style>
        .clock-container {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .clock-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: shimmer 3s ease-in-out infinite;
        }
        
        @keyframes shimmer {
            0%, 100% {
                transform: rotate(0deg);
            }
            50% {
                transform: rotate(180deg);
            }
        }
        
        .time-display {
            position: relative;
            z-index: 2;
        }
        
        .time {
            font-size: 2.5rem;
            font-weight: 700;
            font-family: 'Courier New', monospace;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
        }
        
        .date {
            font-size: 1rem;
            font-weight: 500;
            opacity: 0.9;
            margin-bottom: 8px;
        }
        
        .timezone {
            font-size: 0.85rem;
            opacity: 0.7;
            position: relative;
            z-index: 2;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .clock-container {
                padding: 15px;
            }
            
            .time {
                font-size: 2rem;
            }
            
            .date {
                font-size: 0.9rem;
            }
            
            .timezone {
                font-size: 0.8rem;
            }
        }
        
        @media (max-width: 576px) {
            .time {
                font-size: 1.8rem;
            }
            
            .date {
                font-size: 0.85rem;
            }
        }
        
        /* Loading state */
        .clock-container.loading {
            opacity: 0.7;
        }
        
        .clock-container.loading .time {
            animation: pulse 1.5s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 0.7;
            }
            50% {
                opacity: 1;
            }
        }
        
        /* Error state */
        .clock-container.error {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }
        
        /* Smooth number transitions */
        .time.updating {
            transform: scale(1.05);
        }
    </style>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const clockWidget = {
                container: document.querySelector('[data-widget="clock"]'),
                timeElement: document.getElementById('clock-time'),
                dateElement: document.getElementById('clock-date'),
                timezoneElement: document.getElementById('clock-timezone'),
                intervalId: null,
                
                init() {
                    this.startClock();
                    
                    // Register with widget manager
                    if (window.WidgetManager) {
                        window.WidgetManager.registerWidget('clock', {
                            refresh: () => this.updateTime(),
                            container: this.container.closest('.widget-container')
                        });
                    }
                },
                
                startClock() {
                    // Update immediately
                    this.updateTime();
                    
                    // Update every second
                    this.intervalId = setInterval(() => {
                        this.updateTime();
                    }, 1000);
                },
                
                async updateTime() {
                    try {
                        // For real-time clock, we can use client-side time
                        // But we'll also sync with server periodically
                        const now = new Date();
                        
                        // Add updating animation
                        this.timeElement.classList.add('updating');
                        
                        setTimeout(() => {
                            this.timeElement.textContent = now.toLocaleTimeString('en-US', {
                                hour12: false,
                                hour: '2-digit',
                                minute: '2-digit',
                                second: '2-digit'
                            });
                            
                            this.dateElement.textContent = now.toLocaleDateString('en-US', {
                                weekday: 'long',
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric'
                            });
                            
                            this.timezoneElement.textContent = Intl.DateTimeFormat().resolvedOptions().timeZone;
                            
                            this.timeElement.classList.remove('updating');
                            this.setError(false);
                        }, 100);
                        
                        // Sync with server every minute
                        if (now.getSeconds() === 0) {
                            await this.syncWithServer();
                        }
                        
                    } catch (error) {
                        console.error('Clock widget error:', error);
                        this.setError(true, error.message);
                    }
                },
                
                async syncWithServer() {
                    try {
                        const response = await fetch('/api/widgets/clock');
                        const result = await response.json();
                        
                        if (result.success) {
                            // Optionally adjust for server time if needed
                            const serverTime = new Date(result.data.timestamp * 1000);
                            const clientTime = new Date();
                            const timeDiff = Math.abs(serverTime.getTime() - clientTime.getTime());
                            
                            // If difference is more than 5 seconds, show server time
                            if (timeDiff > 5000) {
                                this.timeElement.textContent = result.data.time;
                                this.dateElement.textContent = result.data.date;
                                this.timezoneElement.textContent = result.data.timezone;
                            }
                        }
                    } catch (error) {
                        // Silently fail for server sync, continue with client time
                        console.warn('Clock server sync failed:', error);
                    }
                },
                
                setError(hasError, message = '') {
                    this.container.classList.toggle('error', hasError);
                    if (hasError) {
                        this.timeElement.textContent = '--:--:--';
                        this.dateElement.textContent = message || 'Error loading time';
                        this.timezoneElement.textContent = '--';
                    }
                },
                
                destroy() {
                    if (this.intervalId) {
                        clearInterval(this.intervalId);
                    }
                }
            };
            
            clockWidget.init();
            
            // Cleanup on page unload
            window.addEventListener('beforeunload', () => {
                clockWidget.destroy();
            });
        });
    </script>
</x-widget-container>