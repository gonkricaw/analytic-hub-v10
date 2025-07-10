@extends('layouts.app')

@section('title', 'Access Forbidden')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="text-center">
            <!-- Error Icon -->
            <div class="mx-auto h-24 w-24 text-red-600 mb-6">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"></path>
                </svg>
            </div>
            
            <!-- Error Code -->
            <h1 class="text-6xl font-bold text-gray-900 mb-4">403</h1>
            
            <!-- Error Title -->
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Access Forbidden</h2>
            
            <!-- Error Message -->
            <p class="text-gray-600 mb-8">
                {{ $message ?? 'You do not have permission to access this resource. Please contact your administrator if you believe this is an error.' }}
            </p>
            
            <!-- Additional Details -->
            @if(isset($details))
            <div class="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Access Denied</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <p>{{ $details }}</p>
                        </div>
                    </div>
                </div>
            </div>
            @endif
            
            <!-- Required Permissions -->
            @if(isset($required_permissions) && is_array($required_permissions))
            <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Required Permissions</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <ul class="list-disc list-inside">
                                @foreach($required_permissions as $permission)
                                <li>{{ $permission }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            @endif
            
            <!-- Action Buttons -->
            <div class="space-y-4">
                @if(url()->previous() !== url()->current())
                <a href="{{ url()->previous() }}" 
                   class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition duration-150 ease-in-out">
                    Go Back
                </a>
                @endif
                
                <a href="{{ route('dashboard') }}" 
                   class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition duration-150 ease-in-out">
                    Return to Dashboard
                </a>
                
                <a href="{{ route('contact.support') ?? '#' }}" 
                   class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition duration-150 ease-in-out">
                    Contact Support
                </a>
            </div>
        </div>
        
        <!-- Additional Information -->
        <div class="mt-8 text-center">
            <p class="text-sm text-gray-500">
                Error Code: 403 | {{ now()->format('Y-m-d H:i:s') }}
            </p>
            @if(auth()->check())
            <p class="text-xs text-gray-400 mt-2">
                User: {{ auth()->user()->username ?? auth()->user()->email }}
            </p>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .error-animation {
        animation: shake 0.5s ease-in-out;
    }
    
    @keyframes shake {
        0%, 100% {
            transform: translateX(0);
        }
        25% {
            transform: translateX(-5px);
        }
        75% {
            transform: translateX(5px);
        }
    }
</style>
@endpush