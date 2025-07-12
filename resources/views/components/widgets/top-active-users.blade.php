{{--
    Top Active Users Widget Component
    
    Displays top 5 most active users based on login frequency
    Features:
    - User avatars (Gravatar integration)
    - Login count and last login time
    - Responsive user cards
    - Auto-refresh capability
--}}

<x-widget-container 
    :title="$title ?? 'Top 5 Active Users'" 
    :icon="$icon ?? 'fas fa-users'" 
    :id="$id ?? 'active-users-widget'" 
    :refresh-interval="$refreshInterval ?? 3600" 
    :refreshable="$refreshable ?? true" 
    :permission="$permission ?? null" 
    :size="$size ?? 'col-lg-6'"
    :initial-loading="$initialLoading ?? true"
>
    <div class="active-users-container" data-widget="active-users">
        <div class="users-list" id="active-users-list">
            <!-- Users will be loaded here -->
        </div>
        <div class="empty-state" id="active-users-empty" style="display: none;">
            <i class="fas fa-user-slash text-muted mb-2"></i>
            <p class="text-muted mb-0">No active users found</p>
        </div>
    </div>
    
    <style>
        .active-users-container {
            padding: 15px;
        }
        
        .users-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .user-item {
            display: flex;
            align-items: center;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        
        .user-item:hover {
            background: #e9ecef;
            transform: translateX(2px);
            border-left-color: #FF7A00;
        }
        
        .user-item:nth-child(1) {
            border-left-color: #FFD700;
            background: linear-gradient(135deg, #fff9e6 0%, #f8f9fa 100%);
        }
        
        .user-item:nth-child(2) {
            border-left-color: #C0C0C0;
            background: linear-gradient(135deg, #f5f5f5 0%, #f8f9fa 100%);
        }
        
        .user-item:nth-child(3) {
            border-left-color: #CD7F32;
            background: linear-gradient(135deg, #fdf2e9 0%, #f8f9fa 100%);
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 12px;
            border: 2px solid #dee2e6;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .user-item:hover .user-avatar {
            transform: scale(1.1);
            border-color: #FF7A00;
        }
        
        .user-info {
            flex: 1;
            min-width: 0;
        }
        
        .user-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .user-email {
            font-size: 0.85rem;
            color: #6c757d;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .user-stats {
            text-align: right;
            min-width: 80px;
        }
        
        .login-count {
            font-weight: 700;
            color: #FF7A00;
            font-size: 1.1rem;
            margin-bottom: 2px;
        }
        
        .last-login {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .rank-badge {
            position: absolute;
            top: -5px;
            left: -5px;
            width: 20px;
            height: 20px;
            background: #FF7A00;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
            border: 2px solid white;
        }
        
        .user-item:nth-child(1) .rank-badge {
            background: #FFD700;
            color: #333;
        }
        
        .user-item:nth-child(2) .rank-badge {
            background: #C0C0C0;
            color: #333;
        }
        
        .user-item:nth-child(3) .rank-badge {
            background: #CD7F32;
            color: white;
        }
        
        .avatar-container {
            position: relative;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }
        
        .empty-state i {
            font-size: 2rem;
        }
        
        /* Loading state */
        .active-users-container.loading .users-list {
            opacity: 0.6;
        }
        
        .loading-skeleton {
            display: flex;
            align-items: center;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 12px;
        }
        
        .skeleton-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            margin-right: 12px;
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
            margin-bottom: 6px;
        }
        
        .skeleton-line.short {
            width: 60%;
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
        .active-users-container.error {
            text-align: center;
            padding: 40px 20px;
            color: #dc3545;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .user-item {
                padding: 10px;
            }
            
            .user-avatar {
                width: 35px;
                height: 35px;
                margin-right: 10px;
            }
            
            .user-name {
                font-size: 0.9rem;
            }
            
            .user-email {
                font-size: 0.8rem;
            }
            
            .login-count {
                font-size: 1rem;
            }
            
            .last-login {
                font-size: 0.75rem;
            }
        }
    </style>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const activeUsersWidget = {
                container: document.querySelector('[data-widget="active-users"]'),
                listElement: document.getElementById('active-users-list'),
                emptyElement: document.getElementById('active-users-empty'),
                
                init() {
                    this.loadData();
                    
                    // Register with widget manager
                    if (window.WidgetManager) {
                        window.WidgetManager.registerWidget('active-users', {
                            refresh: () => this.loadData(),
                            container: this.container.closest('.widget-container')
                        });
                    }
                },
                
                async loadData() {
                    try {
                        this.setLoading(true);
                        
                        const response = await fetch('/api/widgets/active-users');
                        const result = await response.json();
                        
                        if (result.success) {
                            this.updateUsers(result.data);
                            this.setError(false);
                        } else {
                            throw new Error(result.error || 'Failed to load active users data');
                        }
                    } catch (error) {
                        console.error('Active users widget error:', error);
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
                    
                    this.listElement.innerHTML = users.map((user, index) => `
                        <div class="user-item">
                            <div class="avatar-container">
                                <img src="${user.avatar}" alt="${user.name}" class="user-avatar" 
                                     onerror="this.src='https://www.gravatar.com/avatar/default?d=identicon&s=40'">
                                <div class="rank-badge">${index + 1}</div>
                            </div>
                            <div class="user-info">
                                <div class="user-name">${this.escapeHtml(user.name)}</div>
                                <div class="user-email">${this.escapeHtml(user.email)}</div>
                            </div>
                            <div class="user-stats">
                                <div class="login-count">${user.login_count}</div>
                                <div class="last-login">${user.last_login}</div>
                            </div>
                        </div>
                    `).join('');
                },
                
                setLoading(loading) {
                    this.container.classList.toggle('loading', loading);
                    
                    if (loading) {
                        // Show loading skeletons
                        this.listElement.innerHTML = Array(5).fill(0).map(() => `
                            <div class="loading-skeleton">
                                <div class="skeleton-avatar"></div>
                                <div class="skeleton-info">
                                    <div class="skeleton-line"></div>
                                    <div class="skeleton-line short"></div>
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
                                <p class="mb-0">${message || 'Error loading active users'}</p>
                            </div>
                        `;
                        this.emptyElement.style.display = 'none';
                    }
                },
                
                escapeHtml(text) {
                    const div = document.createElement('div');
                    div.textContent = text;
                    return div.innerHTML;
                }
            };
            
            activeUsersWidget.init();
        });
    </script>
</x-widget-container>