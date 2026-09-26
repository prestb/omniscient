@extends('errors.layout')

@section('title', 'Page Not Found')
@section('code', '404')

@section('icon')
    <div class="w-20 h-20 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
        <svg class="w-10 h-10 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
    </div>
@endsection

@section('description')
    The page you're looking for doesn't exist, has been removed, or is temporarily unavailable. 
    Let's get you back on track.
@endsection

@section('actions')
    <button onclick="history.back()" class="inline-flex items-center gap-2 px-6 py-3 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors font-medium border border-gray-200 dark:border-gray-700">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        Go Back
    </button>
@endsection

@section('extra-actions')
    <a href="{{ url('/directory') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors font-medium border border-gray-200 dark:border-gray-700">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
        </svg>
        Browse Directory
    </a>
@endsection

@section('help')
    Looking for a business? Try searching our <a href="{{ url('/directory') }}" class="text-primary-600 dark:text-primary-400 hover:underline">directory</a> 
    or <a href="{{ url('/contact') }}" class="text-primary-600 dark:text-primary-400 hover:underline">contact support</a>.
@endsection