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
    @php($isSupervisor = auth()->user()?->isSupervisor())
    <body class="font-sans min-h-screen {{ $isSupervisor ? 'role-supervisor' : 'role-teacher' }}">
        <div class="min-h-screen flex flex-col">
            @include('layouts.navigation')

            @isset($header)
                <header class="app-header no-print">
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

            <footer class="app-footer text-center text-xs py-4 no-print">
                {{ config('speech.center_name') }} &middot; {{ config('speech.center_dept') }}
                @if($isSupervisor)
                    <span class="mx-1">·</span>
                    <span class="font-semibold text-amber-800/80">وضع المشرف</span>
                @endif
            </footer>
        </div>
    </body>
</html>
