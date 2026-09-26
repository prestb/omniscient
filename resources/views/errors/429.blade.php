@extends('errors.layout')

@section('title', 'Slow Down')
@section('code', '429')

@php
    // ✅ Read the actual Retry-After seconds from the exception.
    //    Falls back to 5s if the header isn't present.
    $retryAfter = 5;
    try {
        if (isset($exception) && method_exists($exception, 'getHeaders')) {
            $headers = $exception->getHeaders();
            if (!empty($headers['Retry-After'])) {
                $retryAfter = (int) $headers['Retry-After'];
            } elseif (!empty($headers['retry-after'])) {
                $retryAfter = (int) $headers['retry-after'];
            }
        }
    } catch (\Throwable $e) {
        // Keep default
    }
    $retryAfter = max(1, min($retryAfter, 3600));
@endphp

@section('icon')
    <div class="w-20 h-20 rounded-full bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center">
        <svg class="w-10 h-10 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    </div>
@endsection

@section('description')
    You've made too many requests in a short period. We're protecting the platform from overload — please wait a moment and try again.
@endsection

@section('actions')
    <button
        id="retry-button"
        data-retry-after="{{ $retryAfter }}"
        onclick="window.location.reload()"
        disabled
        class="btn-shine group inline-flex items-center justify-center gap-2 px-6 py-3 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded-lg border border-gray-200 dark:border-gray-700 font-medium transition-colors disabled:opacity-60 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        <span id="retry-label">Retry in {{ $retryAfter }}s</span>
    </button>

    <script>
        (function () {
            const btn = document.getElementById('retry-button');
            const label = document.getElementById('retry-label');
            if (!btn || !label) return;

            let seconds = parseInt(btn.dataset.retryAfter, 10) || 5;

            const interval = setInterval(() => {
                seconds -= 1;

                if (seconds <= 0) {
                    clearInterval(interval);
                    btn.disabled = false;
                    label.textContent = 'Try Again Now';
                    return;
                }

                label.textContent = `Retry in ${seconds}s`;
            }, 1000);
        })();
    </script>
@endsection

@section('help')
    If this keeps happening, please <a href="{{ url('/contact') }}" class="text-primary-600 dark:text-primary-400 hover:underline">contact support</a>.
@endsection