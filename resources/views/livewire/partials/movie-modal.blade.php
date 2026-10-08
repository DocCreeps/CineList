@if ($showModal && $selectedMovie)
<div x-data="{ openProvider: null }" x-on:keydown.escape.window="$wire.showCast ? $wire.closeCast() : $wire.closeModal()" class="fixed inset-0 z-50 overflow-y-auto bg-black/80 px-4 py-8 backdrop-blur-md transition-opacity">
    <div class="grid min-h-full place-items-center" wire:click.self="closeModal">
        <div class="relative flex w-full max-w-5xl flex-col overflow-hidden rounded-3xl bg-zinc-900 border border-zinc-800 shadow-2xl sm:flex-row" wire:click.stop>
            <button wire:click="closeModal" class="absolute right-3 top-3 z-20 grid h-8 w-8 place-items-center rounded-full bg-black/60 text-zinc-400 backdrop-blur-md transition hover:bg-black hover:text-white" aria-label="Fermer">✕</button>

            <!-- Poster : bloc large, image remplit tout l'espace -->
            <div class="h-80 shrink-0 overflow-hidden bg-zinc-950 sm:h-auto sm:w-[26rem]">
                @if($selectedMovie['poster_url'])
                <img src="{{ $selectedMovie['poster_url'] }}" alt="Affiche de {{ $selectedMovie['title'] }}" class="h-full w-full object-cover">
                @else
                <div class="grid h-full w-full place-items-center p-6 text-center font-serif text-2xl text-zinc-700">
                    {{ $selectedMovie['title'] }}
                </div>
                @endif
            </div>

            <div class="min-w-0 flex-1 p-5 sm:p-6">
                <div class="flex items-center gap-2">
                    <span class="rounded-md bg-amber-950 border border-amber-800/60 px-2 py-0.5 text-xs font-bold text-amber-400">
                        {{ $selectedMovie['year'] ?: 'Année inconnue' }}
                    </span>
                    @if($selectedMovie['runtime'])
                    <span class="text-xs text-zinc-400 font-medium">• {{ $selectedMovie['runtime'] }}</span>
                    @endif
                </div>

                <h2 class="mt-1.5 font-serif text-xl font-normal text-zinc-100 sm:text-2xl">{{ $selectedMovie['title'] }}</h2>
                @if($selectedMovie['tagline'] ?? null)
                <p class="mt-1 text-xs italic text-zinc-500">« {{ $selectedMovie['tagline'] }} »</p>
                @endif

                <div class="mt-2 flex flex-wrap items-center gap-2">
                    @if($selectedMovie['tmdb_rating'])
                    <span class="flex items-center gap-1 text-sm font-bold text-amber-400">
                        ★ {{ $selectedMovie['tmdb_rating'] }} <span class="text-xs text-zinc-500 font-normal">/10</span>
                    </span>
                    @endif
                    @if($selectedMovie['genre'])
                    <span class="text-xs font-medium text-zinc-300 bg-zinc-800 px-2 py-0.5 rounded-md">{{ $selectedMovie['genre'] }}</span>
                    @endif
                </div>

                @if($selectedMovie['collection'] ?? null)
                <div class="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-amber-800/40 bg-amber-950/30 px-3 py-2">
                    <p class="text-[11px] text-amber-300">
                        Saga <span class="font-bold">{{ $selectedMovie['collection']['name'] }}</span>
                    </p>
                    <button
                        type="button"
                        x-on:click="$store.confirmModal.open(@js('Ajouter tous les films de cette saga qui ne sont pas déjà dans votre liste ?'), $wire, 'addCollection', [{{ $selectedMovie['collection']['id'] }}], { danger: false, confirmLabel: 'Ajouter' })"
                        class="shrink-0 rounded-lg bg-amber-500 px-2.5 py-1 text-[10px] font-bold text-zinc-950 transition hover:bg-amber-400"
                    >
                        + Toute la saga
                    </button>
                </div>
                @endif

                <div class="mt-4 grid grid-cols-1 gap-x-6 gap-y-3 border-t border-zinc-800 pt-4 md:grid-cols-2">
                    <dl class="min-w-0 space-y-1.5 self-start text-xs">
                        @if($selectedMovie['director'])
                        <div class="flex gap-1.5">
                            <dt class="shrink-0 font-bold text-zinc-400">Réalisation :</dt>
                            <dd class="min-w-0 text-zinc-200">{{ $selectedMovie['director'] }}</dd>
                        </div>
                        @endif
                        @if($selectedMovie['writers'] ?? null)
                        <div class="flex gap-1.5">
                            <dt class="shrink-0 font-bold text-zinc-400">Scénario :</dt>
                            <dd class="min-w-0 text-zinc-200">{{ $selectedMovie['writers'] }}</dd>
                        </div>
                        @endif
                        @if($selectedMovie['studio'] ?? null)
                        <div class="flex gap-1.5">
                            <dt class="shrink-0 font-bold text-zinc-400">Studio :</dt>
                            <dd class="min-w-0 break-words text-zinc-200">{{ $selectedMovie['studio'] }}</dd>
                        </div>
                        @endif
                        @if($selectedMovie['actors'])
                        <div class="flex gap-1.5">
                            <dt class="shrink-0 font-bold text-zinc-400">Casting :</dt>
                            <dd class="min-w-0 text-zinc-200">{{ $selectedMovie['actors'] }}</dd>
                        </div>
                        @endif
                        @if($selectedMovie['tmdb_id'] ?? null)
                        <div>
                            <button
                                type="button"
                                wire:click="openCast"
                                wire:loading.attr="disabled"
                                wire:target="openCast"
                                class="mt-1 inline-flex items-center gap-1.5 rounded-lg border border-zinc-700 bg-zinc-800/70 px-2.5 py-1.5 text-[11px] font-bold text-zinc-200 transition hover:border-amber-500/50 hover:text-amber-400 disabled:cursor-wait disabled:opacity-60"
                            >
                                <span wire:loading.remove wire:target="openCast">Voir le casting complet</span>
                                <span wire:loading wire:target="openCast">Chargement…</span>
                            </button>
                        </div>
                        @endif
                    </dl>

                    @if($selectedMovie['trailer_key'] ?? null)
                    <div class="min-w-0">
                        <div class="mb-1.5 flex items-center justify-between">
                            <p class="text-xs font-bold text-zinc-400">Bande-annonce</p>
                            @if($selectedMovie['trailer_lang'] ?? null)
                            <span class="rounded-full border border-amber-800/50 bg-amber-950/60 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide text-amber-400">{{ $selectedMovie['trailer_lang'] }}</span>
                            @endif
                        </div>
                        <div class="aspect-video overflow-hidden rounded-xl bg-black">
                            <iframe src="https://www.youtube.com/embed/{{ $selectedMovie['trailer_key'] }}" title="Bande-annonce de {{ $selectedMovie['title'] }}" class="h-full w-full" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                        </div>
                    </div>
                    @endif
                </div>

                @if($selectedMovie['plot'])
                <p class="mt-3 text-xs leading-relaxed text-zinc-400">{{ $selectedMovie['plot'] }}</p>
                @else
                <p class="mt-3 text-xs italic text-zinc-600">Résumé indisponible.</p>
                @endif

                @php
                    $releaseDate = filled($selectedMovie['release_date'] ?? null) ? \Illuminate\Support\Carbon::parse($selectedMovie['release_date'])->locale('fr')->translatedFormat('j F Y') : null;
                    $formatMoney = fn (int $amount): string => match (true) {
                        $amount >= 1_000_000_000 => rtrim(rtrim(number_format($amount / 1_000_000_000, 2, ',', ' '), '0'), ',') . ' Md $',
                        $amount >= 1_000_000 => rtrim(rtrim(number_format($amount / 1_000_000, 1, ',', ' '), '0'), ',') . ' M$',
                        default => number_format($amount, 0, ',', ' ') . ' $',
                    };
                    $facts = array_filter([
                        'Titre original' => $selectedMovie['original_title'] ?? null,
                        'Sortie' => $releaseDate,
                        'Langue originale' => $selectedMovie['original_language'] ?? null,
                        'Pays' => $selectedMovie['countries'] ?? null,
                        'Budget' => ($selectedMovie['budget'] ?? null) ? $formatMoney((int) $selectedMovie['budget']) : null,
                        'Recettes' => ($selectedMovie['revenue'] ?? null) ? $formatMoney((int) $selectedMovie['revenue']) : null,
                    ]);
                @endphp
                @if(! empty($facts))
                <dl class="mt-4 grid grid-cols-2 gap-x-6 gap-y-2.5 border-t border-zinc-800 pt-4 text-xs sm:grid-cols-3">
                    @foreach($facts as $label => $value)
                    <div class="min-w-0 {{ $label === 'Titre original' || $label === 'Pays' ? 'col-span-2 sm:col-span-1' : '' }}">
                        <dt class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">{{ $label }}</dt>
                        <dd class="mt-0.5 break-words text-zinc-200">{{ $value }}</dd>
                    </div>
                    @endforeach
                </dl>
                @endif

                @if(!empty($selectedMovie['watch_providers']) && (!empty($selectedMovie['watch_providers']['flatrate']) || !empty($selectedMovie['watch_providers']['rent']) || !empty($selectedMovie['watch_providers']['buy'])))
                <div class="mt-4 border-t border-zinc-800 pt-4">
                    <p class="mb-1.5 text-xs font-bold text-zinc-400">Où regarder (France)</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ([
                        ['key' => 'flatrate', 'label' => 'Abonnement', 'color' => 'text-emerald-500/80'],
                        ['key' => 'rent', 'label' => 'Location', 'color' => 'text-sky-500/80'],
                        ['key' => 'buy', 'label' => 'Achat', 'color' => 'text-zinc-500'],
                        ] as $group)
                        @if(!empty($selectedMovie['watch_providers'][$group['key']]))
                        <div class="relative" x-on:click.outside="openProvider === '{{ $group['key'] }}' && (openProvider = null)">
                            <button type="button" x-on:click="openProvider = (openProvider === '{{ $group['key'] }}' ? null : '{{ $group['key'] }}')" class="flex items-center gap-1.5 rounded-lg bg-zinc-950/60 px-2.5 py-1.5 text-[10px] font-bold uppercase tracking-widest {{ $group['color'] }} transition hover:bg-zinc-950">
                                {{ $group['label'] }}
                                <span class="text-zinc-600 transition" x-bind:class="openProvider === '{{ $group['key'] }}' && 'rotate-180'">▾</span>
                            </button>

                            <div x-show="openProvider === '{{ $group['key'] }}'" x-transition.origin.top x-cloak class="absolute left-0 top-full z-30 mt-1.5 w-56 rounded-xl border border-zinc-800 bg-zinc-900 p-2 shadow-xl">
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach($selectedMovie['watch_providers'][$group['key']] as $provider)
                                    <span class="flex items-center gap-1 rounded-lg bg-zinc-800/80 px-1.5 py-1 text-[10px] font-semibold text-zinc-200">
                                        @if($provider['logo_url'])
                                        <img src="{{ $provider['logo_url'] }}" alt="" class="h-3.5 w-3.5 rounded">
                                        @endif
                                        {{ $provider['name'] }}
                                    </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endif
                        @endforeach
                    </div>
                    <p class="mt-1.5 text-[9px] text-zinc-600">Données fournies par JustWatch.</p>
                </div>
                @endif

                @if(isset($selectedMovie['item_id']))
                <div class="mt-4 border-t border-zinc-800 pt-4">
                    <p class="mb-1.5 text-xs font-bold text-zinc-400">Statut</p>
                    <div class="flex items-center gap-2" role="group" aria-label="Statut de visionnage">
                        @php($alreadyWatched = in_array($selectedMovie['status'], ['watched', 'to_rewatch'], true))
                        <button
                            wire:click="setStatus({{ $selectedMovie['item_id'] }}, 'to_watch')"
                            @disabled($alreadyWatched)
                            title="{{ $alreadyWatched ? 'Déjà vu : ne peut plus repasser en à voir' : 'Marquer à voir' }}"
                            @class(['grid h-8 w-8 place-items-center rounded-lg text-base font-bold transition', 'bg-amber-950/80 text-amber-400 border border-amber-800/50' => $selectedMovie['status'] === 'to_watch', 'text-zinc-600 border border-transparent hover:text-zinc-300 hover:bg-zinc-800/60' => $selectedMovie['status'] !== 'to_watch', 'opacity-30 cursor-not-allowed hover:bg-transparent hover:text-zinc-600' => $alreadyWatched])
                        >○</button>
                        <button wire:click="setStatus({{ $selectedMovie['item_id'] }}, 'watched')" title="Marquer comme vu" @class(['grid h-8 w-8 place-items-center rounded-lg text-base font-bold transition', 'bg-emerald-950/80 text-emerald-400 border border-emerald-800/50' => $selectedMovie['status'] === 'watched', 'text-zinc-600 border border-transparent hover:text-zinc-300 hover:bg-zinc-800/60' => $selectedMovie['status'] !== 'watched'])>✓</button>
                        <button wire:click="setStatus({{ $selectedMovie['item_id'] }}, 'to_rewatch')" title="Marquer à revoir" @class(['grid h-8 w-8 place-items-center rounded-lg text-base font-bold transition', 'bg-sky-950/80 text-sky-400 border border-sky-800/50' => $selectedMovie['status'] === 'to_rewatch', 'text-zinc-600 border border-transparent hover:text-zinc-300 hover:bg-zinc-800/60' => $selectedMovie['status'] !== 'to_rewatch'])>↺</button>
                    </div>

                    @if(in_array($selectedMovie['status'], ['watched', 'to_rewatch'], true))
                    <p class="mt-1.5 text-[10px] text-zinc-500">Vu {{ $selectedMovie['watch_count'] ?? 1 }} fois</p>
                    @endif

                    @if ($selectedMovie['status'] === 'to_rewatch' && empty($selectedMovie['first_watched_source'] ?? null))
                    {{-- Choix optionnel, comme sur la carte film : ne bloque pas la bascule. --}}
                    <div class="mt-2 flex flex-wrap items-center gap-1.5 rounded-lg border border-dashed border-sky-800/50 bg-sky-950/10 px-2 py-1.5">
                        <span class="text-[10px] leading-tight text-zinc-400">Vu la 1ère fois :</span>
                        <button type="button" wire:click="setStatus({{ $selectedMovie['item_id'] }}, 'to_rewatch', 'cinema')" title="Vu la 1ère fois au cinéma" class="rounded-md bg-zinc-900/80 px-2 py-1 text-[10px] font-bold text-amber-400 transition hover:bg-amber-950/60">🎬 Cinéma</button>
                        <button type="button" wire:click="setStatus({{ $selectedMovie['item_id'] }}, 'to_rewatch', 'streaming')" title="Vu la 1ère fois en streaming" class="rounded-md bg-zinc-900/80 px-2 py-1 text-[10px] font-bold text-violet-400 transition hover:bg-violet-950/60">📺 Streaming</button>
                    </div>
                    @elseif (! empty($selectedMovie['first_watched_source'] ?? null))
                    <p class="mt-1.5 text-[10px] text-zinc-500">1ère fois : {{ $selectedMovie['first_watched_source'] === 'streaming' ? 'streaming' : 'cinéma' }}</p>
                    @endif
                </div>
                @endif

                @if(isset($selectedMovie['item_id']) && in_array($selectedMovie['status'] ?? null, ['watched', 'to_rewatch'], true))
                <div class="mt-4 border-t border-zinc-800 pt-4">
                    <p class="mb-1.5 text-xs font-bold text-zinc-400">Votre note</p>
                    <div class="flex items-center gap-1" role="group" aria-label="Votre note">
                        @for ($star = 1; $star <= 5; $star++)
                        <button wire:click="setPersonalRating({{ $selectedMovie['item_id'] }}, {{ $star }})" title="Noter {{ $star }}/5" class="text-xl leading-none transition {{ $star <= ($selectedMovie['personal_rating'] ?? 0) ? 'text-amber-400' : 'text-zinc-700 hover:text-zinc-500' }}">★</button>
                        @endfor
                    </div>
                </div>
                @endif

                <div class="mt-4 border-t border-zinc-800 pt-4">
                    <label for="watchlist-note" class="mb-1 block text-xs font-bold text-zinc-400">Votre note personnelle</label>
                    @if(isset($selectedMovie['item_id']))
                    <div class="flex gap-2">
                        <textarea id="watchlist-note" wire:model.blur="selectedMovie.note" rows="1" placeholder="Une remarque, un souvenir…" class="min-w-0 flex-1 resize-none rounded-xl border border-zinc-800 bg-zinc-950/80 px-3 py-2 text-xs text-zinc-100 outline-none placeholder:text-zinc-600 focus:border-amber-500/50 focus:ring-1 focus:ring-amber-500/50">{{ $selectedMovie['note'] }}</textarea>
                        <button wire:click="saveNote" class="shrink-0 rounded-lg bg-zinc-800 px-3 py-1.5 text-[10px] font-bold text-zinc-200 transition hover:bg-zinc-700">
                            Enregistrer
                        </button>
                    </div>
                    @else
                    <div class="flex gap-2">
                        <textarea rows="1" disabled placeholder="Ajoutez ce film à votre liste pour pouvoir écrire une note…" class="min-w-0 flex-1 cursor-not-allowed resize-none rounded-xl border border-zinc-800 bg-zinc-950/40 px-3 py-2 text-xs text-zinc-600 outline-none placeholder:text-zinc-600"></textarea>
                        <button type="button" disabled class="shrink-0 cursor-not-allowed rounded-lg bg-zinc-800/50 px-3 py-1.5 text-[10px] font-bold text-zinc-600">
                            Enregistrer
                        </button>
                    </div>
                    @endif
                </div>

                @if(!empty($selectedMovie['similar']))
                <div class="mt-4 min-w-0 border-t border-zinc-800 pt-4" x-data="scrollTrack">
                    <div class="mb-2 flex items-center justify-between">
                        <p class="text-xs font-bold text-zinc-400">Films similaires</p>
                        <div class="flex gap-1">
                            <button type="button" x-on:click="scrollBy(-300)" class="grid h-6 w-6 place-items-center rounded-full bg-zinc-800 text-zinc-300 transition hover:bg-zinc-700" aria-label="Précédent">‹</button>
                            <button type="button" x-on:click="scrollBy(300)" class="grid h-6 w-6 place-items-center rounded-full bg-zinc-800 text-zinc-300 transition hover:bg-zinc-700" aria-label="Suivant">›</button>
                        </div>
                    </div>
                    <div x-ref="track" class="no-scrollbar flex min-w-0 gap-3 overflow-x-auto scroll-smooth">
                        @foreach($selectedMovie['similar'] as $movie)
                        <button wire:click="showDetails('{{ $movie['tmdb_id'] }}')" class="group w-24 shrink-0 text-left">
                            <div class="aspect-[2/3] w-24 overflow-hidden rounded-lg bg-zinc-950">
                                @if($movie['poster_url'])
                                <img src="{{ $movie['poster_url'] }}" alt="" class="h-full w-full object-cover transition group-hover:scale-105">
                                @else
                                <div class="grid h-full w-full place-items-center text-[9px] text-zinc-700">N/A</div>
                                @endif
                            </div>
                            <p class="mt-1.5 line-clamp-2 text-xs leading-snug text-zinc-300 group-hover:text-amber-400">{{ $movie['title'] }}</p>
                        </button>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if ($showCast)
