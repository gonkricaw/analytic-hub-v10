@extends('layouts.admin')

@section('title', $content->title)

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">{{ $content->title }}</h1>
            <div class="d-flex align-items-center mt-2">
                <span class="badge badge-{{ $content->status === 'published' ? 'success' : ($content->status === 'draft' ? 'warning' : 'secondary') }} me-2">
                    {{ ucfirst($content->status) }}
                </span>
                <span class="badge badge-info me-2">{{ ucfirst($content->type) }}</span>
                @if($content->is_featured)
                <span class="badge badge-primary me-2">Featured</span>
                @endif
                <small class="text-muted">
                    Created: {{ $content->created_at->format('M d, Y') }} | 
                    Updated: {{ $content->updated_at->format('M d, Y') }}
                </small>
            </div>
        </div>
        <div>
            @if($content->status === 'published')
            <a href="{{ route('content.show', $content->slug) }}" class="btn btn-outline-success me-2" target="_blank">
                <i class="fas fa-external-link-alt"></i> View Live
            </a>
            @endif
            <a href="{{ route('admin.contents.edit', $content) }}" class="btn btn-primary me-2">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="{{ route('admin.contents.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Content Display -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Content</h6>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-cog"></i> Actions
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('admin.contents.edit', $content) }}"><i class="fas fa-edit"></i> Edit</a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.contents.duplicate', $content) }}"><i class="fas fa-copy"></i> Duplicate</a></li>
                            <li><hr class="dropdown-divider"></li>
                            @if($content->status !== 'published')
                            <li><a class="dropdown-item" href="#" onclick="toggleStatus('published')"><i class="fas fa-paper-plane"></i> Publish</a></li>
                            @endif
                            @if($content->status !== 'draft')
                            <li><a class="dropdown-item" href="#" onclick="toggleStatus('draft')"><i class="fas fa-file-alt"></i> Move to Draft</a></li>
                            @endif
                            @if($content->status !== 'archived')
                            <li><a class="dropdown-item" href="#" onclick="toggleStatus('archived')"><i class="fas fa-archive"></i> Archive</a></li>
                            @endif
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="#" onclick="deleteContent()"><i class="fas fa-trash"></i> Delete</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    @if($content->excerpt)
                    <div class="alert alert-light border-left-primary">
                        <strong>Excerpt:</strong> {{ $content->excerpt }}
                    </div>
                    @endif
                    
                    @if($content->type === 'custom')
                        <!-- Custom HTML Content -->
                        <div class="content-display">
                            {!! $content->content !!}
                        </div>
                    @elseif($content->type === 'embedded')
                        <!-- Embedded Content -->
                        <div class="embedded-content">
                            @if(isset($content->custom_fields['encrypted_url']))
                            <div class="alert alert-info mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-shield-alt"></i>
                                        <strong>Secure Embedded Report</strong><br>
                                        <small>URL is encrypted and protected with UUID masking</small>
                                    </div>
                                    <div>
                                        <span class="badge badge-success">AES-256 Encrypted</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Embedded URL Info -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>UUID:</strong><br>
                                    <code>{{ $content->custom_fields['uuid'] ?? 'N/A' }}</code>
                                </div>
                                <div class="col-md-6">
                                    <strong>Platform:</strong><br>
                                    {{ $content->custom_fields['platform'] ?? 'Unknown' }}
                                </div>
                            </div>
                            
                            <!-- Embed Preview -->
                            <div class="embed-preview">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0">Preview</h6>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="generateAccessToken()">
                                            <i class="fas fa-key"></i> Generate Access Token
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-success" onclick="viewSecure()">
                                            <i class="fas fa-shield-alt"></i> View Secure
                                        </button>
                                    </div>
                                </div>
                                <div class="embed-container" style="position: relative; height: 500px; border: 1px solid #dee2e6; border-radius: 0.375rem; overflow: hidden;">
                                    <iframe 
                                        src="{{ route('content.embed', $content->custom_fields['uuid'] ?? $content->id) }}" 
                                        style="width: 100%; height: 100%; border: none;"
                                        sandbox="allow-scripts allow-same-origin allow-forms"
                                        loading="lazy">
                                    </iframe>
                                </div>
                            </div>
                            @else
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>No embedded URL configured.</strong> Please edit this content to add a report URL.
                            </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- SEO Information -->
            @if($content->meta_title || $content->meta_description || $content->meta_keywords)
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">SEO Information</h6>
                </div>
                <div class="card-body">
                    @if($content->meta_title)
                    <div class="mb-3">
                        <strong>Meta Title:</strong><br>
                        <span class="text-muted">{{ $content->meta_title }}</span>
                    </div>
                    @endif
                    
                    @if($content->meta_description)
                    <div class="mb-3">
                        <strong>Meta Description:</strong><br>
                        <span class="text-muted">{{ $content->meta_description }}</span>
                    </div>
                    @endif
                    
                    @if($content->meta_keywords)
                    <div class="mb-3">
                        <strong>Meta Keywords:</strong><br>
                        <span class="text-muted">{{ $content->meta_keywords }}</span>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Comments Section -->
            @if($content->allow_comments)
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Comments ({{ $content->comment_count }})</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted text-center">Comments functionality will be implemented in future updates.</p>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Content Statistics -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="h4 mb-0 text-primary">{{ number_format($content->view_count) }}</div>
                            <small class="text-muted">Views</small>
                        </div>
                        <div class="col-6">
                            <div class="h4 mb-0 text-success">{{ number_format($content->like_count) }}</div>
                            <small class="text-muted">Likes</small>
                        </div>
                    </div>
                    <hr>
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="h5 mb-0 text-info">{{ number_format($content->share_count) }}</div>
                            <small class="text-muted">Shares</small>
                        </div>
                        <div class="col-6">
                            <div class="h5 mb-0 text-warning">{{ number_format($content->comment_count) }}</div>
                            <small class="text-muted">Comments</small>
                        </div>
                    </div>
                    @if($content->rating > 0)
                    <hr>
                    <div class="text-center">
                        <div class="h5 mb-0">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= $content->rating)
                                    <i class="fas fa-star text-warning"></i>
                                @else
                                    <i class="far fa-star text-muted"></i>
                                @endif
                            @endfor
                        </div>
                        <small class="text-muted">Average Rating</small>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Publication Details -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Publication Details</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Status:</strong><br>
                        <span class="badge badge-{{ $content->status === 'published' ? 'success' : ($content->status === 'draft' ? 'warning' : 'secondary') }}">
                            {{ ucfirst($content->status) }}
                        </span>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Created:</strong><br>
                        <span class="text-muted">{{ $content->created_at->format('M d, Y \a\t g:i A') }}</span>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Last Updated:</strong><br>
                        <span class="text-muted">{{ $content->updated_at->format('M d, Y \a\t g:i A') }}</span>
                    </div>
                    
                    @if($content->published_at)
                    <div class="mb-3">
                        <strong>Published:</strong><br>
                        <span class="text-muted">{{ $content->published_at->format('M d, Y \a\t g:i A') }}</span>
                    </div>
                    @endif
                    
                    @if($content->expires_at)
                    <div class="mb-3">
                        <strong>Expires:</strong><br>
                        <span class="text-muted">{{ $content->expires_at->format('M d, Y \a\t g:i A') }}</span>
                    </div>
                    @endif
                    
                    <div class="mb-3">
                        <strong>Author:</strong><br>
                        <span class="text-muted">{{ $content->user->name ?? 'Unknown' }}</span>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Slug:</strong><br>
                        <code>{{ $content->slug }}</code>
                    </div>
                </div>
            </div>

            <!-- Featured Image -->
            @if($content->featured_image)
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Featured Image</h6>
                </div>
                <div class="card-body p-0">
                    <img src="{{ Storage::url($content->featured_image) }}" alt="{{ $content->title }}" class="img-fluid">
                </div>
            </div>
            @endif

            <!-- Access Control -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Access Control</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Visibility:</strong><br>
                        @php
                            $visibility = $content->visibility_settings['visibility'] ?? 'public';
                        @endphp
                        <span class="badge badge-{{ $visibility === 'public' ? 'success' : ($visibility === 'private' ? 'danger' : 'warning') }}">
                            {{ ucfirst($visibility) }}
                        </span>
                    </div>
                    
                    @if($visibility === 'restricted' && isset($content->access_permissions['allowed_roles']))
                    <div class="mb-3">
                        <strong>Allowed Roles:</strong><br>
                        @foreach($content->access_permissions['allowed_roles'] as $role)
                            <span class="badge badge-outline-primary me-1">{{ ucfirst($role) }}</span>
                        @endforeach
                    </div>
                    @endif
                    
                    <div class="mb-3">
                        <strong>Settings:</strong><br>
                        @if($content->is_featured)
                            <span class="badge badge-primary me-1">Featured</span>
                        @endif
                        @if($content->allow_comments)
                            <span class="badge badge-info me-1">Comments Enabled</span>
                        @endif
                        @if($content->is_searchable)
                            <span class="badge badge-success me-1">Searchable</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Version History -->
            @if($content->versions->count() > 0)
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Version History</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-2">{{ $content->versions->count() }} version(s) available</p>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#versionHistoryModal">
                        <i class="fas fa-history"></i> View History
                    </button>
                </div>
            </div>
            @endif

            <!-- Quick Actions -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.contents.edit', $content) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-edit"></i> Edit Content
                        </a>
                        <a href="{{ route('admin.contents.duplicate', $content) }}" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-copy"></i> Duplicate
                        </a>
                        @if($content->status === 'published')
                        <a href="{{ route('content.show', $content->slug) }}" class="btn btn-outline-success btn-sm" target="_blank">
                            <i class="fas fa-external-link-alt"></i> View Live
                        </a>
                        @endif
                        <button type="button" class="btn btn-outline-warning btn-sm" onclick="shareContent()">
                            <i class="fas fa-share-alt"></i> Share
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteContent()">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Version History Modal -->
<div class="modal fade" id="versionHistoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Version History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @if($content->versions->count() > 0)
                <div class="timeline">
                    @foreach($content->versions->sortByDesc('created_at') as $version)
                    <div class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <h6 class="mb-1">Version {{ $loop->total - $loop->index }}</h6>
                            <p class="text-muted mb-2">{{ $version->created_at->format('M d, Y \a\t g:i A') }}</p>
                            <p class="mb-2">{{ $version->description ?? 'No description' }}</p>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary" onclick="viewVersion({{ $version->id }})">
                                    <i class="fas fa-eye"></i> View
                                </button>
                                <button type="button" class="btn btn-outline-success" onclick="restoreVersion({{ $version->id }})">
                                    <i class="fas fa-undo"></i> Restore
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-muted text-center">No version history available.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Access Token Modal -->
<div class="modal fade" id="accessTokenModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Access Token Generated</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>One-time access token generated.</strong> This token will expire in 1 hour.
                </div>
                <div class="mb-3">
                    <label class="form-label">Access URL:</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="accessUrl" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="copyAccessUrl()">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Token:</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="accessToken" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="copyAccessToken()">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="openAccessUrl()">
                    <i class="fas fa-external-link-alt"></i> Open URL
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
    
    .content-display {
        line-height: 1.6;
    }
    
    .content-display img {
        max-width: 100%;
        height: auto;
        border-radius: 0.375rem;
    }
    
    .border-left-primary {
        border-left: 0.25rem solid #4e73df !important;
    }
    
    .timeline {
        position: relative;
        padding-left: 30px;
    }
    
    .timeline::before {
        content: '';
        position: absolute;
        left: 15px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #dee2e6;
    }
    
    .timeline-item {
        position: relative;
        margin-bottom: 20px;
    }
    
    .timeline-marker {
        position: absolute;
        left: -23px;
        top: 5px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #007bff;
        border: 2px solid #fff;
        box-shadow: 0 0 0 2px #dee2e6;
    }
    
    .timeline-content {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        border-left: 3px solid #007bff;
    }
    
    .badge {
        font-size: 0.75rem;
    }
    
    .embed-container {
        background: #f8f9fa;
    }
    
    .embed-container iframe {
        background: white;
    }
