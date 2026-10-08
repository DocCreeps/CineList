<?php

namespace App\Actions\Admin;

use App\Actions\Stats\ComputeCommunityStats;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Suppression définitive d'un compte membre. Ses films (`watchlist_items`) partent avec lui via la
 * contrainte `cascadeOnDelete` sur `user_id`.
 *
 * Deux garde-fous : on ne peut pas supprimer son propre compte, ni le dernier compte
 * administrateur (l'espace d'administration deviendrait inaccessible). Vérifications et suppression
 * partagent une même transaction, avec les administrateurs verrouillés, pour que deux suppressions
 * simultanées ne puissent pas retirer ensemble les deux derniers admins.
 */
class DeleteMember
{
    /**
     * @return array{deleted: bool, message: string}
     */
    public function handle(int $memberId, User $actor): array
    {
        $result = DB::transaction(function () use ($memberId, $actor): array {
            $member = User::query()->lockForUpdate()->find($memberId);

            if (! $member) {
                return ['deleted' => false, 'message' => 'Ce membre n\'existe plus.'];
            }

            if ($member->is($actor)) {
                return ['deleted' => false, 'message' => 'Impossible de supprimer votre propre compte depuis cette page.'];
            }

            if ($member->isAdmin() && User::query()->where('is_admin', true)->lockForUpdate()->count() <= 1) {
                return ['deleted' => false, 'message' => 'Impossible de supprimer le dernier compte administrateur.'];
            }

            $memberName = $member->name;
            $member->delete();

            return ['deleted' => true, 'message' => "Membre « {$memberName} » supprimé."];
        });

        if ($result['deleted']) {
            // La suppression en cascade des films ne déclenche aucun événement Eloquent.
            ComputeCommunityStats::forget();
        }

        return $result;
    }
}
