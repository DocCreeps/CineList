<?php

namespace App\Livewire;

use App\Actions\Watchlist\WatchlistStatusCounts;
use App\Livewire\Concerns\InteractsWithMovies;
use App\Models\WatchlistItem;
use App\Services\TmdbClient;
use Livewire\Component;

class Home extends Component
{
    use InteractsWithMovies;

    public function with(TmdbClient $tmdb, WatchlistStatusCounts $statusCounts): array
    {
        $toWatch = WatchlistItem::query()->where('status', 'to_watch')->orderBy('priority')->latest()->limit(6)->get();

        // Films "cinéma" encore "à voir", croisés avec les sorties à venir de TMDB
        // (même fenêtre que la page /a-venir) pour n'afficher que ceux dont la date est confirmée.
        $watchlistCinemaIds = WatchlistItem::query()
            ->where('source', 'cinema')
            ->where('status', 'to_watch')
            ->pluck('tmdb_id');

        $upcomingInWatchlist = collect();
        if ($watchlistCinemaIds->isNotEmpty()) {
            $upcoming = $tmdb->upcomingFilms();
            $upcomingInWatchlist = collect($upcoming['results'])
                ->whereIn('tmdb_id', $watchlistCinemaIds->all())
                ->take(4)
                ->values();
        }

        return [
            'counts' => $statusCounts->handle(),
            'toWatch' => $toWatch,
            'upcomingInWatchlist' => $upcomingInWatchlist,
        ];
    }
}
