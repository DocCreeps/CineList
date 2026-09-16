<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WatchlistItem extends Model
{
    // `user_id` volontairement absent : il n'est posé que par le hook `creating` ci-dessous,
    // jamais via un payload en assignation de masse.
    protected $fillable = ['tmdb_id', 'title', 'year', 'poster_url', 'type', 'genre', 'director', 'actors', 'studio', 'runtime', 'imdb_rating', 'plot', 'status', 'source', 'priority', 'note', 'personal_rating', 'watched_at'];

    protected function casts(): array
    {
        return ['watched_at' => 'datetime', 'imdb_rating' => 'decimal:1'];
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
