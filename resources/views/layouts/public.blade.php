<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $settings->description ?: 'Conoce nuestros servicios odontológicos y solicita una cita.' }}">
    <title>{{ $settings->clinic_name ?: 'Clínica dental' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 font-sans text-slate-800 antialiased">
    <a href="#contenido" class="sr-only fixed left-4 top-4 z-50 rounded-xl bg-white px-4 py-3 font-semibold focus:not-sr-only">Saltar al contenido</a>
    @yield('content')
</body>
</html>
