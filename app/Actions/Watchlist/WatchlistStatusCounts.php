<?php

namespace App\Actions\Watchlist;

use App\Models\WatchlistItem;

class WatchlistStatusCounts
{
    /**
     * Comptes calculés en SQL groupé plutôt qu'en chargeant chaque ligne
     * (`WatchlistItem::all()`), pour rester peu coûteux même quand la liste grandit.
     *
     * @return array{all: int, to_watch: int, watched: int, to_rewatch: int}
     */
    public function handle(): array
    {
        $counts = WatchlistItem::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'all' => $counts->sum(),
            'to_watch' => (int) $counts->get('to_watch', 0),
            'watched' => (int) $counts->get('watched', 0),
            'to_rewatch' => (int) $counts->get('to_rewatch', 0),
        ];
    }
}
