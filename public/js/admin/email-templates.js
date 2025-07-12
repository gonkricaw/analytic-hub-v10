/**
 * Email Template Management JavaScript
 * Handles all frontend interactions for email template CRUD operations
 */

class EmailTemplateManager {
    constructor() {
        this.dataTable = null;
        this.currentTemplate = null;
        this.editor = null;
        this.init();
    }

    /**
     * Initialize the email template manager
     */
    init() {
        this.initializeDataTable();
        this.initializeEditor();
        this.bindEvents();
        this.loadStatistics();
    }

    /**
     * Initialize DataTables for email templates listing
     */
    initializeDataTable() {
        if ($.fn.DataTable && $('#emailTemplatesTable').length) {
            this.dataTable = $('#emailTemplatesTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/admin/email-templates/data/table',
                    data: (d) => {
                        d.category = $('#categoryFilter').val();
                        d.type = $('#typeFilter').val();
                        d.status = $('#statusFilter').val();
                        d.template_type = $('#templateTypeFilter').val();
                    }
                },
                columns: [
                    { data: 'id', name: 'id', width: '60px' },
                    { data: 'display_name', name: 'display_name' },
                    { data: 'category', name: 'category' },
                    { data: 'type', name: 'type' },
                    { data: 'template_type', name: 'template_type', orderable: false },
                    { data: 'status', name: 'is_active', orderable: false },
                    { data: 'version', name: 'version', width: '80px' },
                    { data: 'updated_at', name: 'updated_at', width: '120px' },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false, width: '200px' }
                ],
                order: [[0, 'desc']],
                pageLength: 25,
                responsive: true,
                language: {
                    processing: '<div class="loading">Loading templates...</div>'
                },
                drawCallback: () => {
                    this.bindTableEvents();
                }
            });
        }
    }

    /**
     * Initialize rich text editor (Summernote)
     */
    initializeEditor() {
        if ($.fn.summernote && $('#body_html').length) {
            this.editor = $('#body_html').summernote({
                height: 400,
                minHeight: 300,
                maxHeight: 600,
                focus: false,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'underline', 'clear']],
                    ['fontname', ['fontname']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture', 'video']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ],
                callbacks: {
                    onChange: (contents) => {
                        this.updatePreview();
                    }
                }
            });
        }
    }

    /**
     * Bind event handlers
     */
    bindEvents() {
        // Filter events
        $('#categoryFilter, #typeFilter, #statusFilter, #templateTypeFilter').on('change', () => {
            if (this.dataTable) {
                this.dataTable.ajax.reload();
            }
        });

        // Search event
        $('#searchInput').on('keyup', debounce((e) => {
            if (this.dataTable) {
                this.dataTable.search(e.target.value).draw();
            }
        }, 300));

        // Variable insertion
        $(document).on('click', '.variable-item', (e) => {
            this.insertVariable($(e.target).text());
        });

        // Form events
        $('#emailTemplateForm').on('submit', (e) => {
            this.handleFormSubmit(e);
        });

        // Preview events
        $('#previewBtn').on('click', () => {
            this.showPreview();
        });

        // Test email events
        $('#testEmailBtn').on('click', () => {
            this.showTestEmailModal();
        });

        // Modal events
        $('.modal-close, .modal-cancel').on('click', (e) => {
            this.closeModal($(e.target).closest('.modal'));
        });

        // Quick template loading
        $('#quickTemplateSelect').on('change', (e) => {
            this.loadQuickTemplate($(e.target).val());
        });

        // Export/Import events
        $('#exportBtn').on('click', () => {
            this.exportTemplates();
        });

        $('#importBtn').on('click', () => {
            $('#importFile').click();
        });

        $('#importFile').on('change', (e) => {
            this.importTemplates(e.target.files[0]);
        });

        // Auto-save functionality
        if ($('#emailTemplateForm').length) {
            this.initAutoSave();
        }
    }

    /**
     * Bind events for table actions
     */
    bindTableEvents() {
        // View template
        $('.view-template').off('click').on('click', (e) => {
            e.preventDefault();
            const id = $(e.target).closest('button').data('id');
            window.location.href = `/admin/email-templates/${id}`;
        });

        // Edit template
        $('.edit-template').off('click').on('click', (e) => {
            e.preventDefault();
            const id = $(e.target).closest('button').data('id');
            window.location.href = `/admin/email-templates/${id}/edit`;
        });

        // Delete template
        $('.delete-template').off('click').on('click', (e) => {
            e.preventDefault();
            const id = $(e.target).closest('button').data('id');
            const name = $(e.target).closest('button').data('name');
            this.confirmDelete(id, name);
        });

        // Test template
        $('.test-template').off('click').on('click', (e) => {
            e.preventDefault();
            const id = $(e.target).closest('button').data('id');
            this.showTestEmailModal(id);
        });

        // Duplicate template
        $('.duplicate-template').off('click').on('click', (e) => {
            e.preventDefault();
            const id = $(e.target).closest('button').data('id');
            this.duplicateTemplate(id);
        });

        // Preview template
        $('.preview-template').off('click').on('click', (e) => {
            e.preventDefault();
            const id = $(e.target).closest('button').data('id');
            this.showPreview(id);
        });

        // Toggle status
        $('.toggle-status').off('click').on('click', (e) => {
            e.preventDefault();
            const id = $(e.target).closest('button').data('id');
            const action = $(e.target).closest('button').data('action');
            this.toggleStatus(id, action);
        });
    }

    /**
     * Load statistics for dashboard
     */
    loadStatistics() {
        // This would typically load from an API endpoint
        // For now, we'll use the data already rendered in the view
        console.log('Statistics loaded from server-side rendering');
    }

    /**
     * Insert variable into editor
     */
    insertVariable(variable) {
        if (this.editor && $('#body_html').summernote('codeview.isActivated')) {
            $('#body_html').summernote('code', $('#body_html').summernote('code') + variable);
        } else if (this.editor) {
            $('#body_html').summernote('insertText', variable);
        } else {
            // Fallback for plain textarea
            const textarea = $('#body_html')[0];
            if (textarea) {
                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                const text = textarea.value;
                textarea.value = text.substring(0, start) + variable + text.substring(end);
                textarea.selectionStart = textarea.selectionEnd = start + variable.length;
                textarea.focus();
            }
        }
        this.updatePreview();
    }

    /**
     * Update live preview
     */
    updatePreview() {
        const subject = $('#subject').val();
        const bodyHtml = this.editor ? $('#body_html').summernote('code') : $('#body_html').val();
        
        $('#previewSubject').text(subject || 'No subject');
        $('#previewBody').html(bodyHtml || '<p>No content</p>');
    }

    /**
     * Show preview modal
     */
    showPreview(templateId = null) {
        if (templateId) {
            // Load preview for existing template
            $.get(`/admin/email-templates/${templateId}/preview`)
                .done((response) => {
                    $('#previewSubject').text(response.subject);
                    $('#previewBody').html(response.body_html);
                    $('#previewFromEmail').text(response.from_email);
                    $('#previewFromName').text(response.from_name);
                    this.showModal('#previewModal');
                })
                .fail(() => {
                    this.showAlert('Error loading preview', 'error');
                });
        } else {
            // Show current form preview
            this.updatePreview();
            this.showModal('#previewModal');
        }
    }

    /**
     * Show test email modal
     */
    showTestEmailModal(templateId = null) {
        this.currentTemplate = templateId;
        $('#testEmailForm')[0].reset();
        this.showModal('#testEmailModal');
    }

    /**
     * Send test email
     */
    sendTestEmail() {
        const formData = new FormData($('#testEmailForm')[0]);
        const templateId = this.currentTemplate || $('#templateId').val();
        
        if (!templateId) {
            this.showAlert('No template selected', 'error');
            return;
        }

        $('#sendTestBtn').prop('disabled', true).html('<span class="loading"></span> Sending...');

        $.ajax({
            url: `/admin/email-templates/${templateId}/test`,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        })
        .done((response) => {
            this.showAlert('Test email sent successfully!', 'success');
            this.closeModal('#testEmailModal');
        })
        .fail((xhr) => {
            const message = xhr.responseJSON?.message || 'Failed to send test email';
            this.showAlert(message, 'error');
        })
        .always(() => {
            $('#sendTestBtn').prop('disabled', false).html('Send Test Email');
        });
    }

    /**
     * Handle form submission
     */
    handleFormSubmit(e) {
        e.preventDefault();
        
        const form = $(e.target);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        // Validate form
        if (!this.validateForm(form)) {
            return;
        }

        // Update editor content
        if (this.editor) {
            $('#body_html').val($('#body_html').summernote('code'));
        }

        submitBtn.prop('disabled', true).html('<span class="loading"></span> Saving...');

        // Submit form
        $.ajax({
            url: form.attr('action'),
            method: form.attr('method'),
            data: new FormData(form[0]),
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        })
        .done((response) => {
            this.showAlert('Template saved successfully!', 'success');
            if (response.redirect) {
                setTimeout(() => {
                    window.location.href = response.redirect;
                }, 1500);
            }
        })
        .fail((xhr) => {
            const errors = xhr.responseJSON?.errors;
            if (errors) {
                this.showValidationErrors(errors);
            } else {
                this.showAlert(xhr.responseJSON?.message || 'Failed to save template', 'error');
            }
        })
        .always(() => {
            submitBtn.prop('disabled', false).html(originalText);
        });
    }

    /**
     * Validate form
     */
    validateForm(form) {
        let isValid = true;
        
        // Clear previous errors
        form.find('.error-message').remove();
        form.find('.error').removeClass('error');

        // Required fields validation
        form.find('[required]').each((index, element) => {
            const $element = $(element);
            if (!$element.val().trim()) {
                this.showFieldError($element, 'This field is required');
                isValid = false;
            }
        });

        // Email validation
        form.find('input[type="email"]').each((index, element) => {
            const $element = $(element);
            const email = $element.val().trim();
            if (email && !this.isValidEmail(email)) {
                this.showFieldError($element, 'Please enter a valid email address');
                isValid = false;
            }
        });

        return isValid;
    }

    /**
     * Show field error
     */
    showFieldError(element, message) {
        element.addClass('error');
        element.after(`<div class="error-message" style="color: #e74c3c; font-size: 12px; margin-top: 5px;">${message}</div>`);
    }

    /**
     * Show validation errors
     */
    showValidationErrors(errors) {
        Object.keys(errors).forEach(field => {
            const element = $(`[name="${field}"]`);
            if (element.length) {
                this.showFieldError(element, errors[field][0]);
            }
        });
    }

    /**
     * Validate email format
     */
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Confirm delete action
     */
    confirmDelete(id, name) {
        if (confirm(`Are you sure you want to delete the template "${name}"? This action cannot be undone.`)) {
            this.deleteTemplate(id);
        }
    }

    /**
     * Delete template
     */
    deleteTemplate(id) {
        $.ajax({
            url: `/admin/email-templates/${id}`,
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        })
        .done(() => {
            this.showAlert('Template deleted successfully!', 'success');
            if (this.dataTable) {
                this.dataTable.ajax.reload();
            }
        })
        .fail((xhr) => {
            this.showAlert(xhr.responseJSON?.message || 'Failed to delete template', 'error');
        });
    }

    /**
     * Duplicate template
     */
    duplicateTemplate(id) {
        $.ajax({
            url: `/admin/email-templates/${id}/duplicate`,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        })
        .done((response) => {
            this.showAlert('Template duplicated successfully!', 'success');
            if (response.redirect) {
                setTimeout(() => {
                    window.location.href = response.redirect;
                }, 1500);
            } else if (this.dataTable) {
                this.dataTable.ajax.reload();
            }
        })
        .fail((xhr) => {
            this.showAlert(xhr.responseJSON?.message || 'Failed to duplicate template', 'error');
        });
    }

    /**
     * Toggle template status
     */
    toggleStatus(id, action) {
        $.ajax({
            url: `/admin/email-templates/${id}/${action}`,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        })
        .done(() => {
            const message = action === 'activate' ? 'Template activated successfully!' : 'Template deactivated successfully!';
            this.showAlert(message, 'success');
            if (this.dataTable) {
                this.dataTable.ajax.reload();
            }
        })
        .fail((xhr) => {
            this.showAlert(xhr.responseJSON?.message || `Failed to ${action} template`, 'error');
        });
    }

    /**
     * Load quick template
     */
    loadQuickTemplate(templateType) {
        if (!templateType) return;

        // This would load predefined templates
        const templates = {
            'welcome': {
                subject: 'Welcome to {{company_name}}!',
                body: '<h2>Welcome {{user_name}}!</h2><p>Thank you for joining {{company_name}}. We\'re excited to have you on board.</p>'
            },
            'notification': {
                subject: 'Notification from {{company_name}}',
                body: '<h2>Hello {{user_name}},</h2><p>{{notification_message}}</p>'
            },
            'reminder': {
                subject: 'Reminder: {{reminder_title}}',
                body: '<h2>Reminder</h2><p>Hello {{user_name}},</p><p>This is a reminder about: {{reminder_message}}</p>'
            }
        };

        const template = templates[templateType];
        if (template) {
            $('#subject').val(template.subject);
            if (this.editor) {
                $('#body_html').summernote('code', template.body);
            } else {
                $('#body_html').val(template.body);
            }
            this.updatePreview();
        }
    }

    /**
     * Export templates
     */
    exportTemplates() {
        const filters = {
            category: $('#categoryFilter').val(),
            type: $('#typeFilter').val(),
            status: $('#statusFilter').val(),
            template_type: $('#templateTypeFilter').val()
        };

        const queryString = new URLSearchParams(filters).toString();
        window.location.href = `/admin/email-templates/export?${queryString}`;
    }

    /**
     * Import templates
     */
    importTemplates(file) {
        if (!file) return;

        const formData = new FormData();
        formData.append('file', file);

        $.ajax({
            url: '/admin/email-templates/import',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        })
        .done((response) => {
            this.showAlert(`Successfully imported ${response.count} templates!`, 'success');
            if (this.dataTable) {
                this.dataTable.ajax.reload();
            }
        })
        .fail((xhr) => {
            this.showAlert(xhr.responseJSON?.message || 'Failed to import templates', 'error');
        });
    }

    /**
     * Initialize auto-save functionality
     */
    initAutoSave() {
        let autoSaveTimer;
        const autoSaveInterval = 30000; // 30 seconds

        const triggerAutoSave = () => {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(() => {
                this.autoSave();
            }, autoSaveInterval);
        };

        // Bind to form changes
        $('#emailTemplateForm input, #emailTemplateForm select, #emailTemplateForm textarea').on('input change', triggerAutoSave);
        
        if (this.editor) {
            $('#body_html').on('summernote.change', triggerAutoSave);
        }
    }

    /**
     * Auto-save form data
     */
    autoSave() {
        const formData = new FormData($('#emailTemplateForm')[0]);
        formData.append('auto_save', '1');

        $.ajax({
            url: $('#emailTemplateForm').attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        })
        .done(() => {
            this.showAutoSaveIndicator();
        })
        .fail(() => {
            console.log('Auto-save failed');
        });
    }

    /**
     * Show auto-save indicator
     */
    showAutoSaveIndicator() {
        const indicator = $('#autoSaveIndicator');
        if (indicator.length) {
            indicator.text('Auto-saved').fadeIn().delay(2000).fadeOut();
        }
    }

    /**
     * Show modal
     */
    showModal(modalSelector) {
        $(modalSelector).addClass('show');
        $('body').addClass('modal-open');
    }

    /**
     * Close modal
     */
    closeModal(modalSelector) {
        $(modalSelector).removeClass('show');
        $('body').removeClass('modal-open');
    }

    /**
     * Show alert message
     */
    showAlert(message, type = 'info') {
        // Remove existing alerts
        $('.alert').remove();

        const alertClass = {
            'success': 'alert-success',
            'error': 'alert-danger',
            'warning': 'alert-warning',
            'info': 'alert-info'
        }[type] || 'alert-info';

        const alert = $(`
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `);

        $('body').append(alert);

        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            alert.fadeOut(() => alert.remove());
        }, 5000);
    }
}

/**
 * Utility function for debouncing
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Initialize when document is ready
$(document).ready(() => {
    window.emailTemplateManager = new EmailTemplateManager();

    // Bind test email form submission
    $('#testEmailForm').on('submit', (e) => {
        e.preventDefault();
        window.emailTemplateManager.sendTestEmail();
    });

    // Handle modal backdrop clicks
    $('.modal').on('click', (e) => {
        if (e.target === e.currentTarget) {
            window.emailTemplateManager.closeModal($(e.currentTarget));
        }
    });

    // Handle escape key for modals
    $(document).on('keydown', (e) => {
        if (e.key === 'Escape') {
            $('.modal.show').each((index, modal) => {
                window.emailTemplateManager.closeModal($(modal));
            });
        }
    });
});