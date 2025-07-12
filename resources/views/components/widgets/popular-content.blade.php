{{--
    Popular Content Widget Component
    
    Displays top 5 most visited content pages
    Features:
    - Content titles with visit counts
    - Direct links to content
    - Last visited timestamps
    - Responsive design
    - Auto-refresh capability
--}}

<x-widget-container 
    :title="$title ?? 'Popular Content'" 
    :icon="$icon ?? 'fas fa-fire'" 
    :id="$id ?? 'popular-content-widget'" 
    :refresh-interval="$refreshInterval ?? 1800" 
    :refreshable="$refreshable ?? true" 
    :permission="$permission ?? null" 
    :size="$size ?? 'col-lg-6'"
    :initial-loading="$initialLoading ?? true"
>
    <div class="popular-content-container" data-widget="popular-content">
        <div class="content-list" id="popular-content-list">
            <!-- Content items will be loaded here -->
        </div>
        <div class="empty-state" id="popular-content-empty" style="display: none;">
            <i class="fas fa-file-alt text-muted mb-2"></i>
            <p class="text-muted mb-0">No popular content found</p>
        </div>
    </div>
    
    <style>
        .popular-content-container {
            padding: 15px;
        }
        
        .content-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .content-item {
            display: flex;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
            text-decoration: none;
            color: inherit;
        }
        
        .content-item:hover {
            background: #e9ecef;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-left-color: #FF7A00;
            text-decoration: none;
            color: inherit;
        }
        
        .content-item:nth-child(1) {
            border-left-color: #FF7A00;
            background: linear-gradient(135deg, #fff5e6 0%, #f8f9fa 100%);
        }
        
        .content-item:nth-child(2) {
            border-left-color: #0E0E44;
            background: linear-gradient(135deg, #f0f0f8 0%, #f8f9fa 100%);
        }
        
        .content-item:nth-child(3) {
            border-left-color: #28a745;
            background: linear-gradient(135deg, #f0f8f0 0%, #f8f9fa 100%);
        }
        
        .content-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #FF7A00 0%, #0E0E44 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-right: 15px;
            flex-shrink: 0;
            transition: transform 0.3s ease;
        }
        
        .content-item:hover .content-icon {
            transform: scale(1.1) rotate(5deg);
        }
        
        .content-info {
            flex: 1;
            min-width: 0;
        }
        
        .content-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 4px;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .content-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .content-visits {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .content-last-visited {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .content-stats {
            text-align: right;
            min-width: 80px;
        }
        
        .visit-count {
            font-weight: 700;
            color: #FF7A00;
            font-size: 1.2rem;
            margin-bottom: 2px;
        }
        
        .visit-label {
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
        
        .content-item:nth-child(1) .rank-badge {
            background: #FFD700;
            color: #333;
        }
        
        .content-item:nth-child(2) .rank-badge {
            background: #C0C0C0;
            color: #333;
        }
        
        .content-item:nth-child(3) .rank-badge {
            background: #CD7F32;
            color: white;
        }
        
        .icon-container {
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
        .popular-content-container.loading .content-list {
            opacity: 0.6;
        }
        
        .loading-skeleton {
            display: flex;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 12px;
        }
        
        .skeleton-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            margin-right: 15px;
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
        
        .skeleton-line.short {
            width: 60%;
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
        .popular-content-container.error {
            text-align: center;
            padding: 40px 20px;
            color: #dc3545;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .content-item {
                padding: 12px;
            }
            
            .content-icon {
                width: 35px;
                height: 35px;
                margin-right: 12px;
            }
            
            .content-title {
                font-size: 0.9rem;
            }
            
            .content-meta {
                font-size: 0.8rem;
                gap: 10px;
            }
            
            .visit-count {
                font-size: 1.1rem;
            }
            
            .visit-label {
                font-size: 0.75rem;
            }
        }
        
        @media (max-width: 576px) {
            .content-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 4px;
            }
        }
    </style>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const popularContentWidget = {
                container: document.querySelector('[data-widget="popular-content"]'),
                listElement: document.getElementById('popular-content-list'),
                emptyElement: document.getElementById('popular-content-empty'),
                
                init() {
                    this.loadData();
                    
                    // Register with widget manager
                    if (window.WidgetManager) {
                        window.WidgetManager.registerWidget('popular-content', {
                            refresh: () => this.loadData(),
                            container: this.container.closest('.widget-container')
                        });
                    }
                },
                
                async loadData() {
                    try {
                        this.setLoading(true);
                        
                        const response = await fetch('/api/widgets/popular-content');
                        const result = await response.json();
                        
                        if (result.success) {
                            this.updateContent(result.data);
                            this.setError(false);
                        } else {
                            throw new Error(result.error || 'Failed to load popular content data');
                        }
                    } catch (error) {
                        console.error('Popular content widget error:', error);
                        this.setError(true, error.message);
                    } finally {
                        this.setLoading(false);
                    }
                },
                
                updateContent(content) {
                    if (!content || content.length === 0) {
                        this.listElement.style.display = 'none';
                        this.emptyElement.style.display = 'block';
                        return;
                    }
                    
                    this.listElement.style.display = 'block';
                    this.emptyElement.style.display = 'none';
                    
                    this.listElement.innerHTML = content.map((item, index) => `
                        <a href="${item.url}" class="content-item" target="_blank">
                            <div class="icon-container">
                                <div class="content-icon">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div class="rank-badge">${index + 1}</div>
                            </div>
                            <div class="content-info">
                                <div class="content-title">${this.escapeHtml(item.title)}</div>
                                <div class="content-meta">
                                    <div class="content-visits">
                                        <i class="fas fa-eye"></i>
                                        <span>${item.visit_count} views</span>
                                    </div>
                                    <div class="content-last-visited">
                                        <i class="fas fa-clock"></i>
                                        <span>${item.last_visited}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="content-stats">
                                <div class="visit-count">${this.formatNumber(item.visit_count)}</div>
                                <div class="visit-label">visits</div>
                            </div>
                        </a>
                    `).join('');
                },
                
                setLoading(loading) {
                    this.container.classList.toggle('loading', loading);
                    
                    if (loading) {
                        // Show loading skeletons
                        this.listElement.innerHTML = Array(5).fill(0).map(() => `
                            <div class="loading-skeleton">
                                <div class="skeleton-icon"></div>
                                <div class="skeleton-info">
                                    <div class="skeleton-line"></div>
                                    <div class="skeleton-line short"></div>
                                    <div class="skeleton-line shorter"></div>
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
                                <p class="mb-0">${message || 'Error loading popular content'}</p>
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
                
                formatNumber(num) {
                    if (num >= 1000000) {
                        return (num / 1000000).toFixed(1) + 'M';
                    } else if (num >= 1000) {
                        return (num / 1000).toFixed(1) + 'K';
                    }
                    return num.toString();
                }
            };
            
            popularContentWidget.init();
        });
    </script>
</x-widget-container>