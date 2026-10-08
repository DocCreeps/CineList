<?php

namespace App\Actions\Demo;

use App\Models\User;
use App\Models\WatchlistItem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Crée un compte fictif jetable pour un visiteur de la démo : un nouvel utilisateur (jamais
 * admin, sans mot de passe utilisable) auquel on copie la liste de films du compte modèle.
 * Tout ce que le visiteur fait ensuite touche uniquement ce compte, supprimé à l'expiration.
 */
class CreateDemoSandbox
{
    public function handle(User $template): User
    {
        return DB::transaction(function () use ($template) {
            $sandbox = new User;
            $sandbox->forceFill([
                'name' => 'Visiteur démo',
                // Domaine .invalid : aucun mail ne peut jamais y être livré.
                'email' => 'demo-'.Str::lower(Str::random(16)).'@cinelist.invalid',
                'password' => Str::random(64),
                'email_verified_at' => now(),
                'is_admin' => false,
                'is_demo' => true,
                'demo_expires_at' => now()->addHours(max(1, (int) config('demo.sandbox_ttl_hours', 24))),
            ])->save();

            WatchlistItem::withoutGlobalScope('owner')
                ->where('user_id', $template->id)
                ->get()
                ->map(fn (WatchlistItem $item) => array_merge(Arr::except($item->getAttributes(), ['id']), ['user_id' => $sandbox->id]))
                ->chunk(200)
                ->each(fn ($rows) => WatchlistItem::withoutGlobalScope('owner')->insert($rows->all()));

            return $sandbox;
        });
    }
}
