@props([
    'favoriteFilms',
    // 'community' (tous les membres) ou 'personal' (un seul utilisateur) : voir Favorites::filmsForUser()
    // et Favorites::filmsAcrossMembers() pour ce que chaque angle représente dans chaque mode.
    'mode' => 'community',
    // Discriminant pour la clé Livewire quand plusieurs carrousels de même mode sont sur la page
    // (ex. bilan personnel « année en cours » et « général »).
    'scope' => '',
    // 'normal' (par défaut, imbriqué dans une grille avec d'autres cartes) ou 'large' : version mise
    // en avant, en pleine largeur sous les podiums du bilan collectif/admin (affiches et textes agrandis).
    'size' => 'normal',
])

{{--
    Carrousel des films préférés sous 2 angles (nombre de fois vu seul / ajouts ou note seule) :
    voir Favorites::filmsAcrossMembers, Favorites::filmsForUser et Favorites::withoutDuplicates
    (mode "community" uniquement, jamais appliqué en mode "personal" — voir filmsForUser()). Chaque
    diapositive montre le n° 1 de l'angle, avec les n° 2 et 3 à côté. Utilisé à la fois par
    community-stats.blade.php (mode "community") et par la carte "Films préférés" du bilan
    personnel (mode "personal").
--}}
@php
    $isPersonal = $mode === 'personal';
    $isLarge = $size === 'large';

    // Mode personnel : 2 angles — film préféré (nb de fois vu), le mieux noté. Mode communauté :
    // film préféré (nb de fois vu, cf. groupFilms()), le plus ajouté (nombre de membres), le mieux
    // noté. Voir Favorites::filmsForUser()/filmsAcrossMembers().
    $slideDefs = $isPersonal
        ? [
            ['key' => 'general', 'label' => 'Film préféré', 'films' => $favoriteFilms['general']],
            ['key' => 'rating', 'label' => 'Le mieux noté', 'films' => $favoriteFilms['rating']],
        ]
        : [
            ['key' => 'general', 'label' => 'Film préféré', 'films' => $favoriteFilms['general']],
            ['key' => 'adds', 'label' => 'Le plus ajouté', 'films' => $favoriteFilms['adds']],
            ['key' => 'rating', 'label' => 'Le mieux noté', 'films' => $favoriteFilms['rating']],
        ];

    $slides = collect($slideDefs)->filter(fn ($slide) => $slide['films']->isNotEmpty())->values();

    $statusLabels = ['to_watch' => 'à voir', 'watched' => 'vu', 'to_rewatch' => 'à revoir'];

    // Ligne de détail d'un film, selon l'angle de la diapositive où il apparaît.
    $describeFilm = function (array $film, string $angle) use ($statusLabels, $isPersonal): string {
        $addsLabel = 'ajouté par '.$film['adds'].' membre'.($film['adds'] > 1 ? 's' : '');
        $viewsLabel = 'vu '.$film['views'].' fois';
        $ratingLabel = $film['average'] !== null
            ? $film['average'].'/5 en moyenne · '.$film['ratings'].' note'.($film['ratings'] > 1 ? 's' : '')
            : 'pas encore noté';
        $rewatchLabel = ($film['statuses']['to_rewatch'] ?? 0) > 0 ? 'marqué « à revoir »' : null;
        $statusLabel = collect($film['statuses'])
            ->filter()
            ->map(fn ($count, $status) => $count.' '.($status === 'watched' && $count > 1 ? 'vus' : $statusLabels[$status]))
            ->implode(' · ');

        return match (true) {
            $angle === 'general' && $isPersonal => collect([$viewsLabel, $rewatchLabel, $ratingLabel])->filter()->implode(' · '),
            $angle === 'general' => $addsLabel.' · '.$ratingLabel,
            $angle === 'adds' => $addsLabel.' · '.$statusLabel,
            default => $ratingLabel,
        };
    };
