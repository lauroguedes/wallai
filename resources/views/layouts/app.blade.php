<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="application-name" content="WallAI">
        <meta name="apple-mobile-web-app-title" content="WallAI">
        <meta name="theme-color" media="(prefers-color-scheme: light)" content="#f8fafc">
        <meta name="theme-color" media="(prefers-color-scheme: dark)" content="#15191e">

        <title>{{ config('app.name', 'Wallai - Create your own phone wallpaper') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
        <link rel="manifest" href="/site.webmanifest">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen antialiased content-center">
        {{ $slot }}
    </body>
</html>
