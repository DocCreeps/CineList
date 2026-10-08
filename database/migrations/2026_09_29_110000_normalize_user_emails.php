<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Passe les adresses e-mail existantes en minuscules, sans espaces autour : c'est la forme que
 * User::email() écrit désormais, et celle que la connexion et « mot de passe oublié » recherchent.
 *
 * Refuse de s'exécuter si deux comptes ne diffèrent que par la casse (« Jean@x.fr » et « jean@x.fr ») :
 * les fusionner ou en supprimer un est une décision qui ne se prend pas en silence.
 */
return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('users')
            ->selectRaw('lower(trim(email)) as normalized, count(*) as total')
            ->groupByRaw('lower(trim(email))')
            ->havingRaw('count(*) > 1')
            ->pluck('normalized');

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException(
                'Plusieurs comptes ne diffèrent que par la casse de leur e-mail : '.$duplicates->implode(', ')
                .'. Fusionnez ou supprimez les doublons, puis relancez la migration.'
            );
        }

        DB::table('users')->update(['email' => DB::raw('lower(trim(email))')]);
    }

    /** Irréversible : la casse d'origine n'est pas conservée. */
    public function down(): void
    {
        //
    }
};
