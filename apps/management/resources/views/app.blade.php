<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title inertia>{{ config('app.name', 'Aion3 Knowledge Management') }}</title>
    <script>
        try {
            document.documentElement.dataset.theme = localStorage.getItem('aion3-theme') === 'dark' ? 'dark' : 'light';
        } catch (_) {}
    </script>
    {{-- Thai-first webfont --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=noto-sans-thai:400,500,600,700" rel="stylesheet">

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.tsx'])
    @inertiaHead
</head>
<body class="h-full bg-slate-50 font-sans text-slate-900 antialiased">
    @inertia
</body>
</html>
