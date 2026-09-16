<?php

namespace App\Mail;

use App\Models\InviteCode;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InviteCodeMail extends Mailable implements \Illuminate\Contracts\Queue\ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public InviteCode $inviteCode)
    {
        //
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
