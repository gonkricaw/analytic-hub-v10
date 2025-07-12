{{--
    Login Activity Chart Widget Component
    
    Displays 15-day login trend using Chart.js
    Features:
    - Interactive line chart
    - Unique vs total logins
    - Responsive design
    - Hover tooltips
    - Auto-refresh capability
--}}

<x-widget-container 
    :title="$title ?? 'Login Activity (15 Days)'" 
    :icon="$icon ?? 'fas fa-chart-line'" 
    :id="$id ?? 'login-chart-widget'" 
    :refresh-interval="$refreshInterval ?? 300" 
    :refreshable="$refreshable ?? true" 
    :permission="$permission ?? null" 
    :size="$size ?? 'col-lg-8'"
    :initial-loading="$initialLoading ?? true"
>
    <div class="chart-container" data-widget="login-chart">
        <canvas id="login-activity-chart" width="400" height="200"></canvas>
        <div class="chart-legend">
            <div class="legend-item">
                <span class="legend-color" style="background-color: #FF7A00;"></span>
                <span class="legend-label">Unique Logins</span>
            </div>
            <div class="legend-item">
                <span class="legend-color" style="background-color: #0E0E44;"></span>
                <span class="legend-label">Total Logins</span>
            </div>
        </div>
    </div>
    
    <style>
        .chart-container {
            position: relative;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        #login-activity-chart {
            max-height: 300px;
            width: 100% !important;
            height: auto !important;
        }
        
        .chart-legend {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }
        
        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 2px;
        }
        
        .legend-label {
            color: #666;
            font-weight: 500;
        }
        
        /* Loading state */
        .chart-container.loading {
            opacity: 0.7;
        }
        
        .chart-container.loading::after {
            content: 'Loading chart data...';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255, 255, 255, 0.9);
            padding: 10px 20px;
            border-radius: 4px;
            font-size: 0.9rem;
            color: #666;
            z-index: 10;
        }
        
        /* Error state */
        .chart-container.error {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        
        .chart-container.error::after {
            content: 'Error loading chart data';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #dc3545;
            font-size: 0.9rem;
            z-index: 10;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .chart-container {
                padding: 15px;
            }
            
            .chart-legend {
                gap: 15px;
            }
            
            .legend-item {
                font-size: 0.85rem;
            }
        }
    </style>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginChartWidget = {
                container: document.querySelector('[data-widget="login-chart"]'),
                canvas: document.getElementById('login-activity-chart'),
                chart: null,
                
                init() {
                    this.loadData();
                    
                    // Register with widget manager
                    if (window.WidgetManager) {
                        window.WidgetManager.registerWidget('login-chart', {
                            refresh: () => this.loadData(),
                            container: this.container.closest('.widget-container')
                        });
                    }
                },
                
                async loadData() {
                    try {
                        this.setLoading(true);
                        
                        const response = await fetch('/api/widgets/login-activity');
                        const result = await response.json();
                        
                        if (result.success) {
                            this.updateChart(result.chart);
                            this.setError(false);
                        } else {
                            throw new Error(result.error || 'Failed to load login activity data');
                        }
                    } catch (error) {
                        console.error('Login chart widget error:', error);
                        this.setError(true, error.message);
                    } finally {
                        this.setLoading(false);
                    }
                },
                
                updateChart(chartData) {
                    // Destroy existing chart
                    if (this.chart) {
                        this.chart.destroy();
                    }
                    
                    // Create new chart
                    const ctx = this.canvas.getContext('2d');
                    this.chart = new Chart(ctx, {
                        type: 'line',
                        data: chartData,
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false // We use custom legend
                                },
                                tooltip: {
                                    mode: 'index',
                                    intersect: false,
                                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                    titleColor: 'white',
                                    bodyColor: 'white',
                                    borderColor: 'rgba(255, 255, 255, 0.2)',
                                    borderWidth: 1,
                                    cornerRadius: 6,
                                    displayColors: true,
                                    callbacks: {
                                        title: function(context) {
                                            return 'Date: ' + context[0].label;
                                        },
                                        label: function(context) {
                                            return context.dataset.label + ': ' + context.parsed.y + ' logins';
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    display: true,
                                    title: {
                                        display: true,
                                        text: 'Date',
                                        color: '#666',
                                        font: {
                                            size: 12,
                                            weight: 'bold'
                                        }
                                    },
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.1)'
                                    },
                                    ticks: {
                                        color: '#666',
                                        font: {
                                            size: 11
                                        }
                                    }
                                },
                                y: {
                                    display: true,
                                    title: {
                                        display: true,
                                        text: 'Number of Logins',
                                        color: '#666',
                                        font: {
                                            size: 12,
                                            weight: 'bold'
                                        }
                                    },
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.1)'
                                    },
                                    ticks: {
                                        color: '#666',
                                        font: {
                                            size: 11
                                        },
                                        beginAtZero: true,
                                        precision: 0
                                    }
                                }
                            },
                            interaction: {
                                mode: 'nearest',
                                axis: 'x',
                                intersect: false
                            },
                            elements: {
                                point: {
                                    radius: 4,
                                    hoverRadius: 6,
                                    borderWidth: 2,
                                    hoverBorderWidth: 3
                                },
                                line: {
                                    borderWidth: 3,
                                    fill: true
                                }
                            },
                            animation: {
                                duration: 1000,
                                easing: 'easeInOutQuart'
                            }
                        }
                    });
                },
                
                setLoading(loading) {
                    this.container.classList.toggle('loading', loading);
                },
                
                setError(hasError, message = '') {
                    this.container.classList.toggle('error', hasError);
                    if (hasError && this.chart) {
                        this.chart.destroy();
                        this.chart = null;
                    }
                },
                
                destroy() {
                    if (this.chart) {
                        this.chart.destroy();
                    }
                }
            };
            
            // Wait for Chart.js to be available
            if (typeof Chart !== 'undefined') {
                loginChartWidget.init();
            } else {
                // Wait for Chart.js to load
                const checkChart = setInterval(() => {
                    if (typeof Chart !== 'undefined') {
                        clearInterval(checkChart);
                        loginChartWidget.init();
                    }
                }, 100);
            }
            
            // Cleanup on page unload
            window.addEventListener('beforeunload', () => {
                loginChartWidget.destroy();
            });
        });
    </script>
</x-widget-container>