@php
    $statusMeta = [
        'to_watch' => ['label' => 'À voir', 'badge' => 'border-amber-800/50 bg-amber-950/60 text-amber-400', 'chip' => 'border-amber-800/60 bg-amber-950/70 text-amber-300'],
        'watched' => ['label' => 'Vu', 'badge' => 'border-emerald-800/50 bg-emerald-950/60 text-emerald-400', 'chip' => 'border-emerald-800/60 bg-emerald-950/70 text-emerald-300'],
        'to_rewatch' => ['label' => 'À revoir', 'badge' => 'border-sky-800/50 bg-sky-950/60 text-sky-400', 'chip' => 'border-sky-800/60 bg-sky-950/70 text-sky-300'],
    ];
    $chipIdle = 'border-zinc-800 bg-zinc-950 text-zinc-400 hover:border-zinc-700 hover:text-zinc-200';
    $hours = intdiv($detail['watchedMinutes'], 60);
    $ratingMax = max($detail['ratingDistribution'] ?: [0]);
    $remaining = $filteredCount - $films->count();
@endphp
<main class="min-h-screen">
    <div class="mx-auto max-w-6xl px-4 pb-14 sm:px-8 lg:px-12">
        <div class="border-b border-zinc-800 pb-5">
            <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-amber-400">Administration</p>
            <h1 class="font-display mt-1 text-4xl tracking-wide text-zinc-100">Fiche membre</h1>
            @include('livewire.admin.partials.tabs')
        </div>

        <a href="{{ route('admin.members') }}" wire:navigate class="mt-5 inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-zinc-500 transition hover:text-amber-400">← Tous les membres</a>

        <!-- Identité -->
        <section class="mt-4 flex flex-wrap items-center gap-4 rounded-2xl border border-zinc-800/80 bg-zinc-900/60 p-5 sm:p-6">
            <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-amber-500 to-red-600 text-2xl font-black text-white ring-1 ring-amber-400/30" aria-hidden="true">{{ mb_strtoupper(mb_substr($member->name, 0, 1)) }}</span>
            <div class="min-w-0 flex-1">
                <p class="flex flex-wrap items-center gap-2 text-xl font-bold text-zinc-100">
                    <span class="truncate">{{ $member->name }}</span>
                    @if ($member->isAdmin())
                    <span class="rounded-full bg-amber-500/15 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-400">Admin</span>
                    @endif
                </p>
                <p class="mt-0.5 truncate text-sm text-zinc-500">{{ $member->email }}</p>
                <p class="mt-0.5 text-xs text-zinc-600">
                    Inscrit le {{ $member->created_at->copy()->locale('fr')->translatedFormat('j F Y') }}
                    @if ($detail['lastActivity'])
                    · Dernière activité {{ $detail['lastActivity']->copy()->locale('fr')->diffForHumans() }}
                    @endif
                </p>
            </div>
            <p class="rounded-xl border border-zinc-800 bg-zinc-950/60 px-3 py-2 text-[11px] leading-snug text-zinc-500">Lecture seule.<br>Les notes personnelles du membre ne sont pas visibles.</p>
        </section>

        <!-- Chiffres clés -->
        <section class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-2xl border border-zinc-800/80 bg-zinc-900/60 p-5">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">Films dans la liste</p>
                <p class="mt-1 text-3xl font-black tracking-tight text-zinc-100">{{ $detail['total'] }}</p>
                <p class="mt-0.5 text-xs text-zinc-500">{{ $detail['sourceCounts']['cinema'] }} cinéma · {{ $detail['sourceCounts']['streaming'] }} streaming</p>
            </div>
            <div class="rounded-2xl border border-zinc-800/80 bg-zinc-900/60 p-5">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">Déjà vus</p>
                <p class="mt-1 text-3xl font-black tracking-tight text-emerald-400">{{ $detail['watchedCount'] }}</p>
                <p class="mt-0.5 text-xs text-zinc-500">
                    @if ($detail['watchedMinutes'] > 0)≈ {{ $hours }} h de visionnage @else durée non renseignée @endif
                </p>
            </div>
            <div class="rounded-2xl border border-zinc-800/80 bg-zinc-900/60 p-5">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">À voir</p>
                <p class="mt-1 text-3xl font-black tracking-tight text-amber-400">{{ $detail['toWatchCount'] }}</p>
                <p class="mt-0.5 text-xs text-zinc-500">{{ $detail['addedLast30Days'] }} ajouté{{ $detail['addedLast30Days'] > 1 ? 's' : '' }} ces 30 jours</p>
            </div>
            <div class="rounded-2xl border border-zinc-800/80 bg-zinc-900/60 p-5">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">Note moyenne</p>
                <p class="mt-1 text-3xl font-black tracking-tight text-zinc-100">{{ $detail['averageRating'] ? $detail['averageRating'].'/5' : '—' }}</p>
                <p class="mt-0.5 text-xs text-zinc-500">{{ $detail['ratedCount'] }} film{{ $detail['ratedCount'] > 1 ? 's' : '' }} noté{{ $detail['ratedCount'] > 1 ? 's' : '' }}</p>
            </div>
        </section>

        <!-- Genres, réalisateurs, notes -->
        <section class="mt-4 grid gap-4 lg:grid-cols-3 lg:items-start">
            <div class="rounded-2xl border border-zinc-800/80 bg-zinc-900/60 p-6 lg:col-span-2">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-zinc-300">Genres</h2>
                    <p class="text-[11px] text-zinc-500">Clique sur un genre pour filtrer les films ci-dessous</p>
                </div>

                @if ($detail['genreBreakdown']->isEmpty())
                <p class="mt-4 text-sm text-zinc-500">Aucun genre renseigné.</p>
                @else
                <div class="mt-4 grid gap-x-8 gap-y-3 sm:grid-cols-2">
                    @foreach ($detail['genreBreakdown'] as $name => $counts)
                    <button type="button" wire:key="genre-{{ $loop->index }}" wire:click="setGenre(@js($name))" @class(['group block w-full rounded-lg p-1.5 text-left transition', 'bg-amber-950/30 ring-1 ring-amber-700/50' => $activeGenre === $name, 'hover:bg-zinc-800/40' => $activeGenre !== $name])>
                        <span class="mb-1 flex items-baseline justify-between gap-2 text-xs">
                            <span class="truncate font-bold text-zinc-300 group-hover:text-amber-400">{{ $name }}</span>
                            <span class="shrink-0 text-zinc-500">{{ $counts['total'] }} film{{ $counts['total'] > 1 ? 's' : '' }} · {{ $counts['watched'] }} vu{{ $counts['watched'] > 1 ? 's' : '' }}</span>
                        </span>
                        <span class="relative block h-1.5 w-full overflow-hidden rounded-full bg-zinc-800">
                            <span class="absolute inset-y-0 left-0 rounded-full bg-zinc-600" style="width: {{ $detail['topBreakdownCount'] ? round($counts['total'] / $detail['topBreakdownCount'] * 100) : 0 }}%"></span>
                            <span class="absolute inset-y-0 left-0 rounded-full bg-gradient-to-r from-amber-500 to-red-600" style="width: {{ $detail['topBreakdownCount'] ? round($counts['watched'] / $detail['topBreakdownCount'] * 100) : 0 }}%"></span>
                        </span>
                    </button>
                    @endforeach
                </div>
                <p class="mt-4 flex items-center gap-4 text-[11px] text-zinc-500">
                    <span class="flex items-center gap-1.5"><span class="h-2 w-4 rounded-full bg-zinc-600"></span>dans la liste</span>
                    <span class="flex items-center gap-1.5"><span class="h-2 w-4 rounded-full bg-gradient-to-r from-amber-500 to-red-600"></span>déjà vus</span>
                </p>
                @endif
            </div>

            <div class="space-y-4">
                <div class="rounded-2xl border border-sky-800/40 bg-sky-950/20 p-6">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-sky-500/80">À revoir</h2>
                    @if ($detail['toRewatchCount'] === 0)
                    <p class="mt-3 text-sm text-zinc-500">Aucun film marqué « à revoir ».</p>
                    @else
                    <p class="mt-1 text-3xl font-black tracking-tight text-sky-400">{{ $detail['toRewatchCount'] }}</p>
                    <p class="mt-1 text-xs text-zinc-500">1ère fois : {{ $detail['toRewatchFirstSeenCounts']['cinema'] }} cinéma · {{ $detail['toRewatchFirstSeenCounts']['streaming'] }} streaming</p>
                    @endif
                </div>

                <div class="rounded-2xl border border-zinc-800/80 bg-zinc-900/60 p-6">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-zinc-300">Réalisateurs les plus vus</h2>
                    @if ($detail['directors']->isEmpty())
                    <p class="mt-3 text-sm text-zinc-500">Pas encore de film vu avec un réalisateur renseigné.</p>
                    @else
                    <ol class="mt-3 space-y-2">
                        @foreach ($detail['directors'] as $director => $count)
                        <li class="flex items-baseline justify-between gap-3 text-sm">
                            <span class="truncate font-semibold text-zinc-300">{{ $director }}</span>
                            <span class="shrink-0 text-xs text-zinc-500">{{ $count }} film{{ $count > 1 ? 's' : '' }}</span>
                        </li>
                        @endforeach
                    </ol>
                    @endif
                </div>

                <div class="rounded-2xl border border-zinc-800/80 bg-zinc-900/60 p-6">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-zinc-300">Répartition des notes</h2>
                    @if ($detail['ratedCount'] === 0)
                    <p class="mt-3 text-sm text-zinc-500">Aucun film noté.</p>
                    @else
                    <div class="mt-3 space-y-1.5">
                        @foreach (array_reverse($detail['ratingDistribution'], true) as $stars => $count)
                        <div class="flex items-center gap-2.5 text-xs">
                            <span class="w-8 shrink-0 font-bold text-amber-400">{{ $stars }} ★</span>
                            <span class="h-1.5 flex-1 overflow-hidden rounded-full bg-zinc-800">
                                <span class="block h-full rounded-full bg-amber-500" style="width: {{ $ratingMax > 0 ? round($count / $ratingMax * 100) : 0 }}%"></span>
                            </span>
                            <span class="w-5 shrink-0 text-right text-zinc-500">{{ $count }}</span>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </section>

        <!-- Films du membre (lecture seule) -->
        <section class="mt-10">
            <div class="mb-4 flex flex-wrap items-end justify-between gap-2 border-b border-zinc-800 pb-3">
                <h2 class="flex items-center gap-3 text-xl font-bold tracking-tight text-zinc-100">
                    Films de {{ $member->name }}
                    <span class="rounded-full border border-zinc-800 bg-zinc-900 px-2.5 py-1 text-xs font-semibold text-zinc-500">{{ $filteredCount }} film{{ $filteredCount > 1 ? 's' : '' }}</span>
                </h2>
                @if ($hasFilters)
                <button type="button" wire:click="clearFilters" class="text-xs font-bold uppercase tracking-wide text-amber-400 transition hover:text-amber-300">Réinitialiser les filtres</button>
                @endif
            </div>

            <!-- Filtres -->
            <div class="flex flex-wrap items-center gap-2 rounded-2xl border border-zinc-800/80 bg-zinc-900/40 p-2.5">
                @foreach ($statusMeta as $key => $meta)
                <button type="button" wire:click="setStatus('{{ $key }}')" @class(['rounded-xl border px-3 py-1.5 text-xs font-bold transition', $meta['chip'] => $activeStatus === $key, $chipIdle => $activeStatus !== $key])>
                    {{ $meta['label'] }} <span class="opacity-60">{{ $detail['statusCounts'][$key] }}</span>
                </button>
                @endforeach

                <span class="mx-1 hidden h-6 w-px bg-zinc-800 sm:block"></span>

                @foreach (['cinema' => 'Cinéma', 'streaming' => 'Streaming'] as $key => $label)
                <button type="button" wire:click="setSource('{{ $key }}')" @class(['rounded-xl border px-3 py-1.5 text-xs font-bold transition', 'border-amber-500/50 bg-zinc-800 text-zinc-100' => $activeSource === $key, $chipIdle => $activeSource !== $key])>
                    {{ $label }} <span class="opacity-60">{{ $detail['sourceCounts'][$key] }}</span>
                </button>
                @endforeach

                <div class="ml-auto flex flex-wrap items-center gap-2">
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Titre ou réalisateur…"
                        aria-label="Rechercher dans les films du membre"
                        class="h-9 w-44 rounded-xl border border-zinc-700 bg-zinc-950 px-3 text-sm text-zinc-100 placeholder:text-zinc-600 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500"
                    >
                    <select wire:model.live="genre" aria-label="Filtrer par genre" class="h-9 rounded-xl border border-zinc-700 bg-zinc-950 px-3 text-sm font-semibold text-zinc-200 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                        <option value="">Tous les genres</option>
                        @foreach ($detail['genreBreakdown'] as $name => $counts)
                        <option value="{{ $name }}">{{ $name }} ({{ $counts['total'] }})</option>
                        @endforeach
                    </select>
                    <select wire:model.live="sort" aria-label="Trier les films" class="h-9 rounded-xl border border-zinc-700 bg-zinc-950 px-3 text-sm font-semibold text-zinc-200 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                        <option value="added_desc">Ajoutés récemment</option>
                        <option value="watched_desc">Vus récemment</option>
                        <option value="title">Titre (A → Z)</option>
                        <option value="rating_desc">Mieux notés</option>
                        <option value="year_desc">Année de sortie</option>
                    </select>
                </div>
            </div>

            @if ($films->isEmpty())
            <p class="mt-6 rounded-2xl border border-dashed border-zinc-800 bg-zinc-900/30 px-6 py-12 text-center text-sm text-zinc-500">
                {{ $hasFilters ? 'Aucun film ne correspond à ces filtres.' : 'Ce membre n\'a encore aucun film dans sa liste.' }}
            </p>
            @else
            <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
                @foreach ($films as $film)
                @php($meta = $statusMeta[$film->status->value] ?? $statusMeta['to_watch'])
                <article wire:key="film-{{ $film->id }}" class="flex flex-col overflow-hidden rounded-2xl border border-zinc-800/80 bg-zinc-900/90">
                    <div class="relative aspect-[2/3] w-full overflow-hidden bg-zinc-950">
                        @if ($film->poster_url)
                        <img src="{{ $film->poster_url }}" alt="Affiche de {{ $film->title }}" loading="lazy" class="h-full w-full object-cover">
                        @else
                        <div class="grid h-full w-full place-items-center p-3 text-center font-serif text-lg text-zinc-700">{{ $film->title }}</div>
                        @endif
                        <div class="absolute inset-x-0 top-0 flex items-center justify-between gap-2 bg-gradient-to-b from-black/80 via-black/40 to-transparent p-2">
                            <span class="rounded-lg border border-white/10 bg-black/60 px-1.5 py-0.5 text-[10px] font-bold text-zinc-300 backdrop-blur-md">{{ $film->year ?: '—' }}</span>
                            @if ($film->tmdb_rating)
                            <span class="rounded-lg bg-amber-400 px-1.5 py-0.5 text-[10px] font-black text-zinc-950">★ {{ $film->tmdb_rating }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex flex-1 flex-col gap-1.5 p-3">
                        <h3 class="line-clamp-2 text-sm font-bold leading-snug text-zinc-100" title="{{ $film->title }}">{{ $film->title }}</h3>
                        @if ($film->genre)
                        <p class="line-clamp-1 text-[11px] text-zinc-500">{{ $film->genre }}</p>
                        @endif
                        @if ($film->director)
                        <p class="line-clamp-1 text-[11px] text-zinc-600">{{ $film->director }}</p>
                        @endif

                        <div class="mt-auto flex flex-wrap items-center gap-1.5 pt-1.5">
                            <span class="rounded-md border px-1.5 py-px text-[10px] font-bold {{ $meta['badge'] }}">{{ $meta['label'] }}</span>
                            <span class="text-[10px] font-semibold {{ $film->source === \App\Enums\WatchSource::Streaming ? 'text-violet-400' : 'text-amber-400' }}">{{ $film->source === \App\Enums\WatchSource::Streaming ? 'Streaming' : 'Cinéma' }}</span>
                        </div>

                        @if ($film->personal_rating)
                        <p class="text-xs leading-none" aria-label="Note : {{ $film->personal_rating }} sur 5">
                            @for ($star = 1; $star <= 5; $star++)<span class="{{ $star <= $film->personal_rating ? 'text-amber-400' : 'text-zinc-700' }}">★</span>@endfor
                        </p>
                        @endif
                        @if ($film->watched_at)
                        <p class="text-[10px] text-zinc-600">Vu le {{ $film->watched_at->copy()->locale('fr')->translatedFormat('j M Y') }}</p>
                        @endif
                    </div>
                </article>
                @endforeach
            </div>

            @if ($remaining > 0)
            <div class="mt-6 text-center">
                <button type="button" wire:click="loadMore" class="rounded-xl border border-zinc-700 bg-zinc-900/80 px-5 py-2.5 text-sm font-bold text-zinc-100 transition hover:border-amber-500/50 hover:bg-zinc-800">
                    Afficher plus <span class="text-zinc-500">({{ $remaining }} restant{{ $remaining > 1 ? 's' : '' }})</span>
                </button>
            </div>
            @endif
            @endif
        </section>
    </div>
</main>
