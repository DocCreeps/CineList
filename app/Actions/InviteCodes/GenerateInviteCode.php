<?php

namespace App\Actions\InviteCodes;

use App\Models\InviteCode;
use App\Models\User;
use Illuminate\Support\Str;

class GenerateInviteCode
{
    /**
     * Génère un nouveau code d'invitation à usage unique.
     *
     * @param  int|null  $expiresInDays  Nombre de jours avant expiration (illimité si null).
     * @param  string|null  $sentTo  Adresse e-mail à laquelle le code sera envoyé (facultatif).
     * @param  User|null  $creator  Administrateur à l'origine du code (facultatif, pour la traçabilité).
     */
    public function handle(?int $expiresInDays = null, ?string $sentTo = null, ?User $creator = null): InviteCode
    {
        return InviteCode::create([
            'code' => $this->generateUniqueCode(),
            'expires_at' => $expiresInDays ? now()->addDays($expiresInDays) : null,
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