@endphp
{{-- Pause du défilement auto : souris (pas le tactile, où le « survol » resterait bloqué après un glissement) et focus clavier (pas après un simple clic). --}}
<div
    x-data="favoriteCarousel()"
    x-on:pointerenter="paused = $event.pointerType === 'mouse'"
    x-on:pointerleave="paused = false"
    x-on:focusin="paused = $event.target.matches(':focus-visible')"
    x-on:focusout="paused = false"
    x-on:keydown.left="step(-1)"
    x-on:keydown.right="step(1)"
    x-on:touchstart.passive="swipeStart($event)"
    x-on:touchend.passive="swipeEnd($event)"
    {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl border border-rose-800/40 bg-rose-950/20 '.($isLarge ? 'p-6 sm:p-8' : 'p-5')]) }}
>
    @if ($slides->isEmpty())
    <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-rose-500/80">Film préféré</p>
    <p class="mt-1 truncate text-2xl font-black tracking-tight text-rose-400">—</p>
    <p class="mt-0.5 text-xs text-zinc-500">{{ $isPersonal ? 'notez un film vu pour le voir ici' : 'aucun film ajouté' }}</p>
    @else
    {{-- Les diapositives sont empilées dans la même cellule de grille : la carte garde une hauteur stable pendant le fondu. --}}
    <div class="grid grid-cols-[minmax(0,1fr)]">
        @foreach ($slides as $index => $slide)
        @php
            $film = $slide['films']->first();
            $runnersUp = $slide['films']->slice(1);
        @endphp
        <div
            wire:key="favorite-slide-{{ $mode }}-{{ $scope }}-{{ $slide['key'] }}"
            data-slide
            @if ($index > 0) x-cloak @endif
            x-bind:class="active === {{ $index }} ? 'opacity-100' : 'pointer-events-none opacity-0'"
            x-bind:aria-hidden="active !== {{ $index }} ? 'true' : 'false'"
            class="col-start-1 row-start-1 grid min-w-0 {{ $isLarge ? 'gap-6' : 'gap-4' }} transition-opacity duration-500 md:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)] md:items-center {{ $isLarge ? 'md:gap-10' : 'md:gap-6' }}"
        >
            {{-- N° 1 --}}
            <div class="flex min-w-0 items-center {{ $isLarge ? 'gap-6' : 'gap-4' }}">
                @if ($film['poster_url'])
                <img src="{{ $film['poster_url'] }}" alt="Affiche de {{ $film['title'] }}" loading="lazy" class="{{ $isLarge ? 'h-36 w-24 sm:h-44 sm:w-28' : 'h-24 w-16' }} shrink-0 rounded-lg object-cover">
                @endif
                <div class="min-w-0">
                    <p class="{{ $isLarge ? 'text-xs' : 'text-[10px]' }} font-bold uppercase tracking-[0.2em] text-rose-500/80">{{ $slide['label'] }}</p>
                    <p class="mt-1 line-clamp-2 break-words {{ $isLarge ? 'text-3xl sm:text-4xl' : 'text-2xl' }} font-black leading-tight tracking-tight text-rose-400" title="{{ $film['title'] }}">{{ $film['title'] }}</p>
                    <p class="mt-1 break-words {{ $isLarge ? 'text-sm' : 'text-xs' }} text-zinc-500">{{ $describeFilm($film, $slide['key']) }}</p>
                    @if ($slide['key'] === 'general')
                    <p class="mt-0.5 text-[11px] text-zinc-600" title="Basé uniquement sur le nombre de fois vu">Score {{ $film['score'] }}/100 · nb de vues</p>
                    @endif
                </div>
            </div>

            {{-- N° 2 et 3, à côté (sous le n° 1 sur mobile) --}}
            @if ($runnersUp->isNotEmpty())
            <ol class="min-w-0 space-y-2.5 border-t border-rose-900/40 pt-3 md:border-l md:border-t-0 {{ $isLarge ? 'md:pl-8 md:pt-0' : 'md:pl-6 md:pt-0' }}">
                @foreach ($runnersUp as $runnerUp)
                <li class="flex min-w-0 items-center gap-3">
                    <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full bg-zinc-800 text-[10px] font-black text-zinc-400">{{ $loop->iteration + 1 }}</span>
                    @if ($runnerUp['poster_url'])
                    <img src="{{ $runnerUp['poster_url'] }}" alt="Affiche de {{ $runnerUp['title'] }}" loading="lazy" class="{{ $isLarge ? 'h-20 w-14' : 'h-14 w-10' }} shrink-0 rounded-md object-cover">
                    @else
                    <div class="{{ $isLarge ? 'h-20 w-14' : 'h-14 w-10' }} shrink-0 rounded-md bg-zinc-950" aria-hidden="true"></div>
                    @endif
                    <div class="min-w-0">
                        <p class="line-clamp-2 break-words {{ $isLarge ? 'text-base' : 'text-sm' }} font-bold leading-snug text-rose-300/90" title="{{ $runnerUp['title'] }}">{{ $runnerUp['title'] }}</p>
                        <p class="mt-0.5 break-words {{ $isLarge ? 'text-xs' : 'text-[11px]' }} text-zinc-500">{{ $describeFilm($runnerUp, $slide['key']) }}</p>
                    </div>
                </li>
                @endforeach
            </ol>
            @endif
        </div>
        @endforeach
    </div>

    @if ($slides->count() > 1)
    <div x-cloak class="mt-3 flex items-center justify-between gap-2">
        <div class="flex items-center gap-1.5">
            @foreach ($slides as $index => $slide)
            <button
                type="button"
                x-on:click="go({{ $index }})"
                aria-label="Afficher : {{ $slide['label'] }}"
                x-bind:aria-current="active === {{ $index }} ? 'true' : 'false'"
                x-bind:class="active === {{ $index }} ? 'w-5 bg-rose-400' : 'w-1.5 bg-zinc-700 hover:bg-zinc-500'"
                class="h-1.5 rounded-full transition-all duration-300"
            ></button>
            @endforeach
        </div>
        <div class="flex items-center gap-1">
            <button type="button" x-on:click="step(-1)" aria-label="Film précédent" class="grid h-6 w-6 place-items-center rounded-full text-zinc-500 transition hover:bg-rose-900/40 hover:text-rose-300">
                <svg viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4" aria-hidden="true"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 0 1-.02 1.06L8.832 10l3.938 3.71a.75.75 0 1 1-1.04 1.08l-4.5-4.25a.75.75 0 0 1 0-1.08l4.5-4.25a.75.75 0 0 1 1.06.02Z" clip-rule="evenodd" /></svg>
            </button>
            <button type="button" x-on:click="step(1)" aria-label="Film suivant" class="grid h-6 w-6 place-items-center rounded-full text-zinc-500 transition hover:bg-rose-900/40 hover:text-rose-300">
                <svg viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.168 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd" /></svg>
            </button>
        </div>
    </div>
    @endif
    @endif
</div>
