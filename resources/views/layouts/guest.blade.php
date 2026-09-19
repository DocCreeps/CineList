<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? 'Cinélist' }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="flex min-h-screen flex-col bg-zinc-950 text-zinc-100 antialiased selection:bg-amber-500 selection:text-zinc-950">
        <div class="flex flex-1 items-center justify-center px-4 py-10">
            <div class="w-full max-w-sm">
                <a href="{{ route('home') }}" class="mb-8 flex items-center justify-center gap-3" wire:navigate>
                    <span class="grid h-11 w-11 place-items-center rounded-2xl bg-gradient-to-br from-amber-500 to-red-600 text-xl font-black text-white shadow-lg shadow-amber-950/40 ring-1 ring-amber-400/30">🍿</span>
                    <span class="text-2xl font-black tracking-wider uppercase text-zinc-100">Cinélist</span>
                </a>

                <div class="rounded-2xl border border-zinc-800/80 bg-zinc-900/60 p-6 shadow-2xl sm:p-8">
                    {{ $slot }}
                </div>
            </div>
        </div>

        <x-site-footer />

        <x-toast />
        <x-toast-bridge />

        @livewireScripts
    </body>
</html>
