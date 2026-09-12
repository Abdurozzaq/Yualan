<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Icons & PWA -->
        <link rel="icon" type="image/png" sizes="32x32" href="/ios/32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/ios/16.png">
        <link rel="icon" type="image/png" sizes="192x192" href="/android/launchericon-192x192.png">
        <link rel="apple-touch-icon" sizes="180x180" href="/ios/180.png">
        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#2563eb">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="Yualan POS">
        <meta name="msapplication-TileImage" content="/windows/Square150x150Logo.scale-100.png">
        <meta name="msapplication-TileColor" content="#2563eb">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
        <script>
            window.addEventListener('focus', () => {
                document.body.style.pointerEvents = 'auto';
            });
        </script>

        @routes
        @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
