<?php

namespace App\Actions\Watchlist;

use App\Models\Movie;
use App\Models\WatchlistItem;
use App\Support\Movies\FieldList;
use Illuminate\Database\Eloquent\Builder;

class FilterWatchlistItems
{
    public function __construct(private WatchlistStatusCounts $statusCounts) {}

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
            // Genre, réalisateur, studio, année et titre sont des champs du film (table `movies`).
            ->when($genreFilter !== '', fn ($q) => $q->whereMovie(fn (Builder $m) => $m->where('genre', 'like', '%'.$genreFilter.'%')))
            ->when($directorFilter !== '', fn ($q) => $q->whereMovie(fn (Builder $m) => $m->where('director', $directorFilter)))
            ->when($studioFilter !== '', fn ($q) => $q->whereMovie(fn (Builder $m) => $m->where('studio', 'like', '%'.$studioFilter.'%')))
            ->when($minYear !== null, fn ($q) => $q->whereMovie(fn (Builder $m) => $m->where('year', '>=', $minYear)))
            ->when($maxYear !== null, fn ($q) => $q->whereMovie(fn (Builder $m) => $m->where('year', '<=', $maxYear)))
            // La recherche porte sur le titre (film) OU sur la note privée du membre (item).
            ->when($searchQuery !== '', fn ($q) => $q->where(
                fn ($qq) => $qq->whereMovie(fn (Builder $m) => $m->where('title', 'like', '%'.$searchQuery.'%'))
                    ->orWhere('note', 'like', '%'.$searchQuery.'%')
            ))
            ->when($staleOnly, fn ($q) => $q->where('status', 'to_watch')->where('created_at', '<=', now()->subMonths(3)));

        match ($sortBy) {
            'added_desc' => $query->latest(),
            'year_desc' => $query->orderByMovie('year', 'desc'),
            'rating_desc' => $query->orderByMovie('imdb_rating', 'desc'),
            'alpha' => $query->orderByMovie('title'),
            default => $query->orderBy('priority')->latest(),
        };

        $items = $query->get();

        // "Vus" sortis de la grille principale par défaut, sauf filtre explicite sur ce statut.
        $watchedItems = collect();
        if (empty($statusFilter)) {
            $watchedItems = $items->where('status', 'watched')->values();
            $items = $items->reject(fn ($item) => $item->status === 'watched')->values();
        }

        // Grille scindée en deux sections, ordre du tri conservé.
        $toWatchItems = $items->where('status', 'to_watch')->values();
        $toRewatchItems = $items->where('status', 'to_rewatch')->values();

        $counts = $this->statusCounts->handle();
        $sourceCounts = WatchlistItem::query()->selectRaw('source, count(*) as total')->groupBy('source')->pluck('total', 'source');
        $staleCount = WatchlistItem::query()->where('status', 'to_watch')->where('created_at', '<=', now()->subMonths(3))->count();

        // Seules les colonnes utiles aux filtres, lues sur les fiches des films de la liste du
        // membre (le scope `owner` de WatchlistItem s'applique dans `whereHas`).
        $filterFields = Movie::query()->whereHas('watchlistItems')->get(['genre', 'director', 'studio']);

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
            // Valeurs distinctes sur toute la liste ; genre/studio sont des listes séparées
            // par des virgules, donc éclatés avant dédoublonnage.
            'genreOptions' => $filterFields->pluck('genre')->flatMap(fn ($g) => FieldList::split($g))->unique()->sort()->values(),
            'directorOptions' => $filterFields->pluck('director')->filter()->unique()->sort()->values(),
            'studioOptions' => $filterFields->pluck('studio')->flatMap(fn ($s) => FieldList::split($s))->unique()->sort()->values(),
        ];
    }
}
