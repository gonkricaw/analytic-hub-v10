<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'Dashboard') - {{ config('app.name', 'Analytics Hub') }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    
    <!-- Meta Tags -->
    <meta name="description" content="Analytics Hub - Comprehensive analytics dashboard">
    <meta name="keywords" content="analytics, dashboard, reports, data visualization">
    <meta name="author" content="Analytics Hub">
    
    <!-- Security Headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    
    <!-- Custom Theme Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        /* Additional layout-specific styles */
        .app-wrapper {
            display: flex;
            min-height: 100vh;
            background: var(--primary-bg);
        }
        
        /* Navigation Bar Styles */
        .navbar {
            background: var(--secondary-bg);
            border-bottom: 1px solid var(--border-color);
            height: var(--header-height);
            position: sticky;
            top: 0;
            z-index: var(--z-sticky);
            box-shadow: var(--shadow-md);
        }
        
        .navbar-brand {
            color: var(--text-primary) !important;
            font-weight: 600;
            font-size: 1.25rem;
            text-decoration: none;
            transition: color var(--transition-fast);
        }
        
        .navbar-brand:hover {
            color: var(--primary-color) !important;
        }
        
        .navbar-nav .nav-link {
            color: var(--text-secondary) !important;
            font-weight: 500;
            padding: 0.75rem 1rem !important;
            border-radius: var(--border-radius);
            transition: all var(--transition-fast);
            margin: 0 0.25rem;
        }
        
        .navbar-nav .nav-link:hover {
            color: var(--text-primary) !important;
            background: var(--hover-bg);
        }
        
        .navbar-nav .nav-link.active {
            color: var(--primary-color) !important;
            background: var(--active-bg);
        }
        
        .navbar-toggler {
            border: 1px solid var(--border-color);
            padding: 0.5rem;
        }
        
        .navbar-toggler:focus {
            box-shadow: 0 0 0 0.2rem rgba(255, 122, 0, 0.25);
        }
        
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.75%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
        
        /* Dropdown Menus */
        .dropdown-menu {
            background: var(--secondary-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            margin-top: 0.5rem;
        }
        
        .dropdown-item {
            color: var(--text-secondary);
            padding: 0.75rem 1rem;
            transition: all var(--transition-fast);
        }
        
        .dropdown-item:hover {
            background: var(--hover-bg);
            color: var(--text-primary);
        }
        
        .dropdown-divider {
            border-color: var(--border-color);
        }
        
        /* Main Content Area */
        .main-content {
            flex: 1;
            padding: 2rem;
            background: var(--primary-bg);
            min-height: calc(100vh - var(--header-height));
        }
        
        /* Page Header */
        .page-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .page-title {
            color: var(--text-primary);
            font-size: 2rem;
            font-weight: 600;
            margin: 0;
        }
        
        .page-subtitle {
            color: var(--text-secondary);
            font-size: 1rem;
            margin-top: 0.5rem;
        }
        
        /* Breadcrumb */
        .breadcrumb {
            background: transparent;
            padding: 0;
            margin: 0;
        }
        
        .breadcrumb-item {
            color: var(--text-secondary);
        }
        
        .breadcrumb-item.active {
            color: var(--text-primary);
        }
        
        .breadcrumb-item + .breadcrumb-item::before {
            color: var(--text-muted);
            content: "/";
        }
        
        .breadcrumb-item a {
            color: var(--primary-color);
            text-decoration: none;
            transition: color var(--transition-fast);
        }
        
        .breadcrumb-item a:hover {
            color: var(--text-primary);
        }
        
        /* Loading Spinner */
        .loading-spinner {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(26, 26, 58, 0.9);
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
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid var(--accent-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Layout Structure */
        .app-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--darker-bg);
            border-right: 1px solid #333;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            overflow-y: auto;
            transition: transform 0.3s ease;
            z-index: 1000;
        }
        
        .sidebar.collapsed {
            transform: translateX(-100%);
        }
        
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid #333;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }
        
        .sidebar-brand {
            display: flex;
            align-items: center;
            color: white;
            text-decoration: none;
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        .sidebar-brand i {
            margin-right: 0.75rem;
            font-size: 1.5rem;
        }
        
        .sidebar-nav {
            padding: 1rem 0;
        }
        
        .nav-section {
            margin-bottom: 2rem;
        }
        
        .nav-section-title {
            padding: 0.5rem 1.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            color: #888;
            letter-spacing: 0.5px;
        }
        
        .nav-item {
            margin-bottom: 0.25rem;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: #ccc;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left-color: var(--accent-color);
        }
        
        .nav-link.active {
            background: rgba(255, 122, 0, 0.2);
            color: var(--accent-color);
            border-left-color: var(--accent-color);
        }
        
        .nav-link i {
            margin-right: 0.75rem;
            width: 1.25rem;
            text-align: center;
        }
        
        .nav-badge {
            margin-left: auto;
            font-size: 0.75rem;
        }
        
        /* Submenu Styles */
        .nav-submenu {
            background: rgba(0, 0, 0, 0.2);
            border-left: 2px solid #444;
            margin-left: 1.5rem;
        }
        
        .nav-sublink {
            display: block;
            padding: 0.5rem 1rem;
            color: #aaa;
            text-decoration: none;
            font-size: 0.875rem;
            transition: all 0.3s ease;
            border-left: 2px solid transparent;
        }
        
        .nav-sublink:hover {
            background: rgba(255, 255, 255, 0.05);
            color: white;
            border-left-color: var(--accent-color);
        }
        
        .nav-sublink.active {
            background: rgba(255, 122, 0, 0.15);
            color: var(--accent-color);
            border-left-color: var(--accent-color);
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: margin-left 0.3s ease;
        }
        
        .main-content.expanded {
            margin-left: 0;
        }
        
        /* Header */
        .main-header {
            height: var(--header-height);
            background: var(--darker-bg);
            border-bottom: 1px solid #333;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-left {
            display: flex;
            align-items: center;
        }
        
        .sidebar-toggle {
            background: none;
            border: none;
            color: #ccc;
            font-size: 1.25rem;
            margin-right: 1rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.375rem;
            transition: all 0.3s ease;
        }
        
        .sidebar-toggle:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .breadcrumb {
            background: none;
            margin: 0;
            padding: 0;
        }
        
        .breadcrumb-item {
            color: #888;
        }
        
        .breadcrumb-item.active {
            color: white;
        }
        
        .breadcrumb-item + .breadcrumb-item::before {
            color: #666;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-menu {
            position: relative;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .user-avatar:hover {
            transform: scale(1.05);
        }
        
        /* Content Area */
        .content-wrapper {
            padding: 2rem;
            min-height: calc(100vh - var(--header-height));
        }
        
        /* Custom Bootstrap Overrides */
        .card {
            border: 1px solid #333;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }
        
        .card-header {
            border-bottom: 1px solid #333;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            transform: translateY(-1px);
        }
        
        .btn-outline-primary {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .form-control,
        .form-select {
            background-color: var(--dark-bg);
            border-color: #444;
            color: white;
        }
        
        .form-control:focus,
        .form-select:focus {
            background-color: var(--dark-bg);
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.2rem rgba(255, 122, 0, 0.25);
            color: white;
        }
        
        .table-dark {
            --bs-table-bg: var(--dark-bg);
        }
        
        /* DataTables Dark Theme */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            color: #ccc;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: #ccc !important;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: var(--accent-color) !important;
            border-color: var(--accent-color) !important;
            color: white !important;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: var(--accent-color) !important;
            border-color: var(--accent-color) !important;
            color: white !important;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .content-wrapper {
                padding: 1rem;
            }
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
        
        *:focus {
            outline: 2px solid var(--accent-color);
            outline-offset: 2px;
        }
        
        /* Notification Bell Styles */
        .notification-bell {
            position: relative;
            display: inline-block;
        }
        
        .notification-bell-btn {
            background: none;
            border: none;
            color: #ccc;
            font-size: 1.25rem;
            padding: 0.5rem;
            border-radius: 0.375rem;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .notification-bell-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .notification-badge {
            position: absolute;
            top: 0;
            right: 0;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            font-size: 0.75rem;
            min-width: 1.25rem;
            height: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transform: translate(25%, -25%);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: translate(25%, -25%) scale(1); }
            50% { transform: translate(25%, -25%) scale(1.1); }
            100% { transform: translate(25%, -25%) scale(1); }
        }
        
        .notification-dropdown {
            width: 350px;
            max-height: 400px;
            overflow-y: auto;
            background: var(--darker-bg);
            border: 1px solid #333;
            border-radius: 0.5rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
        }
        
        .notification-dropdown-header {
            padding: 1rem;
            border-bottom: 1px solid #333;
            background: var(--dark-bg);
            border-radius: 0.5rem 0.5rem 0 0;
        }
        
        .notification-dropdown-body {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .notification-bell-item {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #333;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .notification-bell-item:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .notification-bell-item.unread {
            background: rgba(255, 122, 0, 0.1);
            border-left: 3px solid var(--accent-color);
        }
        
        .notification-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.875rem;
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-title {
            font-weight: 600;
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
            color: white;
        }
        
        .notification-message {
            font-size: 0.8rem;
            color: #ccc;
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }
        
        .notification-time {
            font-size: 0.75rem;
            color: #888;
        }
        
        .notification-actions {
            display: flex;
            gap: 0.25rem;
        }
        
        .notification-empty {
            padding: 2rem;
            text-align: center;
            color: #888;
        }
        
        .notification-empty i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .mark-read-btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
        
        /* Print Styles */
        @media print {
            .sidebar,
            .main-header {
                display: none;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            body {
                background: white;
                color: black;
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
    
    <div class="app-wrapper">
        <!-- Navigation Bar -->
        <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
            <div class="container-fluid">
                <!-- Brand -->
                <a class="navbar-brand" href="{{ route('dashboard') }}">
                    <i class="fas fa-chart-line me-2"></i>
                    {{ config('app.name', 'Analytics Hub') }}
                </a>

                <!-- Mobile Toggle Button -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Navigation Links -->
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                                <i class="fas fa-tachometer-alt me-1"></i>
                                Dashboard
                            </a>
                        </li>
                        @can('view_analytics')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('analytics.*') ? 'active' : '' }}" href="{{ route('analytics.index') }}">
                                <i class="fas fa-chart-bar me-1"></i>
                                Analytics
                            </a>
                        </li>
                        @endcan
                        @can('manage_users')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                                <i class="fas fa-users me-1"></i>
                                Users
                            </a>
                        </li>
                        @endcan
                        @can('manage_content')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('content.*') ? 'active' : '' }}" href="{{ route('content.index') }}">
                                <i class="fas fa-file-alt me-1"></i>
                                Content
                            </a>
                        </li>
                        @endcan
                        @can('manage_system')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('system.*') ? 'active' : '' }}" href="{{ route('system.index') }}">
                                <i class="fas fa-cogs me-1"></i>
                                System
                            </a>
                        </li>
                        @endcan
                    </ul>

                    <!-- Right Side Navigation -->
                    <ul class="navbar-nav">
                        <!-- Notifications -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-bell"></i>
                                <span class="badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle" id="notification-count">0</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown">
                                <li><h6 class="dropdown-header">Notifications</h6></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#">No new notifications</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-center" href="{{ route('notifications.index') }}">View All</a></li>
                            </ul>
                        </li>

                        <!-- User Menu -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="{{ Auth::user()->avatar ?? asset('images/default-avatar.png') }}" alt="User Avatar" class="rounded-circle me-1" width="24" height="24">
                                {{ Auth::user()->name }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><h6 class="dropdown-header">{{ Auth::user()->email }}</h6></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="{{ route('profile.show') }}">
                                    <i class="fas fa-user me-2"></i>Profile
                                </a></li>
                                <li><a class="dropdown-item" href="{{ route('profile.edit') }}">
                                    <i class="fas fa-cog me-2"></i>Settings
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="dropdown-item">
                                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        
        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="{{ route('dashboard') }}" class="sidebar-brand">
                    <i class="fas fa-chart-line"></i>
                    Analytics Hub
                </a>
            </div>
            
            <div class="sidebar-nav">
                @if(isset($navigationMenu) && $navigationMenu->isNotEmpty())
                    @foreach($navigationMenu as $menu)
                        @if($menu->children && $menu->children->isNotEmpty())
                            <!-- Section with children -->
                            <div class="nav-section">
                                <div class="nav-section-title">{{ $menu->title }}</div>
                                @foreach($menu->children as $child)
                                    <div class="nav-item">
                                        <a href="{{ $child->url ?: '#' }}" 
                                           class="nav-link {{ $menuHelper::isMenuActive($child, request()) ? 'active' : '' }}">
                                            @if($child->icon)
                                                <i class="{{ $child->icon }}"></i>
                                            @endif
                                            {{ $child->title }}
                                        </a>
                                        @if($child->children && $child->children->isNotEmpty())
                                            <div class="nav-submenu">
                                                @foreach($child->children as $grandchild)
                                                    <a href="{{ $grandchild->url ?: '#' }}" 
                                                       class="nav-sublink {{ $menuHelper::isMenuActive($grandchild, request()) ? 'active' : '' }}">
                                                        {{ $grandchild->title }}
                                                    </a>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <!-- Single menu item -->
                            <div class="nav-item">
                                <a href="{{ $menu->url ?: '#' }}" 
                                   class="nav-link {{ $menuHelper::isMenuActive($menu, request()) ? 'active' : '' }}">
                                    @if($menu->icon)
                                        <i class="{{ $menu->icon }}"></i>
                                    @endif
                                    {{ $menu->title }}
                                </a>
                            </div>
                        @endif
                    @endforeach
                @else
                    <!-- Fallback navigation when no dynamic menu is available -->
                    <div class="nav-section">
                        <div class="nav-section-title">Main</div>
                        <div class="nav-item">
                            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                <i class="fas fa-tachometer-alt"></i>
                                Dashboard
                            </a>
                        </div>
                    </div>
                @endif

            </div>
        </nav>
        
        <!-- Main Content -->
        <main class="main-content" style="margin-top: var(--header-height);">
            <!-- Page Header -->
            @hasSection('title')
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="page-title">@yield('title', 'Dashboard')</h1>
                        @hasSection('subtitle')
                        <p class="page-subtitle">@yield('subtitle')</p>
                        @endif
                    </div>
                    @hasSection('page-actions')
                    <div class="page-actions">
                        @yield('page-actions')
                    </div>
                    @endif
                </div>
                
                @hasSection('breadcrumb')
                <nav aria-label="breadcrumb" class="mt-3">
                    <ol class="breadcrumb">
                        @yield('breadcrumb')
                    </ol>
                </nav>
                @endif
            </div>
            @endif

            <!-- Flash Messages -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('warning'))
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    {{ session('warning') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('info'))
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    {{ session('info') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Please fix the following errors:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Page Content -->
            <div class="page-content">
                @yield('content')
            </div>
        </main>
    </div>
    
    <!-- Skip Link for Accessibility -->
    <a href="#main-content" class="sr-only">Skip to main content</a>
    
    <!-- Scripts -->
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    
    <script>
        // CSRF Token Setup
        window.Laravel = {
            csrfToken: '{{ csrf_token() }}'
        };
        
        // jQuery CSRF Setup
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        
        document.addEventListener('DOMContentLoaded', function() {
            const loadingSpinner = document.getElementById('loadingSpinner');
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const sidebarToggle = document.getElementById('sidebarToggle');
            
            // Hide loading spinner after page load
            window.addEventListener('load', function() {
                setTimeout(() => {
                    loadingSpinner.classList.remove('show');
                }, 500);
            });
            
            // Sidebar toggle functionality
            sidebarToggle.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    sidebar.classList.toggle('show');
                } else {
                    sidebar.classList.toggle('collapsed');
                    mainContent.classList.toggle('expanded');
                }
            });
            
            // Close sidebar on mobile when clicking outside
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 768) {
                    if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                        sidebar.classList.remove('show');
                    }
                }
            });
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('show');
                }
            });
            
            // Show loading spinner on form submissions
            const forms = document.querySelectorAll('form:not([data-no-loading])');
            forms.forEach(form => {
                form.addEventListener('submit', function() {
                    loadingSpinner.classList.add('show');
                });
            });
            
            // Show loading spinner on navigation
            const links = document.querySelectorAll('a[href]:not([href^="#"]):not([href^="mailto:"]):not([href^="tel:"]):not([data-no-loading])');
            links.forEach(link => {
                link.addEventListener('click', function() {
                    if (!link.hasAttribute('data-bs-toggle')) {
                        loadingSpinner.classList.add('show');
                    }
                });
            });
        });
        
        // Error Handling
        window.addEventListener('error', function(e) {
            console.error('JavaScript Error:', e.error);
        });
        
        // Unhandled Promise Rejection
        window.addEventListener('unhandledrejection', function(e) {
            console.error('Unhandled Promise Rejection:', e.reason);
        });
        
        // Global AJAX Error Handler
        $(document).ajaxError(function(event, xhr, settings, thrownError) {
            console.error('AJAX Error:', {
                url: settings.url,
                status: xhr.status,
                error: thrownError,
                response: xhr.responseText
            });
            
            // Hide loading spinner on error
            $('#loadingSpinner').removeClass('show');
            
            // Show user-friendly error message
            if (xhr.status === 419) {
                alert('Your session has expired. Please refresh the page and try again.');
                location.reload();
            } else if (xhr.status >= 500) {
                alert('A server error occurred. Please try again later.');
            }
        });
    </script>
    
    @stack('scripts')
</body>
</html>