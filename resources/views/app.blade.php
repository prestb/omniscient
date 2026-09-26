<!-- resources/views/app.blade.php -->
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ config('app.name', 'Omniscient') }}</title>

        <!-- PWA Manifest -->
        <link rel="manifest" href="/manifest.json">

        <!-- Theme color (affects browser chrome + Android status bar) -->
        <meta name="theme-color" content="#0284c7">

        <!-- Favicon -->
        <link rel="icon" type="image/x-icon" href="/favicon.ico">
        <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon-32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/images/favicon-16.png">

        <!-- Apple Touch Icons (iOS home screen) -->
        <link rel="apple-touch-icon" sizes="180x180" href="/images/icon-180.png">
        <link rel="apple-touch-icon" sizes="167x167" href="/images/icon-167.png">
        <link rel="apple-touch-icon" sizes="152x152" href="/images/icon-152.png">
        <link rel="apple-touch-icon" sizes="120x120" href="/images/icon-120.png">

        <!-- iOS PWA meta -->
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="Omniscient">

        <!-- Android / Chrome PWA -->
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="application-name" content="Omniscient">

        <!--
            ✅ First-paint theme hydration.
            Runs BEFORE the browser paints, so there is no flash of light mode
            when the user has chosen dark. Must mirror the localStorage contract
            of resources/js/composables/useDarkMode.js exactly:
              - key:   'dark_mode'
              - value: 'true' (dark) | 'false' (light)
              - fallback when key is absent: follow OS preference
        -->
        <script>
            (function () {
                try {
                    var stored = localStorage.getItem('dark_mode');
                    var isDark = stored !== null
                        ? stored === 'true'
                        : window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (isDark) {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                } catch (e) {
                    // localStorage unavailable (private mode, etc.)
                    // Fall back to OS preference only.
                    if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', 'resources/css/app.css'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>