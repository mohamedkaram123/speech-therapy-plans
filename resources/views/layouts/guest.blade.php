<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=cairo:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased">
        <div class="min-h-screen flex flex-col justify-center items-center px-4 py-8 bg-gradient-to-b from-teal-50 to-slate-100">
            <a href="/" class="flex flex-col items-center text-teal-700">
                <x-application-logo class="w-16 h-16" />
                <span class="mt-2 text-lg font-bold text-slate-800">{{ config('app.name') }}</span>
                <span class="text-xs text-slate-500">{{ config('speech.center_name') }}</span>
            </a>

            <div class="w-full sm:max-w-md mt-6 px-6 py-6 bg-white shadow-md border border-slate-200 rounded-xl">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
