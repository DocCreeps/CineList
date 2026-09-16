<main class="min-h-screen">
    <div class="mx-auto max-w-7xl px-4 pb-10 sm:px-8 lg:px-12 lg:pb-14">

        <!-- Introduction de la page -->
        <div class="flex flex-col gap-4 border-b border-zinc-800 pb-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-amber-400">Ma sélection</p>
                <h1 class="mt-1 font-serif text-3xl font-normal text-zinc-100">Films à voir & à revoir</h1>
            </div>
            <div class="flex items-center gap-2.5 self-start sm:self-auto">
                @if($counts['to_watch'] > 0)
                <button wire:click="surpriseMe" class="rounded-full bg-amber-500 px-3.5 py-1.5 text-xs font-bold text-zinc-950 transition hover:bg-amber-400">
                    🎲 Surprends-moi
                </button>
                @endif
                <span class="text-xs font-semibold text-zinc-400 bg-zinc-900 border border-zinc-800 px-3.5 py-1.5 rounded-full">
                    {{ $counts['all'] }} film{{ $counts['all'] > 1 ? 's' : '' }} dans votre liste
                </span>
            </div>
        </div>

        @include('livewire.partials.notice')

        <!-- Contrôles de filtrage (multi-sélection : plusieurs statuts / sources peuvent être actifs en même temps) -->
        <div class="mt-6 flex flex-col gap-4 rounded-2xl bg-zinc-900/60 p-3 border border-zinc-800/80 backdrop-blur-sm sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap items-center gap-1.5" role="group" aria-label="Filtrer par statut">
                <span class="mr-2 text-[10px] font-bold uppercase tracking-widest text-zinc-500 pl-1">Visionnage</span>
                <button wire:click="clearStatusFilter" @class(['rounded-xl px-3.5 py-1.5 text-xs font-bold transition', 'bg-zinc-100 text-zinc-950 shadow-sm'=> empty($statusFilter), 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-800/60' => !empty($statusFilter)])>
                    Tous <span class="ml-1 opacity-60">({{ $counts['all'] }})</span>
                </button>
                <button wire:click="toggleStatusFilter('to_watch')" @class(['rounded-xl px-3.5 py-1.5 text-xs font-bold transition', 'bg-amber-500 text-zinc-950 shadow-lg shadow-amber-950/40'=> in_array('to_watch', $statusFilter, true), 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-800/60' => !in_array('to_watch', $statusFilter, true)])>
                    À voir <span class="ml-1 opacity-80">({{ $counts['to_watch'] }})</span>
                </button>
                <button wire:click="toggleStatusFilter('watched')" @class(['rounded-xl px-3.5 py-1.5 text-xs font-bold transition', 'bg-emerald-600 text-white shadow-lg shadow-emerald-950/40'=> in_array('watched', $statusFilter, true), 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-800/60' => !in_array('watched', $statusFilter, true)])>
                    Déjà vus <span class="ml-1 opacity-70">({{ $counts['watched'] }})</span>
                </button>
                <button wire:click="toggleStatusFilter('to_rewatch')" @class(['rounded-xl px-3.5 py-1.5 text-xs font-bold transition', 'bg-sky-600 text-white shadow-lg shadow-sky-950/40'=> in_array('to_rewatch', $statusFilter, true), 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-800/60' => !in_array('to_rewatch', $statusFilter, true)])>
                    À revoir <span class="ml-1 opacity-70">({{ $counts['to_rewatch'] }})</span>
                </button>
                @if($counts['stale'] > 0)
                <button wire:click="toggleStale" @class(['rounded-xl px-3.5 py-1.5 text-xs font-bold transition', 'bg-orange-600 text-white shadow-lg shadow-orange-950/40'=> $staleOnly, 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-800/60' => !$staleOnly]) title="Films « à voir » ajoutés il y a plus de 3 mois">
                    🕸️ Oubliés <span class="ml-1 opacity-80">({{ $counts['stale'] }})</span>
                </button>
                @endif
            </div>

            <div class="h-px sm:h-5 sm:w-px bg-zinc-800"></div>

            <div class="flex flex-wrap items-center gap-1.5" role="group" aria-label="Filtrer par source">
                <span class="mr-2 text-[10px] font-bold uppercase tracking-widest text-zinc-500 pl-1">Où regarder</span>
                <button wire:click="clearSourceFilter" @class(['rounded-xl px-3.5 py-1.5 text-xs font-bold transition', 'bg-zinc-100 text-zinc-950 shadow-sm'=> empty($sourceFilter), 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-800/60' => !empty($sourceFilter)])>
                    Tous
                </button>
                <button wire:click="toggleSourceFilter('cinema')" @class(['rounded-xl px-3.5 py-1.5 text-xs font-bold transition', 'bg-amber-950 text-amber-300 border border-amber-800/60'=> in_array('cinema', $sourceFilter, true), 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-800/60' => !in_array('cinema', $sourceFilter, true)])>
                    Cinéma <span class="ml-1 opacity-60">({{ $counts['cinema'] }})</span>
                </button>
                <button wire:click="toggleSourceFilter('streaming')" @class(['rounded-xl px-3.5 py-1.5 text-xs font-bold transition', 'bg-violet-950 text-violet-300 border border-violet-800/60'=> in_array('streaming', $sourceFilter, true), 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-800/60' => !in_array('streaming', $sourceFilter, true)])>
                    Streaming <span class="ml-1 opacity-60">({{ $counts['streaming'] }})</span>
                </button>
            </div>
        </div>

        <!-- Filtres genre / réalisateur / studio / année / texte + tri -->
        <div class="mt-3 flex flex-wrap items-center gap-2.5">
            <select wire:model.live="genreFilter" class="rounded-lg border border-zinc-800 bg-zinc-900 px-2.5 py-1.5 text-xs font-semibold text-zinc-300 focus:border-amber-500/50 focus:outline-none focus:ring-0">
                <option value="">Tous les genres</option>
                @foreach($genreOptions as $genre)
                <option value="{{ $genre }}">{{ $genre }}</option>
                @endforeach
            </select>
            <select wire:model.live="directorFilter" class="rounded-lg border border-zinc-800 bg-zinc-900 px-2.5 py-1.5 text-xs font-semibold text-zinc-300 focus:border-amber-500/50 focus:outline-none focus:ring-0">
                <option value="">Tous les réalisateurs</option>
                @foreach($directorOptions as $director)
                <option value="{{ $director }}">{{ $director }}</option>
                @endforeach
            </select>
            <select wire:model.live="studioFilter" class="rounded-lg border border-zinc-800 bg-zinc-900 px-2.5 py-1.5 text-xs font-semibold text-zinc-300 focus:border-amber-500/50 focus:outline-none focus:ring-0">
                <option value="">Tous les studios</option>
                @foreach($studioOptions as $studio)
                <option value="{{ $studio }}">{{ $studio }}</option>
                @endforeach
            </select>
            <input type="number" wire:model.live.debounce.400ms="minYear" placeholder="Année min" class="w-24 rounded-lg border border-zinc-800 bg-zinc-900 px-2.5 py-1.5 text-xs font-semibold text-zinc-300 outline-none placeholder:text-zinc-600 focus:border-amber-500/50 focus:ring-0">
            <input type="number" wire:model.live.debounce.400ms="maxYear" placeholder="Année max" class="w-24 rounded-lg border border-zinc-800 bg-zinc-900 px-2.5 py-1.5 text-xs font-semibold text-zinc-300 outline-none placeholder:text-zinc-600 focus:border-amber-500/50 focus:ring-0">
            <input type="search" wire:model.live.debounce.300ms="searchQuery" placeholder="Titre ou note…" class="w-40 rounded-lg border border-zinc-800 bg-zinc-900 px-2.5 py-1.5 text-xs font-semibold text-zinc-300 outline-none placeholder:text-zinc-600 focus:border-amber-500/50 focus:ring-0">

            <div class="ml-auto flex items-center gap-2">
                <span class="text-[10px] font-bold uppercase tracking-widest text-zinc-500">Trier</span>
                <select wire:model.live="sortBy" class="rounded-lg border border-zinc-800 bg-zinc-900 px-2.5 py-1.5 text-xs font-semibold text-zinc-300 focus:border-amber-500/50 focus:outline-none focus:ring-0">
                    <option value="priority">Priorité</option>
                    <option value="added_desc">Ajout récent</option>
                    <option value="year_desc">Année (récent)</option>
                    <option value="rating_desc">Note TMDB</option>
                    <option value="alpha">Alphabétique</option>
                </select>
            </div>
        </div>

        <!-- Barre d'actions groupées : apparaît dès qu'au moins un film est coché dans la grille -->
        @if(!empty($selectedIds))
        <div class="mt-3 flex flex-wrap items-center gap-2 rounded-2xl border border-amber-800/40 bg-amber-950/20 p-3">
            <span class="text-xs font-bold text-amber-300">{{ count($selectedIds) }} sélectionné{{ count($selectedIds) > 1 ? 's' : '' }}</span>
            <button wire:click="clearSelection" class="text-xs font-semibold text-zinc-400 transition hover:text-zinc-100">Désélectionner</button>
            <span class="h-4 w-px bg-zinc-700"></span>
            <button wire:click="bulkSetStatus('to_watch')" class="rounded-lg bg-zinc-800 px-2.5 py-1 text-[11px] font-bold text-zinc-200 transition hover:bg-zinc-700">○ À voir</button>
            <button wire:click="bulkSetStatus('watched')" class="rounded-lg bg-emerald-900/60 px-2.5 py-1 text-[11px] font-bold text-emerald-300 transition hover:bg-emerald-800/60">✓ Vu</button>
            <button wire:click="bulkSetStatus('to_rewatch')" class="rounded-lg bg-sky-900/60 px-2.5 py-1 text-[11px] font-bold text-sky-300 transition hover:bg-sky-800/60">↺ À revoir</button>
            <span class="h-4 w-px bg-zinc-700"></span>
            <button wire:click="bulkSetPriority(1)" class="rounded-lg bg-amber-950/80 px-2.5 py-1 text-[11px] font-bold text-amber-400 transition hover:bg-amber-900">Priorité haute</button>
            <button wire:click="bulkSetPriority(2)" class="rounded-lg bg-zinc-800 px-2.5 py-1 text-[11px] font-bold text-zinc-300 transition hover:bg-zinc-700">Priorité moyenne</button>
            <button wire:click="bulkSetPriority(3)" class="rounded-lg bg-zinc-800 px-2.5 py-1 text-[11px] font-bold text-zinc-300 transition hover:bg-zinc-700">Priorité basse</button>
            <span class="h-4 w-px bg-zinc-700"></span>
            <button
                type="button"
                x-on:click="$store.confirmModal.open(@js('Retirer ' . count($selectedIds) . ' film(s) de votre liste ?'), () => $wire.bulkRemove())"
                class="rounded-lg bg-red-950/60 px-2.5 py-1 text-[11px] font-bold text-red-400 transition hover:bg-red-900/60"
            >🗑️ Supprimer</button>
        </div>
        @endif

        <!-- Grille de films ou état vide -->
        @if ($items->isEmpty())
        <div class="mt-8 rounded-3xl border border-dashed border-zinc-800 bg-zinc-900/30 px-6 py-16 text-center">
            <div class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-zinc-800 text-xl text-amber-400 font-bold">🍿</div>
            <h3 class="mt-4 text-base font-bold text-zinc-100">
                @if ($counts['all'] > 0)
                Aucun film ne correspond à ces filtres.
                @else
                Votre liste est vide pour le moment
                @endif
            </h3>
            <p class="mt-1 text-sm text-zinc-500 max-w-sm mx-auto">
                @if ($counts['all'] > 0)
                Essayez de combiner d'autres statuts ou sources, ou réinitialisez les filtres.
                @else
                Recherchez un film pour préparer vos prochaines soirées ciné.
                @endif
            </p>
            @if ($counts['all'] === 0)
            <a href="{{ route('search.index') }}" wire:navigate class="mt-5 inline-block rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-zinc-950 transition hover:bg-amber-400">
                🔍 Chercher un film
            </a>
            @endif
        </div>
        @else
        <div class="mt-6 flex items-center justify-between">
            @php $allVisibleSelected = $items->isNotEmpty() && $items->pluck('id')->diff($selectedIds)->isEmpty(); @endphp
            <button wire:click="selectAllVisible({{ $items->pluck('id')->implode(',') }})" class="flex items-center gap-1.5 rounded-xl border border-zinc-800 bg-zinc-900 px-3.5 py-1.5 text-xs font-bold text-zinc-300 transition hover:border-zinc-700 hover:bg-zinc-800">
                @if($allVisibleSelected)
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                Tout désélectionner <span class="ml-0.5 opacity-60">({{ $items->count() }})</span>
                @else
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                Tout sélectionner <span class="ml-0.5 opacity-60">({{ $items->count() }})</span>
                @endif
            </button>
        </div>
        @if($toWatchItems->isNotEmpty())
        <div class="mt-6">
            <h2 class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-amber-400">
                ○ À voir <span class="font-normal normal-case tracking-normal text-zinc-500">({{ $toWatchItems->count() }})</span>
            </h2>
            <div class="mt-3 grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($toWatchItems as $item)
                @include('livewire.partials.movie-card', ['item' => $item])
                @endforeach
            </div>
        </div>
        @endif

        @if($toRewatchItems->isNotEmpty())
        <div class="mt-10">
            <h2 class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-sky-400">
                ↺ À revoir <span class="font-normal normal-case tracking-normal text-zinc-500">({{ $toRewatchItems->count() }})</span>
            </h2>
            <div class="mt-3 grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($toRewatchItems as $item)
                @include('livewire.partials.movie-card', ['item' => $item])
                @endforeach
            </div>
        </div>
        @endif
        @endif

        <!-- Films déjà vus : rangés dans une section repliable plutôt que d'encombrer la grille principale -->
        @if($watchedItems->isNotEmpty())
        <div class="mt-10 border-t border-zinc-800 pt-6">
            <button wire:click="toggleShowWatched" class="flex w-full items-center justify-between rounded-xl bg-zinc-900/60 border border-zinc-800/80 px-4 py-3 text-left transition hover:border-zinc-700">
                <span class="text-sm font-bold text-zinc-300">
                    🎬 Déjà vus <span class="ml-1 font-normal text-zinc-500">({{ $watchedItems->count() }})</span>
                </span>
                <svg class="h-4 w-4 text-zinc-500 transition-transform {{ $showWatched ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            @if($showWatched)
            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($watchedItems as $item)
                @include('livewire.partials.movie-card', ['item' => $item])
                @endforeach
            </div>
            @endif
        </div>
        @endif

        @include('livewire.partials.movie-modal')
    </div>
</main>
