{{--
    New Users Widget Component
    
    Displays recently registered users
    Features:
    - User avatars and profile information
    - Registration timestamps
    - User status indicators
    - Responsive design
    - Auto-refresh capability
--}}

<x-widget-container 
    :title="$title ?? 'New Users'" 
    :icon="$icon ?? 'fas fa-user-plus'" 
    :id="$id ?? 'new-users-widget'" 
    :refresh-interval="$refreshInterval ?? 300" 
    :refreshable="$refreshable ?? true" 
    :permission="$permission ?? null" 
    :size="$size ?? 'col-lg-6'"
    :initial-loading="$initialLoading ?? true"
>
    <div class="new-users-container" data-widget="new-users">
        <div class="users-list" id="new-users-list">
            <!-- New users will be loaded here -->
        </div>
        <div class="empty-state" id="new-users-empty" style="display: none;">
            <i class="fas fa-users text-muted mb-2"></i>
            <p class="text-muted mb-0">No new users recently</p>
        </div>
    </div>
    
    <style>
        .new-users-container {
            padding: 15px;
        }
        
        .users-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            max-height: 400px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #dee2e6 transparent;
        }
        
        .users-list::-webkit-scrollbar {
            width: 4px;
        }
        
        .users-list::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .users-list::-webkit-scrollbar-thumb {
            background: #dee2e6;
            border-radius: 2px;
        }
        
        .user-item {
            display: flex;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            transition: all 0.3s ease;
            border-left: 4px solid #FF7A00;
            position: relative;
            overflow: hidden;
        }
        
        .user-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            background: #e9ecef;
        }
        
        .user-avatar {
            position: relative;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .avatar-img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            background: linear-gradient(135deg, #FF7A00, #e66a00);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
        }
        
        .status-indicator {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            border: 2px solid #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        
        .status-indicator.active {
            background: #28a745;
        }
        
        .status-indicator.inactive {
            background: #6c757d;
        }
        
        .status-indicator.pending {
            background: #ffc107;
        }
        
        .user-info {
            flex: 1;
            min-width: 0;
        }
        
        .user-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 4px;
            font-size: 1rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .user-email {
            color: #6c757d;
            font-size: 0.85rem;
            margin-bottom: 6px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .user-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .user-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 8px;
            margin-left: 15px;
            flex-shrink: 0;
        }
        
        .user-status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .user-status.active {
            background: #d4edda;
            color: #155724;
        }
        
        .user-status.inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .user-status.pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .registration-time {
            font-size: 0.75rem;
            color: #6c757d;
            text-align: right;
        }
        
        .new-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #dc3545;
            color: white;
            font-size: 0.6rem;
            padding: 2px 6px;
            border-radius: 8px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.7;
                transform: scale(1.05);
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
        .new-users-container.loading .users-list {
            opacity: 0.6;
        }
        
        .loading-skeleton {
            display: flex;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 12px;
            border-left: 4px solid #dee2e6;
        }
        
        .skeleton-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .skeleton-info {
            flex: 1;
        }
        
        .skeleton-line {
            height: 12px;
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: 4px;
            margin-bottom: 8px;
        }
        
        .skeleton-line.name {
            width: 60%;
            height: 16px;
        }
        
        .skeleton-line.email {
            width: 80%;
            height: 12px;
        }
        
        .skeleton-line.meta {
            width: 40%;
            height: 10px;
        }
        
        .skeleton-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 8px;
            margin-left: 15px;
        }
        
        .skeleton-status {
            width: 60px;
            height: 20px;
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: 12px;
        }
        
        .skeleton-time {
            width: 80px;
            height: 10px;
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: 4px;
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
        .new-users-container.error {
            text-align: center;
            padding: 40px 20px;
            color: #dc3545;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .user-item {
                padding: 12px;
            }
            
            .avatar-img {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
            
            .user-name {
                font-size: 0.9rem;
            }
            
            .user-email {
                font-size: 0.8rem;
            }
            
            .user-meta {
                font-size: 0.75rem;
                gap: 10px;
            }
            
            .user-status {
                font-size: 0.65rem;
                padding: 2px 6px;
            }
        }
        
        @media (max-width: 576px) {
            .user-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .user-avatar {
                margin-right: 0;
                align-self: center;
            }
            
            .user-info {
                text-align: center;
                width: 100%;
            }
            
            .user-actions {
                margin-left: 0;
                align-items: center;
                flex-direction: row;
                justify-content: center;
                width: 100%;
            }
            
            .user-meta {
                justify-content: center;
                flex-wrap: wrap;
            }
        }
    </style>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const newUsersWidget = {
                container: document.querySelector('[data-widget="new-users"]'),
                listElement: document.getElementById('new-users-list'),
                emptyElement: document.getElementById('new-users-empty'),
                
                init() {
                    this.loadData();
                    
                    // Register with widget manager
                    if (window.WidgetManager) {
                        window.WidgetManager.registerWidget('new-users', {
                            refresh: () => this.loadData(),
                            container: this.container.closest('.widget-container')
                        });
                    }
                },
                
                async loadData() {
                    try {
                        this.setLoading(true);
                        
                        const response = await fetch('/api/widgets/new-users');
                        const result = await response.json();
                        
                        if (result.success) {
                            this.updateUsers(result.data);
                            this.setError(false);
                        } else {
                            throw new Error(result.error || 'Failed to load new users data');
                        }
                    } catch (error) {
                        console.error('New users widget error:', error);
                        this.setError(true, error.message);
                    } finally {
                        this.setLoading(false);
                    }
                },
                
                updateUsers(users) {
                    if (!users || users.length === 0) {
                        this.listElement.style.display = 'none';
                        this.emptyElement.style.display = 'block';
                        return;
                    }
                    
                    this.listElement.style.display = 'block';
                    this.emptyElement.style.display = 'none';
                    
                    this.listElement.innerHTML = users.map(user => {
                        const isNew = this.isNewUser(user.created_at);
                        const avatarContent = user.avatar 
                            ? `<img src="${user.avatar}" alt="${user.name}" class="avatar-img">` 
                            : `<div class="avatar-img">${this.getInitials(user.name)}</div>`;
                        
                        return `
                            <div class="user-item">
                                ${isNew ? '<div class="new-badge">New</div>' : ''}
                                <div class="user-avatar">
                                    ${avatarContent}
                                    <div class="status-indicator ${user.status}"></div>
                                </div>
                                <div class="user-info">
                                    <div class="user-name">${this.escapeHtml(user.name)}</div>
                                    <div class="user-email">${this.escapeHtml(user.email)}</div>
                                    <div class="user-meta">
                                        <div class="meta-item">
                                            <i class="fas fa-calendar-alt"></i>
                                            <span>Joined ${user.created_at}</span>
                                        </div>
                                        ${user.last_login ? `
                                            <div class="meta-item">
                                                <i class="fas fa-sign-in-alt"></i>
                                                <span>Last login ${user.last_login}</span>
                                            </div>
                                        ` : `
                                            <div class="meta-item">
                                                <i class="fas fa-clock"></i>
                                                <span>Never logged in</span>
                                            </div>
                                        `}
                                    </div>
                                </div>
                                <div class="user-actions">
                                    <div class="user-status ${user.status}">${user.status}</div>
                                    <div class="registration-time">${this.getTimeAgo(user.created_at)}</div>
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
                                <div class="skeleton-avatar"></div>
                                <div class="skeleton-info">
                                    <div class="skeleton-line name"></div>
                                    <div class="skeleton-line email"></div>
                                    <div class="skeleton-line meta"></div>
                                </div>
                                <div class="skeleton-actions">
                                    <div class="skeleton-status"></div>
                                    <div class="skeleton-time"></div>
                                </div>
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
                                <p class="mb-0">${message || 'Error loading new users'}</p>
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
                
                getInitials(name) {
                    return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
                },
                
                isNewUser(createdAt) {
                    // Consider users from last 7 days as new
                    const now = new Date();
                    const created = new Date(createdAt);
                    const daysDiff = (now - created) / (1000 * 60 * 60 * 24);
                    return daysDiff <= 7;
                },
                
                getTimeAgo(dateString) {
                    const now = new Date();
                    const date = new Date(dateString);
                    const diffInSeconds = Math.floor((now - date) / 1000);
                    
                    if (diffInSeconds < 60) return 'Just now';
                    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
                    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
                    if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)}d ago`;
                    return `${Math.floor(diffInSeconds / 604800)}w ago`;
                }
            };
            
            newUsersWidget.init();
        });
    </script>
</x-widget-container>