<?php

namespace App\Actions\Demo;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Supprime les bacs à sable de la démo arrivés à expiration, avec leurs films (suppression en
 * cascade) et leurs sessions. Ne touche jamais au compte modèle ni aux vrais membres.
 */
class PurgeDemoSandboxes
{
    /** @return int Nombre de bacs à sable supprimés. */
    public function handle(): int
    {
        $purged = 0;

        User::query()
            ->demoSandboxes()
            ->where('demo_expires_at', '<', now())
            ->select('id')
            ->chunkById(100, function ($sandboxes) use (&$purged) {
                $ids = $sandboxes->pluck('id')->all();

                DB::table('sessions')->whereIn('user_id', $ids)->delete();
                $purged += User::query()->whereIn('id', $ids)->delete();
            });

        return $purged;
    }
}
