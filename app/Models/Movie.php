<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;

/**
 * Fiche d'un film, telle que TMDB la décrit : une seule ligne par `tmdb_id`, partagée par tous les
 * membres qui l'ont dans leur liste. Ne contient rien de personnel — statut, source, priorité, notes,
 * date et nombre de visionnages vivent dans WatchlistItem (une ligne par membre et par film).
 */
class Movie extends Model
{
    use HasFactory;

    protected $fillable = [
        'tmdb_id', 'title', 'year', 'poster_url', 'type', 'genre', 'director', 'actors', 'studio',
        'runtime', 'imdb_rating', 'plot', 'release_date',
    ];

    protected function casts(): array
    {
        return ['release_date' => 'date', 'imdb_rating' => 'decimal:1'];
    }

    /** @return HasMany<WatchlistItem, $this> */
    public function watchlistItems(): HasMany
    {
        return $this->hasMany(WatchlistItem::class);
    }

    /**
     * Crée la fiche d'un film à partir d'un résultat TMDB (TmdbClient::find() ou un résultat de
     * recherche), ou la met à jour si ce `tmdb_id` existe déjà — un film ajouté par un second membre
     * réutilise donc la même ligne.
     *
     * Seuls les champs renseignés écrasent l'existant : un résultat de recherche, plus pauvre qu'une
     * fiche complète, ne doit pas vider un réalisateur ou un synopsis déjà connus.
     *
     * @param  array<string, mixed>  $data  Doit contenir `tmdb_id` (les autres clés, ex. bande-annonce, sont ignorées).
     */
    public static function syncFromTmdb(array $data): self
    {
        $movie = static::firstOrNew(['tmdb_id' => (string) $data['tmdb_id']]);

        $attributes = array_filter(
            Arr::only($data, $movie->getFillable()),
            fn ($value) => $value !== null && $value !== '',
        );

        $movie->fill($attributes)->save();

        return $movie;
    }
}
