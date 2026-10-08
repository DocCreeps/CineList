<?php

namespace App\Mail;

use App\Models\InviteCode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Envoyé en file d'attente (ShouldQueue) : la page admin répond tout de suite, sans attendre le
 * serveur SMTP. Un `queue:work` (ou `composer dev`) doit tourner pour que le mail parte réellement.
 */
class InviteCodeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** Nombre d'essais et délais (secondes) entre deux essais, si le serveur SMTP est indisponible. */
    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120];

    public function __construct(public InviteCode $inviteCode)
    {
        // N'envoie qu'une fois la transaction éventuelle validée : le code existe bien en base.
        $this->afterCommit();
    }

    /** Appelée quand tous les essais ont échoué : trace l'échec sans journaliser le code d'invitation. */
    public function failed(Throwable $exception): void
    {
        Log::error('Invitation e-mail failed.', [
            'invite_code_id' => $this->inviteCode->getKey(),
            'message' => $exception->getMessage(),
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ton invitation pour '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.invite-code',
            with: [
                'code' => $this->inviteCode->code,
                'expiresAt' => $this->inviteCode->expires_at,
                'maxUses' => $this->inviteCode->max_uses,
                'registerUrl' => route('register'),
            ],
        );
    }
}
