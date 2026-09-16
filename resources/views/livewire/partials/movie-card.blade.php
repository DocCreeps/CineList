<article wire:key="movie-{{ $item->id }}" class="group relative flex flex-col overflow-hidden rounded-2xl bg-zinc-900/90 border border-zinc-800/80 transition duration-300 hover:-translate-y-1 hover:border-zinc-700 hover:shadow-2xl hover:shadow-amber-950/20">

    <!-- Conteneur de l'affiche -->
    <button wire:click="showDetails('{{ $item->tmdb_id }}')" class="relative aspect-[2/3] w-full cursor-pointer overflow-hidden bg-zinc-950 text-left focus:outline-none" aria-label="Voir le résumé de {{ $item->title }}">
        @if($item->poster_url)
        <img src="{{ $item->poster_url }}" alt="Affiche de {{ $item->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105 group-hover:opacity-90">
        @else
        <div class="grid h-full w-full place-items-center p-4 text-center font-serif text-xl text-zinc-700">
            {{ $item->title }}
        </div>
        @endif

        <!-- Badges en surimpression : décalés à gauche pour laisser la place à la case de sélection épinglée dans le même coin,
             but only while that checkbox is actually visible (hover/focus, or the film is already
             selected) — otherwise the date badge stays flush left. -->
        <div @class([ 'absolute inset-x-0 top-0 flex items-center justify-between gap-2 py-3 pr-3 bg-gradient-to-b from-black/80 via-black/40 to-transparent transition-[padding]' , 'pl-11'=> in_array($item->id, $selectedIds ?? []),
            'pl-3 group-hover:pl-11 group-focus-within:pl-11' => !in_array($item->id, $selectedIds ?? []),
            ])>
            <span class="rounded-lg bg-black/60 backdrop-blur-md px-2 py-1 text-[11px] font-bold text-zinc-300 border border-white/10">
                {{ $item->year ?: '—' }}
            </span>
            @if($item->imdb_rating)
            <span class="flex items-center gap-1 rounded-lg bg-amber-400 backdrop-blur-md px-2 py-1 text-[11px] font-black text-zinc-950 shadow-md">
                ★ {{ $item->imdb_rating }}
            </span>
            @endif
        </div>
    </button>

    <!-- Case de sélection, pour la barre d'actions groupées : reste discrète tant qu'elle n'est ni survolée ni cochée, pour ne pas concurrencer le badge de l'année -->
    <label @class([ 'absolute left-2 top-2 z-20 grid h-7 w-7 cursor-pointer place-items-center rounded-md border border-white/20 bg-black/70 backdrop-blur-md transition-opacity' , 'opacity-100'=> in_array($item->id, $selectedIds ?? []),
        'opacity-0 group-hover:opacity-100 focus-within:opacity-100' => !in_array($item->id, $selectedIds ?? []),
        ]) title="Sélectionner">
        <input type="checkbox" wire:click="toggleSelect({{ $item->id }})" @checked(in_array($item->id, $selectedIds ?? [])) class="h-4 w-4 accent-amber-500">
    </label>

    <!-- Informations du film -->
    <div class="flex flex-1 flex-col justify-between p-4">
        <div>
            <h3 class="line-clamp-1 font-bold text-zinc-100 text-base group-hover:text-amber-400 transition-colors" title="{{ $item->title }}">
                {{ $item->title }}
            </h3>
            <p class="mt-0.5 line-clamp-1 text-xs text-zinc-500">
                {{ $item->genre ?: 'Film' }}
            </p>
            <p class="mt-1 text-[11px] font-semibold {{ $item->source === 'streaming' ? 'text-violet-400' : 'text-amber-400' }}">
                {{ $item->source === 'streaming' ? 'Streaming' : 'Cinéma' }}
            </p>

            <!-- Priorité -->
            <div class="mt-2 flex items-center gap-1" role="group" aria-label="Priorité">
                @foreach (['1' => 'Haute', '2' => 'Moyenne', '3' => 'Basse'] as $level => $label)
                <button wire:click="setPriority({{ $item->id }}, {{ $level }})" title="Priorité {{ $label }}" @class(['rounded-md px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide transition', 'bg-amber-950/80 text-amber-400 border border-amber-800/50'=> (int) $item->priority === (int) $level, 'text-zinc-600 border border-transparent hover:text-zinc-300 hover:bg-zinc-800/60' => (int) $item->priority !== (int) $level])>
                    {{ $label }}
                </button>
                @endforeach
            </div>

            <!-- Note personnelle : n'a de sens qu'une fois le film réellement vu -->
            @if(in_array($item->status, ['watched', 'to_rewatch'], true))
            <div class="mt-2 flex items-center gap-0.5" role="group" aria-label="Votre note">
                @for ($star = 1; $star <= 5; $star++) <button wire:click="setPersonalRating({{ $item->id }}, {{ $star }})" title="Noter {{ $star }}/5" class="text-sm leading-none transition {{ $star <= ($item->personal_rating ?? 0) ? 'text-amber-400' : 'text-zinc-700 hover:text-zinc-500' }}">★</button>
                    @endfor
            </div>
            @endif
        </div>

        <div class="mt-4 flex items-center justify-between pt-3 border-t border-zinc-800/80">
            <div class="flex items-center gap-1" role="group" aria-label="Statut de visionnage">
                <button wire:click="setStatus({{ $item->id }}, 'to_watch')" title="Marquer à voir" @class(['grid h-7 w-7 place-items-center rounded-lg text-sm font-bold transition', 'bg-amber-950/80 text-amber-400 border border-amber-800/50'=> $item->status === 'to_watch', 'text-zinc-600 border border-transparent hover:text-zinc-300 hover:bg-zinc-800/60' => $item->status !== 'to_watch'])>○</button>
                <button wire:click="setStatus({{ $item->id }}, 'watched')" title="Marquer comme vu" @class(['grid h-7 w-7 place-items-center rounded-lg text-sm font-bold transition', 'bg-emerald-950/80 text-emerald-400 border border-emerald-800/50'=> $item->status === 'watched', 'text-zinc-600 border border-transparent hover:text-zinc-300 hover:bg-zinc-800/60' => $item->status !== 'watched'])>✓</button>
                <button wire:click="setStatus({{ $item->id }}, 'to_rewatch')" title="Marquer à revoir" @class(['grid h-7 w-7 place-items-center rounded-lg text-sm font-bold transition', 'bg-sky-950/80 text-sky-400 border border-sky-800/50'=> $item->status === 'to_rewatch', 'text-zinc-600 border border-transparent hover:text-zinc-300 hover:bg-zinc-800/60' => $item->status !== 'to_rewatch'])>↺</button>
            </div>
            <button wire:click="remove({{ $item->id }})" wire:confirm="Retirer ce film de votre liste ?" class="p-1 text-zinc-600 hover:text-red-400 transition" title="Retirer de la liste">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </button>
        </div>
    </div>
</article>
