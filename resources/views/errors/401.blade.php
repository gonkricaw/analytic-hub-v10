@extends('layouts.app')

@section('title', 'Authentication Required')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="text-center">
            <!-- Error Icon -->
            <div class="mx-auto h-24 w-24 text-red-500 mb-6">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </div>
            
            <!-- Error Code -->
            <h1 class="text-6xl font-bold text-gray-900 mb-4">401</h1>
            
            <!-- Error Title -->
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Authentication Required</h2>
            
            <!-- Error Message -->
            <p class="text-gray-600 mb-8">
                {{ $message ?? 'You need to be logged in to access this resource. Please sign in to continue.' }}
            </p>
            
            <!-- Action Buttons -->
            <div class="space-y-4">
                <a href="{{ route('login') }}" 
                   class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition duration-150 ease-in-out">
                    Sign In
                </a>
                
                @if(url()->previous() !== url()->current())
                <a href="{{ url()->previous() }}" 
                   class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition duration-150 ease-in-out">
                    Go Back
                </a>
                @endif
                
                <a href="{{ route('dashboard') }}" 
                   class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition duration-150 ease-in-out">
                    Return to Dashboard
                </a>
            </div>
        </div>
        
        <!-- Additional Information -->
        <div class="mt-8 text-center">
            <p class="text-sm text-gray-500">
                If you believe this is an error, please contact support.
            </p>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .error-animation {
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.5;
        }
    }
</style>
@endpush