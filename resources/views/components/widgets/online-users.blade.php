{{--
    Online Users Widget Component
    
    Displays real-time count of online users and recent activity
    Features:
    - Real-time user count
    - Recent activity feed
    - Auto-refresh every 30 seconds
    - Animated counters
    - Activity timeline
--}}

<x-widget-container 
    :title="$title ?? 'Online Users'" 
    :icon="$icon ?? 'fas fa-circle text-success'" 
    :id="$id ?? 'online-users-widget'" 
    :refresh-interval="$refreshInterval ?? 30" 
    :refreshable="$refreshable ?? true" 
    :permission="$permission ?? null" 
    :size="$size ?? 'col-lg-4'"
    :initial-loading="$initialLoading ?? true"
>
    <div class="online-users-container" data-widget="online-users">
        <div class="online-count-section">
            <div class="count-display">
                <div class="online-indicator">
                    <span class="pulse-dot"></span>
                </div>
                <div class="count-info">
                    <div class="count-number" id="online-count">0</div>
                    <div class="count-label">Users Online</div>
                </div>
            </div>
        </div>
        
        <div class="activity-section">
            <h6 class="activity-title">
                <i class="fas fa-clock me-2"></i>Recent Activity
            </h6>
            <div class="activity-list" id="activity-list">
                <!-- Activity items will be loaded here -->
            </div>
            <div class="no-activity" id="no-activity" style="display: none;">
                <i class="fas fa-moon text-muted"></i>
                <span class="text-muted">No recent activity</span>
            </div>
        </div>
    </div>
    
    <style>
        .online-users-container {
            padding: 20px;
        }
        
        .online-count-section {
            text-align: center;
            margin-bottom: 25px;
            padding: 20px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border-radius: 12px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .online-count-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: shimmer 4s ease-in-out infinite;
        }
        
        @keyframes shimmer {
            0%, 100% {
                transform: rotate(0deg);
            }
            50% {
                transform: rotate(180deg);
            }
        }
        
        .count-display {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            position: relative;
            z-index: 2;
        }
        
        .online-indicator {
            position: relative;
        }
        
        .pulse-dot {
            display: inline-block;
            width: 12px;
            height: 12px;
            background: #fff;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        .pulse-dot::before {
            content: '';
            position: absolute;
            top: -4px;
            left: -4px;
            right: -4px;
            bottom: -4px;
            border: 2px solid rgba(255, 255, 255, 0.6);
            border-radius: 50%;
            animation: pulse-ring 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.2);
            }
        }
        
        @keyframes pulse-ring {
            0% {
                transform: scale(1);
                opacity: 1;
            }
            100% {
                transform: scale(2);
                opacity: 0;
            }
        }
        
        .count-number {
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
            transition: all 0.5s ease;
        }
        
        .count-number.updating {
            transform: scale(1.1);
        }
        
        .count-label {
            font-size: 0.9rem;
            opacity: 0.9;
            font-weight: 500;
        }
        
        .activity-section {
            margin-top: 20px;
        }
        
        .activity-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .activity-list {
            max-height: 200px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #dee2e6 transparent;
        }
        
        .activity-list::-webkit-scrollbar {
            width: 4px;
        }
        
        .activity-list::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .activity-list::-webkit-scrollbar-thumb {
            background: #dee2e6;
            border-radius: 2px;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f1f3f4;
            animation: slideIn 0.3s ease;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-10px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .activity-dot {
            width: 8px;
            height: 8px;
            background: #28a745;
            border-radius: 50%;
            margin-right: 12px;
            flex-shrink: 0;
        }
        
        .activity-info {
            flex: 1;
            min-width: 0;
        }
        
        .activity-user {
            font-weight: 500;
            color: #2c3e50;
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .activity-time {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .no-activity {
            text-align: center;
            padding: 30px 20px;
            color: #6c757d;
        }
        
        .no-activity i {
            font-size: 1.5rem;
            margin-bottom: 8px;
            display: block;
        }
        
        /* Loading state */
        .online-users-container.loading .count-number {
            animation: pulse 1.5s ease-in-out infinite;
        }
        
        .loading-skeleton {
            display: flex;
            align-items: center;
            padding: 8px 0;
            margin-bottom: 8px;
        }
        
        .skeleton-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            margin-right: 12px;
        }
        
        .skeleton-line {
            height: 12px;
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: 4px;
            flex: 1;
        }
        
        @keyframes loading {
            0% {
                background-position: 200% 0;
            }
            100% {
                background-position: -200% 0;
            }
        }
        
        /* Error state */
        .online-users-container.error .online-count-section {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .online-users-container {
                padding: 15px;
            }
            
            .online-count-section {
                padding: 15px;
                margin-bottom: 20px;
            }
            
            .count-number {
                font-size: 2rem;
            }
            
            .count-label {
                font-size: 0.85rem;
            }
            
            .activity-title {
                font-size: 0.9rem;
            }
        }
    </style>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const onlineUsersWidget = {
                container: document.querySelector('[data-widget="online-users"]'),
                countElement: document.getElementById('online-count'),
                activityElement: document.getElementById('activity-list'),
                noActivityElement: document.getElementById('no-activity'),
                currentCount: 0,
                
                init() {
                    this.loadData();
                    
                    // Register with widget manager
                    if (window.WidgetManager) {
                        window.WidgetManager.registerWidget('online-users', {
                            refresh: () => this.loadData(),
                            container: this.container.closest('.widget-container')
                        });
                    }
                },
                
                async loadData() {
                    try {
                        this.setLoading(true);
                        
                        const response = await fetch('/api/widgets/online-users');
                        const result = await response.json();
                        
                        if (result.success) {
                            this.updateData(result.data);
                            this.setError(false);
                        } else {
                            throw new Error(result.error || 'Failed to load online users data');
                        }
                    } catch (error) {
                        console.error('Online users widget error:', error);
                        this.setError(true, error.message);
                    } finally {
                        this.setLoading(false);
                    }
                },
                
                updateData(data) {
                    // Animate count change
                    this.animateCount(data.count);
                    
                    // Update activity list
                    this.updateActivity(data.recent_activity || []);
                },
                
                animateCount(newCount) {
                    const startCount = this.currentCount;
                    const duration = 1000; // 1 second
                    const startTime = Date.now();
                    
                    this.countElement.classList.add('updating');
                    
                    const animate = () => {
                        const elapsed = Date.now() - startTime;
                        const progress = Math.min(elapsed / duration, 1);
                        
                        // Easing function
                        const easeOut = 1 - Math.pow(1 - progress, 3);
                        
                        const currentValue = Math.round(startCount + (newCount - startCount) * easeOut);
                        this.countElement.textContent = currentValue;
                        
                        if (progress < 1) {
                            requestAnimationFrame(animate);
                        } else {
                            this.currentCount = newCount;
                            this.countElement.classList.remove('updating');
                        }
                    };
                    
                    animate();
                },
                
                updateActivity(activities) {
                    if (!activities || activities.length === 0) {
                        this.activityElement.style.display = 'none';
                        this.noActivityElement.style.display = 'block';
                        return;
                    }
                    
                    this.activityElement.style.display = 'block';
                    this.noActivityElement.style.display = 'none';
                    
                    this.activityElement.innerHTML = activities.map(activity => `
                        <div class="activity-item">
                            <div class="activity-dot"></div>
                            <div class="activity-info">
                                <div class="activity-user">${this.escapeHtml(activity.name)}</div>
                                <div class="activity-time">${activity.time}</div>
                            </div>
                        </div>
                    `).join('');
                },
                
                setLoading(loading) {
                    this.container.classList.toggle('loading', loading);
                    
                    if (loading) {
                        // Show loading skeletons for activity
                        this.activityElement.innerHTML = Array(3).fill(0).map(() => `
                            <div class="loading-skeleton">
                                <div class="skeleton-dot"></div>
                                <div class="skeleton-line"></div>
                            </div>
                        `).join('');
                        this.noActivityElement.style.display = 'none';
                    }
                },
                
                setError(hasError, message = '') {
                    this.container.classList.toggle('error', hasError);
                    if (hasError) {
                        this.countElement.textContent = '--';
                        this.activityElement.innerHTML = `
                            <div class="text-center text-danger py-3">
                                <i class="fas fa-exclamation-triangle mb-2"></i>
                                <div>${message || 'Error loading data'}</div>
                            </div>
                        `;
                        this.noActivityElement.style.display = 'none';
                    }
                },
                
                escapeHtml(text) {
                    const div = document.createElement('div');
                    div.textContent = text;
                    return div.innerHTML;
                }
            };
            
            onlineUsersWidget.init();
        });
    </script>
</x-widget-container>