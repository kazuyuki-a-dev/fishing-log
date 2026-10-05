<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=BIZ+UDPGothic:wght@400;700&family=Yomogi&display=swap" rel="stylesheet">
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased">
    <x-crayon-filters />
    <div class="min-h-screen crayon-paper">
        @include('layouts.navigation')

        {{-- 本文の紙（#108）。開いているページの付箋の色にする。どの付箋にも入らないページはメモ帳の紙の色のまま --}}
        @php
        $sectionColors = $currentSection ? $sections[$currentSection] : null;
        @endphp
        <div class="crayon-section-paper"
            @if ($sectionColors) style="--section: {{ $sectionColors['tab'] }}; --section-paper: {{ $sectionColors['paper'] }}; --section-line: {{ $sectionColors['line'] }};" @endif>
            <!-- Page Heading -->
            @isset($header)
            <header>
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
        <footer class="py-6 text-center text-xs text-sand space-x-4">
            <a href="{{ route('terms') }}" class="hover:underline">利用規約</a>
            <a href="{{ route('privacy') }}" class="hover:underline">プライバシーポリシー</a>
        </footer>
    </div>
</body>

</html>