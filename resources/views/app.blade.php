<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b3d0b">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title inertia>{{ \App\Models\SystemSetting::getValue('app_name', config('app.name', 'NA E-Ticket')) }}</title>
    @php
        $__logoUrl = \App\Models\BrandingLogo::url();
    @endphp
    @if($__logoUrl)
        <link rel="icon" href="{{ $__logoUrl }}">
        <link rel="apple-touch-icon" href="{{ $__logoUrl }}">
    @endif
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600&family=jetbrains-mono:400" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    @inertiaHead
</head>
<body class="font-sans antialiased bg-slate-50">
    @inertia
</body>
</html>
