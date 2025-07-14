@extends('layouts.auth')

@section('title', 'Terms & Conditions - Accept to Continue')

@section('content')
<div class="terms-page">
    <div class="terms-modal">
        <!-- Header -->
        <div class="modal-header">
            <button type="button" class="menu-btn">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="modal-title">Terms and Conditions</h1>
            <button type="button" class="search-btn">
                <i class="fas fa-search"></i>
            </button>
        </div>

        <!-- Content -->
        <div class="modal-content">
            <div class="content-header">
                <p class="last-updated">Last updated: Feb 7th 2019</p>
            </div>
            
            <div class="content-body">
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam sed bibendum dolor, non condimentum diam. Sed non odio placerat, tempus erat id, hendrerit justo. Morbi orci dui, facilisis nec rutrum et, consectetur et leo. Nunc vel efficitur ipsum, et commodo erat. In eu condimentum enim. Nulla nisl leo, mattis in laoreet ac, tristique non felis.</p>
                
                <p>Cras ornare, arcu ut molestie vehicula, nisl tortor tempor eros, id eleifend velit leo ac sem. Suspendisse posuere. Maecenas quis eros nunc.</p>
                
                <h4>Sed a enim diam</h4>
                
                <p>Morbi vestibulum consectetur metus, et lacinia ipsum ullamcorper sit amet. In interdum risus in sapibus consectetur. Ut sollicitudin congue mauris, quis vulputate metus accumsan et. Nunc ut tortor magna. Sed a enim diam. Suspendisse fringilla quam vitae sollicitudin rhoncus. Maecenas eu dignissim neque. Proin eu dolor purus. Cras aptent taciti sociosqu ad litora torquent.</p>
                
                <h4>Morbi orci dui</h4>
                
                <p>Ut et tellus ac sapien tincidunt mattis interdum et elit. Sed ac tempor risus, et volutpat nunc. Pellentesque venenatis, ex a bibendum volutpat, ligula est condimentum magna, non varius tellus mi eu urna. Donec vel tristique urna. Vestibulum lobortis elit in posuere fermentum. Vestibulum</p>
            </div>
        </div>
        
        <!-- Form Section -->
        <div class="modal-footer">
            <form method="POST" action="{{ route('terms.accept.submit') }}" id="termsForm" style="display: flex; gap: 10px; justify-content: center;">
                @csrf
                <button type="submit" class="btn-accept" name="action" value="accept">
                    Accept
                </button>
                <button type="button" class="btn-decline" onclick="window.history.back();">
                    Decline
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    /* Analytics Hub Dark Theme - Terms & Conditions */
    body {
        margin: 0;
        padding: 0;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        background: #0E0E44; /* Analytics Hub dark blue background */
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1.6;
        color: #FFFFFF;
    }
    
    .terms-page {
        width: 100%;
        max-width: 700px;
        margin: 20px;
        position: relative;
    }
    
    .terms-modal {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.2);
        overflow: hidden;
        animation: slideUp 0.4s ease-out;
    }
    
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .modal-header {
        background: linear-gradient(135deg, #FF7A00 0%, #FF9500 100%);
        padding: 24px;
        border-bottom: none;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: relative;
    }
    
    .modal-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(45deg, rgba(255, 255, 255, 0.1) 0%, transparent 100%);
        pointer-events: none;
    }
    
    .menu-btn, .search-btn {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: #FFFFFF;
        font-size: 18px;
        cursor: pointer;
        padding: 10px;
        border-radius: 8px;
        transition: all 0.3s ease;
        backdrop-filter: blur(5px);
    }
    
    .menu-btn:hover, .search-btn:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }
    
    .modal-title {
        font-size: 22px;
        font-weight: 600;
        margin: 0;
        color: #FFFFFF;
        text-align: center;
        flex: 1;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        letter-spacing: 0.5px;
    }
    
    .modal-content {
        padding: 30px;
        max-height: 450px;
        overflow-y: auto;
        background: #FFFFFF;
    }
    
    /* Custom scrollbar for modal content */
    .modal-content::-webkit-scrollbar {
        width: 8px;
    }
    
    .modal-content::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }
    
    .modal-content::-webkit-scrollbar-thumb {
        background: #FF7A00;
        border-radius: 4px;
    }
    
    .modal-content::-webkit-scrollbar-thumb:hover {
        background: #FF9500;
    }
    
    .content-header {
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 2px solid #FF7A00;
    }
    
    .last-updated {
        font-size: 13px;
        color: #666666;
        margin: 0;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .content-body {
        line-height: 1.7;
    }
    
    .content-body p {
        margin: 0 0 18px 0;
        color: #333333;
        font-size: 15px;
        text-align: justify;
    }
    
    .content-body h4 {
        margin: 28px 0 16px 0;
        color: #0E0E44;
        font-size: 18px;
        font-weight: 700;
        border-left: 4px solid #FF7A00;
        padding-left: 16px;
        background: linear-gradient(90deg, rgba(255, 122, 0, 0.1) 0%, transparent 100%);
        padding: 12px 0 12px 16px;
        border-radius: 0 8px 8px 0;
    }
    
    .modal-footer {
        padding: 24px 30px;
        border-top: 1px solid #E5E7EB;
        background: linear-gradient(135deg, #F8F9FA 0%, #E9ECEF 100%);
    }
    
    .btn-accept, .btn-decline {
        padding: 14px 32px;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        position: relative;
        overflow: hidden;
    }
    
    .btn-accept {
        background: linear-gradient(135deg, #FF7A00 0%, #FF9500 100%);
        color: #FFFFFF;
        box-shadow: 0 4px 15px rgba(255, 122, 0, 0.3);
        border: 2px solid transparent;
        min-width: 120px;
        position: relative;
        z-index: 1;
    }
    
    .btn-accept::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        transition: left 0.5s;
    }
    
    .btn-accept:hover::before {
        left: 100%;
    }
    
    .btn-accept:hover {
        background: linear-gradient(135deg, #FF9500 0%, #FFB000 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(255, 122, 0, 0.4);
    }
    
    .btn-accept:active {
        transform: translateY(0);
        box-shadow: 0 2px 10px rgba(255, 122, 0, 0.3);
    }
    
    .btn-decline {
        background: #FFFFFF;
        color: #666666;
        border: 2px solid #D1D5DB;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .btn-decline:hover {
        background: #F8F9FA;
        color: #333333;
        border-color: #9CA3AF;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    .btn-decline:active {
        transform: translateY(0);
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
    }
    
    /* Enhanced loading state for accept button */
    .btn-accept.loading {
        pointer-events: none;
        opacity: 0.9;
        background: linear-gradient(135deg, #FF9500 0%, #FFB000 100%);
        cursor: not-allowed;
    }
    
    .btn-accept.loading i {
        margin-right: 8px;
        animation: spin 1s linear infinite;
    }
    
    .btn-accept:disabled {
        cursor: not-allowed;
        opacity: 0.8;
    }
    
    .btn-decline:disabled {
        cursor: not-allowed;
        opacity: 0.6;
        pointer-events: none;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Enhanced responsive design */
    @media (max-width: 768px) {
        .terms-page {
            margin: 15px;
        }
        
        .modal-header {
            padding: 20px;
        }
        
        .modal-title {
            font-size: 20px;
        }
        
        .modal-content {
            padding: 24px;
            max-height: 350px;
        }
        
        .modal-footer {
            padding: 20px 24px;
        }
        
        .btn-accept, .btn-decline {
            padding: 16px 28px;
            font-size: 16px;
            width: 100%;
            margin-bottom: 10px;
        }
        
        #termsForm {
            flex-direction: column !important;
        }
    }
    
    @media (max-width: 480px) {
        .terms-page {
            margin: 10px;
        }
        
        .modal-header {
            padding: 16px;
        }
        
        .modal-title {
            font-size: 18px;
        }
        
        .modal-content {
            padding: 20px;
            max-height: 300px;
        }
        
        .content-body h4 {
            font-size: 16px;
        }
        
        .content-body p {
            font-size: 14px;
        }
    }
    
</style>
@endsection

@section('scripts')
<script>
    /**
     * Enhanced Terms & Conditions Form Handler
     * Purpose: Handle form submission with loading states and user feedback
     * Features: Loading animation, double-click prevention, smooth UX
     */
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('termsForm');
        const acceptBtn = document.querySelector('.btn-accept');
        const declineBtn = document.querySelector('.btn-decline');
        
        // Handle accept button click with enhanced loading state
        form.addEventListener('submit', function(e) {
            // Prevent double submission
            if (acceptBtn.classList.contains('loading')) {
                e.preventDefault();
                return false;
            }
            
            // Validate form before submission
            if (!form.checkValidity()) {
                e.preventDefault();
                return false;
            }
            
            // Add loading state to accept button
            acceptBtn.classList.add('loading');
            acceptBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            acceptBtn.disabled = true;
            acceptBtn.style.pointerEvents = 'none';
            
            // Disable decline button during processing
            declineBtn.disabled = true;
            declineBtn.style.opacity = '0.6';
            declineBtn.style.pointerEvents = 'none';
            
            // Add visual feedback to the modal
            const modal = document.querySelector('.terms-modal');
            modal.style.opacity = '0.9';
            
            // Show user feedback
            console.log('Terms acceptance in progress...');
            
            // Set a timeout to handle potential server delays
            setTimeout(function() {
                if (acceptBtn.classList.contains('loading')) {
                    acceptBtn.innerHTML = '<i class="fas fa-clock"></i> Please wait...';
                }
            }, 3000);
            
            // Form will submit normally to backend
            // Loading state will be cleared on page redirect/reload
        });
        
        // Handle decline button with confirmation
        declineBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Show confirmation dialog
            if (confirm('Are you sure you want to decline the Terms & Conditions? You will not be able to access the system.')) {
                // Add visual feedback
                declineBtn.style.background = '#dc3545';
                declineBtn.style.color = '#ffffff';
                declineBtn.textContent = 'Declining...';
                
                // Simulate decline action (could redirect to logout or show message)
                setTimeout(function() {
                    window.history.back();
                }, 500);
            }
        });
        
        // Add smooth scroll to content for better UX
        const modalContent = document.querySelector('.modal-content');
        if (modalContent) {
            modalContent.addEventListener('scroll', function() {
                const scrollPercentage = (modalContent.scrollTop / (modalContent.scrollHeight - modalContent.clientHeight)) * 100;
                
                // Optional: Could add a reading progress indicator
                // This helps users understand how much content they've read
                if (scrollPercentage > 80) {
                    acceptBtn.style.boxShadow = '0 6px 25px rgba(255, 122, 0, 0.5)';
                }
            });
        }
        
        // Add keyboard navigation support
        document.addEventListener('keydown', function(e) {
            // Enter key on accept button
            if (e.key === 'Enter' && document.activeElement === acceptBtn) {
                form.submit();
            }
            
            // Escape key for decline
            if (e.key === 'Escape') {
                declineBtn.click();
            }
        });
        
        // Add focus management for accessibility
        acceptBtn.addEventListener('focus', function() {
            this.style.outline = '3px solid rgba(255, 122, 0, 0.5)';
        });
        
        acceptBtn.addEventListener('blur', function() {
            this.style.outline = 'none';
        });
        
        declineBtn.addEventListener('focus', function() {
            this.style.outline = '3px solid rgba(108, 117, 125, 0.5)';
        });
        
        declineBtn.addEventListener('blur', function() {
            this.style.outline = 'none';
        });
        
        // Initialize page with focus on accept button for better UX
        setTimeout(function() {
            acceptBtn.focus();
        }, 500);
        
        console.log('Terms & Conditions page initialized successfully');
    });
</script>
@endsection