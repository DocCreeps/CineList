<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class ForgotPassword extends Component
{
    public string $email = '';
    public bool $sent = false;

    public function sendResetLink(): void
    {
        $this->validate(['email' => ['required', 'string', 'email']]);

        Password::sendResetLink(['email' => $this->email]);

        // Même résultat que l'adresse existe ou non : cet écran ne peut pas servir à
        // savoir quelles adresses e-mail ont un compte.
        $this->sent = true;
    }
}
