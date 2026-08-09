<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Xelqun is dark-only; keep the background painted before the app boots to avoid a flash. --}}
        <style>
            html {
                background-color: #0b0d0e;
            }
        </style>

        <link rel="icon" href="/favicon.ico?v=3" sizes="any">
        <link rel="icon" href="/favicon.svg?v=3" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png?v=3">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
