/**
 * Email Queue Management JavaScript
 * 
 * Handles all frontend interactions for email queue monitoring and management.
 * Includes DataTables, charts, AJAX operations, and modal management.
 * 
 * @package Analytics Hub
 * @version 1.0.0
 */

(function($) {
    'use strict';

    // Global variables
    let emailQueueTable;
    let emailTrendsChart;
    let emailTypesChart;
    let selectedEmails = [];

    /**
     * Initialize the email queue management interface
     */
    function initEmailQueue() {
        initDataTable();
        initCharts();
        initEventHandlers();
        loadEmailTemplates();
        refreshStatistics();
        
        // Auto-refresh every 30 seconds
        setInterval(function() {
            if (emailQueueTable) {
                emailQueueTable.ajax.reload(null, false);
            }
            refreshStatistics();
        }, 30000);
    }

    /**
     * Initialize DataTable for email queue
     */
    function initDataTable() {
        emailQueueTable = $('#emailQueueTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/admin/email-queue/data',
                data: function(d) {
                    // Add filter parameters
                    d.status = $('#statusFilter').val();
                    d.email_type = $('#typeFilter').val();
                    d.priority = $('#priorityFilter').val();
                    d.date_from = $('#dateFromFilter').val();
                    d.date_to = $('#dateToFilter').val();
                }
            },
            columns: [
                {
                    data: 'id',
                    name: 'id',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return '<input type="checkbox" class="email-checkbox" value="' + data + '">';
                    }
                },
                {
                    data: 'recipient',
                    name: 'to_email',
                    orderable: false
                },
                {
                    data: 'subject',
                    name: 'subject',
                    render: function(data, type, row) {
                        if (data && data.length > 50) {
                            return '<span title="' + data + '">' + data.substring(0, 50) + '...</span>';
                        }
                        return data || '-';
                    }
                },
                {
                    data: 'template',
                    name: 'template.display_name',
                    orderable: false
                },
                {
                    data: 'status_badge',
                    name: 'status',
                    orderable: false
                },
                {
                    data: 'priority_badge',
                    name: 'priority',
                    orderable: false
                },
                {
                    data: 'attempts_info',
                    name: 'attempts',
                    orderable: false
                },
                {
                    data: 'timing',
                    name: 'created_at'
                },
                {
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false
                }
            ],
            order: [[7, 'desc']], // Order by created_at desc
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            responsive: true,
            language: {
                processing: '<div class="loading-spinner"></div>',
                emptyTable: 'No email records found',
                zeroRecords: 'No matching email records found'
            },
            drawCallback: function() {
                // Update bulk actions visibility
                updateBulkActions();
                
                // Initialize tooltips
                $('[title]').tooltip();
            }
        });
    }

    /**
     * Initialize charts
     */
    function initCharts() {
        initEmailTrendsChart();
        initEmailTypesChart();
    }

    /**
     * Initialize email trends chart
     */
    function initEmailTrendsChart() {
        const ctx = document.getElementById('emailTrendsChart');
        if (!ctx) return;

        emailTrendsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Sent',
                        data: [],
                        borderColor: '#1cc88a',
                        backgroundColor: 'rgba(28, 200, 138, 0.1)',
                        tension: 0.3
                    },
                    {
                        label: 'Failed',
                        data: [],
                        borderColor: '#e74a3b',
                        backgroundColor: 'rgba(231, 74, 59, 0.1)',
                        tension: 0.3
                    },
                    {
                        label: 'Queued',
                        data: [],
                        borderColor: '#f6c23e',
                        backgroundColor: 'rgba(246, 194, 62, 0.1)',
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    title: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });

        // Load chart data
        loadEmailTrendsData();
    }

    /**
     * Initialize email types chart
     */
    function initEmailTypesChart() {
        const ctx = document.getElementById('emailTypesChart');
        if (!ctx) return;

        emailTypesChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Transactional', 'Marketing', 'Notification', 'System'],
                datasets: [{
                    data: [0, 0, 0, 0],
                    backgroundColor: [
                        '#4e73df',
                        '#1cc88a',
                        '#36b9cc',
                        '#f6c23e'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Load chart data
        loadEmailTypesData();
    }

    /**
     * Initialize event handlers
     */
    function initEventHandlers() {
        // Select all checkbox
        $('#selectAll').on('change', function() {
            const isChecked = $(this).is(':checked');
            $('.email-checkbox').prop('checked', isChecked);
            updateSelectedEmails();
        });

        // Individual email checkboxes
        $(document).on('change', '.email-checkbox', function() {
            updateSelectedEmails();
            
            // Update select all checkbox
            const totalCheckboxes = $('.email-checkbox').length;
            const checkedCheckboxes = $('.email-checkbox:checked').length;
            
            $('#selectAll').prop('indeterminate', checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes);
            $('#selectAll').prop('checked', checkedCheckboxes === totalCheckboxes);
        });

        // Bulk email form
        $('#bulkEmailForm').on('submit', function(e) {
            e.preventDefault();
            sendBulkEmail();
        });

        // Cleanup form
        $('#cleanupForm').on('submit', function(e) {
            e.preventDefault();
            cleanupOldEmails();
        });

        // Filter form changes
        $('#filtersForm input, #filtersForm select').on('change', function() {
            // Auto-apply filters after a short delay
            clearTimeout(window.filterTimeout);
            window.filterTimeout = setTimeout(applyFilters, 500);
        });
    }

    /**
     * Load email templates for bulk email modal
     */
    function loadEmailTemplates() {
        $.get('/admin/email-templates/data', { active_only: true })
            .done(function(response) {
                const select = $('#bulkTemplateId');
                select.empty().append('<option value="">Select Template</option>');
                
                if (response.data && response.data.length > 0) {
                    response.data.forEach(function(template) {
                        select.append(`<option value="${template.id}">${template.display_name}</option>`);
                    });
                }
            })
            .fail(function() {
                showAlert('Failed to load email templates', 'error');
            });
    }

    /**
     * Load email trends chart data
     */
    function loadEmailTrendsData(period = '30days') {
        $.get('/admin/email-queue/statistics', { period: period })
            .done(function(response) {
                if (response.success && response.statistics.trends) {
                    const trends = response.statistics.trends;
                    
                    emailTrendsChart.data.labels = trends.labels || [];
                    emailTrendsChart.data.datasets[0].data = trends.sent || [];
                    emailTrendsChart.data.datasets[1].data = trends.failed || [];
                    emailTrendsChart.data.datasets[2].data = trends.queued || [];
                    
                    emailTrendsChart.update();
                }
            })
            .fail(function() {
                console.error('Failed to load email trends data');
            });
    }

    /**
     * Load email types chart data
     */
    function loadEmailTypesData() {
        $.get('/admin/email-queue/statistics')
            .done(function(response) {
                if (response.success && response.statistics.by_type) {
                    const byType = response.statistics.by_type;
                    
                    emailTypesChart.data.datasets[0].data = [
                        byType.transactional || 0,
                        byType.marketing || 0,
                        byType.notification || 0,
                        byType.system || 0
                    ];
                    
                    emailTypesChart.update();
                }
            })
            .fail(function() {
                console.error('Failed to load email types data');
            });
    }

    /**
     * Refresh statistics
     */
    function refreshStatistics() {
        $.get('/admin/email-queue/statistics')
            .done(function(response) {
                if (response.success && response.statistics) {
                    const stats = response.statistics;
                    
                    $('#total-emails').text(stats.total_emails || 0);
                    $('#sent-emails').text(stats.sent_emails || 0);
                    $('#queued-emails').text((stats.queued_emails || 0) + (stats.processing_emails || 0));
                    $('#failed-emails').text(stats.failed_emails || 0);
                }
            })
            .fail(function() {
                console.error('Failed to refresh statistics');
            });
    }

    /**
     * Update selected emails array
     */
    function updateSelectedEmails() {
        selectedEmails = [];
        $('.email-checkbox:checked').each(function() {
            selectedEmails.push($(this).val());
        });
        updateBulkActions();
    }

    /**
     * Update bulk actions visibility
     */
    function updateBulkActions() {
        const bulkActions = $('.bulk-actions');
        const selectedCount = selectedEmails.length;
        
        if (selectedCount > 0) {
            bulkActions.addClass('show');
            $('.selected-count').text(selectedCount);
        } else {
            bulkActions.removeClass('show');
        }
    }

    /**
     * Apply filters to the table
     */
    window.applyFilters = function() {
        if (emailQueueTable) {
            emailQueueTable.ajax.reload();
        }
    };

    /**
     * Clear all filters
     */
    window.clearFilters = function() {
        $('#filtersForm')[0].reset();
        applyFilters();
    };

    /**
     * View email details
     */
    window.viewEmail = function(emailId) {
        $.get(`/admin/email-queue/${emailId}`)
            .done(function(response) {
                if (response.success) {
                    displayEmailDetails(response.email);
                    $('#emailDetailsModal').modal('show');
                } else {
                    showAlert('Failed to load email details', 'error');
                }
            })
            .fail(function() {
                showAlert('Failed to load email details', 'error');
            });
    };

    /**
     * Display email details in modal
     */
    function displayEmailDetails(email) {
        const content = $('#emailDetailsContent');
        
        const html = `
            <div class="email-details">
                <div class="detail-row">
                    <div class="detail-label">Message ID:</div>
                    <div class="detail-value">${email.message_id || '-'}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Template:</div>
                    <div class="detail-value">${email.template ? email.template.display_name : '-'}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Subject:</div>
                    <div class="detail-value">${email.subject || '-'}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">To:</div>
                    <div class="detail-value">${email.to_name ? email.to_name + ' &lt;' + email.to_email + '&gt;' : email.to_email}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">From:</div>
                    <div class="detail-value">${email.from_name ? email.from_name + ' &lt;' + email.from_email + '&gt;' : email.from_email}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Status:</div>
                    <div class="detail-value"><span class="badge badge-${getStatusBadgeClass(email.status)}">${email.status.charAt(0).toUpperCase() + email.status.slice(1)}</span></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Priority:</div>
                    <div class="detail-value"><span class="badge badge-${getPriorityBadgeClass(email.priority)}">${email.priority.charAt(0).toUpperCase() + email.priority.slice(1)}</span></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Attempts:</div>
                    <div class="detail-value">${email.attempts}/${email.max_attempts}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Created:</div>
                    <div class="detail-value">${email.created_at}</div>
                </div>
                ${email.sent_at ? `
                <div class="detail-row">
                    <div class="detail-label">Sent:</div>
                    <div class="detail-value">${email.sent_at}</div>
                </div>
                ` : ''}
                ${email.failed_at ? `
                <div class="detail-row">
                    <div class="detail-label">Failed:</div>
                    <div class="detail-value">${email.failed_at}</div>
                </div>
                ` : ''}
                ${email.error_message ? `
                <div class="detail-row">
                    <div class="detail-label">Error:</div>
                    <div class="detail-value text-danger">${email.error_message}</div>
                </div>
                ` : ''}
                ${email.html_body ? `
                <div class="detail-row">
                    <div class="detail-label">HTML Content:</div>
                    <div class="detail-value">
                        <div class="email-content">${email.html_body}</div>
                    </div>
                </div>
                ` : ''}
                ${email.text_body ? `
                <div class="detail-row">
                    <div class="detail-label">Text Content:</div>
                    <div class="detail-value">
                        <div class="email-content"><pre>${email.text_body}</pre></div>
                    </div>
                </div>
                ` : ''}
            </div>
        `;
        
        content.html(html);
    }

    /**
     * Retry single email
     */
    window.retryEmail = function(emailId) {
        if (!confirm('Are you sure you want to retry this email?')) {
            return;
        }
        
        $.post('/admin/email-queue/retry', {
            email_ids: [emailId],
            _token: $('meta[name="csrf-token"]').attr('content')
        })
        .done(function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                emailQueueTable.ajax.reload(null, false);
            } else {
                showAlert(response.message || 'Failed to retry email', 'error');
            }
        })
        .fail(function() {
            showAlert('Failed to retry email', 'error');
        });
    };

    /**
     * Cancel single email
     */
    window.cancelEmail = function(emailId) {
        if (!confirm('Are you sure you want to cancel this email?')) {
            return;
        }
        
        $.post('/admin/email-queue/cancel', {
            email_ids: [emailId],
            reason: 'Cancelled by administrator',
            _token: $('meta[name="csrf-token"]').attr('content')
        })
        .done(function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                emailQueueTable.ajax.reload(null, false);
            } else {
                showAlert(response.message || 'Failed to cancel email', 'error');
            }
        })
        .fail(function() {
            showAlert('Failed to cancel email', 'error');
        });
    };

    /**
     * Retry failed emails
     */
    window.retryFailedEmails = function() {
        if (!confirm('Are you sure you want to retry all failed emails?')) {
            return;
        }
        
        $.post('/admin/email-queue/retry', {
            _token: $('meta[name="csrf-token"]').attr('content')
        })
        .done(function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                emailQueueTable.ajax.reload(null, false);
                refreshStatistics();
            } else {
                showAlert(response.message || 'Failed to retry emails', 'error');
            }
        })
        .fail(function() {
            showAlert('Failed to retry emails', 'error');
        });
    };

    /**
     * Bulk retry selected emails
     */
    window.bulkRetryEmails = function() {
        if (selectedEmails.length === 0) {
            showAlert('Please select emails to retry', 'warning');
            return;
        }
        
        if (!confirm(`Are you sure you want to retry ${selectedEmails.length} selected email(s)?`)) {
            return;
        }
        
        $.post('/admin/email-queue/retry', {
            email_ids: selectedEmails,
            _token: $('meta[name="csrf-token"]').attr('content')
        })
        .done(function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                emailQueueTable.ajax.reload(null, false);
                refreshStatistics();
                
                // Clear selection
                $('.email-checkbox').prop('checked', false);
                $('#selectAll').prop('checked', false);
                updateSelectedEmails();
            } else {
                showAlert(response.message || 'Failed to retry emails', 'error');
            }
        })
        .fail(function() {
            showAlert('Failed to retry emails', 'error');
        });
    };

    /**
     * Bulk cancel selected emails
     */
    window.bulkCancelEmails = function() {
        if (selectedEmails.length === 0) {
            showAlert('Please select emails to cancel', 'warning');
            return;
        }
        
        if (!confirm(`Are you sure you want to cancel ${selectedEmails.length} selected email(s)?`)) {
            return;
        }
        
        $.post('/admin/email-queue/cancel', {
            email_ids: selectedEmails,
            reason: 'Bulk cancelled by administrator',
            _token: $('meta[name="csrf-token"]').attr('content')
        })
        .done(function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                emailQueueTable.ajax.reload(null, false);
                refreshStatistics();
                
                // Clear selection
                $('.email-checkbox').prop('checked', false);
                $('#selectAll').prop('checked', false);
                updateSelectedEmails();
            } else {
                showAlert(response.message || 'Failed to cancel emails', 'error');
            }
        })
        .fail(function() {
            showAlert('Failed to cancel emails', 'error');
        });
    };

    /**
     * Send bulk email
     */
    function sendBulkEmail() {
        const form = $('#bulkEmailForm');
        const formData = new FormData(form[0]);
        
        // Parse recipients
        const recipientsText = $('#bulkRecipients').val().trim();
        let recipients;
        
        try {
            // Try to parse as JSON first
            recipients = JSON.parse(recipientsText);
        } catch (e) {
            // Parse as line-separated emails
            recipients = recipientsText.split('\n')
                .map(line => line.trim())
                .filter(line => line.length > 0)
                .map(email => ({ email: email }));
        }
        
        if (!recipients || recipients.length === 0) {
            showAlert('Please provide at least one recipient', 'error');
            return;
        }
        
        // Add recipients to form data
        formData.delete('recipients');
        formData.append('recipients', JSON.stringify(recipients));
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
        
        $.ajax({
            url: '/admin/email-queue/send-bulk',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false
        })
        .done(function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                $('#bulkEmailModal').modal('hide');
                form[0].reset();
                emailQueueTable.ajax.reload(null, false);
                refreshStatistics();
            } else {
                showAlert(response.message || 'Failed to send bulk email', 'error');
            }
        })
        .fail(function() {
            showAlert('Failed to send bulk email', 'error');
        });
    }

    /**
     * Cleanup old emails
     */
    function cleanupOldEmails() {
        const daysOld = $('#cleanupDays').val();
        
        if (!confirm(`Are you sure you want to delete all email records older than ${daysOld} days? This action cannot be undone.`)) {
            return;
        }
        
        $.post('/admin/email-queue/cleanup', {
            days_old: daysOld,
            _token: $('meta[name="csrf-token"]').attr('content')
        })
        .done(function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                $('#cleanupModal').modal('hide');
                emailQueueTable.ajax.reload(null, false);
                refreshStatistics();
            } else {
                showAlert(response.message || 'Failed to cleanup emails', 'error');
            }
        })
        .fail(function() {
            showAlert('Failed to cleanup emails', 'error');
        });
    }

    /**
     * Update chart period
     */
    window.updateChart = function(period) {
        loadEmailTrendsData(period);
    };

    /**
     * Get status badge class
     */
    function getStatusBadgeClass(status) {
        const classes = {
            'queued': 'warning',
            'processing': 'info',
            'sent': 'success',
            'failed': 'danger',
            'cancelled': 'secondary',
            'expired': 'dark'
        };
        return classes[status] || 'light';
    }

    /**
     * Get priority badge class
     */
    function getPriorityBadgeClass(priority) {
        const classes = {
            'urgent': 'danger',
            'high': 'warning',
            'normal': 'info',
            'low': 'secondary'
        };
        return classes[priority] || 'light';
    }

    /**
     * Show alert message
     */
    function showAlert(message, type = 'info') {
        const alertClass = type === 'error' ? 'alert-danger' : `alert-${type}`;
        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        // Remove existing alerts
        $('.alert').remove();
        
        // Add new alert at the top of the container
        $('.container-fluid').prepend(alertHtml);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);
    }

    // Initialize when document is ready
    $(document).ready(function() {
        initEmailQueue();
    });

})(jQuery);