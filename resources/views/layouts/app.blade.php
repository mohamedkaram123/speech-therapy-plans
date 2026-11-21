<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title.' - ' : '' }}{{ config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=cairo:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans min-h-screen">
        <div class="min-h-screen flex flex-col">
            @include('layouts.navigation')

            @isset($header)
                <header class="bg-white border-b border-slate-200 no-print">
                    <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8 flex flex-wrap items-center justify-between gap-3">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main class="flex-1">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    @include('layouts.flash')
                    {{ $slot }}
                </div>
            </main>

            <footer class="text-center text-xs text-slate-400 py-4 no-print">
                {{ config('speech.center_name') }} &middot; {{ config('speech.center_dept') }}
            </footer>
        </div>
    </body>
</html>
