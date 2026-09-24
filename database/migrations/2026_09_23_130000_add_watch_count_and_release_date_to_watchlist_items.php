<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `watch_count` : nombre de fois où le film a été (re)vu. Incrémenté automatiquement à chaque
     * passage en "vu" (voir UpdateWatchlistItemStatus) et modifiable manuellement depuis la carte
     * film ; sert au classement "Le plus revu" et entre dans le score du "Film préféré" (voir
     * App\Support\Movies\Favorites::filmsForUser).
     *
     * `release_date` : date de sortie TMDB du film, posée à l'ajout (voir AddMovieToWatchlist).
     * Sert uniquement à repérer les films "à voir" pas encore sortis, pour le classement "Films
     * les plus attendus" du bilan collectif (voir App\Actions\Stats\ComputeCommunityStats).
     */
    public function up(): void
    {
        Schema::table('watchlist_items', function (Blueprint $table) {
            $table->unsignedInteger('watch_count')->default(0)->after('watched_at');
            $table->date('release_date')->nullable()->after('watch_count');
        });
    }

    public function down(): void
    {
        Schema::table('watchlist_items', function (Blueprint $table) {
            $table->dropColumn(['watch_count', 'release_date']);
        });
    }
};
