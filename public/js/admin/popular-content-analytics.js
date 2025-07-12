/**
 * Popular Content Analytics Dashboard JavaScript
 * Handles chart initialization, data updates, and user interactions
 */

class PopularContentAnalytics {
    constructor() {
        this.charts = {};
        this.currentPeriod = '7_days';
        this.currentCategory = 'all';
        this.currentContentType = 'all';
        this.refreshInterval = null;
        this.init();
    }

    /**
     * Initialize the analytics dashboard
     */
    init() {
        this.initializeCharts();
        this.bindEvents();
        this.loadInitialData();
        this.startAutoRefresh();
    }

    /**
     * Initialize all charts
     */
    initializeCharts() {
        // Popular Content Chart
        const popularCtx = document.getElementById('popularContentChart');
        if (popularCtx) {
            this.charts.popular = new Chart(popularCtx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Views',
                        data: [],
                        backgroundColor: 'rgba(54, 162, 235, 0.8)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Most Popular Content'
                        },
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString();
                                }
                            }
                        },
                        x: {
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45
                            }
                        }
                    },
                    onClick: (event, elements) => {
                        if (elements.length > 0) {
                            const index = elements[0].index;
                            const contentId = this.charts.popular.data.datasets[0].contentIds[index];
                            this.showContentDetails(contentId);
                        }
                    }
                }
            });
        }

        // Trending Content Chart
        const trendingCtx = document.getElementById('trendingContentChart');
        if (trendingCtx) {
            this.charts.trending = new Chart(trendingCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Growth Rate (%)',
                        data: [],
                        borderColor: 'rgba(255, 99, 132, 1)',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Trending Content (Growth Rate)'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    }
                }
            });
        }

        // Engagement Distribution Chart
        const engagementCtx = document.getElementById('engagementChart');
        if (engagementCtx) {
            this.charts.engagement = new Chart(engagementCtx, {
                type: 'doughnut',
                data: {
                    labels: ['High Engagement', 'Medium Engagement', 'Low Engagement'],
                    datasets: [{
                        data: [],
                        backgroundColor: [
                            'rgba(75, 192, 192, 0.8)',
                            'rgba(255, 206, 86, 0.8)',
                            'rgba(255, 99, 132, 0.8)'
                        ],
                        borderColor: [
                            'rgba(75, 192, 192, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(255, 99, 132, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Content Engagement Distribution'
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        // Category Performance Chart
        const categoryCtx = document.getElementById('categoryChart');
        if (categoryCtx) {
            this.charts.category = new Chart(categoryCtx, {
                type: 'radar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Average Views',
                        data: [],
                        borderColor: 'rgba(153, 102, 255, 1)',
                        backgroundColor: 'rgba(153, 102, 255, 0.2)',
                        pointBackgroundColor: 'rgba(153, 102, 255, 1)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Performance by Category'
                        }
                    },
                    scales: {
                        r: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    /**
     * Bind event listeners
     */
    bindEvents() {
        // Period filter
        document.getElementById('periodFilter')?.addEventListener('change', (e) => {
            this.currentPeriod = e.target.value;
            this.loadData();
        });

        // Category filter
        document.getElementById('categoryFilter')?.addEventListener('change', (e) => {
            this.currentCategory = e.target.value;
            this.loadData();
        });

        // Content type filter
        document.getElementById('contentTypeFilter')?.addEventListener('change', (e) => {
            this.currentContentType = e.target.value;
            this.loadData();
        });

        // Refresh button
        document.getElementById('refreshBtn')?.addEventListener('click', () => {
            this.loadData();
        });

        // Export buttons
        document.getElementById('exportCsvBtn')?.addEventListener('click', () => {
            this.exportData('csv');
        });

        document.getElementById('exportExcelBtn')?.addEventListener('click', () => {
            this.exportData('excel');
        });

        // Auto-refresh toggle
        document.getElementById('autoRefreshToggle')?.addEventListener('change', (e) => {
            if (e.target.checked) {
                this.startAutoRefresh();
            } else {
                this.stopAutoRefresh();
            }
        });
    }

    /**
     * Load initial data
     */
    loadInitialData() {
        this.loadSummaryCards();
        this.loadData();
    }

    /**
     * Load summary cards data
     */
    async loadSummaryCards() {
        try {
            const response = await fetch('/admin/analytics/popular-content/summary', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            if (response.ok) {
                const data = await response.json();
                this.updateSummaryCards(data);
            }
        } catch (error) {
            console.error('Error loading summary data:', error);
        }
    }

    /**
     * Update summary cards
     */
    updateSummaryCards(data) {
        const cards = {
            'total-views': data.total_views,
            'popular-content': data.popular_content_count,
            'trending-content': data.trending_content_count,
            'avg-engagement': data.avg_engagement_score
        };

        Object.entries(cards).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) {
                if (id === 'avg-engagement') {
                    element.textContent = parseFloat(value).toFixed(2);
                } else {
                    element.textContent = parseInt(value).toLocaleString();
                }
            }
        });
    }

    /**
     * Load chart data
     */
    async loadData() {
        this.showLoading(true);
        
        try {
            await Promise.all([
                this.loadPopularContent(),
                this.loadTrendingContent(),
                this.loadEngagementData(),
                this.loadCategoryData()
            ]);
        } catch (error) {
            console.error('Error loading data:', error);
            this.showError('Failed to load analytics data');
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * Load popular content data
     */
    async loadPopularContent() {
        const params = new URLSearchParams({
            period: this.currentPeriod,
            category: this.currentCategory,
            content_type: this.currentContentType,
            limit: 10
        });

        const response = await fetch(`/admin/analytics/popular-content/popular?${params}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        if (response.ok) {
            const data = await response.json();
            this.updatePopularChart(data.data);
            this.updatePopularTable(data.data);
        }
    }

    /**
     * Load trending content data
     */
    async loadTrendingContent() {
        const params = new URLSearchParams({
            category: this.currentCategory,
            content_type: this.currentContentType,
            limit: 10
        });

        const response = await fetch(`/admin/analytics/popular-content/trending?${params}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        if (response.ok) {
            const data = await response.json();
            this.updateTrendingChart(data.data);
            this.updateTrendingTable(data.data);
        }
    }

    /**
     * Load engagement data
     */
    async loadEngagementData() {
        const params = new URLSearchParams({
            period: this.currentPeriod,
            category: this.currentCategory,
            content_type: this.currentContentType
        });

        const response = await fetch(`/admin/analytics/popular-content/engagement?${params}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        if (response.ok) {
            const data = await response.json();
            this.updateEngagementChart(data);
        }
    }

    /**
     * Load category performance data
     */
    async loadCategoryData() {
        const params = new URLSearchParams({
            period: this.currentPeriod,
            content_type: this.currentContentType
        });

        const response = await fetch(`/admin/analytics/popular-content/categories?${params}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        if (response.ok) {
            const data = await response.json();
            this.updateCategoryChart(data);
        }
    }

    /**
     * Update popular content chart
     */
    updatePopularChart(data) {
        if (this.charts.popular && data.length > 0) {
            this.charts.popular.data.labels = data.map(item => 
                item.title.length > 30 ? item.title.substring(0, 30) + '...' : item.title
            );
            this.charts.popular.data.datasets[0].data = data.map(item => 
                parseInt(item[`views_${this.currentPeriod}`] || item.views_7_days)
            );
            this.charts.popular.data.datasets[0].contentIds = data.map(item => item.id);
            this.charts.popular.update();
        }
    }

    /**
     * Update trending content chart
     */
    updateTrendingChart(data) {
        if (this.charts.trending && data.length > 0) {
            this.charts.trending.data.labels = data.map(item => 
                item.title.length > 30 ? item.title.substring(0, 30) + '...' : item.title
            );
            this.charts.trending.data.datasets[0].data = data.map(item => 
                parseFloat(item.growth_rate_percentage || 0)
            );
            this.charts.trending.update();
        }
    }

    /**
     * Update engagement chart
     */
    updateEngagementChart(data) {
        if (this.charts.engagement) {
            this.charts.engagement.data.datasets[0].data = [
                data.high_engagement || 0,
                data.medium_engagement || 0,
                data.low_engagement || 0
            ];
            this.charts.engagement.update();
        }
    }

    /**
     * Update category chart
     */
    updateCategoryChart(data) {
        if (this.charts.category && data.length > 0) {
            this.charts.category.data.labels = data.map(item => item.category || 'Uncategorized');
            this.charts.category.data.datasets[0].data = data.map(item => 
                parseInt(item.avg_views || 0)
            );
            this.charts.category.update();
        }
    }

    /**
     * Update popular content table
     */
    updatePopularTable(data) {
        const tbody = document.querySelector('#popularContentTable tbody');
        if (tbody && data.length > 0) {
            tbody.innerHTML = data.map((item, index) => `
                <tr>
                    <td>${index + 1}</td>
                    <td>
                        <a href="/content/${item.id}" target="_blank" class="text-decoration-none">
                            ${item.title}
                        </a>
                    </td>
                    <td>${item.category || 'Uncategorized'}</td>
                    <td>${parseInt(item[`views_${this.currentPeriod}`] || item.views_7_days).toLocaleString()}</td>
                    <td>${parseFloat(item.engagement_score || 0).toFixed(2)}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="analytics.showContentDetails(${item.id})">
                            <i class="fas fa-eye"></i> Details
                        </button>
                    </td>
                </tr>
            `).join('');
        }
    }

    /**
     * Update trending content table
     */
    updateTrendingTable(data) {
        const tbody = document.querySelector('#trendingContentTable tbody');
        if (tbody && data.length > 0) {
            tbody.innerHTML = data.map((item, index) => `
                <tr>
                    <td>${index + 1}</td>
                    <td>
                        <a href="/content/${item.id}" target="_blank" class="text-decoration-none">
                            ${item.title}
                        </a>
                    </td>
                    <td>${item.category || 'Uncategorized'}</td>
                    <td>${parseFloat(item.growth_rate_percentage || 0).toFixed(1)}%</td>
                    <td>${parseInt(item.views_7_days || 0).toLocaleString()}</td>
                    <td>
                        <span class="badge bg-${item.is_trending ? 'success' : 'secondary'}">
                            ${item.is_trending ? 'Trending' : 'Normal'}
                        </span>
                    </td>
                </tr>
            `).join('');
        }
    }

    /**
     * Show content details modal
     */
    async showContentDetails(contentId) {
        try {
            const response = await fetch(`/admin/analytics/popular-content/details/${contentId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            if (response.ok) {
                const data = await response.json();
                this.displayContentDetailsModal(data);
            }
        } catch (error) {
            console.error('Error loading content details:', error);
        }
    }

    /**
     * Display content details in modal
     */
    displayContentDetailsModal(data) {
        // Implementation would depend on your modal structure
        // This is a placeholder for the modal display logic
        console.log('Content details:', data);
    }

    /**
     * Export data
     */
    async exportData(format) {
        const params = new URLSearchParams({
            format: format,
            period: this.currentPeriod,
            category: this.currentCategory,
            content_type: this.currentContentType
        });

        try {
            const response = await fetch(`/admin/analytics/popular-content/export?${params}`, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `popular-content-analytics-${new Date().toISOString().split('T')[0]}.${format}`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            }
        } catch (error) {
            console.error('Error exporting data:', error);
            this.showError('Failed to export data');
        }
    }

    /**
     * Start auto-refresh
     */
    startAutoRefresh() {
        this.stopAutoRefresh();
        this.refreshInterval = setInterval(() => {
            this.loadData();
        }, 300000); // Refresh every 5 minutes
    }

    /**
     * Stop auto-refresh
     */
    stopAutoRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
    }

    /**
     * Show loading state
     */
    showLoading(show) {
        const loader = document.getElementById('loadingIndicator');
        if (loader) {
            loader.style.display = show ? 'block' : 'none';
        }
    }

    /**
     * Show error message
     */
    showError(message) {
        // Implementation would depend on your notification system
        console.error(message);
        alert(message); // Temporary implementation
    }
}

// Initialize analytics when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.analytics = new PopularContentAnalytics();
});

// Export for global access
window.PopularContentAnalytics = PopularContentAnalytics;