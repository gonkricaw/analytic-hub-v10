@extends('layouts.app')

@section('title', 'Notification Center')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/notifications.css') }}">
@endpush

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-bell"></i> Notifications
            </h1>
            <p class="mb-0 text-muted">Your notification center</p>
        </div>
        <div>
            <button class="btn btn-outline-primary me-2" onclick="markAllAsRead()">
                <i class="fas fa-check-double"></i> Mark All Read
            </button>
            <button class="btn btn-outline-secondary" onclick="refreshNotifications()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Notification Stats -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Notifications
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-count">{{ $stats['total'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-bell fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Unread
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="unread-count">{{ $stats['unread'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-envelope fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Read
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="read-count">{{ $stats['read'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-envelope-open fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                High Priority
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="high-priority-count">{{ $stats['high_priority'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-filter"></i> Filters
            </h6>
        </div>
        <div class="card-body">
            <form id="filterForm">
                <div class="row">
                    <div class="col-md-3">
                        <label for="status_filter" class="form-label">Status</label>
                        <select class="form-select" id="status_filter" name="status">
                            <option value="">All Status</option>
                            <option value="unread">Unread</option>
                            <option value="read">Read</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="type_filter" class="form-label">Type</label>
                        <select class="form-select" id="type_filter" name="type">
                            <option value="">All Types</option>
                            <option value="system">System</option>
                            <option value="announcement">Announcement</option>
                            <option value="alert">Alert</option>
                            <option value="info">Information</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="priority_filter" class="form-label">Priority</label>
                        <select class="form-select" id="priority_filter" name="priority">
                            <option value="">All Priorities</option>
                            <option value="high">High</option>
                            <option value="normal">Normal</option>
                            <option value="low">Low</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="category_filter" class="form-label">Category</label>
                        <select class="form-select" id="category_filter" name="category">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category }}">{{ $category }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <button type="button" class="btn btn-primary" onclick="applyFilters()">
                            <i class="fas fa-search"></i> Apply Filters
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="clearFilters()">
                            <i class="fas fa-times"></i> Clear
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Notifications List -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list"></i> Your Notifications
            </h6>
        </div>
        <div class="card-body">
            <div id="notifications-container">
                <div class="text-center py-4">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading notifications...</p>
                </div>
            </div>
            
            <!-- Load More Button -->
            <div class="text-center mt-4" id="load-more-container" style="display: none;">
                <button class="btn btn-outline-primary" id="load-more-btn" onclick="loadMoreNotifications()">
                    <i class="fas fa-plus"></i> Load More
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Notification Detail Modal -->
<div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="notificationModalLabel">
                    <i class="fas fa-bell"></i> Notification Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="notificationModalBody">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="markAsReadBtn" onclick="markAsRead()" style="display: none;">
                    <i class="fas fa-check"></i> Mark as Read
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.notification-item {
    border-left: 4px solid #e3e6f0;
    transition: all 0.3s ease;
}

.notification-item:hover {
    background-color: #f8f9fc;
    transform: translateX(2px);
}

.notification-item.unread {
    border-left-color: #4e73df;
    background-color: #f8f9fc;
}

.notification-item.high-priority {
    border-left-color: #e74a3b;
}

.notification-item.normal-priority {
    border-left-color: #36b9cc;
}

.notification-item.low-priority {
    border-left-color: #f6c23e;
}

.notification-actions {
    opacity: 0;
    transition: opacity 0.3s ease;
}

.notification-item:hover .notification-actions {
    opacity: 1;
}

.notification-time {
    font-size: 0.8rem;
    color: #858796;
}

.notification-type-badge {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
}

.priority-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 5px;
}

.priority-high { background-color: #e74a3b; }
.priority-normal { background-color: #36b9cc; }
.priority-low { background-color: #f6c23e; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
let currentPage = 1;
let isLoading = false;
let hasMorePages = true;
let currentNotificationId = null;

$(document).ready(function() {
    loadNotifications();
    
    // Auto-refresh every 30 seconds
    setInterval(function() {
        refreshStats();
    }, 30000);
});

function loadNotifications(page = 1, append = false) {
    if (isLoading) return;
    
    isLoading = true;
    
    const filters = {
        status: $('#status_filter').val(),
        type: $('#type_filter').val(),
        priority: $('#priority_filter').val(),
        category: $('#category_filter').val(),
        page: page
    };
    
    $.ajax({
        url: '{{ route("user.notifications.get") }}',
        data: filters,
        success: function(response) {
            if (append) {
                $('#notifications-container').append(response.html);
            } else {
                $('#notifications-container').html(response.html);
            }
            
            hasMorePages = response.has_more;
            currentPage = page;
            
            if (hasMorePages) {
                $('#load-more-container').show();
            } else {
                $('#load-more-container').hide();
            }
            
            updateStats(response.stats);
        },
        error: function() {
            $('#notifications-container').html('<div class="alert alert-danger">Failed to load notifications.</div>');
        },
        complete: function() {
            isLoading = false;
        }
    });
}

function loadMoreNotifications() {
    if (hasMorePages && !isLoading) {
        loadNotifications(currentPage + 1, true);
    }
}

function refreshNotifications() {
    currentPage = 1;
    loadNotifications();
}

function applyFilters() {
    currentPage = 1;
    loadNotifications();
}

function clearFilters() {
    $('#filterForm')[0].reset();
    applyFilters();
}

function showNotification(notificationId) {
    currentNotificationId = notificationId;
    
    $.ajax({
        url: '{{ route("user.notifications.show", ":id") }}'.replace(':id', notificationId),
        success: function(response) {
            $('#notificationModalBody').html(response.html);
            
            if (!response.is_read) {
                $('#markAsReadBtn').show();
            } else {
                $('#markAsReadBtn').hide();
            }
            
            $('#notificationModal').modal('show');
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load notification details.'
            });
        }
    });
}

function markAsRead(notificationId = null) {
    const id = notificationId || currentNotificationId;
    
    if (!id) return;
    
    $.ajax({
        url: '{{ route("user.notifications.mark-read", ":id") }}'.replace(':id', id),
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                // Update the notification item
                const $item = $(`.notification-item[data-id="${id}"]`);
                $item.removeClass('unread').addClass('read');
                $item.find('.unread-indicator').remove();
                
                // Update stats
                refreshStats();
                
                // Hide mark as read button in modal
                $('#markAsReadBtn').hide();
                
                // Update notification bell
                updateNotificationBell();
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to mark notification as read.'
            });
        }
    });
}

function markAllAsRead() {
    Swal.fire({
        title: 'Mark All as Read?',
        text: 'This will mark all your notifications as read.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, mark all as read'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ route("user.notifications.mark-all-read") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        // Update all notification items
                        $('.notification-item.unread').removeClass('unread').addClass('read');
                        $('.unread-indicator').remove();
                        
                        // Refresh stats
                        refreshStats();
                        
                        // Update notification bell
                        updateNotificationBell();
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'All notifications marked as read.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to mark all notifications as read.'
                    });
                }
            });
        }
    });
}

