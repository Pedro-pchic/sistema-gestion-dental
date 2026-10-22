<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('logo.png') }}" type="image/png">
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/inertia.jsx'])
    <x-inertia::head />
</head>
<body class="min-h-screen bg-cream text-coffee antialiased">
    <x-inertia::app />
</body>
</html>