@php($credits = $this->fullCredits)
<div class="fixed inset-0 z-[60] overflow-y-auto bg-black/70 px-4 py-8 backdrop-blur-sm" role="dialog" aria-modal="true" aria-label="Casting complet de {{ $selectedMovie['title'] }}">
    <div class="grid min-h-full place-items-start sm:place-items-center" wire:click.self="closeCast">
        <div class="relative w-full max-w-4xl rounded-3xl border border-zinc-800 bg-zinc-900 shadow-2xl" wire:click.stop>
            <div class="sticky top-0 z-10 flex items-start justify-between gap-3 rounded-t-3xl border-b border-zinc-800 bg-zinc-900/95 px-5 py-4 backdrop-blur-md sm:px-6">
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-amber-400">Casting complet</p>
                    <h3 class="mt-0.5 truncate font-serif text-lg text-zinc-100" title="{{ $selectedMovie['title'] }}">{{ $selectedMovie['title'] }}@if($selectedMovie['year'] ?? null) <span class="text-sm text-zinc-500">({{ $selectedMovie['year'] }})</span>@endif</h3>
                </div>
                <button wire:click="closeCast" class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-black/60 text-zinc-400 transition hover:bg-black hover:text-white" aria-label="Retour à la fiche du film">✕</button>
            </div>

            <div class="px-5 py-5 sm:px-6">
                @if(! empty($credits['crew']))
                <dl class="grid gap-x-6 gap-y-2 text-xs sm:grid-cols-2">
                    @foreach($credits['crew'] as $label => $names)
                    <div class="flex gap-1.5">
                        <dt class="shrink-0 font-bold text-zinc-400">{{ $label }} :</dt>
                        <dd class="min-w-0 text-zinc-200">{{ $names }}</dd>
                    </div>
                    @endforeach
                </dl>
                @endif

                @if(empty($credits['cast']))
                <p class="py-10 text-center text-sm text-zinc-500">Casting indisponible pour ce film.</p>
                @else
                <p class="{{ empty($credits['crew']) ? '' : 'mt-5 border-t border-zinc-800 pt-4' }} text-xs font-bold text-zinc-400">
                    Distribution
                    <span class="font-normal text-zinc-600">
                        · {{ count($credits['cast']) }}{{ $credits['total'] > count($credits['cast']) ? ' sur '.$credits['total'] : '' }} rôle{{ $credits['total'] > 1 ? 's' : '' }}
                    </span>
                </p>
                <ul class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                    @foreach($credits['cast'] as $person)
                    <li class="min-w-0">
                        <div class="aspect-[2/3] overflow-hidden rounded-xl bg-zinc-950">
                            @if($person['photo_url'])
                            <img src="{{ $person['photo_url'] }}" alt="" loading="lazy" class="h-full w-full object-cover">
                            @else
                            <div class="grid h-full w-full place-items-center bg-zinc-800/60 text-2xl font-black text-zinc-600" aria-hidden="true">{{ mb_strtoupper(mb_substr($person['name'], 0, 1)) }}</div>
                            @endif
                        </div>
                        <p class="mt-1.5 line-clamp-2 text-xs font-bold leading-snug text-zinc-100">{{ $person['name'] }}</p>
                        @if($person['character'])
                        <p class="line-clamp-2 text-[11px] leading-snug text-zinc-500">{{ $person['character'] }}</p>
                        @endif
                    </li>
                    @endforeach
                </ul>
                @endif
                <p class="mt-5 text-[9px] text-zinc-600">Données fournies par TMDB.</p>
            </div>
        </div>
    </div>
</div>
@endif

<style>
    [x-cloak] {
        display: none !important;
    }

    .no-scrollbar {
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }

</style>
@endif