function dismissNotification(notificationId) {
    Swal.fire({
        title: 'Dismiss Notification?',
        text: 'This notification will be removed from your list.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f6c23e',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, dismiss it'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ route("user.notifications.dismiss", ":id") }}'.replace(':id', notificationId),
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        // Remove the notification item
                        $(`.notification-item[data-id="${notificationId}"]`).fadeOut(300, function() {
                            $(this).remove();
                        });
                        
                        // Refresh stats
                        refreshStats();
                        
                        // Update notification bell
                        updateNotificationBell();
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Dismissed',
                            text: 'Notification has been dismissed.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to dismiss notification.'
                    });
                }
            });
        }
    });
}

function refreshStats() {
    $.ajax({
        url: '{{ route("user.notifications.stats") }}',
        success: function(response) {
            updateStats(response);
        }
    });
}

function updateStats(stats) {
    $('#total-count').text(stats.total || 0);
    $('#unread-count').text(stats.unread || 0);
    $('#read-count').text(stats.read || 0);
    $('#high-priority-count').text(stats.high_priority || 0);
}

function updateNotificationBell() {
    // Update the notification bell in the main navigation
    $.ajax({
        url: '{{ route("api.notifications.unread-count") }}',
        success: function(response) {
            const count = response.count || 0;
            const $bell = $('.notification-bell');
            const $badge = $bell.find('.notification-badge');
            
            if (count > 0) {
                $badge.text(count > 99 ? '99+' : count).show();
            } else {
                $badge.hide();
            }
        }
    });
}
</script>
@endpush