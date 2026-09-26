@extends('errors.layout')

@section('title', 'Something Went Wrong')
@section('code', '500')

@section('icon')
    <div class="w-20 h-20 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
        <svg class="w-10 h-10 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
    </div>
@endsection

@section('description')
    We're experiencing technical difficulties. Our team has been notified and is working on a fix. 
    Please try again in a few moments.
@endsection

@section('actions')
    <button onclick="window.location.reload()" class="inline-flex items-center gap-2 px-6 py-3 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors font-medium border border-gray-200 dark:border-gray-700">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        Try Again
    </button>
@endsection

@section('help')
    If the problem persists, please <a href="{{ url('/contact') }}" class="text-primary-600 dark:text-primary-400 hover:underline">contact our support team</a>.
    @if(config('app.debug'))
        <br><span class="text-xs text-red-500">Debug mode is ON - check logs for details.</span>
    @endif
@endsection