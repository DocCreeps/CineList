<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? 'Cinélist' }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-zinc-950 text-zinc-100 antialiased selection:bg-amber-500 selection:text-zinc-950">
        <div class="sticky top-0 z-50 mb-10 border-b border-zinc-800/80 bg-zinc-950/85 backdrop-blur-sm">
            <div class="mx-auto max-w-7xl px-4 sm:px-8 lg:px-12">
                <x-site-nav />
            </div>
        </div>

        {{ $slot }}

        <x-site-footer />

        @livewireScripts
    </body>
</html>
