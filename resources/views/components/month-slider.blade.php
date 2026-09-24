@props([
    // Collection « Mois Année » => films du mois, du plus récent au plus ancien (voir ComputeWatchlistStats).
    'timeline',
    'title',
    // 'amber' (films vus) ou 'sky' (films à revoir)
    'accent' => 'amber',
    // Préfixe de la date de visionnage sur chaque carte (ex. « Vu le »).
    'datePrefix' => '',
])

{{--
    Historique replié par défaut : un clic sur l'en-tête le déploie, puis on navigue mois par mois
    (flèches, pastilles défilables, glissement tactile ou touches ← →) au lieu de dérouler toute la
    frise. Les mois sont affichés du plus ancien (gauche) au plus récent (droite) ; le mois le plus
    récent est sélectionné à l'ouverture.
--}}
@php
    $months = $timeline->reverse();
    $count = $months->count();
    $total = $months->sum(fn ($group) => $group->count());

    $theme = [
        'amber' => ['title' => 'text-amber-400', 'chipOn' => 'bg-amber-500 text-zinc-950', 'arrow' => 'hover:bg-amber-900/40 hover:text-amber-300'],
        'sky' => ['title' => 'text-sky-400', 'chipOn' => 'bg-sky-500 text-zinc-950', 'arrow' => 'hover:bg-sky-900/40 hover:text-sky-300'],
    ][$accent] ?? null;
    $theme ??= [
        'title' => 'text-amber-400', 'chipOn' => 'bg-amber-500 text-zinc-950', 'arrow' => 'hover:bg-amber-900/40 hover:text-amber-300',
    ];
@endphp

@if ($count > 0)
<section
    {{ $attributes->merge(['class' => 'rounded-2xl border border-zinc-800 bg-zinc-900/40']) }}
    x-data="{
        open: false,
        i: {{ $count - 1 }},
        count: {{ $count }},
        startX: null,
        toggle() {
            this.open = ! this.open;
            if (this.open) this.go(this.i);
        },
        go(n) {
            this.i = Math.max(0, Math.min(this.count - 1, n));
            this.$nextTick(() => this.$refs['chip' + this.i]?.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' }));
        },
        swipeStart(e) { this.startX = e.changedTouches[0].clientX; },
        swipeEnd(e) {
            if (this.startX === null) return;
            const dx = e.changedTouches[0].clientX - this.startX;
            this.startX = null;
            if (Math.abs(dx) > 50) this.go(this.i + (dx < 0 ? 1 : -1));
        },
    }"
>
    <button
        type="button"
        x-on:click="toggle()"
        x-bind:aria-expanded="open.toString()"
        class="flex w-full items-center justify-between gap-3 px-5 py-4 text-left"
    >
        <span class="flex items-baseline gap-3">
            <span class="font-display text-2xl tracking-wide text-zinc-100">{{ $title }}</span>
            <span class="text-xs text-zinc-500">{{ $total }} film{{ $total > 1 ? 's' : '' }} · {{ $count }} mois</span>
        </span>
        <svg viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5 shrink-0 text-zinc-500 transition-transform duration-200" x-bind:class="open ? 'rotate-180' : ''" aria-hidden="true"><path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" /></svg>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition.opacity.duration.200ms
        x-on:keydown.left="go(i - 1)"
        x-on:keydown.right="go(i + 1)"
        class="border-t border-zinc-800 px-5 pb-5 pt-4"
    >
        {{-- Sélecteur de mois --}}
        <div class="flex items-center gap-2">
            <button type="button" x-on:click="go(i - 1)" x-bind:disabled="i === 0" aria-label="Mois précédent" class="grid h-8 w-8 shrink-0 place-items-center rounded-full text-zinc-500 transition disabled:opacity-30 {{ $theme['arrow'] }}">
                <svg viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 0 1-.02 1.06L8.832 10l3.938 3.71a.75.75 0 1 1-1.04 1.08l-4.5-4.25a.75.75 0 0 1 0-1.08l4.5-4.25a.75.75 0 0 1 1.06.02Z" clip-rule="evenodd" /></svg>
            </button>

            <div class="flex min-w-0 flex-1 gap-2 overflow-x-auto py-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" role="tablist" aria-label="Mois">
                @foreach ($months as $month => $group)
                <button
                    type="button"
                    role="tab"
                    x-ref="chip{{ $loop->index }}"
                    x-on:click="go({{ $loop->index }})"
                    x-bind:aria-selected="(i === {{ $loop->index }}).toString()"
                    x-bind:class="i === {{ $loop->index }} ? '{{ $theme['chipOn'] }}' : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700 hover:text-zinc-200'"
                    class="shrink-0 rounded-full px-3.5 py-1.5 text-xs font-bold transition"
                >{{ $month }} <span class="opacity-60">· {{ $group->count() }}</span></button>
                @endforeach
            </div>

            <button type="button" x-on:click="go(i + 1)" x-bind:disabled="i === count - 1" aria-label="Mois suivant" class="grid h-8 w-8 shrink-0 place-items-center rounded-full text-zinc-500 transition disabled:opacity-30 {{ $theme['arrow'] }}">
                <svg viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.168 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd" /></svg>
            </button>
        </div>

        {{-- Films du mois sélectionné --}}
        <div class="mt-4" x-on:touchstart.passive="swipeStart($event)" x-on:touchend.passive="swipeEnd($event)">
            @foreach ($months as $month => $group)
            <div x-show="i === {{ $loop->index }}" @if (! $loop->last) x-cloak @endif role="tabpanel" aria-label="{{ $month }}">
                <h3 class="text-[11px] font-bold uppercase tracking-widest {{ $theme['title'] }}">{{ $month }}</h3>
                <div class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($group as $item)
                    <div class="flex gap-3 rounded-2xl border border-zinc-800 bg-zinc-900/80 p-3">
                        <div class="h-20 w-14 shrink-0 overflow-hidden rounded-lg bg-zinc-950">
                            @if ($item->poster_url)
                            <img src="{{ $item->poster_url }}" alt="Affiche de {{ $item->title }}" loading="lazy" class="h-full w-full object-cover">
                            @else
                            <div class="grid h-full w-full place-items-center text-[9px] text-zinc-700">N/A</div>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-zinc-100" title="{{ $item->title }}">{{ $item->title }}</p>
                            <p class="mt-0.5 text-xs text-zinc-500">{{ $datePrefix }}{{ $datePrefix !== '' ? ' ' : '' }}{{ $item->watched_at->translatedFormat('d F Y') }}</p>
                            @if ($item->personal_rating)
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
    </div>
</section>
@endif
