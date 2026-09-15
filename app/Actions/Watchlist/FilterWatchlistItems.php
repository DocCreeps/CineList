<?php

namespace App\Actions\Watchlist;

use App\Models\WatchlistItem;

class FilterWatchlistItems
{
    /**
     * @param  array<int, string>  $statusFilter  Vide = pas de filtre (tous les statuts).
     * @param  array<int, string>  $sourceFilter  Vide = pas de filtre (toutes les sources).
     */
    public function handle(
        array $statusFilter = [],
        array $sourceFilter = [],
        string $genreFilter = '',
        string $directorFilter = '',
        string $studioFilter = '',
        ?int $minYear = null,
        ?int $maxYear = null,
        string $searchQuery = '',
        bool $staleOnly = false,
        string $sortBy = 'priority',
    ): array {
        $query = WatchlistItem::query()
            ->when(! empty($statusFilter), fn ($q) => $q->whereIn('status', $statusFilter))
            ->when(! empty($sourceFilter), fn ($q) => $q->whereIn('source', $sourceFilter))
            ->when($genreFilter !== '', fn ($q) => $q->where('genre', 'like', '%'.$genreFilter.'%'))
            ->when($directorFilter !== '', fn ($q) => $q->where('director', $directorFilter))
            ->when($studioFilter !== '', fn ($q) => $q->where('studio', 'like', '%'.$studioFilter.'%'))
            ->when($minYear !== null, fn ($q) => $q->where('year', '>=', $minYear))
            ->when($maxYear !== null, fn ($q) => $q->where('year', '<=', $maxYear))
            ->when($searchQuery !== '', fn ($q) => $q->where(
                fn ($qq) => $qq->where('title', 'like', '%'.$searchQuery.'%')
                    ->orWhere('note', 'like', '%'.$searchQuery.'%')
            ))
            ->when($staleOnly, fn ($q) => $q->where('status', 'to_watch')->where('created_at', '<=', now()->subMonths(3)));

        match ($sortBy) {
            'added_desc' => $query->latest(),
            'year_desc' => $query->orderByDesc('year'),
            'rating_desc' => $query->orderByDesc('imdb_rating'),
            'alpha' => $query->orderBy('title'),
            default => $query->orderBy('priority')->latest(),
        };

        $items = $query->get();

        // Par défaut, les films "vus" sont sortis de la grille principale et rangés dans une
        // section repliable à part — sauf si l'utilisateur a explicitement filtré sur "vu" via
        // les puces de statut, auquel cas c'est justement ce qu'il voulait voir.
        $watchedItems = collect();
        if (empty($statusFilter)) {
            $watchedItems = $items->where('status', 'watched')->values();
            $items = $items->reject(fn ($item) => $item->status === 'watched')->values();
        }

        // La grille principale est ensuite scindée en deux sections bien séparées — "à voir"
        // et "à revoir" — plutôt que de mélanger les deux statuts. L'ordre du tri ci-dessus
        // est conservé.
        $toWatchItems = $items->where('status', 'to_watch')->values();
        $toRewatchItems = $items->where('status', 'to_rewatch')->values();

        $counts = (new WatchlistStatusCounts)->handle();
        $sourceCounts = WatchlistItem::query()->selectRaw('source, count(*) as total')->groupBy('source')->pluck('total', 'source');
        $staleCount = WatchlistItem::query()->where('status', 'to_watch')->where('created_at', '<=', now()->subMonths(3))->count();

        // Seules les trois colonnes utiles aux listes déroulantes de filtre, plutôt que
        // d'hydrater des WatchlistItem complets (affiche, résumé...) juste pour lister des valeurs.
        $filterFields = WatchlistItem::query()->select(['genre', 'director', 'studio'])->get();

        return [
            'items' => $items,
            'toWatchItems' => $toWatchItems,
            'toRewatchItems' => $toRewatchItems,
            'watchedItems' => $watchedItems,
            'counts' => [
                ...$counts,
                'cinema' => (int) $sourceCounts->get('cinema', 0),
                'streaming' => (int) $sourceCounts->get('streaming', 0),
                'stale' => $staleCount,
            ],
            // Valeurs distinctes sur toute la liste (pas seulement le sous-ensemble filtré), pour
            // les listes déroulantes. `genre` et `studio` sont stockés en listes séparées par
            // virgule, donc éclatés d'abord ; `director` est déjà une valeur unique.
            'genreOptions' => $filterFields->pluck('genre')->flatMap(fn ($g) => array_map('trim', explode(',', (string) $g)))->filter()->unique()->sort()->values(),
            'directorOptions' => $filterFields->pluck('director')->filter()->unique()->sort()->values(),
            'studioOptions' => $filterFields->pluck('studio')->flatMap(fn ($s) => array_map('trim', explode(',', (string) $s)))->filter()->unique()->sort()->values(),
        ];
    }
}
