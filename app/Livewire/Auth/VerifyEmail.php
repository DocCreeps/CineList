<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class VerifyEmail extends Component
{
    public bool $sent = false;

    public function resend(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectRoute('home', navigate: true);

            return;
        }

        Auth::user()->sendEmailVerificationNotification();
        $this->sent = true;
    }
}
