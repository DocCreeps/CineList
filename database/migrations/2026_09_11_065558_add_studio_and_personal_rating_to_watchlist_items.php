<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Applique la migration.
     */
    public function up(): void
    {
        Schema::table('watchlist_items', function (Blueprint $table) {
            // Studio(s)/sociétés de production, séparés par des virgules comme `actors` —
            // servent à filtrer le tableau de bord depuis l'ajout de la recherche par studio.
            $table->string('studio')->nullable()->after('director');

            // Note personnelle de 1 à 5 étoiles, distincte de la colonne `note` en texte libre
            // et du `imdb_rating` de TMDB — renseignée une fois le film vu.
            $table->unsignedTinyInteger('personal_rating')->nullable()->after('note');
        });
    }

    /**
     * Annule la migration.
     */
    public function down(): void
    {
        Schema::table('watchlist_items', function (Blueprint $table) {
            $table->dropColumn(['studio', 'personal_rating']);
        });
    }
};
