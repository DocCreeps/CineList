<?php

namespace App\Livewire\Admin;

use App\Actions\InviteCodes\GenerateInviteCode;
use App\Mail\InviteCodeMail;
use App\Models\InviteCode;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Invitations extends Component
{
    public string $email = '';
    public string $expiresInDays = '';
    // Vide = usage unique (comportement historique) ; "0" (via la case "illimité") = pas de limite.
    public string $maxUses = '1';
    public bool $unlimitedUses = false;
    public ?string $lastGeneratedCode = null;

    /** @return array<int, InviteCode> */
    public function getCodesProperty(): array
    {
        return InviteCode::query()
            ->with(['creator', 'redemptions.user'])
            ->latest()
            ->limit(100)
            ->get()
            ->all();
    }

    public function generate(GenerateInviteCode $action): void
    {
        $this->validate([
            'email' => ['nullable', 'email', 'max:255'],
            'expiresInDays' => ['nullable', 'integer', 'min:1', 'max:365'],
            'maxUses' => ['required_unless:unlimitedUses,true', 'nullable', 'integer', 'min:1', 'max:10000'],
        ]);

        // Un admin malveillant/compromis ne peut pas spammer l'envoi de mails :
        // 10 générations par minute maximum, par administrateur.
        $throttleKey = 'invite-generate:'.Auth::id();

        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            $this->addError('email', 'Trop de générations en peu de temps, réessaie dans une minute.');

            return;
        }

        RateLimiter::hit($throttleKey, 60);

        $invite = $action->handle(
            expiresInDays: $this->expiresInDays !== '' ? (int) $this->expiresInDays : null,
            maxUses: $this->unlimitedUses ? null : (int) $this->maxUses,
            sentTo: $this->email !== '' ? $this->email : null,
            creator: Auth::user(),
        );

        if ($invite->sent_to) {
            Mail::to($invite->sent_to)->send(new InviteCodeMail($invite));
            session()->flash('notice', "Code généré et envoyé par e-mail à {$invite->sent_to}.");
            $this->lastGeneratedCode = null;
        } else {
            session()->flash('notice', "Code généré : {$invite->code}");
            $this->lastGeneratedCode = $invite->code;
        }

        $this->reset(['email', 'expiresInDays', 'maxUses', 'unlimitedUses']);
        $this->maxUses = '1';
    }

    /** Suppression définitive : uniquement pour un code n'ayant jamais servi (rien à garder en historique). */
    public function revoke(int $inviteCodeId): void
    {
        $invite = InviteCode::query()->where('uses_count', 0)->find($inviteCodeId);

        if (! $invite) {
            return;
        }

        $invite->delete();

        session()->flash('notice', 'Code révoqué.');
    }

    /**
     * Désactive un code déjà partiellement utilisé (multi-usage) sans effacer son historique :
     * fige `max_uses` à son compteur actuel, ce qui le rend immédiatement épuisé/indisponible.
     */
    public function disable(int $inviteCodeId): void
    {
        $invite = InviteCode::query()->available()->where('uses_count', '>', 0)->find($inviteCodeId);

        if (! $invite) {
            return;
        }

        $invite->update(['max_uses' => $invite->uses_count]);

        session()->flash('notice', 'Code désactivé : il ne pourra plus être utilisé, mais son historique est conservé.');
    }
}
