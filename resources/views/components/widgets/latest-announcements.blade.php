{{--
    Latest Announcements Widget Component
    
    Displays recent announcements and notifications
    Features:
    - Priority-based styling
    - Action URLs for clickable announcements
    - Time-based display
    - Responsive design
    - Auto-refresh capability
--}}

<x-widget-container 
    :title="$title ?? 'Latest Announcements'" 
    :icon="$icon ?? 'fas fa-bullhorn'" 
    :id="$id ?? 'announcements-widget'" 
    :refresh-interval="$refreshInterval ?? 120" 
    :refreshable="$refreshable ?? true" 
    :permission="$permission ?? null" 
    :size="$size ?? 'col-lg-6'"
    :initial-loading="$initialLoading ?? true"
>
    <div class="announcements-container" data-widget="announcements">
        <div class="announcements-list" id="announcements-list">
            <!-- Announcements will be loaded here -->
        </div>
        <div class="empty-state" id="announcements-empty" style="display: none;">
            <i class="fas fa-bell-slash text-muted mb-2"></i>
            <p class="text-muted mb-0">No announcements available</p>
        </div>
    </div>
    
    <style>
        .announcements-container {
            padding: 15px;
        }
        
        .announcements-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            max-height: 400px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #dee2e6 transparent;
        }
        
        .announcements-list::-webkit-scrollbar {
            width: 4px;
        }
        
        .announcements-list::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .announcements-list::-webkit-scrollbar-thumb {
            background: #dee2e6;
            border-radius: 2px;
        }
        
        .announcement-item {
            padding: 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
            border-left: 4px solid #dee2e6;
            background: #f8f9fa;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .announcement-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .announcement-item.clickable:hover {
            background: #e9ecef;
        }
        
        /* Priority-based styling */
        .announcement-item.priority-high {
            border-left-color: #dc3545;
            background: linear-gradient(135deg, #fff5f5 0%, #f8f9fa 100%);
        }
        
        .announcement-item.priority-medium {
            border-left-color: #ffc107;
            background: linear-gradient(135deg, #fffbf0 0%, #f8f9fa 100%);
        }
        
        .announcement-item.priority-low {
            border-left-color: #28a745;
            background: linear-gradient(135deg, #f0f8f0 0%, #f8f9fa 100%);
        }
        
        .announcement-item.priority-info {
            border-left-color: #17a2b8;
            background: linear-gradient(135deg, #f0f8ff 0%, #f8f9fa 100%);
        }
        
        .announcement-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .announcement-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 4px;
            line-height: 1.3;
            flex: 1;
            margin-right: 10px;
        }
        
        .priority-badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            flex-shrink: 0;
        }
        
        .priority-badge.high {
            background: #dc3545;
            color: white;
        }
        
        .priority-badge.medium {
            background: #ffc107;
            color: #212529;
        }
        
        .priority-badge.low {
            background: #28a745;
            color: white;
        }
        
        .priority-badge.info {
            background: #17a2b8;
            color: white;
        }
        
        .announcement-message {
            color: #495057;
            font-size: 0.9rem;
            line-height: 1.4;
            margin-bottom: 10px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .announcement-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .announcement-time {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .announcement-action {
            display: flex;
            align-items: center;
            gap: 4px;
            color: #FF7A00;
            font-weight: 500;
            text-decoration: none;
        }
        
        .announcement-action:hover {
            color: #e66a00;
            text-decoration: none;
        }
        
        .new-indicator {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 8px;
            height: 8px;
            background: #dc3545;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.7;
                transform: scale(1.2);
            }
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }
        
        .empty-state i {
            font-size: 2rem;
        }
        
        /* Loading state */
        .announcements-container.loading .announcements-list {
            opacity: 0.6;
        }
        
        .loading-skeleton {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 12px;
            border-left: 4px solid #dee2e6;
        }
        
        .skeleton-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .skeleton-line {
            height: 12px;
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: 4px;
            margin-bottom: 8px;
        }
        
        .skeleton-line.title {
            width: 70%;
            height: 16px;
        }
        
        .skeleton-line.badge {
            width: 60px;
            height: 20px;
        }
        
        .skeleton-line.short {
            width: 80%;
        }
        
        .skeleton-line.shorter {
            width: 40%;
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
        .announcements-container.error {
            text-align: center;
            padding: 40px 20px;
            color: #dc3545;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .announcement-item {
                padding: 12px;
            }
            
            .announcement-title {
                font-size: 0.9rem;
            }
            
            .announcement-message {
                font-size: 0.85rem;
            }
            
            .announcement-footer {
                font-size: 0.75rem;
            }
            
            .priority-badge {
                font-size: 0.65rem;
                padding: 1px 6px;
            }
        }
        
        @media (max-width: 576px) {
            .announcement-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .announcement-footer {
                flex-direction: column;
                align-items: flex-start;
                gap: 4px;
            }
        }
    </style>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const announcementsWidget = {
                container: document.querySelector('[data-widget="announcements"]'),
                listElement: document.getElementById('announcements-list'),
                emptyElement: document.getElementById('announcements-empty'),
                
                init() {
                    this.loadData();
                    
                    // Register with widget manager
                    if (window.WidgetManager) {
                        window.WidgetManager.registerWidget('announcements', {
                            refresh: () => this.loadData(),
                            container: this.container.closest('.widget-container')
                        });
                    }
                },
                
                async loadData() {
                    try {
                        this.setLoading(true);
                        
                        const response = await fetch('/api/widgets/announcements');
                        const result = await response.json();
                        
                        if (result.success) {
                            this.updateAnnouncements(result.data);
                            this.setError(false);
                        } else {
                            throw new Error(result.error || 'Failed to load announcements data');
                        }
                    } catch (error) {
                        console.error('Announcements widget error:', error);
                        this.setError(true, error.message);
                    } finally {
                        this.setLoading(false);
                    }
                },
                
                updateAnnouncements(announcements) {
                    if (!announcements || announcements.length === 0) {
                        this.listElement.style.display = 'none';
                        this.emptyElement.style.display = 'block';
                        return;
                    }
                    
                    this.listElement.style.display = 'block';
                    this.emptyElement.style.display = 'none';
                    
                    this.listElement.innerHTML = announcements.map(announcement => {
                        const isNew = this.isNewAnnouncement(announcement.created_at);
                        const hasAction = announcement.url && announcement.url.trim();
                        
                        return `
                            <div class="announcement-item priority-${announcement.priority} ${hasAction ? 'clickable' : ''}" 
                                 ${hasAction ? `onclick="window.open('${announcement.url}', '_blank')"` : ''}>
                                ${isNew ? '<div class="new-indicator"></div>' : ''}
                                <div class="announcement-header">
                                    <div class="announcement-title">${this.escapeHtml(announcement.title)}</div>
                                    <div class="priority-badge ${announcement.priority}">${announcement.priority}</div>
                                </div>
                                <div class="announcement-message">${this.escapeHtml(announcement.message)}</div>
                                <div class="announcement-footer">
                                    <div class="announcement-time">
                                        <i class="fas fa-clock"></i>
                                        <span>${announcement.created_at}</span>
                                    </div>
                                    ${hasAction ? `
                                        <div class="announcement-action">
                                            <span>View Details</span>
                                            <i class="fas fa-external-link-alt"></i>
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                        `;
                    }).join('');
                },
                
                setLoading(loading) {
                    this.container.classList.toggle('loading', loading);
                    
                    if (loading) {
                        // Show loading skeletons
                        this.listElement.innerHTML = Array(5).fill(0).map(() => `
                            <div class="loading-skeleton">
                                <div class="skeleton-header">
                                    <div class="skeleton-line title"></div>
                                    <div class="skeleton-line badge"></div>
                                </div>
                                <div class="skeleton-line short"></div>
                                <div class="skeleton-line"></div>
                                <div class="skeleton-line shorter"></div>
                            </div>
                        `).join('');
                        this.emptyElement.style.display = 'none';
                    }
                },
                
                setError(hasError, message = '') {
                    this.container.classList.toggle('error', hasError);
                    if (hasError) {
                        this.listElement.innerHTML = `
                            <div class="text-center text-danger">
                                <i class="fas fa-exclamation-triangle mb-2"></i>
                                <p class="mb-0">${message || 'Error loading announcements'}</p>
                            </div>
                        `;
                        this.emptyElement.style.display = 'none';
                    }
                },
                
                escapeHtml(text) {
                    const div = document.createElement('div');
                    div.textContent = text;
                    return div.innerHTML;
                },
                
                isNewAnnouncement(createdAt) {
                    // Consider announcements from last 24 hours as new
                    const now = new Date();
                    const created = new Date(createdAt);
                    const hoursDiff = (now - created) / (1000 * 60 * 60);
                    return hoursDiff <= 24;
                }
            };
            
            announcementsWidget.init();
        });
    </script>
</x-widget-container>