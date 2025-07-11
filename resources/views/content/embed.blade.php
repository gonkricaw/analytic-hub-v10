<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $content->title }} - Secure Embed</title>
    
    <!-- Security Headers -->
    <meta http-equiv="X-Frame-Options" content="SAMEORIGIN">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">
    
    <!-- Disable caching for security -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body, html {
            height: 100%;
            overflow: hidden;
            background: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .embed-container {
            position: relative;
            width: 100%;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .embed-header {
            background: #343a40;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            z-index: 1000;
        }
        
        .embed-title {
            font-weight: 600;
            margin: 0;
        }
        
        .embed-info {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 12px;
            opacity: 0.8;
        }
        
        .security-indicator {
            display: flex;
            align-items: center;
            gap: 5px;
            background: rgba(40, 167, 69, 0.2);
            padding: 4px 8px;
            border-radius: 12px;
            color: #28a745;
        }
        
        .embed-content {
            flex: 1;
            position: relative;
            background: white;
        }
        
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #f8f9fa;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 100;
            transition: opacity 0.3s ease;
        }
        
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #e9ecef;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .loading-text {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .loading-progress {
            width: 200px;
            height: 4px;
            background: #e9ecef;
            border-radius: 2px;
            overflow: hidden;
        }
        
        .loading-progress-bar {
            height: 100%;
            background: #007bff;
            width: 0%;
            transition: width 0.3s ease;
        }
        
        .secure-iframe {
            width: 100%;
            height: 100%;
            border: none;
            display: none;
        }
        
        .error-container {
            display: none;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100%;
            text-align: center;
            padding: 40px;
        }
        
        .error-icon {
            font-size: 48px;
            color: #dc3545;
            margin-bottom: 20px;
        }
        
        .error-title {
            font-size: 24px;
            color: #dc3545;
            margin-bottom: 15px;
        }
        
        .error-message {
            color: #6c757d;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        
        .retry-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        
        .retry-btn:hover {
            background: #0056b3;
        }
        
        .token-expired {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            text-align: center;
            display: none;
            z-index: 1001;
        }
        
        .token-expired.show {
            display: block;
        }
        
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: none;
            z-index: 1000;
        }
        
        .overlay.show {
            display: block;
        }
        
        /* Hide scrollbars for security */
        ::-webkit-scrollbar {
            display: none;
        }
        
        /* Disable text selection */
        * {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        
        /* Disable drag and drop */
        * {
            -webkit-user-drag: none;
            -khtml-user-drag: none;
            -moz-user-drag: none;
            -o-user-drag: none;
            user-drag: none;
        }
    </style>
</head>
<body>
    <div class="embed-container">
        <!-- Embed Header -->
        <div class="embed-header">
            <h1 class="embed-title">{{ $content->title }}</h1>
            <div class="embed-info">
                <div class="security-indicator">
                    <i class="fas fa-shield-alt"></i>
                    <span>Secure</span>
                </div>
                <span id="timer">{{ $expires_in }}s</span>
            </div>
        </div>
        
        <!-- Embed Content -->
        <div class="embed-content">
            <!-- Loading Overlay -->
            <div class="loading-overlay" id="loadingOverlay">
                <div class="loading-spinner"></div>
                <div class="loading-text">Loading secure content...</div>
                <div class="loading-progress">
                    <div class="loading-progress-bar" id="progressBar"></div>
                </div>
            </div>
            
            <!-- Error Container -->
            <div class="error-container" id="errorContainer">
                <div class="error-icon">⚠️</div>
                <div class="error-title">Content Load Failed</div>
                <div class="error-message" id="errorMessage">Unable to load the requested content.</div>
                <button class="retry-btn" onclick="retryLoad()">Retry</button>
            </div>
            
            <!-- Secure Iframe -->
            <iframe 
                id="secureIframe"
                class="secure-iframe"
                sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-presentation"
                referrerpolicy="strict-origin-when-cross-origin"
                loading="lazy"
            ></iframe>
        </div>
    </div>
    
    <!-- Token Expired Modal -->
    <div class="overlay" id="overlay"></div>
    <div class="token-expired" id="tokenExpired">
        <div class="error-icon">🔒</div>
        <div class="error-title">Session Expired</div>
        <div class="error-message">Your secure access session has expired. Please refresh to continue.</div>
        <button class="retry-btn" onclick="window.location.reload()">Refresh</button>
    </div>
    
    <!-- Browser Protection Script -->
    {!! $protection_script !!}
    
    <script>
        // Configuration
        const CONFIG = {
            contentId: '{{ $content->id }}',
            uuid: '{{ $uuid }}',
            accessToken: '{{ $access_token }}',
            expiresIn: {{ $expires_in }},
            secureViewUrl: '{{ route("content.secure-view", ":token") }}'
        };
        
        // State management
        let tokenTimer = CONFIG.expiresIn;
        let loadAttempts = 0;
        let maxRetries = 3;
        let isLoading = false;
        
        // DOM elements
        const loadingOverlay = document.getElementById('loadingOverlay');
        const errorContainer = document.getElementById('errorContainer');
        const secureIframe = document.getElementById('secureIframe');
        const progressBar = document.getElementById('progressBar');
        const timerElement = document.getElementById('timer');
        const tokenExpiredModal = document.getElementById('tokenExpired');
        const overlay = document.getElementById('overlay');
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            startTokenTimer();
            loadSecureContent();
        });
        
        // Token countdown timer
        function startTokenTimer() {
            const timer = setInterval(function() {
                tokenTimer--;
                
                if (tokenTimer <= 0) {
                    clearInterval(timer);
                    showTokenExpired();
                    return;
                }
                
                // Update timer display
                const minutes = Math.floor(tokenTimer / 60);
                const seconds = tokenTimer % 60;
                timerElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                
                // Warning when less than 2 minutes
                if (tokenTimer <= 120) {
                    timerElement.style.color = '#ffc107';
                }
                
                // Critical when less than 30 seconds
                if (tokenTimer <= 30) {
                    timerElement.style.color = '#dc3545';
                    timerElement.style.fontWeight = 'bold';
                }
            }, 1000);
        }
        
        // Load secure content
        function loadSecureContent() {
            if (isLoading) return;
            
            isLoading = true;
            loadAttempts++;
            
            // Show loading state
            showLoading();
            
            // Simulate loading progress
            let progress = 0;
            const progressInterval = setInterval(function() {
                progress += Math.random() * 15;
                if (progress > 90) progress = 90;
                progressBar.style.width = progress + '%';
            }, 200);
            
            // Generate secure URL
            const secureUrl = CONFIG.secureViewUrl.replace(':token', CONFIG.accessToken);
            
            // Set iframe source
            secureIframe.src = secureUrl;
            
            // Handle iframe load
            secureIframe.onload = function() {
                clearInterval(progressInterval);
                progressBar.style.width = '100%';
                
                setTimeout(function() {
                    hideLoading();
                    showContent();
                    isLoading = false;
                }, 500);
            };
            
            // Handle iframe error
            secureIframe.onerror = function() {
                clearInterval(progressInterval);
                handleLoadError('Failed to load secure content');
                isLoading = false;
            };
            
            // Timeout after 30 seconds
            setTimeout(function() {
                if (isLoading) {
                    clearInterval(progressInterval);
                    handleLoadError('Content load timeout');
                    isLoading = false;
                }
            }, 30000);
        }
        
        // Show loading state
        function showLoading() {
            loadingOverlay.style.display = 'flex';
            errorContainer.style.display = 'none';
            secureIframe.style.display = 'none';
            progressBar.style.width = '0%';
        }
        
        // Hide loading state
        function hideLoading() {
            loadingOverlay.style.opacity = '0';
            setTimeout(function() {
                loadingOverlay.style.display = 'none';
            }, 300);
        }
        
        // Show content
        function showContent() {
            secureIframe.style.display = 'block';
            errorContainer.style.display = 'none';
        }
        
        // Handle load error
        function handleLoadError(message) {
            document.getElementById('errorMessage').textContent = message;
            loadingOverlay.style.display = 'none';
            errorContainer.style.display = 'flex';
            secureIframe.style.display = 'none';
        }
        
        // Retry loading
        function retryLoad() {
            if (loadAttempts >= maxRetries) {
                handleLoadError('Maximum retry attempts reached. Please refresh the page.');
                return;
            }
            
            loadSecureContent();
        }
        
        // Show token expired modal
        function showTokenExpired() {
            overlay.classList.add('show');
            tokenExpiredModal.classList.add('show');
        }
        
        // Security monitoring
        function monitorSecurity() {
            // Monitor for developer tools
            let devtools = false;
            
            setInterval(function() {
                if (window.outerHeight - window.innerHeight > 200 || 
                    window.outerWidth - window.innerWidth > 200) {
                    if (!devtools) {
                        devtools = true;
                        // Log security violation
                        console.warn('Security violation detected');
                        // Optionally redirect or show warning
                    }
                } else {
                    devtools = false;
                }
            }, 1000);
        }
        
        // Start security monitoring
        monitorSecurity();
        
        // Prevent common inspection methods
        document.addEventListener('keydown', function(e) {
            // Prevent F12, Ctrl+Shift+I, Ctrl+Shift+C, Ctrl+U, Ctrl+S
            if (e.keyCode === 123 || 
                (e.ctrlKey && e.shiftKey && (e.keyCode === 73 || e.keyCode === 67)) ||
                (e.ctrlKey && (e.keyCode === 85 || e.keyCode === 83))) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });
        
        // Prevent right-click
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            return false;
        });
        
        // Prevent text selection
        document.addEventListener('selectstart', function(e) {
            e.preventDefault();
            return false;
        });
        
        // Prevent drag and drop
        document.addEventListener('dragstart', function(e) {
            e.preventDefault();
            return false;
        });
        
        // Clear console periodically
        setInterval(function() {
            if (typeof console !== 'undefined' && console.clear) {
                console.clear();
            }
        }, 2000);
        
        // Disable print
        window.addEventListener('beforeprint', function(e) {
            e.preventDefault();
            return false;
        });
        
        // Monitor for screenshot attempts (limited detection)
        document.addEventListener('keydown', function(e) {
            // Print Screen, Alt+Print Screen
            if (e.keyCode === 44 || (e.altKey && e.keyCode === 44)) {
                // Log potential screenshot attempt
                console.warn('Screenshot attempt detected');
            }
        });
        
        // Blur content when window loses focus (anti-screenshot)
        window.addEventListener('blur', function() {
            document.body.style.filter = 'blur(5px)';
        });
        
        window.addEventListener('focus', function() {
            document.body.style.filter = 'none';
        });
        
        // Log page visibility changes
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                // Page is hidden - potential security risk
                console.warn('Page visibility changed - hidden');
            } else {
                // Page is visible again
                console.log('Page visibility changed - visible');
            }
        });
    </script>
</body>
</html>