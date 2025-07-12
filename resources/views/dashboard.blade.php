@extends('layouts.app')

@section('title', 'Dashboard')

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
        <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
    </ol>
</nav>
@endsection

@push('scripts')
<script>
    // Initialize dashboard widgets
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize charts if they exist
        if (typeof initializeCharts === 'function') {
            initializeCharts();
        }
        
        // Initialize calendar if it exists
        if (typeof initializeCalendar === 'function') {
            initializeCalendar();
        }
        
        // Initialize task progress if it exists
        if (typeof initializeTaskProgress === 'function') {
            initializeTaskProgress();
        }
        
        // Auto-refresh system status every 30 seconds
        setInterval(function() {
            if (typeof refreshSystemStatus === 'function') {
                refreshSystemStatus();
            }
        }, 30000);
        
        // Auto-refresh recent activity every 60 seconds
        setInterval(function() {
            if (typeof refreshRecentActivity === 'function') {
                refreshRecentActivity();
            }
        }, 60000);
    });
</script>
@endpush

@section('content')
<!-- Include Widget Styles and Scripts -->
<link href="{{ asset('css/widgets.css') }}" rel="stylesheet">
<script src="{{ asset('js/widgets.js') }}" defer></script>

<style>
    .welcome-card {
        background: linear-gradient(135deg, #FF7A00 0%, #e66a00 100%);
        color: white;
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(255, 122, 0, 0.3);
        position: relative;
        overflow: hidden;
    }
    
    .welcome-card::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        transform: rotate(45deg);
    }
    
    .welcome-card h2 {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 10px;
        position: relative;
        z-index: 1;
    }
    
    .welcome-card p {
        font-size: 1.1rem;
        opacity: 0.9;
        margin-bottom: 0;
        position: relative;
        z-index: 1;
    }
    
    .dashboard-section {
        margin-bottom: 30px;
    }
    
    .section-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .section-title i {
        color: #FF7A00;
    }
</style>
<!-- Welcome Card -->
<div class="welcome-card">
    <h2><i class="fas fa-tachometer-alt me-2"></i>Welcome to Analytics Hub</h2>
    <p>Hello, {{ auth()->user()->name }}! Here's your dashboard overview.</p>
</div>

<!-- Dashboard Widget Grid -->
<x-widget-grid>
    <!-- Image Banner Widget (Full Width) -->
    <x-widgets.image-banner 
        :size="'col-12'" 
        :refresh-interval="600" 
        :permission="null" 
    />
    
    <!-- Marquee Text Widget (Full Width) -->
    <x-widgets.marquee-text 
        :size="'col-12'" 
        :refresh-interval="300" 
        :permission="null" 
    />
    
    <!-- Digital Clock Widget -->
    <x-widgets.digital-clock 
        :size="'col-lg-3 col-md-6'" 
        :refresh-interval="1" 
        :permission="null" 
    />
    
    <!-- Online Users Widget -->
    <x-widgets.online-users 
        :size="'col-lg-3 col-md-6'" 
        :refresh-interval="30" 
        :permission="null" 
    />
    
    <!-- Top Active Users Widget -->
    <x-widgets.top-active-users 
        :size="'col-lg-3 col-md-6'" 
        :refresh-interval="300" 
        :permission="'view_users'" 
    />
    
    <!-- New Users Widget -->
    <x-widgets.new-users 
        :size="'col-lg-3 col-md-6'" 
        :refresh-interval="300" 
        :permission="'view_users'" 
    />
    
    <!-- Login Activity Chart Widget -->
    <x-widgets.login-activity-chart 
        :size="'col-lg-8'" 
        :refresh-interval="300" 
        :permission="'view_analytics'" 
    />
    
    <!-- Latest Announcements Widget -->
    <x-widgets.latest-announcements 
        :size="'col-lg-4'" 
        :refresh-interval="120" 
        :permission="null" 
    />
    
    <!-- Popular Content Widget -->
    <x-widgets.popular-content 
        :size="'col-lg-6'" 
        :refresh-interval="300" 
        :permission="'view_content'" 
    />
    
    <!-- System Status Widget -->
    <div class="col-lg-6">
        <x-widget-container 
            title="System Status" 
            icon="fas fa-server" 
            id="system-status-widget" 
            :refresh-interval="60" 
            :refreshable="true" 
            :permission="'view_system_status'" 
            :size="'col-12'"
        >
            <div class="system-status-container p-3">
                <div class="status-item d-flex justify-content-between align-items-center mb-3">
                    <span><i class="fas fa-database me-2"></i>Database</span>
                    <span class="badge bg-success">Online</span>
                </div>
                <div class="status-item d-flex justify-content-between align-items-center mb-3">
                    <span><i class="fas fa-memory me-2"></i>Cache</span>
                    <span class="badge bg-success">Active</span>
                </div>
                <div class="status-item d-flex justify-content-between align-items-center mb-3">
                    <span><i class="fas fa-tasks me-2"></i>Queue</span>
                    <span class="badge bg-success">Running</span>
                </div>
                <div class="status-item d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-hdd me-2"></i>Storage</span>
                    <span class="badge bg-warning">75% Used</span>
                </div>
            </div>
        </x-widget-container>
    </div>
</x-widget-grid>
@endsection