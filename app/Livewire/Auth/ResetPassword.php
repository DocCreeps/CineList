<?php

namespace App\Livewire\Auth;

use App\Actions\Fortify\PasswordValidationRules;
use Illuminate\Support\Facades\Password;
use Laravel\Fortify\Contracts\ResetsUserPasswords;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class ResetPassword extends Component
{
    use PasswordValidationRules;

    public string $token = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = (string) request()->query('email', '');
    }

    public function reset(ResetsUserPasswords $resetter): void
    {
        // La politique de mot de passe (12 caractères, majuscule, minuscule,
        // chiffre, caractère spécial) vient de Password::defaults(), définie
        // dans AppServiceProvider et appliquée via passwordRules() ci-dessous.
        $this->validate([
            'email' => ['required', 'string', 'email'],
            'password' => $this->passwordRules(),
        ]);

        $status = Password::reset(
            [
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'token' => $this->token,
            ],
            fn ($user) => $resetter->reset($user, [
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
            ])
        );

        if ($status !== Password::PASSWORD_RESET) {
            $this->addError('email', 'Ce lien de réinitialisation est invalide ou a expiré.');

            return;
        }

        session()->flash('notice', 'Mot de passe mis à jour, vous pouvez vous connecter.');
        $this->redirectRoute('login', navigate: true);
    }
}
