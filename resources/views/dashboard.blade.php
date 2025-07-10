@extends('layouts.app')

@section('title', 'Dashboard')

@section('breadcrumb')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@push('styles')
<style>
    .welcome-card {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
        border: none;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: var(--dark-bg);
        border: 1px solid #333;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
    }
    
    .stat-number {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: var(--accent-color);
    }
    
    .stat-label {
        font-size: 1rem;
        color: #ccc;
    }
    
    .activity-item {
        padding: 1rem 0;
        border-bottom: 1px solid #333;
    }
    
    .activity-item:last-child {
        border-bottom: none;
    }
    
    .activity-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--accent-color);
        color: white;
        margin-right: 1rem;
    }
    
    .quick-action-card {
        background: var(--dark-bg);
        border: 1px solid #333;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .quick-action-card:hover {
        border-color: var(--accent-color);
        transform: translateY(-2px);
    }
    
    .chart-container {
        background: var(--dark-bg);
        border: 1px solid #333;
        border-radius: 0.5rem;
        padding: 1.5rem;
    }
</style>
@endpush

@section('content')
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
                <small class="badge bg-success">Active</small>
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
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-history me-2"></i>
                    Recent Activity
                </h5>
            </div>
            <div class="card-body">
                <div class="activity-item d-flex align-items-center">
                    <div class="activity-icon">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                    <div class="flex-grow-1">
                        <strong>User Login</strong>
                        <p class="mb-1 text-muted">{{ auth()->user()->name }} logged in successfully</p>
                        <small class="text-muted">
                            <i class="fas fa-clock me-1"></i>
                            {{ now()->format('M d, Y H:i') }}
                        </small>
                    </div>
                </div>
                
                <div class="activity-item d-flex align-items-center">
                    <div class="activity-icon">
                        <i class="fas fa-sync-alt"></i>
                    </div>
                    <div class="flex-grow-1">
                        <strong>System Update</strong>
                        <p class="mb-1 text-muted">Analytics Hub updated to version 1.0.0</p>
                        <small class="text-muted">
                            <i class="fas fa-clock me-1"></i>
                            {{ now()->subHours(2)->format('M d, Y H:i') }}
                        </small>
                    </div>
                </div>
                
                <div class="activity-item d-flex align-items-center">
                    <div class="activity-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="flex-grow-1">
                        <strong>Security Scan</strong>
                        <p class="mb-1 text-muted">Automated security scan completed successfully</p>
                        <small class="text-muted">
                            <i class="fas fa-clock me-1"></i>
                            {{ now()->subHours(6)->format('M d, Y H:i') }}
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-bolt me-2"></i>
                    Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <div class="card quick-action-card">
                        <div class="card-body text-center py-3">
                            <i class="fas fa-plus fa-2x mb-2"></i>
                            <h6 class="mb-0">Create Report</h6>
                        </div>
                    </div>
                    <div class="card quick-action-card">
                        <div class="card-body text-center py-3">
                            <i class="fas fa-download fa-2x mb-2"></i>
                            <h6 class="mb-0">Export Data</h6>
                        </div>
                    </div>
                    <div class="card quick-action-card">
                        <div class="card-body text-center py-3">
                            <i class="fas fa-cog fa-2x mb-2"></i>
                            <h6 class="mb-0">Settings</h6>
                        </div>
                    </div>
                    <div class="card quick-action-card">
                        <div class="card-body text-center py-3">
                            <i class="fas fa-question-circle fa-2x mb-2"></i>
                            <h6 class="mb-0">Help & Support</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- System Status -->
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-server me-2"></i>
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
@endsection