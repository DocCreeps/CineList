<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\WatchlistItem;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateDemoUser extends Command
{
    protected $signature = 'demo:create
        {--from= : E-mail d\'un compte existant dont la liste de films est copiée (sans les notes)}
        {--email=demo-modele@cinelist.invalid : Adresse du compte modèle (domaine .invalid : aucun mail ne peut y arriver)}
        {--refresh : Remplace la liste actuelle du compte modèle}';

    protected $description = 'Crée (ou met à jour) le compte modèle de la démo, copié pour chaque visiteur (bac à sable jetable)';

    public function handle(): int
    {
        $source = null;

        if ($from = $this->option('from')) {
            $source = User::query()->where('email', $from)->first();

            if (! $source) {
                $this->error("Aucun compte trouvé avec l'adresse {$from}.");

                return self::FAILURE;
            }
        }

        $demo = User::query()->demoTemplate()->first() ?? new User;

        // Mot de passe aléatoire jamais communiqué : personne ne se connecte jamais à ce compte, chaque
        // visiteur de la démo reçoit une copie jetable (voir App\Actions\Demo\CreateDemoSandbox).
        // is_admin / is_demo sont hors $fillable (voir User) : posés avec forceFill.
        $demo->forceFill([
            'name' => 'Compte démo (modèle)',
            'email' => $this->option('email'),
            'password' => Str::random(64),
            'email_verified_at' => now(),
            'is_admin' => false,
            'is_demo' => true,
            'demo_expires_at' => null,
        ])->save();

        if ($source) {
            $this->copyWatchlist($source, $demo);
        }

        $this->info("Compte modèle prêt ({$demo->email}). Mettez DEMO_ENABLED=true dans .env, puis créez un lien dans Admin → Liens démo.");

        return self::SUCCESS;
    }

    private function copyWatchlist(User $source, User $demo): void
    {
        $existing = WatchlistItem::withoutGlobalScope('owner')->where('user_id', $demo->id);

        if ($existing->exists()) {
            if (! $this->option('refresh')) {
                $this->warn('Le compte démo a déjà des films : ajoutez --refresh pour les remplacer.');

                return;
            }

            $existing->delete();
        }

        $count = 0;

        WatchlistItem::withoutGlobalScope('owner')
            ->where('user_id', $source->id)
            ->get()
            ->each(function (WatchlistItem $item) use ($demo, &$count) {
                // Les notes sont du texte libre privé : jamais copiées vers un compte public.
                $item->replicate(['note'])->forceFill(['user_id' => $demo->id])->save();
                $count++;
            });

        $this->info("{$count} film(s) copié(s) depuis {$source->email} (sans les notes).");
    }
}
