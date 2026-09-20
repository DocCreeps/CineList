<main class="min-h-screen">
    <div class="mx-auto max-w-6xl px-4 pb-14 sm:px-8 lg:px-12">
        <div class="border-b border-zinc-800 pb-5">
            <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-amber-400">Administration</p>
            <h1 class="mt-1 font-serif text-3xl font-normal text-zinc-100">Membres & catégories</h1>
            <p class="mt-2 text-sm text-zinc-500">Les comptes, leur activité et la répartition des films par genre. Ouvrez la fiche d'un membre pour voir ses statistiques et sa liste de films.</p>
            @include('livewire.admin.partials.tabs')
        </div>

        <!-- Chiffres clés -->
        <section class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-2xl border border-zinc-800/80 bg-zinc-900/60 p-5">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">Membres</p>
                <p class="mt-1 text-3xl font-black tracking-tight text-zinc-100">{{ $members->count() }}</p>
                <p class="mt-0.5 text-xs text-zinc-500">dont {{ $adminCount }} administrateur{{ $adminCount > 1 ? 's' : '' }}</p>
            </div>
            <div class="rounded-2xl border border-zinc-800/80 bg-zinc-900/60 p-5">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">Films au total</p>
                <p class="mt-1 text-3xl font-black tracking-tight text-zinc-100">{{ $totalFilms }}</p>
                <p class="mt-0.5 text-xs text-zinc-500">toutes listes confondues</p>
            </div>
            <div class="rounded-2xl border border-zinc-800/80 bg-zinc-900/60 p-5">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">Films vus</p>
                <p class="mt-1 text-3xl font-black tracking-tight text-zinc-100">{{ $watchedTotal }}</p>
                <p class="mt-0.5 text-xs text-zinc-500">{{ $totalFilms > 0 ? round($watchedTotal / $totalFilms * 100) : 0 }} % des films</p>
            </div>
            <div class="rounded-2xl border border-zinc-800/80 bg-zinc-900/60 p-5">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">Genre n° 1</p>
                <p class="mt-1 truncate text-2xl font-black tracking-tight text-zinc-100">{{ $genreCounts->keys()->first() ?? '—' }}</p>
                <p class="mt-0.5 text-xs text-zinc-500">{{ $topGenreCount ? $topGenreCount.' film'.($topGenreCount > 1 ? 's' : '') : 'aucun genre renseigné' }}</p>
            </div>
        </section>

        <!-- Favoris, tous membres confondus (réalisateur et studio : films déjà vus ; films préférés : tous statuts) -->
        <section class="mt-3 grid gap-3 md:grid-cols-3">
            <div class="rounded-2xl border border-violet-800/40 bg-violet-950/20 p-5">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-violet-500/80">Réalisateur n° 1</p>
                <p class="mt-1 truncate text-2xl font-black tracking-tight text-violet-400">{{ $topDirector ?? '—' }}</p>
                <p class="mt-0.5 text-xs text-zinc-500">{{ $topDirectorCount ? $topDirectorCount.' film'.($topDirectorCount > 1 ? 's' : '').' vu'.($topDirectorCount > 1 ? 's' : '') : 'aucun réalisateur renseigné' }}</p>
            </div>
            <div class="rounded-2xl border border-emerald-800/40 bg-emerald-950/20 p-5">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-emerald-500/80">Studio n° 1</p>
                <p class="mt-1 truncate text-2xl font-black tracking-tight text-emerald-400">{{ $topStudio ?? '—' }}</p>
                <p class="mt-0.5 text-xs text-zinc-500">{{ $topStudioCount ? $topStudioCount.' film'.($topStudioCount > 1 ? 's' : '').' vu'.($topStudioCount > 1 ? 's' : '') : 'aucun studio renseigné' }}</p>
            </div>
            {{-- Films préférés : un carrousel à trois angles au plus (note + ajouts / ajouts seuls / notes seules), sans doublon : voir Favorites::filmsAcrossMembers et Favorites::withoutDuplicates. --}}
            @php
                $slides = collect([
                    ['key' => 'general', 'label' => 'Film préféré', 'film' => $favoriteFilms['general']],
                    ['key' => 'adds', 'label' => 'Le plus ajouté', 'film' => $favoriteFilms['adds']],
                    ['key' => 'rating', 'label' => 'Le mieux noté', 'film' => $favoriteFilms['rating']],
                ])->filter(fn ($slide) => $slide['film'] !== null)->values();

                $statusLabels = ['to_watch' => 'à voir', 'watched' => 'vu', 'to_rewatch' => 'à revoir'];
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
                class="overflow-hidden rounded-2xl border border-rose-800/40 bg-rose-950/20 p-5"
            >
                @if ($slides->isEmpty())
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-rose-500/80">Film préféré</p>
                <p class="mt-1 truncate text-2xl font-black tracking-tight text-rose-400">—</p>
                <p class="mt-0.5 text-xs text-zinc-500">aucun film ajouté</p>
                @else
                {{-- Les diapositives sont empilées dans la même cellule de grille : la carte garde une hauteur stable pendant le fondu. --}}
                <div class="grid grid-cols-[minmax(0,1fr)]">
                    @foreach ($slides as $index => $slide)
                    @php
                        $film = $slide['film'];
                        $addsLabel = $film['adds'].' membre'.($film['adds'] > 1 ? 's' : '');
                        $ratingLabel = $film['average'] !== null
                            ? $film['average'].'/5 en moyenne · '.$film['ratings'].' note'.($film['ratings'] > 1 ? 's' : '')
                            : 'pas encore noté';
                        $statusLabel = collect($film['statuses'])
                            ->filter()
                            ->map(fn ($count, $status) => $count.' '.($status === 'watched' && $count > 1 ? 'vus' : $statusLabels[$status]))
                            ->implode(' · ');
                    @endphp
                    <div
                        wire:key="favorite-slide-{{ $slide['key'] }}"
                        data-slide
                        @if ($index > 0) x-cloak @endif
                        x-bind:class="active === {{ $index }} ? 'opacity-100' : 'pointer-events-none opacity-0'"
                        x-bind:aria-hidden="active !== {{ $index }} ? 'true' : 'false'"
                        class="col-start-1 row-start-1 flex min-w-0 items-center gap-4 transition-opacity duration-500"
                    >
                        @if ($film['poster_url'])
                        <img src="{{ $film['poster_url'] }}" alt="Affiche de {{ $film['title'] }}" loading="lazy" class="h-20 w-14 shrink-0 rounded-lg object-cover">
                        @endif
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-rose-500/80">{{ $slide['label'] }}</p>
                            <p class="mt-1 line-clamp-2 break-words text-2xl font-black leading-tight tracking-tight text-rose-400" title="{{ $film['title'] }}">{{ $film['title'] }}</p>
                            <p class="mt-0.5 break-words text-xs text-zinc-500">
                                @if ($slide['key'] === 'general')
                                ajouté par {{ $addsLabel }} · {{ $ratingLabel }}
                                @elseif ($slide['key'] === 'adds')
                                ajouté par {{ $addsLabel }} · {{ $statusLabel }}
                                @else
                                {{ $ratingLabel }}
                                @endif
                            </p>
                            @if ($slide['key'] === 'general')
                            <p class="mt-0.5 text-[11px] text-zinc-600" title="60 % de la note (lissée) + 40 % du nombre de membres qui l'ont ajouté">Score {{ $film['score'] }}/100 · note + ajouts</p>
                            @endif
                        </div>
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
        </section>

        <!-- Membres : une carte par compte -->
        <section class="mt-8">
            <div class="mb-4 flex items-center justify-between border-b border-zinc-800 pb-3">
                <h2 class="text-xl font-bold tracking-tight text-zinc-100">Membres</h2>
                <span class="rounded-full bg-zinc-800 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-zinc-400">{{ $members->count() }} compte{{ $members->count() > 1 ? 's' : '' }}</span>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($members as $member)
                @php($stats = $memberStats[$member->id])
                <article wire:key="member-{{ $member->id }}" class="flex flex-col rounded-2xl border border-zinc-800/80 bg-zinc-900/60 p-5 transition hover:border-zinc-700">
                    <div class="flex items-start gap-3.5">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-amber-500 to-red-600 text-lg font-black text-white ring-1 ring-amber-400/30" aria-hidden="true">{{ mb_strtoupper(mb_substr($member->name, 0, 1)) }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="flex flex-wrap items-center gap-2 text-base font-bold text-zinc-100">
                                <span class="truncate">{{ $member->name }}</span>
                                @if ($member->isAdmin())
                                <span class="rounded-full bg-amber-500/15 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide text-amber-400">Admin</span>
                                @endif
                                @if ($member->id === auth()->id())
                                <span class="rounded-full bg-zinc-800 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide text-zinc-400">Vous</span>
                                @endif
                            </p>
                            <p class="mt-0.5 truncate text-xs text-zinc-500">{{ $member->email }}</p>
                            <p class="mt-0.5 text-[11px] text-zinc-600">
                                Inscrit le {{ $member->created_at->copy()->locale('fr')->translatedFormat('j F Y') }}
                                @if ($stats['lastActivity'])
                                · Actif {{ $stats['lastActivity']->copy()->locale('fr')->diffForHumans() }}
                                @endif
                            </p>
                        </div>
                    </div>

                    <dl class="mt-4 grid grid-cols-3 divide-x divide-zinc-800 rounded-xl border border-zinc-800 bg-zinc-950/60 text-center">
                        <div class="px-2 py-2.5">
                            <dd class="text-lg font-black text-zinc-100">{{ $member->watchlist_items_count }}</dd>
                            <dt class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Films</dt>
                        </div>
                        <div class="px-2 py-2.5">
                            <dd class="text-lg font-black text-emerald-400">{{ $stats['watched'] }}</dd>
                            <dt class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Vus</dt>
                        </div>
                        <div class="px-2 py-2.5">
                            <dd class="text-lg font-black text-amber-400">{{ $stats['toWatch'] }}</dd>
                            <dt class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">À voir</dt>
                        </div>
                    </dl>

                    <div class="mt-3 flex min-h-[1.75rem] flex-wrap items-center gap-1.5">
                        @forelse ($stats['topGenres'] as $genre)
                        <span class="rounded-full border border-zinc-700/70 bg-zinc-800/60 px-2.5 py-0.5 text-[11px] font-semibold text-zinc-300">{{ $genre }}</span>
                        @empty
                        <span class="text-xs text-zinc-600">Aucun genre renseigné.</span>
                        @endforelse
                    </div>

                    <div class="mt-4 flex items-center justify-between border-t border-zinc-800/70 pt-3">
                        <a href="{{ route('admin.members.show', $member) }}" wire:navigate class="text-xs font-bold uppercase tracking-wide text-amber-400 transition hover:text-amber-300">Voir la fiche & les films →</a>

                        @if ($member->id !== auth()->id())
                        <button
                            type="button"
                            x-on:click="$store.confirmModal.open(@js('Supprimer définitivement « ' . $member->name . ' » ? Tous ses films seront également supprimés. Cette action est irréversible.'), () => $wire.deleteMember({{ $member->id }}))"
                            class="text-[11px] font-bold uppercase tracking-wide text-red-400 transition hover:text-red-300"
                        >Supprimer</button>
                        @endif
                    </div>
                </article>
                @endforeach
            </div>
        </section>

        <!-- Films par catégorie (genre), tous membres confondus -->
        <section class="mt-10 rounded-2xl border border-zinc-800/80 bg-zinc-900/60 p-6 shadow-2xl sm:p-8">
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
</main>
