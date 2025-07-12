@extends('layouts.admin')

@section('title', 'Preview: ' . $content->title)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0 text-white">
                        <i class="fas fa-eye me-2"></i>
                        Preview: {{ $content->title }}
                    </h1>
                    <p class="text-muted mb-0">{{ $content->type }} • {{ $content->status }}</p>
                </div>
                <div>
                    <a href="{{ route('admin.contents.show', $content) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        Back to Content
                    </a>
                    @if($content->status === 'published')
                        <a href="{{ route('content.show', $content->slug ?: $content->id) }}" class="btn btn-primary" target="_blank">
                            <i class="fas fa-external-link-alt me-1"></i>
                            View Live
                        </a>
                    @endif
                </div>
            </div>

            <!-- Preview Container -->
            <div class="card bg-dark border-secondary">
                <div class="card-header bg-secondary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-desktop me-2"></i>
                            Content Preview
                        </h5>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-outline-light active" onclick="setPreviewMode('desktop')">
                                <i class="fas fa-desktop"></i> Desktop
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-light" onclick="setPreviewMode('tablet')">
                                <i class="fas fa-tablet-alt"></i> Tablet
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-light" onclick="setPreviewMode('mobile')">
                                <i class="fas fa-mobile-alt"></i> Mobile
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="preview-container" id="previewContainer">
                        @if($isEmbedded && $decryptedUrl)
                            <!-- Embedded Content Preview -->
                            <div class="embed-preview-wrapper">
                                <div class="embed-info bg-info text-white p-2 small">
                                    <i class="fas fa-info-circle me-1"></i>
                                    <strong>Embedded Content:</strong> {{ parse_url($decryptedUrl, PHP_URL_HOST) }}
                                    <span class="float-end">
                                        <i class="fas fa-shield-alt me-1"></i>
                                        Encrypted & Secured
                                    </span>
                                </div>
                                <iframe 
                                    src="{{ $decryptedUrl }}"
                                    class="preview-iframe"
                                    sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-presentation"
                                    referrerpolicy="strict-origin-when-cross-origin"
                                    loading="lazy"
                                    onload="handleIframeLoad()"
                                    onerror="handleIframeError()">
                                </iframe>
                                <div id="iframeLoader" class="iframe-loader">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-2 text-muted">Loading embedded content...</p>
                                </div>
                                <div id="iframeError" class="iframe-error" style="display: none;">
                                    <div class="text-center p-4">
                                        <i class="fas fa-exclamation-triangle text-warning fa-3x mb-3"></i>
                                        <h5>Failed to Load Content</h5>
                                        <p class="text-muted">The embedded content could not be loaded. This may be due to:</p>
                                        <ul class="text-start text-muted">
                                            <li>Network connectivity issues</li>
                                            <li>Content provider restrictions</li>
                                            <li>Invalid or expired URL</li>
                                        </ul>
                                        <button class="btn btn-outline-primary" onclick="retryIframe()">
                                            <i class="fas fa-redo me-1"></i>
                                            Retry
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @else
                            <!-- Custom Content Preview -->
                            <div class="custom-content-preview">
                                <div class="content-header p-3 border-bottom">
                                    <h1 class="h2 text-white mb-2">{{ $content->title }}</h1>
                                    @if($content->excerpt)
                                        <p class="text-muted mb-0">{{ $content->excerpt }}</p>
                                    @endif
                                    <div class="content-meta mt-2">
                                        <small class="text-muted">
                                            <i class="fas fa-user me-1"></i>
                                            {{ $content->creator->name ?? 'Unknown' }}
                                            <span class="mx-2">•</span>
                                            <i class="fas fa-calendar me-1"></i>
                                            {{ $content->created_at->format('M d, Y') }}
                                            @if($content->category)
                                                <span class="mx-2">•</span>
                                                <i class="fas fa-folder me-1"></i>
                                                {{ $content->category }}
                                            @endif
                                        </small>
                                    </div>
                                </div>
                                <div class="content-body p-3">
                                    <div class="content-html">
                                        {!! $content->content !!}
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Content Information -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card bg-dark border-secondary">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                Content Information
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-sm-4"><strong>Type:</strong></div>
                                <div class="col-sm-8">{{ ucfirst($content->type) }}</div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-sm-4"><strong>Status:</strong></div>
                                <div class="col-sm-8">
                                    <span class="badge bg-{{ $content->status === 'published' ? 'success' : ($content->status === 'draft' ? 'warning' : 'secondary') }}">
                                        {{ ucfirst($content->status) }}
                                    </span>
                                </div>
                            </div>
                            @if($content->published_at)
                                <div class="row mt-2">
                                    <div class="col-sm-4"><strong>Published:</strong></div>
                                    <div class="col-sm-8">{{ $content->published_at->format('M d, Y H:i') }}</div>
                                </div>
                            @endif
                            @if($content->expires_at)
                                <div class="row mt-2">
                                    <div class="col-sm-4"><strong>Expires:</strong></div>
                                    <div class="col-sm-8">{{ $content->expires_at->format('M d, Y H:i') }}</div>
                                </div>
                            @endif
                            <div class="row mt-2">
                                <div class="col-sm-4"><strong>Views:</strong></div>
                                <div class="col-sm-8">{{ number_format($content->view_count) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card bg-dark border-secondary">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-tags me-2"></i>
                                Metadata
                            </h6>
                        </div>
                        <div class="card-body">
                            @if($content->category)
                                <div class="row">
                                    <div class="col-sm-4"><strong>Category:</strong></div>
                                    <div class="col-sm-8">{{ $content->category }}</div>
                                </div>
                            @endif
                            @if($content->tags && count($content->tags) > 0)
                                <div class="row mt-2">
                                    <div class="col-sm-4"><strong>Tags:</strong></div>
                                    <div class="col-sm-8">
                                        @foreach($content->tags as $tag)
                                            <span class="badge bg-secondary me-1">{{ $tag }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            @if($content->meta_title)
                                <div class="row mt-2">
                                    <div class="col-sm-4"><strong>SEO Title:</strong></div>
                                    <div class="col-sm-8">{{ $content->meta_title }}</div>
                                </div>
                            @endif
                            @if($content->meta_description)
                                <div class="row mt-2">
                                    <div class="col-sm-4"><strong>SEO Description:</strong></div>
                                    <div class="col-sm-8">{{ Str::limit($content->meta_description, 100) }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.preview-container {
    background: #fff;
    border-radius: 0.375rem;
    overflow: hidden;
    transition: all 0.3s ease;
}

.preview-iframe {
    width: 100%;
    height: 600px;
    border: none;
    display: block;
}

.iframe-loader {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
    z-index: 10;
}

.iframe-error {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 20;
}

.embed-preview-wrapper {
    position: relative;
    min-height: 600px;
}

.custom-content-preview {
    min-height: 400px;
}

.content-html {
    color: #333;
    line-height: 1.6;
}

.content-html img {
    max-width: 100%;
    height: auto;
    border-radius: 0.375rem;
}

.content-html table {
    width: 100%;
    border-collapse: collapse;
    margin: 1rem 0;
}

.content-html table th,
.content-html table td {
    padding: 0.75rem;
    border: 1px solid #dee2e6;
    text-align: left;
}

.content-html table th {
    background-color: #f8f9fa;
    font-weight: 600;
}

.content-html blockquote {
    border-left: 4px solid #007bff;
    padding-left: 1rem;
    margin: 1rem 0;
    font-style: italic;
    color: #6c757d;
}

.content-html pre {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 0.375rem;
    overflow-x: auto;
}

.content-html code {
    background: #f8f9fa;
    padding: 0.2rem 0.4rem;
    border-radius: 0.25rem;
    font-size: 0.875em;
}

/* Responsive preview modes */
.preview-container.tablet {
    max-width: 768px;
    margin: 0 auto;
}

.preview-container.mobile {
    max-width: 375px;
    margin: 0 auto;
}

.preview-container.tablet .preview-iframe,
.preview-container.mobile .preview-iframe {
    height: 500px;
}
</style>
@endpush

@push('scripts')
<script>
let currentMode = 'desktop';

function setPreviewMode(mode) {
    currentMode = mode;
    const container = document.getElementById('previewContainer');
    const buttons = document.querySelectorAll('.btn-group .btn');
    
    // Update button states
    buttons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    // Update container class
    container.className = 'preview-container ' + mode;
}

function handleIframeLoad() {
    document.getElementById('iframeLoader').style.display = 'none';
    document.getElementById('iframeError').style.display = 'none';
}

function handleIframeError() {
    document.getElementById('iframeLoader').style.display = 'none';
    document.getElementById('iframeError').style.display = 'flex';
}

function retryIframe() {
    const iframe = document.querySelector('.preview-iframe');
    const loader = document.getElementById('iframeLoader');
    const error = document.getElementById('iframeError');
    
    error.style.display = 'none';
    loader.style.display = 'block';
    
    // Reload iframe
    iframe.src = iframe.src;
}

// Auto-hide loader after timeout
setTimeout(() => {
    const loader = document.getElementById('iframeLoader');
    if (loader && loader.style.display !== 'none') {
        handleIframeError();
    }
}, 15000);
</script>
@endpush