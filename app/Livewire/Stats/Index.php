<?php

namespace App\Livewire\Stats;

use App\Actions\Watchlist\ComputeWatchlistStats;
use Livewire\Component;

class Index extends Component
{
    public function with(ComputeWatchlistStats $stats): array
    {
        return $stats->handle();
    }
}
