<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ config('app.name', 'Wallai - Create your own phone wallpaper') }}</title>

        @vite('resources/css/app.css')
    </head>
    <body class="min-h-screen antialiased content-center">
        {{ $slot }}
    </body>
</html>
