@props([
    // Tableau issu de ComputeWatchlistStats::summarize() (année en cours ou ensemble des films).
    'stats',
    'eyebrow',
    'title',
    'watchedHint' => '',
    // Discriminant des clés Livewire du carrousel (« year » / « general »).
    'scope',
])

{{--
    Bloc de bilan personnel : chiffres clés, top 3 genres, top 3 réalisateurs, top 3 studios et
    films préférés. Utilisé deux fois sur la page Bilan, côte à côte : « année en cours » et
    « stats générales ».
--}}
@php
    $podiums = [
        [
            'title' => 'Top 3 genres',
            'entries' => $stats['topGenres'],
            'card' => 'border-sky-800/40 bg-sky-950/20',
            'label' => 'text-sky-500/80',
            'name' => 'text-sky-400',
        ],
        [
            'title' => 'Top 3 réalisateurs',
            'entries' => $stats['topDirectors'],
            'card' => 'border-violet-800/40 bg-violet-950/20',
            'label' => 'text-violet-500/80',
            'name' => 'text-violet-400',
        ],
        [
            'title' => 'Top 3 studios',
            'entries' => $stats['topStudios'],
            'card' => 'border-emerald-800/40 bg-emerald-950/20',
            'label' => 'text-emerald-500/80',
            'name' => 'text-emerald-400',
        ],
    ];
@endphp
<section {{ $attributes }}>
    <div class="border-b border-zinc-800 pb-3">
        <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-amber-400">{{ $eyebrow }}</p>
        <h2 class="font-display mt-1 text-2xl tracking-wide text-zinc-100">{{ $title }}</h2>
    </div>

    <x-personal-stat-cards class="mt-6" :stats="$stats" :watched-hint="$watchedHint" />

    <div class="mt-3 grid gap-3 md:grid-cols-3 xl:grid-cols-1">
        @foreach ($podiums as $podium)
        <div class="rounded-2xl border {{ $podium['card'] }} p-5">
            <p class="text-[10px] font-bold uppercase tracking-[0.2em] {{ $podium['label'] }}">{{ $podium['title'] }}</p>
            @if ($podium['entries']->isEmpty())
            <p class="mt-1 truncate text-2xl font-black tracking-tight {{ $podium['name'] }}">—</p>
            <p class="mt-1 text-xs text-zinc-500">Pas assez de données.</p>
            @else
            <ol class="mt-3 space-y-2.5">
                @foreach ($podium['entries'] as $name => $count)
                <li class="flex items-center gap-3">
                    <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full text-[10px] font-black {{ $loop->first ? 'bg-amber-500 text-zinc-950' : 'bg-zinc-800 text-zinc-400' }}">{{ $loop->iteration }}</span>
                    <span class="min-w-0 flex-1 truncate {{ $loop->first ? 'text-2xl font-black tracking-tight '.$podium['name'] : 'text-sm font-bold text-zinc-300' }}" title="{{ $name }}">{{ $name }}</span>
                    <span class="shrink-0 text-xs text-zinc-500">{{ $count }} film{{ $count > 1 ? 's' : '' }}</span>
                </li>
                @endforeach
            </ol>
            @endif
        </div>
        @endforeach
    </div>

    {{-- Films préférés sous 2 angles (nb de vues seul / note seule) : voir Favorites::filmsForUser() --}}
    <x-favorite-films-carousel class="mt-3" :favorite-films="$stats['favoriteFilms']" mode="personal" :scope="$scope" />
</section>
