@php
    $links = [
        'settings.profile' => 'Profil',
        'settings.password' => 'Mot de passe',
        'settings.two-factor' => 'Double authentification',
        'settings.sessions' => 'Sessions actives',
    ];
@endphp

<nav class="flex flex-wrap gap-1.5 border-b border-zinc-800 pb-4" aria-label="Navigation des paramètres">
    @foreach ($links as $routeName => $label)
    <a href="{{ route($routeName) }}" wire:navigate
        @class([
            'rounded-xl px-3.5 py-2 text-xs font-bold uppercase tracking-wide transition',
            'bg-amber-500 text-zinc-950 shadow-lg shadow-amber-950/40' => request()->routeIs($routeName),
            'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-800/60' => ! request()->routeIs($routeName),
        ])>
        {{ $label }}
    </a>
    @endforeach
</nav>
