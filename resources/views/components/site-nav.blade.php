{{--
    Barre de navigation principale, utilisée par le layout sur toutes les pages.
    Pour ajouter/retirer/renommer un lien de nav : modifier uniquement le tableau ci-dessous.
--}}
@php
    $links = [
        'home' => 'Accueil',
        'watchlist.dashboard' => 'Tableau de bord',
        'search.index' => 'Recherche',
        'upcoming.index' => 'À venir',
        'stats.index' => 'Bilan',
    ];
@endphp

<header class="flex flex-wrap items-center justify-between gap-4 py-4 lg:py-5">
    <a href="{{ route('home') }}" class="group flex items-center gap-3.5" wire:navigate>
        <span class="grid h-11 w-11 place-items-center rounded-2xl bg-gradient-to-br from-amber-500 to-red-600 text-xl font-black text-white shadow-lg shadow-amber-950/40 ring-1 ring-amber-400/30 transition-transform duration-300 group-hover:scale-105">🍿</span>
        <span>
            <span class="block text-2xl font-black tracking-wider uppercase text-zinc-100 group-hover:text-amber-400 transition-colors">Cinélist</span>
            <span class="block text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">Watch & Rewatch</span>
        </span>
    </a>

    <nav class="flex flex-wrap items-center gap-1.5" aria-label="Navigation principale">
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

        <form method="POST" action="{{ route('logout') }}" class="ml-2 flex items-center gap-2.5 border-l border-zinc-800 pl-3">
            @csrf
            @if (auth()->user()->isAdmin())
                <a href="{{ route('admin.invitations') }}" wire:navigate
                    @class([
                        'rounded-xl px-3 py-2 text-xs font-bold uppercase tracking-wide transition',
                        'bg-amber-500 text-zinc-950 shadow-lg shadow-amber-950/40' => request()->routeIs('admin.*'),
                        'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-800/60' => ! request()->routeIs('admin.*'),
                    ])>
                    Admin
                </a>
            @endif
            <a href="{{ route('settings.profile') }}" wire:navigate class="rounded-xl px-3 py-2 text-xs font-bold uppercase tracking-wide text-zinc-400 transition hover:bg-zinc-800/60 hover:text-zinc-100">
                {{ auth()->user()->name }}
            </a>
            <button type="submit" class="rounded-xl px-3 py-2 text-xs font-bold uppercase tracking-wide text-zinc-400 transition hover:bg-zinc-800/60 hover:text-red-400">
                Déconnexion
            </button>
        </form>
    </nav>
</header>
