<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ManageAdminUser extends Command
{
    protected $signature = 'user:make-admin {email : E-mail du compte à modifier} {--revoke : Retire les droits admin au lieu de les accorder}';

    protected $description = "Accorde (ou retire) les droits d'administration à un compte existant";

    public function handle(): int
    {
        $email = $this->argument('email');

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $this->error("Aucun compte trouvé avec l'adresse {$email}.");

            return self::FAILURE;
        }

        $revoke = (bool) $this->option('revoke');
        $user->forceFill(['is_admin' => ! $revoke])->save();

        $this->info($revoke
            ? "{$user->email} n'est plus administrateur."
            : "{$user->email} est maintenant administrateur. Il peut générer des codes d'invitation depuis /admin/invitations.");

        return self::SUCCESS;
    }
}
