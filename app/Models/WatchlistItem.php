<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WatchlistItem extends Model
{
    // `user_id` volontairement absent : il n'est posé que par le hook `creating` ci-dessous,
    // jamais via un payload en assignation de masse.
    protected $fillable = ['tmdb_id', 'title', 'year', 'poster_url', 'type', 'genre', 'director', 'actors', 'studio', 'runtime', 'imdb_rating', 'plot', 'status', 'source', 'first_watched_source', 'priority', 'note', 'personal_rating', 'watched_at', 'watch_count', 'release_date'];

    /**
     * Colonnes qu'un contexte admin (vue d'ensemble, détail d'un membre, statistiques de
     * communauté) a le droit de charger en désactivant le scope `owner`. `note` (texte libre
     * privé), `plot` et `actors` en sont volontairement absents : un admin voit ce que fait un
     * membre, pas ses annotations personnelles. Source unique de vérité — les actions admin
     * doivent s'appuyer dessus plutôt que retaper leur propre liste, pour qu'un futur champ
     * sensible ne soit exclu qu'à un seul endroit.
     */
    public const ADMIN_SAFE_COLUMNS = [
        'id', 'user_id', 'tmdb_id', 'title', 'year', 'poster_url', 'genre', 'director', 'studio',
        'runtime', 'imdb_rating', 'status', 'source', 'first_watched_source', 'priority', 'personal_rating', 'watched_at',
        'watch_count', 'release_date', 'created_at',
    ];

    protected function casts(): array
    {
        return ['watched_at' => 'datetime', 'release_date' => 'date', 'imdb_rating' => 'decimal:1', 'watch_count' => 'integer'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Chaque film appartient à un seul utilisateur. Un scope global limite automatiquement
     * toutes les requêtes existantes — recherche, tableau de bord, statistiques, accueil — aux
     * films de l'utilisateur connecté, sans avoir à les modifier une par une. `creating`
     * estampille les nouvelles lignes avec l'utilisateur courant.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('owner', function (Builder $query) {
            if (auth()->check()) {
                $query->where('watchlist_items.user_id', auth()->id());
            }
        });

        static::creating(function (self $item) {
            $item->user_id ??= auth()->id();
        });
    }
}
