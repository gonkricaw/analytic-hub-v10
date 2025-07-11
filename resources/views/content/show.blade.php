@extends('layouts.app')

@section('title', $meta_title)

@section('meta')
    <meta name="description" content="{{ $meta_description }}">
    @if($meta_keywords)
        <meta name="keywords" content="{{ $meta_keywords }}">
    @endif
    <link rel="canonical" href="{{ $canonical_url }}">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="{{ $meta_title }}">
    <meta property="og:description" content="{{ $meta_description }}">
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{ $canonical_url }}">
    @if($content->featured_image)
        <meta property="og:image" content="{{ asset('storage/' . $content->featured_image) }}">
    @endif
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $meta_title }}">
    <meta name="twitter:description" content="{{ $meta_description }}">
    @if($content->featured_image)
        <meta name="twitter:image" content="{{ asset('storage/' . $content->featured_image) }}">
    @endif
@endsection

@section('styles')
<style>
    .content-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .content-header {
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid #e9ecef;
    }
    
    .content-title {
        font-size: 2.5rem;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 15px;
        line-height: 1.2;
    }
    
    .content-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        color: #6c757d;
        font-size: 0.9rem;
        margin-bottom: 15px;
    }
    
    .content-meta-item {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .content-meta-item i {
        width: 16px;
        text-align: center;
    }
    
    .content-featured-image {
        width: 100%;
        max-height: 400px;
        object-fit: cover;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    
    .content-excerpt {
        font-size: 1.1rem;
        color: #495057;
        font-style: italic;
        margin-bottom: 20px;
        padding: 15px;
        background: #f8f9fa;
        border-left: 4px solid #007bff;
        border-radius: 0 4px 4px 0;
    }
    
    .content-body {
        font-size: 1rem;
        line-height: 1.8;
        color: #333;
    }
    
    .content-body h1,
    .content-body h2,
    .content-body h3,
    .content-body h4,
    .content-body h5,
    .content-body h6 {
        margin-top: 2rem;
        margin-bottom: 1rem;
        color: #2c3e50;
    }
    
    .content-body p {
        margin-bottom: 1.5rem;
    }
    
    .content-body img {
        max-width: 100%;
        height: auto;
        border-radius: 4px;
        margin: 1rem 0;
    }
    
    .content-body blockquote {
        margin: 2rem 0;
        padding: 1rem 1.5rem;
        background: #f8f9fa;
        border-left: 4px solid #007bff;
        font-style: italic;
    }
    
    .content-body pre {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 4px;
        overflow-x: auto;
        margin: 1rem 0;
    }
    
    .content-body code {
        background: #f8f9fa;
        padding: 0.2rem 0.4rem;
        border-radius: 3px;
        font-family: 'Courier New', monospace;
        font-size: 0.9em;
    }
    
    .content-body pre code {
        background: none;
        padding: 0;
    }
    
    .content-tags {
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #e9ecef;
    }
    
    .content-tag {
        display: inline-block;
        background: #007bff;
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        text-decoration: none;
        margin-right: 8px;
        margin-bottom: 8px;
        transition: background-color 0.3s;
    }
    
    .content-tag:hover {
        background: #0056b3;
        color: white;
        text-decoration: none;
    }
    
    .content-actions {
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #e9ecef;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .content-share {
        display: flex;
        gap: 10px;
    }
    
    .share-btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 8px 15px;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        text-decoration: none;
        color: #495057;
        font-size: 0.9rem;
        transition: all 0.3s;
    }
    
    .share-btn:hover {
        background: #f8f9fa;
        text-decoration: none;
        color: #495057;
    }
    
    .content-stats {
        display: flex;
        gap: 20px;
        font-size: 0.9rem;
        color: #6c757d;
    }
    
    .content-navigation {
        margin-top: 40px;
        padding-top: 30px;
        border-top: 2px solid #e9ecef;
    }
    
    .nav-links {
        display: flex;
        justify-content: space-between;
        gap: 20px;
    }
    
    .nav-link {
        flex: 1;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 8px;
        text-decoration: none;
        color: #495057;
        transition: all 0.3s;
    }
    
    .nav-link:hover {
        background: #e9ecef;
        text-decoration: none;
        color: #495057;
    }
    
    .nav-link-label {
        font-size: 0.8rem;
        color: #6c757d;
        margin-bottom: 5px;
    }
    
    .nav-link-title {
        font-weight: 600;
        font-size: 1rem;
    }
    
    @media (max-width: 768px) {
        .content-container {
            padding: 15px;
        }
        
        .content-title {
            font-size: 2rem;
        }
        
        .content-meta {
            flex-direction: column;
            gap: 10px;
        }
        
        .content-actions {
            flex-direction: column;
            align-items: stretch;
        }
        
        .nav-links {
            flex-direction: column;
        }
    }
</style>
@endsection

@section('content')
<div class="content-container">
    <!-- Content Header -->
    <header class="content-header">
        <h1 class="content-title">{{ $content->title }}</h1>
        
        <div class="content-meta">
            @if($content->published_at)
                <div class="content-meta-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span>{{ $content->published_at->format('F j, Y') }}</span>
                </div>
            @endif
            
            @if($content->created_by_user)
                <div class="content-meta-item">
                    <i class="fas fa-user"></i>
                    <span>{{ $content->created_by_user->name }}</span>
                </div>
            @endif
            
            <div class="content-meta-item">
                <i class="fas fa-eye"></i>
                <span>{{ number_format($content->view_count) }} {{ Str::plural('view', $content->view_count) }}</span>
            </div>
            
            @if($content->reading_time)
                <div class="content-meta-item">
                    <i class="fas fa-clock"></i>
                    <span>{{ $content->reading_time }} min read</span>
                </div>
            @endif
            
            @if($content->rating && $content->rating > 0)
                <div class="content-meta-item">
                    <i class="fas fa-star"></i>
                    <span>{{ number_format($content->rating, 1) }}/5</span>
                </div>
            @endif
        </div>
        
        @if($content->featured_image)
            <img src="{{ asset('storage/' . $content->featured_image) }}" 
                 alt="{{ $content->title }}" 
                 class="content-featured-image">
        @endif
        
        @if($content->excerpt)
            <div class="content-excerpt">
                {{ $content->excerpt }}
            </div>
        @endif
    </header>
    
    <!-- Content Body -->
    <main class="content-body">
        {!! $content->content !!}
    </main>
    
    <!-- Content Tags -->
    @if($content->tags && count($content->tags) > 0)
        <div class="content-tags">
            @foreach($content->tags as $tag)
                <a href="#" class="content-tag">{{ $tag }}</a>
            @endforeach
        </div>
    @endif
    
    <!-- Content Actions -->
    <div class="content-actions">
        <div class="content-share">
            <a href="#" class="share-btn" onclick="shareContent('facebook')">
                <i class="fab fa-facebook-f"></i>
                Share
            </a>
            <a href="#" class="share-btn" onclick="shareContent('twitter')">
                <i class="fab fa-twitter"></i>
                Tweet
            </a>
            <a href="#" class="share-btn" onclick="shareContent('linkedin')">
                <i class="fab fa-linkedin-in"></i>
                Share
            </a>
            <a href="#" class="share-btn" onclick="copyToClipboard()">
                <i class="fas fa-link"></i>
                Copy Link
            </a>
        </div>
        
        <div class="content-stats">
            <span><i class="fas fa-thumbs-up"></i> {{ $content->like_count ?? 0 }}</span>
            <span><i class="fas fa-comment"></i> {{ $content->comment_count ?? 0 }}</span>
            <span><i class="fas fa-share"></i> {{ $content->share_count ?? 0 }}</span>
        </div>
    </div>
    
    <!-- Content Navigation -->
    @if(isset($previousContent) || isset($nextContent))
        <nav class="content-navigation">
            <div class="nav-links">
                @if(isset($previousContent))
                    <a href="{{ route('content.show', $previousContent->slug) }}" class="nav-link">
                        <div class="nav-link-label">← Previous</div>
                        <div class="nav-link-title">{{ Str::limit($previousContent->title, 50) }}</div>
                    </a>
                @else
                    <div></div>
                @endif
                
                @if(isset($nextContent))
                    <a href="{{ route('content.show', $nextContent->slug) }}" class="nav-link">
                        <div class="nav-link-label">Next →</div>
                        <div class="nav-link-title">{{ Str::limit($nextContent->title, 50) }}</div>
                    </a>
                @endif
            </div>
        </nav>
    @endif
</div>
@endsection

@section('scripts')
<script>
    // Share functionality
    function shareContent(platform) {
        const url = encodeURIComponent(window.location.href);
        const title = encodeURIComponent('{{ addslashes($content->title) }}');
        const description = encodeURIComponent('{{ addslashes($meta_description) }}');
        
        let shareUrl = '';
        
        switch(platform) {
            case 'facebook':
                shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
                break;
            case 'twitter':
                shareUrl = `https://twitter.com/intent/tweet?url=${url}&text=${title}`;
                break;
            case 'linkedin':
                shareUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${url}`;
                break;
        }
        
        if (shareUrl) {
            window.open(shareUrl, '_blank', 'width=600,height=400');
        }
    }
    
    // Copy link to clipboard
    function copyToClipboard() {
        navigator.clipboard.writeText(window.location.href).then(function() {
            // Show success message
            const btn = event.target.closest('.share-btn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
            btn.style.background = '#28a745';
            btn.style.color = 'white';
            
            setTimeout(function() {
                btn.innerHTML = originalText;
                btn.style.background = '';
                btn.style.color = '';
            }, 2000);
        }).catch(function(err) {
            console.error('Could not copy text: ', err);
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = window.location.href;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
        });
    }
    
    // Track reading progress
    let readingStartTime = Date.now();
    let hasScrolled = false;
    
    window.addEventListener('scroll', function() {
        if (!hasScrolled) {
            hasScrolled = true;
            // Track that user started reading
        }
        
        // Calculate reading progress
        const scrollTop = window.pageYOffset;
        const docHeight = document.documentElement.scrollHeight - window.innerHeight;
        const scrollPercent = (scrollTop / docHeight) * 100;
        
        // You can send this data to analytics
        if (scrollPercent > 75 && !window.readingTracked) {
            window.readingTracked = true;
            // Track that user read most of the content
        }
    });
    
    // Track time spent on page
    window.addEventListener('beforeunload', function() {
        const timeSpent = Math.round((Date.now() - readingStartTime) / 1000);
        // You can send this data to analytics
    });
</script>
@endsection