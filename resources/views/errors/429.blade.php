@extends('layouts.app')

@section('title', 'Too Many Requests')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="text-center">
            <!-- Error Icon -->
            <div class="mx-auto h-24 w-24 text-orange-500 mb-6">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            
            <!-- Error Code -->
            <h1 class="text-6xl font-bold text-gray-900 mb-4">429</h1>
            
            <!-- Error Title -->
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Too Many Requests</h2>
            
            <!-- Error Message -->
            <p class="text-gray-600 mb-6">
                {{ $message ?? 'You have made too many requests. Please slow down and try again later.' }}
            </p>
            
            @if(isset($retry_after))
            <!-- Retry Information -->
            <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-orange-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-orange-700">
                            Please wait <strong id="retry-countdown">{{ $retry_after }}</strong> seconds before trying again.
                        </p>
                    </div>
                </div>
            </div>
            @endif
            
            <!-- Action Buttons -->
            <div class="space-y-3">
                <button 
                    onclick="window.history.back()" 
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition duration-150 ease-in-out"
                >
                    Go Back
                </button>
                
                <a 
                    href="{{ route('dashboard') }}" 
                    class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition duration-150 ease-in-out"
                >
                    Return to Dashboard
                </a>
            </div>
            
            <!-- Additional Information -->
            <div class="mt-8 text-xs text-gray-500">
                <p>If you believe this is an error, please contact the system administrator.</p>
                <p class="mt-1">Error Code: 429 - Rate Limit Exceeded</p>
            </div>
        </div>
    </div>
</div>

@if(isset($retry_after))
<script>
// Countdown timer for retry
let retryAfter = {{ $retry_after }};
const countdownElement = document.getElementById('retry-countdown');

if (countdownElement && retryAfter > 0) {
    const countdown = setInterval(() => {
        retryAfter--;
        countdownElement.textContent = retryAfter;
        
        if (retryAfter <= 0) {
            clearInterval(countdown);
            countdownElement.textContent = '0';
            
            // Show refresh option
            const refreshButton = document.createElement('button');
            refreshButton.textContent = 'Try Again';
            refreshButton.className = 'mt-2 inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded text-orange-700 bg-orange-100 hover:bg-orange-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500';
            refreshButton.onclick = () => window.location.reload();
            
            countdownElement.parentNode.appendChild(refreshButton);
        }
    }, 1000);
}
</script>
@endif
@endsection

@push('styles')
<style>
    body {
        background-color: #f9fafb;
    }
</style>
@endpush