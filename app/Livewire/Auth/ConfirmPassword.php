<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class ConfirmPassword extends Component
{
    public string $password = '';

    public function confirm(): void
    {
        $this->validate(['password' => ['required', 'string']]);

        if (! Hash::check($this->password, Auth::user()->password)) {
            $this->addError('password', 'Mot de passe incorrect.');

            return;
        }

        request()->session()->put('auth.password_confirmed_at', time());

        $this->redirect(
            redirect()->intended(route('settings.profile'))->getTargetUrl(),
            navigate: true
        );
    }
}
