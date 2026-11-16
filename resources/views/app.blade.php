<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#8e66cc">
    <title>Barriopoli</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="/favicon.ico" sizes="32x32">
    {{-- La fuente es crítica para el primer render (títulos): se precarga. --}}
    <link rel="preload" href="/fonts/Rosario-Variable-latin.woff2" as="font" type="font/woff2" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>
<body>
    @inertia
</body>
</html>