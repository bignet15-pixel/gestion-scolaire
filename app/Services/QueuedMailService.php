<?php

namespace App\Services;

use App\Mail\PasswordOtpMail;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class QueuedMailService
{
    /**
     * Tous les emails doivent passer par la queue.
     * Aucun fallback en envoi direct n'est volontairement prévu ici.
     */
    public function queue(string $email, Mailable $mailable): void
    {
        Mail::to($email)->queue($mailable->afterCommit()->onQueue('emails'));
    }

    public function envoyerOtpMotDePasse(User $user, string $code, int $expirationMinutes): void
    {
        if (! $user->email) {
            return;
        }

        $nomComplet = trim(($user->prenom ?? '').' '.($user->nom ?? ''));

        $this->queue(
            $user->email,
            new PasswordOtpMail($nomComplet !== '' ? $nomComplet : ($user->name ?? 'Parent'), $code, $expirationMinutes)
        );
    }
}
