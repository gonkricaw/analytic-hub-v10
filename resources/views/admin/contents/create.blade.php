@extends('layouts.admin')

@section('title', 'Create Content')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Create Content</h1>
            <p class="mb-0 text-muted">Create new custom HTML content or secure embedded reports</p>
        </div>
        <div>
            <a href="{{ route('admin.contents.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <form id="contentForm" action="{{ route('admin.contents.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        
        <div class="row">
            <!-- Main Content Form -->
            <div class="col-lg-8">
                <!-- Basic Information -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Basic Information</h6>
                    </div>
                    <div class="card-body">
                        <!-- Content Type -->
                        <div class="mb-3">
                            <label for="type" class="form-label">Content Type <span class="text-danger">*</span></label>
                            <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                <option value="">Select content type...</option>
                                <option value="custom" {{ old('type') === 'custom' ? 'selected' : '' }}>Custom HTML Content</option>
                                <option value="embedded" {{ old('type') === 'embedded' ? 'selected' : '' }}>Embedded Report</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                <strong>Custom HTML:</strong> Rich text content with media embedding<br>
                                <strong>Embedded Report:</strong> Secure external reports (Power BI, Tableau, etc.)
                            </div>
                        </div>

                        <!-- Title -->
                        <div class="mb-3">
                            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                   id="title" name="title" value="{{ old('title') }}" required maxlength="255">
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Slug -->
                        <div class="mb-3">
                            <label for="slug" class="form-label">Slug</label>
                            <input type="text" class="form-control @error('slug') is-invalid @enderror" 
                                   id="slug" name="slug" value="{{ old('slug') }}" maxlength="255">
                            @error('slug')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Leave empty to auto-generate from title</div>
                        </div>

                        <!-- Excerpt -->
                        <div class="mb-3">
                            <label for="excerpt" class="form-label">Excerpt</label>
                            <textarea class="form-control @error('excerpt') is-invalid @enderror" 
                                      id="excerpt" name="excerpt" rows="3" maxlength="500">{{ old('excerpt') }}</textarea>
                            @error('excerpt')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Brief description for previews and SEO</div>
                        </div>
                    </div>
                </div>

                <!-- Content Editor -->
                <div class="card shadow mb-4" id="contentEditorCard">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Content</h6>
                    </div>
                    <div class="card-body">
                        <!-- Custom HTML Content Editor -->
                        <div id="customContentEditor" style="display: none;">
                            <label for="content" class="form-label">Content <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('content') is-invalid @enderror" 
                                      id="content" name="content">{{ old('content') }}</textarea>
                            @error('content')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Embedded Content URL -->
                        <div id="embeddedContentEditor" style="display: none;">
                            <label for="embedded_url" class="form-label">Report URL <span class="text-danger">*</span></label>
                            <input type="url" class="form-control @error('embedded_url') is-invalid @enderror" 
                                   id="embedded_url" name="embedded_url" value="{{ old('embedded_url') }}" 
                                   placeholder="https://app.powerbi.com/view?r=...">
                            @error('embedded_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                <strong>Supported platforms:</strong> Power BI, Tableau, Google Data Studio, Looker, Qlik, Domo<br>
                                <strong>Security:</strong> URL will be encrypted with AES-256 and masked with UUID
                            </div>
                            
                            <!-- URL Validation -->
                            <div id="urlValidation" class="mt-2" style="display: none;">
                                <div class="alert alert-info" id="urlValidationMessage"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SEO Settings -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">SEO Settings</h6>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#seoSettings">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                    <div class="collapse" id="seoSettings">
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="meta_title" class="form-label">Meta Title</label>
                                <input type="text" class="form-control @error('meta_title') is-invalid @enderror" 
                                       id="meta_title" name="meta_title" value="{{ old('meta_title') }}" maxlength="60">
                                @error('meta_title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Recommended: 50-60 characters</div>
                            </div>

                            <div class="mb-3">
                                <label for="meta_description" class="form-label">Meta Description</label>
                                <textarea class="form-control @error('meta_description') is-invalid @enderror" 
                                          id="meta_description" name="meta_description" rows="3" maxlength="160">{{ old('meta_description') }}</textarea>
                                @error('meta_description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Recommended: 150-160 characters</div>
                            </div>

                            <div class="mb-3">
                                <label for="meta_keywords" class="form-label">Meta Keywords</label>
                                <input type="text" class="form-control @error('meta_keywords') is-invalid @enderror" 
                                       id="meta_keywords" name="meta_keywords" value="{{ old('meta_keywords') }}">
                                @error('meta_keywords')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Comma-separated keywords</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Publish Settings -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Publish Settings</h6>
                    </div>
                    <div class="card-body">
                        <!-- Status -->
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                                <option value="draft" {{ old('status', 'draft') === 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="published" {{ old('status') === 'published' ? 'selected' : '' }}>Published</option>
                                <option value="archived" {{ old('status') === 'archived' ? 'selected' : '' }}>Archived</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Published Date -->
                        <div class="mb-3">
                            <label for="published_at" class="form-label">Publish Date</label>
                            <input type="datetime-local" class="form-control @error('published_at') is-invalid @enderror" 
                                   id="published_at" name="published_at" value="{{ old('published_at') }}">
                            @error('published_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Leave empty to publish immediately</div>
                        </div>

                        <!-- Expiry Date -->
                        <div class="mb-3">
                            <label for="expires_at" class="form-label">Expiry Date</label>
                            <input type="datetime-local" class="form-control @error('expires_at') is-invalid @enderror" 
                                   id="expires_at" name="expires_at" value="{{ old('expires_at') }}">
                            @error('expires_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Leave empty for no expiry</div>
                        </div>

                        <!-- Featured -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1" 
                                       {{ old('is_featured') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_featured">
                                    Featured Content
                                </label>
                            </div>
                        </div>

                        <!-- Allow Comments -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="allow_comments" name="allow_comments" value="1" 
                                       {{ old('allow_comments', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="allow_comments">
                                    Allow Comments
                                </label>
                            </div>
                        </div>

                        <!-- Searchable -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_searchable" name="is_searchable" value="1" 
                                       {{ old('is_searchable', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_searchable">
                                    Include in Search
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Featured Image -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Featured Image</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <input type="file" class="form-control @error('featured_image') is-invalid @enderror" 
                                   id="featured_image" name="featured_image" accept="image/*">
                            @error('featured_image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Recommended: 1200x630px, max 2MB</div>
                        </div>
                        <div id="imagePreview" style="display: none;">
                            <img id="previewImg" src="" alt="Preview" class="img-fluid rounded">
                        </div>
                    </div>
                </div>

                <!-- Access Control -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Access Control</h6>
                    </div>
                    <div class="card-body">
                        <!-- Visibility -->
                        <div class="mb-3">
                            <label class="form-label">Visibility</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="visibility" id="visibility_public" value="public" 
                                       {{ old('visibility', 'public') === 'public' ? 'checked' : '' }}>
                                <label class="form-check-label" for="visibility_public">
                                    Public - All authenticated users
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="visibility" id="visibility_private" value="private" 
                                       {{ old('visibility') === 'private' ? 'checked' : '' }}>
                                <label class="form-check-label" for="visibility_private">
                                    Private - Only me and admins
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="visibility" id="visibility_restricted" value="restricted" 
                                       {{ old('visibility') === 'restricted' ? 'checked' : '' }}>
                                <label class="form-check-label" for="visibility_restricted">
                                    Restricted - Specific roles/users
                                </label>
                            </div>
                        </div>

                        <!-- Role-based Access -->
                        <div id="roleAccess" style="display: none;">
                            <label class="form-label">Allowed Roles</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="allowed_roles[]" value="admin" id="role_admin">
                                <label class="form-check-label" for="role_admin">Admin</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="allowed_roles[]" value="manager" id="role_manager">
                                <label class="form-check-label" for="role_manager">Manager</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="allowed_roles[]" value="user" id="role_user">
                                <label class="form-check-label" for="role_user">User</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card shadow">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary" name="action" value="save">
                                <i class="fas fa-save"></i> Save Content
                            </button>
                            <button type="submit" class="btn btn-success" name="action" value="save_and_publish">
                                <i class="fas fa-paper-plane"></i> Save & Publish
                            </button>
                            <button type="button" class="btn btn-outline-info" onclick="previewContent()">
                                <i class="fas fa-eye"></i> Preview
                            </button>
                            <a href="{{ route('admin.contents.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Content Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="previewFrame" style="width: 100%; height: 600px; border: none;"></iframe>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .card {
        border: none;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    }
    
    .form-label {
        font-weight: 600;
        color: #5a5c69;
    }
    
    .form-text {
        font-size: 0.8rem;
    }
    
    .text-danger {
        color: #e74a3b !important;
    }
    
    #imagePreview img {
        max-height: 200px;
        object-fit: cover;
    }
    
    .tox-tinymce {
        border-radius: 0.35rem;
    }
    
    .url-validation-success {
        border-color: #28a745;
        background-color: #d4edda;
    }
    
    .url-validation-error {
        border-color: #dc3545;
        background-color: #f8d7da;
    }
</style>
@endsection

@section('scripts')
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
<script>
$(document).ready(function() {
    // Content type change handler
    $('#type').on('change', function() {
        const type = $(this).val();
        
        if (type === 'custom') {
            $('#customContentEditor').show();
            $('#embeddedContentEditor').hide();
            initTinyMCE();
        } else if (type === 'embedded') {
            $('#customContentEditor').hide();
            $('#embeddedContentEditor').show();
            destroyTinyMCE();
        } else {
            $('#customContentEditor').hide();
            $('#embeddedContentEditor').hide();
            destroyTinyMCE();
        }
    });
    
    // Visibility change handler
    $('input[name="visibility"]').on('change', function() {
        if ($(this).val() === 'restricted') {
            $('#roleAccess').show();
        } else {
            $('#roleAccess').hide();
        }
    });
    
    // Title to slug conversion
    $('#title').on('input', function() {
        if ($('#slug').val() === '') {
            const slug = $(this).val()
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
            $('#slug').val(slug);
        }
    });
    
    // Featured image preview
    $('#featured_image').on('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#previewImg').attr('src', e.target.result);
                $('#imagePreview').show();
            };
            reader.readAsDataURL(file);
        } else {
            $('#imagePreview').hide();
        }
    });
    
    // Embedded URL validation
    $('#embedded_url').on('input', function() {
        const url = $(this).val();
        if (url) {
            validateEmbeddedUrl(url);
        } else {
            $('#urlValidation').hide();
        }
    });
    
    // Form submission
    $('#contentForm').on('submit', function(e) {
        const type = $('#type').val();
        
        if (type === 'custom') {
            // Update TinyMCE content
            if (tinymce.get('content')) {
                tinymce.get('content').save();
            }
        } else if (type === 'embedded') {
            // Validate embedded URL
            const url = $('#embedded_url').val();
            if (!url) {
                e.preventDefault();
                Swal.fire('Error!', 'Please enter a valid report URL.', 'error');
                return false;
            }
        }
    });
    
    // Initialize based on old values
    const oldType = '{{ old("type") }}';
    if (oldType) {
        $('#type').val(oldType).trigger('change');
    }
    
    const oldVisibility = '{{ old("visibility", "public") }}';
    if (oldVisibility === 'restricted') {
        $('#roleAccess').show();
    }
});

// Initialize TinyMCE
function initTinyMCE() {
    if (tinymce.get('content')) {
        return; // Already initialized
    }
    
    tinymce.init({
        selector: '#content',
        height: 500,
        menubar: true,
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount', 'emoticons'
        ],
        toolbar: 'undo redo | blocks | ' +
            'bold italic backcolor | alignleft aligncenter ' +
            'alignright alignjustify | bullist numlist outdent indent | ' +
            'removeformat | help | link image media | code preview fullscreen',
        content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 14px; }',
        image_advtab: true,
        image_uploadtab: true,
        file_picker_types: 'image',
        automatic_uploads: true,
        images_upload_url: '/admin/upload/image',
        images_upload_handler: function (blobInfo, success, failure) {
            const formData = new FormData();
            formData.append('file', blobInfo.blob(), blobInfo.filename());
            formData.append('_token', '{{ csrf_token() }}');
            
            fetch('/admin/upload/image', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    success(result.location);
                } else {
                    failure('Image upload failed: ' + result.message);
                }
            })
            .catch(error => {
                failure('Image upload failed: ' + error.message);
            });
        },
        setup: function(editor) {
            editor.on('change', function() {
                editor.save();
            });
        }
    });
}

// Destroy TinyMCE
function destroyTinyMCE() {
    if (tinymce.get('content')) {
        tinymce.get('content').destroy();
    }
}

// Validate embedded URL
function validateEmbeddedUrl(url) {
    const allowedDomains = [
        'app.powerbi.com',
        'public.tableau.com',
        'datastudio.google.com',
        'lookerstudio.google.com',
        'embed.looker.com',
        'qlikview.com',
        'qliksense.com',
        'domo.com'
    ];
    
    try {
        const urlObj = new URL(url);
        const domain = urlObj.hostname.toLowerCase();
        
        let isValid = false;
        let platform = '';
        
        for (const allowedDomain of allowedDomains) {
            if (domain === allowedDomain || domain.endsWith('.' + allowedDomain)) {
                isValid = true;
                platform = allowedDomain;
                break;
            }
        }
        
        if (isValid) {
            $('#embedded_url').removeClass('url-validation-error').addClass('url-validation-success');
            $('#urlValidationMessage').html(`
                <i class="fas fa-check-circle text-success"></i>
                <strong>Valid URL detected:</strong> ${platform}
            `);
            $('#urlValidation').show();
        } else {
            $('#embedded_url').removeClass('url-validation-success').addClass('url-validation-error');
            $('#urlValidationMessage').html(`
                <i class="fas fa-exclamation-triangle text-warning"></i>
                <strong>Warning:</strong> This domain is not in the allowed list. Supported platforms: Power BI, Tableau, Google Data Studio, Looker, Qlik, Domo.
            `);
            $('#urlValidation').show();
        }
    } catch (e) {
        $('#embedded_url').removeClass('url-validation-success').addClass('url-validation-error');
        $('#urlValidationMessage').html(`
            <i class="fas fa-times-circle text-danger"></i>
            <strong>Invalid URL format.</strong> Please enter a valid URL.
        `);
        $('#urlValidation').show();
    }
}

// Preview content
function previewContent() {
    const type = $('#type').val();
    
    if (!type) {
        Swal.fire('Error!', 'Please select a content type first.', 'error');
        return;
    }
    
    let content = '';
    
    if (type === 'custom') {
        if (tinymce.get('content')) {
            content = tinymce.get('content').getContent();
        } else {
            content = $('#content').val();
        }
        
        if (!content.trim()) {
            Swal.fire('Error!', 'Please enter some content to preview.', 'error');
            return;
        }
        
        // Create preview HTML
        const previewHtml = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>${$('#title').val() || 'Content Preview'}</title>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <style>
                    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; margin: 40px; }
                    img { max-width: 100%; height: auto; }
                </style>
            </head>
            <body>
                <h1>${$('#title').val() || 'Untitled'}</h1>
                ${content}
            </body>
            </html>
        `;
        
        const blob = new Blob([previewHtml], { type: 'text/html' });
        const url = URL.createObjectURL(blob);
        $('#previewFrame').attr('src', url);
        
    } else if (type === 'embedded') {
        const url = $('#embedded_url').val();
        
        if (!url) {
            Swal.fire('Error!', 'Please enter a report URL to preview.', 'error');
            return;
        }
        
        $('#previewFrame').attr('src', url);
    }
    
    $('#previewModal').modal('show');
}
</script>
@endsection