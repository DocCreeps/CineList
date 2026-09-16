<main class="lg:h-[calc(100vh-8rem)] lg:overflow-hidden">
    <div class="mx-auto flex h-full max-w-7xl flex-col gap-5 px-4 pb-6 sm:px-8 lg:px-12 lg:pb-8">

        @include('livewire.partials.notice')

        <!-- Élément signature : le bandeau principal sous forme de véritable ticket d'entrée — une souche sombre pour le
             pitch + CTAs, torn from a cream admission slip carrying the personal counts.
             The seam between them is a real row of punched perforation dots (not just a
             dashed line), which is what actually reads as "ticket" rather than "two boxes". -->
        <div class="flex shrink-0 flex-col lg:flex-row lg:items-stretch">
            <div class="relative flex-1 overflow-hidden rounded-t-2xl border border-b-0 border-zinc-800/80 bg-zinc-900/90 p-6 shadow-2xl sm:p-8 lg:rounded-t-none lg:rounded-l-2xl lg:border-b lg:border-r-0 lg:p-9">
                <p class="font-mono text-[11px] tracking-[0.3em] text-amber-500/70">CINÉLIST · SÉANCE PERSONNELLE</p>

                <h1 class="font-display mt-3 text-[2.75rem] leading-[0.92] tracking-wide text-zinc-100 sm:text-6xl lg:text-[3.4rem]">
                    NE CHERCHEZ PLUS<br>QUOI REGARDER
                </h1>

                <p class="mt-4 max-w-md text-sm leading-relaxed text-zinc-400">
                    Constituez votre liste de films à voir ou à revoir, au cinéma comme en streaming,
                    et ne ratez aucune sortie salle des prochains mois.
                </p>

                <div class="mt-6 flex flex-wrap gap-2.5">
                    <a href="{{ route('search.index') }}" wire:navigate class="rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-zinc-950 shadow-lg shadow-amber-950/40 transition hover:bg-amber-400">
                        🔍 Chercher un film
                    </a>
                    <a href="{{ route('watchlist.dashboard') }}" wire:navigate class="rounded-xl border border-zinc-700 bg-zinc-900/80 px-4 py-2.5 text-sm font-bold text-zinc-100 transition hover:border-zinc-600 hover:bg-zinc-800">
                        🍿 Tableau de bord
                    </a>
                    @if($counts['to_watch'] > 0)
                    <button wire:click="surpriseMe" class="rounded-xl border border-zinc-700 bg-zinc-900/80 px-4 py-2.5 text-sm font-bold text-zinc-100 transition hover:border-amber-500/50 hover:bg-zinc-800">
                        🎲 Surprends-moi
                    </button>
                    @endif
                </div>
            </div>

            <!-- Ligne de perforation : une rangée de trous circulaires découpés à travers le
                 join, painted in the page's own background color so it reads as torn paper.
                 Horizontal row on mobile (stacked), vertical column on desktop (side by side). -->
            <div class="relative z-10 -my-3 h-6 w-full pointer-events-none lg:hidden" style="background-image: radial-gradient(circle at center, #09090b 7px, transparent 7.5px); background-size: 22px 100%; background-repeat: repeat-x; background-position: center;"></div>
            <div class="relative z-10 -mx-3 hidden w-6 shrink-0 pointer-events-none lg:block" style="background-image: radial-gradient(circle at center, #09090b 7px, transparent 7.5px); background-size: 100% 22px; background-repeat: repeat-y; background-position: center;"></div>

            <!-- Souche du ticket : les compteurs, imprimés sur un ticket physique plutôt que sur des cartes de tableau de bord -->
            <div class="relative w-full shrink-0 rounded-b-2xl border border-t-0 border-amber-900/30 bg-[#F3E7C9] p-5 text-[#3B2A1A] lg:w-[240px] lg:rounded-b-none lg:rounded-r-2xl lg:border-t lg:border-l-0 lg:p-6">
                <div class="flex items-center justify-between">
                    <span class="font-display text-sm tracking-[0.25em]">ADMISSION</span>
                    <span class="font-mono text-[10px] tracking-wide text-[#3B2A1A]/55">Nº {{ str_pad((string) $counts['all'], 4, '0', STR_PAD_LEFT) }}</span>
                </div>

                <dl class="mt-3 divide-y divide-dashed divide-[#3B2A1A]/25">
                    <div class="flex items-baseline justify-between py-2">
                        <dt class="text-[11px] font-bold uppercase tracking-wide text-[#3B2A1A]/70">Dans la liste</dt>
                        <dd class="font-display text-2xl">{{ $counts['all'] }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between py-2">
                        <dt class="text-[11px] font-bold uppercase tracking-wide text-[#3B2A1A]/70">À voir</dt>
                        <dd class="font-display text-2xl">{{ $counts['to_watch'] }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between py-2">
                        <dt class="text-[11px] font-bold uppercase tracking-wide text-[#3B2A1A]/70">Déjà vus</dt>
                        <dd class="font-display text-2xl">{{ $counts['watched'] }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between py-2">
                        <dt class="text-[11px] font-bold uppercase tracking-wide text-[#3B2A1A]/70">À revoir</dt>
                        <dd class="font-display text-2xl">{{ $counts['to_rewatch'] }}</dd>
                    </div>
                </dl>

                <div class="mt-3 h-5 w-full opacity-70" style="background-image: repeating-linear-gradient(90deg, #3B2A1A 0 1px, transparent 1px 3px, #3B2A1A 3px 4px, transparent 4px 8px, #3B2A1A 8px 10px, transparent 10px 13px, #3B2A1A 13px 14px, transparent 14px 19px, #3B2A1A 19px 21px, transparent 21px 24px);"></div>
            </div>
        </div>

        <!-- Raccourcis : pastilles discrètes en forme d'onglets de ticket, pas le moment de concurrencer le bandeau principal -->
        <section class="grid shrink-0 grid-cols-2 gap-2 lg:grid-cols-4">
            <a href="{{ route('watchlist.dashboard') }}" wire:navigate class="group flex items-center gap-2.5 rounded-full border border-zinc-800/80 bg-zinc-900/60 py-1.5 pl-1.5 pr-4 transition hover:border-amber-500/50 hover:bg-zinc-900">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-amber-950/80 text-sm border border-amber-800/50">🍿</span>
                <span class="truncate text-xs font-bold text-zinc-100 group-hover:text-amber-400 transition-colors">Tableau de bord</span>
            </a>
            <a href="{{ route('search.index') }}" wire:navigate class="group flex items-center gap-2.5 rounded-full border border-zinc-800/80 bg-zinc-900/60 py-1.5 pl-1.5 pr-4 transition hover:border-amber-500/50 hover:bg-zinc-900">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-violet-950/80 text-sm border border-violet-800/50">🔍</span>
                <span class="truncate text-xs font-bold text-zinc-100 group-hover:text-amber-400 transition-colors">Recherche</span>
            </a>
            <a href="{{ route('upcoming.index') }}" wire:navigate class="group flex items-center gap-2.5 rounded-full border border-zinc-800/80 bg-zinc-900/60 py-1.5 pl-1.5 pr-4 transition hover:border-amber-500/50 hover:bg-zinc-900">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-sky-950/80 text-sm border border-sky-800/50">🎬</span>
                <span class="truncate text-xs font-bold text-zinc-100 group-hover:text-amber-400 transition-colors">Sorties à venir</span>
            </a>
            <a href="{{ route('stats.index') }}" wire:navigate class="group flex items-center gap-2.5 rounded-full border border-zinc-800/80 bg-zinc-900/60 py-1.5 pl-1.5 pr-4 transition hover:border-amber-500/50 hover:bg-zinc-900">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-emerald-950/80 text-sm border border-emerald-800/50">📊</span>
                <span class="truncate text-xs font-bold text-zinc-100 group-hover:text-amber-400 transition-colors">Mon année ciné</span>
            </a>
        </section>

        <!-- Ajouts récents / à venir : chaque panneau défile en interne pour que la page elle-même ne
             grows past the viewport on desktop, whatever the size of the lists. -->
        <div class="grid min-h-0 flex-1 gap-4 lg:grid-cols-2">
            <section class="flex min-h-0 flex-col rounded-2xl border border-zinc-800/80 bg-zinc-900/40 p-4">
                <div class="shrink-0">
                    <div class="flex items-end justify-between">
                        <h2 class="font-display text-xl tracking-wide text-zinc-100">VOTRE LISTE</h2>
                        <a href="{{ route('watchlist.dashboard') }}" wire:navigate class="text-xs font-semibold text-amber-400 hover:text-amber-300 transition">Tout voir →</a>
                    </div>
                    <p class="text-[11px] text-zinc-500">Votre liste à regarder</p>
                    <div class="mt-2 h-[3px] w-full opacity-50" style="background-image: radial-gradient(circle, #f59e0b 1px, transparent 1.6px); background-size: 10px 3px; background-position: left center;"></div>
                </div>
                @if($toWatch->isNotEmpty())
                <div class="mt-3.5 grid flex-1 auto-rows-min grid-cols-3 gap-3 overflow-y-auto pr-1 sm:grid-cols-4">
                    @foreach($toWatch as $item)
                    <button wire:key="towatch-{{ $item->id }}" wire:click="showDetails('{{ $item->tmdb_id }}')" class="group text-left">
                        <div class="aspect-[2/3] w-full overflow-hidden rounded-xl bg-zinc-950 border border-zinc-800/80 transition group-hover:border-amber-500/50">
                            @if($item->poster_url)
                            <img src="{{ $item->poster_url }}" alt="Affiche de {{ $item->title }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                            @else
                            <div class="grid h-full w-full place-items-center p-2 text-center text-[11px] text-zinc-700">{{ $item->title }}</div>
                            @endif
                        </div>
                        <p class="mt-1.5 line-clamp-1 text-xs font-semibold text-zinc-300 group-hover:text-amber-400 transition-colors">{{ $item->title }}</p>
                    </button>
                    @endforeach
                </div>
                @else
                <div class="mt-3.5 grid flex-1 place-items-center text-center text-xs text-zinc-600">
                    Aucun film à voir pour le moment.
                </div>
                @endif
            </section>

            <section class="flex min-h-0 flex-col rounded-2xl border border-zinc-800/80 bg-zinc-900/40 p-4">
                <div class="shrink-0">
                    <div class="flex items-end justify-between">
                        <h2 class="font-display text-xl tracking-wide text-zinc-100">PROCHAINEMENT</h2>
                        <a href="{{ route('upcoming.index') }}" wire:navigate class="text-xs font-semibold text-amber-400 hover:text-amber-300 transition">Toutes les sorties →</a>
                    </div>
                    <p class="text-[11px] text-zinc-500">Bientôt au cinéma, dans votre liste</p>
                    <div class="mt-2 h-[3px] w-full opacity-50" style="background-image: radial-gradient(circle, #f59e0b 1px, transparent 1.6px); background-size: 10px 3px; background-position: left center;"></div>
                </div>
                @if($upcomingInWatchlist->isNotEmpty())
                <div class="mt-3.5 flex flex-1 flex-col gap-2.5 overflow-y-auto pr-1">
                    @foreach($upcomingInWatchlist as $movie)
                    <button wire:key="upcoming-{{ $movie['tmdb_id'] }}" wire:click="showDetails('{{ $movie['tmdb_id'] }}')" class="group flex shrink-0 items-center gap-3 rounded-xl border border-zinc-800 bg-zinc-900/80 p-2.5 text-left transition hover:border-amber-500/50 hover:bg-zinc-900">
                        <div class="h-14 w-10 shrink-0 overflow-hidden rounded-lg bg-zinc-950">
                            @if($movie['poster_url'])
                            <img src="{{ $movie['poster_url'] }}" alt="" class="h-full w-full object-cover">
                            @else
                            <div class="grid h-full w-full place-items-center text-[8px] text-zinc-700">N/A</div>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <p class="line-clamp-1 text-sm font-bold text-zinc-100 group-hover:text-amber-400 transition-colors">{{ $movie['title'] }}</p>
                            <p class="mt-0.5 font-mono text-xs font-semibold text-amber-400">{{ \Illuminate\Support\Carbon::parse($movie['release_date'])->translatedFormat('d F Y') }}</p>
                        </div>
                    </button>
                    @endforeach
                </div>
                @else
                <div class="mt-3.5 grid flex-1 place-items-center text-center text-xs text-zinc-600">
                    Aucune sortie à venir pour vos films « à voir » au cinéma.
                </div>
                @endif
            </section>
        </div>

        @include('livewire.partials.movie-modal')
    </div>
</main>
