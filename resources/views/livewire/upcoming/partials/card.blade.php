{{--
    Carte d'une sortie cinéma de la page « À venir ».
    Variables : $movie (entrée de TmdbClient::releasesBetween()), $inList (statut par tmdb_id des films
    déjà dans la liste), $keyPrefix (préfixe du wire:key, unique par section) et $showStatus
    (affiche « Aujourd'hui » / « Demain » / « En salles », utile dans « Cette semaine » qui mêle sorties
    passées et à venir).
--}}
@php
    $inListStatus = $inList[$movie['tmdb_id']] ?? null;

    $today = now()->startOfDay();
    $releaseDate = \Illuminate\Support\Carbon::parse($movie['release_date'])->startOfDay();
    $isOut = $releaseDate->lte($today);

    // Même classement que sur la recherche : à l'affiche ou à venir (+ Cinéma) ou sorti depuis plus
    // de ~2 mois (Déjà vue / + Streaming / Revoir).
    $window = \App\Support\Movies\ReleaseWindow::classify($movie['release_date']);

    // Ressortie en salles : le film existe déjà (sorti il y a longtemps), on peut donc aussi l'ajouter en streaming.
    $isRerelease = filled($movie['original_release_date'] ?? null)
        && \App\Support\Movies\ReleaseWindow::classify($movie['original_release_date']) === 'old';

    $statusBadge = null;
    if ($showStatus ?? false) {
        if ($releaseDate->isSameDay($today)) {
            $statusBadge = ["Aujourd'hui", 'border-amber-800/60 bg-amber-950/60 text-amber-400'];
        } elseif ($releaseDate->isSameDay($today->copy()->addDay())) {
            $statusBadge = ['Demain', 'border-zinc-700 bg-zinc-800/60 text-zinc-300'];
        } elseif ($isOut) {
            $statusBadge = ['En salles', 'border-emerald-800/50 bg-emerald-950/60 text-emerald-400'];
        }
    }

    // Français forcé sur l'instance : APP_LOCALE vaut « en » par défaut.
    $dateLabel = $releaseDate->copy()->locale('fr')->translatedFormat($releaseDate->year === $today->year ? 'l j F' : 'l j F Y');
@endphp
<article wire:key="{{ $keyPrefix }}-{{ $movie['tmdb_id'] }}" @class(['group relative flex gap-3.5 rounded-2xl border p-3 shadow-lg transition', 'border-zinc-800/60 bg-zinc-900/40 opacity-50 grayscale hover:opacity-75 hover:grayscale-0' => $inListStatus, 'border-zinc-800 bg-zinc-900/80 hover:border-amber-500/50 hover:bg-zinc-900' => ! $inListStatus])>
    <button wire:click="showDetails('{{ $movie['tmdb_id'] }}')" class="relative h-24 w-16 shrink-0 cursor-pointer overflow-hidden rounded-xl bg-zinc-950 focus:outline-none" aria-label="Détails de {{ $movie['title'] }}">
        @if($movie['poster_url'])
        <img src="{{ $movie['poster_url'] }}" alt="" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
        @else
        <div class="grid h-full w-full place-items-center text-xs text-zinc-700">N/A</div>
        @endif
    </button>
    <div class="flex min-w-0 flex-1 flex-col justify-between py-0.5">
        <div>
            <h3 class="truncate font-bold text-zinc-100 text-sm group-hover:text-amber-400 transition-colors" title="{{ $movie['title'] }}">{{ $movie['title'] }}</h3>
            <p class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-zinc-500">
                <span>{{ $isOut ? 'Sorti' : 'Sortie' }} le {{ $dateLabel }}</span>
                @if($statusBadge)
                <span class="rounded-md border px-1.5 py-px text-[10px] font-bold {{ $statusBadge[1] }}">{{ $statusBadge[0] }}</span>
                @endif
            </p>
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
            @elseif($window === 'old')
            <button
                type="button"
                x-on:click="$store.confirmModal.open(@js('Marquer « ' . $movie['title'] . ' » comme déjà vu et l\'ajouter à votre liste ?'), () => $wire.add('{{ $movie['tmdb_id'] }}', 'streaming', 'watched'))"
                class="rounded-lg bg-zinc-800/80 border border-zinc-700/60 px-2.5 py-1 text-[11px] font-bold text-zinc-300 transition hover:bg-zinc-700 hover:text-white"
            >Déjà vue</button>
            <button wire:click="add('{{ $movie['tmdb_id'] }}', 'streaming')" class="rounded-lg bg-violet-950/80 border border-violet-800/60 px-2.5 py-1 text-[11px] font-bold text-violet-300 transition hover:bg-violet-900 hover:text-white">+ Streaming</button>
            <button
                type="button"
                x-on:click="$store.confirmModal.open(@js('Ajouter « ' . $movie['title'] . ' » à votre liste « à revoir » ?'), () => $wire.add('{{ $movie['tmdb_id'] }}', 'streaming', 'to_rewatch'), { danger: false, confirmLabel: 'Ajouter' })"
                class="rounded-lg bg-sky-950/80 border border-sky-800/60 px-2.5 py-1 text-[11px] font-bold text-sky-300 transition hover:bg-sky-900 hover:text-white"
            >Revoir</button>
            @else
            <button wire:click="add('{{ $movie['tmdb_id'] }}', 'cinema')" class="rounded-lg bg-amber-950/80 border border-amber-800/60 px-2.5 py-1 text-[11px] font-bold text-amber-400 transition hover:bg-amber-900 hover:text-white">+ Cinéma</button>
            @if($isOut)
            {{-- Déjà à l'affiche : on a pu le voir en salle, à enregistrer comme tel. --}}
            <button
                type="button"
                x-on:click="$store.confirmModal.open(@js('Marquer « ' . $movie['title'] . ' » comme déjà vu au cinéma et l\'ajouter à votre liste ?'), () => $wire.add('{{ $movie['tmdb_id'] }}', 'cinema', 'watched'))"
                class="rounded-lg bg-zinc-800/80 border border-zinc-700/60 px-2.5 py-1 text-[11px] font-bold text-zinc-300 transition hover:bg-zinc-700 hover:text-white"
            >Déjà vue</button>
            @endif
            @if($isRerelease)
            <button wire:click="add('{{ $movie['tmdb_id'] }}', 'streaming')" class="rounded-lg bg-violet-950/80 border border-violet-800/60 px-2.5 py-1 text-[11px] font-bold text-violet-300 transition hover:bg-violet-900 hover:text-white">+ Streaming</button>
            @endif
            @endif
        </div>
    </div>
</article>
