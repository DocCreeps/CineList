@props([
    // Tableau de chiffres clés : voir ComputeWatchlistStats::summarize().
    'stats',
    // Sous-titre de la carte « Films vus » (ex. « depuis le 1er janvier », « au total »).
    'watchedHint' => '',
])

{{--
    Films vus, note, vus au cinéma, vus en streaming, à revoir. Le réalisateur favori et le genre
    favori n'ont plus leur propre carte ici : ils apparaissent déjà en tête des podiums « Top 3
    réalisateurs » et « Top 3 genres » (voir personal-bilan.blade.php), pas la peine de les répéter.
    4 colonnes (2 sur mobile) : 4 chiffres, puis « à revoir » en pleine largeur.
--}}
<div {{ $attributes->merge(['class' => 'grid grid-cols-2 gap-3 sm:grid-cols-4']) }}>
    <div class="rounded-2xl border border-zinc-800/80 bg-zinc-900/60 p-4">
        <p class="text-[10px] font-bold uppercase leading-tight tracking-[0.15em] text-zinc-500">Films vus</p>
        <p class="mt-1 text-3xl font-black tracking-tight text-zinc-100">{{ $stats['totalWatched'] }}</p>
        @if ($watchedHint !== '')
        <p class="mt-1 text-[11px] leading-tight text-zinc-500">{{ $watchedHint }}</p>
        @endif
    </div>
    <div class="rounded-2xl border border-amber-800/40 bg-amber-950/20 p-4">
        <p class="text-[10px] font-bold uppercase leading-tight tracking-[0.15em] text-amber-500/80">Note moyenne</p>
        <p class="mt-1 text-3xl font-black tracking-tight text-amber-400">{{ $stats['averageRating'] ?? '—' }}</p>
        <p class="mt-1 text-[11px] leading-tight text-zinc-500">sur les films notés (/5).</p>
    </div>
    <div class="rounded-2xl border border-zinc-800/80 bg-zinc-900/60 p-4">
        <p class="text-[10px] font-bold uppercase leading-tight tracking-[0.15em] text-zinc-500">Vus au cinéma</p>
        <p class="mt-1 text-3xl font-black tracking-tight text-amber-400">{{ $stats['cinemaCount'] }}</p>
    </div>
    <div class="rounded-2xl border border-zinc-800/80 bg-zinc-900/60 p-4">
        <p class="text-[10px] font-bold uppercase leading-tight tracking-[0.15em] text-zinc-500">Vus en streaming</p>
        <p class="mt-1 text-3xl font-black tracking-tight text-violet-400">{{ $stats['streamingCount'] }}</p>
    </div>
    <div class="col-span-2 rounded-2xl border border-sky-800/40 bg-sky-950/20 p-4 sm:col-span-4">
        <p class="text-[10px] font-bold uppercase leading-tight tracking-[0.15em] text-sky-500/80">À revoir</p>
        <p class="mt-1 text-3xl font-black tracking-tight text-sky-400">{{ $stats['toRewatchCount'] }}</p>
        <p class="mt-1 text-[11px] leading-tight text-zinc-500">
            non comptés dans les films. vus la 1ère fois:
            @if ($stats['toRewatchCount'] > 0)
             cinéma: {{ $stats['toRewatchCinemaCount'] }} · streaming: {{ $stats['toRewatchStreamingCount'] }}


            @endif
        </p>
    </div>
</div>