</style>
@endsection

@section('scripts')
<script>
// Toggle content status
function toggleStatus(status) {
    Swal.fire({
        title: `Change Status to ${status.charAt(0).toUpperCase() + status.slice(1)}?`,
        text: `This will change the content status to ${status}.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, change it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("admin.contents.toggle-status", $content) }}';
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);
            
            const statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'status';
            statusInput.value = status;
            form.appendChild(statusInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    });
}

// Delete content
function deleteContent() {
    Swal.fire({
        title: 'Delete Content?',
        text: 'This action cannot be undone. The content will be permanently deleted.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc3545'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("admin.contents.destroy", $content) }}';
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);
            
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';
            form.appendChild(methodInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    });
}

// Share content
function shareContent() {
    const url = '{{ $content->status === "published" ? route("content.show", $content->slug) : "#" }}';
    const title = '{{ $content->title }}';
    
    if (navigator.share) {
        navigator.share({
            title: title,
            url: url
        }).catch(console.error);
    } else {
        // Fallback: copy to clipboard
        navigator.clipboard.writeText(url).then(() => {
            Swal.fire('Copied!', 'Content URL copied to clipboard.', 'success');
        }).catch(() => {
            Swal.fire('Error!', 'Failed to copy URL to clipboard.', 'error');
        });
    }
}

// Generate access token for embedded content
function generateAccessToken() {
    @if($content->type === 'embedded')
    fetch('{{ route("content.generate-access-token", $content->custom_fields["uuid"] ?? $content->id) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#accessToken').val(data.token);
            $('#accessUrl').val(data.url);
            $('#accessTokenModal').modal('show');
        } else {
            Swal.fire('Error!', data.message || 'Failed to generate access token.', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error!', 'Failed to generate access token.', 'error');
    });
    @else
    Swal.fire('Error!', 'Access tokens are only available for embedded content.', 'error');
    @endif
}

// View secure content
function viewSecure() {
    @if($content->type === 'embedded')
    const uuid = '{{ $content->custom_fields["uuid"] ?? $content->id }}';
    window.open(`{{ url('/content/secure') }}/${uuid}`, '_blank');
    @else
    Swal.fire('Error!', 'Secure viewing is only available for embedded content.', 'error');
    @endif
}

// Copy access URL
function copyAccessUrl() {
    const accessUrl = document.getElementById('accessUrl');
    accessUrl.select();
    document.execCommand('copy');
    
    Swal.fire({
        title: 'Copied!',
        text: 'Access URL copied to clipboard.',
        icon: 'success',
        timer: 1500,
        showConfirmButton: false
    });
}

// Copy access token
function copyAccessToken() {
    const accessToken = document.getElementById('accessToken');
    accessToken.select();
    document.execCommand('copy');
    
    Swal.fire({
        title: 'Copied!',
        text: 'Access token copied to clipboard.',
        icon: 'success',
        timer: 1500,
        showConfirmButton: false
    });
}

// Open access URL
function openAccessUrl() {
    const accessUrl = document.getElementById('accessUrl').value;
    if (accessUrl) {
        window.open(accessUrl, '_blank');
    }
}

// View version
function viewVersion(versionId) {
    window.open(`{{ route('admin.contents.show', $content) }}?version=${versionId}`, '_blank');
}

// Restore version
function restoreVersion(versionId) {
    Swal.fire({
        title: 'Restore Version',
        text: 'This will restore the content to the selected version. Current changes will be lost.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, restore it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("admin.contents.restore-version", $content) }}';
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);
            
            const versionInput = document.createElement('input');
            versionInput.type = 'hidden';
            versionInput.name = 'version_id';
            versionInput.value = versionId;
            form.appendChild(versionInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>
@endsection