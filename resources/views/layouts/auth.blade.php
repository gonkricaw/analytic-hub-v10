<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'Authentication') - {{ config('app.name', 'Analytics Hub') }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    
    <!-- Meta Tags -->
    <meta name="description" content="Analytics Hub - Secure authentication portal">
    <meta name="keywords" content="analytics, dashboard, authentication, login">
    <meta name="author" content="Analytics Hub">
    
    <!-- Security Headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Figtree', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            line-height: 1.6;
            color: #333;
            overflow-x: hidden;
        }
        
        /* Loading Spinner */
        .loading-spinner {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .loading-spinner.show {
            opacity: 1;
            visibility: visible;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Background Animation */
        .auth-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            z-index: -1;
        }
        
        .auth-background::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="%23ffffff" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>') repeat;
            opacity: 0.3;
        }
        
        /* Floating Elements */
        .floating-elements {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }
        
        .floating-element {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-element:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .floating-element:nth-child(2) {
            width: 60px;
            height: 60px;
            top: 60%;
            right: 10%;
            animation-delay: 2s;
        }
        
        .floating-element:nth-child(3) {
            width: 40px;
            height: 40px;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
                opacity: 0.7;
            }
            50% {
                transform: translateY(-20px) rotate(180deg);
                opacity: 1;
            }
        }
        
        /* Main Content */
        .auth-main {
            position: relative;
            z-index: 1;
        }
        
        /* Error Handling */
        .error-boundary {
            padding: 20px;
            background: #f8d7da;
            color: #721c24;
            border-radius: 8px;
            margin: 20px;
            text-align: center;
        }
        
        /* Accessibility */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        
        /* Focus Styles */
        *:focus {
            outline: 2px solid #667eea;
            outline-offset: 2px;
        }
        
        /* Print Styles */
        @media print {
            .auth-background,
            .floating-elements {
                display: none;
            }
            
            body {
                background: white;
                color: black;
            }
        }
        
        /* High Contrast Mode */
        @media (prefers-contrast: high) {
            body {
                background: #000;
                color: #fff;
            }
        }
        
        /* Reduced Motion */
        @media (prefers-reduced-motion: reduce) {
            .floating-element,
            .spinner {
                animation: none;
            }
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <!-- Loading Spinner -->
    <div class="loading-spinner" id="loadingSpinner">
        <div class="spinner"></div>
    </div>
    
    <!-- Background -->
    <div class="auth-background"></div>
    
    <!-- Floating Elements -->
    <div class="floating-elements">
        <div class="floating-element"></div>
        <div class="floating-element"></div>
        <div class="floating-element"></div>
    </div>
    
    <!-- Main Content -->
    <main class="auth-main" role="main">
        @yield('content')
    </main>
    
    <!-- Skip Link for Accessibility -->
    <a href="#main-content" class="sr-only">Skip to main content</a>
    
    <!-- Scripts -->
    <script>
        // CSRF Token Setup
        window.Laravel = {
            csrfToken: '{{ csrf_token() }}'
        };
        
        // Loading State Management
        document.addEventListener('DOMContentLoaded', function() {
            const loadingSpinner = document.getElementById('loadingSpinner');
            
            // Hide loading spinner after page load
            window.addEventListener('load', function() {
                setTimeout(() => {
                    loadingSpinner.classList.remove('show');
                }, 500);
            });
            
            // Show loading spinner on form submissions
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function() {
                    loadingSpinner.classList.add('show');
                });
            });
            
            // Show loading spinner on navigation
            const links = document.querySelectorAll('a[href]:not([href^="#"]):not([href^="mailto:"]):not([href^="tel:"])');
            links.forEach(link => {
                link.addEventListener('click', function() {
                    loadingSpinner.classList.add('show');
                });
            });
        });
        
        // Error Handling
        window.addEventListener('error', function(e) {
            console.error('JavaScript Error:', e.error);
            // You can add error reporting here
        });
        
        // Unhandled Promise Rejection
        window.addEventListener('unhandledrejection', function(e) {
            console.error('Unhandled Promise Rejection:', e.reason);
            // You can add error reporting here
        });
        
        // Security: Disable right-click context menu (optional)
        // document.addEventListener('contextmenu', function(e) {
        //     e.preventDefault();
        // });
        
        // Security: Disable F12 and other developer tools shortcuts (optional)
        // document.addEventListener('keydown', function(e) {
        //     if (e.key === 'F12' || 
        //         (e.ctrlKey && e.shiftKey && e.key === 'I') ||
        //         (e.ctrlKey && e.shiftKey && e.key === 'C') ||
        //         (e.ctrlKey && e.shiftKey && e.key === 'J')) {
        //         e.preventDefault();
        //     }
        // });
    </script>
    
    @stack('scripts')
</body>
</html>