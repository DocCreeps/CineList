<?php

namespace App\Support\Movies;

use Illuminate\Support\Collection;

class Genres
{
    /**
     * Nombre de films par genre. Le champ `genre` d'un film est une liste séparée par des virgules
     * (« Action, Science-Fiction ») : un film compte donc une fois dans chacun de ses genres.
     * Trié du plus fréquent au moins fréquent.
     *
     * @param  Collection<int, \App\Models\WatchlistItem>  $items
     * @return Collection<string, int>
     */
    public static function count(Collection $items): Collection
    {
        return $items->pluck('genre')
            ->flatMap(fn ($genre) => array_map('trim', explode(',', (string) $genre)))
            ->filter()
            ->countBy()
            ->sortDesc();
    }
}
