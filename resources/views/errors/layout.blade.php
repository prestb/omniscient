<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Error') - {{ config('app.name', 'Omniscient') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('images/icon-192.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900" rel="stylesheet" />

    <!-- Styles -->
    @vite(['resources/css/app.css'])

    <style>
        /* ✅ Ensure the page fills the viewport */
        html, body {
            min-height: 100vh;
            height: 100%;
        }

        body {
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
            margin: 0;
        }

        /* Animated gradient background — full viewport */
        .bg-animated {
            background: linear-gradient(-45deg, #f8fafc, #f1f5f9, #e2e8f0, #f8fafc);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
        }
        .dark .bg-animated {
            background: linear-gradient(-45deg, #0f172a, #1e293b, #0f172a, #1e293b);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
        }
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Floating animation for icon */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
        }
        .animate-float { animation: float 4s ease-in-out infinite; }

        /* Pulse glow for error code */
        @keyframes pulseGlow {
            0%, 100% { opacity: 0.15; transform: scale(1); }
            50% { opacity: 0.25; transform: scale(1.1); }
        }
        .animate-pulse-glow { animation: pulseGlow 3s ease-in-out infinite; }

        /* Fade in up animation */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out forwards;
            opacity: 0;
        }
        .delay-100 { animation-delay: 0.1s; }
        .delay-200 { animation-delay: 0.2s; }
        .delay-300 { animation-delay: 0.3s; }
        .delay-400 { animation-delay: 0.4s; }
        .delay-500 { animation-delay: 0.5s; }

        /* Shine effect on buttons */
        .btn-shine {
            position: relative;
            overflow: hidden;
        }
        .btn-shine::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }
        .btn-shine:hover::before { left: 100%; }

        /* Gradient text animated */
        .text-gradient-animated {
            background: linear-gradient(135deg, #0ea5e9, #6366f1, #8b5cf6, #ec4899);
            background-size: 300% 300%;
            animation: gradientText 6s ease infinite;
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        @keyframes gradientText {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        /* Glass morphism */
        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .dark .glass {
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Mobile: Reduce background blob opacity to prevent overflow */
        @media (max-width: 640px) {
            .blob-mobile-hide { display: none; }
        }

        /* Prevent horizontal scroll */
        .no-scroll-x { overflow-x: clip; }
    </style>
</head>
<body class="bg-animated no-scroll-x">

    <!-- ✅ Wrapping container: fills viewport, uses flexbox for sticky footer -->
    <div class="min-h-screen flex flex-col relative">

        <!-- Decorative background elements (scoped to wrapper, not fixed) -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
            <div class="blob-mobile-hide absolute -top-40 -right-40 w-80 h-80 bg-primary-400/20 dark:bg-primary-500/10 rounded-full blur-3xl"></div>
            <div class="blob-mobile-hide absolute -bottom-40 -left-40 w-80 h-80 bg-purple-400/20 dark:bg-purple-500/10 rounded-full blur-3xl"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-64 h-64 sm:w-96 sm:h-96 bg-blue-400/10 dark:bg-blue-500/5 rounded-full blur-3xl"></div>
        </div>

        <!-- Navigation -->
        <nav class="glass border-b border-gray-200/50 dark:border-gray-700/50 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-14 sm:h-16 items-center">
                    <div class="flex items-center">
                        <a href="{{ url('/') }}" class="flex items-center gap-2 text-lg sm:text-xl font-bold text-gray-900 dark:text-white group">
                            <svg class="w-7 h-7 sm:w-9 sm:h-9 text-primary-600 dark:text-primary-400 transition-transform group-hover:scale-110 group-hover:rotate-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 10.5V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h9.5" />
                                <polyline points="16 2 22 8 16 8" />
                                <line x1="10" y1="14" x2="21" y2="14" />
                                <line x1="10" y1="18" x2="18" y2="18" />
                                <line x1="3" y1="10" x2="8" y2="10" />
                            </svg>
                            <span class="bg-gradient-to-r from-gray-900 to-gray-700 dark:from-white dark:to-gray-300 bg-clip-text text-transparent hidden xs:inline">
                                Omniscient
                            </span>
                        </a>
                    </div>

                    <div class="flex items-center gap-2 sm:gap-3">
                        <a href="{{ url('/') }}" class="text-xs sm:text-sm text-gray-600 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition-colors font-medium">
                            Home
                        </a>
                        @auth
                            <a href="{{ url('/dashboard') }}" class="btn-shine px-3 sm:px-4 py-1.5 sm:py-2 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-lg hover:from-primary-700 hover:to-primary-800 transition-all text-xs sm:text-sm font-medium shadow-lg shadow-primary-500/30">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ url('/login') }}" class="btn-shine px-3 sm:px-4 py-1.5 sm:py-2 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-lg hover:from-primary-700 hover:to-primary-800 transition-all text-xs sm:text-sm font-medium shadow-lg shadow-primary-500/30">
                                Sign In
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="flex-1 flex items-center justify-center py-8 sm:py-12 px-4 sm:px-6 lg:px-8 relative">
            <div class="max-w-2xl w-full text-center">

                <!-- Error Code with Animation -->
                <div class="relative mb-2 sm:mb-4 animate-fade-in-up">
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="w-40 h-40 sm:w-64 sm:h-64 lg:w-96 lg:h-96 rounded-full bg-gradient-to-br from-primary-500/30 to-purple-500/30 blur-3xl animate-pulse-glow"></div>
                    </div>

                    <div class="relative">
                        <div class="text-[100px] xs:text-[120px] sm:text-[160px] lg:text-[220px] font-black leading-none text-gradient-animated select-none tracking-tighter">
                            @yield('code', '404')
                        </div>
                        <div class="hidden sm:block absolute top-1/4 left-1/4 w-2 h-2 bg-primary-500/40 rounded-full animate-ping"></div>
                        <div class="hidden sm:block absolute bottom-1/4 right-1/4 w-3 h-3 bg-purple-500/40 rounded-full animate-ping" style="animation-delay: 1s;"></div>
                    </div>
                </div>

                <!-- Icon -->
                <div class="mb-4 sm:mb-6 flex justify-center animate-fade-in-up delay-100">
                    <div class="animate-float">
                        @yield('icon')
                    </div>
                </div>

                <!-- Title -->
                <h1 class="text-2xl xs:text-3xl sm:text-4xl font-bold text-gray-900 dark:text-white mb-3 sm:mb-4 animate-fade-in-up delay-200 tracking-tight px-2">
                    @yield('title')
                </h1>

                <!-- Description -->
                <p class="text-sm sm:text-base lg:text-lg text-gray-600 dark:text-gray-400 mb-6 sm:mb-8 max-w-lg mx-auto animate-fade-in-up delay-300 leading-relaxed px-2">
                    @yield('description')
                </p>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-2 sm:gap-3 animate-fade-in-up delay-400 px-2 sm:px-0">
                    @yield('actions')
                    <a href="{{ url('/') }}" class="btn-shine group inline-flex items-center justify-center gap-2 px-6 py-3 sm:py-3.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all font-semibold shadow-xl shadow-primary-500/40 hover:shadow-primary-500/60 hover:-translate-y-0.5 text-sm sm:text-base">
                        <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Go Home
                    </a>
                    @yield('extra-actions')
                </div>

                <!-- Help Text -->
                @hasSection('help')
                    <div class="mt-8 sm:mt-10 pt-6 sm:pt-8 border-t border-gray-200/50 dark:border-gray-700/50 animate-fade-in-up delay-500 px-2">
                        <div class="inline-flex items-start gap-2 sm:gap-3 px-3 sm:px-4 py-3 bg-white/50 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-200/50 dark:border-gray-700/50 max-w-md mx-auto text-left">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-primary-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                                @yield('help')
                            </p>
                        </div>
                    </div>
                @endif
            </div>
        </main>

        <!-- Footer -->
        <footer class="glass border-t border-gray-200/50 dark:border-gray-700/50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-5">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 sm:gap-4">
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 text-center sm:text-left order-2 sm:order-1">
                        &copy; {{ date('Y') }} Omniscient Directory. All rights reserved.
                    </p>
                    <div class="flex items-center gap-4 sm:gap-6 text-xs sm:text-sm order-1 sm:order-2">
                        <a href="{{ url('/about') }}" class="text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">About</a>
                        <a href="{{ url('/contact') }}" class="text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Contact</a>
                        <a href="{{ url('/directory') }}" class="text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Directory</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <!-- Dark mode script -->
    <script>
        (function() {
            const stored = localStorage.getItem('dark_mode');
            if (stored === 'true' || (!stored && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
</body>
</html>