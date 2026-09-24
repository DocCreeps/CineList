<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `source` indique où un film est/sera regardé (utile dès l'ajout, y compris pour les films
     * "à voir"). `first_watched_source` est distinct : posé uniquement quand un film passe pour
     * la première fois par "à revoir", il garde le souvenir de où on l'a vu la toute première
     * fois, indépendamment de `source` qui peut continuer à évoluer par ailleurs.
     */
    public function up(): void
    {
        Schema::table('watchlist_items', function (Blueprint $table) {
            $table->string('first_watched_source', 20)->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('watchlist_items', function (Blueprint $table) {
            $table->dropColumn('first_watched_source');
        });
    }
};
