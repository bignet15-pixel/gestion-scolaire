<?php

namespace App\Mail;

use App\Models\Annonce;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AnnonceDetailMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Annonce $annonce
    ) {
    }

    public function build(): self
    {
        return $this->subject($this->annonce->titre.' - '.config('ecole.nom'))
            ->view('emails.notifications.annonce-detail')
            ->with([
                'annonce' => $this->annonce,
                'ecoleNom' => config('ecole.nom'),
            ]);
    }
}
