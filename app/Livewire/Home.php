<?php

namespace App\Livewire;

use App\Actions\Watchlist\WatchlistStatusCounts;
use App\Enums\WatchSource;
use App\Enums\WatchStatus;
use App\Livewire\Concerns\InteractsWithMovies;
use App\Models\WatchlistItem;
use App\Services\TmdbClient;
use App\Support\Movies\ReleaseWindow;
use Livewire\Component;

class Home extends Component
{
    use InteractsWithMovies;

    public function with(TmdbClient $tmdb, WatchlistStatusCounts $statusCounts): array
    {
        $toWatch = WatchlistItem::query()->where('status', WatchStatus::ToWatch->value)->orderBy('priority')->latest()->limit(6)->get();

        // Tous les films « cinéma » encore « à voir », avec leur date de sortie française. On
        // interroge TMDB film par film plutôt que de croiser avec la liste des sorties des 2
        // prochains mois : un film plus lointain (ou déjà à l'affiche) n'y figurait pas, et la liste
        // était en plus tronquée à 4 entrées.
        $cinemaItems = WatchlistItem::query()
            ->where('source', WatchSource::Cinema->value)
            ->where('status', WatchStatus::ToWatch->value)
            ->get();

        $releases = $cinemaItems->isEmpty()
            ? []
            : $tmdb->cinemaReleaseDates($cinemaItems->pluck('tmdb_id')->all());

        $today = now()->toDateString();

        $upcomingInWatchlist = $cinemaItems
            ->map(function (WatchlistItem $item) use ($releases, $today) {
                $release = $releases[$item->tmdb_id] ?? ['release_date' => null, 'confirmed' => false];
                $date = $release['release_date'];

                // Sorti depuis longtemps : ni « prochainement », ni encore à l'affiche.
                if ($date && ReleaseWindow::classify($date) === 'old') {
                    return null;
                }

                return [
                    'tmdb_id' => $item->tmdb_id,
                    'title' => $item->title,
                    'poster_url' => $item->poster_url,
                    'release_date' => $date,
                    'confirmed' => $release['confirmed'],
                    'is_out' => $release['confirmed'] && $date !== null && $date <= $today,
                ];
            })
            ->filter()
            // Déjà à l'affiche d'abord, puis par date croissante ; sans date connue en dernier.
            ->sortBy(fn (array $movie) => $movie['release_date'] ?? '9999-12-31')
            ->values();

        return [
            'counts' => $statusCounts->handle(),
            'toWatch' => $toWatch,
            'upcomingInWatchlist' => $upcomingInWatchlist,
        ];
    }
}
