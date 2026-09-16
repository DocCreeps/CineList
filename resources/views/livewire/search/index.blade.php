<main>
    <div class="mx-auto max-w-7xl px-4 pb-10 sm:px-8 lg:px-12 lg:pb-14">

        <!-- Bandeau principal / zone de recherche -->
        <section x-data="{ showHint: false }" class="relative overflow-hidden rounded-3xl border border-zinc-800/80 bg-gradient-to-b from-zinc-900/90 via-zinc-900/40 to-zinc-950 p-6 sm:p-10 lg:grid lg:grid-cols-[1fr_280px] lg:gap-12 lg:p-12 shadow-2xl">
            <!-- Halos lumineux d'ambiance -->
            <div class="absolute -right-20 -top-20 h-96 w-96 rounded-full bg-amber-500/10 blur-3xl pointer-events-none"></div>
            <div class="absolute left-1/3 -bottom-20 h-80 w-80 rounded-full bg-red-600/10 blur-3xl pointer-events-none"></div>

            <div class="relative max-w-2xl">
                <span class="inline-flex items-center gap-2 rounded-full bg-amber-950/60 px-3.5 py-1 text-xs font-semibold text-amber-400 ring-1 ring-inset ring-amber-800/50 mb-4">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                    Recherche de films
                </span>

                <h1 class="font-serif text-3xl font-normal tracking-tight text-zinc-100 sm:text-5xl leading-[1.15]">
                    Trouvez votre prochain <span class="italic font-serif text-transparent bg-clip-text bg-gradient-to-r from-amber-400 via-red-400 to-rose-300">film à voir ou à revoir.</span>
                </h1>

                <p class="mt-3 text-base leading-relaxed text-zinc-400 max-w-lg">
                    Recherchez un titre, un réalisateur, un acteur ou un studio pour alimenter votre liste.
                </p>

                <!-- Barre de recherche -->
                <div class="mt-8 rounded-2xl bg-zinc-900/90 p-2 shadow-2xl ring-1 ring-zinc-800/80 transition-all focus-within:ring-2 focus-within:ring-amber-500/50 focus-within:shadow-amber-950/20">
                    <div class="grid grid-cols-1 gap-px overflow-hidden rounded-xl bg-zinc-800/80 sm:grid-cols-2 lg:grid-cols-4">

                        <!-- Recherche : titre -->
                        <div class="relative flex items-center gap-2.5 bg-zinc-900/90 px-3.5 py-2.5">
                            <svg class="h-4 w-4 shrink-0 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="7" />
                                <path d="m21 21-4.3-4.3" />
                            </svg>
                            <div class="min-w-0 flex-1">
                                <label for="queryTitle" class="block text-[9px] font-bold uppercase tracking-widest text-zinc-500">Titre</label>
                                <input id="queryTitle" wire:model.live.debounce.500ms="queryTitle" type="search" placeholder="Film à chercher…" autocomplete="off" class="w-full border-0 bg-transparent p-0 text-sm font-medium text-zinc-100 outline-none placeholder:text-zinc-600 focus:ring-0">
                            </div>
                            <svg wire:loading wire:target="queryTitle" class="h-4 w-4 shrink-0 animate-spin text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path d="M21 12a9 9 0 1 1-6.22-8.56" />
                            </svg>
                        </div>

                        <!-- Recherche : réalisateur -->
                        <div class="relative flex items-center gap-2.5 bg-zinc-900/90 px-3.5 py-2.5">
                            <svg class="h-4 w-4 shrink-0 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m15 10 4.553-2.276A1 1 0 0 1 21 8.618v6.764a1 1 0 0 1-1.447.894L15 14M5 18h8a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2Z" />
                            </svg>
                            <div class="min-w-0 flex-1">
                                <label for="queryDirector" class="block text-[9px] font-bold uppercase tracking-widest text-zinc-500">Réalisateur</label>
                                <input id="queryDirector" wire:model.live.debounce.500ms="queryDirector" type="search" placeholder="Nom du réalisateur…" autocomplete="off" class="w-full border-0 bg-transparent p-0 text-sm font-medium text-zinc-100 outline-none placeholder:text-zinc-600 focus:ring-0">
                            </div>
                            <svg wire:loading wire:target="queryDirector" class="h-4 w-4 shrink-0 animate-spin text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path d="M21 12a9 9 0 1 1-6.22-8.56" />
                            </svg>
                        </div>

                        <!-- Recherche : acteur -->
                        <div class="relative flex items-center gap-2.5 bg-zinc-900/90 px-3.5 py-2.5">
                            <svg class="h-4 w-4 shrink-0 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                            <div class="min-w-0 flex-1">
                                <label for="queryActor" class="block text-[9px] font-bold uppercase tracking-widest text-zinc-500">Acteur</label>
                                <input id="queryActor" wire:model.live.debounce.500ms="queryActor" type="search" placeholder="Nom de l'acteur…" autocomplete="off" class="w-full border-0 bg-transparent p-0 text-sm font-medium text-zinc-100 outline-none placeholder:text-zinc-600 focus:ring-0">
                            </div>
                            <svg wire:loading wire:target="queryActor" class="h-4 w-4 shrink-0 animate-spin text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path d="M21 12a9 9 0 1 1-6.22-8.56" />
                            </svg>
                        </div>

                        <!-- Recherche : studio -->
                        <div class="relative flex items-center gap-2.5 bg-zinc-900/90 px-3.5 py-2.5">
                            <svg class="h-4 w-4 shrink-0 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="6" width="20" height="12" rx="2" />
                                <path d="m7 6 3-4h4l3 4M2 10h20" />
                            </svg>
                            <div class="min-w-0 flex-1">
                                <label for="queryStudio" class="block text-[9px] font-bold uppercase tracking-widest text-zinc-500">Studio</label>
                                <input id="queryStudio" wire:model.live.debounce.500ms="queryStudio" type="search" placeholder="Ex : Ghibli…" autocomplete="off" class="w-full border-0 bg-transparent p-0 text-sm font-medium text-zinc-100 outline-none placeholder:text-zinc-600 focus:ring-0">
                            </div>
                            <svg wire:loading wire:target="queryStudio" class="h-4 w-4 shrink-0 animate-spin text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path d="M21 12a9 9 0 1 1-6.22-8.56" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="mt-3 flex items-center justify-between text-xs text-zinc-500">
                    <p class="truncate">Recherche automatique après 2 caractères. Combinez plusieurs champs pour affiner.</p>
                    <button x-on:click="showHint = !showHint" class="shrink-0 font-semibold text-amber-400 hover:text-amber-300 transition" x-text="showHint ? 'Masquer' : 'Comment ajouter ?'"></button>
                </div>

                <p x-cloak x-show="showHint" x-transition class="mt-2 text-xs leading-relaxed text-zinc-300 rounded-xl bg-zinc-900/90 p-3.5 border border-zinc-800">
                    💡 Tapez un titre, un réalisateur, un acteur et/ou un studio (ex : Ghibli, Pixar) — les champs remplis se combinent pour affiner les résultats. Choisissez ensuite <strong class="text-amber-400">+ Cinéma</strong> ou <strong class="text-violet-400">+ Streaming</strong> pour enregistrer le film dans votre liste d'attente.
                </p>
            </div>

            <!-- Filtre année minimale -->
            <aside class="relative mt-8 self-end rounded-2xl border border-zinc-800/80 bg-zinc-900/60 backdrop-blur-md p-6 shadow-xl">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-950/80 text-amber-400 border border-amber-800/50 font-bold">📅</div>
                <label for="minYear" class="mt-4 block text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">Année minimale</label>
                <input id="minYear" wire:model.live.debounce.500ms="minYear" type="number" placeholder="Ex : 2000" class="mt-2 w-full rounded-xl border border-zinc-800 bg-zinc-950/80 px-3 py-2 text-sm font-medium text-zinc-100 outline-none placeholder:text-zinc-600 focus:border-amber-500/50 focus:ring-1 focus:ring-amber-500/50">
                <p class="mt-2 text-xs text-zinc-500 leading-normal">Ne garder que les films sortis à partir de cette année.</p>
            </aside>
        </section>

        @include('livewire.partials.notice')

        <!-- Zone des résultats de recherche -->
        @if ($hasSearched)
        <section class="mt-10" aria-live="polite">
            <div class="mb-5 flex items-end justify-between border-b border-zinc-800 pb-3">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-amber-400">
                        {{ count($this->activeQueries()) > 1 ? 'Résultats combinés' : 'Résultats ' . match ($searchMode) { 'title' => 'par titre', 'director' => 'par réalisateur', 'actor' => 'par acteur', 'studio' => 'par studio', default => '' } }}
                    </p>
                    <h2 class="mt-1 text-xl font-bold tracking-tight text-zinc-100">
                        Films trouvés pour
                        @foreach ($this->activeQueries() as $field => [$label, $value])
                        <span class="text-zinc-100">{{ $label }} : « {{ $value }} »</span>{{ !$loop->last ? ' · ' : '' }}
                        @endforeach
                    </h2>
                </div>
                <button wire:click="clearSearch" class="rounded-xl px-3 py-1.5 text-xs font-semibold text-zinc-400 transition hover:bg-zinc-900 hover:text-zinc-100">
                    Fermer la recherche
                </button>
            </div>

            @if (!$searchError && $searchMode === 'actor' && ($roleCounts['acting'] ?? 0) + ($roleCounts['voice'] ?? 0) > 0)
            <div class="mb-5 flex flex-wrap items-center gap-1.5" role="group" aria-label="Filtrer par type de rôle">
                <button wire:click="setRoleFilter('all')" @class(['rounded-xl px-3.5 py-1.5 text-xs font-bold transition', 'bg-zinc-100 text-zinc-950 shadow-sm'=> $roleFilter === 'all', 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-800/60' => $roleFilter !== 'all'])>
                    Tous <span class="ml-1 opacity-60">({{ $roleCounts['acting'] + $roleCounts['voice'] }})</span>
                </button>
                <button wire:click="setRoleFilter('acting')" @class(['rounded-xl px-3.5 py-1.5 text-xs font-bold transition', 'bg-amber-500 text-zinc-950 shadow-lg shadow-amber-950/40'=> $roleFilter === 'acting', 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-800/60' => $roleFilter !== 'acting'])>
                    Acteur <span class="ml-1 opacity-80">({{ $roleCounts['acting'] }})</span>
                </button>
                <button wire:click="setRoleFilter('voice')" @class(['rounded-xl px-3.5 py-1.5 text-xs font-bold transition', 'bg-sky-600 text-white shadow-lg shadow-sky-950/40'=> $roleFilter === 'voice', 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-800/60' => $roleFilter !== 'voice'])>
                    Doublage <span class="ml-1 opacity-80">({{ $roleCounts['voice'] }})</span>
                </button>
            </div>
            @endif

            @if ($searchError)
            <div class="rounded-2xl border border-amber-900/60 bg-amber-950/30 p-5 text-amber-200">
                <p class="font-semibold">Format de recherche incomplet</p>
                <p class="mt-1 text-sm text-amber-300/80">{{ $searchError }}</p>
            </div>
            @elseif ($resultsTotal)
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($pageResults as $result)
                @php $inListStatus = $inList[$result['tmdb_id']] ?? null; @endphp
                <article wire:key="result-{{ $result['tmdb_id'] }}" @class(['group relative flex gap-3.5 rounded-2xl border p-3 shadow-lg transition', 'border-zinc-800/60 bg-zinc-900/40 opacity-50 grayscale hover:opacity-75 hover:grayscale-0' => $inListStatus, 'border-zinc-800 bg-zinc-900/80 hover:border-amber-500/50 hover:bg-zinc-900' => !$inListStatus])>
                    <button wire:click="showDetails('{{ $result['tmdb_id'] }}')" class="relative h-24 w-16 shrink-0 cursor-pointer overflow-hidden rounded-xl bg-zinc-950 focus:outline-none" aria-label="Détails de {{ $result['title'] }}">
                        @if($result['poster_url'])
                        <img src="{{ $result['poster_url'] }}" alt="" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                        @else
                        <div class="grid h-full w-full place-items-center text-xs text-zinc-700">N/A</div>
                        @endif
                    </button>
                    <div class="flex min-w-0 flex-1 flex-col justify-between py-0.5">
                        <div>
                            <h3 class="truncate font-bold text-zinc-100 text-sm group-hover:text-amber-400 transition-colors">{{ $result['title'] }}</h3>
                            <p class="mt-0.5 flex items-center gap-1.5 text-xs text-zinc-500">
                                <span>{{ $result['year'] ?: '—' }}</span>
                                @if(!empty($result['imdb_rating']))
                                <span class="text-zinc-700">·</span>
                                <span class="font-semibold text-amber-400">★ {{ $result['imdb_rating'] }}</span>
                                @endif
                            </p>
                            @if($searchMode === 'studio' && !empty($result['studio']))
                            <p class="mt-1 truncate text-xs text-zinc-400">Par <span class="font-medium text-zinc-300">{{ $result['studio'] }}</span></p>
                            @elseif(!empty($result['director']))
                            <p class="mt-1 truncate text-xs text-zinc-400">De <span class="font-medium text-zinc-300">{{ $result['director'] }}</span></p>
                            @endif
                            @if(!empty($result['actors']))
                            <p class="mt-0.5 truncate text-xs text-zinc-400">Avec <span class="font-medium text-zinc-300">{{ $result['actors'] }}</span></p>
                            @endif
                        </div>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @if($inListStatus)
                            @php
                                $statusLabel = match($inListStatus) {
                                    'watched' => '✓ Déjà vue',
                                    'to_rewatch' => '↺ À revoir',
                                    default => '○ Déjà dans la liste',
                                };
                                $statusClass = match($inListStatus) {
                                    'watched' => 'bg-emerald-950/60 border-emerald-800/50 text-emerald-400',
                                    'to_rewatch' => 'bg-sky-950/60 border-sky-800/50 text-sky-400',
                                    default => 'bg-amber-950/60 border-amber-800/50 text-amber-400',
                                };
                            @endphp
                            <span class="rounded-lg border px-2.5 py-1 text-[11px] font-bold {{ $statusClass }}">{{ $statusLabel }}</span>
                            @else
                            @php $window = $this->releaseWindow($result['release_date'] ?? null); @endphp
                            @if($window === 'upcoming' || $window === 'in_cinema')
                            <button wire:click="add('{{ $result['tmdb_id'] }}', 'cinema')" class="rounded-lg bg-amber-950/80 border border-amber-800/60 px-2.5 py-1 text-[11px] font-bold text-amber-400 transition hover:bg-amber-900 hover:text-white">+ Cinéma</button>
                            @else
                            <button wire:click="add('{{ $result['tmdb_id'] }}', 'streaming', 'watched')" wire:confirm="Marquer « {{ $result['title'] }} » comme déjà vu et l'ajouter à votre liste ?" class="rounded-lg bg-zinc-800/80 border border-zinc-700/60 px-2.5 py-1 text-[11px] font-bold text-zinc-300 transition hover:bg-zinc-700 hover:text-white">Déjà vue</button>
                            <button wire:click="add('{{ $result['tmdb_id'] }}', 'streaming')" class="rounded-lg bg-violet-950/80 border border-violet-800/60 px-2.5 py-1 text-[11px] font-bold text-violet-300 transition hover:bg-violet-900 hover:text-white">+ Streaming</button>
                            <button wire:click="add('{{ $result['tmdb_id'] }}', 'streaming', 'to_rewatch')" wire:confirm="Ajouter « {{ $result['title'] }} » à votre liste « à revoir » ?" class="rounded-lg bg-sky-950/80 border border-sky-800/60 px-2.5 py-1 text-[11px] font-bold text-sky-300 transition hover:bg-sky-900 hover:text-white">Revoir</button>
                            @endif
                            @endif
                        </div>
                    </div>
                </article>
                @endforeach
            </div>

            @if ($totalPages > 1)
            <div class="mt-6 flex items-center justify-center gap-3">
                <button wire:click="goToPage({{ $page - 1 }})" @disabled($page <=1) class="rounded-xl px-3.5 py-1.5 text-xs font-bold text-zinc-400 transition hover:bg-zinc-900 hover:text-zinc-100 disabled:opacity-30 disabled:pointer-events-none">
                    ← Précédent
                </button>
                <span class="text-xs font-semibold text-zinc-500">Page {{ $page }} / {{ $totalPages }} · {{ $resultsTotal }} film{{ $resultsTotal > 1 ? 's' : '' }}</span>
                <button wire:click="goToPage({{ $page + 1 }})" @disabled($page>= $totalPages) class="rounded-xl px-3.5 py-1.5 text-xs font-bold text-zinc-400 transition hover:bg-zinc-900 hover:text-zinc-100 disabled:opacity-30 disabled:pointer-events-none">
                    Suivant →
                </button>
            </div>
            @endif
            @else
            <div class="rounded-2xl border border-dashed border-zinc-800 bg-zinc-900/50 p-8 text-center text-sm text-zinc-500">
                Aucun film correspondant à votre recherche.
            </div>
            @endif
        </section>
        @else
        <div class="mt-10 rounded-2xl border border-dashed border-zinc-800 bg-zinc-900/30 px-6 py-14 text-center text-sm text-zinc-500">
            Commencez à taper un titre, un réalisateur, un acteur ou un studio ci-dessus pour voir apparaître les résultats.
        </div>
        @endif

        @include('livewire.partials.movie-modal')
    </div>
</main>
