<?php

namespace App\Actions\InviteCodes;

use App\Models\InviteCode;
use App\Models\User;
use Illuminate\Support\Str;

class GenerateInviteCode
{
    /**
     * Génère un nouveau code d'invitation.
     *
     * @param  int|null  $expiresInDays  Nombre de jours avant expiration (illimité si null).
     * @param  int|null  $maxUses  Nombre d'inscriptions autorisées avec ce code (illimité si null, usage unique si 1).
     * @param  string|null  $sentTo  Adresse e-mail à laquelle le code sera envoyé (facultatif).
     * @param  User|null  $creator  Administrateur à l'origine du code (facultatif, pour la traçabilité).
     */
    public function handle(?int $expiresInDays = null, ?int $maxUses = 1, ?string $sentTo = null, ?User $creator = null): InviteCode
    {
        return InviteCode::create([
            'code' => $this->generateUniqueCode(),
            'expires_at' => $expiresInDays ? now()->addDays($expiresInDays) : null,
            'max_uses' => $maxUses,
            'sent_to' => $sentTo,
            'created_by' => $creator?->id,
        ]);
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4));
        } while (InviteCode::query()->where('code', $code)->exists());

        return $code;
    }
}
