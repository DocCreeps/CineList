{{--
    Sous-navigation de la section admin, partagée par toutes les pages `admin.*`.
    Pour ajouter un nouvel onglet : ajouter une entrée au tableau ci-dessous.
--}}
@php
    $adminTabs = [
        'admin.invitations' => "Codes d'invitation",
        'admin.members' => 'Membres & catégories',
    ];
@endphp

<nav class="mt-4 flex flex-wrap gap-1.5" aria-label="Navigation admin">
    @foreach ($adminTabs as $routeName => $label)
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
