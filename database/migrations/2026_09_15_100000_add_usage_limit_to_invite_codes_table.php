<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invite_codes', function (Blueprint $table) {
            // Nombre d'utilisations autorisées pour ce code. Null = illimité. Les codes déjà
            // existants (usage unique historique) sont explicitement fixés à 1 ci-dessous.
            $table->unsignedInteger('max_uses')->nullable()->after('expires_at');
            // Compteur d'utilisations réelles, incrémenté à chaque inscription réussie avec ce
            // code. Redondant avec le count() de `invite_code_redemptions` mais évité pour ne
            // pas avoir à la recompter à chaque affichage de la liste des codes.
            $table->unsignedInteger('uses_count')->default(0)->after('max_uses');
        });

        // Les codes déjà utilisés avant cette migration étaient à usage unique : on fige cette
        // règle explicitement plutôt que de les laisser à `max_uses = null` (illimité), ce qui
        // changerait leur comportement rétroactivement.
        DB::table('invite_codes')->update(['max_uses' => 1]);
        DB::table('invite_codes')->whereNotNull('used_at')->update(['uses_count' => 1]);

        Schema::create('invite_code_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invite_code_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        // Reprend l'historique des codes déjà utilisés (une seule redemption chacun, sur
        // l'unique compte qui les a consommés) pour que le détail "utilisé par" reste correct
        // pour les codes créés avant l'introduction du multi-usage.
        DB::table('invite_codes')
            ->whereNotNull('used_at')
            ->whereNotNull('used_by')
            ->get(['id', 'used_by', 'used_at'])
            ->each(function ($invite) {
                DB::table('invite_code_redemptions')->insert([
                    'invite_code_id' => $invite->id,
                    'user_id' => $invite->used_by,
                    'created_at' => $invite->used_at,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('invite_code_redemptions');

        Schema::table('invite_codes', function (Blueprint $table) {
            $table->dropColumn(['max_uses', 'uses_count']);
        });
    }
};
