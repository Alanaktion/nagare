<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ config('app.name', 'Nagare') }}</title>

        <!-- Scripts -->
        <script>
            const media = window.matchMedia('(prefers-color-scheme: dark)')
            if (localStorage.theme === 'dark' || (!('theme' in localStorage) && media.matches)) {
                document.querySelector('html').classList.add('dark')
            } else {
                document.querySelector('html').classList.remove('dark')
            }
            media.addListener(m => {
                if ('theme' in localStorage) {
                    return;
                }
                const cl = document.querySelector('html').classList;
                if (m.matches) {
                    cl.add('dark')
                } else {
                    cl.remove('dark')
                }
            })
        </script>
        @routes
        @vite(['resources/js/app.js', 'resources/css/app.css'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-white dark:bg-neutral-900 dark:text-gray-100">
        @inertia
    </body>
</html>
