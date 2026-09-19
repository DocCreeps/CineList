{{--
    Une section de la page « À venir » : en-tête (titre, compteur, période) et grille de cartes.
    Variables : $title, $section (résultat de Index::loadWeek()), $inList, $keyPrefix, $empty, et
    facultatifs $range, $showStatus (badges « Aujourd'hui » / « En salles ») et $loadingTarget
    (actions Livewire pendant lesquelles la section est grisée).
--}}
<div @if(filled($loadingTarget ?? null)) wire:loading.class="opacity-40" wire:target="{{ $loadingTarget }}" @endif>
    <div class="mb-5 flex flex-wrap items-end justify-between gap-x-4 gap-y-1 border-b border-zinc-800 pb-3">
        <h2 class="flex items-center gap-3 text-xl font-bold tracking-tight text-zinc-100">
            {{ $title }}
            @if(! $section['error'] || $section['total'] > 0)
            <span class="text-xs font-semibold text-zinc-500 bg-zinc-900 border border-zinc-800 px-2.5 py-1 rounded-full">
                {{ $section['total'] }} film{{ $section['total'] > 1 ? 's' : '' }}
            </span>
            @endif
        </h2>
        @if(filled($range ?? null))
        <p class="text-xs font-semibold text-zinc-500">{{ $range }}</p>
        @endif
    </div>

    @if($section['error'] && $section['total'] === 0)
    <div class="rounded-2xl border border-amber-900/60 bg-amber-950/30 p-5 text-amber-200">
        <p class="font-semibold">Sorties indisponibles</p>
        <p class="mt-1 text-sm text-amber-300/80">{{ $section['error'] }}</p>
    </div>
    @elseif($section['total'] === 0)
    <p class="rounded-2xl border border-dashed border-zinc-800 bg-zinc-900/30 px-6 py-8 text-center text-sm text-zinc-500">{{ $empty }}</p>
    @else
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($section['movies'] as $movie)
        @include('livewire.upcoming.partials.card', ['movie' => $movie, 'inList' => $inList, 'keyPrefix' => $keyPrefix, 'showStatus' => $showStatus ?? false])
        @endforeach
    </div>

    @if($section['error'])
    <p class="mt-4 text-xs text-amber-300/80">Certaines sorties n'ont pas pu être chargées ({{ $section['error'] }}).</p>
    @endif
    @endif
</div>
