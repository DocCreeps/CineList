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
        Schema::create('watchlist_items', function (Blueprint $table) {
            $table->id();
            $table->string('imdb_id')->unique();
            $table->string('title');
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('poster_url', 2048)->nullable();
            $table->string('type', 32)->default('movie');
            $table->string('genre')->nullable();
            $table->string('runtime', 32)->nullable();
            $table->decimal('imdb_rating', 3, 1)->nullable();
            $table->text('plot')->nullable();
            $table->string('status', 24)->default('to_watch');
            $table->string('source', 24)->default('cinema');
            $table->unsignedTinyInteger('priority')->default(2);
            $table->text('note')->nullable();
            $table->timestamp('watched_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'priority']);
        });
    }

    /**
     * Annule la migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('watchlist_items');
    }
};
