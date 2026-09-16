<main class="min-h-screen">
    <div class="mx-auto max-w-7xl px-4 pb-10 sm:px-8 lg:px-12 lg:pb-14">

        <div class="border-b border-zinc-800 pb-5">
            <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-amber-400">Bilan</p>
            <h1 class="mt-1 font-serif text-3xl font-normal text-zinc-100">Mon année ciné</h1>
        </div>

        @if($totalWatched === 0 && $toRewatchCount === 0)
        <div class="mt-8 rounded-3xl border border-dashed border-zinc-800 bg-zinc-900/30 px-6 py-16 text-center">
            <div class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-zinc-800 text-xl text-amber-400 font-bold">📊</div>
            <h3 class="mt-4 text-base font-bold text-zinc-100">Aucun film marqué comme vu pour le moment.</h3>
            <p class="mt-1 text-sm text-zinc-500">Vos statistiques apparaîtront ici dès que vous aurez coché vos premiers films.</p>
        </div>
        @else
        <!-- Cartes de statistiques -->
        <section class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl border border-zinc-800/80 bg-zinc-900/60 p-6">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">Films vus</p>
                <p class="mt-1 text-4xl font-black tracking-tight text-zinc-100">{{ $totalWatched }}</p>
                <p class="mt-1 text-xs text-zinc-500">dont {{ $watchedThisYear }} en {{ now()->year }}.</p>
            </div>
            <div class="rounded-2xl border border-amber-800/40 bg-amber-950/20 p-6">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-amber-500/80">Note moyenne</p>
                <p class="mt-1 text-4xl font-black tracking-tight text-amber-400">{{ $averageRating ?? '—' }}</p>
                <p class="mt-1 text-xs text-zinc-500">sur les films notés (/5).</p>
            </div>
            <div class="rounded-2xl border border-sky-800/40 bg-sky-950/20 p-6">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-sky-500/80">Genre favori</p>
                <p class="mt-1 truncate text-2xl font-black tracking-tight text-sky-400">{{ $topGenre ?? '—' }}</p>
                <p class="mt-1 text-xs text-zinc-500">{{ $topGenreCount ? $topGenreCount . ' film' . ($topGenreCount > 1 ? 's' : '') : 'Pas assez de données.' }}</p>
            </div>
            <div class="rounded-2xl border border-violet-800/40 bg-violet-950/20 p-6">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-violet-500/80">Réalisateur favori</p>
                <p class="mt-1 truncate text-2xl font-black tracking-tight text-violet-400">{{ $topDirector ?? '—' }}</p>
                <p class="mt-1 text-xs text-zinc-500">{{ $topDirectorCount ? $topDirectorCount . ' film' . ($topDirectorCount > 1 ? 's' : '') : 'Pas assez de données.' }}</p>
            </div>
        </section>

        <section class="mt-4 grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-zinc-800/80 bg-zinc-900/60 p-6">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">Vus au cinéma</p>
                <p class="mt-1 text-3xl font-black tracking-tight text-amber-400">{{ $cinemaCount }}</p>
            </div>
            <div class="rounded-2xl border border-zinc-800/80 bg-zinc-900/60 p-6">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">Vus en streaming</p>
                <p class="mt-1 text-3xl font-black tracking-tight text-violet-400">{{ $streamingCount }}</p>
            </div>
            <div class="rounded-2xl border border-sky-800/40 bg-sky-950/20 p-6">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-sky-500/80">À revoir</p>
                <p class="mt-1 text-3xl font-black tracking-tight text-sky-400">{{ $toRewatchCount }}</p>
                <p class="mt-1 text-xs text-zinc-500">non comptés dans les films vus.</p>
            </div>
        </section>

        <!-- Frise chronologique -->
        <section class="mt-10">
            <h2 class="border-b border-zinc-800 pb-3 font-serif text-xl font-normal text-zinc-100">Historique</h2>
            <div class="mt-6 space-y-8">
                @foreach($timeline as $month => $group)
                <div>
                    <h3 class="text-[11px] font-bold uppercase tracking-widest text-amber-400">{{ $month }}</h3>
                    <div class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($group as $item)
                        <div class="flex gap-3 rounded-2xl border border-zinc-800 bg-zinc-900/80 p-3">
                            <div class="h-20 w-14 shrink-0 overflow-hidden rounded-lg bg-zinc-950">
                                @if($item->poster_url)
                                <img src="{{ $item->poster_url }}" alt="Affiche de {{ $item->title }}" class="h-full w-full object-cover">
                                @else
                                <div class="grid h-full w-full place-items-center text-[9px] text-zinc-700">N/A</div>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-zinc-100">{{ $item->title }}</p>
                                <p class="mt-0.5 text-xs text-zinc-500">{{ $item->watched_at->translatedFormat('d F Y') }}</p>
                                @if($item->personal_rating)
                                <p class="mt-1 text-xs font-semibold text-amber-400">
                                    {{ str_repeat('★', $item->personal_rating) }}<span class="text-zinc-700">{{ str_repeat('★', 5 - $item->personal_rating) }}</span>
                                </p>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </section>

        <!-- Films à revoir : jamais mélangés à l'historique des films vus ci-dessus -->
        @if($toRewatchCount > 0)
        <section class="mt-10">
            <h2 class="border-b border-zinc-800 pb-3 font-serif text-xl font-normal text-zinc-100">Films à revoir</h2>
            <div class="mt-6 space-y-8">
                @foreach($toRewatchTimeline as $month => $group)
                <div>
                    <h3 class="text-[11px] font-bold uppercase tracking-widest text-sky-400">{{ $month }}</h3>
                    <div class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($group as $item)
                        <div class="flex gap-3 rounded-2xl border border-zinc-800 bg-zinc-900/80 p-3">
                            <div class="h-20 w-14 shrink-0 overflow-hidden rounded-lg bg-zinc-950">
                                @if($item->poster_url)
                                <img src="{{ $item->poster_url }}" alt="Affiche de {{ $item->title }}" class="h-full w-full object-cover">
                                @else
                                <div class="grid h-full w-full place-items-center text-[9px] text-zinc-700">N/A</div>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-zinc-100">{{ $item->title }}</p>
                                <p class="mt-0.5 text-xs text-zinc-500">Vu le {{ $item->watched_at->translatedFormat('d F Y') }}</p>
                                @if($item->personal_rating)
                                <p class="mt-1 text-xs font-semibold text-amber-400">
                                    {{ str_repeat('★', $item->personal_rating) }}<span class="text-zinc-700">{{ str_repeat('★', 5 - $item->personal_rating) }}</span>
                                </p>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </section>
        @endif
        @endif
    </div>
</main>
