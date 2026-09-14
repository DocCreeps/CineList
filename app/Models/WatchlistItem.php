<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WatchlistItem extends Model
{
    // `user_id` is intentionally left out: it's only ever set by the `creating` hook below,
    // never through a mass-assigned payload.
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
     * Every film belongs to exactly one user. A global scope keeps every existing query —
     * search, dashboard, stats, home — automatically limited to the logged-in user's own films,
     * without having to touch each of them individually. `creating` stamps new rows with the
     * current user automatically.
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
