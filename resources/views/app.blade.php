<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Nagare') }}</title>

        <!-- Styles -->
        <link rel="stylesheet" href="{{ mix('css/app.css') }}">

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
        <script src="{{ mix('js/app.js') }}" defer></script>
    </head>
    <body class="font-sans antialiased bg-white dark:bg-trueGray-900 dark:text-gray-100">
        @inertia
    </body>
</html>
