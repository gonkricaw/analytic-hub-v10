<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Analytics Hub') }} - Dashboard</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .stat-card .card-body {
            padding: 2rem;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .stat-label {
            font-size: 1rem;
            opacity: 0.9;
        }
        .welcome-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            margin-bottom: 2rem;
        }
        .btn-logout {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            transition: all 0.3s ease;
        }
        .btn-logout:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
        }
        .activity-item {
            padding: 1rem;
            border-left: 4px solid #667eea;
            margin-bottom: 1rem;
            background: white;
            border-radius: 0 10px 10px 0;
        }
        .activity-time {
            font-size: 0.875rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="{{ route('dashboard') }}">
                <i class="fas fa-chart-line me-2"></i>
                Analytics Hub
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-2"></i>
                        {{ auth()->user()->name }}
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('profile.show') }}">
                            <i class="fas fa-user me-2"></i>Profile
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="container mt-4">
        <!-- Welcome Card -->
        <div class="card welcome-card">
            <div class="card-body text-center py-5">
                <h1 class="display-4 mb-3">
                    <i class="fas fa-tachometer-alt me-3"></i>
                    Welcome to Analytics Hub
                </h1>
                <p class="lead mb-4">Hello, {{ auth()->user()->name }}! Here's your dashboard overview.</p>
                <div class="row text-center">
                    <div class="col-md-4">
                        <i class="fas fa-clock fa-2x mb-2"></i>
                        <p class="mb-0">Last Login</p>
                        <small>{{ auth()->user()->last_seen_at ? auth()->user()->last_seen_at->format('M d, Y H:i') : 'First time' }}</small>
                    </div>
                    <div class="col-md-4">
                        <i class="fas fa-shield-alt fa-2x mb-2"></i>
                        <p class="mb-0">Account Status</p>
                        <small class="badge bg-success">{{ ucfirst(auth()->user()->status) }}</small>
                    </div>
                    <div class="col-md-4">
                        <i class="fas fa-envelope fa-2x mb-2"></i>
                        <p class="mb-0">Email Status</p>
                        <small class="badge {{ auth()->user()->email_verified_at ? 'bg-success' : 'bg-warning' }}">
                            {{ auth()->user()->email_verified_at ? 'Verified' : 'Pending' }}
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-3x mb-3 opacity-75"></i>
                        <div class="stat-number">1,234</div>
                        <div class="stat-label">Total Users</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-chart-bar fa-3x mb-3 opacity-75"></i>
                        <div class="stat-number">5,678</div>
                        <div class="stat-label">Analytics Reports</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-eye fa-3x mb-3 opacity-75"></i>
                        <div class="stat-number">98,765</div>
                        <div class="stat-label">Page Views</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-3x mb-3 opacity-75"></i>
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">Uptime</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Content Row -->
        <div class="row">
            <!-- Recent Activity -->
            <div class="col-md-8 mb-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history me-2 text-primary"></i>
                            Recent Activity
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="activity-item">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong>User Login</strong>
                                    <p class="mb-1">{{ auth()->user()->name }} logged in successfully</p>
                                    <small class="activity-time">
                                        <i class="fas fa-clock me-1"></i>
                                        {{ now()->format('M d, Y H:i') }}
                                    </small>
                                </div>
                                <div class="text-success">
                                    <i class="fas fa-check-circle fa-lg"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="activity-item">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong>System Update</strong>
                                    <p class="mb-1">Analytics Hub updated to version 1.0.0</p>
                                    <small class="activity-time">
                                        <i class="fas fa-clock me-1"></i>
                                        {{ now()->subHours(2)->format('M d, Y H:i') }}
                                    </small>
                                </div>
                                <div class="text-info">
                                    <i class="fas fa-info-circle fa-lg"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="activity-item">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong>Security Scan</strong>
                                    <p class="mb-1">Automated security scan completed successfully</p>
                                    <small class="activity-time">
                                        <i class="fas fa-clock me-1"></i>
                                        {{ now()->subHours(6)->format('M d, Y H:i') }}
                                    </small>
                                </div>
                                <div class="text-success">
                                    <i class="fas fa-shield-alt fa-lg"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-bolt me-2 text-warning"></i>
                            Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-primary">
                                <i class="fas fa-plus me-2"></i>
                                Create Report
                            </button>
                            <button class="btn btn-outline-success">
                                <i class="fas fa-download me-2"></i>
                                Export Data
                            </button>
                            <button class="btn btn-outline-info">
                                <i class="fas fa-cog me-2"></i>
                                Settings
                            </button>
                            <button class="btn btn-outline-warning">
                                <i class="fas fa-question-circle me-2"></i>
                                Help & Support
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- System Status -->
                <div class="card mt-3">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-server me-2 text-success"></i>
                            System Status
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Database</span>
                            <span class="badge bg-success">Online</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Cache</span>
                            <span class="badge bg-success">Active</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Queue</span>
                            <span class="badge bg-success">Running</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Storage</span>
                            <span class="badge bg-warning">75% Used</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>