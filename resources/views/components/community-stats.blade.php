@props([
    'totalFilms',
    'watchedTotal',
    'genreCounts',
    'topGenreCount',
    'topDirectors',
    'topStudios',
    'favoriteFilms',
    'mostAnticipated' => null,
    // Vus au cinéma / en streaming, tous membres confondus (films actuellement "watched" uniquement)
    // — voir App\Actions\Stats\ComputeCommunityStats.
    'cinemaCount' => 0,
    'streamingCount' => 0,
])

{{--
    Statistiques agrégées, tous membres confondus, sans aucun détail nominatif : ni liste de
    comptes, ni répartition individuelle par membre. Alimenté par App\Actions\Stats\ComputeCommunityStats.
    Utilisé sur la page « Bilan » (accessible à tout membre) et en tête de la vue d'ensemble admin
    (identique, l'admin y ajoutant seulement sa propre carte « Membres » — voir
    resources/views/livewire/admin/members.blade.php).
--}}
<div {{ $attributes }}>

    <!-- Chiffres clés -->
    <section class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="cine-card p-5">
            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">Films au total</p>
            <p class="mt-1 text-3xl font-black tracking-tight text-zinc-100">{{ $totalFilms }}</p>
            <p class="mt-0.5 text-xs text-zinc-500">toutes listes confondues</p>
        </div>
        <div class="cine-card p-5">
            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">Films vus</p>
            <p class="mt-1 text-3xl font-black tracking-tight text-zinc-100">{{ $watchedTotal }}</p>
            <p class="mt-0.5 text-xs text-zinc-500">{{ $totalFilms > 0 ? round($watchedTotal / $totalFilms * 100) : 0 }} % des films</p>
        </div>
        <div class="cine-card p-5">
            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">Vus au cinéma</p>
            <p class="mt-1 text-3xl font-black tracking-tight text-amber-400">{{ $cinemaCount }}</p>
        </div>
        <div class="cine-card p-5">
            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">Vus en streaming</p>
            <p class="mt-1 text-3xl font-black tracking-tight text-violet-400">{{ $streamingCount }}</p>
        </div>
    </section>

    <!-- Favoris, tous membres confondus (réalisateurs, studios et genres : films déjà vus ; films
         préférés : tous statuts). Podiums de 3. Le genre favori n'a plus sa propre carte dans les
         chiffres clés ci-dessus : il apparaît déjà en tête du podium « Top 3 genres ». -->
    <section class="mt-3 grid gap-3 md:grid-cols-2">
        @php
            $podiums = [
                [
                    'title' => 'Top 3 genres',
                    'entries' => $genreCounts->take(3),
                    'empty' => 'aucun genre renseigné',
                    'card' => 'border-sky-800/40 bg-sky-950/20',
                    'label' => 'text-sky-500/80',
                    'name' => 'text-sky-400',
                ],
                [
                    'title' => 'Top 3 réalisateurs',
                    'entries' => $topDirectors,
                    'empty' => 'aucun réalisateur renseigné',
                    'card' => 'border-violet-800/40 bg-violet-950/20',
                    'label' => 'text-violet-500/80',
                    'name' => 'text-violet-400',
                ],
                [
                    'title' => 'Top 3 studios',
                    'entries' => $topStudios,
                    'empty' => 'aucun studio renseigné',
                    'card' => 'border-emerald-800/40 bg-emerald-950/20',
                    'label' => 'text-emerald-500/80',
                    'name' => 'text-emerald-400',
                ],
            ];
        @endphp
        @foreach ($podiums as $podium)
        <div class="rounded-2xl border {{ $podium['card'] }} p-5">
            <p class="text-[10px] font-bold uppercase tracking-[0.2em] {{ $podium['label'] }}">{{ $podium['title'] }}</p>
            @if ($podium['entries']->isEmpty())
            <p class="mt-1 truncate text-2xl font-black tracking-tight {{ $podium['name'] }}">—</p>
            <p class="mt-0.5 text-xs text-zinc-500">{{ $podium['empty'] }}</p>
            @else
            <ol class="mt-2.5 space-y-2">
                @foreach ($podium['entries'] as $name => $count)
                <li class="flex items-center gap-3">
                    <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full text-[10px] font-black {{ $loop->first ? 'bg-amber-500 text-zinc-950' : 'bg-zinc-800 text-zinc-400' }}">{{ $loop->iteration }}</span>
                    <span class="min-w-0 flex-1 truncate {{ $loop->first ? 'text-2xl font-black tracking-tight '.$podium['name'] : 'text-sm font-bold text-zinc-300' }}" title="{{ $name }}">{{ $name }}</span>
                    <span class="shrink-0 text-xs text-zinc-500">{{ $count }} film{{ $count > 1 ? 's' : '' }} vu{{ $count > 1 ? 's' : '' }}</span>
                </li>
                @endforeach
            </ol>
            @endif
        </div>
        @endforeach

        {{-- Films les plus attendus : films "à voir" pas encore
             sortis, les plus ajoutés — voir App\Actions\Stats\ComputeCommunityStats. Uniquement
             dans le bilan collectif. --}}
        @php($mostAnticipated ??= collect())
        <div class="rounded-2xl border border-amber-800/40 bg-amber-950/20 p-5">
            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-amber-500/80">Films les plus attendus</p>
            @if ($mostAnticipated->isEmpty())
            <p class="mt-1 truncate text-2xl font-black tracking-tight text-amber-400">—</p>
            <p class="mt-0.5 text-xs text-zinc-500">aucun film « à voir » pas encore sorti</p>
            @else
            <p class="mt-0.5 text-xs text-zinc-500">« À voir », pas encore sortis, les plus ajoutés.</p>
            <ol class="mt-3 space-y-2.5">
                @foreach ($mostAnticipated as $film)
                <li class="flex items-center gap-3">
                    <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full text-[10px] font-black {{ $loop->first ? 'bg-amber-500 text-zinc-950' : 'bg-zinc-800 text-zinc-400' }}">{{ $loop->iteration }}</span>
                    @if ($film['poster_url'])
                    <img src="{{ $film['poster_url'] }}" alt="Affiche de {{ $film['title'] }}" loading="lazy" class="h-14 w-10 shrink-0 rounded-md object-cover">
                    @endif
                    <div class="min-w-0">
                        <p class="line-clamp-2 break-words text-sm font-bold leading-snug text-amber-300" title="{{ $film['title'] }}">{{ $film['title'] }}</p>
                        <p class="mt-0.5 text-[11px] text-zinc-500">ajouté par {{ $film['adds'] }} membre{{ $film['adds'] > 1 ? 's' : '' }}</p>
                    </div>
                </li>
                @endforeach
            </ol>
            @endif
        </div>
    </section>

    {{-- Films préférés, tous membres confondus, mis en avant en pleine largeur sous les podiums —
         voir resources/views/components/favorite-films-carousel.blade.php. --}}
    <x-favorite-films-carousel :favorite-films="$favoriteFilms" mode="community" size="large" class="mt-3" />

    <!-- Films par catégorie (genre), tous membres confondus -->
    <section class="cine-card mt-3 p-6 sm:p-8">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-bold uppercase tracking-wide text-zinc-300">Films par catégorie</h2>
            <span class="rounded-full bg-zinc-800 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-zinc-400">{{ $totalFilms }} film{{ $totalFilms > 1 ? 's' : '' }} au total</span>
        </div>

        @if ($genreCounts->isEmpty())
        <p class="mt-4 text-sm text-zinc-500">Aucun genre renseigné pour le moment.</p>
        @else
        <div class="mt-5 grid gap-x-10 gap-y-3 md:grid-cols-2">
            @foreach ($genreCounts as $genre => $count)
            <div>
                <div class="mb-1 flex items-baseline justify-between text-xs">
                    <span class="font-bold text-zinc-300">{{ $genre }}</span>
                    <span class="text-zinc-500">{{ $count }} film{{ $count > 1 ? 's' : '' }}</span>
                </div>
                <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-800">
                    <div class="h-full rounded-full bg-gradient-to-r from-amber-500 to-red-600" style="width: {{ $topGenreCount ? round($count / $topGenreCount * 100) : 0 }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </section>
</div>
