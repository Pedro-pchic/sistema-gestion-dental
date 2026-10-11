<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <title>@yield('title', 'Acceso') | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-espresso text-stone-900 antialiased">
    <main class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-12">
        <div class="absolute inset-x-0 top-0 h-64 bg-brand-900/35" aria-hidden="true"></div>
        <div class="absolute -right-24 -top-24 size-80 rounded-full border border-white/8" aria-hidden="true"></div>
        <div class="absolute -bottom-40 -left-32 size-96 rounded-full border border-brand-500/15" aria-hidden="true"></div>
        <div class="relative w-full max-w-md">
            @yield('content')
        </div>
    </main>
</body>
</html>
