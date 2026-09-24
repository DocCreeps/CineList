<?php

namespace App\Livewire\Stats;

use App\Actions\Stats\ComputeCommunityStats;
use App\Actions\Watchlist\ComputeWatchlistStats;
use Livewire\Component;

class Index extends Component
{
    /**
     * Bilan personnel (`ComputeWatchlistStats`, scopé à l'utilisateur connecté) et bilan de la
     * communauté (`ComputeCommunityStats`, tous membres confondus, sans détail nominatif — ce
     * détail reste réservé à la page admin des membres). Les deux jeux de statistiques sont
     * imbriqués sous des clés distinctes pour éviter toute collision de nom (les deux calculent
     * par exemple un `topGenreCount`).
     */
    public function with(ComputeWatchlistStats $personalStats, ComputeCommunityStats $communityStats): array
    {
        return [
            ...$personalStats->handle(),
            'community' => $communityStats->handle(),
        ];
    }
}
