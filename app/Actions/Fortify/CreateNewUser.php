<?php

namespace App\Actions\Fortify;

use App\Models\InviteCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Valide les données d'inscription et crée le compte.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => $this->passwordRules(),
            'invite_code' => ['required', 'string'],
        ])->validate();

        // Verrouille la ligne du code d'invitation le temps de la transaction (lockForUpdate) :
        // sans ça, deux inscriptions simultanées sur un code à usage unique (ou dont il ne reste
        // qu'une utilisation) peuvent toutes les deux passer la vérification de disponibilité
        // avant que l'une d'elles n'ait eu le temps d'incrémenter le compteur, et donc réussir
        // toutes les deux. La transaction (et, sur SQLite, la sérialisation des écritures propre
        // au moteur) garantit qu'une seule inscription passe par code disponible.
        return DB::transaction(function () use ($input) {
            $invite = InviteCode::query()
                ->available()
                ->where('code', $input['invite_code'])
                ->lockForUpdate()
                ->first();

            if (! $invite) {
                throw ValidationException::withMessages([
                    'invite_code' => "Ce code d'invitation est invalide ou a déjà été utilisé.",
                ]);
            }

            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'], // haché automatiquement (cast sur User::password)
            ]);

            $invite->recordUse($user);

            return $user;
        });
    }
}
