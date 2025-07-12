{{--
    Image Banner Widget Component
    
    Displays rotating image banners/slideshow
    Features:
    - Auto-rotating slideshow
    - Navigation controls
    - Responsive design
    - Configurable timing
    - Click actions for banners
--}}

<x-widget-container 
    :title="$title ?? 'Image Banner'" 
    :icon="$icon ?? 'fas fa-images'" 
    :id="$id ?? 'image-banner-widget'" 
    :refresh-interval="$refreshInterval ?? 600" 
    :refreshable="$refreshable ?? true" 
    :permission="$permission ?? null" 
    :size="$size ?? 'col-lg-12'"
    :initial-loading="$initialLoading ?? true"
>
    <div class="image-banner-container" data-widget="image-banner">
        <div class="banner-slideshow" id="banner-slideshow">
            <div class="slides-container" id="slides-container">
                <!-- Slides will be loaded here -->
            </div>
            
            <!-- Navigation arrows -->
            <div class="nav-arrow nav-prev" id="nav-prev">
                <i class="fas fa-chevron-left"></i>
            </div>
            <div class="nav-arrow nav-next" id="nav-next">
                <i class="fas fa-chevron-right"></i>
            </div>
            
            <!-- Slide indicators -->
            <div class="slide-indicators" id="slide-indicators">
                <!-- Indicators will be generated here -->
            </div>
            
            <!-- Slide counter -->
            <div class="slide-counter" id="slide-counter">
                <span id="current-slide">1</span> / <span id="total-slides">1</span>
            </div>
        </div>
        
        <div class="empty-state" id="banner-empty" style="display: none;">
            <i class="fas fa-image text-muted mb-2"></i>
            <p class="text-muted mb-0">No banners available</p>
        </div>
    </div>
    
    <style>
        .image-banner-container {
            padding: 0;
            position: relative;
        }
        
        .banner-slideshow {
            position: relative;
            width: 100%;
            height: 300px;
            overflow: hidden;
            border-radius: 8px;
            background: #f8f9fa;
        }
        
        .slides-container {
            position: relative;
            width: 100%;
            height: 100%;
            display: flex;
            transition: transform 0.5s ease-in-out;
        }
        
        .slide {
            min-width: 100%;
            height: 100%;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }
        
        .slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .slide:hover img {
            transform: scale(1.05);
        }
        
        .slide-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.7));
            color: white;
            padding: 30px 20px 20px;
            transform: translateY(100%);
            transition: transform 0.3s ease;
        }
        
        .slide:hover .slide-overlay {
            transform: translateY(0);
        }
        
        .slide-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 8px;
            line-height: 1.3;
        }
        
        .slide-description {
            font-size: 0.9rem;
            opacity: 0.9;
            line-height: 1.4;
            margin-bottom: 10px;
        }
        
        .slide-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #FF7A00;
            font-weight: 500;
            font-size: 0.9rem;
            text-decoration: none;
        }
        
        .slide-action:hover {
            color: #e66a00;
            text-decoration: none;
        }
        
        /* Navigation arrows */
        .nav-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            background: rgba(0,0,0,0.5);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 10;
            opacity: 0;
        }
        
        .banner-slideshow:hover .nav-arrow {
            opacity: 1;
        }
        
        .nav-arrow:hover {
            background: rgba(0,0,0,0.7);
            transform: translateY(-50%) scale(1.1);
        }
        
        .nav-prev {
            left: 15px;
        }
        
        .nav-next {
            right: 15px;
        }
        
        /* Slide indicators */
        .slide-indicators {
            position: absolute;
            bottom: 15px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 8px;
            z-index: 10;
        }
        
        .indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(255,255,255,0.5);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .indicator.active {
            background: #FF7A00;
            transform: scale(1.2);
        }
        
        .indicator:hover {
            background: rgba(255,255,255,0.8);
        }
        
        /* Slide counter */
        .slide-counter {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(0,0,0,0.6);
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
            z-index: 10;
        }
        
        /* Auto-play indicator */
        .autoplay-indicator {
            position: absolute;
            top: 15px;
            left: 15px;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: rgba(0,0,0,0.6);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 10;
        }
        
        .autoplay-indicator:hover {
            background: rgba(0,0,0,0.8);
        }
        
        .autoplay-indicator.playing {
            color: #FF7A00;
        }
        
        /* Progress bar for auto-play */
        .progress-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background: #FF7A00;
            transition: width 0.1s linear;
            z-index: 10;
        }
        
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            height: 300px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .empty-state i {
            font-size: 3rem;
        }
        
        /* Loading state */
        .image-banner-container.loading .banner-slideshow {
            opacity: 0.6;
        }
        
        .loading-skeleton {
            width: 100%;
            height: 300px;
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: 8px;
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
        .image-banner-container.error {
            text-align: center;
            padding: 80px 20px;
            color: #dc3545;
            height: 300px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .banner-slideshow {
                height: 200px;
            }
            
            .slide-overlay {
                padding: 20px 15px 15px;
            }
            
            .slide-title {
                font-size: 1rem;
            }
            
            .slide-description {
                font-size: 0.8rem;
            }
            
            .nav-arrow {
                width: 35px;
                height: 35px;
            }
            
            .nav-prev {
                left: 10px;
            }
            
            .nav-next {
                right: 10px;
            }
            
            .slide-indicators {
                bottom: 10px;
            }
            
            .slide-counter {
                top: 10px;
                right: 10px;
                font-size: 0.7rem;
            }
            
            .autoplay-indicator {
                top: 10px;
                left: 10px;
                width: 25px;
                height: 25px;
            }
        }
        
        @media (max-width: 576px) {
            .banner-slideshow {
                height: 150px;
            }
            
            .slide-overlay {
                padding: 15px 10px 10px;
            }
            
            .slide-title {
                font-size: 0.9rem;
                margin-bottom: 4px;
            }
            
            .slide-description {
                font-size: 0.75rem;
                margin-bottom: 6px;
            }
            
            .slide-action {
                font-size: 0.8rem;
            }
        }
    </style>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const imageBannerWidget = {
                container: document.querySelector('[data-widget="image-banner"]'),
                slideshow: document.getElementById('banner-slideshow'),
                slidesContainer: document.getElementById('slides-container'),
                emptyElement: document.getElementById('banner-empty'),
                indicators: document.getElementById('slide-indicators'),
                currentSlideSpan: document.getElementById('current-slide'),
                totalSlidesSpan: document.getElementById('total-slides'),
                
                slides: [],
                currentSlide: 0,
                autoPlayInterval: null,
                autoPlayDuration: 5000, // 5 seconds
                isPlaying: true,
                
                init() {
                    this.loadData();
                    this.bindEvents();
                    
                    // Register with widget manager
                    if (window.WidgetManager) {
                        window.WidgetManager.registerWidget('image-banner', {
                            refresh: () => this.loadData(),
                            container: this.container.closest('.widget-container')
                        });
                    }
                },
                
                bindEvents() {
                    // Navigation arrows
                    document.getElementById('nav-prev').addEventListener('click', () => {
                        this.prevSlide();
                    });
                    
                    document.getElementById('nav-next').addEventListener('click', () => {
                        this.nextSlide();
                    });
                    
                    // Pause on hover
                    this.slideshow.addEventListener('mouseenter', () => {
                        this.pauseAutoPlay();
                    });
                    
                    this.slideshow.addEventListener('mouseleave', () => {
                        if (this.isPlaying) {
                            this.startAutoPlay();
                        }
                    });
                },
                
                async loadData() {
                    try {
                        this.setLoading(true);
                        
                        const response = await fetch('/api/widgets/image-banners');
                        const result = await response.json();
                        
                        if (result.success) {
                            this.updateBanners(result.data);
                            this.setError(false);
                        } else {
                            throw new Error(result.error || 'Failed to load banner data');
                        }
                    } catch (error) {
                        console.error('Image banner widget error:', error);
                        this.setError(true, error.message);
                    } finally {
                        this.setLoading(false);
                    }
                },
                
                updateBanners(banners) {
                    if (!banners || banners.length === 0) {
                        this.slideshow.style.display = 'none';
                        this.emptyElement.style.display = 'flex';
                        return;
                    }
                    
                    this.slideshow.style.display = 'block';
                    this.emptyElement.style.display = 'none';
                    
                    this.slides = banners;
                    this.currentSlide = 0;
                    
                    // Create slides
                    this.slidesContainer.innerHTML = banners.map((banner, index) => `
                        <div class="slide" onclick="${banner.url ? `window.open('${banner.url}', '_blank')` : 'void(0)'}">
                            <img src="${banner.image_url}" alt="${this.escapeHtml(banner.title || 'Banner')}" loading="lazy">
                            ${banner.title || banner.description ? `
                                <div class="slide-overlay">
                                    ${banner.title ? `<div class="slide-title">${this.escapeHtml(banner.title)}</div>` : ''}
                                    ${banner.description ? `<div class="slide-description">${this.escapeHtml(banner.description)}</div>` : ''}
                                    ${banner.url ? `
                                        <div class="slide-action">
                                            <span>Learn More</span>
                                            <i class="fas fa-arrow-right"></i>
                                        </div>
                                    ` : ''}
                                </div>
                            ` : ''}
                        </div>
                    `).join('');
                    
                    // Create indicators
                    this.indicators.innerHTML = banners.map((_, index) => `
                        <div class="indicator ${index === 0 ? 'active' : ''}" onclick="imageBannerWidget.goToSlide(${index})"></div>
                    `).join('');
                    
                    // Add autoplay indicator
                    if (!document.querySelector('.autoplay-indicator')) {
                        const autoplayBtn = document.createElement('div');
                        autoplayBtn.className = 'autoplay-indicator playing';
                        autoplayBtn.innerHTML = '<i class="fas fa-pause"></i>';
                        autoplayBtn.addEventListener('click', () => this.toggleAutoPlay());
                        this.slideshow.appendChild(autoplayBtn);
                    }
                    
                    // Add progress bar
                    if (!document.querySelector('.progress-bar')) {
                        const progressBar = document.createElement('div');
                        progressBar.className = 'progress-bar';
                        progressBar.style.width = '0%';
                        this.slideshow.appendChild(progressBar);
                    }
                    
                    // Update counters
                    this.updateSlideCounter();
                    
                    // Start autoplay
                    this.startAutoPlay();
                },
                
                goToSlide(index) {
                    if (index < 0 || index >= this.slides.length) return;
                    
                    this.currentSlide = index;
                    const translateX = -index * 100;
                    this.slidesContainer.style.transform = `translateX(${translateX}%)`;
                    
                    // Update indicators
                    document.querySelectorAll('.indicator').forEach((indicator, i) => {
                        indicator.classList.toggle('active', i === index);
                    });
                    
                    this.updateSlideCounter();
                    this.resetProgressBar();
                },
                
                nextSlide() {
                    const nextIndex = (this.currentSlide + 1) % this.slides.length;
                    this.goToSlide(nextIndex);
                },
                
                prevSlide() {
                    const prevIndex = (this.currentSlide - 1 + this.slides.length) % this.slides.length;
                    this.goToSlide(prevIndex);
                },
                
                startAutoPlay() {
                    if (this.slides.length <= 1) return;
                    
                    this.pauseAutoPlay();
                    this.autoPlayInterval = setInterval(() => {
                        this.nextSlide();
                    }, this.autoPlayDuration);
                    
                    this.startProgressBar();
                },
                
                pauseAutoPlay() {
                    if (this.autoPlayInterval) {
                        clearInterval(this.autoPlayInterval);
                        this.autoPlayInterval = null;
                    }
                    this.pauseProgressBar();
                },
                
                toggleAutoPlay() {
                    this.isPlaying = !this.isPlaying;
                    const autoplayBtn = document.querySelector('.autoplay-indicator');
                    const icon = autoplayBtn.querySelector('i');
                    
                    if (this.isPlaying) {
                        autoplayBtn.classList.add('playing');
                        icon.className = 'fas fa-pause';
                        this.startAutoPlay();
                    } else {
                        autoplayBtn.classList.remove('playing');
                        icon.className = 'fas fa-play';
                        this.pauseAutoPlay();
                    }
                },
                
                startProgressBar() {
                    const progressBar = document.querySelector('.progress-bar');
                    if (!progressBar) return;
                    
                    progressBar.style.width = '0%';
                    progressBar.style.transition = `width ${this.autoPlayDuration}ms linear`;
                    
                    // Small delay to ensure transition works
                    setTimeout(() => {
                        progressBar.style.width = '100%';
                    }, 50);
                },
                
                pauseProgressBar() {
                    const progressBar = document.querySelector('.progress-bar');
                    if (!progressBar) return;
                    
                    const currentWidth = progressBar.offsetWidth;
                    const containerWidth = progressBar.parentElement.offsetWidth;
                    const percentage = (currentWidth / containerWidth) * 100;
                    
                    progressBar.style.transition = 'none';
                    progressBar.style.width = `${percentage}%`;
                },
                
                resetProgressBar() {
                    const progressBar = document.querySelector('.progress-bar');
                    if (!progressBar) return;
                    
                    progressBar.style.transition = 'none';
                    progressBar.style.width = '0%';
                },
                
                updateSlideCounter() {
                    this.currentSlideSpan.textContent = this.currentSlide + 1;
                    this.totalSlidesSpan.textContent = this.slides.length;
                },
                
                setLoading(loading) {
                    this.container.classList.toggle('loading', loading);
                    
                    if (loading) {
                        this.slideshow.innerHTML = '<div class="loading-skeleton"></div>';
                        this.emptyElement.style.display = 'none';
                    }
                },
                
                setError(hasError, message = '') {
                    this.container.classList.toggle('error', hasError);
                    if (hasError) {
                        this.slideshow.style.display = 'none';
                        this.emptyElement.style.display = 'none';
                        this.container.innerHTML = `
                            <div class="error">
                                <i class="fas fa-exclamation-triangle mb-2"></i>
                                <p class="mb-0">${message || 'Error loading banners'}</p>
                            </div>
                        `;
                    }
                },
                
                escapeHtml(text) {
                    const div = document.createElement('div');
                    div.textContent = text;
                    return div.innerHTML;
                }
            };
            
            // Make widget globally accessible for indicator clicks
            window.imageBannerWidget = imageBannerWidget;
            imageBannerWidget.init();
        });
    </script>
</x-widget-container>