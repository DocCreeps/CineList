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

        // Same outcome whether or not the address exists, so this screen can't be used to
        // check which e-mails have an account.
        $this->sent = true;
    }
}
