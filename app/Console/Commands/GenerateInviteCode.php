<?php

namespace App\Console\Commands;

use App\Models\InviteCode;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateInviteCode extends Command
{
    protected $signature = 'invite:generate {--expires-in-days= : Nombre de jours avant expiration (illimité si omis)}';

    protected $description = "Génère un code d'invitation à usage unique pour l'inscription";

    public function handle(): int
    {
        $code = strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4));
        $expiresInDays = $this->option('expires-in-days');

        InviteCode::create([
            'code' => $code,
            'expires_at' => $expiresInDays ? now()->addDays((int) $expiresInDays) : null,
        ]);

        $this->info("Code d'invitation généré : {$code}");

        if ($expiresInDays) {
            $this->line('Expire le : '.now()->addDays((int) $expiresInDays)->format('d/m/Y'));
        }

        return self::SUCCESS;
    }
}
