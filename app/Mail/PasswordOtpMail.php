<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nomComplet,
        public string $code,
        public int $expirationMinutes,
    ) {
        $this->onQueue('emails');
    }

    public function build(): self
    {
        return $this->subject('Code de réinitialisation du mot de passe')
            ->view('emails.auth.password-otp')
            ->with([
                'user' => (object) [
                    'name' => $this->nomComplet,
                    'nom' => $this->nomComplet,
                ],
                'code' => $this->code,
                'expirationMinutes' => $this->expirationMinutes,
            ]);
    }
}
